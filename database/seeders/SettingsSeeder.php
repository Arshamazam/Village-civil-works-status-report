<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'cutoff_time' => '13:00',
            'report_time' => '14:00',
            'reminder_time' => '12:00',
            'rollup_time' => '13:30',
            'thin_manned_threshold' => '5',
            'default_geofence_m' => '500',
            'tracking_start_date' => '2026-10-05',
            'timezone' => 'Asia/Karachi',
            'watch_band_max' => '2',
            'warn_band_max' => '4',
            'escalate_band_max' => '6',
            'critical_band_min' => '7',
            'sender_email' => 'reports@prmsc.punjab.gov.pk',
            'sender_name' => 'PRMSC Daily Reports',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
