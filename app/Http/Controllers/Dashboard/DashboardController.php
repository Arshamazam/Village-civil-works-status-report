<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\DailyReturn;
use App\Models\DailySummary;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\ReturnPhoto;
use App\Models\Setting;
use App\Models\Site;
use App\Models\SiteDaySnapshot;
use App\Models\Tehsil;
use App\Services\DashboardSummary;
use App\Services\ReportBuilder;
use App\Services\RollupService;
use App\Exports\CardDetailsExport;
use App\Exports\DailyProgressExport;
use App\Exports\NoProgressSitesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    /**
     * Dashboard main view (DASH-1 to DASH-8, DASH-11).
     */
    public function index(Request $request, RollupService $rollupService, DashboardSummary $dashboardSummary)
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $today = Carbon::now($tz)->toDateString();

        // Default to today. Never jump ahead to future dates.
        $requestedDate = $request->get('date');
        $date = $requestedDate ? Carbon::parse($requestedDate)->toDateString() : $today;

        // For today (or when explicitly requested via ?refresh=1), compute live to reflect newly filled forms immediately.
        // For past dates, use the cached summary if available.
        if ($date === $today || $request->has('refresh')) {
            $payload = $rollupService->execute($date);
        } else {
            $summaryRecord = DailySummary::where(function ($q) use ($date) {
                $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
            })->first();

            $payload = $summaryRecord ? $summaryRecord->payload : $rollupService->execute($date);
        }

        // Extract actual distinct values directly from the authentic daily returns payload
        $allRegisterRows = collect($payload['register_rows'] ?? []);
        $distinctTehsils = $allRegisterRows->pluck('tehsil')->filter()->unique()->sort()->values();
        $distinctContractors = $allRegisterRows->pluck('contractor')->filter()->unique()->sort()->values();
        $distinctVillages = $allRegisterRows->pluck('village')->filter()->unique()->sort()->values();

        // Map each Tehsil to its authentic villages for dependent filtering
        $tehsilVillagesMap = $allRegisterRows
            ->groupBy('tehsil')
            ->map(fn ($g) => $g->pluck('village')->filter()->unique()->sort()->values())
            ->toArray();

        // Master data for filters (DASH-1)
        $phases = Phase::all();
        $tehsils = Tehsil::orderBy('name')->get();
        $priorities = Priority::orderBy('sort_order')->get();
        $contractors = Contractor::orderBy('name')->get();
        $villages = $distinctVillages;

        $phaseOptions = [
            'phase_0' => 'Phase-0',
            'priority_a' => 'Priority A',
            'priority_b' => 'Priority B',
            'priority_c' => 'Priority C',
            'remaining_pilot' => 'Remaining Pilot',
        ];

        $selectedPhase = $request->get('phase');
        $selectedTehsil = $request->get('tehsil');
        $selectedContractor = $request->get('contractor');
        $selectedVillage = $request->get('village');

        $hasFilters = (bool) ($selectedPhase || $selectedTehsil || $selectedContractor || $selectedVillage);

        // Apply filters to payload if provided
        if ($hasFilters) {
            $rows = $allRegisterRows;

            if ($selectedPhase) {
                $rows = $rows->filter(function ($r) use ($selectedPhase) {
                    return match ($selectedPhase) {
                        'phase_0' => ($r['phase_abbr'] ?? '') === 'Ph-0' || str_contains($r['group_name'] ?? '', 'PHASE-0'),
                        'priority_a' => in_array($r['priority_code'] ?? '', ['1', 'A']) || str_contains($r['group_name'] ?? '', 'PRIORITY A'),
                        'priority_b' => in_array($r['priority_code'] ?? '', ['2', 'B']) || str_contains($r['group_name'] ?? '', 'PRIORITY B'),
                        'priority_c' => in_array($r['priority_code'] ?? '', ['3', 'C']) || str_contains($r['group_name'] ?? '', 'PRIORITY C'),
                        'remaining_pilot' => str_contains($r['group_name'] ?? '', 'REMAINING') || (in_array($r['priority_code'] ?? '', ['4', 'D', '']) && ($r['phase_abbr'] ?? '') !== 'Ph-0' && ! str_contains($r['group_name'] ?? '', 'PRIORITY')),
                        default => true,
                    };
                });
            }
            if ($selectedTehsil) {
                $rows = $rows->filter(fn ($r) => strcasecmp($r['tehsil'] ?? '', $selectedTehsil) === 0);
            }
            if ($selectedContractor) {
                $rows = $rows->filter(fn ($r) => strcasecmp($r['contractor'] ?? '', $selectedContractor) === 0);
            }
            if ($selectedVillage) {
                $rows = $rows->filter(fn ($r) => strcasecmp($r['village'] ?? '', $selectedVillage) === 0 || stripos($r['village'] ?? '', $selectedVillage) !== false);
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
            $payload['thinly_manned_count'] = $activeRows->where('labour', '<', 5)->count();
            $payload['active_pct'] = $rows->count() > 0 ? round(($activeRows->count() / $rows->count()) * 100) : 0;
            $payload['register_rows'] = $rows->values()->toArray();

            // Recompute idle groupings
            $idleGroupings = [
                'Contractor' => 0,
                'PRMSC' => 0,
                'External' => 0,
                "VLD's" => 0,
                'Govt. Land' => 0,
                'Unstated' => 0,
            ];
            foreach ($dormantRows as $r) {
                $grp = $r['top_level_reason'] ?: 'Unstated';
                if (! isset($idleGroupings[$grp])) {
                    $grp = 'Unstated';
                }
                $idleGroupings[$grp]++;
            }
            $payload['idle_groupings'] = $idleGroupings;

            // Recompute Pilot vs Phase-0
            $pilotRows = $rows->filter(fn ($r) => ($r['phase_abbr'] ?? '') !== 'Ph-0');
            $ph0Rows = $rows->filter(fn ($r) => ($r['phase_abbr'] ?? '') === 'Ph-0');

            $payload['phases'] = [
                [
                    'name' => 'Pilot Phase',
                    'active' => $pilotRows->where('day_status', 'active')->count(),
                    'dormant' => $pilotRows->where('day_status', 'dormant')->count(),
                    'nr' => $pilotRows->where('day_status', 'not_reported')->count(),
                ],
                [
                    'name' => 'Phase-0',
                    'active' => $ph0Rows->where('day_status', 'active')->count(),
                    'dormant' => $ph0Rows->where('day_status', 'dormant')->count(),
                    'nr' => $ph0Rows->where('day_status', 'not_reported')->count(),
                ],
            ];
        }

        // Priority cards and headline figures shared with the PDF report
        ['priorityDisplay' => $priorityDisplay, 'dashStats' => $dashStats] = $dashboardSummary->build($payload, $date, $allRegisterRows);

        // Sites with latest snapshot and coordinates for Leaflet Map (DASH-8)
        $mapQuery = Site::where('status', 'ongoing')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['snapshotOn' => fn ($q) => $q->where(function ($sq) use ($date) {
                $sq->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
            }), 'priority', 'tehsil', 'contractor', 'phase']);

        if ($selectedPhase) {
            match ($selectedPhase) {
                'phase_0' => $mapQuery->whereHas('phase', fn ($q) => $q->where('name', 'like', '%0%')->orWhere('code', 'like', '%0%')),
                'priority_a' => $mapQuery->whereHas('priority', fn ($q) => $q->whereIn('code', ['1', 'A'])),
                'priority_b' => $mapQuery->whereHas('priority', fn ($q) => $q->whereIn('code', ['2', 'B'])),
                'priority_c' => $mapQuery->whereHas('priority', fn ($q) => $q->whereIn('code', ['3', 'C'])),
                'remaining_pilot' => $mapQuery->whereHas('phase', fn ($q) => $q->where('name', 'like', '%pilot%'))->whereDoesntHave('priority', fn ($q) => $q->whereIn('code', ['1', '2', '3', 'A', 'B', 'C'])),
                default => null,
            };
        }
        if ($selectedTehsil) {
            $mapQuery->whereHas('tehsil', fn ($q) => $q->where('name', $selectedTehsil));
        }
        if ($selectedContractor) {
            $mapQuery->whereHas('contractor', fn ($q) => $q->where('name', $selectedContractor));
        }
        if ($selectedVillage) {
            $mapQuery->where('village_name', 'like', "%{$selectedVillage}%");
        }

        $mapSites = $mapQuery->get();

        return view('dashboard.index', compact(
            'payload',
            'date',
            'phases',
            'tehsils',
            'priorities',
            'contractors',
            'villages',
            'phaseOptions',
            'selectedPhase',
            'distinctTehsils',
            'distinctContractors',
            'distinctVillages',
            'tehsilVillagesMap',
            'selectedTehsil',
            'selectedContractor',
            'selectedVillage',
            'hasFilters',
            'priorityDisplay',
            'dashStats',
            'mapSites'
        ));
    }

    /**
     * Download the list behind a dashboard card's detail pop-up as a PDF, respecting the dashboard filters and pop-up search.
     */
    public function detailsPdf(Request $request, ReportBuilder $reportBuilder, DashboardSummary $dashboardSummary): Response
    {
        $validated = $request->validate([
            'type' => ['required', 'in:total_villages,tendering_phase,completed_villages,remaining_villages,total_labour,total_machinery,physical_progress,active_sites,dormant_sites,nr_sites,no_progress,priority_card'],
            'name' => ['required_if:type,priority_card', 'nullable', 'string', 'max:50'],
            'date' => ['required', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
            'phase' => ['nullable', 'string', 'max:50'],
            'tehsil' => ['nullable', 'string', 'max:100'],
            'contractor' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
        ]);

        $fullPayload = $reportBuilder->payloadFor($validated['date']);
        $filters = array_filter($request->only(['phase', 'tehsil', 'contractor', 'village']));
        $payload = $reportBuilder->applyFilters($fullPayload, $filters);
        ['priorityDisplay' => $priorityDisplay] = $dashboardSummary->build($payload, $validated['date'], collect($fullPayload['register_rows'] ?? []));

        $detail = $dashboardSummary->detailList($payload, $validated['type'], $validated['name'] ?? null, $priorityDisplay, $validated['q'] ?? null);

        $pdf = Pdf::loadView('reports.detail', [
            'detail' => $detail,
            'date' => $validated['date'],
            'filters' => $filters,
            'search' => $validated['q'] ?? null,
        ])->setPaper('a4', 'landscape')->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'PRSWSSP_'.Str::slug($detail['title'], '_').'_'.$validated['date'].'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download the list behind a dashboard card's detail pop-up as an Excel (.xlsx / .csv) sheet.
     */
    public function detailsExcel(Request $request, ReportBuilder $reportBuilder, DashboardSummary $dashboardSummary)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:total_villages,tendering_phase,completed_villages,remaining_villages,total_labour,total_machinery,physical_progress,active_sites,dormant_sites,nr_sites,no_progress,priority_card'],
            'name' => ['required_if:type,priority_card', 'nullable', 'string', 'max:50'],
            'date' => ['required', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
            'phase' => ['nullable', 'string', 'max:50'],
            'tehsil' => ['nullable', 'string', 'max:100'],
            'contractor' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $fullPayload = $reportBuilder->payloadFor($validated['date']);
        $filters = array_filter($request->only(['phase', 'tehsil', 'contractor', 'village']));
        $payload = $reportBuilder->applyFilters($fullPayload, $filters);
        ['priorityDisplay' => $priorityDisplay] = $dashboardSummary->build($payload, $validated['date'], collect($fullPayload['register_rows'] ?? []));

        $detail = $dashboardSummary->detailList($payload, $validated['type'], $validated['name'] ?? null, $priorityDisplay, $validated['q'] ?? null);
        $format = $validated['format'] ?? 'xlsx';
        $filename = 'PRSWSSP_'.Str::slug($detail['title'], '_').'_'.$validated['date'].($format === 'csv' ? '.csv' : '.xlsx');

        try {
            return Excel::download(new CardDetailsExport($detail['rows'], $detail['title'], $validated['date']), $filename, $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX);
        } catch (\Throwable $e) {
            return response()->streamDownload(function () use ($detail, $validated) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['Site Code', 'Village Name', 'Tehsil', 'Package', 'Phase', 'Contractor', 'Status', 'Physical Progress (%)', 'Labour', 'Machinery', 'Idle Days', 'Root Cause / Reason', 'Remarks / Action Required', 'Report Date']);
                foreach ($detail['rows'] as $r) {
                    fputcsv($handle, [
                        $r['site_code'] ?? '',
                        $r['village'] ?? '',
                        $r['tehsil'] ?? '',
                        $r['package'] ?? '—',
                        $r['phase_abbr'] ?? '',
                        $r['contractor'] ?? 'Unassigned',
                        $r['status_label'] ?? strtoupper((string) ($r['day_status'] ?? '')),
                        ($r['physical_progress'] ?? 0).'%',
                        $r['labour'] ?? 0,
                        $r['machinery'] ?? 0,
                        $r['idle_days'] ?? 0,
                        $r['top_level_reason'] ?? $r['reason_code'] ?? '',
                        $r['remarks'] ?? '',
                        $validated['date'],
                    ]);
                }
                fclose($handle);
            }, 'PRSWSSP_'.Str::slug($detail['title'], '_').'_'.$validated['date'].'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }
    }

    /**
     * Download comprehensive daily progress data sheet (.xlsx / .csv).
     */
    public function exportDataSheet(Request $request, ReportBuilder $reportBuilder)
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $request->get('date') ? Carbon::parse($request->get('date'))->toDateString() : Carbon::now($tz)->toDateString();
        $format = $request->get('format', 'xlsx');

        $fullPayload = $reportBuilder->payloadFor($date);
        $filters = array_filter($request->only(['phase', 'tehsil', 'contractor', 'village']));
        $payload = ! empty($filters) ? $reportBuilder->applyFilters($fullPayload, $filters) : $fullPayload;

        $rows = collect($payload['register_rows'] ?? []);

        // The register only covers sites under active execution. Without this, sites still in
        // tendering/procurement or administratively suspended never appear in any downloaded
        // sheet, which reads as the sheet being incomplete / missing villages.
        if (empty($filters['phase'])) {
            $rows = $rows->concat($this->nonExecutionRegisterRows($date, $filters));
        }

        $filename = 'PRSWSSP_Progress_Data_'.$date.($format === 'csv' ? '.csv' : '.xlsx');

        try {
            return Excel::download(new DailyProgressExport($rows, $date), $filename, $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX);
        } catch (\Throwable $e) {
            return response()->streamDownload(function () use ($rows, $date) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['Site Code', 'Village Name', 'Tehsil', 'Package', 'Phase', 'Priority', 'Contractor', 'Day Status', 'Status Description', 'Physical Progress (%)', 'Labour Count', 'Machinery Units', 'Idle Days', 'Blocker Category', 'Idle Reason Code', 'Expected Completion Date', 'Remarks / Required Action', 'Report Date']);
                foreach ($rows as $r) {
                    fputcsv($handle, [
                        $r['site_code'] ?? '',
                        $r['village'] ?? '',
                        $r['tehsil'] ?? '',
                        $r['package'] ?? '—',
                        $r['phase_abbr'] ?? '',
                        $r['group_name'] ?? $r['priority_code'] ?? '',
                        $r['contractor'] ?? 'Unassigned',
                        strtoupper((string) ($r['day_status'] ?? '')),
                        $r['status_label'] ?? '',
                        ($r['physical_progress'] ?? 0).'%',
                        $r['labour'] ?? 0,
                        $r['machinery'] ?? 0,
                        $r['idle_days'] ?? 0,
                        $r['top_level_reason'] ?? '',
                        $r['reason_code'] ?? '',
                        $r['expected_completion_date'] ?? '',
                        $r['remarks'] ?? '',
                        $date,
                    ]);
                }
                fclose($handle);
            }, 'PRSWSSP_Progress_Data_'.$date.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }
    }

    /**
     * Download list and sheet of sites where there is no progress (dormant, not reported, zero activity).
     */
    public function exportNoProgress(Request $request, ReportBuilder $reportBuilder)
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $request->get('date') ? Carbon::parse($request->get('date'))->toDateString() : Carbon::now($tz)->toDateString();
        $format = $request->get('format', 'xlsx');

        $fullPayload = $reportBuilder->payloadFor($date);
        $filters = array_filter($request->only(['phase', 'tehsil', 'contractor', 'village']));
        $payload = ! empty($filters) ? $reportBuilder->applyFilters($fullPayload, $filters) : $fullPayload;

        $all = collect($payload['register_rows'] ?? []);
        $noProgressRows = $all->filter(fn ($r) => in_array($r['day_status'] ?? '', ['dormant', 'not_reported']) || ((float) ($r['physical_progress'] ?? 0) <= 0 && (int) ($r['labour'] ?? 0) === 0))->values();

        // Sites still in tendering/procurement or administratively suspended have no progress
        // either, and belong in this sheet too — otherwise they silently never appear in any
        // downloadable sheet.
        if (empty($filters['phase'])) {
            $noProgressRows = $noProgressRows->concat($this->nonExecutionRegisterRows($date, $filters))->values();
        }

        $filename = 'PRSWSSP_No_Progress_Sites_'.$date.($format === 'csv' ? '.csv' : '.xlsx');

        try {
            return Excel::download(new NoProgressSitesExport($noProgressRows, $date), $filename, $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX);
        } catch (\Throwable $e) {
            return response()->streamDownload(function () use ($noProgressRows, $date) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['Site Code', 'Village Name', 'Tehsil', 'Package', 'Phase', 'Priority', 'Contractor', 'Issue Type', 'Status Description', 'Idle Days', 'Blocker Root Cause Category', 'Idle Reason Code', 'Physical Progress (%)', 'Target Completion Date', 'Remarks / Required Action', 'Report Date']);
                foreach ($noProgressRows as $r) {
                    $dayStatus = (string) ($r['day_status'] ?? '');
                    $issueType = match ($dayStatus) {
                        'dormant' => 'Dormant (Work Stopped / Idle)',
                        'not_reported' => 'Not Reported (No Daily Return)',
                        default => 'Zero Activity (No Progress)',
                    };
                    fputcsv($handle, [
                        $r['site_code'] ?? '',
                        $r['village'] ?? '',
                        $r['tehsil'] ?? '',
                        $r['package'] ?? '—',
                        $r['phase_abbr'] ?? '',
                        $r['group_name'] ?? $r['priority_code'] ?? '',
                        $r['contractor'] ?? 'Unassigned',
                        $issueType,
                        $r['status_label'] ?? '',
                        $r['idle_days'] ?? 0,
                        $r['top_level_reason'] ?? 'Unstated',
                        $r['reason_code'] ?? '',
                        ($r['physical_progress'] ?? 0).'%',
                        $r['expected_completion_date'] ?? '',
                        $r['remarks'] ?? '',
                        $date,
                    ]);
                }
                fclose($handle);
            }, 'PRSWSSP_No_Progress_Sites_'.$date.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }
    }

    /**
     * Rows for sites outside the execution register (tendering/procurement or administratively
     * suspended) in the same shape as a register row, so sheet downloads can include every site
     * rather than only those under active civil-works execution.
     *
     * @param  array{tehsil?: string|null, contractor?: string|null, village?: string|null}  $filters
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function nonExecutionRegisterRows(string $date, array $filters = []): \Illuminate\Support\Collection
    {
        $query = Site::whereIn('status', ['tendering', 'suspended'])
            ->with(['tehsil', 'phase', 'package', 'priority', 'contractor']);

        if (! empty($filters['tehsil'])) {
            $query->whereHas('tehsil', fn ($q) => $q->where('name', $filters['tehsil']));
        }
        if (! empty($filters['contractor'])) {
            $query->whereHas('contractor', fn ($q) => $q->where('name', $filters['contractor']));
        }
        if (! empty($filters['village'])) {
            $query->where('village_name', 'like', '%'.$filters['village'].'%');
        }

        return $query->get()->map(function ($site) use ($date) {
            $isPhase0 = $site->phase && (str_contains(strtolower($site->phase->name), '0') || str_contains(strtolower($site->phase->code ?? ''), '0'));
            $statusLabel = $site->status === 'tendering' ? 'Tendering / Procurement' : 'Suspended';

            return [
                'site_code' => $site->site_code,
                'village' => $site->village_name,
                'tehsil' => $site->tehsil->name ?? '',
                'package' => $site->package->code ?? '—',
                'phase_abbr' => $isPhase0 ? 'Ph-0' : 'Pilot',
                'group_name' => strtoupper($site->status),
                'priority_code' => (string) ($site->priority->code ?? ''),
                'contractor' => $site->contractor->name ?? 'Unassigned',
                'day_status' => $site->status,
                'status_label' => $statusLabel,
                'physical_progress' => $site->physical_progress ?? 0,
                'labour' => 0,
                'machinery' => 0,
                'idle_days' => 0,
                'top_level_reason' => $statusLabel,
                'reason_code' => '',
                'expected_completion_date' => $site->expected_completion_date?->toDateString(),
                'remarks' => $site->status === 'tendering'
                    ? 'Site under tendering / procurement — civil works not yet started.'
                    : 'Site suspended — not currently under execution.',
                'report_date' => $date,
            ];
        });
    }

    /**
     * API JSON summary endpoint for dynamic chart updating.
     */
    public function apiSummary(string $date, RollupService $rollupService)
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $today = Carbon::now($tz)->toDateString();

        if ($date === $today || request()->has('refresh')) {
            $payload = $rollupService->execute($date);
        } else {
            $summaryRecord = DailySummary::where(function ($q) use ($date) {
                $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
            })->first();

            $payload = $summaryRecord ? $summaryRecord->payload : $rollupService->execute($date);
        }

        return response()->json($payload);
    }

    /**
     * Site profile page with 30-day status strip & photo gallery (DASH-9).
     */
    public function showSite(Site $site)
    {
        $site->load(['phase', 'tehsil', 'package', 'priority', 'contractor']);

        // 30-day calendar strip
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(29);
        $snapshots = SiteDaySnapshot::where('site_id', $site->id)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn ($s) => Carbon::parse($s->report_date)->toDateString());

        $calendarStrip = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $snap = $snapshots->get($d);
            $calendarStrip[] = [
                'date' => $d,
                'status' => $snap ? $snap->day_status : 'not_reported',
                'band' => $snap ? $snap->idle_band : 'watch',
                'labour' => $snap ? $snap->labour : 0,
                'machinery' => $snap ? $snap->machinery : 0,
            ];
        }

        // Returns history and photos
        $returns = DailyReturn::with(['photos', 'submitter', 'approver', 'reason'])
            ->where('site_id', $site->id)
            ->latest('report_date')
            ->take(30)
            ->get();

        return view('dashboard.site_detail', compact('site', 'calendarStrip', 'returns'));
    }

    /**
     * Photo gallery by date/tehsil with lightbox (DASH-10).
     */
    public function gallery(Request $request)
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $request->get('date', Carbon::now($tz)->toDateString());
        $tehsilId = $request->get('tehsil_id');

        $query = ReturnPhoto::with('dailyReturn.site.tehsil')
            ->whereHas('dailyReturn', function ($q) use ($date, $tehsilId) {
                $q->where(function ($dq) use ($date) {
                    $dq->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
                });
                if ($tehsilId) {
                    $q->whereHas('site', fn ($sq) => $sq->where('tehsil_id', $tehsilId));
                }
            })
            ->latest('id');

        $photos = $query->paginate(24)->withQueryString();
        $tehsils = Tehsil::all();

        return view('dashboard.gallery', compact('photos', 'date', 'tehsils', 'tehsilId'));
    }
}
