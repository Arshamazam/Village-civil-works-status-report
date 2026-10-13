<?php

namespace Database\Seeders;

use App\Models\Contractor;
use App\Models\Package;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\Reason;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleMasterAndRollupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Phases
        $pilot = Phase::updateOrCreate(
            ['code' => 'pilot'],
            ['name' => 'Pilot Phase', 'is_active' => true]
        );
        Phase::updateOrCreate(
            ['code' => 'phase-0'],
            ['name' => 'Phase-0', 'is_active' => true]
        );

        // 2. Priorities
        $priorities = [
            '1' => ['name' => 'Priority 1', 'target_days' => 15, 'deadline' => '2026-10-20', 'start_date' => '2026-10-05', 'sort_order' => 1, 'colour' => '#EF4444'],
            '2' => ['name' => 'Priority 2', 'target_days' => 45, 'deadline' => '2026-11-19', 'start_date' => '2026-10-05', 'sort_order' => 2, 'colour' => '#F59E0B'],
            '3' => ['name' => 'Priority 3', 'target_days' => 75, 'deadline' => '2026-12-19', 'start_date' => '2026-10-05', 'sort_order' => 3, 'colour' => '#3B82F6'],
            '4' => ['name' => 'Priority 4', 'target_days' => 90, 'deadline' => '2027-01-03', 'start_date' => '2026-10-05', 'sort_order' => 4, 'colour' => '#10B981'],
        ];
        $priorityModels = [];
        foreach ($priorities as $code => $pData) {
            $priorityModels[$code] = Priority::updateOrCreate(['code' => $code], $pData);
        }

        // 3. Tehsils (01 to 16)
        $tehsilModels = [];
        for ($i = 1; $i <= 16; $i++) {
            $name = sprintf('Tehsil %02d', $i);
            $code = sprintf('T%02d', $i);
            $tehsilModels[$name] = Tehsil::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'district' => 'Punjab District']
            );
        }

        // 4. Contractors (A to Q)
        $contractorModels = [];
        foreach (range('A', 'Q') as $char) {
            $cName = 'Contractor ' . $char;
            $contractorModels[$cName] = Contractor::updateOrCreate(
                ['name' => $cName],
                [
                    'contact_person' => "Manager $char",
                    'phone' => "0300-11122" . ord($char),
                    'email' => strtolower("contact.$char@contractors.pk"),
                ]
            );
        }

        // 5. Link users to Tehsils
        $inchargeUser = User::where('email', 'incharge.t01@prmsc.gov.pk')->first();
        if ($inchargeUser && isset($tehsilModels['Tehsil 01'])) {
            $inchargeUser->tehsils()->syncWithoutDetaching([$tehsilModels['Tehsil 01']->id]);
        }
        $fieldUser = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        if ($fieldUser && isset($tehsilModels['Tehsil 01'])) {
            $fieldUser->tehsils()->syncWithoutDetaching([$tehsilModels['Tehsil 01']->id]);
        }

        // 6. 133 Ongoing Sample Sites raw text from Sample Daily Report
        $sitesRaw = $this->getRawSites();
        $baseLat = 30.7100;
        $baseLng = 70.6500;

        foreach ($sitesRaw as $index => $row) {
            $pri = $row['pri'];
            $phCode = $row['ph'];
            $village = $row['village'];
            $tehsilName = $row['tehsil'];
            $contractorName = $row['contractor'];

            preg_match('/Sample Village (\d+)/', $village, $m);
            $vNum = isset($m[1]) ? (int)$m[1] : ($index + 1);

            $phaseModel = ($phCode === 'Pil') ? $pilot : $phase1;
            if ($phCode === 'Pil') {
                $siteCode = sprintf('PLT-%03d', $vNum);
            } else {
                $siteCode = sprintf('PH1-%03d', $vNum > 80 ? $vNum - 80 : $vNum);
            }

            $tehsil = $tehsilModels[$tehsilName] ?? $tehsilModels['Tehsil 01'];
            $contractor = $contractorModels[$contractorName] ?? $contractorModels['Contractor A'];
            $priority = $priorityModels[$pri] ?? $priorityModels['1'];

            // Package
            $pkgCode = $tehsil->code . '-PKG-' . substr($contractor->name, -1);
            $package = Package::firstOrCreate(
                ['code' => $pkgCode],
                [
                    'tehsil_id' => $tehsil->id,
                    'phase_id' => $phaseModel->id,
                    'contractor_id' => $contractor->id,
                ]
            );

            // Coordinates roughly in Punjab
            $lat = $baseLat + ($index * 0.015);
            $lng = $baseLng + ($index * 0.012);

            Site::updateOrCreate(
                ['site_code' => $siteCode],
                [
                    'village_name' => $village,
                    'phase_id' => $phaseModel->id,
                    'tehsil_id' => $tehsil->id,
                    'package_id' => $package->id,
                    'priority_id' => $priority->id,
                    'contractor_id' => $contractor->id,
                    'latitude' => round($lat, 6),
                    'longitude' => round($lng, 6),
                    'geofence_m' => 500,
                    'status' => 'ongoing',
                    'is_operational' => false,
                ]
            );
        }

        // 7. Seed 92 Completed Pilot Sites (to match 92/172 done in Pilot phase)
        for ($c = 1; $c <= 92; $c++) {
            $code = sprintf('PLT-CMP-%03d', $c);
            $village = sprintf('Completed Pilot Village %03d', $c);
            $tehsil = $tehsilModels['Tehsil ' . sprintf('%02d', (($c % 16) + 1))];
            $contractor = $contractorModels['Contractor ' . chr(65 + ($c % 17))];

            Site::updateOrCreate(
                ['site_code' => $code],
                [
                    'village_name' => $village,
                    'phase_id' => $pilot->id,
                    'tehsil_id' => $tehsil->id,
                    'package_id' => null,
                    'priority_id' => null,
                    'contractor_id' => $contractor->id,
                    'latitude' => round($baseLat + ($c * 0.005), 6),
                    'longitude' => round($baseLng - ($c * 0.005), 6),
                    'geofence_m' => 500,
                    'status' => 'completed',
                    'completed_on' => '2026-09-30',
                    'is_operational' => true,
                ]
            );
        }
    }

    public static function getRawSites(): array
    {
        $raw = [
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 126', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '26 days', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 020', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor L', 'lab' => 0, 'mach' => 0, 'status' => '24 days', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 027', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '24 days', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 111', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor I', 'lab' => 0, 'mach' => 0, 'status' => '20 days', 'reason_group' => 'PRMSC'],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 099', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '19 days', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 129', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor G', 'lab' => 0, 'mach' => 0, 'status' => '7 days', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 016', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 0, 'mach' => 0, 'status' => '4 days', 'reason_group' => 'PRMSC'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 056', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor I', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 127', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor Q', 'lab' => 0, 'mach' => 0, 'status' => '2 days', 'reason_group' => 'External'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 040', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 'NR', 'mach' => 'NR', 'status' => '1 day', 'reason_group' => 'Unstated'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 065', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 070', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor N', 'lab' => 'NR', 'mach' => 'NR', 'status' => '1 day', 'reason_group' => 'Unstated'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 037', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor Q', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 077', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor A', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'Contractor'],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 084', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor P', 'lab' => 'NR', 'mach' => 'NR', 'status' => '1 day', 'reason_group' => 'Unstated'],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 044', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor I', 'lab' => 24, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 101', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 16, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 072', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor J', 'lab' => 2, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 010', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 10, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 023', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 10, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 032', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor C', 'lab' => 3, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 128', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor N', 'lab' => 19, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 110', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor B', 'lab' => 8, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 069', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor C', 'lab' => 25, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 076', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor A', 'lab' => 9, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 097', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor P', 'lab' => 4, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Ph1', 'village' => 'Sample Village 088', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor P', 'lab' => 10, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '1', 'ph' => 'Pil', 'village' => 'Sample Village 041', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor K', 'lab' => 26, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],

            // Priority 2
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 079', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor A', 'lab' => 0, 'mach' => 0, 'status' => '26 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 124', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor J', 'lab' => 0, 'mach' => 0, 'status' => '25 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 051', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '8 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 031', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor J', 'lab' => 0, 'mach' => 0, 'status' => '7 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 091', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor L', 'lab' => 0, 'mach' => 0, 'status' => '7 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 029', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor A', 'lab' => 0, 'mach' => 0, 'status' => '7 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 113', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor E', 'lab' => 0, 'mach' => 0, 'status' => '6 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 013', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor J', 'lab' => 0, 'mach' => 0, 'status' => '5 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 119', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor Q', 'lab' => 0, 'mach' => 0, 'status' => '5 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 015', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '4 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 022', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor M', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'PRMSC'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 117', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 107', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor A', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 109', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 074', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor G', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'PRMSC'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 043', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor N', 'lab' => 'NR', 'mach' => 'NR', 'status' => '1 day', 'reason_group' => 'Unstated'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 039', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'Contractor'],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 036', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor Q', 'lab' => 32, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 081', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 32, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 092', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 15, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 130', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 22, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 061', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 17, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 017', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor J', 'lab' => 31, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 026', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor G', 'lab' => 21, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 108', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 10, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 049', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor O', 'lab' => 9, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 104', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor C', 'lab' => 19, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 001', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor O', 'lab' => 21, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 009', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor A', 'lab' => 11, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 042', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor A', 'lab' => 18, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 086', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor Q', 'lab' => 14, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 024', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor Q', 'lab' => 31, '0' => 0, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 083', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor K', 'lab' => 13, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 096', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor C', 'lab' => 26, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 112', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor P', 'lab' => 28, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 052', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor P', 'lab' => 34, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 064', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor M', 'lab' => 13, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Ph1', 'village' => 'Sample Village 106', 'tehsil' => 'Tehsil 16', 'contractor' => 'ContractI', 'contractor' => 'Contractor I', 'lab' => 18, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 014', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor K', 'lab' => 31, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '2', 'ph' => 'Pil', 'village' => 'Sample Village 046', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor K', 'lab' => 8, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],

            // Priority 3
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 005', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor P', 'lab' => 0, 'mach' => 0, 'status' => '34 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 028', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 0, 'mach' => 0, 'status' => '33 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 118', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor K', 'lab' => 0, 'mach' => 0, 'status' => '32 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 035', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor I', 'lab' => 0, 'mach' => 0, 'status' => '30 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 075', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor M', 'lab' => 0, 'mach' => 0, 'status' => '20 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 125', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor M', 'lab' => 0, 'mach' => 0, 'status' => '6 days', 'reason_group' => 'PRMSC'],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 021', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor O', 'lab' => 0, 'mach' => 0, 'status' => '6 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 100', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor Q', 'lab' => 0, 'mach' => 0, 'status' => '4 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 085', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor J', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 082', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 030', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '2 days', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 131', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor P', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 094', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor P', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'Contractor'],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 133', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor I', 'lab' => 15, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 008', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor Q', 'lab' => 30, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 006', 'tehsil' => 'Tehsil 02', 'contractor' => 'Contractor B', 'lab' => 19, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 098', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 31, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 033', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor K', 'lab' => 34, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 004', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor N', 'lab' => 24, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 034', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor N', 'lab' => 15, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 087', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor O', 'lab' => 19, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 121', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor A', 'lab' => 10, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 132', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor Q', 'lab' => 21, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 025', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor A', 'lab' => 15, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 055', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor B', 'lab' => 20, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 060', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor B', 'lab' => 16, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 067', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor B', 'lab' => 20, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 078', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor A', 'lab' => 34, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 123', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor N', 'lab' => 28, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 073', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor N', 'lab' => 4, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Ph1', 'village' => 'Sample Village 115', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor C', 'lab' => 24, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 059', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor C', 'lab' => 12, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 066', 'tehsil' => 'Tehsil 13', 'contractor' => 'Contractor C', 'lab' => 6, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 057', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor L', 'lab' => 17, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '3', 'ph' => 'Pil', 'village' => 'Sample Village 063', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor I', 'lab' => 7, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],

            // Priority 4
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 114', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor L', 'lab' => 0, 'mach' => 0, 'status' => '30 days', 'reason_group' => 'Contractor'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 054', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor Q', 'lab' => 0, 'mach' => 0, 'status' => '29 days', 'reason_group' => 'Contractor'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 019', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor J', 'lab' => 0, 'mach' => 0, 'status' => '27 days', 'reason_group' => 'Contractor'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 053', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor Q', 'lab' => 0, 'mach' => 0, 'status' => '26 days', 'reason_group' => 'Contractor'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 050', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor O', 'lab' => 0, 'mach' => 0, 'status' => '6 days', 'reason_group' => 'External'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 071', 'tehsil' => 'Tehsil 10', 'contractor' => 'Contractor N', 'lab' => 0, 'mach' => 0, 'status' => '4 days', 'reason_group' => 'Contractor'],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 095', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor C', 'lab' => 0, 'mach' => 0, 'status' => '3 days', 'reason_group' => 'Contractor'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 011', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor Q', 'lab' => 'NR', 'mach' => 'NR', 'status' => '1 day', 'reason_group' => 'Unstated'],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 090', 'tehsil' => 'Tehsil 04', 'contractor' => 'Contractor O', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'External'],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 038', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor K', 'lab' => 0, 'mach' => 0, 'status' => '1 day', 'reason_group' => 'PRMSC'],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 103', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor Q', 'lab' => 10, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 012', 'tehsil' => 'Tehsil 01', 'contractor' => 'Contractor Q', 'lab' => 8, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 120', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor J', 'lab' => 32, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 018', 'tehsil' => 'Tehsil 03', 'contractor' => 'Contractor G', 'lab' => 5, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 116', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor C', 'lab' => 15, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 122', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor C', 'lab' => 32, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 068', 'tehsil' => 'Tehsil 05', 'contractor' => 'Contractor J', 'lab' => 23, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 105', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor N', 'lab' => 13, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 047', 'tehsil' => 'Tehsil 06', 'contractor' => 'Contractor M', 'lab' => 23, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 093', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor O', 'lab' => 31, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 062', 'tehsil' => 'Tehsil 07', 'contractor' => 'Contractor O', 'lab' => 27, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 102', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor A', 'lab' => 17, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 080', 'tehsil' => 'Tehsil 08', 'contractor' => 'Contractor Q', 'lab' => 32, 'mach' => 3, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Ph1', 'village' => 'Sample Village 089', 'tehsil' => 'Tehsil 09', 'contractor' => 'Contractor B', 'lab' => 16, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 003', 'tehsil' => 'Tehsil 11', 'contractor' => 'Contractor C', 'lab' => 28, 'mach' => 4, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 058', 'tehsil' => 'Tehsil 12', 'contractor' => 'Contractor P', 'lab' => 3, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 002', 'tehsil' => 'Tehsil 14', 'contractor' => 'Contractor L', 'lab' => 14, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 007', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor P', 'lab' => 20, 'mach' => 0, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 048', 'tehsil' => 'Tehsil 15', 'contractor' => 'Contractor M', 'lab' => 8, 'mach' => 1, 'status' => 'Active', 'reason_group' => null],
            ['pri' => '4', 'ph' => 'Pil', 'village' => 'Sample Village 045', 'tehsil' => 'Tehsil 16', 'contractor' => 'Contractor I', 'lab' => 29, 'mach' => 2, 'status' => 'Active', 'reason_group' => null],
        ];

        return $raw;
    }
}
