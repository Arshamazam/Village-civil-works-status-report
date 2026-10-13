<?php

namespace App\Services;

use App\Models\DailyReturn;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\Site;
use App\Models\SiteDaySnapshot;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class IdleCalculator
{
    /**
     * Determine if a date is a holiday or non-reporting day (Sunday).
     */
    public static function isNonReportingDay(string $date): bool
    {
        $carbon = Carbon::parse($date);

        // Sundays are non-reporting days
        if ($carbon->isSunday()) {
            return true;
        }

        // Check gazetted holidays table
        return Holiday::whereDate('date', $carbon->toDateString())->exists();
    }

    /**
     * Calculate idle days and last working date for a site on date D.
     */
    public static function calculateForSite(Site $site, string $date, ?DailyReturn $todayReturn): array
    {
        // 1. If site was already completed in master record
        if ($site->status === 'completed' && $site->completed_on && Carbon::parse($site->completed_on)->lte(Carbon::parse($date))) {
            return [
                'day_status' => 'completed',
                'idle_days' => 0,
                'idle_band' => 'completed',
                'last_working_date' => $site->completed_on,
                'labour' => 0,
                'machinery' => 0,
                'thinly_manned' => false,
                'reason_id' => null,
                'top_level_reason' => null,
            ];
        }

        // 2. Today's return exists (field staff submitted work today - respect actual site activity)
        if ($todayReturn) {
            if ($todayReturn->status === 'active' && ($todayReturn->labour_count > 0 || $todayReturn->machinery_count > 0)) {
                $thinThreshold = (int) Setting::get('thin_manned_threshold', 5);
                $isThinlyManned = ($todayReturn->labour_count < $thinThreshold);

                return [
                    'day_status' => 'active',
                    'idle_days' => 0,
                    'idle_band' => 'active',
                    'last_working_date' => $date,
                    'labour' => $todayReturn->labour_count,
                    'machinery' => $todayReturn->machinery_count,
                    'thinly_manned' => $isThinlyManned,
                    'reason_id' => null,
                    'top_level_reason' => null,
                ];
            }

            if ($todayReturn->status === 'completed') {
                return [
                    'day_status' => 'completed',
                    'idle_days' => 0,
                    'idle_band' => 'completed',
                    'last_working_date' => $date,
                    'labour' => 0,
                    'machinery' => 0,
                    'thinly_manned' => false,
                    'reason_id' => null,
                    'top_level_reason' => null,
                ];
            }

            // Status is Dormant
            $topLevelReason = $todayReturn->reason ? $todayReturn->reason->top_level_group : 'Unstated';
            $idleSince = $todayReturn->idle_since ? $todayReturn->idle_since->toDateString() : null;
            $lastWork = $idleSince ? Carbon::parse($idleSince)->subDay()->toDateString() : null;

            $idleDays = static::countReportingDaysBetween($lastWork, $date);

            return [
                'day_status' => 'dormant',
                'idle_days' => $idleDays,
                'idle_band' => static::determineBand($idleDays),
                'last_working_date' => $lastWork,
                'labour' => 0,
                'machinery' => 0,
                'thinly_manned' => false,
                'reason_id' => $todayReturn->reason_id,
                'top_level_reason' => $topLevelReason,
            ];
        }

        // 3. A site reported completed stays completed until a newer return says otherwise
        $latestPreviousReturn = DailyReturn::where('site_id', $site->id)
            ->whereDate('report_date', '<', $date)
            ->latest('report_date')
            ->first();

        if ($latestPreviousReturn && $latestPreviousReturn->status === 'completed') {
            return [
                'day_status' => 'completed',
                'idle_days' => 0,
                'idle_band' => 'completed',
                'last_working_date' => $latestPreviousReturn->report_date->toDateString(),
                'labour' => 0,
                'machinery' => 0,
                'thinly_manned' => false,
                'reason_id' => null,
                'top_level_reason' => null,
            ];
        }

        // 4. If holiday or Sunday on date D and no return was submitted
        if (static::isNonReportingDay($date)) {
            $prevSnapshot = SiteDaySnapshot::where('site_id', $site->id)
                ->where('report_date', '<', $date)
                ->latest('report_date')
                ->first();

            return [
                'day_status' => 'holiday',
                'idle_days' => $prevSnapshot ? $prevSnapshot->idle_days : 0,
                'idle_band' => 'holiday',
                'last_working_date' => $prevSnapshot ? $prevSnapshot->last_working_date : null,
                'labour' => 0,
                'machinery' => 0,
                'thinly_manned' => false,
                'reason_id' => null,
                'top_level_reason' => null,
            ];
        }

        // 5. No return by cut-off -> Not Reported (NR)
        $lastActiveReturn = DailyReturn::where('site_id', $site->id)
            ->where('report_date', '<', $date)
            ->where('status', 'active')
            ->latest('report_date')
            ->first();

        $lastWork = $lastActiveReturn ? $lastActiveReturn->report_date->toDateString() : null;

        if (! $lastWork) {
            $prevSnapshot = SiteDaySnapshot::where('site_id', $site->id)
                ->where('report_date', '<', $date)
                ->latest('report_date')
                ->first();

            $lastWork = $prevSnapshot ? $prevSnapshot->last_working_date : null;
        }

        $idleDays = static::countReportingDaysBetween($lastWork, $date);

        return [
            'day_status' => 'not_reported',
            'idle_days' => $idleDays,
            'idle_band' => static::determineBand($idleDays),
            'last_working_date' => $lastWork,
            'labour' => 0,
            'machinery' => 0,
            'thinly_manned' => false,
            'reason_id' => null,
            'top_level_reason' => 'Unstated',
        ];
    }

    /**
     * Count reporting days between two dates, skipping Sundays and holidays (Section 5.2).
     */
    public static function countReportingDaysBetween(?string $startDate, string $endDate): int
    {
        if (! $startDate) {
            return 1;
        }

        $start = Carbon::parse($startDate)->addDay();
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        $holidays = Holiday::whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $count = 0;
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $day) {
            // Skip Sunday
            if ($day->isSunday()) {
                continue;
            }
            // Skip gazetted holidays
            if (in_array($day->toDateString(), $holidays, true)) {
                continue;
            }
            $count++;
        }

        return max(1, $count);
    }

    /**
     * Determine traffic light idle band: Watch (1-2), Warn (3-4), Escalate (5-6), Critical (7+).
     */
    public static function determineBand(int $idleDays): string
    {
        if ($idleDays <= 0) {
            return 'active';
        }
        if ($idleDays <= 2) {
            return 'watch';
        }
        if ($idleDays <= 4) {
            return 'warn';
        }
        if ($idleDays <= 6) {
            return 'escalate';
        }

        return 'critical';
    }
}
