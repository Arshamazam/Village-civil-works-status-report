@php
    $reportDate = \Carbon\Carbon::parse($payload['report_date'] ?? $date);
    $regRows = collect($payload['register_rows'] ?? []);

    // Flatten the register into lines (group headers + site rows), then fill pages column by column.
    // 41 lines per column keeps each register page on one A4 landscape sheet at this font size.
    $linesPerColumn = 41;
    $registerLines = [];
    $currentGroup = null;
    foreach ($regRows as $row) {
        if (($row['group_name'] ?? null) !== $currentGroup) {
            $currentGroup = $row['group_name'] ?? null;
            [$groupTitle, $groupDetail] = array_pad(explode(' · ', (string) $currentGroup, 2), 2, null);
            $groupCount = $regRows->where('group_name', $currentGroup)->count();
            $registerLines[] = ['type' => 'group', 'label' => trim("{$groupTitle} · {$groupCount} sites".($groupDetail ? " · {$groupDetail}" : ''))];
        }
        $registerLines[] = ['type' => 'row', 'row' => $row];
    }

    $registerColumns = [];
    $column = [];
    $columnGroup = null;
    foreach ($registerLines as $line) {
        if (count($column) >= $linesPerColumn) {
            $registerColumns[] = $column;
            $column = [];
        }
        if ($column === [] && $line['type'] === 'row' && $columnGroup !== null) {
            $column[] = ['type' => 'group', 'label' => $columnGroup.' (cont.)'];
        }
        if ($line['type'] === 'group') {
            $columnGroup = $line['label'];
        }
        $column[] = $line;
    }
    if ($column !== []) {
        $registerColumns[] = $column;
    }
    $registerPages = array_chunk($registerColumns, 2);
    $totalPages = 1 + count($registerPages);
    $registerBands = [
        'dormant' => ['bg' => '#fde8e8', 'edge' => '#dc2626', 'label' => 'Dormant — nothing on site'],
        'past_target' => ['bg' => '#fdebd3', 'edge' => '#ea580c', 'label' => 'Working, past target'],
        'watch' => ['bg' => '#fef6d6', 'edge' => '#eab308', 'label' => 'Watch — not reported, thin (<5), blocker, target ≤10 days'],
        'on_track' => ['bg' => '#e8f5ec', 'edge' => '#16a34a', 'label' => 'On track / completed'],
    ];
    $trendPdf = ['up' => ['#15803d', '▲ '], 'down' => ['#dc2626', '▼ '], 'same' => ['#64748b', '= '], 'none' => ['#94a3b8', '']];
    $causeBoxes = [
        ['key' => 'Contractor', 'label' => 'Contractor', 'colour' => '#ea580c', 'empty' => 'Not mobilised / labour n/a'],
        ['key' => "VLD's", 'label' => "VLD's", 'colour' => '#e11d48', 'empty' => 'Village-level issues'],
        ['key' => 'Govt. Land', 'label' => 'Govt Land', 'colour' => '#059669', 'empty' => 'Land / site possession'],
        ['key' => 'PRMSC', 'label' => 'PRMSC', 'colour' => '#dc2626', 'empty' => 'Payment, EOT, advance, testing'],
        ['key' => 'External', 'label' => 'External', 'colour' => '#ea580c', 'empty' => 'NOC, community, weather'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PRSWSSP Daily Site Mobilization Report - {{ $reportDate->toDateString() }}</title>
    <style>
        @page { size: A4 landscape; margin: 5mm 7mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; color: #0f172a; line-height: 1.2; margin: 0; padding: 0; background: #ffffff; }
        table { border-collapse: collapse; }
        .page-break { page-break-before: always; }

        /* Header banner */
        .header-banner { width: 100%; background: #14532d; color: #ffffff; margin-bottom: 4px; }
        .header-banner td { padding: 5px 9px; vertical-align: middle; }
        .header-dept { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; color: #86efac; }
        .header-title { font-size: 12pt; font-weight: bold; color: #ffffff; margin: 2px 0 1px; }
        .header-sub { font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.4px; color: #bbf7d0; }
        .header-meta { text-align: right; }
        .header-meta-date { font-size: 9pt; font-weight: bold; color: #ffffff; }
        .header-meta-line { font-size: 6.5pt; color: #bbf7d0; margin-top: 2px; }

        /* Section wrapper */
        .section { width: 100%; border: 1px solid #e2e8f0; margin-bottom: 4px; }
        .section > tbody > tr > td, .section > tr > td { padding: 3px 6px; }
        .section-head { width: 100%; border-bottom: 1px solid #e2e8f0; margin-bottom: 3px; }
        .section-head td { padding: 0 0 2px 0; }
        .section-title { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; color: #0f172a; }
        .section-title-blue { color: #1e3a8a; }
        .section-sub { font-size: 6.5pt; color: #64748b; text-align: right; }

        /* Card grids */
        .grid { width: 100%; border-collapse: separate; border-spacing: 4px 0; }
        .card { border: 1px solid #cbd5e1; background: #f8fafc; padding: 3px 5px; vertical-align: top; }
        .card-center { text-align: center; }
        .card-label { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; color: #64748b; }
        .card-num { font-size: 15pt; font-weight: bold; line-height: 1.1; color: #0f172a; margin: 1px 0; }
        .card-sub { font-size: 6pt; color: #64748b; }
        .chip { font-size: 6pt; font-weight: bold; padding: 0 3px; }
        .badge { display: inline-block; color: #ffffff; font-size: 6pt; font-weight: bold; text-transform: uppercase; padding: 1px 4px; }
        .pill-dark { display: inline-block; background: #0f172a; color: #ffffff; font-size: 6.5pt; font-weight: bold; padding: 1px 5px; }

        /* Stacked status bar */
        .bar { width: 100%; height: 5px; background: #e2e8f0; margin: 3px 0; }
        .bar td { height: 5px; padding: 0; }

        /* Data tables */
        .data-table { width: 100%; font-size: 7pt; }
        .data-table th { background: #f1f5f9; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 6.5pt; padding: 3px 4px; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: left; }
        .data-table td { padding: 2px 4px; border-bottom: 1px solid #f1f5f9; }
        .data-table .total-row td { background: #f1f5f9; font-weight: bold; border-top: 1px solid #cbd5e1; }

        /* Register */
        .reg-table { width: 100%; font-size: 6pt; }
        .reg-table th { background: #14532d; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 5.5pt; padding: 2px 3px; white-space: nowrap; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: left; }
        .reg-table td { padding: 1.4px 3px; border-bottom: 0.5px solid #ffffff; white-space: nowrap; }
        .reg-table td.reg-group-cell { background: #dcefe2; font-weight: bold; font-size: 5.5pt; text-transform: uppercase; color: #14532d; border-top: 1px solid #b7d9c2; }
        .reg-table td.reg-empty { border-bottom: none; }
        .reg-table .reg-gap { width: 1%; border: none; background: #ffffff; }
        .tag { color: #ffffff; background: #e11d48; font-size: 5.5pt; font-weight: bold; padding: 0 2px; }
        .legend { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 6.5pt; margin-bottom: 4px; }
        .legend td { padding: 3px 6px; }
        .swatch { display: inline-block; width: 7px; height: 7px; margin-right: 2px; }

        /* Footer */
        .footer { width: 100%; border-top: 1px solid #cbd5e1; font-size: 6pt; color: #64748b; margin-top: 3px; }
        .footer td { padding-top: 3px; vertical-align: top; }

        .c-green { color: #15803d; } .c-dkgreen { color: #14532d; } .c-red { color: #dc2626; } .c-amber { color: #d97706; } .c-sky { color: #0369a1; } .c-muted { color: #64748b; }
        .right { text-align: right; } .center { text-align: center; } .bold { font-weight: bold; }
    </style>
</head>
<body>

{{-- ===================== PAGE 1: EXECUTIVE SUMMARY (same order as the dashboard) ===================== --}}

<table class="header-banner">
    <tr>
        <td style="width: 62%;">
            <div class="header-dept">Punjab Rural Municipal Services Company (PRMSC)</div>
            <div class="header-title">PRSWSSP — DAILY SITE MOBILIZATION REPORT</div>
            <div class="header-sub">Executive mobilization snapshot &bull; Form DSM-1 rollup</div>
        </td>
        <td style="width: 38%;" class="header-meta">
            <div class="header-meta-date">Report date: {{ $reportDate->format('D, d M Y') }}</div>
            <div class="header-meta-line">
                Day {{ $payload['day_of_tracking'] ?? 1 }} of tracking &bull; {{ $dashStats['total_count'] }} villages under execution &bull; {{ $payload['tendering_count'] ?? 0 }} in tendering
            </div>
            <div class="header-meta-line">Generated {{ \Carbon\Carbon::now('Asia/Karachi')->format('d M Y, H:i') }} PKT</div>
        </td>
    </tr>
</table>

{{-- 1. Villages under execution --}}
<table class="section">
    <tr><td>
        <table class="section-head">
            <tr>
                <td class="section-title section-title-blue">Villages Under Execution</td>
                <td class="section-sub">
                    Scope: <strong>{{ $dashStats['total_count'] }} villages</strong> under execution (after COC) &bull; <strong>{{ $payload['tendering_count'] ?? 0 }} villages</strong> in tendering phase
                </td>
            </tr>
        </table>
        <table class="grid">
            <tr>
                <td class="card card-center" style="width: 20%;">
                    <div class="card-label">Total Villages (after COC)</div>
                    <div class="card-num">{{ $dashStats['total_count'] }}</div>
                    <div class="card-sub">Pilot {{ $dashStats['pilot_count'] }} &bull; Phase-0 {{ $dashStats['phase0_count'] }}</div>
                </td>
                <td class="card card-center" style="width: 20%; background: #f0fdf4; border-color: #86efac;">
                    <div class="card-label c-dkgreen">Completed Villages <span class="chip" style="background: #bbf7d0;">{{ $dashStats['completed_pct'] }}%</span></div>
                    <div class="card-num c-green">{{ $dashStats['completed_count'] }}</div>
                    <div class="card-sub c-green">At 100% progress {{ $dashStats['full_progress_count'] }} &bull; COC pending</div>
                </td>
                <td class="card card-center" style="width: 20%; background: #f0f9ff; border-color: #7dd3fc;">
                    <div class="card-label c-sky">Remaining Villages</div>
                    <div class="card-num c-sky">{{ $dashStats['remaining_count'] }}</div>
                    <div class="card-sub c-sky">{{ $dashStats['remaining_pct'] }}% of scope still to finish</div>
                </td>
                <td class="card card-center" style="width: 20%;">
                    <div class="card-label">Total Labour</div>
                    <div class="card-num">{{ number_format($payload['total_labour'] ?? 0) }}</div>
                    <div class="card-sub">on {{ $payload['active_count'] ?? 0 }} active sites &bull; {{ $payload['thinly_manned_count'] ?? 0 }} thin (&lt;5)</div>
                </td>
                <td class="card card-center" style="width: 20%;">
                    <div class="card-label">Total Machinery</div>
                    <div class="card-num">{{ number_format($payload['total_machinery'] ?? 0) }}</div>
                    <div class="card-sub">avg {{ ($payload['active_count'] ?? 0) > 0 ? round(($payload['total_machinery'] ?? 0) / $payload['active_count'], 1) : 0 }} units per active site</div>
                </td>
            </tr>
        </table>
    </td></tr>
</table>

{{-- 2. Position by completion priority --}}
<table class="section">
    <tr><td>
        <table class="section-head">
            <tr>
                <td class="section-title">Position by Completion Priority</td>
                <td class="section-sub">
                    Bar: <span class="swatch" style="background: #14532d;"></span>completed
                    <span class="swatch" style="background: #22c55e;"></span>active
                    <span class="swatch" style="background: #ef4444;"></span>dormant
                    <span class="swatch" style="background: #f59e0b;"></span>not reported
                </td>
            </tr>
        </table>
        <table class="grid">
            <tr>
                @foreach($priorityDisplay as $p)
                    @php $barTotal = max(1, $p['sites']); @endphp
                    <td class="card" style="width: 20%; background: #ffffff;">
                        <div><span class="bold" style="font-size: 8pt;">{{ $p['name'] }}</span> <span class="c-muted bold">{{ $p['sites'] }} sites</span></div>
                        <div class="card-sub">Target: <span class="bold" style="color: #334155;">{{ $p['target'] }}</span></div>
                        <div style="margin-top: 2px;"><span class="badge" style="background: {{ $p['badge_hex'] }};">{{ $p['badge'] }}</span></div>
                        <table class="bar">
                            <tr>
                                @foreach([['done', '#14532d'], ['active', '#22c55e'], ['dormant', '#ef4444'], ['nr', '#f59e0b']] as [$segment, $colour])
                                    @if($p[$segment] > 0)
                                        <td style="width: {{ round(($p[$segment] / $barTotal) * 100, 1) }}%; background: {{ $colour }};"></td>
                                    @endif
                                @endforeach
                            </tr>
                        </table>
                        <div style="font-size: 7pt;" class="bold">
                            @if($p['done'] > 0)<span class="c-dkgreen">Done {{ $p['done'] }}</span> &bull; @endif
                            <span class="c-green">Act {{ $p['active'] }}</span> &bull;
                            <span class="c-red">Dorm {{ $p['dormant'] }}</span>
                            @if($p['nr'] > 0) &bull; <span class="c-amber">NR {{ $p['nr'] }}</span>@endif
                        </div>
                        <table style="width: 100%; border-top: 1px solid #f1f5f9; margin-top: 2px;">
                            <tr>
                                <td class="card-sub" style="padding-top: 2px;">Labour <strong style="color: #0f172a;">{{ $p['labour'] }}</strong></td>
                                <td class="card-sub right" style="padding-top: 2px;">Machinery <strong style="color: #0f172a;">{{ $p['machinery'] }}</strong></td>
                            </tr>
                        </table>
                    </td>
                @endforeach
            </tr>
        </table>
    </td></tr>
</table>

{{-- 3. Site status today --}}
<table class="section">
    <tr><td>
        <table class="section-head">
            <tr>
                <td class="section-title">Site Status Today</td>
                <td class="section-sub">Remaining {{ $dashStats['remaining_count'] }} villages = Active + Dormant + Not reported</td>
            </tr>
        </table>
        <table class="grid">
            <tr>
                @foreach([
                    ['Active Sites', 'active', $payload['active_count'] ?? 0, $dashStats['active_pct'], '#15803d', 'Labour '.number_format($payload['total_labour'] ?? 0).' &bull; Machinery '.($payload['total_machinery'] ?? 0)],
                    ['Dormant Sites', 'dormant', $payload['dormant_count'] ?? 0, $dashStats['dormant_pct'], '#dc2626', 'No labour, no machinery on site'],
                    ['Not Reported', 'not_reported', $payload['nr_count'] ?? 0, $dashStats['nr_pct'], '#d97706', 'Status left blank in field return'],
                ] as [$label, $status, $count, $pct, $colour, $note])
                    <td class="card" style="width: 33.3%; background: #ffffff; border: 1.5px solid {{ $colour }}; border-top: 3px solid {{ $colour }};">
                        <table style="width: 100%;">
                            <tr>
                                <td class="bold" style="font-size: 8pt; text-transform: uppercase; color: {{ $colour }};">{{ $label }}</td>
                                <td class="right bold" style="font-size: 7pt; color: {{ $colour }};">{{ $pct }}% of remaining</td>
                            </tr>
                            <tr>
                                <td class="bold" style="font-size: 16pt; line-height: 1.1; color: {{ $colour }};">{{ $count }}</td>
                                <td class="right"><span class="pill-dark">Pilot {{ $dashStats['phase_split'][$status]['pilot'] }} &bull; Phase-0 {{ $dashStats['phase_split'][$status]['phase0'] }}</span></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="card-sub" style="border-top: 1px solid #f1f5f9; padding-top: 2px; color: #475569;">{!! $note !!}</td>
                            </tr>
                        </table>
                    </td>
                @endforeach
            </tr>
        </table>
    </td></tr>
</table>

{{-- 4. Dormant sites by cause --}}
<table class="section">
    <tr><td>
        <table class="section-head">
            <tr>
                <td class="section-title">Dormant Sites by Cause</td>
                <td class="section-sub">{{ $dashStats['dormant_count'] }} dormant sites split by responsible side (from the Remarks column)</td>
            </tr>
        </table>
        <table class="grid">
            <tr>
                @foreach($causeBoxes as $box)
                    @php $cause = $dashStats['causes'][$box['key']]; @endphp
                    <td class="card" style="width: 20%; background: #ffffff; border: 1.5px solid {{ $box['colour'] }};">
                        <table style="width: 100%;">
                            <tr>
                                <td class="bold" style="font-size: 7pt; text-transform: uppercase; color: {{ $box['colour'] }};">{{ $box['label'] }}</td>
                                <td class="right bold" style="font-size: 6.5pt; color: {{ $box['colour'] }};">{{ $cause['count'] > 0 ? $cause['pct'].'%' : 'none today' }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="bold" style="font-size: 13pt; line-height: 1.1; color: {{ $box['colour'] }};">{{ $cause['count'] }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-size: 6.5pt; color: #334155; border-top: 1px solid #f1f5f9; padding-top: 2px;">
                                    @forelse($cause['lines'] as $lineLabel => $lineCount)
                                        {{ $lineLabel }} {{ $lineCount }}@if(! $loop->last)<br>@endif
                                    @empty
                                        <span class="c-muted">{{ $box['empty'] }}</span>
                                    @endforelse
                                </td>
                            </tr>
                        </table>
                    </td>
                @endforeach
            </tr>
        </table>
    </td></tr>
</table>

{{-- 5. Payment-related dormancy --}}
@if($dashStats['payment_related_count'] > 0)
<table style="width: 100%; background: #fff1f2; border: 1px solid #fecdd3; margin-bottom: 5px;">
    <tr>
        <td style="width: 15%; padding: 4px;">
            <div style="background: #991b1b; color: #ffffff; text-align: center; padding: 4px;">
                <div style="font-size: 6pt; font-weight: bold; text-transform: uppercase; color: #fecdd3;">Payment-related dormancy</div>
                <div style="font-size: 15pt; font-weight: bold; line-height: 1.1;">{{ $dashStats['payment_related_count'] }}</div>
                <div style="font-size: 6pt; color: #fecdd3;">{{ $dashStats['payment_related_pct'] }}% of all dormant sites</div>
            </div>
        </td>
        <td style="width: 85%; padding: 4px 8px; vertical-align: middle;">
            <div class="bold" style="font-size: 8pt; color: #881337;">
                {{ $dashStats['payment_count'] }} {{ \Illuminate\Support\Str::plural('site', $dashStats['payment_count']) }} halted over pending payments / IPCs ({{ $dashStats['payment_pct'] }}%)@if($dashStats['advance_count'] > 0) + {{ $dashStats['advance_count'] }} awaiting mobilization advance ({{ $dashStats['advance_pct'] }}%)@endif.
            </div>
            @if($dashStats['payment_list'])
                <div style="font-size: 7pt; color: #334155; margin-top: 2px;"><strong>Payments pending:</strong> {{ $dashStats['payment_list'] }}</div>
            @endif
            @if($dashStats['advance_list'])
                <div style="font-size: 7pt; color: #334155; margin-top: 2px;"><strong>Mob. advance pending:</strong> {{ $dashStats['advance_list'] }}</div>
            @endif
        </td>
    </tr>
</table>
@endif

{{-- 6. Phase progress summary --}}
<table class="section">
    <tr><td>
        <table class="section-head">
            <tr>
                <td class="section-title">Phase Progress Summary</td>
                <td class="section-sub">Scope overview</td>
            </tr>
        </table>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Phase</th>
                    <th class="center">Villages</th>
                    <th class="center c-dkgreen">Completed</th>
                    <th class="center c-green">Active</th>
                    <th class="center c-red">Dormant</th>
                    <th class="center c-amber">Not Reported</th>
                    <th class="center">% Active</th>
                    <th class="right">Labour / Machinery</th>
                </tr>
            </thead>
            <tbody>
                @foreach([['Pilot Phase', fn ($r) => ($r['phase_abbr'] ?? '') !== 'Ph-0'], ['Phase-0', fn ($r) => ($r['phase_abbr'] ?? '') === 'Ph-0']] as [$phaseName, $phaseMatcher])
                    @php
                        $phaseRows = $regRows->filter($phaseMatcher);
                        $phaseActive = $phaseRows->where('day_status', 'active');
                    @endphp
                    <tr>
                        <td class="bold">{{ $phaseName }}</td>
                        <td class="center bold">{{ $phaseRows->count() }}</td>
                        <td class="center bold c-dkgreen">{{ $phaseRows->where('day_status', 'completed')->count() }}</td>
                        <td class="center bold c-green">{{ $phaseActive->count() }}</td>
                        <td class="center bold c-red">{{ $phaseRows->where('day_status', 'dormant')->count() }}</td>
                        <td class="center bold c-amber">{{ $phaseRows->where('day_status', 'not_reported')->count() }}</td>
                        <td class="center bold">{{ $phaseRows->count() > 0 ? round(($phaseActive->count() / $phaseRows->count()) * 100) : 0 }}%</td>
                        <td class="right">{{ number_format($phaseActive->sum('labour')) }} / {{ number_format($phaseActive->sum('machinery')) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total</td>
                    <td class="center">{{ $dashStats['total_count'] }}</td>
                    <td class="center c-dkgreen">{{ $dashStats['completed_count'] }}</td>
                    <td class="center c-green">{{ $payload['active_count'] ?? 0 }}</td>
                    <td class="center c-red">{{ $payload['dormant_count'] ?? 0 }}</td>
                    <td class="center c-amber">{{ $payload['nr_count'] ?? 0 }}</td>
                    <td class="center">{{ $dashStats['total_count'] > 0 ? round((($payload['active_count'] ?? 0) / $dashStats['total_count']) * 100) : 0 }}%</td>
                    <td class="right">{{ number_format($payload['total_labour'] ?? 0) }} / {{ number_format($payload['total_machinery'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </td></tr>
</table>

<table class="footer">
    <tr>
        <td style="width: 82%;">
            <strong>Active</strong> = reported Active by field staff (L = labour, M = machinery). <strong>Dormant</strong> = reported Inactive. <strong>Not reported</strong> = status left blank.
            <strong>Completed</strong> = reported completed (COC pending). <strong>Scope</strong> = {{ $dashStats['total_count'] }} villages under execution (Pilot {{ $dashStats['pilot_count'] }} + Phase-0 {{ $dashStats['phase0_count'] }}), plus {{ $payload['tendering_count'] ?? 0 }} in tendering.
            @if(count($dashStats['zero_active_villages']) > 0)
                <strong>Reported Active with 0 labour / 0 machinery:</strong> {{ implode(', ', $dashStats['zero_active_villages']) }}.
            @endif
        </td>
        <td style="width: 18%;" class="right">PRSWSSP Daily Site Mobilization Report<br>Page 1 of {{ $totalPages }}</td>
    </tr>
</table>

{{-- ===================== PAGE 2+: SITE REGISTER (two columns, same columns as the dashboard) ===================== --}}
@foreach($registerPages as $pageIndex => $pageColumns)
    @php
        [$leftLines, $rightLines] = [$pageColumns[0], $pageColumns[1] ?? []];
        $lineCount = max(count($leftLines), count($rightLines));
    @endphp
    <div class="page-break">
        <table class="header-banner">
            <tr>
                <td style="width: 65%;">
                    <div class="header-dept">Punjab Rural Municipal Services Company (PRMSC)</div>
                    <div class="header-title" style="font-size: 11pt;">Site Register</div>
                </td>
                <td style="width: 35%;" class="header-meta">
                    <div class="header-meta-date">Report date: {{ $reportDate->format('D, d M Y') }}</div>
                    <div class="header-meta-line">Register part {{ $pageIndex + 1 }} of {{ count($registerPages) }} &bull; {{ $regRows->count() }} villages</div>
                </td>
            </tr>
        </table>

        <table class="legend">
            <tr>
                <td>
                    @foreach($registerBands as $band)
                        <span class="swatch" style="width: 12px; background: {{ $band['bg'] }}; border: 1px solid {{ $band['edge'] }};"></span> {{ $band['label'] }} &nbsp;&nbsp;
                    @endforeach
                    <span class="tag">PAYMENT</span> <span class="bold" style="color: #b91c1c;">payment-related</span>
                </td>
            </tr>
        </table>

        <table class="reg-table">
            <thead>
                <tr>
                    @foreach([1, 2] as $side)
                        <th style="width: 9%;">Package</th>
                        <th style="width: 3.5%;">Phase</th>
                        <th style="width: 13%;">Village</th>
                        <th style="width: 8.5%;">Tehsil</th>
                        <th style="width: 11%;">Status</th>
                        <th style="width: 3%;" class="center">Act. days</th>
                        <th style="width: 5%;">vs prev.</th>
                        @if($side === 1)<th class="reg-gap"></th>@endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < $lineCount; $i++)
                    <tr>
                        @foreach([$leftLines[$i] ?? null, $rightLines[$i] ?? null] as $side => $line)
                            @if($line === null)
                                <td colspan="7" class="reg-empty"></td>
                            @elseif($line['type'] === 'group')
                                <td colspan="7" class="reg-group-cell">{{ $line['label'] }}</td>
                            @else
                                @php
                                    $r = $line['row'];
                                    $band = $registerBands[$r['row_band'] ?? 'on_track'] ?? $registerBands['on_track'];
                                    $trend = $r['previous_entry'] ?? ['label' => '—', 'tone' => 'none'];
                                    [$trendColour, $trendIcon] = $trendPdf[$trend['tone']] ?? $trendPdf['none'];
                                @endphp
                                <td style="color: #475569; background: {{ $band['bg'] }}; border-left: 3px solid {{ $band['edge'] }};">{{ trim(preg_replace('/\s*\(.*\)/', '', $r['package'] ?? '—')) }}</td>
                                <td style="color: #475569; background: {{ $band['bg'] }};">{{ $r['phase_abbr'] ?? 'Pilot' }}</td>
                                <td class="bold" style="background: {{ $band['bg'] }};">{{ $r['village'] }}</td>
                                <td style="color: #334155; background: {{ $band['bg'] }};">{{ $r['tehsil'] }}</td>
                                <td style="background: {{ $band['bg'] }};">
                                    @if($r['day_status'] === 'active')
                                        <span class="bold c-green">Active {{ $r['labour'] }}L &bull; {{ $r['machinery'] }}M</span>
                                    @elseif($r['day_status'] === 'not_reported')
                                        <span class="bold" style="color: #b45309;">No report</span>
                                    @elseif($r['day_status'] === 'completed')
                                        <span class="bold c-dkgreen">Completed ({{ $r['physical_progress'] ?? 0 }}%)</span>
                                    @else
                                        @php $label = \App\Services\DashboardSummary::shortReasonLabel($r); @endphp
                                        <span class="bold c-red">Dormant</span>
                                        @if(in_array($label, ['Payment', 'Mob. advance']))
                                            <span class="tag">{{ strtoupper($label) }}</span>
                                        @else
                                            <span class="c-red">&bull; {{ $label }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="center bold" style="background: {{ $band['bg'] }}; color: {{ ($r['active_days'] ?? 0) > 0 ? '#15803d' : '#64748b' }};">{{ $r['active_days'] ?? 0 }}</td>
                                <td style="background: {{ $band['bg'] }}; color: {{ $trendColour }}; font-size: 5.5pt;">{{ $trendIcon }}{{ $trend['label'] }}</td>
                            @endif
                            @if($side === 0)<td class="reg-gap"></td>@endif
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="footer">
            <tr>
                <td style="width: 80%;">PRSWSSP site register &bull; Grouped by completion priority, dormant sites first within each group</td>
                <td style="width: 20%;" class="right">Page {{ $pageIndex + 2 }} of {{ $totalPages }}</td>
            </tr>
        </table>
    </div>
@endforeach

</body>
</html>
