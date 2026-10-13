@php
    $total = $active + $dormant + $nr;
    $r = 38;
    $cx = 50;
    $cy = 50;
    $circumference = 2 * M_PI * $r; // ~238.76

    $activePct = $total > 0 ? $active / $total : 0;
    $dormantPct = $total > 0 ? $dormant / $total : 0;
    $nrPct = $total > 0 ? $nr / $total : 0;

    $activeDash = $activePct * $circumference;
    $dormantDash = $dormantPct * $circumference;
    $nrDash = $nrPct * $circumference;

    $activeOffset = 0;
    $dormantOffset = -$activeDash;
    $nrOffset = -($activeDash + $dormantDash);
@endphp

<svg width="{{ $size ?? 100 }}" height="{{ $size ?? 100 }}" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
    <!-- Background circle -->
    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#e2e8f0" stroke-width="18" />

    @if($total > 0)
        @if($active > 0)
            <!-- Active (Green #10b981) -->
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#10b981" stroke-width="18"
                    stroke-dasharray="{{ $activeDash }} {{ $circumference }}"
                    stroke-dashoffset="{{ $activeOffset }}" />
        @endif

        @if($dormant > 0)
            <!-- Dormant (Amber #f59e0b) -->
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#f59e0b" stroke-width="18"
                    stroke-dasharray="{{ $dormantDash }} {{ $circumference }}"
                    stroke-dashoffset="{{ $dormantOffset }}" />
        @endif

        @if($nr > 0)
            <!-- NR (Red #ef4444) -->
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#ef4444" stroke-width="18"
                    stroke-dasharray="{{ $nrDash }} {{ $circumference }}"
                    stroke-dashoffset="{{ $nrOffset }}" />
        @endif
    @endif
</svg>
