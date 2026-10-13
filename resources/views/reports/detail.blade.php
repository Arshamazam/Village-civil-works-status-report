@php
    $reportDate = \Carbon\Carbon::parse($date);
    $statusStyles = [
        'active' => ['Active', '#15803d', '#dcfce7'],
        'dormant' => ['Dormant', '#b91c1c', '#fee2e2'],
        'not_reported' => ['No report', '#b45309', '#fef3c7'],
        'completed' => ['Completed', '#ffffff', '#14532d'],
        'tendering' => ['Tendering', '#6b21a8', '#f3e8ff'],
    ];
    $filterLabels = array_filter([
        'Phase / priority' => $filters['phase'] ?? null,
        'Tehsil' => $filters['tehsil'] ?? null,
        'Contractor' => $filters['contractor'] ?? null,
        'Village' => $filters['village'] ?? null,
        'Search' => $search,
    ]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $detail['title'] }} — {{ $reportDate->format('d M Y') }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 9mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5pt; color: #0f172a; margin: 0; }
        table { border-collapse: collapse; }
        .header { width: 100%; background: #14532d; color: #ffffff; }
        .header td { padding: 7px 10px; vertical-align: middle; }
        .dept { font-size: 6.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; color: #86efac; }
        .title { font-size: 13pt; font-weight: bold; margin: 2px 0; }
        .subtitle { font-size: 7pt; color: #bbf7d0; }
        .meta { text-align: right; font-size: 7pt; color: #bbf7d0; }
        .meta strong { color: #ffffff; font-size: 9pt; }
        .bar { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; margin: 6px 0; }
        .bar td { padding: 5px 8px; font-size: 7.5pt; }
        .stat { display: inline-block; margin-right: 16px; }
        .stat span { color: #64748b; }
        .filters { font-size: 7pt; color: #475569; margin-bottom: 5px; }
        .list { width: 100%; }
        .list th { background: #14532d; color: #ffffff; font-size: 6.5pt; text-transform: uppercase; text-align: left; padding: 4px 5px; }
        .list td { padding: 3.5px 5px; border-bottom: 0.5px solid #e2e8f0; vertical-align: middle; }
        .list tr:nth-child(even) td { background: #f8fafc; }
        .pill { font-size: 6.5pt; font-weight: bold; padding: 1px 5px; }
        .center { text-align: center; }
        .muted { color: #64748b; }
        .bold { font-weight: bold; }
        .footer { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 6.5pt; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 2px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 65%;">
                <div class="dept">Punjab Rural Municipal Services Company (PRMSC) &bull; PRSWSSP</div>
                <div class="title">{{ $detail['title'] }}</div>
                <div class="subtitle">{{ $detail['subtitle'] }}</div>
            </td>
            <td style="width: 35%;" class="meta">
                <strong>Report date: {{ $reportDate->format('D, d M Y') }}</strong><br>
                {{ $detail['rows']->count() }} {{ \Illuminate\Support\Str::plural('scheme', $detail['rows']->count()) }} listed<br>
                Generated {{ \Carbon\Carbon::now('Asia/Karachi')->format('d M Y, H:i') }} PKT
            </td>
        </tr>
    </table>

    <table class="bar">
        <tr>
            <td>
                @foreach($detail['stats'] as $label => $value)
                    <span class="stat"><span>{{ $label }}:</span> <strong>{{ $value }}</strong></span>
                @endforeach
            </td>
        </tr>
    </table>

    @if($filterLabels)
        <div class="filters">
            <strong>Filtered by:</strong>
            @foreach($filterLabels as $label => $value)
                {{ $label }}: <strong>{{ $value }}</strong>@if(! $loop->last) &bull; @endif
            @endforeach
        </div>
    @endif

    <table class="list">
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 9%;">Package</th>
                <th style="width: 15%;">Village</th>
                <th style="width: 10%;">Tehsil</th>
                <th style="width: 18%;">Contractor</th>
                <th style="width: 8%;" class="center">Status</th>
                <th style="width: 7%;" class="center">Progress</th>
                <th style="width: 6%;" class="center">Labour</th>
                <th style="width: 7%;" class="center">Machinery</th>
                <th style="width: 17%;">Remarks / cause</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detail['rows'] as $index => $row)
                @php [$statusLabel, $statusColour, $statusBg] = $statusStyles[$row['day_status'] ?? ''] ?? [ucfirst((string) ($row['day_status'] ?? '—')), '#334155', '#f1f5f9']; @endphp
                <tr>
                    <td class="muted">{{ $index + 1 }}</td>
                    <td class="muted">{{ $row['package'] ?? '—' }}</td>
                    <td class="bold">{{ $row['village'] ?? '' }}</td>
                    <td>{{ $row['tehsil'] ?? '' }}</td>
                    <td class="muted">{{ $row['contractor'] ?? '—' }}</td>
                    <td class="center"><span class="pill" style="color: {{ $statusColour }}; background: {{ $statusBg }};">{{ $statusLabel }}</span></td>
                    <td class="center bold">{{ $row['physical_progress'] ?? 0 }}%</td>
                    <td class="center bold" style="color: {{ ($row['labour'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">{{ $row['labour'] ?? 0 }}</td>
                    <td class="center bold" style="color: {{ ($row['machinery'] ?? 0) > 0 ? '#15803d' : '#94a3b8' }};">{{ $row['machinery'] ?? 0 }}</td>
                    <td class="muted">{{ ($row['day_status'] ?? '') === 'dormant' ? \App\Services\DashboardSummary::shortReasonLabel($row) : (($row['day_status'] ?? '') === 'active' ? '—' : ($row['status_label'] ?? '—')) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="center muted" style="padding: 14px;">No schemes match this selection.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">PRSWSSP Daily Site Mobilization &bull; {{ $detail['title'] }} &bull; {{ $reportDate->format('d M Y') }}</div>

</body>
</html>
