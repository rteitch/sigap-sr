<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Incident;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $this->month($request);
        $report = $this->buildReport($request, $month);

        return Inertia::render('Reports/Index', [
            'month' => $month->format('Y-m'),
            'month_label' => $month->translatedFormat('F Y'),
            'report' => $report,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $month = $this->month($request);
        $report = $this->buildReport($request, $month);
        $filename = 'laporan-bulanan-sigap-sr-'.$month->format('Y-m').'.csv';

        AuditLogger::record('report.exported', null, 'Ekspor laporan agregat bulan '.$month->format('Y-m').'.');

        return response()->streamDownload(function () use ($report, $month) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Laporan SIGAP-SR', $month->translatedFormat('F Y')], ';');
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Metrik', 'Nilai'], ';');
            foreach ($report['summary'] as $row) {
                fputcsv($handle, [$row['label'], $row['value']], ';');
            }
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Kategori', 'Jumlah'], ';');
            foreach ($report['categories'] as $row) {
                fputcsv($handle, [$row['name'], $row['value']], ';');
            }
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Catatan', 'Angka kelompok kurang dari '.$report['threshold'].' disembunyikan. Laporan tidak memuat identitas anak.'], ';');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildReport(Request $request, Carbon $month): array
    {
        $user = $request->user();
        $school = $user->school;
        $threshold = (int) ($school->setting('small_group_threshold') ?? config('sigap.small_group_threshold'));

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $visibleStudentIds = Student::query()->visibleTo($user)->pluck('id');

        $incidents = Incident::query()
            ->visibleTo($user)
            ->whereHas('category', fn ($q) => $q->where('is_positive', false))
            ->whereBetween('occurred_at', [$start, $end]);

        $total = (clone $incidents)->count();
        $open = (clone $incidents)->open()->count();
        $overdue = (clone $incidents)->open()->where('due_at', '<', now())->count();
        $closed = (clone $incidents)->where('status', 'selesai')->count();
        $confidential = (clone $incidents)->where('confidentiality', 'sangat_rahasia')->count();

        $bySeverity = (clone $incidents)
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $byCategory = (clone $incidents)
            ->with('category:id,name')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($items) => [
                'name' => $items->first()->category?->name ?? 'Tanpa kategori',
                'value' => $items->count(),
            ])
            ->sortByDesc('value')
            ->values();

        $visibleCategories = $byCategory->filter(fn (array $row) => $row['value'] >= $threshold)->values();
        $hiddenCategoryCount = $byCategory->filter(fn (array $row) => $row['value'] < $threshold)->count();

        $attendanceQuery = AttendanceRecord::query()
            ->whereIn('student_id', $visibleStudentIds)
            ->where('type', 'kelas')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        $attendanceTotal = (clone $attendanceQuery)->count();
        $attendancePresent = (clone $attendanceQuery)->where('status', 'hadir')->count();
        $attendanceRate = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100, 1) : null;

        $resolvedIncidents = (clone $incidents)
            ->whereNotNull('closed_at')
            ->get(['occurred_at', 'closed_at']);
        $averageResolutionDays = $resolvedIncidents->isNotEmpty()
            ? round($resolvedIncidents->avg(fn (Incident $incident) => $incident->occurred_at->diffInHours($incident->closed_at) / 24), 1)
            : null;

        return [
            'threshold' => $threshold,
            'summary' => [
                ['label' => 'Total insiden', 'value' => $total],
                ['label' => 'Kasus terbuka', 'value' => $open],
                ['label' => 'Kasus lewat tenggat', 'value' => $overdue],
                ['label' => 'Kasus selesai', 'value' => $closed],
                ['label' => 'Kasus sangat rahasia', 'value' => $confidential],
                ['label' => 'Kehadiran kelas', 'value' => $attendanceRate !== null ? $attendanceRate.'%' : 'Belum ada data'],
                ['label' => 'Rata-rata penyelesaian', 'value' => $averageResolutionDays !== null ? $averageResolutionDays.' hari' : 'Belum ada data'],
            ],
            'severities' => collect(config('sigap.incident_severities'))->map(fn ($label, $severity) => [
                'severity' => (int) $severity,
                'label' => $label,
                'value' => (int) ($bySeverity->get($severity) ?? 0),
            ])->values(),
            'categories' => $visibleCategories,
            'hidden_category_count' => $hiddenCategoryCount,
            'notes' => [
                'Kenaikan angka di awal dapat berarti pelaporan membaik, bukan hanya perilaku memburuk.',
                'Kelompok dengan kurang dari '.$threshold.' catatan disembunyikan untuk melindungi identitas anak.',
                'Laporan ini tidak memuat nama, kode siswa, atau detail yang dapat mengidentifikasi anak.',
            ],
        ];
    }

    private function month(Request $request): Carbon
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();
    }
}
