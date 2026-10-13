<?php

namespace Database\Seeders;

use App\Models\Contractor;
use App\Models\DailyReturn;
use App\Models\Package;
use App\Models\Phase;
use App\Models\Reason;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhaseZeroSeeder extends Seeder
{
    public function run(): void
    {
        $phase0 = Phase::firstOrCreate(
            ['code' => 'phase-0'],
            ['name' => 'Phase-0', 'is_active' => true]
        );

        $defaultContractor = Contractor::firstOrCreate(['name' => 'PRMSC Civil Works Contractor']);
        $defaultPackage = Package::firstOrCreate(
            ['code' => 'PH0-PKG'],
            ['phase_id' => $phase0->id, 'contractor_id' => $defaultContractor->id]
        );

        $adminUser = User::where('email', 'admin@prmsc.punjab.gov.pk')->first()
            ?? User::where('email', 'admin@prmsc.gov.pk')->first()
            ?? User::first();

        $contractorReason = Reason::where('code', 'C1')->first()
            ?? Reason::where('top_level_group', 'Contractor')->first()
            ?? Reason::first();

        // 78 Authentic Phase-0 Schemes from Master Sheet
        $items = [
            // 1-2: Ahmed Pur Sial (Deleted Villages, excluded from the 58 Phase-0 villages under execution)
            ['tehsil' => 'Ahmed Pur Sial', 'village' => 'Chak 4/3L', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'site_status' => 'suspended', 'remarks' => 'Deleted Village'],
            ['tehsil' => 'Ahmed Pur Sial', 'village' => 'Chak No.12/3-L', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'site_status' => 'suspended', 'remarks' => 'Deleted Village'],

            // 3-4: Alipur
            ['tehsil' => 'Alipur', 'village' => 'Langar Wah', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'V1', 'remarks' => 'VLD issue (possession of 16 sites in progress)'],
            ['tehsil' => 'Alipur', 'village' => 'Sundar', 'settlements' => 1, 'day_status' => 'active', 'labour' => 8, 'machinery' => 4, 'progress' => 1, 'reason' => null, 'remarks' => 'Soakage Pit & CST'],

            // 5-7: Bahawalnagar
            ['tehsil' => 'Bahawalnagar', 'village' => 'Shera Chitra', 'settlements' => 2, 'day_status' => 'active', 'labour' => 2, 'machinery' => 1, 'progress' => 2, 'reason' => null, 'remarks' => 'Excavation start'],
            ['tehsil' => 'Bahawalnagar', 'village' => 'Mohammad Pura', 'settlements' => 3, 'day_status' => 'active', 'labour' => 20, 'machinery' => 1, 'progress' => 25, 'reason' => null, 'remarks' => 'Steel fixing, shuttering, Drainage construction'],
            ['tehsil' => 'Bahawalnagar', 'village' => 'Shadiwala', 'settlements' => 3, 'day_status' => 'active', 'labour' => 29, 'machinery' => 1, 'progress' => 20, 'reason' => null, 'remarks' => 'Steel fixing, shuttering, Drainage construction'],

            // 8: Kahror Pakka
            ['tehsil' => 'Kahror Pakka', 'village' => 'Sharak Wah', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'RCC pipes deployed, Land delivered, will start within one week'],

            // 9-10: Khairpur Tamewali
            ['tehsil' => 'Khairpur Tamewali', 'village' => 'Piru Joya', 'settlements' => 1, 'day_status' => 'active', 'labour' => 7, 'machinery' => 1, 'progress' => 1, 'reason' => null, 'remarks' => 'PCC, Drain Excavation'],
            ['tehsil' => 'Khairpur Tamewali', 'village' => 'Dhuddi', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'X2', 'remarks' => 'Cancel village - community refusal'],

            // 11-26: Kot Momin (16 sites)
            ['tehsil' => 'Kot Momin', 'village' => 'Bagri', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Dolewala', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Mumrana', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Thatha Bairan', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Thathi Kalan', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Thathi Khurd', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Sughrewala', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Fatu Wala', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Chani Dal', 'settlements' => 3, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Burj Ghulam Rasul', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Kot Ghazi Kalan', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Kot Ghazi Khurd', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Sewar', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Kot Kala', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Kot Fateh Khan', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],
            ['tehsil' => 'Kot Momin', 'village' => 'Chak Suleman', 'settlements' => 3, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'P5', 'remarks' => 'Material Testing'],

            // 27-44: Liaqatpur (18 sites)
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 52', 'settlements' => 4, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 55 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak 51 Abbsia', 'settlements' => 1, 'day_status' => 'active', 'labour' => 7, 'machinery' => 0, 'progress' => 10, 'reason' => null, 'remarks' => 'Brick work Stand Post Chak, drain excavation'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 07 Abbasia', 'settlements' => 1, 'day_status' => 'active', 'labour' => 6, 'machinery' => 3, 'progress' => 20, 'reason' => null, 'remarks' => 'PCC Drain & Brick work Drain'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 10', 'settlements' => 2, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'No civil works'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 06', 'settlements' => 2, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'No civil works'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 02 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'No civil works'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 54 Abbasia', 'settlements' => 1, 'day_status' => 'active', 'labour' => 11, 'machinery' => 3, 'progress' => 45, 'reason' => null, 'remarks' => 'Brick work Drain & RCC Slab Drain'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak 53 Abbasia', 'settlements' => 1, 'day_status' => 'active', 'labour' => 12, 'machinery' => 3, 'progress' => 45, 'reason' => null, 'remarks' => 'RCC Septic tank & Curb, PCC Drain Chak'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 16 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 14 Abbasia', 'settlements' => 2, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 27 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 13 Abbasia', 'settlements' => 2, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 15 Abbasia', 'settlements' => 2, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 138 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 139 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 140 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],
            ['tehsil' => 'Liaqatpur', 'village' => 'Chak No 142 Abbasia', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Land given, contractor has not started work (MVS)'],

            // 45-58: Noorpur Thal (14 sites)
            ['tehsil' => 'Noorpur Thal', 'village' => 'Lal Hussain', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Contractor has not started work'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chandi Balochan', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Haveli Chiragh Joiya', 'settlements' => 2, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Pipli Sydean', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Contractor has not started work'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 014-A/M.B', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Contractor has not started work'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 016/M.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 041/D.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 042/D.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 043/D.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 044/D.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 045/D.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Chak No. 054/D.B', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Awanawala', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],
            ['tehsil' => 'Noorpur Thal', 'village' => 'Rakh Mahmood Shaheed', 'settlements' => 1, 'day_status' => 'active', 'labour' => 3, 'machinery' => 1, 'progress' => 5, 'reason' => null, 'remarks' => 'Boring in progress'],

            // 59: Taunsa
            ['tehsil' => 'Taunsa', 'village' => 'Jhok Bhutta', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'reason' => 'C1', 'remarks' => 'Contractor not working'],

            // 60: Shujabad
            ['tehsil' => 'Shujabad', 'village' => 'Sikanderabad Gharbi', 'settlements' => 1, 'day_status' => 'active', 'labour' => 56, 'machinery' => 7, 'progress' => 6, 'reason' => null, 'remarks' => 'RCC Septic tank & Drain excavation'],

            // 61-62: Kahror Pakka
            ['tehsil' => 'Kahror Pakka', 'village' => 'Hamble wala', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Kahror Pakka', 'village' => 'Kotla Narangan', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Deleted Village'],

            // 63-65: Khairpur Tamewali
            ['tehsil' => 'Khairpur Tamewali', 'village' => 'Thali Talbani', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Deleted Village'],
            ['tehsil' => 'Khairpur Tamewali', 'village' => 'Jhoke Lal', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Khairpur Tamewali', 'village' => 'Tibbi Ghafar Bukhsh', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],

            // 66-67: Kot Momin
            ['tehsil' => 'Kot Momin', 'village' => 'Wan Miana', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Kot Momin', 'village' => 'Rahimpur', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],

            // 68: Alipur
            ['tehsil' => 'Alipur', 'village' => 'Nawandera', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],

            // 69-71: Shujabad
            ['tehsil' => 'Shujabad', 'village' => 'Khoja Dera', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Shujabad', 'village' => 'Matotli', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Shujabad', 'village' => 'Sikandarabad Sharqi', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],

            // 72: Taunsa
            ['tehsil' => 'Taunsa', 'village' => 'Bindi', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Deleted Village'],

            // 73-74: Pakpattan
            ['tehsil' => 'Pakpattan', 'village' => 'Asadullah Pur', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Pakpattan', 'village' => 'Nizam Adlike', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],

            // 75-76: Darya Khan
            ['tehsil' => 'Darya Khan', 'village' => 'Chak No 46/ML', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process - Inactive at site'],
            ['tehsil' => 'Darya Khan', 'village' => 'Chak No 47/ML', 'settlements' => 1, 'day_status' => 'dormant', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process - Inactive at site'],

            // 77-78: Bhowana
            ['tehsil' => 'Bhowana', 'village' => 'Chak 156', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
            ['tehsil' => 'Bhowana', 'village' => 'Chak 203', 'settlements' => 1, 'day_status' => 'not_reported', 'labour' => 0, 'machinery' => 0, 'progress' => 0, 'remarks' => 'Tendering in Process'],
        ];

        $codeCounter = 1;
        $reportDate = '2026-10-07';
        $tehsilMap = [];

        foreach ($items as $item) {
            $tName = $item['tehsil'];
            if (! isset($tehsilMap[$tName])) {
                $tehsil = Tehsil::where('name', $tName)
                    ->when($tName === 'Kahror Pakka' || $tName === 'Kahror Pacca', function ($q) {
                        $q->orWhere('name', 'Kahror Pakka')->orWhere('name', 'Kahror Pacca');
                    })
                    ->first();

                if (! $tehsil) {
                    $tehsil = Tehsil::create([
                        'name' => $tName,
                        'district' => $tName,
                        'code' => strtoupper(substr(str_replace(' ', '', $tName), 0, 3)),
                    ]);
                }
                $tehsilMap[$tName] = $tehsil;
            }
            $tehsil = $tehsilMap[$tName];

            $site = Site::where('phase_id', $phase0->id)
                ->where('tehsil_id', $tehsil->id)
                ->where('village_name', $item['village'])
                ->first();

            $isTendering = str_contains($item['remarks'] ?? '', 'Tendering') || str_contains($item['remarks'] ?? '', 'Deleted');
            $siteStatus = $item['site_status'] ?? ($isTendering ? 'tendering' : 'ongoing');

            if (! $site) {
                $siteCode = 'PH0-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);
                while (Site::where('site_code', $siteCode)->exists()) {
                    $siteCode = 'PH0-'.str_pad($codeCounter++, 3, '0', STR_PAD_LEFT);
                }
                $site = Site::create([
                    'site_code' => $siteCode,
                    'phase_id' => $phase0->id,
                    'tehsil_id' => $tehsil->id,
                    'village_name' => $item['village'],
                    'settlements' => $item['settlements'],
                    'package_id' => $defaultPackage->id,
                    'contractor_id' => $defaultContractor->id,
                    'latitude' => 30.5000 + ($codeCounter * 0.01),
                    'longitude' => 71.0000 + ($codeCounter * 0.01),
                    'geofence_m' => 500,
                    'status' => $siteStatus,
                    'is_operational' => ! $isTendering,
                    'physical_progress' => $item['progress'],
                ]);
            } else {
                $site->update([
                    'settlements' => $item['settlements'],
                    'package_id' => $defaultPackage->id,
                    'contractor_id' => $defaultContractor->id,
                    'status' => $siteStatus,
                    'is_operational' => ! $isTendering,
                    'physical_progress' => $item['progress'],
                ]);
            }

            // Sync daily returns for 2026-10-07 only if site is ongoing
            if ($adminUser && ! $isTendering) {
                // In unit tests, leave Jhok Bhutta without return so DailyReturnTest and RollupAndReviewTest can test new submissions
                if (app()->runningUnitTests() && $item['village'] === 'Jhok Bhutta') {
                    continue;
                }

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
                        'reason_id' => (Reason::where('code', $item['reason'] ?? 'C1')->first() ?? $contractorReason)->id,
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

        // Attach all these tehsils to field officers so they have full access
        $fieldUsers = User::role('Field Officer')->get();
        $allTehsilIds = Tehsil::pluck('id')->toArray();
        foreach ($fieldUsers as $u) {
            $u->tehsils()->syncWithoutDetaching($allTehsilIds);
        }
    }
}
