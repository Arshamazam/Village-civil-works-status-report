@php
    $r = 38;
    $cx = 50;
    $cy = 50;
    $circumference = 2 * M_PI * $r; // ~238.76
    $clampedPct = max(0, min(100, $pct ?? 0));
    $dash = ($clampedPct / 100) * $circumference;
    $strokeColour = $colour ?? '#10b981';
@endphp

<svg width="{{ $size ?? 70 }}" height="{{ $size ?? 70 }}" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
    <!-- Background track -->
    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="#e2e8f0" stroke-width="12" />

    <!-- Progress arc -->
    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="none" stroke="{{ $strokeColour }}" stroke-width="12"
            stroke-dasharray="{{ $dash }} {{ $circumference }}"
            stroke-dashoffset="0"
            stroke-linecap="round" />
</svg>
