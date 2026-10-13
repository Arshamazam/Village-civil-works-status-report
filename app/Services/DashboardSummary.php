<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardSummary
{
    /**
     * Badge colours per deadline tone, as Tailwind classes (dashboard) and hex values (PDF).
     *
     * @var array<string, array{class: string, hex: string}>
     */
    private const BADGE_COLOURS = [
        'overdue' => ['class' => 'bg-[#dc2626]', 'hex' => '#dc2626'],
        'urgent' => ['class' => 'bg-[#d97706]', 'hex' => '#d97706'],
        'on_track' => ['class' => 'bg-[#15803d]', 'hex' => '#15803d'],
        'none' => ['class' => 'bg-slate-600', 'hex' => '#475569'],
    ];

    /**
     * Short labels for dormancy reason codes shown in the cause breakdown.
     *
     * @var array<string, string>
     */
    private const REASON_SHORT_LABELS = [
        'C' => 'Contractor issue', 'C1' => 'Labour / machinery n/a', 'C2' => 'Material n/a', 'C3' => 'Cash-flow / dispute',
        'P' => 'PRMSC issue', 'P1' => 'Payment', 'P2' => 'EOT pending', 'P3' => 'Design / decision', 'P4' => 'Mob. advance', 'P5' => 'Mat. testing',
        'L' => 'Land / site possession', 'L1' => 'Land / site possession',
        'X' => 'External', 'X1' => 'Weather', 'X2' => 'Community', 'X3' => 'NOC',
        'V' => 'VLD', 'V1' => 'VLD',
    ];

    /**
     * Build the priority cards and headline figures shared by the dashboard and the PDF report.
     *
     * @param  array<string, mixed>  $payload  Rollup payload (possibly filtered)
     * @param  Collection<int, array<string, mixed>>|null  $allRegisterRows  Unfiltered register rows, used so deadlines do not shift with filters
     * @return array{priorityDisplay: array<int, array<string, mixed>>, dashStats: array<string, mixed>}
     */
    public function build(array $payload, string $date, ?Collection $allRegisterRows = null): array
    {
        $rows = collect($payload['register_rows'] ?? []);
        $allRegisterRows ??= $rows;
        $reportDate = Carbon::parse($payload['report_date'] ?? $date);

        return [
            'priorityDisplay' => $this->priorityCards($rows, $allRegisterRows, $reportDate),
            'dashStats' => $this->headlineStats($rows),
        ];
    }

    /**
     * The list shown in a dashboard card's detail pop-up, built with the same rules as the pop-up itself.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $priorityDisplay
     * @return array{title: string, subtitle: string, rows: Collection<int, array<string, mixed>>, stats: array<string, string|int>}
     */
    public function detailList(array $payload, string $type, ?string $groupName, array $priorityDisplay, ?string $search = null): array
    {
        $all = collect($payload['register_rows'] ?? []);
        $active = $all->where('day_status', 'active');
        $sum = fn (Collection $rows, string $field) => (int) $rows->sum($field);

        [$title, $subtitle, $rows, $stats] = match ($type) {
            'total_villages' => ['Total Villages Under Execution', 'All active & dormant schemes under current monitoring', $all, [
                'Total Schemes' => $all->count(),
                'Active' => $active->count(),
                'Dormant' => $all->where('day_status', 'dormant')->count(),
                'Labour' => $sum($all, 'labour'),
                'Machinery' => $sum($all, 'machinery'),
            ]],
            'tendering_phase' => ['In The Tendering Phase', 'Phase-0 & deferred schemes currently in procurement & tendering', collect($payload['tendering_rows'] ?? []), [
                'Tendering Schemes' => count($payload['tendering_rows'] ?? []),
            ]],
            'completed_villages' => ['Completed Villages', '100% physically completed & COC pending',
                $all->filter(fn ($r) => $r['day_status'] === 'completed' || (float) ($r['physical_progress'] ?? 0) >= 100), [
                    'Completed Schemes' => $all->where('day_status', 'completed')->count(),
                    'At 100% Progress (COC Pending)' => $all->filter(fn ($r) => (float) ($r['physical_progress'] ?? 0) >= 100)->count(),
                ]],
            'remaining_villages' => ['Remaining Villages To Finish', 'Schemes currently active, dormant, or awaiting reports',
                $remaining = $all->where('day_status', '!=', 'completed'), [
                    'Remaining Schemes' => $remaining->count(),
                    'Active' => $remaining->where('day_status', 'active')->count(),
                    'Dormant' => $remaining->where('day_status', 'dormant')->count(),
                    'Not Reported' => $remaining->where('day_status', 'not_reported')->count(),
                ]],
            'total_labour' => ['Total Labour Mobilization', 'Active sites ranked by highest labour workforce on site', $active->sortByDesc('labour'), [
                'Total Labour' => $sum($active, 'labour'),
                'Active Sites' => $active->count(),
                'Avg per Site' => $active->count() ? (int) round($sum($active, 'labour') / $active->count()) : 0,
                'Thinly Manned (<5)' => $active->where('labour', '<', 5)->count(),
            ]],
            'total_machinery' => ['Total Machinery Mobilization', 'Sites ranked by heavy equipment and machinery units',
                $all->filter(fn ($r) => ($r['machinery'] ?? 0) > 0 || $r['day_status'] === 'active')->sortByDesc('machinery'), [
                    'Total Machinery' => $sum($active, 'machinery'),
                    'Active Sites' => $active->count(),
                    'Avg per Active Site' => $active->count() ? round($sum($active, 'machinery') / $active->count(), 1) : 0,
                ]],
            'physical_progress' => ['Physical Progress — Village Status', 'Cumulative civil works execution progress across monitored schemes', $all->sortByDesc('physical_progress'), [
                'Avg Progress' => ($all->count() ? (int) round($all->avg('physical_progress')) : 0).'%',
                'Completed (100%)' => $all->filter(fn ($r) => (float) ($r['physical_progress'] ?? 0) >= 100)->count(),
                'Total Monitored' => $all->count(),
            ]],
            'active_sites' => ['Active Sites Today', 'Sites reported Active with working workforce & machinery', $active, [
                'Active Schemes' => $active->count(),
                'Total Labour' => $sum($active, 'labour'),
                'Total Machinery' => $sum($active, 'machinery'),
                'Thinly Manned' => $active->where('labour', '<', 5)->count(),
            ]],
            'dormant_sites' => ['Dormant Sites Today', 'Zero labour and machinery on site · grouped by root cause', $dormant = $all->where('day_status', 'dormant'), [
                'Dormant Schemes' => $dormant->count(),
                'Payment Issues' => $dormant->filter(fn ($r) => static::shortReasonLabel($r) === 'Payment')->count(),
                'Mob. Advance' => $dormant->filter(fn ($r) => static::shortReasonLabel($r) === 'Mob. advance')->count(),
            ]],
            'nr_sites' => ['Not Reported Sites', 'Status left blank in field return sheets', $all->where('day_status', 'not_reported'), [
                'No Report Schemes' => $all->where('day_status', 'not_reported')->count(),
            ]],
            'no_progress' => ['Sites With No Progress', 'Dormant, stopped, un-mobilized or un-reported sites requiring executive intervention',
                $all->filter(fn ($r) => in_array($r['day_status'] ?? '', ['dormant', 'not_reported']) || ((float) ($r['physical_progress'] ?? 0) <= 0 && (int) ($r['labour'] ?? 0) === 0)), [
                    'Total No Progress Sites' => $all->filter(fn ($r) => in_array($r['day_status'] ?? '', ['dormant', 'not_reported']) || ((float) ($r['physical_progress'] ?? 0) <= 0 && (int) ($r['labour'] ?? 0) === 0))->count(),
                    'Dormant (Stopped)' => $all->where('day_status', 'dormant')->count(),
                    'Not Reported' => $all->where('day_status', 'not_reported')->count(),
                    'Zero Progress (0%)' => $all->filter(fn ($r) => (float) ($r['physical_progress'] ?? 0) <= 0)->count(),
                ]],
            'priority_card' => $this->priorityDetail($all, (string) $groupName, $priorityDisplay),
            default => throw new \InvalidArgumentException("Unknown detail type [{$type}]."),
        };

        if (filled($search)) {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(fn ($r) => collect(['village', 'tehsil', 'contractor', 'package', 'status_label'])
                ->contains(fn ($field) => str_contains(mb_strtolower((string) ($r[$field] ?? '')), $needle)));
        }

        return ['title' => $title, 'subtitle' => $subtitle, 'rows' => $rows->values(), 'stats' => $stats];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $all
     * @param  array<int, array<string, mixed>>  $priorityDisplay
     * @return array{0: string, 1: string, 2: Collection<int, array<string, mixed>>, 3: array<string, int>}
     */
    private function priorityDetail(Collection $all, string $groupName, array $priorityDisplay): array
    {
        $groupName = strtoupper($groupName);
        $matcher = static::groupMatchers()[$groupName] ?? throw new \InvalidArgumentException("Unknown group [{$groupName}].");
        $rows = $all->filter($matcher);
        $card = collect($priorityDisplay)->firstWhere('name', $groupName);

        return [$groupName, $card ? "Target: {$card['target']} · {$card['badge']}" : '', $rows, [
            'Total Sites' => $rows->count(),
            'Active' => $rows->where('day_status', 'active')->count(),
            'Dormant' => $rows->where('day_status', 'dormant')->count(),
            'Labour' => (int) $rows->sum('labour'),
            'Machinery' => (int) $rows->sum('machinery'),
        ]];
    }

    /**
     * Short display label for a dormant register row's reason.
     *
     * @param  array<string, mixed>  $row
     */
    public static function shortReasonLabel(array $row): string
    {
        return self::REASON_SHORT_LABELS[$row['reason_code'] ?? ''] ?? ($row['status_label'] ?? 'Unstated');
    }

    /**
     * Matchers that assign a register row to its completion-priority group.
     *
     * @return array<string, \Closure(array<string, mixed>): bool>
     */
    public static function groupMatchers(): array
    {
        return [
            'PRIORITY A' => fn ($r) => in_array($r['priority_code'] ?? '', ['1', 'A']) || str_contains($r['group_name'] ?? '', 'PRIORITY A'),
            'PRIORITY B' => fn ($r) => in_array($r['priority_code'] ?? '', ['2', 'B']) || str_contains($r['group_name'] ?? '', 'PRIORITY B'),
            'PRIORITY C' => fn ($r) => in_array($r['priority_code'] ?? '', ['3', 'C']) || str_contains($r['group_name'] ?? '', 'PRIORITY C'),
            'REMAINING PILOT' => fn ($r) => str_contains($r['group_name'] ?? '', 'REMAINING') || (in_array($r['priority_code'] ?? '', ['4', 'D', '']) && ($r['phase_abbr'] ?? '') !== 'Ph-0' && ! str_contains($r['group_name'] ?? '', 'PRIORITY')),
            'PHASE-0' => fn ($r) => ($r['phase_abbr'] ?? '') === 'Ph-0' || str_contains($r['group_name'] ?? '', 'PHASE-0'),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, array<string, mixed>>  $allRegisterRows
     * @return array<int, array{name: string, sites: int, target: string, badge: string, badge_bg: string, badge_hex: string, active: int, dormant: int, nr: int, done: int, labour: int, machinery: int}>
     */
    private function priorityCards(Collection $rows, Collection $allRegisterRows, Carbon $reportDate): array
    {
        $cards = [];

        foreach (static::groupMatchers() as $groupName => $matcher) {
            $matched = $rows->filter($matcher);
            $deadline = RollupService::deadlineSummary(
                $allRegisterRows->filter($matcher)->pluck('expected_completion_date'),
                $reportDate
            );
            $active = $matched->where('day_status', 'active');

            $cards[] = [
                'name' => $groupName,
                'sites' => $matched->count(),
                'target' => $deadline['target'],
                'badge' => $deadline['badge'],
                'badge_bg' => self::BADGE_COLOURS[$deadline['tone']]['class'],
                'badge_hex' => self::BADGE_COLOURS[$deadline['tone']]['hex'],
                'active' => $active->count(),
                'dormant' => $matched->where('day_status', 'dormant')->count(),
                'nr' => $matched->where('day_status', 'not_reported')->count(),
                'done' => $matched->where('day_status', 'completed')->count(),
                'labour' => (int) $active->sum('labour'),
                'machinery' => (int) $active->sum('machinery'),
            ];
        }

        return $cards;
    }

    /**
     * Derive the headline figures (completion, scope split, dormancy causes, payment callout) from register rows.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{pilot_count: int, phase0_count: int, phase_split: array<string, array{pilot: int, phase0: int}>, total_count: int, completed_count: int, full_progress_count: int, completed_pct: int, remaining_count: int, remaining_pct: int, active_pct: int, dormant_pct: int, nr_pct: int, dormant_count: int, causes: array<string, array{count: int, pct: int, lines: array<string, int>}>, payment_count: int, advance_count: int, payment_related_count: int, payment_related_pct: int, payment_pct: int, advance_pct: int, payment_list: string, advance_list: string, zero_active_villages: array<int, string>}
     */
    private function headlineStats(Collection $rows): array
    {
        $totalCount = $rows->count();
        $completedCount = $rows->where('day_status', 'completed')->count();
        $remainingCount = $totalCount - $completedCount;
        $dormantRows = $rows->where('day_status', 'dormant');
        $dormantCount = $dormantRows->count();

        $percentOf = fn (int $part, int $whole): int => $whole > 0 ? (int) round(($part / $whole) * 100) : 0;

        $causes = [];
        foreach (['Contractor', "VLD's", 'Govt. Land', 'PRMSC', 'External'] as $group) {
            $groupRows = $dormantRows->where('top_level_reason', $group);
            $causes[$group] = [
                'count' => $groupRows->count(),
                'pct' => $percentOf($groupRows->count(), $dormantCount),
                'lines' => $groupRows
                    ->countBy(fn ($r) => static::shortReasonLabel($r))
                    ->sortDesc()
                    ->all(),
            ];
        }

        $isAdvance = fn ($r) => ($r['reason_code'] ?? '') === 'P4' || str_contains(strtolower($r['status_label'] ?? ''), 'advance');
        $isPayment = fn ($r) => ! $isAdvance($r) && (($r['reason_code'] ?? '') === 'P1' || str_contains(strtolower($r['status_label'] ?? ''), 'payment') || str_contains(strtolower($r['status_label'] ?? ''), 'ipc'));
        $paymentRows = $dormantRows->filter($isPayment);
        $advanceRows = $dormantRows->filter($isAdvance);

        $summariseByTehsil = fn ($groupRows) => $groupRows
            ->groupBy('tehsil')
            ->map(function ($tehsilRows, $tehsil) {
                $packages = $tehsilRows->pluck('package')
                    ->map(fn ($p) => trim(preg_replace('/\s*\(.*\)/', '', (string) $p)))
                    ->unique()
                    ->sort()
                    ->implode(', ');

                return "{$tehsil} {$packages} ({$tehsilRows->count()})";
            })
            ->sortByDesc(fn ($label) => (int) preg_replace('/.*\((\d+)\)$/', '$1', $label))
            ->implode(' • ');

        $paymentRelatedCount = $paymentRows->count() + $advanceRows->count();

        return [
            'pilot_count' => $rows->where('phase_abbr', '!=', 'Ph-0')->count(),
            'phase0_count' => $rows->where('phase_abbr', 'Ph-0')->count(),
            'phase_split' => collect(['active', 'dormant', 'not_reported'])
                ->mapWithKeys(fn ($status) => [$status => [
                    'pilot' => $rows->where('day_status', $status)->where('phase_abbr', '!=', 'Ph-0')->count(),
                    'phase0' => $rows->where('day_status', $status)->where('phase_abbr', 'Ph-0')->count(),
                ]])
                ->all(),
            'total_count' => $totalCount,
            'completed_count' => $completedCount,
            'full_progress_count' => $rows->filter(fn ($r) => (float) ($r['physical_progress'] ?? 0) >= 100)->count(),
            'completed_pct' => $percentOf($completedCount, $totalCount),
            'remaining_count' => $remainingCount,
            'remaining_pct' => $percentOf($remainingCount, $totalCount),
            'active_pct' => $percentOf($rows->where('day_status', 'active')->count(), $remainingCount),
            'dormant_pct' => $percentOf($dormantCount, $remainingCount),
            'nr_pct' => $percentOf($rows->where('day_status', 'not_reported')->count(), $remainingCount),
            'dormant_count' => $dormantCount,
            'causes' => $causes,
            'payment_count' => $paymentRows->count(),
            'advance_count' => $advanceRows->count(),
            'payment_related_count' => $paymentRelatedCount,
            'payment_related_pct' => $percentOf($paymentRelatedCount, $dormantCount),
            'payment_pct' => $percentOf($paymentRows->count(), $dormantCount),
            'advance_pct' => $percentOf($advanceRows->count(), $dormantCount),
            'payment_list' => $summariseByTehsil($paymentRows),
            'advance_list' => $summariseByTehsil($advanceRows),
            'zero_active_villages' => $rows
                ->filter(fn ($r) => $r['day_status'] === 'active' && (int) $r['labour'] === 0 && (int) $r['machinery'] === 0)
                ->pluck('village')
                ->values()
                ->all(),
        ];
    }
}
