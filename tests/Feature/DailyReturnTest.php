<?php

namespace Tests\Feature;

use App\Models\DailyReturn;
use App\Models\Reason;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-07 10:00:00', 'Asia/Karachi'));
        $this->seed();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_field_officer_sees_only_assigned_tehsil_sites(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $officer->tehsils()->sync([$tehsil1->id]);

        $response = $this->actingAs($officer)->get('/today');
        $response->assertStatus(200);

        // Should see Tehsil 01 site
        $t1Site = Site::where('tehsil_id', $tehsil1->id)->where('status', 'ongoing')->first();
        $response->assertSee($t1Site->village_name);

        // Should not see other tehsils
        $otherSite = Site::where('tehsil_id', '!=', $tehsil1->id)->where('status', 'ongoing')->first();
        $response->assertDontSee($otherSite->village_name);
    }

    public function test_officer_cannot_access_site_from_another_tehsil(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $officer->tehsils()->sync([$tehsil1->id]);

        $foreignSite = Site::where('tehsil_id', '!=', $tehsil1->id)->first();

        $response = $this->actingAs($officer)->get("/returns/{$foreignSite->id}");
        $response->assertStatus(403);
    }

    public function test_site_return_form_offers_completed_status_for_dashboard_card(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $site = Site::first();
        $officer->tehsils()->sync([$site->tehsil_id]);

        $response = $this->actingAs($officer)->get("/returns/{$site->id}/2026-10-14");

        $response->assertOk()
            ->assertSee('Completed (100%)')
            ->assertSee('Completed Villages');
    }

    public function test_field_today_places_immediate_completion_action_after_village_selection(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $site = Site::first();
        $officer->tehsils()->sync([$site->tehsil_id]);

        $response = $this->actingAs($officer)->get('/today');

        $response->assertOk()
            ->assertSee('Select Revenue Village:')
            ->assertSee('Mark ')
            ->assertSee('submitAsCompleted()')
            ->assertSee('immediately as completed');

        $html = $response->getContent();
        $villageSelectorPosition = strpos($html, 'id="villageSelect"');
        $completedButtonPosition = strpos($html, 'submitAsCompleted()');
        $dailyReturnFormPosition = strpos($html, '<!-- STEP 4: FURTHER QUESTIONS');

        $this->assertNotFalse($villageSelectorPosition);
        $this->assertNotFalse($completedButtonPosition);
        $this->assertNotFalse($dailyReturnFormPosition);
        $this->assertLessThan($completedButtonPosition, $villageSelectorPosition);
        $this->assertLessThan($dailyReturnFormPosition, $completedButtonPosition);
        $this->assertStringContainsString(
            "'/returns/' + currentVillage.id + '/' + date",
            $html,
        );
    }

    public function test_active_return_requires_labour_or_machinery(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $site = Site::where('tehsil_id', $tehsil1->id)->where('status', 'ongoing')->first();

        $photo = UploadedFile::fake()->image('site.jpg');

        // Both 0 should fail (FORM-4)
        $response = $this->actingAs($officer)->post("/returns/{$site->id}", [
            'status' => 'active',
            'labour_count' => 0,
            'machinery_count' => 0,
            'work_done' => 'Tough tiles 200 Rft',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'photos' => [$photo],
        ]);

        $response->assertSessionHasErrors('labour_count');
    }

    public function test_officer_can_submit_valid_active_return_with_photos(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $site = Site::where('tehsil_id', $tehsil1->id)->where('status', 'ongoing')->first();

        $photo = UploadedFile::fake()->image('work.jpg');

        $pktDate = Carbon::now('Asia/Karachi')->toDateString();

        $response = $this->actingAs($officer)->post("/returns/{$site->id}", [
            'status' => 'active',
            'labour_count' => 12,
            'machinery_count' => 2,
            'machinery_types' => ['excavator', 'mixer'],
            'work_done' => 'Excavation completed and PCC poured.',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'photos' => [$photo],
        ]);

        // Returns to the same phase / priority and tehsil so the officer can continue with the next village
        $response->assertRedirect(route('field.today', ['date' => $pktDate, 'cat' => 'phase-0', 'tehsil_id' => $site->tehsil_id]));

        $this->assertDatabaseHas('daily_returns', [
            'site_id' => $site->id,
            'status' => 'active',
            'labour_count' => 12,
            'machinery_count' => 2,
            'geofence_ok' => 1,
        ]);

        $this->assertDatabaseHas('return_photos', [
            'latitude' => $site->latitude,
        ]);
    }

    public function test_dormant_return_requires_reason_and_idle_since(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $site = Site::where('tehsil_id', $tehsil1->id)->where('status', 'ongoing')->first();

        $photo = UploadedFile::fake()->image('idle.jpg');

        // Missing reason should fail
        $response = $this->actingAs($officer)->post("/returns/{$site->id}", [
            'status' => 'dormant',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'photos' => [$photo],
        ]);

        $response->assertSessionHasErrors(['reason_id', 'idle_since']);
    }

    public function test_dormant_return_with_other_reason_requires_remarks(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $site = Site::where('tehsil_id', $tehsil1->id)->where('status', 'ongoing')->first();
        $otherReason = Reason::where('code', 'OT')->first();

        $photo = UploadedFile::fake()->image('idle.jpg');

        // Missing remarks for OT reason should fail
        $response = $this->actingAs($officer)->post("/returns/{$site->id}", [
            'status' => 'dormant',
            'reason_id' => $otherReason->id,
            'idle_since' => '2026-10-10',
            'remarks' => '',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'photos' => [$photo],
        ]);

        $response->assertSessionHasErrors('remarks');
    }

    public function test_outside_geofence_requires_remark(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $site = Site::where('tehsil_id', $tehsil1->id)->where('status', 'ongoing')->first();

        $photo = UploadedFile::fake()->image('site.jpg');

        // 10 km away (far outside 500m geofence) with empty remarks
        $response = $this->actingAs($officer)->post("/returns/{$site->id}", [
            'status' => 'active',
            'labour_count' => 10,
            'machinery_count' => 1,
            'work_done' => 'Work ongoing',
            'latitude' => $site->latitude + 0.1, // ~11 km away
            'longitude' => $site->longitude + 0.1,
            'remarks' => '',
            'photos' => [$photo],
        ]);

        $response->assertSessionHasErrors('remarks');

        // With remark, it should succeed but record geofence_ok = false (FORM-8)
        $response = $this->actingAs($officer)->post("/returns/{$site->id}", [
            'status' => 'active',
            'labour_count' => 10,
            'machinery_count' => 1,
            'work_done' => 'Work ongoing',
            'latitude' => $site->latitude + 0.1,
            'longitude' => $site->longitude + 0.1,
            'remarks' => 'Road blocked due to flooding, monitored from distance',
            'photos' => [$photo],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('daily_returns', [
            'site_id' => $site->id,
            'geofence_ok' => 0,
        ]);
    }

    public function test_offline_batch_sync_is_idempotent(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $site = Site::where('tehsil_id', $tehsil1->id)->first();
        $uuid = (string) Str::uuid();

        $payload = [
            'returns' => [
                [
                    'client_uuid' => $uuid,
                    'site_id' => $site->id,
                    'report_date' => '2026-10-14',
                    'status' => 'active',
                    'labour_count' => 20,
                    'machinery_count' => 3,
                    'work_done' => 'Offline synced work description',
                    'latitude' => $site->latitude,
                    'longitude' => $site->longitude,
                ],
            ],
        ];

        // First sync
        $response1 = $this->actingAs($officer)->postJson('/api/returns/sync', $payload);
        $response1->assertStatus(200);
        $this->assertEquals(1, DailyReturn::where('client_uuid', $uuid)->count());

        // Re-sync with same client_uuid does not duplicate
        $response2 = $this->actingAs($officer)->postJson('/api/returns/sync', $payload);
        $response2->assertStatus(200);
        $this->assertEquals(1, DailyReturn::where('client_uuid', $uuid)->count());
    }

    public function test_saving_daily_return_updates_physical_progress(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $site = Site::first();
        $officer->tehsils()->sync([$site->tehsil_id]);

        $response = $this->actingAs($officer)->post("/returns/{$site->id}/2026-10-14", [
            'status' => 'active',
            'physical_progress' => 85,
            'labour_count' => 12,
            'machinery_count' => 2,
            'work_done' => 'Soling and laying pipes completed',
            'latitude' => $site->latitude ?? 30.71,
            'longitude' => $site->longitude ?? 70.65,
        ]);

        $response->assertSessionHasNoErrors();
        $site->refresh();
        $this->assertEquals(85, $site->physical_progress);
    }

    public function test_submitting_village_as_completed_marks_status_completed_and_100_percent_progress(): void
    {
        $officer = User::where('email', 'field.t01@prmsc.gov.pk')->first();
        $site = Site::first();
        $officer->tehsils()->sync([$site->tehsil_id]);

        $response = $this->actingAs($officer)->post("/returns/{$site->id}/2026-10-14", [
            'status' => 'completed',
            'latitude' => $site->latitude ?? 30.71,
            'longitude' => $site->longitude ?? 70.65,
            'remarks' => 'Civil works completed on site.',
        ]);

        $response->assertSessionHasNoErrors();
        $site->refresh();
        $this->assertEquals(100, $site->physical_progress);

        $return = DailyReturn::where('site_id', $site->id)->whereDate('report_date', '2026-10-14')->first();
        $this->assertNotNull($return);
        $this->assertEquals('completed', $return->status);
    }
}
