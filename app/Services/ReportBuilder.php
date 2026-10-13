<?php

namespace App\Services;

use App\Models\DailySummary;
use App\Models\Setting;
use App\Models\Tehsil;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportBuilder
{
    /**
     * Generate daily mobilization report PDF (RPT-1, RPT-3, 6.5).
     *
     * @param  string  $date  (YYYY-MM-DD)
     * @return array ['path' => string, 'content' => string, 'filename' => string]
     */
    public function buildDailyPdf(string $date, ?int $tehsilId = null, array $filters = []): array
    {
        // 1. Fetch or generate rollup summary
        $payload = $this->payloadFor($date);

        $allRegisterRows = collect($payload['register_rows'] ?? []);

        // 2. Filter payload if tehsil-scoped (RPT-3) or custom filtered
        $tehsilSuffix = '';
        if ($tehsilId) {
            $tehsil = Tehsil::find($tehsilId);
            $tehsilName = $tehsil ? $tehsil->name : "Tehsil-{$tehsilId}";
            $filters['tehsil'] = $tehsilName;
            $tehsilSuffix = '_'.str_replace(' ', '-', $tehsilName);
        }

        $payload = $this->applyFilters($payload, $filters);

        // 3. Render Blade View to HTML
        ['priorityDisplay' => $priorityDisplay, 'dashStats' => $dashStats] = app(DashboardSummary::class)->build($payload, $date, $allRegisterRows);
        $html = view('reports.daily', compact('payload', 'date', 'priorityDisplay', 'dashStats'))->render();

        // 4. Generate PDF via Dompdf
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'Helvetica');

        $pdfOutput = $pdf->output();

        // 5. Store PDF file (storage/app/reports/YYYY/MM/PRSWSSP_DSM_YYYY-MM-DD.pdf)
        $dateCarbon = Carbon::parse($date);
        $dir = 'reports/'.$dateCarbon->format('Y/m');
        $filename = "PRSWSSP_DSM_{$date}{$tehsilSuffix}.pdf";
        $relativePath = "{$dir}/{$filename}";

        Storage::disk('local')->put($relativePath, $pdfOutput);

        return [
            'path' => $relativePath,
            'full_path' => Storage::disk('local')->path($relativePath),
            'content' => $pdfOutput,
            'filename' => $filename,
        ];
    }

    /**
     * The cached rollup payload for a date, generating it when missing.
     *
     * @return array<string, mixed>
     */
    public function payloadFor(string $date): array
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $today = Carbon::now($tz)->toDateString();

        if ($date === $today) {
            return app(RollupService::class)->execute($date);
        }

        $summaryRecord = DailySummary::where(function ($q) use ($date) {
            $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
        })->first();

        return $summaryRecord ? $summaryRecord->payload : app(RollupService::class)->execute($date);
    }

    /**
     * Narrow a payload to the dashboard filters (phase / tehsil / contractor / village) and recompute its totals.
     *
     * @param  array<string, mixed>  $payload
     * @param  array{phase?: string|null, tehsil?: string|null, contractor?: string|null, village?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function applyFilters(array $payload, array $filters): array
    {
        if (empty($filters['phase']) && empty($filters['tehsil']) && empty($filters['contractor']) && empty($filters['village'])) {
            return $payload;
        }

        $rows = collect($payload['register_rows'] ?? []);

        if (! empty($filters['phase'])) {
            $p = $filters['phase'];
            $rows = $rows->filter(function ($r) use ($p) {
                return match ($p) {
                    'phase_0' => ($r['phase_abbr'] ?? '') === 'Ph-0' || str_contains($r['group_name'] ?? '', 'PHASE-0'),
                    'priority_a' => in_array($r['priority_code'] ?? '', ['1', 'A']) || str_contains($r['group_name'] ?? '', 'PRIORITY A'),
                    'priority_b' => in_array($r['priority_code'] ?? '', ['2', 'B']) || str_contains($r['group_name'] ?? '', 'PRIORITY B'),
                    'priority_c' => in_array($r['priority_code'] ?? '', ['3', 'C']) || str_contains($r['group_name'] ?? '', 'PRIORITY C'),
                    'remaining_pilot' => str_contains($r['group_name'] ?? '', 'REMAINING') || (in_array($r['priority_code'] ?? '', ['4', 'D', '']) && ($r['phase_abbr'] ?? '') !== 'Ph-0' && ! str_contains($r['group_name'] ?? '', 'PRIORITY')),
                    default => true,
                };
            });
        }
        if (! empty($filters['tehsil'])) {
            $rows = $rows->filter(fn ($r) => strcasecmp($r['tehsil'] ?? '', $filters['tehsil']) === 0);
        }
        if (! empty($filters['contractor'])) {
            $rows = $rows->filter(fn ($r) => strcasecmp($r['contractor'] ?? '', $filters['contractor']) === 0);
        }
        if (! empty($filters['village'])) {
            $rows = $rows->filter(fn ($r) => strcasecmp($r['village'] ?? '', $filters['village']) === 0 || stripos($r['village'] ?? '', $filters['village']) !== false);
        }

        $activeRows = $rows->where('day_status', 'active');
        $dormantRows = $rows->where('day_status', 'dormant');
        $nrRows = $rows->where('day_status', 'not_reported');

        $payload['total_ongoing'] = $rows->count();
        $payload['active_count'] = $activeRows->count();
        $payload['dormant_count'] = $dormantRows->count();
        $payload['nr_count'] = $nrRows->count();
        $payload['total_labour'] = $activeRows->sum('labour');
        $payload['total_machinery'] = $activeRows->sum('machinery');
        $payload['active_pct'] = $payload['total_ongoing'] > 0 ? round(($payload['active_count'] / $payload['total_ongoing']) * 100) : 0;
        $payload['avg_physical_progress'] = $payload['total_ongoing'] > 0 ? round($rows->avg('physical_progress') ?? 0) : 0;
        $payload['register_rows'] = $rows->values()->toArray();

        if (! empty($filters['tehsil'])) {
            $payload['tehsils'] = collect($payload['tehsils'] ?? [])->where('name', $filters['tehsil'])->values()->toArray();
        }
        if (! empty($filters['contractor'])) {
            $payload['contractors'] = collect($payload['contractors'] ?? [])->where('name', $filters['contractor'])->values()->toArray();
        }

        $tendering = collect($payload['tendering_rows'] ?? []);
        if (! empty($filters['tehsil'])) {
            $tendering = $tendering->filter(fn ($r) => strcasecmp($r['tehsil'] ?? '', $filters['tehsil']) === 0);
        }
        if (! empty($filters['village'])) {
            $tendering = $tendering->filter(fn ($r) => strcasecmp($r['village'] ?? '', $filters['village']) === 0 || stripos($r['village'] ?? '', $filters['village']) !== false);
        }
        $payload['tendering_rows'] = $tendering->values()->toArray();
        $payload['tendering_count'] = count($payload['tendering_rows']);

        return $payload;
    }
}
