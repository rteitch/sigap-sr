<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\IncidentService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['q', 'status', 'severity', 'category_id', 'overdue']);

        $query = Incident::query()
            ->visibleTo($user)
            ->with(['category:id,name,is_positive', 'assignee:id,name,role'])
            ->when($filters['q'] ?? null, function ($query, string $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('case_number', 'like', "%{$q}%")
                        ->orWhereHas('students.identity', fn ($identity) => $identity->where('full_name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(($filters['overdue'] ?? null) === '1', fn ($query) => $query->open()->where('due_at', '<', now()));

        $incidents = $query
            ->withCount('participants')
            ->latest('occurred_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Incident $incident) => [
                'id' => $incident->id,
                'case_number' => $incident->case_number,
                'category' => $incident->category?->name,
                'is_positive' => (bool) $incident->category?->is_positive,
                'severity' => $incident->severity,
                'status' => $incident->status,
                'confidentiality' => $incident->confidentiality,
                'occurred_at' => $incident->occurred_at->toIso8601String(),
                'location' => $incident->location,
                'assignee' => $incident->assignee?->name,
                'assignee_role' => $incident->assignee?->roleLabel(),
                'participants_count' => $incident->participants_count,
                'overdue' => $incident->isOverdue(),
            ]);

        $visible = Incident::query()->visibleTo($user)->whereHas('category', fn ($q) => $q->where('is_positive', false));

        return Inertia::render('Incidents/Index', [
            'incidents' => $incidents,
            'filters' => $filters,
            'categories' => IncidentCategory::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $user->school_id))
                ->orderBy('sort_order')
                ->get(['id', 'name']),
            'stats' => [
                'open' => (clone $visible)->open()->count(),
                'overdue' => (clone $visible)->open()->where('due_at', '<', now())->count(),
                'urgent' => (clone $visible)->open()->where('severity', '>=', 3)->count(),
                'this_month' => (clone $visible)->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Incidents/Create', [
            'categories' => IncidentCategory::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $user->school_id))
                ->orderBy('sort_order')
                ->get(['id', 'name', 'default_confidentiality', 'is_positive']),
            'students' => Student::query()
                ->where('school_id', $user->school_id)
                ->where('status', 'aktif')
                ->with(['identity:id,student_id,full_name', 'classroom:id,name', 'dorm:id,name'])
                ->orderBy('code')
                ->get()
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'code' => $student->code,
                    'name' => $student->identity?->full_name ?? $student->code,
                    'classroom' => $student->classroom?->name,
                    'dorm' => $student->dorm?->name,
                ]),
            'locations' => IncidentLocation::query()
                ->where('school_id', $user->school_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'prefillStudentId' => $request->integer('student_id') ?: null,
        ]);
    }

    public function store(Request $request, IncidentService $incidentService): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => ['required', Rule::exists('incident_categories', 'id')->where('is_active', true)],
            'severity' => ['required', 'integer', 'between:1,4'],
            'occurred_at' => ['required', 'date', 'before_or_equal:'.now()->addHour()->format('Y-m-d H:i:s')],
            'location_id' => ['nullable', 'integer', Rule::exists('incident_locations', 'id')->where('school_id', $user->school_id)->where('is_active', true)],
            'location' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'initial_action' => ['nullable', 'string', 'max:3000'],
            'needs_medical' => ['boolean'],
            'participants' => ['required', 'array', 'min:1', 'max:20'],
            'participants.*.student_id' => ['required', 'integer', 'distinct', Rule::exists('students', 'id')->where('school_id', $user->school_id)],
            'participants.*.role' => ['required', Rule::in(array_keys(config('sigap.incident_roles')))],
            'participants.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $location = isset($validated['location_id'])
            ? IncidentLocation::query()
                ->where('school_id', $user->school_id)
                ->where('is_active', true)
                ->find($validated['location_id'])
            : null;

        if (! $location && blank($validated['location'] ?? null)) {
            throw ValidationException::withMessages([
                'location_id' => 'Pilih lokasi insiden yang terdaftar.',
            ]);
        }

        $validated['incident_location_id'] = $location?->id;
        $validated['location'] = $location?->name ?? $validated['location'];

        $category = IncidentCategory::query()
            ->where('id', $validated['category_id'])
            ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $user->school_id))
            ->firstOrFail();

        if ($category->is_positive) {
            $validated['severity'] = 1;
            $validated['needs_medical'] = false;
        }

        $incident = $incidentService->create($validated, $user, $user->school);

        if (! $incident->canBeViewedBy($user)) {
            return redirect()->route('incidents.index')
                ->with('info', 'Kasus tercatat dan dialihkan ke jalur terbatas. Anda tidak memiliki akses ke detail kasus ini.');
        }

        return redirect()->route('incidents.show', $incident)
            ->with('success', 'Insiden '.$incident->case_number.' berhasil dicatat.');
    }

    public function show(Request $request, Incident $incident): Response
    {
        $user = $request->user();
        abort_unless($incident->school_id === $user->school_id, 404);
        abort_unless($incident->canBeViewedBy($user), 403, 'Anda tidak memiliki akses ke kasus ini.');

        if ($incident->confidentiality === 'sangat_rahasia') {
            AuditLogger::record('incident.viewed_sensitive', $incident, 'Kasus sangat rahasia dibuka.');
        }

        $incident->load([
            'category:id,name,default_confidentiality,is_positive',
            'reporter:id,name,role',
            'assignee:id,name,role',
            'participants.student.identity:id,student_id,full_name',
            'participants.student.classroom:id,name',
            'participants.student.dorm:id,name',
            'tasks.doneBy:id,name',
            'followups.doneBy:id,name',
        ]);

        $canManage = $user->canManageIncidents() || $incident->assigned_to === $user->id;

        return Inertia::render('Incidents/Show', [
            'incident' => [
                'id' => $incident->id,
                'case_number' => $incident->case_number,
                'category' => $incident->category?->name,
                'is_positive' => (bool) $incident->category?->is_positive,
                'severity' => $incident->severity,
                'occurred_at' => $incident->occurred_at->toIso8601String(),
                'location' => $incident->location,
                'description' => $incident->description,
                'initial_action' => $incident->initial_action,
                'needs_medical' => $incident->needs_medical,
                'status' => $incident->status,
                'confidentiality' => $incident->confidentiality,
                'due_at' => $incident->due_at?->toIso8601String(),
                'closed_at' => $incident->closed_at?->toIso8601String(),
                'sanction' => $incident->sanction,
                'resolution_notes' => $incident->resolution_notes,
                'reporter' => $incident->reporter?->only(['id', 'name']),
                'assignee' => $incident->assignee ? [
                    'id' => $incident->assignee->id,
                    'name' => $incident->assignee->name,
                    'role_label' => $incident->assignee->roleLabel(),
                ] : null,
                'participants' => $incident->participants->map(fn ($participant) => [
                    'id' => $participant->id,
                    'role' => $participant->role,
                    'notes' => $participant->notes,
                    'student' => [
                        'id' => $participant->student?->id,
                        'code' => $participant->student?->code,
                        'name' => $participant->student?->identity?->full_name,
                        'classroom' => $participant->student?->classroom?->name,
                        'dorm' => $participant->student?->dorm?->name,
                    ],
                ]),
                'tasks' => $incident->tasks->map(fn ($task) => [
                    'id' => $task->id,
                    'label' => $task->label,
                    'is_done' => $task->is_done,
                    'done_by' => $task->doneBy?->name,
                    'done_at' => $task->done_at?->toIso8601String(),
                ]),
                'followups' => $incident->followups->map(fn ($followup) => [
                    'id' => $followup->id,
                    'week' => $followup->week,
                    'due_at' => $followup->due_at->toDateString(),
                    'status' => $followup->status,
                    'notes' => $followup->notes,
                    'done_by' => $followup->doneBy?->name,
                    'done_at' => $followup->done_at?->toIso8601String(),
                ]),
                'overdue' => $incident->isOverdue(),
            ],
            'canManage' => $canManage,
            'canManageConfidential' => $user->canViewConfidentialIncidents(),
            'assignableUsers' => User::query()
                ->where('school_id', $user->school_id)
                ->where('is_active', true)
                ->whereIn('role', ['kepala_sekolah', 'tppk', 'bk', 'wali_kelas', 'wali_asrama', 'guru'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $assignee) => [
                    'id' => $assignee->id,
                    'name' => $assignee->name,
                    'role_label' => $assignee->roleLabel(),
                ]),
            'audit' => $canManage
                ? AuditLog::query()
                    ->where('subject_type', Incident::class)
                    ->where('subject_id', $incident->id)
                    ->with('user:id,name')
                    ->latest('created_at')
                    ->limit(30)
                    ->get()
                    ->map(fn (AuditLog $log) => [
                        'id' => $log->id,
                        'action' => $log->action,
                        'description' => $log->description,
                        'user' => $log->user?->name,
                        'created_at' => $log->created_at?->toIso8601String(),
                    ])
                : [],
        ]);
    }

    public function update(Request $request, Incident $incident): RedirectResponse
    {
        $user = $request->user();
        abort_unless($incident->school_id === $user->school_id, 404);
        abort_unless($user->canManageIncidents() || $incident->assigned_to === $user->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('sigap.incident_statuses')))],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('school_id', $user->school_id)->where('is_active', true)],
            'due_at' => ['nullable', 'date'],
            'sanction' => ['nullable', Rule::in(array_keys(config('sigap.sanctions')))],
            'resolution_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($validated['status'] === 'selesai' && blank($validated['resolution_notes'] ?? null)) {
            return back()->withErrors(['resolution_notes' => 'Catatan penyelesaian wajib diisi sebelum menutup kasus.'])->withInput();
        }

        if (($validated['sanction'] ?? null) === 'dikeluarkan' || ($validated['sanction'] ?? null) === 'dipulangkan') {
            return back()->withErrors(['sanction' => 'Sanksi keluar atau dipulangkan tidak tersedia. Gunakan wizard keluar M10 sebagai satu-satunya jalur keluar.'])->withInput();
        }

        $statusChanged = $incident->status !== $validated['status'];
        $assigneeChanged = $incident->assigned_to !== ($validated['assigned_to'] ?? null);

        $incident->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_at' => $validated['due_at'] ?? $incident->due_at,
            'sanction' => $validated['sanction'] ?? null,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'closed_at' => $validated['status'] === 'selesai' ? now() : null,
        ]);

        AuditLogger::record(
            'incident.updated',
            $incident,
            'Kasus diperbarui. Status: '.$validated['status'].($assigneeChanged ? '. Penanggung jawab diganti.' : '')
        );

        if ($statusChanged || $assigneeChanged) {
            $reporter = User::find($incident->reported_by);
            if ($reporter && $reporter->id !== $user->id) {
                NotificationService::send(
                    $reporter,
                    'Status kasus diperbarui',
                    'Ada pembaruan pada kasus '.$incident->case_number.'. Buka SIGAP-SR untuk melihat detail.',
                    route('incidents.show', $incident)
                );
            }

            if ($assigneeChanged && $incident->assignee && $incident->assigned_to !== $user->id) {
                NotificationService::send(
                    $incident->assignee,
                    'Penugasan kasus baru',
                    'Anda ditetapkan sebagai penanggung jawab kasus '.$incident->case_number.'. Buka SIGAP-SR untuk melihat detail.',
                    route('incidents.show', $incident)
                );
            }
        }

        return back()->with('success', 'Kasus berhasil diperbarui.');
    }

    public function toggleTask(Request $request, Incident $incident, int $task): RedirectResponse
    {
        $user = $request->user();
        abort_unless($incident->school_id === $user->school_id, 404);
        abort_unless($user->canManageIncidents() || $incident->assigned_to === $user->id, 403);

        $incidentTask = $incident->tasks()->whereKey($task)->firstOrFail();
        $incidentTask->forceFill([
            'is_done' => ! $incidentTask->is_done,
            'done_by' => $incidentTask->is_done ? null : $user->id,
            'done_at' => $incidentTask->is_done ? null : now(),
        ])->save();

        AuditLogger::record('incident.task_toggled', $incident, 'Langkah protokol diperbarui.');

        return back();
    }

    public function storeTask(Request $request, Incident $incident): RedirectResponse
    {
        $user = $request->user();
        abort_unless($incident->school_id === $user->school_id, 404);
        abort_unless($user->canManageIncidents() || $incident->assigned_to === $user->id, 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $maxOrder = $incident->tasks()->max('sort_order') ?? 0;

        $incident->tasks()->create([
            'label' => $validated['label'],
            'is_done' => false,
            'sort_order' => $maxOrder + 1,
        ]);

        AuditLogger::record('incident.task_created', $incident, 'Menambahkan langkah tindakan mandiri pada kasus: '.$validated['label']);

        return back()->with('success', 'Tindakan baru berhasil ditambahkan.');
    }

    public function completeFollowup(Request $request, Incident $incident, int $followup): RedirectResponse
    {
        $user = $request->user();
        abort_unless($incident->school_id === $user->school_id, 404);
        abort_unless($user->canManageIncidents() || $incident->assigned_to === $user->id, 403);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $incidentFollowup = $incident->followups()->whereKey($followup)->firstOrFail();
        $incidentFollowup->forceFill([
            'status' => 'done',
            'notes' => $validated['notes'],
            'done_by' => $user->id,
            'done_at' => now(),
        ])->save();

        AuditLogger::record('incident.followup_completed', $incident, 'Tindak lanjut minggu ke-'.$incidentFollowup->week.' diselesaikan.');

        return back()->with('success', 'Tindak lanjut dicatat.');
    }
}
