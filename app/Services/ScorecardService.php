<?php

namespace App\Services;

use App\Models\Contractor;
use App\Models\Priority;
use App\Models\SiteDaySnapshot;
use App\Models\Tehsil;
use Illuminate\Support\Collection;

class ScorecardService
{
    /**
     * Compute contractor scorecards sorted worst first (Section 5.6).
     */
    public static function buildContractorScorecards(string $date): Collection
    {
        $priority1 = Priority::where('code', '1')->orWhere('code', 'A')->first();
        $p1Id = $priority1 ? $priority1->id : null;

        $contractors = Contractor::with(['sites' => function ($q) use ($date) {
            $q->where('status', 'ongoing')
              ->with(['snapshotOn' => fn($sq) => $sq->where('report_date', $date), 'priority']);
        }])->get();

        $rows = $contractors->map(function ($c) use ($date, $p1Id) {
            $sites = $c->sites;
            $totalSites = $sites->count();

            if ($totalSites === 0) {
                return null;
            }

            $active = 0;
            $dormant = 0;
            $nr = 0;
            $idle3Plus = 0;
            $idle7Plus = 0;
            $priority1Idle = 0;
            $totalLabour = 0;
            $totalMachinery = 0;

            foreach ($sites as $site) {
                $snapshot = $site->snapshotOn;
                $status = $snapshot ? $snapshot->day_status : 'not_reported';
                $idleDays = $snapshot ? $snapshot->idle_days : 1;

                if ($status === 'active') {
                    $active++;
                    $totalLabour += $snapshot ? $snapshot->labour : 0;
                    $totalMachinery += $snapshot ? $snapshot->machinery : 0;
                } elseif ($status === 'dormant') {
                    $dormant++;
                } else {
                    $nr++;
                }

                if ($status !== 'active') {
                    if ($idleDays >= 3) $idle3Plus++;
                    if ($idleDays >= 7) $idle7Plus++;
                    if ($p1Id && $site->priority_id == $p1Id) $priority1Idle++;
                }
            }

            $pctActive = $totalSites > 0 ? round(($active / $totalSites) * 100) : 0;

            return [
                'id' => $c->id,
                'name' => $c->name,
                'sites' => $totalSites,
                'active' => $active,
                'dormant' => $dormant,
                'nr' => $nr,
                'no_report' => $nr,
                'idle_3_plus' => $idle3Plus,
                'idle_7_plus' => $idle7Plus,
                'pri_a_idle' => $priority1Idle,
                'priority_1_idle' => $priority1Idle,
                'labour' => $totalLabour,
                'machinery' => $totalMachinery,
                'active_pct' => $pctActive,
                'pct_active' => $pctActive,
            ];
        })->filter()->values();

        // Sort worst first: idle 7+ desc, idle 3+ desc, priority 1 idle desc, % active asc
        return $rows->sort(function ($a, $b) {
            if ($a['idle_7_plus'] !== $b['idle_7_plus']) {
                return $b['idle_7_plus'] <=> $a['idle_7_plus'];
            }
            if ($a['idle_3_plus'] !== $b['idle_3_plus']) {
                return $b['idle_3_plus'] <=> $a['idle_3_plus'];
            }
            if ($a['priority_1_idle'] !== $b['priority_1_idle']) {
                return $b['priority_1_idle'] <=> $a['priority_1_idle'];
            }
            return $a['pct_active'] <=> $b['pct_active'];
        })->values();
    }

    /**
     * Compute tehsil scorecards sorted worst first (Section 5.6).
     */
    public static function buildTehsilScorecards(string $date): Collection
    {
        $priority1 = Priority::where('code', '1')->orWhere('code', 'A')->first();
        $p1Id = $priority1 ? $priority1->id : null;

        $tehsils = Tehsil::with(['sites' => function ($q) use ($date) {
            $q->where('status', 'ongoing')
              ->with(['snapshotOn' => fn($sq) => $sq->where('report_date', $date), 'priority']);
        }])->get();

        $rows = $tehsils->map(function ($t) use ($date, $p1Id) {
            $sites = $t->sites;
            $totalSites = $sites->count();

            if ($totalSites === 0) {
                return null;
            }

            $active = 0;
            $dormant = 0;
            $nr = 0;
            $idle3Plus = 0;
            $idle7Plus = 0;
            $priority1Idle = 0;
            $totalLabour = 0;
            $totalMachinery = 0;

            foreach ($sites as $site) {
                $snapshot = $site->snapshotOn;
                $status = $snapshot ? $snapshot->day_status : 'not_reported';
                $idleDays = $snapshot ? $snapshot->idle_days : 1;

                if ($status === 'active') {
                    $active++;
                    $totalLabour += $snapshot ? $snapshot->labour : 0;
                    $totalMachinery += $snapshot ? $snapshot->machinery : 0;
                } elseif ($status === 'dormant') {
                    $dormant++;
                } else {
                    $nr++;
                }

                if ($status !== 'active') {
                    if ($idleDays >= 3) $idle3Plus++;
                    if ($idleDays >= 7) $idle7Plus++;
                    if ($p1Id && $site->priority_id == $p1Id) $priority1Idle++;
                }
            }

            $pctActive = $totalSites > 0 ? round(($active / $totalSites) * 100) : 0;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'sites' => $totalSites,
                'active' => $active,
                'dormant' => $dormant,
                'nr' => $nr,
                'no_report' => $nr,
                'idle_3_plus' => $idle3Plus,
                'idle_7_plus' => $idle7Plus,
                'pri_a_idle' => $priority1Idle,
                'priority_1_idle' => $priority1Idle,
                'labour' => $totalLabour,
                'machinery' => $totalMachinery,
                'active_pct' => $pctActive,
                'pct_active' => $pctActive,
            ];
        })->filter()->values();

        // Sort worst first
        return $rows->sort(function ($a, $b) {
            if ($a['idle_7_plus'] !== $b['idle_7_plus']) {
                return $b['idle_7_plus'] <=> $a['idle_7_plus'];
            }
            if ($a['idle_3_plus'] !== $b['idle_3_plus']) {
                return $b['idle_3_plus'] <=> $a['idle_3_plus'];
            }
            if ($a['priority_1_idle'] !== $b['priority_1_idle']) {
                return $b['priority_1_idle'] <=> $a['priority_1_idle'];
            }
            return $a['pct_active'] <=> $b['pct_active'];
        })->values();
    }
}
