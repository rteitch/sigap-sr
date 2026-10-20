<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Incident;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $school = $user->school;
        $threshold = (int) ($school->setting('small_group_threshold') ?? config('sigap.small_group_threshold'));

        $visibleStudents = Student::query()->visibleTo($user);
        $studentIds = (clone $visibleStudents)->pluck('id');
        $visibleIncidents = Incident::query()->visibleTo($user)->whereHas('category', fn ($q) => $q->where('is_positive', false));

        $activeStudents = (clone $visibleStudents)->where('status', 'aktif')->count();
        $incidentsThisMonth = (clone $visibleIncidents)
            ->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $openCases = (clone $visibleIncidents)->open()->count();
        $overdueCases = (clone $visibleIncidents)->open()->where('due_at', '<', now())->count();
        $confidentialOpen = (clone $visibleIncidents)->open()
            ->where('confidentiality', 'sangat_rahasia')
            ->count();

        $attendanceQuery = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'kelas')
            ->where('date', '>=', now()->subDays(30)->toDateString());
        $attendanceTotal = (clone $attendanceQuery)->count();
        $attendancePresent = (clone $attendanceQuery)->where('status', 'hadir')->count();
        $attendanceRate = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100) : null;

        $trend = [];
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
        $trendIncidents = (clone $visibleIncidents)
            ->where('occurred_at', '>=', $months->first()->copy()->startOfMonth())
            ->get(['occurred_at'])
            ->groupBy(fn (Incident $incident) => $incident->occurred_at->format('Y-m'));

        foreach ($months as $month) {
            $trend[] = [
                'label' => $month->translatedFormat('M'),
                'full_label' => $month->translatedFormat('F Y'),
                'value' => $trendIncidents->get($month->format('Y-m'), collect())->count(),
            ];
        }

        $severityCounts = (clone $visibleIncidents)
            ->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $categoryBreakdown = (clone $visibleIncidents)
            ->whereBetween('occurred_at', [now()->subDays(90), now()])
            ->with('category:id,name')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($items) => [
                'name' => $items->first()->category?->name ?? 'Tanpa kategori',
                'value' => $items->count(),
            ])
            ->sortByDesc('value')
            ->values();

        $visibleCategories = $categoryBreakdown->filter(fn (array $row) => $row['value'] >= $threshold)->take(6)->values();
        $hiddenCategoryCount = $categoryBreakdown->filter(fn (array $row) => $row['value'] < $threshold)->count();

        $recentIncidents = (clone $visibleIncidents)
            ->with(['category:id,name', 'assignee:id,name'])
            ->latest('occurred_at')
            ->limit(6)
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'case_number' => $incident->case_number,
                'category' => $incident->category?->name,
                'severity' => $incident->severity,
                'confidentiality' => $incident->confidentiality,
                'status' => $incident->status,
                'occurred_at' => $incident->occurred_at->toIso8601String(),
                'assignee' => $incident->assignee?->name,
                'overdue' => $incident->isOverdue(),
            ]);

        $dormToday = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('type', ['apel_pagi', 'makan', 'apel_malam', 'tidur'])
            ->where('date', now()->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $dormTotal = $dormToday->sum();
        $dormPresent = $dormToday->get('hadir', 0);

        $qualityIncidents = (clone $visibleIncidents)
            ->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->withCount(['tasks', 'tasks as done_tasks_count' => fn ($q) => $q->where('is_done', true), 'followups'])
            ->get();
        $taskCompletion = $qualityIncidents->sum('tasks_count') > 0
            ? round(($qualityIncidents->sum('done_tasks_count') / $qualityIncidents->sum('tasks_count')) * 100)
            : null;
        $withoutFollowup = $qualityIncidents->where('severity', '>=', 3)->where('followups_count', 0)->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'active_students' => $activeStudents,
                'incidents_month' => $incidentsThisMonth,
                'open_cases' => $openCases,
                'overdue_cases' => $overdueCases,
                'confidential_open' => $confidentialOpen,
                'attendance_rate' => $attendanceRate,
                'dorm_present' => $dormPresent,
                'dorm_total' => $dormTotal,
            ],
            'trend' => $trend,
            'severities' => collect(config('sigap.incident_severities'))->keys()->map(fn ($severity) => [
                'severity' => (int) $severity,
                'label' => config('sigap.incident_severities.'.$severity),
                'value' => (int) ($severityCounts->get($severity) ?? 0),
            ]),
            'categories' => $visibleCategories,
            'hidden_category_count' => $hiddenCategoryCount,
            'recent_incidents' => $recentIncidents,
            'quality' => [
                'task_completion' => $taskCompletion,
                'without_followup' => $withoutFollowup,
                'audited_sample' => (int) ceil($qualityIncidents->count() * 0.1),
                'total_incidents' => $qualityIncidents->count(),
            ],
            'threshold' => $threshold,
            'generated_at' => Carbon::now()->toIso8601String(),
        ]);
    }
}
