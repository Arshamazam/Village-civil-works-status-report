<?php

namespace Tests\Feature;

use App\Models\DailyReturn;
use App\Models\DailySummary;
use App\Models\Reason;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $officer;

    protected Tehsil $tehsil;

    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00', 'Asia/Karachi'));
        $this->seed();
        Storage::fake('local');

        $this->officer = User::where('email', 'field.t01@prmsc.gov.pk')->firstOrFail();
        $this->tehsil = Tehsil::where('name', 'Taunsa')->firstOrFail();
        $this->officer->tehsils()->sync([$this->tehsil->id]);
        $this->site = Site::where('tehsil_id', $this->tehsil->id)->where('status', 'ongoing')->where('village_name', 'Mithe Wali')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function activeReturn(array $overrides = []): array
    {
        return array_merge([
            'client_uuid' => (string) Str::uuid(),
            'site_id' => $this->site->id,
            'report_date' => '2026-10-08',
            'captured_at' => Carbon::parse('2026-10-08 09:30:00', 'Asia/Karachi')->toIso8601String(),
            'status' => 'active',
            'labour_count' => 30,
            'machinery_count' => 2,
            'machinery_types' => ['Excavator'],
            'work_done' => 'Soling and house connections in progress',
            'physical_progress' => 92,
            'latitude' => $this->site->latitude,
            'longitude' => $this->site->longitude,
        ], $overrides);
    }

    public function test_officer_logs_in_and_receives_token_with_assigned_tehsils(): void
    {
        $response = $this->postJson('/api/mobile/login', [
            'email' => $this->officer->email,
            'password' => 'Field@12345',
            'device_name' => 'Test phone',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles', 'tehsils']])
            ->assertJsonPath('user.tehsils.0.name', 'Taunsa');
    }

    public function test_wrong_password_is_rejected_and_counted(): void
    {
        $this->postJson('/api/mobile/login', [
            'email' => $this->officer->email,
            'password' => 'wrong-password',
            'device_name' => 'Test phone',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertSame(1, $this->officer->fresh()->failed_login_attempts);
    }

    public function test_bootstrap_lists_only_assigned_villages_with_categories_and_reasons(): void
    {
        Sanctum::actingAs($this->officer);

        $response = $this->getJson('/api/mobile/bootstrap')->assertOk();

        $sites = collect($response->json('sites'));
        $this->assertNotEmpty($sites);
        $this->assertTrue($sites->every(fn ($site) => $site['tehsil_id'] === $this->tehsil->id));
        $this->assertSame('priority-b', $sites->firstWhere('village', 'Mithe Wali')['category']);
        $this->assertTrue($sites->firstWhere('village', 'Kalu Wala')['is_completed']);
        $this->assertSame('2026-10-08', $response->json('server_date'));
        $this->assertNotEmpty($response->json('reasons.0.children'));
    }

    public function test_queued_return_is_stored_with_photos_and_resending_updates_the_same_record(): void
    {
        Sanctum::actingAs($this->officer);
        $payload = $this->activeReturn(['photos' => [UploadedFile::fake()->image('site.jpg')]]);

        $this->post('/api/mobile/returns', $payload, ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('photos', 1)
            ->assertJsonPath('geofence_ok', true);

        $this->post('/api/mobile/returns', array_merge($payload, ['labour_count' => 35, 'photos' => []]), ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame(1, DailyReturn::where('client_uuid', $payload['client_uuid'])->count());
        $this->assertSame(35, DailyReturn::where('client_uuid', $payload['client_uuid'])->value('labour_count'));
        $this->assertSame(92, $this->site->fresh()->physical_progress);
        $this->assertTrue(DailySummary::whereDate('report_date', '2026-10-08')->exists());
    }

    public function test_return_recorded_before_cutoff_is_accepted_when_synced_later(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 18:00:00', 'Asia/Karachi'));
        Sanctum::actingAs($this->officer);

        $this->postJson('/api/mobile/returns', $this->activeReturn())
            ->assertCreated()
            ->assertJsonPath('is_late', false);
    }

    public function test_return_recorded_after_cutoff_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 18:00:00', 'Asia/Karachi'));
        Sanctum::actingAs($this->officer);

        $this->postJson('/api/mobile/returns', $this->activeReturn([
            'captured_at' => Carbon::parse('2026-10-08 15:00:00', 'Asia/Karachi')->toIso8601String(),
        ]))->assertUnprocessable();
    }

    public function test_officer_cannot_submit_for_another_tehsil(): void
    {
        Sanctum::actingAs($this->officer);
        $foreignSite = Site::where('tehsil_id', '!=', $this->tehsil->id)->where('status', 'ongoing')->firstOrFail();

        $this->postJson('/api/mobile/returns', $this->activeReturn(['site_id' => $foreignSite->id]))
            ->assertForbidden();
    }

    public function test_form_rules_match_the_web_form(): void
    {
        Sanctum::actingAs($this->officer);

        $this->postJson('/api/mobile/returns', $this->activeReturn(['labour_count' => 0, 'machinery_count' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('labour_count');

        $this->postJson('/api/mobile/returns', $this->activeReturn(['status' => 'dormant']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason_id', 'idle_since']);

        $this->postJson('/api/mobile/returns', $this->activeReturn([
            'status' => 'dormant',
            'reason_id' => Reason::where('code', 'P1')->value('id'),
            'idle_since' => '2026-10-07',
        ]))->assertCreated();
    }

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/mobile/bootstrap')->assertUnauthorized();
        $this->postJson('/api/mobile/returns', [])->assertUnauthorized();
    }
}
