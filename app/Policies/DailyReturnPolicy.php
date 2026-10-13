<?php

namespace App\Policies;

use App\Models\DailyReturn;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class DailyReturnPolicy
{
    /**
     * Determine whether the user can view the daily return (AUTH-6).
     */
    public function view(User $user, DailyReturn $return): bool
    {
        if ($user->hasRole(['Super Admin', 'HQ Manager'])) {
            return true;
        }

        $site = $return->site;
        if (!$site) {
            return false;
        }

        return $user->tehsils()->where('tehsils.id', $site->tehsil_id)->exists();
    }

    /**
     * Determine whether the user can update the daily return (FORM-12, FORM-13, REV-2).
     */
    public function update(User $user, DailyReturn $return): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $site = $return->site;
        if (!$site || !$user->tehsils()->where('tehsils.id', $site->tehsil_id)->exists()) {
            return false;
        }

        // Tehsil in-charge can edit/review on the same day
        if ($user->hasRole('Tehsil In-charge')) {
            return true;
        }

        // Field Officer can edit own submission until approved or until cut-off unless unlocked/returned
        if ($user->hasRole('Field Officer')) {
            if ($return->approval_status === 'approved') {
                return false;
            }

            if ($return->approval_status === 'returned') {
                return true; // Returned items re-open editing (REV-2)
            }

            // Check cut-off time (FORM-12)
            $tz = Setting::get('timezone', 'Asia/Karachi');
            $cutoff = Setting::get('cutoff_time', '13:00');
            $now = Carbon::now($tz);
            $cutoffTime = Carbon::createFromFormat('H:i', $cutoff, $tz);

            if ($now->greaterThan($cutoffTime) && !$return->is_late) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can review and approve/return the item (REV-1).
     */
    public function review(User $user, DailyReturn $return): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (!$user->hasRole('Tehsil In-charge')) {
            return false;
        }

        $site = $return->site;
        if (!$site) {
            return false;
        }

        return $user->tehsils()->where('tehsils.id', $site->tehsil_id)->exists();
    }
}
