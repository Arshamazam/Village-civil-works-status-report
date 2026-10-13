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

class RemainingVillagesSeeder extends Seeder
{
    public function run(): void
    {
        $priorityD = Priority::firstOrCreate(
            ['code' => 'D'],
            [
                'name' => 'Remaining Pilot',
                'target_days' => 90,
                'deadline' => '2027-01-03',
                'start_date' => '2026-10-05',
                'colour' => '#64748B',
                'sort_order' => 4,
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

        $litigationReason = Reason::where('code', 'L1')->first()
            ?? Reason::where('top_level_group', 'Court')->first()
            ?? Reason::where('code', 'C1')->first()
            ?? Reason::first();

        // 31 Authentic Remaining Pilot Schemes from Master Sheet
        $items = [
            // Taunsa - TAU-06 (New Awarded)
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-06 (New Awarded)',
                'contractor' => 'M/s Zibcon Construction Company JV Best',
                'village' => 'Chulani',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'X1',
                'remarks' => 'Raining today',
                'lat' => 30.7045,
                'lng' => 70.6514,
            ],
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-06 (New Awarded)',
                'contractor' => 'M/s Zibcon Construction Company JV Best',
                'village' => 'Basti Peer',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 5,
                'machinery' => 2,
                'progress' => 38,
                'reason' => null,
                'remarks' => 'Sewer Work & Water supply',
                'lat' => 30.7100,
                'lng' => 70.6570,
            ],
            [
                'tehsil' => 'Taunsa',
                'district' => 'Taunsa',
                'tehsil_code' => 'TAU',
                'package' => 'TAU-06 (New Awarded)',
                'contractor' => 'M/s Zibcon Construction Company JV Best',
                'village' => 'Sanjar Syedan',
                'settlements' => 2,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 35,
                'reason' => 'X1',
                'remarks' => 'Raining today',
                'lat' => 30.7150,
                'lng' => 70.6620,
            ],

            // Bhowana - BNA-02
            [
                'tehsil' => 'Bhowana',
                'district' => 'Chiniot',
                'tehsil_code' => 'BNA',
                'package' => 'BNA-02',
                'contractor' => 'M/S Telerison Pvt Ltd.',
                'village' => 'Mouza Taja Bairwala',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue, pending with finance',
                'lat' => 31.5683,
                'lng' => 72.6489,
            ],
            [
                'tehsil' => 'Bhowana',
                'district' => 'Chiniot',
                'tehsil_code' => 'BNA',
                'package' => 'BNA-02',
                'contractor' => 'M/S Telerison Pvt Ltd.',
                'village' => 'Mouza Barkhurdar',
                'settlements' => 2,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue, pending with finance',
                'lat' => 31.5740,
                'lng' => 72.6540,
            ],
            [
                'tehsil' => 'Bhowana',
                'district' => 'Chiniot',
                'tehsil_code' => 'BNA',
                'package' => 'BNA-02',
                'contractor' => 'M/S Telerison Pvt Ltd.',
                'village' => 'Mouza Suleman',
                'settlements' => 2,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue, pending with finance',
                'lat' => 31.5800,
                'lng' => 72.6600,
            ],
            [
                'tehsil' => 'Bhowana',
                'district' => 'Chiniot',
                'tehsil_code' => 'BNA',
                'package' => 'BNA-02',
                'contractor' => 'M/S Telerison Pvt Ltd.',
                'village' => 'Mouza Bilharke',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue, pending with finance',
                'lat' => 31.5860,
                'lng' => 72.6660,
            ],

            // Darya Khan - DRK-02
            [
                'tehsil' => 'Darya Khan',
                'district' => 'Bhakkar',
                'tehsil_code' => 'DRK',
                'package' => 'DRK-02',
                'contractor' => 'M/s Damaan Builders JV M/s Ch. Shafqat & Co.',
                'village' => 'Lundi Nasheb',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'X2',
                'remarks' => 'Community issue',
                'lat' => 31.7874,
                'lng' => 71.1031,
            ],

            // Kahror Pakka - KRP-02 & KRP-05
            [
                'tehsil' => 'Kahror Pakka',
                'district' => 'Lodhran',
                'tehsil_code' => 'KRP',
                'package' => 'KRP-02',
                'contractor' => 'M/s Haji Gul Muhammad Khan Shirani',
                'village' => 'Jhok Ahir',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 22,
                'machinery' => 3,
                'progress' => 50,
                'reason' => null,
                'remarks' => 'Sewer line',
                'lat' => 29.6247,
                'lng' => 71.9149,
            ],
            [
                'tehsil' => 'Kahror Pakka',
                'district' => 'Lodhran',
                'tehsil_code' => 'KRP',
                'package' => 'KRP-05 (New Awarded)',
                'contractor' => 'Saddaqat Buider JV Yasir Iqbal',
                'village' => 'Chalay Wahin',
                'settlements' => 2,
                'day_status' => 'active',
                'labour' => 25,
                'machinery' => 5,
                'progress' => 90,
                'reason' => null,
                'remarks' => 'TST + Soling',
                'lat' => 29.6310,
                'lng' => 71.9220,
            ],

            // Alipur - ALP-02 & ALP-03
            [
                'tehsil' => 'Alipur',
                'district' => 'Muzaffargarh',
                'tehsil_code' => 'ALP',
                'package' => 'ALP-02 (New Awarded)',
                'contractor' => 'M/S Manzoor & Co.',
                'village' => 'Beit Nabi Shah',
                'settlements' => 12,
                'day_status' => 'active',
                'labour' => 7,
                'machinery' => 11,
                'progress' => 57,
                'reason' => null,
                'remarks' => 'Manhole, OHR column, Water supply',
                'lat' => 29.3824,
                'lng' => 70.9109,
            ],
            [
                'tehsil' => 'Alipur',
                'district' => 'Muzaffargarh',
                'tehsil_code' => 'ALP',
                'package' => 'ALP-02 (New Awarded)',
                'contractor' => 'M/S Manzoor & Co.',
                'village' => 'Ghauspur',
                'settlements' => 3,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 56,
                'reason' => 'P1',
                'remarks' => 'Payment issue, IPC pending at HO',
                'lat' => 29.3890,
                'lng' => 70.9180,
            ],
            [
                'tehsil' => 'Alipur',
                'district' => 'Muzaffargarh',
                'tehsil_code' => 'ALP',
                'package' => 'ALP-03 (New Awarded)',
                'contractor' => 'M/s Manzoor & Co. JV M/s Sajid Bin Aziz',
                'village' => 'Pirowali',
                'settlements' => 8,
                'day_status' => 'active',
                'labour' => 52,
                'machinery' => 4,
                'progress' => 73,
                'reason' => null,
                'remarks' => 'Soling Remaining',
                'lat' => 29.3950,
                'lng' => 70.9250,
            ],
            [
                'tehsil' => 'Alipur',
                'district' => 'Muzaffargarh',
                'tehsil_code' => 'ALP',
                'package' => 'ALP-03 (New Awarded)',
                'contractor' => 'M/s Manzoor & Co. JV M/s Sajid Bin Aziz',
                'village' => 'Yakewali',
                'settlements' => 8,
                'day_status' => 'active',
                'labour' => 124,
                'machinery' => 16,
                'progress' => 80,
                'reason' => null,
                'remarks' => 'Finishing Works',
                'lat' => 29.4020,
                'lng' => 70.9320,
            ],

            // Kallar Kahar
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-03',
                'contractor' => 'M/s Naveed Construction Pvt Ltd',
                'village' => 'Sarkalan',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 32.7818,
                'lng' => 72.6983,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-04',
                'contractor' => 'M/s Saif Construction Pvt Ltd',
                'village' => 'Wasnal',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 9,
                'machinery' => 1,
                'progress' => 65,
                'reason' => null,
                'remarks' => 'Zone 1 & 2: no work, Zone: Pipeline line excavation, restoration',
                'lat' => 32.7870,
                'lng' => 72.7050,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-04',
                'contractor' => 'M/s Saif Construction Pvt Ltd',
                'village' => 'Jhamra',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 15,
                'machinery' => 5,
                'progress' => 70,
                'reason' => null,
                'remarks' => 'Zone 1: remaining, Zone 2: PCC restoration, sluice valves',
                'lat' => 32.7930,
                'lng' => 72.7120,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-05 (New Awarded)',
                'contractor' => 'M/s Advance Engineering & Construction Company (ADECON)',
                'village' => 'Khoot',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 25,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 32.7990,
                'lng' => 72.7190,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-05 (New Awarded)',
                'contractor' => 'M/s Advance Engineering & Construction Company (ADECON)',
                'village' => 'Pahar Khan',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 10,
                'machinery' => 3,
                'progress' => 52,
                'reason' => null,
                'remarks' => 'Pipeline laying',
                'lat' => 32.8050,
                'lng' => 72.7260,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-05 (New Awarded)',
                'contractor' => 'M/s Advance Engineering & Construction Company (ADECON)',
                'village' => 'Sethi',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 32,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 32.8110,
                'lng' => 72.7330,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-05 (New Awarded)',
                'contractor' => 'M/s Advance Engineering & Construction Company (ADECON)',
                'village' => 'Warala',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 20,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 32.8170,
                'lng' => 72.7400,
            ],
            [
                'tehsil' => 'Kallar Kahar',
                'district' => 'Chakwal',
                'tehsil_code' => 'KLK',
                'package' => 'KLK-05 (New Awarded)',
                'contractor' => 'M/s Advance Engineering & Construction Company (ADECON)',
                'village' => 'Mattan Khurd',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 47,
                'reason' => 'P1',
                'remarks' => 'Payment issue',
                'lat' => 32.8230,
                'lng' => 72.7470,
            ],

            // Noorpur Thal
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-02 (New Awarded)',
                'contractor' => 'M/s Unique & Best JV',
                'village' => 'Adhi Sergal',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 12,
                'machinery' => 2,
                'progress' => 15,
                'reason' => null,
                'remarks' => 'ABR concrete',
                'lat' => 31.8744,
                'lng' => 71.9056,
            ],
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-02 (New Awarded)',
                'contractor' => 'M/s Unique & Best JV',
                'village' => 'Chak 49 MB',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 6,
                'machinery' => 1,
                'progress' => 10,
                'reason' => null,
                'remarks' => 'Sewer line in progress',
                'lat' => 31.8810,
                'lng' => 71.9120,
            ],
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-02 (New Awarded)',
                'contractor' => 'M/s Unique & Best JV',
                'village' => 'Chak 29 MB',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'C1',
                'remarks' => 'Labour unavailability',
                'lat' => 31.8870,
                'lng' => 71.9190,
            ],
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-02 (New Awarded)',
                'contractor' => 'M/s Unique & Best JV',
                'village' => 'Chak 53 DB',
                'settlements' => 1,
                'day_status' => 'active',
                'labour' => 6,
                'machinery' => 0,
                'progress' => 10,
                'reason' => null,
                'remarks' => 'House Connection',
                'lat' => 31.8930,
                'lng' => 71.9260,
            ],
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-03 (New Awarded)',
                'contractor' => 'M/s Unique & Best JV',
                'village' => 'Rangpur Baghure',
                'settlements' => 3,
                'day_status' => 'active',
                'labour' => 10,
                'machinery' => 2,
                'progress' => 15,
                'reason' => null,
                'remarks' => 'Manhole work',
                'lat' => 31.8990,
                'lng' => 71.9330,
            ],
            [
                'tehsil' => 'Noorpur Thal',
                'district' => 'Khushab',
                'tehsil_code' => 'NPT',
                'package' => 'NPT-04 (PC-I Revised)',
                'contractor' => 'M/S Naveed Brothers',
                'village' => 'Maikan',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 78,
                'reason' => 'P1',
                'remarks' => 'Contractor payment issue',
                'lat' => 31.9050,
                'lng' => 71.9400,
            ],

            // Khairpur Tamewali
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Awal Khan',
                'settlements' => 4,
                'day_status' => 'active',
                'labour' => 3,
                'machinery' => 1,
                'progress' => 10,
                'reason' => null,
                'remarks' => 'Soakage pit in progress',
                'lat' => 29.5802,
                'lng' => 72.2472,
            ],
            [
                'tehsil' => 'Khairpur Tamewali',
                'district' => 'Bahawalpur',
                'tehsil_code' => 'KPT',
                'package' => 'KPT-02 (Re-Tendered)',
                'contractor' => 'M/s Saddaqat Builders Pvt Ltd',
                'village' => 'Sharaf',
                'settlements' => 1,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'C1',
                'remarks' => 'No labour deployed',
                'lat' => 29.5860,
                'lng' => 72.2540,
            ],

            // Pakpattan
            [
                'tehsil' => 'Pakpattan',
                'district' => 'Pakpattan',
                'tehsil_code' => 'PKN',
                'package' => 'PKN-08',
                'contractor' => 'M/s Damaan Brothers Pvt Ltd.',
                'village' => 'Chak 23 SP',
                'settlements' => 2,
                'day_status' => 'dormant',
                'labour' => 0,
                'machinery' => 0,
                'progress' => 0,
                'reason' => 'P1',
                'remarks' => 'Payment',
                'lat' => 30.3410,
                'lng' => 73.3860,
            ],
        ];

        $codeCounter = 1;
        $reportDate = '2026-10-07';

        foreach ($items as $item) {
            $tehsil = Tehsil::where('name', $item['tehsil'])
                ->when($item['tehsil'] === 'Kahror Pakka' || $item['tehsil'] === 'Kahror Pacca', function ($q) {
                    $q->orWhere('name', 'Kahror Pakka')->orWhere('name', 'Kahror Pacca');
                })
                ->first();

            if (! $tehsil) {
                $tehsil = Tehsil::create([
                    'name' => $item['tehsil'],
                    'district' => $item['district'],
                    'code' => $item['tehsil_code'],
                ]);
            }

            $contractor = Contractor::firstOrCreate(
                ['name' => $item['contractor']],
                [
                    'contact_person' => $item['contractor'],
                    'phone' => '0300-44400'.str_pad($codeCounter, 2, '0', STR_PAD_LEFT),
                    'email' => 'contractord'.$codeCounter.'@prmsc.pk',
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

            $site = Site::where('priority_id', $priorityD->id)
                ->where('tehsil_id', $tehsil->id)
                ->where('village_name', $item['village'])
                ->first();

            if (! $site) {
                $siteCode = 'REM-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);
                while (Site::where('site_code', $siteCode)->exists()) {
                    $siteCode = 'REM-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);
                }
                $site = Site::create([
                    'site_code' => $siteCode,
                    'phase_id' => $phase1->id,
                    'tehsil_id' => $tehsil->id,
                    'village_name' => $item['village'],
                    'priority_id' => $priorityD->id,
                    'package_id' => $package->id,
                    'contractor_id' => $contractor->id,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'geofence_m' => 500,
                    'status' => 'ongoing',
                    'settlements' => $item['settlements'],
                    'physical_progress' => $item['progress'],
                ]);
            } else {
                $site->update([
                    'phase_id' => $phase1->id,
                    'package_id' => $package->id,
                    'contractor_id' => $contractor->id,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'status' => 'ongoing',
                    'settlements' => $item['settlements'],
                    'physical_progress' => $item['progress'],
                ]);
            }

            // Sync daily return for 2026-10-07
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
                        'work_done' => $item['remarks'] ?? 'Civil works in progress',
                        'approval_status' => 'approved',
                        'approved_by' => $adminUser->id,
                        'approved_at' => $reportDate.' 11:00:00',
                        'submitted_at' => $reportDate.' 10:45:00',
                    ]);
                } elseif ($item['day_status'] === 'dormant') {
                    $dormantReason = match ($item['reason'] ?? 'C1') {
                        'P1' => $paymentReason,
                        'L1' => $litigationReason,
                        'C1' => $contractorReason,
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
                        'labour_count' => $item['labour'],
                        'machinery_count' => $item['machinery'],
                        'reason_id' => $dormantReason->id,
                        'idle_since' => '2026-10-06',
                        'remarks' => $item['remarks'] ?? 'Inactive at site',
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
