<?php

namespace Tests\Feature;

use App\Models\Phase;
use App\Models\Priority;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $fieldOfficerTehsilA;
    protected User $fieldOfficerTehsilB;
    protected Tehsil $tehsilA;
    protected Tehsil $tehsilB;
    protected Site $siteA;
    protected Site $siteB;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldRole = Role::firstOrCreate(['name' => 'Field Officer']);

        $this->tehsilA = Tehsil::create(['name' => 'Taunsa', 'district' => 'DG Khan', 'code' => 'TAU']);
        $this->tehsilB = Tehsil::create(['name' => 'Kot Addu', 'district' => 'Kot Addu', 'code' => 'KTA']);

        $phase = Phase::create(['name' => 'Pilot', 'code' => 'PLT', 'is_active' => true]);
        $priority = Priority::create(['code' => 'A', 'name' => 'Priority A', 'deadline' => '2026-10-20', 'colour' => '#EF4444', 'target_days' => 45]);

        $this->siteA = Site::create([
            'site_code' => 'PLT-001',
            'village_name' => 'Village In Tehsil A',
            'phase_id' => $phase->id,
            'tehsil_id' => $this->tehsilA->id,
            'status' => 'ongoing',
        ]);

        $this->siteB = Site::create([
            'site_code' => 'PLT-002',
            'village_name' => 'Village In Tehsil B',
            'phase_id' => $phase->id,
            'tehsil_id' => $this->tehsilB->id,
            'status' => 'ongoing',
        ]);

        $this->fieldOfficerTehsilA = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $this->fieldOfficerTehsilA->assignRole($fieldRole);
        $this->fieldOfficerTehsilA->tehsils()->attach($this->tehsilA->id);

        $this->fieldOfficerTehsilB = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $this->fieldOfficerTehsilB->assignRole($fieldRole);
        $this->fieldOfficerTehsilB->tehsils()->attach($this->tehsilB->id);
    }

    public function test_field_officer_cannot_access_site_of_another_tehsil(): void
    {
        // Officer A attempting to access form for Site B (in Tehsil B) must receive 403 Forbidden
        $response = $this->actingAs($this->fieldOfficerTehsilA)
            ->get('/returns/' . $this->siteB->id);

        $response->assertStatus(403);
    }

    public function test_field_officer_cannot_submit_return_for_site_of_another_tehsil(): void
    {
        $response = $this->actingAs($this->fieldOfficerTehsilA)
            ->post('/returns/' . $this->siteB->id, [
                'status' => 'active',
                'labour_count' => 10,
                'machinery_count' => 1,
                'work_done' => 'Unauthorized submission attempt',
                'latitude' => 30.7,
                'longitude' => 70.6,
                'gps_accuracy_m' => 10,
            ]);

        $response->assertStatus(403);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@prmsc.test',
            'password' => Hash::make('password123'),
            'is_active' => false,
            'force_password_change' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@prmsc.test',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_logins_lock_user_after_five_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'testlock@prmsc.test',
            'password' => Hash::make('secret123'),
            'is_active' => true,
            'force_password_change' => false,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'testlock@prmsc.test',
                'password' => 'wrong-pass',
            ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
    }

    public function test_non_admin_cannot_access_admin_screens(): void
    {
        $response = $this->actingAs($this->fieldOfficerTehsilA)
            ->get('/admin/sites');

        $response->assertStatus(403);
    }
}
