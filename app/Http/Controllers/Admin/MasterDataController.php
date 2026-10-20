<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\IncidentLocation;
use App\Models\Level;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolId = $request->user()->school_id;

        return Inertia::render('Admin/MasterData/Index', [
            'academicYears' => AcademicYear::query()
                ->where('school_id', $schoolId)
                ->withCount('classrooms')
                ->orderByDesc('name')
                ->get()
                ->map(fn (AcademicYear $year) => [
                    'id' => $year->id,
                    'name' => $year->name,
                    'start_date' => $year->start_date?->toDateString(),
                    'end_date' => $year->end_date?->toDateString(),
                    'is_active' => $year->is_active,
                    'classrooms_count' => $year->classrooms_count,
                ]),
            'levels' => Level::query()
                ->where('school_id', $schoolId)
                ->withCount('classrooms')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (Level $level) => [
                    'id' => $level->id,
                    'code' => $level->code,
                    'name' => $level->name,
                    'sort_order' => $level->sort_order,
                    'is_active' => $level->is_active,
                    'classrooms_count' => $level->classrooms_count,
                ]),
            'classrooms' => Classroom::query()
                ->where('school_id', $schoolId)
                ->with(['homeroomTeacher:id,name', 'levelInfo:id,code,name', 'academicYear:id,name'])
                ->withCount('students')
                ->orderBy('name')
                ->get()
                ->map(fn (Classroom $classroom) => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'level' => $classroom->level,
                    'level_id' => $classroom->level_id,
                    'level_name' => $classroom->levelInfo?->name,
                    'academic_year' => $classroom->academic_year,
                    'academic_year_id' => $classroom->academic_year_id,
                    'academic_year_name' => $classroom->academicYear?->name,
                    'homeroom_teacher_id' => $classroom->homeroom_teacher_id,
                    'homeroom_teacher' => $classroom->homeroomTeacher?->name,
                    'is_active' => $classroom->is_active,
                    'students_count' => $classroom->students_count,
                ]),
            'dorms' => Dorm::query()
                ->where('school_id', $schoolId)
                ->with('wali:id,name')
                ->withCount('students')
                ->orderBy('name')
                ->get()
                ->map(fn (Dorm $dorm) => [
                    'id' => $dorm->id,
                    'name' => $dorm->name,
                    'gender' => $dorm->gender,
                    'capacity' => $dorm->capacity,
                    'wali_id' => $dorm->wali_id,
                    'wali' => $dorm->wali?->name,
                    'is_active' => $dorm->is_active,
                    'students_count' => $dorm->students_count,
                ]),
            'locations' => IncidentLocation::query()
                ->where('school_id', $schoolId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (IncidentLocation $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'sort_order' => $location->sort_order,
                    'is_active' => $location->is_active,
                ]),
            'teachers' => User::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->whereIn('role', ['guru', 'wali_kelas', 'wali_asrama', 'bk', 'tppk', 'kepala_sekolah'])
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'role_label' => $user->roleLabel(),
                ]),
            'options' => [
                'genders' => config('sigap.genders'),
            ],
        ]);
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $validated = $this->validateAcademicYear($request);
        $validated['school_id'] = $request->user()->school_id;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $year = DB::transaction(function () use ($validated, $request) {
            $hasActive = AcademicYear::query()
                ->where('school_id', $request->user()->school_id)
                ->where('is_active', true)
                ->exists();
            $validated['is_active'] = $validated['is_active'] || ! $hasActive;

            $year = AcademicYear::create($validated);

            if ($year->is_active) {
                $this->makeAcademicYearActive($year);
            }

            return $year;
        });

        AuditLogger::record('academic_year.created', $year, 'Tahun ajaran ditambahkan.');

        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->ensureSchool($request, $academicYear->school_id);
        $validated = $this->validateAcademicYear($request, $academicYear);
        $oldName = $academicYear->name;

        DB::transaction(function () use ($academicYear, $validated, $oldName) {
            $academicYear->update($validated);

            if ($oldName !== $academicYear->name) {
                Classroom::query()
                    ->where('academic_year_id', $academicYear->id)
                    ->update(['academic_year' => $academicYear->name]);
            }

            if ($academicYear->is_active) {
                $this->makeAcademicYearActive($academicYear);
            }
        });

        AuditLogger::record('academic_year.updated', $academicYear, 'Tahun ajaran diperbarui.');

        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function activateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->ensureSchool($request, $academicYear->school_id);

        DB::transaction(fn () => $this->makeAcademicYearActive($academicYear));

        AuditLogger::record('academic_year.activated', $academicYear, 'Tahun ajaran diaktifkan.');

        return back()->with('success', 'Tahun ajaran '.$academicYear->name.' diaktifkan.');
    }

    public function storeLevel(Request $request): RedirectResponse
    {
        $validated = $this->validateLevel($request);
        $validated['school_id'] = $request->user()->school_id;
        $validated['code'] = strtoupper($validated['code']);
        $validated['sort_order'] ??= (int) Level::where('school_id', $request->user()->school_id)->max('sort_order') + 1;

        $level = Level::create($validated);
        AuditLogger::record('level.created', $level, 'Jenjang ditambahkan.');

        return back()->with('success', 'Jenjang berhasil ditambahkan.');
    }

    public function updateLevel(Request $request, Level $level): RedirectResponse
    {
        $this->ensureSchool($request, $level->school_id);
        $validated = $this->validateLevel($request, $level);
        $validated['code'] = strtoupper($validated['code']);
        $oldCode = $level->code;

        DB::transaction(function () use ($level, $validated, $oldCode) {
            $level->update($validated);

            if ($oldCode !== $level->code) {
                Classroom::query()
                    ->where('level_id', $level->id)
                    ->update(['level' => $level->code]);
            }
        });

        AuditLogger::record('level.updated', $level, 'Jenjang diperbarui.');

        return back()->with('success', 'Jenjang berhasil diperbarui.');
    }

    public function storeClassroom(Request $request): RedirectResponse
    {
        $validated = $this->validateClassroom($request);
        $validated['school_id'] = $request->user()->school_id;
        $validated = $this->syncClassroomLabels($validated);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $classroom = Classroom::create($validated);
        AuditLogger::record('classroom.created', $classroom, 'Kelas ditambahkan.');

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClassroom(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->ensureSchool($request, $classroom->school_id);
        $validated = $this->validateClassroom($request, $classroom);
        $validated = $this->syncClassroomLabels($validated);

        $classroom->update($validated);
        AuditLogger::record('classroom.updated', $classroom, 'Kelas diperbarui.');

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function storeDorm(Request $request): RedirectResponse
    {
        $validated = $this->validateDorm($request);
        $validated['school_id'] = $request->user()->school_id;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $dorm = Dorm::create($validated);
        AuditLogger::record('dorm.created', $dorm, 'Asrama ditambahkan.');

        return back()->with('success', 'Asrama berhasil ditambahkan.');
    }

    public function updateDorm(Request $request, Dorm $dorm): RedirectResponse
    {
        $this->ensureSchool($request, $dorm->school_id);
        $validated = $this->validateDorm($request, $dorm);

        if ($validated['capacity'] < $dorm->students()->count()) {
            throw ValidationException::withMessages([
                'capacity' => 'Kapasitas tidak boleh lebih kecil dari jumlah siswa yang sudah ditempatkan ('.$dorm->students()->count().' siswa).',
            ]);
        }

        $dorm->update($validated);
        AuditLogger::record('dorm.updated', $dorm, 'Asrama diperbarui.');

        return back()->with('success', 'Asrama berhasil diperbarui.');
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $validated = $this->validateLocation($request);
        $validated['school_id'] = $request->user()->school_id;
        $validated['sort_order'] ??= (int) IncidentLocation::where('school_id', $request->user()->school_id)->max('sort_order') + 1;

        $location = IncidentLocation::create($validated);
        AuditLogger::record('incident_location.created', $location, 'Lokasi insiden ditambahkan.');

        return back()->with('success', 'Lokasi insiden berhasil ditambahkan.');
    }

    public function updateLocation(Request $request, IncidentLocation $location): RedirectResponse
    {
        $this->ensureSchool($request, $location->school_id);
        $validated = $this->validateLocation($request, $location);

        $location->update($validated);
        AuditLogger::record('incident_location.updated', $location, 'Lokasi insiden diperbarui.');

        return back()->with('success', 'Lokasi insiden berhasil diperbarui.');
    }

    private function makeAcademicYearActive(AcademicYear $year): void
    {
        AcademicYear::query()
            ->where('school_id', $year->school_id)
            ->whereKeyNot($year->id)
            ->update(['is_active' => false]);

        $year->update(['is_active' => true]);

        $school = $year->school;
        if ($school) {
            $school->update([
                'settings' => array_merge($school->settings ?? [], ['academic_year' => $year->name]),
            ]);
        }
    }

    private function validateAcademicYear(Request $request, ?AcademicYear $year = null): array
    {
        $schoolId = $request->user()->school_id;

        return $request->validate([
            'name' => [
                'required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('academic_years', 'name')->where('school_id', $schoolId)->ignore($year?->id),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ], [
            'name.regex' => 'Format tahun ajaran harus seperti 2026/2027.',
        ]);
    }

    private function validateLevel(Request $request, ?Level $level = null): array
    {
        $schoolId = $request->user()->school_id;

        return $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('levels', 'code')->where('school_id', $schoolId)->ignore($level?->id),
            ],
            'name' => ['required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
    }

    private function validateClassroom(Request $request, ?Classroom $classroom = null): array
    {
        $schoolId = $request->user()->school_id;

        return $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('classrooms', 'name')->where('school_id', $schoolId)->ignore($classroom?->id),
            ],
            'level_id' => ['required', Rule::exists('levels', 'id')->where('school_id', $schoolId)],
            'academic_year_id' => ['nullable', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'homeroom_teacher_id' => ['nullable', Rule::exists('users', 'id')->where('school_id', $schoolId)],
            'is_active' => ['boolean'],
        ]);
    }

    private function validateDorm(Request $request, ?Dorm $dorm = null): array
    {
        $schoolId = $request->user()->school_id;

        return $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('dorms', 'name')->where('school_id', $schoolId)->ignore($dorm?->id),
            ],
            'gender' => ['required', Rule::in(array_keys(config('sigap.genders')))],
            'capacity' => ['required', 'integer', 'between:1,200'],
            'wali_id' => ['nullable', Rule::exists('users', 'id')->where('school_id', $schoolId)],
            'is_active' => ['boolean'],
        ]);
    }

    private function validateLocation(Request $request, ?IncidentLocation $location = null): array
    {
        $schoolId = $request->user()->school_id;

        return $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('incident_locations', 'name')->where('school_id', $schoolId)->ignore($location?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
    }

    private function syncClassroomLabels(array $validated): array
    {
        $level = Level::query()->findOrFail($validated['level_id']);
        $validated['level'] = $level->code;

        if (! empty($validated['academic_year_id'])) {
            $year = AcademicYear::query()->find($validated['academic_year_id']);
            $validated['academic_year'] = $year?->name;
        } else {
            $validated['academic_year'] = null;
        }

        return $validated;
    }

    private function ensureSchool(Request $request, int $schoolId): void
    {
        abort_unless($schoolId === $request->user()->school_id, 404);
    }
}
