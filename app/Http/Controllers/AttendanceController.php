<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('homeroomTeacher:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'level', 'homeroom_teacher_id']);

        $dorms = Dorm::query()
            ->where('school_id', $schoolId)
            ->with('wali:id,name')
            ->when(! $user->isManagement(), fn ($query) => $query->whereIn('id', $user->dormAssignments()->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name', 'gender', 'wali_id']);

        $type = $request->string('type', 'kelas')->toString();
        if (! array_key_exists($type, config('sigap.attendance_types'))) {
            $type = 'kelas';
        }

        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $classroomId = $request->integer('classroom_id') ?: $classrooms->first()?->id;
        $dormId = $request->integer('dorm_id') ?: $dorms->first()?->id;

        if ($dormId !== null) {
            abort_unless($dorms->contains('id', $dormId), 403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        $isDormType = in_array($type, config('sigap.dorm_attendance_types'), true);

        $studentsQuery = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'aktif')
            ->with(['identity:id,student_id,full_name,photo_path', 'classroom:id,name', 'dorm:id,name'])
            ->orderBy('code');

        if ($isDormType) {
            $studentsQuery->where('dorm_id', $dormId);
        } else {
            $studentsQuery->where('classroom_id', $classroomId);
        }

        $students = $studentsQuery->get();

        $records = AttendanceRecord::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->where('date', $date)
            ->where('type', $type)
            ->get()
            ->keyBy('student_id');

        return Inertia::render('Attendance/Index', [
            'classrooms' => $classrooms,
            'dorms' => $dorms,
            'filters' => [
                'date' => $date,
                'type' => $type,
                'classroom_id' => $classroomId,
                'dorm_id' => $dormId,
            ],
            'isDormType' => $isDormType,
            'selectedClassroom' => $classrooms->firstWhere('id', $classroomId),
            'selectedDorm' => $dorms->firstWhere('id', $dormId),
            'students' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'code' => $student->code,
                'name' => $student->identity?->full_name ?? $student->code,
                'photo_url' => $student->photo_url,
                'classroom' => $student->classroom?->name,
                'dorm' => $student->dorm?->name,
                'status' => $records->get($student->id)?->status ?? 'hadir',
                'notes' => $records->get($student->id)?->notes,
                'saved' => $records->has($student->id),
            ]),
            'summary' => collect(config('sigap.attendance_statuses'))->map(fn ($label, $status) => [
                'status' => $status,
                'label' => $label,
                'count' => $records->where('status', $status)->count(),
            ])->values(),
            'dormTypes' => config('sigap.dorm_attendance_types'),
        ]);
    }

    public function store(Request $request, IncidentService $incidentService): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(config('sigap.attendance_types')))],
            'classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where('school_id', $user->school_id)],
            'dorm_id' => ['nullable', Rule::exists('dorms', 'id')->where('school_id', $user->school_id)],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.status' => ['required', Rule::in(array_keys(config('sigap.attendance_statuses')))],
            'records.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $isDormType = in_array($validated['type'], config('sigap.dorm_attendance_types'), true);
        if ($isDormType && empty($validated['dorm_id'])) {
            return back()->with('error', 'Asrama harus dipilih untuk presensi kegiatan asrama.');
        }
        if (! $isDormType && empty($validated['classroom_id'])) {
            return back()->with('error', 'Kelas harus dipilih untuk presensi kelas.');
        }
        if ($isDormType && ! $user->isManagement()) {
            abort_unless(
                $user->dormAssignments()->whereKey($validated['dorm_id'])->exists(),
                403,
                'Anda tidak memiliki akses ke asrama ini.'
            );
        }

        $groupStudentIds = Student::query()
            ->where('school_id', $user->school_id)
            ->when($isDormType, fn ($q) => $q->where('dorm_id', $validated['dorm_id']), fn ($q) => $q->where('classroom_id', $validated['classroom_id']))
            ->pluck('id')
            ->all();

        $invalid = collect($validated['records'])->pluck('student_id')->diff($groupStudentIds);
        if ($invalid->isNotEmpty()) {
            return back()->with('error', 'Ada siswa yang tidak termasuk dalam kelas atau asrama yang dipilih.');
        }

        $missing = [];
        $date = $validated['date'];

        DB::transaction(function () use ($validated, $user, $isDormType, $incidentService, &$missing, $date) {
            foreach ($validated['records'] as $record) {
                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $record['student_id'],
                        'date' => $date,
                        'type' => $validated['type'],
                    ],
                    [
                        'school_id' => $user->school_id,
                        'classroom_id' => $isDormType ? null : $validated['classroom_id'],
                        'dorm_id' => $isDormType ? $validated['dorm_id'] : null,
                        'status' => $record['status'],
                        'notes' => $record['notes'] ?? null,
                        'recorded_by' => $user->id,
                    ]
                );
            }

            if (! $isDormType) {
                return;
            }

            $missingCategory = IncidentCategory::query()
                ->where('school_id', $user->school_id)
                ->where('name', 'like', 'Kabur%')
                ->where('is_active', true)
                ->first();

            if (! $missingCategory) {
                return;
            }

            $absentIds = collect($validated['records'])
                ->where('status', 'alpa')
                ->pluck('student_id')
                ->unique();

            foreach ($absentIds as $studentId) {
                $alreadyReported = Incident::query()
                    ->where('school_id', $user->school_id)
                    ->where('category_id', $missingCategory->id)
                    ->whereDate('occurred_at', $date)
                    ->whereHas('students', fn ($q) => $q->where('students.id', $studentId))
                    ->exists();

                if ($alreadyReported) {
                    continue;
                }

                $incident = $incidentService->create([
                    'category_id' => $missingCategory->id,
                    'severity' => 3,
                    'occurred_at' => $date.' '.now()->format('H:i:s'),
                    'location' => 'Asrama',
                    'description' => 'Siswa tidak hadir pada kegiatan '.config('sigap.attendance_types.'.$validated['type']).' tanggal '.$date.' tanpa keterangan. Pemeriksaan lokasi dan pencarian dimulai sesuai SOP.',
                    'initial_action' => 'Pemeriksaan lokasi dan pencarian dimulai, wali asrama dan kepala sekolah diberi tahu.',
                    'needs_medical' => false,
                    'participants' => [
                        ['student_id' => $studentId, 'role' => 'pelapor'],
                    ],
                ], $user, $user->school);

                $missing[] = $incident->case_number;
            }
        });

        AuditLogger::record('attendance.saved', null, 'Presensi '.$validated['type'].' tanggal '.$date.' disimpan.');

        $message = 'Presensi berhasil disimpan.';
        if ($missing !== []) {
            $message .= ' Insiden tidak ditemukan otomatis dicatat: '.implode(', ', $missing).'.';
        }

        return redirect()
            ->route('attendance.index', [
                'date' => $date,
                'type' => $validated['type'],
                'classroom_id' => $validated['classroom_id'] ?? null,
                'dorm_id' => $validated['dorm_id'] ?? null,
            ])
            ->with('success', $message);
    }
}
