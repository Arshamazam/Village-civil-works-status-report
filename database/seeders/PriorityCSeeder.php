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

class PriorityCSeeder extends Seeder
{
    public function run(): void
    {
        $priorityC = Priority::firstOrCreate(
            ['code' => 'C'],
            [
                'name' => 'Priority C',
                'target_days' => 55,
                'deadline' => '2026-11-30',
                'start_date' => '2026-10-05',
                'colour' => '#3B82F6',
                'sort_order' => 3,
            ]
        );
        $priorityC->update([
            'deadline' => '2026-11-30',
            'target_days' => 55,
        ]);

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

        // Unify Kahror Pacca / Kahror Pakka naming
        $kahror = Tehsil::where('name', 'Kahror Pacca')->orWhere('name', 'Kahror Pakka')->first();
        if ($kahror) {
            $kahror->update(['name' => 'Kahror Pakka', 'district' => 'Lodhran', 'code' => 'KRP']);
        }

        // Authentic Excel Sheet: Priority C Data (21 Sites)
        $items = [
            // Row 1: Kahror Pakka / KRP-02
            [
                'tehsil' => 'Kahror Pakka',
                'district' => 'Lodhran',
                'tehsil_code' => 'KRP',
                'package' => 'KRP-02',
                'contractor' => 'M/s Haji Gul Muhammad Khan Shirani',
                'village' => 'Paywagna',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 15,
                'machinery' => 1,
                'reason' => null,
                'remarks' => 'Soling Remaining',
                'lat' => 29.6247,
                'lng' => 71.9149,
            ],
            // Row 2: Kahror Pakka / KRP-02
            [
                'tehsil' => 'Kahror Pakka',
                'district' => 'Lodhran',
                'tehsil_code' => 'KRP',
                'package' => 'KRP-02',
                'contractor' => 'M/s Haji Gul Muhammad Khan Shirani',
                'village' => 'Noor Shah',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 88,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 9,
                'machinery' => 1,
                'reason' => null,
                'remarks' => 'Soling 40% remaining',
                'lat' => 29.6300,
                'lng' => 71.9210,
            ],
            // Row 3: Kahror Pakka / KRP-02
            [
                'tehsil' => 'Kahror Pakka',
                'district' => 'Lodhran',
                'tehsil_code' => 'KRP',
                'package' => 'KRP-02',
                'contractor' => 'M/s Haji Gul Muhammad Khan Shirani',
                'village' => 'Alipur Kanju',
                'settlements' => 5,
                'status' => 'ongoing',
                'physical_progress' => 98,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 2,
                'machinery' => 0,
                'reason' => null,
                'remarks' => 'Finishing works',
                'lat' => 29.6360,
                'lng' => 71.9280,
            ],

            // Row 4: Shujabad / SJD-03 (PC-I Revised)
            [
                'tehsil' => 'Shujabad',
                'district' => 'Multan',
                'tehsil_code' => 'SJD',
                'package' => 'SJD-03 (PC-I Revised)',
                'contractor' => 'M/s Al Shan Construction & Co.',
                'village' => 'Mithu Gharbi',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 86,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 5,
                'machinery' => 1,
                'reason' => null,
                'remarks' => 'Water supply connection, restoration',
                'lat' => 29.8800,
                'lng' => 71.2950,
            ],
            // Row 5: Shujabad / SJD-03 (PC-I Revised)
            [
                'tehsil' => 'Shujabad',
                'district' => 'Multan',
                'tehsil_code' => 'SJD',
                'package' => 'SJD-03 (PC-I Revised)',
                'contractor' => 'M/s Al Shan Construction & Co.',
                'village' => 'Gardezpur',
                'settlements' => 3,
                'status' => 'ongoing',
                'physical_progress' => 86,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'X3',
                'remarks' => '2400 rft pipe laying in C&W (NOC noting done at HO), ABR finishing',
                'lat' => 29.8870,
                'lng' => 71.3020,
            ],

            // Row 6: Kallar Kahar / KLK-01
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-01',
                'contractor' => 'M/s. Tayyab Manzoor Tarar (Pvt) Ltd',
                'village' => 'Ghai',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 9,
                'machinery' => 4,
                'reason' => null,
                'remarks' => 'Sewer line excavation (1800rft), Soling rectification, STP finishing works, waste water pumps',
                'lat' => 32.7818,
                'lng' => 72.6983,
            ],
            // Row 7: Kallar Kahar / KLK-01
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-01',
                'contractor' => 'M/s. Tayyab Manzoor Tarar (Pvt) Ltd',
                'village' => 'Makhyal',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 18,
                'machinery' => 5,
                'reason' => null,
                'remarks' => 'Pipe Line Testing Remaining, Waste Water Pump Remaining, 14000 Rft Restoration Brick soling Remaining & STP Works Remaining',
                'lat' => 32.7870,
                'lng' => 72.7050,
            ],
            // Row 8: Kallar Kahar / KLK-02
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-02',
                'contractor' => 'M/s Naveed Construction Pvt Ltd',
                'village' => 'Khairpur',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => '800 Water Supply House Connections Remaining, Complete Pavement Works Remaining, rectification of one bore remaining, Problematic Contractor - Not Responding at Site nor addressing the slow Progress, Payment issue, (Contract not shown on MIS, Pending Previous IPCs & VOs status on MIS)',
                'lat' => 32.7930,
                'lng' => 72.7120,
            ],
            // Row 9: Kallar Kahar / KLK-03
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-03',
                'contractor' => 'M/s Naveed Construction Pvt Ltd',
                'village' => 'Ghuffanwala',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 32.7990,
                'lng' => 72.7190,
            ],

            // Row 10: Noorpur Thal / NPT-01 (PC-I Revised)
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-01 (PC-I Revised)',
                'contractor' => 'M/s Aurangzeb & Sons',
                'village' => 'Tetri',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 81,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 31.8744,
                'lng' => 71.9056,
            ],
            // Row 11: Noorpur Thal / NPT-05
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-01 (PC-I Revised)',
                'contractor' => 'M/s Aurangzeb & Sons',
                'village' => 'Chak 17/MB',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 9,
                'machinery' => 1,
                'reason' => null,
                'remarks' => 'Boundary Wall',
                'lat' => 31.8810,
                'lng' => 71.9120,
            ],
            // Row 12: Noorpur Thal / NPT-05
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-05',
                'contractor' => 'M/S Ali Constuction Company',
                'village' => 'Nawa Saggu',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 31.8870,
                'lng' => 71.9190,
            ],
            // Row 13: Noorpur Thal / NPT-04 (PC-I Revised)
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-04 (PC-I Revised)',
                'contractor' => 'M/S Naveed Brothers',
                'village' => 'Chak 51 DB',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 81,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 31.8930,
                'lng' => 71.9260,
            ],
            // Row 14: Noorpur Thal / NPT-04 (PC-I Revised)
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-04 (PC-I Revised)',
                'contractor' => 'M/S Naveed Brothers',
                'village' => 'Chak 50 DB',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 82,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 31.8990,
                'lng' => 71.9330,
            ],
            // Row 15: Noorpur Thal / NPT-04 (PC-I Revised)
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-04 (PC-I Revised)',
                'contractor' => 'M/S Naveed Brothers',
                'village' => 'Chak 52 DB',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 31.9050,
                'lng' => 71.9400,
            ],

            // Row 16: Khairpur Tamewali / KPT-02 (Re-Tendered)
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Kot Azam',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'active',
                'labour' => 5,
                'machinery' => 1,
                'reason' => null,
                'remarks' => 'Finishing work',
                'lat' => 29.5802,
                'lng' => 72.2472,
            ],
            // Row 17: Khairpur Tamewali / KPT-02 (Re-Tendered)
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Ara Budhu',
                'settlements' => 2,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'C1',
                'remarks' => 'Labour not deployed',
                'lat' => 29.5860,
                'lng' => 72.2540,
            ],

            // Row 18: Pakpattan / PKN-08
            [
                'tehsil' => 'Pakpattan',
                'district' => 'Pakpattan',
                'tehsil_code' => 'PKN',
                'package' => 'PKN-08',
                'contractor' => 'M/s Damaan Brothers Pvt Ltd.',
                'village' => 'Chak 93-D',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 30.3410,
                'lng' => 73.3860,
            ],
            // Row 19: Pakpattan / PKN-08
            [
                'tehsil' => 'Pakpattan',
                'district' => 'Pakpattan',
                'tehsil_code' => 'PKN',
                'package' => 'PKN-08',
                'contractor' => 'M/s Damaan Brothers Pvt Ltd.',
                'village' => 'Chak 15 SP',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 30.3470,
                'lng' => 73.3930,
            ],
            // Row 20: Pakpattan / PKN-08
            [
                'tehsil' => 'Pakpattan',
                'district' => 'Pakpattan',
                'tehsil_code' => 'PKN',
                'package' => 'PKN-08',
                'contractor' => 'M/s Damaan Brothers Pvt Ltd.',
                'village' => 'Chak 16 SP',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 80,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 30.3530,
                'lng' => 73.4000,
            ],
            // Row 21: Pakpattan / PKN-08
            [
                'tehsil' => 'Pakpattan',
                'district' => 'Pakpattan',
                'tehsil_code' => 'PKN',
                'package' => 'PKN-08',
                'contractor' => 'M/s Damaan Brothers Pvt Ltd.',
                'village' => 'CHAK 2-KB',
                'settlements' => 1,
                'status' => 'ongoing',
                'physical_progress' => 85,
                'expected_completion_date' => '2026-11-30',
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 30.3590,
                'lng' => 73.4070,
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
                    'phone' => '0300-33300'.str_pad($codeCounter, 2, '0', STR_PAD_LEFT),
                    'email' => 'contractorc'.$codeCounter.'@prmsc.pk',
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

            $siteCode = 'PRIC-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);

            $site = Site::updateOrCreate(
                [
                    'tehsil_id' => $tehsil->id,
                    'village_name' => $item['village'],
                    'priority_id' => $priorityC->id,
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
                    $dormantReason = match ($item['reason']) {
                        'P1' => $paymentReason,
                        null, 'C1' => $contractorReason,
                        default => Reason::where('code', $item['reason'])->first() ?? $contractorReason,
                    };

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
                        'reason_id' => $dormantReason->id,
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

        // Attach all tehsils to field officers so they have full access in the form
        $fieldUsers = User::role('Field Officer')->get();
        $allTehsilIds = Tehsil::pluck('id')->toArray();
        foreach ($fieldUsers as $u) {
            $u->tehsils()->syncWithoutDetaching($allTehsilIds);
        }
    }
}
