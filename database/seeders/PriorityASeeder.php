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

class PriorityASeeder extends Seeder
{
    public function run(): void
    {
        $priorityA = Priority::firstOrCreate(
            ['code' => 'A'],
            [
                'name' => 'Priority A',
                'target_days' => 15,
                'deadline' => '2026-10-20',
                'start_date' => '2026-10-05',
                'colour' => '#EF4444',
                'sort_order' => 1,
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

        // Authentic Excel Sheet: Priority A Data
        $items = [
            // Row 1: Taunsa / TAU-04
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-01',
                'contractor' => 'M/S Zarif Khan Hussainzai & Brothers',
                'village' => 'Kalu Wala',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 100,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'completed',
                'labour' => 0,
                'machinery' => 0,
                'remarks' => 'Original Scope Completed, water meter pending',
                'lat' => 30.7045,
                'lng' => 70.6514,
            ],

            // Row 2: Bhowana / BNA-02
            [
                'tehsil' => 'Bhowana',
                'district' => 'Chiniot',
                'tehsil_code' => 'BNA',
                'package' => 'BNA-02',
                'contractor' => 'M/S Telerison Pvt Ltd.',
                'village' => 'Chak 195',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 100,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'completed',
                'labour' => 0,
                'machinery' => 0,
                'remarks' => 'Original Scope Completed, water meter pending (451)',
                'lat' => 31.5683,
                'lng' => 72.6489,
            ],

            // Rows 3-6: Darya Khan / DRK-01
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-01',
                'contractor' => 'M/S Habib Khan Builders',
                'village' => 'Angra Daggar & Lundi Daggar',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 96,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 15,
                'machinery' => 3,
                'remarks' => 'Finishing work, repairing Soling Rectification (2000 Rft)',
                'lat' => 31.7874,
                'lng' => 71.1031,
            ],
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-01',
                'contractor' => 'M/S Habib Khan Builders',
                'village' => 'Tiba Murtaza/Hamid Shah',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 96,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 10,
                'machinery' => 1,
                'remarks' => 'Finishing work, repairing, Soling Rectification (2000 Rft)',
                'lat' => 31.7920,
                'lng' => 71.1080,
            ],
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-01',
                'contractor' => 'M/S Habib Khan Builders',
                'village' => 'Haji Hussain Shah Daggar',
                'settlements' => 4,
                'status' => 'ongoing',
                'physical_progress' => 96,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 10,
                'machinery' => 1,
                'remarks' => 'Finishing work, repairing, Soling Rectification (2000 Rft)',
                'lat' => 31.7980,
                'lng' => 71.1140,
            ],
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-01',
                'contractor' => 'M/S Habib Khan Builders',
                'village' => 'Thalla Sarin',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 98,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'completed',
                'labour' => 0,
                'machinery' => 0,
                'remarks' => 'Cleaning of Soakage Pits',
                'lat' => 31.8040,
                'lng' => 71.1210,
            ],

            // Rows 7-9: Darya Khan / DRK-02
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-02',
                'contractor' => 'M/s Damaan Builders JV M/s Ch. Shafqat & Co.',
                'village' => 'Morani Sandi',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 89,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 20,
                'machinery' => 3,
                'remarks' => 'Manhole concrete (200), finishing works, cleaning (desilting)',
                'lat' => 31.8100,
                'lng' => 71.1280,
            ],
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-02',
                'contractor' => 'M/s Damaan Builders JV M/s Ch. Shafqat & Co.',
                'village' => 'Morani Shumali',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 89,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 2,
                'machinery' => 0,
                'remarks' => 'Tiles and Paint, Slow Progress',
                'lat' => 31.8160,
                'lng' => 71.1350,
            ],
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-02',
                'contractor' => 'M/s Damaan Builders JV M/s Ch. Shafqat & Co.',
                'village' => 'Chak No. 17/TDA',
                'settlements' => 3,
                'status' => 'ongoing',
                'physical_progress' => 88,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'completed',
                'labour' => 0,
                'machinery' => 0,
                'remarks' => 'Finishing Works',
                'lat' => 31.8220,
                'lng' => 71.1420,
            ],

            // Row 10: Kallar Kahar / KLK-02
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-02',
                'contractor' => 'M/s Naveed Construction Pvt Ltd',
                'village' => 'Khai',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 95,
                'expected_completion_date' => '2026-10-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue, (Contract not shown on MIS, Pending Previous IPCs & VOs status on MIS)',
                'lat' => 32.7818,
                'lng' => 72.6983,
            ],

            // Rows 11-14: Khairpur Tamewali / KPT-02 (Re-Tendered)
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Goth Noor Muhammad',
                'settlements' => 4,
                'status' => 'ongoing',
                'physical_progress' => 96,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 16,
                'machinery' => 1,
                'remarks' => '350 rft line remaining, Chamber work, HC works',
                'lat' => 29.5802,
                'lng' => 72.2472,
            ],
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Gulpur Hithar',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 98,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 4,
                'machinery' => 0,
                'remarks' => 'Finishing work',
                'lat' => 29.5850,
                'lng' => 72.2530,
            ],
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Usman Ali Shah',
                'settlements' => 4,
                'status' => 'ongoing',
                'physical_progress' => 96,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 5,
                'machinery' => 0,
                'remarks' => 'Finishing work',
                'lat' => 29.5910,
                'lng' => 72.2600,
            ],
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Jilani',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 98,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 6,
                'machinery' => 1,
                'remarks' => 'Finishing work',
                'lat' => 29.5970,
                'lng' => 72.2670,
            ],

            // Rows 15-16: Bahawalnagar / BWN-05
            [
                'tehsil' => 'Bahawalnagar',
                'district' => 'Bahawalnagar',
                'tehsil_code' => 'BWN',
                'package' => 'BWN-05',
                'contractor' => 'M/S Nazim & Qasim Pvt Ltd',
                'village' => 'Jund Wala Kalan',
                'settlements' => 3,
                'status' => 'ongoing',
                'physical_progress' => 94,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'active',
                'labour' => 3,
                'machinery' => 1,
                'remarks' => 'Finishing work start, actual tuff paver work remaining',
                'lat' => 29.9984,
                'lng' => 73.2536,
            ],
            [
                'tehsil' => 'Bahawalnagar',
                'district' => 'Bahawalnagar',
                'tehsil_code' => 'BWN',
                'package' => 'BWN-05',
                'contractor' => 'M/S Nazim & Qasim Pvt Ltd',
                'village' => 'Murad Kharal',
                'settlements' => 5,
                'status' => 'ongoing',
                'physical_progress' => 100,
                'expected_completion_date' => '2026-09-30',
                'day_status' => 'completed',
                'labour' => 0,
                'machinery' => 0,
                'remarks' => 'Completed',
                'lat' => 30.0050,
                'lng' => 73.2610,
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
                    'phone' => '0300-11100'.str_pad($codeCounter, 2, '0', STR_PAD_LEFT),
                    'email' => 'contractor'.$codeCounter.'@prmsc.pk',
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

            $siteCode = 'PRIA-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);

            $site = Site::updateOrCreate(
                [
                    'tehsil_id' => $tehsil->id,
                    'village_name' => $item['village'],
                    'priority_id' => $priorityA->id,
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
                    'completed_on' => $item['completed_on'] ?? null,
                    'is_operational' => $item['status'] === 'completed',
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
                } elseif ($item['day_status'] === 'completed') {
                    DailyReturn::create([
                        'site_id' => $site->id,
                        'report_date' => $reportDate,
                        'submitted_by' => $adminUser->id,
                        'visit_time' => '11:00:00',
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'gps_accuracy_m' => 5.0,
                        'distance_from_site_m' => 10.0,
                        'geofence_ok' => true,
                        'status' => 'completed',
                        'labour_count' => 0,
                        'machinery_count' => 0,
                        'work_done' => 'Civil works 100% completed on site.',
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
