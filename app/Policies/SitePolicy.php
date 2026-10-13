<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    /**
     * Determine whether the user can view the site (AUTH-6).
     */
    public function view(User $user, Site $site): bool
    {
        if ($user->hasRole(['Super Admin', 'HQ Manager'])) {
            return true;
        }

        return $user->tehsils()->where('tehsils.id', $site->tehsil_id)->exists();
    }

    /**
     * Determine whether the user can submit a daily return for the site.
     */
    public function submitReturn(User $user, Site $site): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (!$user->hasRole(['Field Officer', 'Tehsil In-charge'])) {
            return false;
        }

        return $user->tehsils()->where('tehsils.id', $site->tehsil_id)->exists();
    }
}
