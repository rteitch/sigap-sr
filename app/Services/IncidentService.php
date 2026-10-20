<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentFollowup;
use App\Models\IncidentTask;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function create(array $data, User $reporter, School $school): Incident
    {
        return DB::transaction(function () use ($data, $reporter, $school) {
            /** @var IncidentCategory $category */
            $category = IncidentCategory::query()
                ->where('id', $data['category_id'])
                ->where(function ($q) use ($school) {
                    $q->whereNull('school_id')->orWhere('school_id', $school->id);
                })
                ->where('is_active', true)
                ->firstOrFail();

            $confidentiality = $category->default_confidentiality;
            $severity = (int) $data['severity'];
            $occurredAt = Carbon::parse($data['occurred_at']);
            $caseNumber = $this->nextCaseNumber($occurredAt->year);

            $assignee = $this->resolveAssignee($school, $reporter, $severity, $confidentiality);
            $dueHours = config('sigap.response_due_hours.'.$severity, 48);
            $isUrgent = $severity >= 3 || $confidentiality === 'sangat_rahasia';

            $incident = Incident::create([
                'school_id' => $school->id,
                'case_number' => $caseNumber,
                'category_id' => $category->id,
                'severity' => $severity,
                'occurred_at' => $occurredAt,
                'incident_location_id' => $data['incident_location_id'] ?? null,
                'location' => $data['location'],
                'description' => $data['description'],
                'initial_action' => $data['initial_action'] ?? null,
                'needs_medical' => (bool) ($data['needs_medical'] ?? false),
                'status' => 'baru',
                'confidentiality' => $confidentiality,
                'reported_by' => $reporter->id,
                'assigned_to' => $assignee?->id,
                'due_at' => now()->addHours($dueHours),
            ]);

            foreach ($data['participants'] as $participant) {
                $incident->participants()->create([
                    'student_id' => $participant['student_id'],
                    'role' => $participant['role'],
                    'notes' => $participant['notes'] ?? null,
                ]);
            }

            $this->seedTasks($incident, $category->is_positive);

            if ($isUrgent && ! $category->is_positive) {
                $this->seedFollowups($incident);
            }

            $this->notify($incident, $assignee, $isUrgent, $category->is_positive);

            AuditLogger::record(
                'incident.created',
                $incident,
                'Kasus '.$incident->case_number.' dicatat.'
            );

            return $incident;
        });
    }

    public function nextCaseNumber(int $year): string
    {
        $prefix = 'SR-'.$year.'-';

        $last = Incident::query()
            ->where('case_number', 'like', $prefix.'%')
            ->orderByDesc('case_number')
            ->value('case_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        do {
            $candidate = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Incident::where('case_number', $candidate)->exists());

        return $candidate;
    }

    private function resolveAssignee(School $school, User $reporter, int $severity, string $confidentiality): ?User
    {
        if ($severity >= 3) {
            return User::query()
                ->where('school_id', $school->id)
                ->where('role', 'kepala_sekolah')
                ->where('is_active', true)
                ->first();
        }

        if ($confidentiality === 'sangat_rahasia') {
            return User::query()
                ->where('school_id', $school->id)
                ->whereIn('role', ['tppk', 'kepala_sekolah'])
                ->where('is_active', true)
                ->orderByRaw("case when role = 'tppk' then 0 else 1 end")
                ->first();
        }

        if ($reporter->role !== 'admin') {
            return $reporter;
        }

        return User::query()
            ->where('school_id', $school->id)
            ->where('role', 'kepala_sekolah')
            ->where('is_active', true)
            ->first();
    }

    private function seedTasks(Incident $incident, bool $isPositive): void
    {
        if ($isPositive) {
            $labels = [
                'Sampaikan apresiasi kepada siswa secara bermakna',
                'Catat dalam portofolio positif siswa',
            ];
        } elseif ($incident->confidentiality === 'sangat_rahasia' && $incident->severity >= 3) {
            $labels = [
                'Lindungi korban dan pastikan layanan medis',
                'Pisahkan tempat sementara di dalam lingkungan sekolah',
                'Beri tahu orang tua/wali sesuai arahan TPPK',
                'Lapor ke balai/Dinsos dan aparat atau unit perlindungan anak',
                'Siapkan paket rujukan',
                'Susun rencana pemulihan korban',
                'Catat seluruh akses dan tindakan',
            ];
        } elseif ($incident->severity >= 3) {
            $labels = [
                'Pastikan keselamatan dan layanan medis',
                'Beri tahu kepala sekolah segera',
                'Tetapkan tim penanganan lintas peran',
                'Hubungi orang tua atau wali',
                'Susun rencana pemulihan korban',
                'Jadwalkan tinjauan minggu 1, 2, dan 4',
            ];
        } else {
            $labels = [
                'Tindak lanjut pembinaan oleh wali kelas atau wali asrama',
                'Catat hasil pembinaan',
                'Tinjau ulang bila perilaku berulang',
            ];
        }

        foreach ($labels as $index => $label) {
            IncidentTask::create([
                'incident_id' => $incident->id,
                'label' => $label,
                'sort_order' => $index,
            ]);
        }
    }

    private function seedFollowups(Incident $incident): void
    {
        foreach ([1, 2, 4] as $week) {
            IncidentFollowup::create([
                'incident_id' => $incident->id,
                'week' => $week,
                'due_at' => $incident->occurred_at->copy()->addWeeks($week)->toDateString(),
                'status' => 'pending',
            ]);
        }
    }

    private function notify(Incident $incident, ?User $assignee, bool $urgent, bool $isPositive): void
    {
        $link = route('incidents.show', $incident);
        $title = $isPositive ? 'Catatan apresiasi baru' : 'Kasus baru perlu ditinjau';
        $body = $isPositive
            ? 'Ada catatan apresiasi siswa yang baru dicatat. Buka SIGAP-SR untuk melihat detail.'
            : 'Ada kasus baru yang perlu ditinjau. Buka SIGAP-SR untuk melihat detail.';

        if ($assignee && $assignee->id !== $incident->reported_by) {
            NotificationService::send($assignee, $title, $body, $link);
        }

        if ($urgent && ! $isPositive) {
            $headmasters = User::query()
                ->where('school_id', $incident->school_id)
                ->where('role', 'kepala_sekolah')
                ->where('is_active', true)
                ->when($assignee?->role === 'kepala_sekolah', fn ($q) => $q->where('id', '!=', $assignee->id))
                ->get();

            foreach ($headmasters as $headmaster) {
                NotificationService::send(
                    $headmaster,
                    'Kasus prioritas tinggi',
                    'Ada kasus tingkat 3 atau 4 yang perlu segera ditinjau. Buka SIGAP-SR untuk detail.',
                    $link
                );
            }
        }

        if ($incident->confidentiality === 'sangat_rahasia') {
            $handlers = User::query()
                ->where('school_id', $incident->school_id)
                ->whereIn('role', ['tppk', 'bk'])
                ->where('is_active', true)
                ->get();

            foreach ($handlers as $handler) {
                if ($handler->id === $assignee?->id) {
                    continue;
                }
                NotificationService::send(
                    $handler,
                    'Kasus sangat rahasia baru',
                    'Ada kasus sangat rahasia yang memerlukan penanganan terbatas. Buka SIGAP-SR untuk detail.',
                    $link
                );
            }
        }
    }
}
