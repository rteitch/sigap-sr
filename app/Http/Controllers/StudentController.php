<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Dorm;
use App\Models\Incident;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['q', 'classroom_id', 'status']);

        $students = Student::query()
            ->visibleTo($user)
            ->with(['identity', 'classroom:id,name,level', 'dorm:id,name', 'familyContacts' => fn ($q) => $q->where('is_primary', true)])
            ->when($filters['q'] ?? null, function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('code', 'like', "%{$q}%")
                        ->orWhereHas('identity', fn ($identity) => $identity->where('full_name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['classroom_id'] ?? null, fn ($query, $classId) => $query->where('classroom_id', $classId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Student $student) => $this->studentSummary($student));

        return Inertia::render('Students/Index', [
            'students' => $students,
            'filters' => $filters,
            'classrooms' => Classroom::query()->where('school_id', $user->school_id)->orderBy('name')->get(['id', 'name', 'level']),
            'canManage' => $user->canManageStudents(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Students/Form', [
            'student' => null,
            'classrooms' => $this->classrooms($request),
            'dorms' => $this->dorms($request),
            'suggestedCode' => $this->nextCode($request->user()->school_id),
            'editableStatuses' => ['calon', 'aktif', 'cuti', 'lulus'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $this->validated($request, null);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('avatars', 'branding');
        }

        $autoGenerateCode = empty($data['code']);
        $maxAttempts = $autoGenerateCode ? 5 : 1;
        $student = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $code = $data['code'] ?: $this->nextCode($request->user()->school_id);

                $student = DB::transaction(function () use ($request, $data, $photoPath, $code) {
                    $student = Student::create([
                        'school_id' => $request->user()->school_id,
                        'classroom_id' => $data['classroom_id'] ?? null,
                        'dorm_id' => $data['dorm_id'] ?? null,
                        'code' => $code,
                        'gender' => $data['gender'],
                        'birth_date' => $data['birth_date'] ?? null,
                        'entry_date' => $data['entry_date'] ?? null,
                        'status' => $data['status'],
                        'special_needs' => $data['special_needs'] ?? null,
                        'socioeconomic_desil' => $data['socioeconomic_desil'] ?? null,
                        'consent_given_at' => $data['consent_given_at'] ?? null,
                        'consent_scope' => $data['consent_scope'] ?? null,
                        'consent_document' => $data['consent_document'] ?? null,
                        'consent_withdrawn_at' => $data['consent_withdrawn_at'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);

                    $student->identity()->create([
                        'full_name' => $data['full_name'],
                        'nickname' => $data['nickname'] ?? null,
                        'birth_place' => $data['birth_place'] ?? null,
                        'address' => $data['address'] ?? null,
                        'notes' => $data['identity_notes'] ?? null,
                        'photo_path' => $photoPath,
                    ]);

                    $this->syncContacts($student, $data['contacts'] ?? []);

                    return $student;
                });

                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                usleep(20000);
            }
        }

        AuditLogger::record('student.created', $student, 'Data siswa baru ditambahkan.');

        return redirect()->route('students.show', $student)->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function show(Request $request, Student $student): Response
    {
        abort_unless($student->school_id === $request->user()->school_id, 404);
        abort_unless($request->user()->canViewStudentIdentity($student), 403, 'Anda tidak memiliki akses ke profil siswa ini.');

        $student->load(['identity', 'classroom.homeroomTeacher', 'dorm.wali', 'familyContacts']);
        abort_unless($student->identity !== null, 404);

        $attendanceSummary = $student->attendances()
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $attendanceRecent = $student->attendances()
            ->latest('date')
            ->limit(10)
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'date' => (string) $record->date,
                'type' => $record->type,
                'status' => $record->status,
                'notes' => $record->notes,
            ]);

        $incidentQuery = Incident::query()
            ->visibleTo($request->user())
            ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
            ->with(['category:id,name'])
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'case_number' => $incident->case_number,
                'category' => $incident->category?->name,
                'severity' => $incident->severity,
                'status' => $incident->status,
                'confidentiality' => $incident->confidentiality,
                'occurred_at' => $incident->occurred_at->toIso8601String(),
                'role' => $incident->students->firstWhere('id', $student->id)?->pivot?->role,
            ]);

        AuditLogger::record('student.viewed', $student, 'Profil siswa dibuka.');

        return Inertia::render('Students/Show', [
            'student' => [
                ...$this->studentDetail($student),
                'consent' => [
                    'given_at' => $student->consent_given_at?->toIso8601String(),
                    'scope' => $student->consent_scope,
                    'document' => $student->consent_document,
                    'withdrawn_at' => $student->consent_withdrawn_at?->toIso8601String(),
                    'active' => $student->hasActiveConsent(),
                ],
                'special_needs' => $student->special_needs,
                'socioeconomic_desil' => $request->user()->isHeadmaster() || $request->user()->isAdmin()
                    ? $student->socioeconomic_desil
                    : null,
            ],
            'attendance' => [
                'summary' => $attendanceSummary,
                'recent' => $attendanceRecent,
            ],
            'incidents' => $incidentQuery,
            'canManage' => $request->user()->canManageStudents(),
        ]);
    }

    public function edit(Request $request, Student $student): Response
    {
        $this->authorizeManage($request);
        abort_unless($student->school_id === $request->user()->school_id, 404);
        $student->load(['identity', 'familyContacts']);

        return Inertia::render('Students/Form', [
            'student' => [
                ...$this->studentDetail($student),
                'nickname' => $student->identity?->nickname,
                'birth_place' => $student->identity?->birth_place,
                'address' => $student->identity?->address,
                'identity_notes' => $student->identity?->notes,
                'special_needs' => $student->special_needs,
                'socioeconomic_desil' => $student->socioeconomic_desil,
                'contacts' => $student->familyContacts->map(fn ($contact) => [
                    'name' => $contact->name,
                    'relation' => $contact->relation,
                    'phone' => $contact->phone,
                    'is_primary' => $contact->is_primary,
                    'can_pickup' => $contact->can_pickup,
                ]),
                'consent_given_at' => $student->consent_given_at?->format('Y-m-d'),
                'consent_scope' => $student->consent_scope,
                'consent_document' => $student->consent_document,
                'consent_withdrawn_at' => $student->consent_withdrawn_at?->format('Y-m-d'),
            ],
            'classrooms' => $this->classrooms($request),
            'dorms' => $this->dorms($request),
            'suggestedCode' => $student->code,
            'editableStatuses' => ['calon', 'aktif', 'cuti', 'lulus'],
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($student->school_id === $request->user()->school_id, 404);

        $data = $this->validated($request, $student);

        if (in_array($data['status'], ['pindah', 'mundur'], true) && $student->status !== $data['status']) {
            return back()->withErrors([
                'status' => 'Status pindah atau mundur hanya dapat diubah melalui wizard keluar (M10). Jalur ini belum termasuk MVP.',
            ])->withInput();
        }

        $student->loadMissing('identity');
        $oldPhoto = $student->identity?->photo_path;
        $photoPath = $oldPhoto;

        if ($request->boolean('remove_photo') && $oldPhoto) {
            Storage::disk('branding')->delete($oldPhoto);
            $photoPath = null;
        }

        if ($request->hasFile('photo')) {
            $newPath = $request->file('photo')->store('avatars', 'branding');
            if ($newPath !== false) {
                if ($oldPhoto) {
                    Storage::disk('branding')->delete($oldPhoto);
                }
                $photoPath = $newPath;
            }
        }

        DB::transaction(function () use ($student, $data, $photoPath) {
            $student->update([
                'classroom_id' => $data['classroom_id'] ?? null,
                'dorm_id' => $data['dorm_id'] ?? null,
                'code' => ! empty($data['code']) ? $data['code'] : $student->code,
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'] ?? null,
                'entry_date' => $data['entry_date'] ?? null,
                'status' => $data['status'],
                'special_needs' => $data['special_needs'] ?? null,
                'socioeconomic_desil' => $data['socioeconomic_desil'] ?? null,
                'consent_given_at' => $data['consent_given_at'] ?? null,
                'consent_scope' => $data['consent_scope'] ?? null,
                'consent_document' => $data['consent_document'] ?? null,
                'consent_withdrawn_at' => $data['consent_withdrawn_at'] ?? null,
            ]);

            $student->identity()->updateOrCreate(
                ['student_id' => $student->id],
                [
                    'full_name' => $data['full_name'],
                    'nickname' => $data['nickname'] ?? null,
                    'birth_place' => $data['birth_place'] ?? null,
                    'address' => $data['address'] ?? null,
                    'notes' => $data['identity_notes'] ?? null,
                    'photo_path' => $photoPath,
                ]
            );

            $this->syncContacts($student, $data['contacts'] ?? []);
        });

        AuditLogger::record('student.updated', $student, 'Data siswa diperbarui.');

        return redirect()->route('students.show', $student)->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function importForm(Request $request): Response
    {
        $this->authorizeManage($request);

        $rows = $request->session()->get('student_import_rows', []);

        return Inertia::render('Students/Import', [
            'preview' => $rows === [] ? null : [
                'rows' => $rows,
                'valid_count' => collect($rows)->where('errors', [])->count(),
                'invalid_count' => collect($rows)->filter(fn ($row) => $row['errors'] !== [])->count(),
            ],
        ]);
    }

    public function importPreview(Request $request): Response|RedirectResponse
    {
        $this->authorizeManage($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:4096'],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Berkas tidak dapat dibaca. Pastikan format sesuai templat.');
        }

        if (count($rows) < 2) {
            return back()->with('error', 'Berkas kosong atau tidak memiliki baris data.');
        }

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), array_shift($rows));
        $required = ['kode', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'kelas', 'asrama', 'status'];
        $missing = array_diff($required, $headers);

        if ($missing !== []) {
            return back()->with('error', 'Kolom wajib tidak ditemukan: '.implode(', ', $missing).'.');
        }

        $classrooms = Classroom::query()->where('school_id', $request->user()->school_id)->get()->keyBy(fn ($class) => strtolower($class->name));
        $dorms = Dorm::query()->where('school_id', $request->user()->school_id)->get()->keyBy(fn ($dorm) => strtolower($dorm->name));
        $existingCodes = Student::query()->where('school_id', $request->user()->school_id)->pluck('code')->map(fn ($code) => strtolower($code))->all();

        $preview = [];
        foreach ($rows as $index => $row) {
            $data = array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null));
            if (collect($data)->filter(fn ($value) => filled($value))->isEmpty()) {
                continue;
            }

            $birthDate = $this->normalizeDate($data['tanggal_lahir'] ?? null);
            $classKey = strtolower(trim((string) ($data['kelas'] ?? '')));
            $dormKey = strtolower(trim((string) ($data['asrama'] ?? '')));
            $gender = strtoupper(trim((string) ($data['jenis_kelamin'] ?? '')));
            $status = strtolower(trim((string) ($data['status'] ?? 'aktif')));

            $rowData = [
                'row' => $index + 2,
                'code' => trim((string) ($data['kode'] ?? '')),
                'full_name' => trim((string) ($data['nama'] ?? '')),
                'gender' => $gender,
                'birth_date' => $birthDate,
                'classroom_name' => $data['kelas'] ?? null,
                'dorm_name' => $data['asrama'] ?? null,
                'classroom_id' => $classrooms->get($classKey)?->id,
                'dorm_id' => $dorms->get($dormKey)?->id,
                'status' => $status,
            ];

            $errors = [];
            if ($rowData['code'] === '') {
                $errors[] = 'Kode wajib diisi.';
            } elseif (in_array(strtolower($rowData['code']), $existingCodes, true)) {
                $errors[] = 'Kode sudah digunakan.';
            }
            if ($rowData['full_name'] === '') {
                $errors[] = 'Nama wajib diisi.';
            }
            if (! in_array($gender, ['L', 'P'], true)) {
                $errors[] = 'Jenis kelamin harus L atau P.';
            }
            if ($birthDate === null) {
                $errors[] = 'Tanggal lahir tidak valid (gunakan YYYY-MM-DD).';
            }
            if (! $rowData['classroom_id']) {
                $errors[] = 'Kelas tidak ditemukan.';
            }
            if (! $rowData['dorm_id']) {
                $errors[] = 'Asrama tidak ditemukan.';
            }
            if (! array_key_exists($status, config('sigap.student_statuses'))) {
                $errors[] = 'Status tidak dikenal.';
            }

            $rowData['errors'] = $errors;
            $preview[] = $rowData;
        }

        $request->session()->put('student_import_rows', $preview);

        return redirect()->route('students.import');
    }

    public function importConfirm(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $rows = $request->session()->pull('student_import_rows', []);
        if ($rows === []) {
            return redirect()->route('students.import')->with('error', 'Tidak ada data pratinjau. Silakan unggah berkas kembali.');
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $request, &$imported, &$skipped) {
            foreach ($rows as $row) {
                if ($row['errors'] !== [] || Student::where('code', $row['code'])->exists()) {
                    $skipped++;

                    continue;
                }

                $student = Student::create([
                    'school_id' => $request->user()->school_id,
                    'classroom_id' => $row['classroom_id'],
                    'dorm_id' => $row['dorm_id'],
                    'code' => $row['code'],
                    'gender' => $row['gender'],
                    'birth_date' => $row['birth_date'],
                    'entry_date' => now()->toDateString(),
                    'status' => $row['status'],
                    'consent_given_at' => now(),
                    'consent_scope' => 'Impor data awal sekolah.',
                    'consent_document' => 'Berkas impor operator',
                    'created_by' => $request->user()->id,
                ]);

                $student->identity()->create(['full_name' => $row['full_name']]);
                $imported++;
            }
        });

        AuditLogger::record('student.imported', null, "Impor siswa: {$imported} berhasil, {$skipped} dilewati.");

        return redirect()->route('students.index')->with('success', "Impor selesai: {$imported} siswa ditambahkan, {$skipped} dilewati.");
    }

    public function template(Request $request): StreamedResponse
    {
        $this->authorizeManage($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['kode', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'kelas', 'asrama', 'status'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            ['SR-2026-9001', 'Contoh Nama Siswa', 'P', '2012-05-17', '7A', 'Melati', 'aktif'],
        ], null, 'A2');
        $sheet->setTitle('Siswa');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'templat-impor-siswa-sigap-sr.xlsx');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $students = Student::query()
            ->visibleTo($request->user())
            ->with(['identity', 'classroom:id,name', 'dorm:id,name'])
            ->orderBy('code')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['kode', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'kelas', 'asrama', 'status', 'persetujuan_aktif'],
        ], null, 'A1');

        $row = 2;
        foreach ($students as $student) {
            $sheet->fromArray([[
                $student->code,
                $student->identity?->full_name,
                $student->gender,
                $student->birth_date?->toDateString(),
                $student->classroom?->name,
                $student->dorm?->name,
                $student->status,
                $student->hasActiveConsent() ? 'ya' : 'tidak',
            ]], null, 'A'.$row);
            $row++;
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        AuditLogger::record('student.exported', null, 'Ekspor data siswa. Alasan: '.$validated['reason']);

        return $this->downloadSpreadsheet($spreadsheet, 'data-siswa-sigap-sr-'.now()->format('Ymd-His').'.xlsx');
    }

    private function validated(Request $request, ?Student $student): array
    {
        $schoolId = $request->user()->school_id;

        return $request->validate([
            'code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('students', 'code')->ignore($student?->id),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'entry_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(config('sigap.student_statuses')))],
            'classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'dorm_id' => ['nullable', Rule::exists('dorms', 'id')->where('school_id', $schoolId)],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            'socioeconomic_desil' => ['nullable', 'integer', 'between:1,10'],
            'address' => ['nullable', 'string', 'max:1000'],
            'identity_notes' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'consent_given_at' => ['nullable', 'date'],
            'consent_scope' => ['nullable', 'string', 'max:255'],
            'consent_document' => ['nullable', 'string', 'max:255'],
            'consent_withdrawn_at' => ['nullable', 'date'],
            'contacts' => ['array', 'max:5'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.relation' => ['nullable', 'string', 'max:60'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.is_primary' => ['boolean'],
            'contacts.*.can_pickup' => ['boolean'],
        ]);
    }

    private function syncContacts(Student $student, array $contacts): void
    {
        $student->familyContacts()->delete();

        foreach ($contacts as $index => $contact) {
            if (blank($contact['name'] ?? null)) {
                continue;
            }

            $student->familyContacts()->create([
                'name' => $contact['name'],
                'relation' => $contact['relation'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'is_primary' => (bool) ($contact['is_primary'] ?? $index === 0),
                'can_pickup' => (bool) ($contact['can_pickup'] ?? false),
            ]);
        }
    }

    private function nextCode(int $schoolId): string
    {
        $prefix = 'SR-'.now()->year.'-';
        $last = Student::query()
            ->where('school_id', $schoolId)
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');
        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        do {
            $code = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Student::where('code', $code)->exists());

        return $code;
    }

    private function classrooms(Request $request): array
    {
        return Classroom::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['homeroomTeacher:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'level' => $c->level,
                'homeroom_teacher' => $c->homeroomTeacher?->name,
            ])
            ->toArray();
    }

    private function dorms(Request $request): array
    {
        return Dorm::query()
            ->where('school_id', $request->user()->school_id)
            ->with(['wali:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Dorm $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'gender' => $d->gender,
                'wali_name' => $d->wali?->name,
            ])
            ->toArray();
    }

    private function studentSummary(Student $student): array
    {
        return [
            'id' => $student->id,
            'code' => $student->code,
            'name' => $student->identity?->full_name,
            'photo_url' => $student->photo_url,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date?->toDateString(),
            'age' => $student->age,
            'classroom' => $student->classroom?->name,
            'level' => $student->classroom?->level,
            'dorm' => $student->dorm?->name,
            'status' => $student->status,
            'consent_active' => $student->hasActiveConsent(),
            'primary_contact' => $student->familyContacts->first()?->only(['name', 'phone', 'relation']),
        ];
    }

    private function studentDetail(Student $student): array
    {
        return [
            'id' => $student->id,
            'code' => $student->code,
            'full_name' => $student->identity?->full_name,
            'nickname' => $student->identity?->nickname,
            'photo_url' => $student->photo_url,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date?->toDateString(),
            'birth_place' => $student->identity?->birth_place,
            'age' => $student->age,
            'address' => $student->identity?->address,
            'identity_notes' => $student->identity?->notes,
            'entry_date' => $student->entry_date?->toDateString(),
            'status' => $student->status,
            'classroom_id' => $student->classroom_id,
            'classroom' => $student->classroom?->name,
            'homeroom_teacher' => $student->classroom?->homeroomTeacher?->name,
            'dorm_id' => $student->dorm_id,
            'dorm' => $student->dorm?->name,
            'dorm_wali' => $student->dorm?->wali?->name,
            'contacts' => $student->familyContacts->map(fn ($contact) => [
                'name' => $contact->name,
                'relation' => $contact->relation,
                'phone' => $contact->phone,
                'is_primary' => $contact->is_primary,
                'can_pickup' => $contact->can_pickup,
            ]),
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'sigap-sr-');
        $path = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);
        (new Xlsx($spreadsheet))->save($path);

        return response()->streamDownload(function () use ($path) {
            readfile($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->canManageStudents(), 403, 'Hanya administrator atau kepala sekolah yang dapat mengelola data siswa.');
    }
}
