<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Phase;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_view_sites_master_list(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();

        $response = $this->actingAs($admin)->get('/admin/sites');
        $response->assertStatus(200);
        $response->assertSeeText('Sites & Schemes Master Data');
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $field = User::where('email', 'field.t01@prmsc.gov.pk')->first();

        $response = $this->actingAs($field)->get('/admin/sites');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_site(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();
        $phase = Phase::first();
        $tehsil = Tehsil::first();

        $response = $this->actingAs($admin)->post('/admin/sites', [
            'site_code' => 'TEST-001',
            'village_name' => 'Test Model Village',
            'phase_id' => $phase->id,
            'tehsil_id' => $tehsil->id,
            'status' => 'ongoing',
            'latitude' => 30.1234,
            'longitude' => 70.5678,
            'geofence_m' => 500,
        ]);

        $response->assertRedirect('/admin/sites');
        $this->assertDatabaseHas('sites', [
            'site_code' => 'TEST-001',
            'village_name' => 'Test Model Village',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created_site',
            'model' => 'Site',
        ]);
    }

    public function test_admin_can_mark_site_completed(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();
        $site = Site::where('status', 'ongoing')->first();

        $response = $this->actingAs($admin)->post("/admin/sites/{$site->id}/complete");

        $response->assertSessionHas('success');
        $site->refresh();
        $this->assertEquals('completed', $site->status);
        $this->assertNotNull($site->completed_on);
        $this->assertTrue($site->is_operational);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'marked_completed_site',
            'model' => 'Site',
            'model_id' => $site->id,
        ]);
    }

    public function test_admin_can_update_gps_coordinates(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();
        $site = Site::first();

        $response = $this->actingAs($admin)->post("/admin/sites/{$site->id}/gps", [
            'latitude' => 31.999999,
            'longitude' => 72.888888,
            'geofence_m' => 600,
        ]);

        $response->assertSessionHas('success');
        $site->refresh();
        $this->assertEquals(31.999999, $site->latitude);
        $this->assertEquals(72.888888, $site->longitude);
        $this->assertEquals(600, $site->geofence_m);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated_gps_coordinates',
            'model' => 'Site',
            'model_id' => $site->id,
        ]);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $admin = User::where('email', 'admin@prmsc.gov.pk')->first();
        $field = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $this->assertTrue($field->is_active);

        $response = $this->actingAs($admin)->post("/admin/users/{$field->id}/toggle-active");

        $field->refresh();
        $this->assertFalse($field->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'toggled_user_active',
            'model' => 'User',
            'model_id' => $field->id,
        ]);
    }
}
