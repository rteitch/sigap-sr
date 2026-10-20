<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Dorm;
use App\Models\DormLog;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DormController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $dorms = Dorm::query()
            ->where('school_id', $user->school_id)
            ->with('wali:id,name')
            ->when(! $user->isManagement(), fn ($query) => $query->whereIn('id', $user->dormAssignments()->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name', 'gender', 'wali_id']);

        $dormId = $request->integer('dorm_id') ?: $dorms->first()?->id;
        $dorm = $dorms->firstWhere('id', $dormId);

        abort_if($dorm === null, 403, 'Anda tidak memiliki akses ke asrama ini.');

        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        $students = Student::query()
            ->where('school_id', $user->school_id)
            ->where('dorm_id', $dorm->id)
            ->where('status', 'aktif')
            ->with(['identity:id,student_id,full_name', 'classroom:id,name'])
            ->orderBy('code')
            ->get();

        $records = AttendanceRecord::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->where('date', $date)
            ->whereIn('type', config('sigap.dorm_attendance_types'))
            ->get()
            ->groupBy('student_id');

        $logs = DormLog::query()
            ->where('dorm_id', $dorm->id)
            ->where('log_date', $date)
            ->with('officer:id,name')
            ->orderByRaw("case shift when 'pagi' then 1 when 'siang' then 2 when 'malam' then 3 else 4 end")
            ->latest('id')
            ->get();

        $activitySummary = collect(config('sigap.dorm_attendance_types'))->map(function (string $type) use ($records) {
            $statuses = $records->map(fn ($items) => $items->firstWhere('type', $type)?->status);
            $present = $statuses->filter(fn ($status) => $status === 'hadir')->count();
            $absent = $statuses->filter(fn ($status) => $status === 'alpa')->count();
            $recorded = $statuses->filter()->count();

            return [
                'type' => $type,
                'label' => config('sigap.attendance_types.'.$type),
                'present' => $present,
                'absent' => $absent,
                'recorded' => $recorded,
            ];
        })->values();

        $missingStudents = $students
            ->filter(function (Student $student) use ($records) {
                return $records->get($student->id, collect())
                    ->whereIn('type', ['apel_malam', 'tidur'])
                    ->contains(fn ($record) => $record->status === 'alpa');
            })
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->identity?->full_name,
                'code' => $student->code,
            ])
            ->values();

        return Inertia::render('Dorm/Index', [
            'dorms' => $dorms,
            'filters' => [
                'dorm_id' => $dorm->id,
                'date' => $date,
            ],
            'dorm' => [
                'id' => $dorm->id,
                'name' => $dorm->name,
                'gender' => $dorm->gender,
                'wali' => $dorm->wali?->name,
            ],
            'students' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'code' => $student->code,
                'name' => $student->identity?->full_name ?? $student->code,
                'classroom' => $student->classroom?->name,
                'statuses' => collect(config('sigap.dorm_attendance_types'))->mapWithKeys(function (string $type) use ($records, $student) {
                    return [$type => $records->get($student->id, collect())->firstWhere('type', $type)?->status];
                }),
            ]),
            'activitySummary' => $activitySummary,
            'logs' => $logs->map(fn (DormLog $log) => [
                'id' => $log->id,
                'shift' => $log->shift,
                'officer' => $log->officer?->name,
                'checkpoints' => $log->checkpoints ?? [],
                'notes' => $log->notes,
                'handover_notes' => $log->handover_notes,
                'created_at' => $log->created_at?->toIso8601String(),
            ]),
            'missingStudents' => $missingStudents,
        ]);
    }

    public function storeLog(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'dorm_id' => ['required', Rule::exists('dorms', 'id')->where('school_id', $user->school_id)],
            'log_date' => ['required', 'date', 'before_or_equal:today'],
            'shift' => ['required', Rule::in(array_keys(config('sigap.dorm_shifts')))],
            'checkpoints' => ['array'],
            'checkpoints.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'handover_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (! $user->isManagement()) {
            abort_unless(
                $user->dormAssignments()->whereKey($validated['dorm_id'])->exists(),
                403,
                'Anda tidak memiliki akses ke asrama ini.'
            );
        }

        $log = DormLog::create([
            'school_id' => $user->school_id,
            'dorm_id' => $validated['dorm_id'],
            'log_date' => $validated['log_date'],
            'shift' => $validated['shift'],
            'officer_id' => $user->id,
            'checkpoints' => $validated['checkpoints'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'handover_notes' => $validated['handover_notes'] ?? null,
        ]);

        AuditLogger::record('dorm.log_created', $log, 'Logbook piket asrama ditambahkan ('.$validated['shift'].').');

        return redirect()
            ->route('dorm.index', ['dorm_id' => $validated['dorm_id'], 'date' => $validated['log_date']])
            ->with('success', 'Logbook piket berhasil disimpan.');
    }
}
