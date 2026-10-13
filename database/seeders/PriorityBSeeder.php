<?php

namespace Database\Seeders;

use App\Models\Contractor;
use App\Models\DailyReturn;
use App\Models\Package;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\Reason;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Database\Seeder;

class PriorityBSeeder extends Seeder
{
    public function run(): void
    {
        $priorityB = Priority::firstOrCreate(
            ['code' => 'B'],
            [
                'name' => 'Priority B',
                'target_days' => 45,
                'deadline' => '2026-10-15',
                'start_date' => '2026-10-05',
                'colour' => '#F59E0B',
                'sort_order' => 2,
            ]
        );

        $phase1 = Phase::firstOrCreate(
            ['code' => 'pilot'],
            ['name' => 'Pilot Phase', 'is_active' => true]
        );

        $adminUser = User::where('email', 'admin@prmsc.punjab.gov.pk')->first()
            ?? User::where('email', 'admin@prmsc.gov.pk')->first()
            ?? User::first();

        $contractorReason = Reason::where('code', 'C1')->first()
            ?? Reason::where('top_level_group', 'Contractor')->first()
            ?? Reason::first();

        $paymentReason = Reason::where('code', 'P1')->first()
            ?? Reason::where('top_level_group', 'PRMSC')->first()
            ?? $contractorReason;

        // Authentic Excel Sheet: Priority B Data
        $items = [
            // Row 1: Taunsa / TAU-01
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-01',
                'contractor' => 'M/S Zarif Khan Hussainzai & Brothers',
                'village' => 'Mithe Wali',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 91,
                'expected_completion_date' => '2026-10-30',
                'day_status' => 'active',
                'labour' => 35,
                'machinery' => 2,
                'remarks' => 'Soling (15000/38000), House connections 250/600',
                'lat' => 30.7080,
                'lng' => 70.6550,
            ],

            // Row 2: Taunsa / TAU-04
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-04',
                'contractor' => 'M/S Saif Construction Pvt. Ltd',
                'village' => 'Basti Buzdar',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 88,
                'expected_completion_date' => '2026-10-15',
                'day_status' => 'active',
                'labour' => 36,
                'machinery' => 3,
                'remarks' => '50 Manholes, Sewerage remains as it is, Tuff Tiles as it is remaining, New Soling 33500 Rft Remaining',
                'lat' => 30.7120,
                'lng' => 70.6600,
            ],

            // Row 3: Taunsa / TAU-04
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-04',
                'contractor' => 'M/S Saif Construction Pvt. Ltd',
                'village' => 'Tub',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 81,
                'expected_completion_date' => '2026-10-15',
                'day_status' => 'active',
                'labour' => 0,
                'machinery' => 1,
                'remarks' => 'Manhole work (65), ABR Boundary wall',
                'lat' => 30.7160,
                'lng' => 70.6650,
            ],

            // Row 4: Bhowana / BNA-02
            [
                'tehsil' => 'Bhowana',
                'district' => 'Chiniot',
                'tehsil_code' => 'BNA',
                'package' => 'BNA-02',
                'contractor' => 'M/S Telerison Pvt Ltd.',
                'village' => 'Chak 191',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-10-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue, IPC pending with finance',
                'lat' => 31.5720,
                'lng' => 72.6530,
            ],

            // Row 5: Darya Khan / DRK-02
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-02',
                'contractor' => 'M/s Damaan Builders JV M/s Ch. Shafqat & Co.',
                'village' => 'Surani Daggar & Surani Nasheb',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 87,
                'expected_completion_date' => '2026-10-30',
                'day_status' => 'active',
                'labour' => 20,
                'machinery' => 3,
                'remarks' => 'ABR BW, Guard Room of OHR & ABR, Sewer Cleaning, HC slabs',
                'lat' => 31.8280,
                'lng' => 71.1490,
            ],
        ];

        $codeCounter = 1;
        $reportDate = '2026-10-07';

        foreach ($items as $item) {
            $tehsil = Tehsil::firstOrCreate(
                ['name' => $item['tehsil']],
                ['district' => $item['district'], 'code' => $item['tehsil_code']]
            );

            $contractor = Contractor::firstOrCreate(
                ['name' => $item['contractor']],
                [
                    'contact_person' => $item['contractor'],
                    'phone' => '0300-22200'.str_pad($codeCounter, 2, '0', STR_PAD_LEFT),
                    'email' => 'contractorb'.$codeCounter.'@prmsc.pk',
                ]
            );

            $package = Package::updateOrCreate(
                ['code' => $item['package']],
                [
                    'tehsil_id' => $tehsil->id,
                    'phase_id' => $phase1->id,
                    'contractor_id' => $contractor->id,
                ]
            );

            $siteCode = 'PRIB-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);

            $site = Site::updateOrCreate(
                [
                    'tehsil_id' => $tehsil->id,
                    'village_name' => $item['village'],
                    'priority_id' => $priorityB->id,
                ],
                [
                    'site_code' => $siteCode,
                    'phase_id' => $phase1->id,
                    'package_id' => $package->id,
                    'contractor_id' => $contractor->id,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'geofence_m' => 500,
                    'status' => $item['status'],
                    'settlements' => $item['settlements'],
                    'physical_progress' => $item['physical_progress'],
                    'expected_completion_date' => $item['expected_completion_date'],
                ]
            );

            // Sync daily return for dashboard & reports
            if ($adminUser) {
                DailyReturn::where('site_id', $site->id)
                    ->where(function ($q) use ($reportDate) {
                        $q->where('report_date', $reportDate)->orWhere('report_date', 'like', "$reportDate%");
                    })
                    ->delete();

                if ($item['day_status'] === 'active') {
                    DailyReturn::create([
                        'site_id' => $site->id,
                        'report_date' => $reportDate,
                        'submitted_by' => $adminUser->id,
                        'visit_time' => '10:30:00',
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'gps_accuracy_m' => 5.0,
                        'distance_from_site_m' => 20.0,
                        'geofence_ok' => true,
                        'status' => 'active',
                        'labour_count' => $item['labour'],
                        'machinery_count' => $item['machinery'],
                        'work_done' => $item['remarks'],
                        'approval_status' => 'approved',
                        'approved_by' => $adminUser->id,
                        'approved_at' => $reportDate.' 11:00:00',
                        'submitted_at' => $reportDate.' 10:45:00',
                    ]);
                } elseif ($item['day_status'] === 'dormant') {
                    DailyReturn::create([
                        'site_id' => $site->id,
                        'report_date' => $reportDate,
                        'submitted_by' => $adminUser->id,
                        'visit_time' => '11:00:00',
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'gps_accuracy_m' => 6.0,
                        'distance_from_site_m' => 30.0,
                        'geofence_ok' => true,
                        'status' => 'dormant',
                        'labour_count' => 0,
                        'machinery_count' => 0,
                        'reason_id' => (($item['reason'] ?? null) === 'P1' ? $paymentReason : $contractorReason)->id,
                        'idle_since' => '2026-10-06',
                        'remarks' => $item['remarks'],
                        'approval_status' => 'approved',
                        'approved_by' => $adminUser->id,
                        'approved_at' => $reportDate.' 11:30:00',
                        'submitted_at' => $reportDate.' 11:15:00',
                    ]);
                }
            }
        }

        // Attach all tehsils to field officers
        $fieldUsers = User::role('Field Officer')->get();
        $allTehsilIds = Tehsil::pluck('id')->toArray();
        foreach ($fieldUsers as $u) {
            $u->tehsils()->syncWithoutDetaching($allTehsilIds);
        }
    }
}
