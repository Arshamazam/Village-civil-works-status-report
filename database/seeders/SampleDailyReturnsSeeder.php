<?php

namespace Database\Seeders;

use App\Models\DailyReturn;
use App\Models\Reason;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleDailyReturnsSeeder extends Seeder
{
    public function run(): void
    {
        $date = '2026-10-14';
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();
        $reasonsByGroup = Reason::whereNotNull('parent_id')->get()->groupBy('top_level_group');
        $contractorReason = Reason::where('code', 'C1')->first();
        $prmscReason = Reason::where('code', 'P1')->first();
        $externalReason = Reason::where('code', 'X1')->first();

        $rawSites = SampleMasterAndRollupSeeder::getRawSites();

        foreach ($rawSites as $row) {
            $site = Site::where('village_name', $row['village'])->first();
            if (!$site) continue;

            $statusStr = $row['status'];
            $lab = $row['lab'];
            $mach = $row['mach'];
            $reasonGroup = $row['reason_group'];

            if ($statusStr === 'Active') {
                DailyReturn::updateOrCreate(
                    ['site_id' => $site->id, 'report_date' => $date],
                    [
                        'submitted_by' => $admin->id,
                        'visit_time' => '10:30:00',
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'gps_accuracy_m' => 8.5,
                        'distance_from_site_m' => 45.0,
                        'geofence_ok' => true,
                        'status' => 'active',
                        'labour_count' => (int)$lab,
                        'machinery_count' => (int)$mach,
                        'machinery_types' => ((int)$mach > 0) ? ['mixer', 'tractor-trolley'] : [],
                        'work_done' => 'Civil works executed as per schedule. PCC / soling / boundary wall progress ongoing.',
                        'approval_status' => 'approved',
                        'approved_by' => $admin->id,
                        'approved_at' => '2026-10-14 11:30:00',
                        'submitted_at' => '2026-10-14 10:45:00',
                    ]
                );
            } elseif ($lab === 'NR' || $mach === 'NR') {
                // Not Reported site - no return created by cut-off
                DailyReturn::where('site_id', $site->id)->where('report_date', $date)->delete();
            } else {
                // Dormant
                preg_match('/(\d+)\s+day/', $statusStr, $m);
                $days = isset($m[1]) ? (int)$m[1] : 1;
                $idleSince = Carbon::parse($date)->subDays($days)->toDateString();

                $chosenReason = null;
                if ($reasonGroup === 'Contractor') {
                    $chosenReason = $contractorReason;
                } elseif ($reasonGroup === 'PRMSC') {
                    $chosenReason = $prmscReason;
                } elseif ($reasonGroup === 'External') {
                    $chosenReason = $externalReason;
                }

                DailyReturn::updateOrCreate(
                    ['site_id' => $site->id, 'report_date' => $date],
                    [
                        'submitted_by' => $admin->id,
                        'visit_time' => '11:15:00',
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'gps_accuracy_m' => 12.0,
                        'distance_from_site_m' => 60.0,
                        'geofence_ok' => true,
                        'status' => 'dormant',
                        'labour_count' => 0,
                        'machinery_count' => 0,
                        'reason_id' => $chosenReason ? $chosenReason->id : null,
                        'idle_since' => $idleSince,
                        'remarks' => $chosenReason ? 'Field staff verified dormant status.' : 'Reason unstated in field report.',
                        'approval_status' => 'approved',
                        'approved_by' => $admin->id,
                        'approved_at' => '2026-10-14 12:00:00',
                        'submitted_at' => '2026-10-14 11:30:00',
                    ]
                );
            }
        }
    }
}
