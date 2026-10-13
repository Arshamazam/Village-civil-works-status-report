@php
    $maxVal = 1;
    foreach ($history as $h) {
        if ($h['active'] > $maxVal) {
            $maxVal = $h['active'];
        }
    }
    // Round maxVal up to multiple of 20
    $yMax = ceil($maxVal / 20) * 20;
    if ($yMax < 100) $yMax = 100;

    $chartWidth = $width ?? 320;
    $chartHeight = $height ?? 90;
    $barWidth = 14;
    $gap = 8;
    $leftPad = 25;
    $bottomPad = 20;
    $plotHeight = $chartHeight - $bottomPad;
@endphp

<svg width="{{ $chartWidth }}" height="{{ $chartHeight }}" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}">
    <!-- Grid line at top & mid -->
    <line x1="{{ $leftPad }}" y1="5" x2="{{ $chartWidth }}" y2="5" stroke="#f1f5f9" stroke-width="1" />
    <text x="2" y="10" font-family="sans-serif" font-size="8" fill="#94a3b8">{{ $yMax }}</text>

    <line x1="{{ $leftPad }}" y1="{{ $plotHeight / 2 }}" x2="{{ $chartWidth }}" y2="{{ $plotHeight / 2 }}" stroke="#f1f5f9" stroke-width="1" />
    <text x="2" y="{{ ($plotHeight / 2) + 3 }}" font-family="sans-serif" font-size="8" fill="#94a3b8">{{ round($yMax / 2) }}</text>

    <!-- Baseline -->
    <line x1="{{ $leftPad }}" y1="{{ $plotHeight }}" x2="{{ $chartWidth }}" y2="{{ $plotHeight }}" stroke="#cbd5e1" stroke-width="1" />

    <!-- 14 Bars -->
    @foreach($history as $idx => $item)
        @php
            $x = $leftPad + ($idx * ($barWidth + $gap));
            $barH = ($item['active'] / $yMax) * ($plotHeight - 8);
            $y = $plotHeight - $barH;
            $isToday = $idx === (count($history) - 1);
            $barColor = $isToday ? '#059669' : '#10b981';
        @endphp
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barH }}" rx="2" fill="{{ $barColor }}" />
        
        <!-- Day label -->
        <text x="{{ $x + ($barWidth / 2) }}" y="{{ $chartHeight - 4 }}" font-family="sans-serif" font-size="7.5" fill="#64748b" text-anchor="middle">
            {{ $item['day'] }}
        </text>

        <!-- Value on top of latest bar -->
        @if($isToday)
        <text x="{{ $x + ($barWidth / 2) }}" y="{{ $y - 3 }}" font-family="sans-serif" font-size="7.5" font-weight="bold" fill="#047857" text-anchor="middle">
            {{ $item['active'] }}
        </text>
        @endif
    @endforeach
</svg>
