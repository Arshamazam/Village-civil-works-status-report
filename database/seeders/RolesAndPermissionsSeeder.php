<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            'view-dashboard',
            'export-reports',
            'view-returns',
            'submit-returns',
            'edit-returns',
            'review-returns',
            'unlock-returns',
            'manage-master-data',
            'manage-users',
            'manage-settings',
            'resend-reports',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        // Create roles
        $superAdmin = Role::findOrCreate('Super Admin');
        $superAdmin->givePermissionTo(Permission::all());

        $hqManager = Role::findOrCreate('HQ Manager');
        $hqManager->givePermissionTo([
            'view-dashboard',
            'export-reports',
            'view-returns',
        ]);

        $inCharge = Role::findOrCreate('Tehsil In-charge');
        $inCharge->givePermissionTo([
            'view-dashboard',
            'view-returns',
            'review-returns',
            'unlock-returns',
            'edit-returns',
        ]);

        $fieldOfficer = Role::findOrCreate('Field Officer');
        $fieldOfficer->givePermissionTo([
            'view-returns',
            'submit-returns',
            'edit-returns',
        ]);

        $viewer = Role::findOrCreate('Viewer');
        $viewer->givePermissionTo([
            'view-dashboard',
        ]);

        // Default Users
        // Demo Users for PRSWSSP System
        $punjabAdmin = User::updateOrCreate(
            ['email' => 'admin@prmsc.punjab.gov.pk'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('Password123!'),
                'designation' => 'Super Administrator',
                'phone' => '0300-0000001',
                'is_active' => true,
            ]
        );
        $punjabAdmin->syncRoles(['Super Admin']);

        $punjabHq = User::updateOrCreate(
            ['email' => 'hq@prmsc.punjab.gov.pk'],
            [
                'name' => 'HQ Project Leadership',
                'password' => Hash::make('Password123!'),
                'designation' => 'Executive Director',
                'phone' => '0300-0000002',
                'is_active' => true,
            ]
        );
        $punjabHq->syncRoles(['HQ Manager']);

        $punjabIncharge = User::updateOrCreate(
            ['email' => 'incharge@prmsc.punjab.gov.pk'],
            [
                'name' => 'Tehsil In-Charge Taunsa',
                'password' => Hash::make('Password123!'),
                'designation' => 'Sub-Divisional Officer',
                'phone' => '0300-0000003',
                'is_active' => true,
            ]
        );
        $punjabIncharge->syncRoles(['Tehsil In-charge']);

        $punjabField = User::updateOrCreate(
            ['email' => 'field@prmsc.punjab.gov.pk'],
            [
                'name' => 'Field Officer Taunsa',
                'password' => Hash::make('Password123!'),
                'designation' => 'Field Inspector',
                'phone' => '0300-0000004',
                'is_active' => true,
            ]
        );
        $punjabField->syncRoles(['Field Officer']);

        // Test / Legacy Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@prmsc.gov.pk'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@12345'),
                'designation' => 'IT Director / Super Admin',
                'phone' => '0300-1234567',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['Super Admin']);

        $ceo = User::firstOrCreate(
            ['email' => 'ceo@prmsc.gov.pk'],
            [
                'name' => 'Chief Executive Officer',
                'password' => Hash::make('Ceo@12345'),
                'designation' => 'CEO PRMSC',
                'phone' => '0300-7654321',
                'is_active' => true,
            ]
        );
        $ceo->syncRoles(['HQ Manager']);

        $incharge1 = User::firstOrCreate(
            ['email' => 'incharge.t01@prmsc.gov.pk'],
            [
                'name' => 'Tehsil In-Charge 01',
                'password' => Hash::make('Incharge@12345'),
                'designation' => 'Sub-Divisional Officer',
                'phone' => '0311-1234567',
                'is_active' => true,
            ]
        );
        $incharge1->syncRoles(['Tehsil In-charge']);

        $field1 = User::firstOrCreate(
            ['email' => 'field.t01@prmsc.gov.pk'],
            [
                'name' => 'Field Officer 01',
                'password' => Hash::make('Field@12345'),
                'designation' => 'Field Inspector',
                'phone' => '0322-1234567',
                'is_active' => true,
            ]
        );
        $field1->syncRoles(['Field Officer']);
    }
}
