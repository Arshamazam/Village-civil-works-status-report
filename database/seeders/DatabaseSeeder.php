<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            ReasonsSeeder::class,
            PhaseZeroSeeder::class,
            PriorityASeeder::class,
            PriorityBSeeder::class,
            PriorityCSeeder::class,
            RemainingVillagesSeeder::class,
        ]);
    }
}
