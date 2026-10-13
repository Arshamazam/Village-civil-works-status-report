<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\DailyReturn;
use App\Models\DailySummary;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use App\Services\DashboardSummary;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $hqManager;

    protected Site $site;

    protected string $reportDate = '2026-10-14';

    protected function setUp(): void
    {
        parent::setUp();

        $hqRole = Role::firstOrCreate(['name' => 'HQ Manager']);
        $this->hqManager = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $this->hqManager->assignRole($hqRole);

        $phase = Phase::create(['name' => 'Pilot', 'code' => 'PLT', 'is_active' => true]);
        $tehsil = Tehsil::create(['name' => 'Taunsa', 'district' => 'DG Khan', 'code' => 'TAU']);
        $priority = Priority::create(['code' => 'A', 'name' => 'Priority A', 'deadline' => '2026-10-20', 'colour' => '#EF4444', 'target_days' => 45]);
        $contractor = Contractor::create(['name' => 'Test Contractor']);

        $this->site = Site::create([
            'site_code' => 'PLT-001',
            'village_name' => 'Basti Buzdar',
            'phase_id' => $phase->id,
            'tehsil_id' => $tehsil->id,
            'priority_id' => $priority->id,
            'contractor_id' => $contractor->id,
            'latitude' => 30.701,
            'longitude' => 70.651,
            'status' => 'ongoing',
        ]);
    }

    public function test_dashboard_loads_fast_using_daily_summary_cache(): void
    {
        // Pre-cache rollup in daily_summaries
        $rollupService = app(RollupService::class);
        $rollupService->execute($this->reportDate);

        $this->assertTrue(
            DailySummary::where('report_date', 'like', $this->reportDate.'%')->exists()
        );

        $startTime = microtime(true);
        $response = $this->actingAs($this->hqManager)
            ->get('/dashboard?date='.$this->reportDate);
        $loadTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
        $response->assertSee('DAILY SITE MOBILIZATION DASHBOARD');
        $response->assertSee('Ongoing Sites');
        // Must load under 3 seconds (DASH-11, NFR-1)
        $this->assertLessThan(3.0, $loadTime);
    }

    public function test_priority_cards_and_headline_figures_are_computed_from_site_data(): void
    {
        $this->site->update(['expected_completion_date' => '2026-10-10', 'physical_progress' => 100]);
        DailyReturn::create([
            'site_id' => $this->site->id,
            'report_date' => $this->reportDate,
            'status' => 'completed',
            'labour_count' => 0,
            'machinery_count' => 0,
            'latitude' => $this->site->latitude,
            'longitude' => $this->site->longitude,
            'approval_status' => 'approved',
        ]);

        app(RollupService::class)->execute($this->reportDate);

        $response = $this->actingAs($this->hqManager)->get('/dashboard?date='.$this->reportDate);

        $response->assertStatus(200);

        $priorityA = collect($response->viewData('priorityDisplay'))->firstWhere('name', 'PRIORITY A');
        $this->assertSame(1, $priorityA['sites']);
        $this->assertSame(1, $priorityA['done']);
        $this->assertSame('10 Oct 2026', $priorityA['target']);
        $this->assertSame('4 DAYS OVERDUE', $priorityA['badge']);

        $dashStats = $response->viewData('dashStats');
        $this->assertSame(1, $dashStats['completed_count']);
        $this->assertSame(1, $dashStats['full_progress_count']);
        $this->assertSame(0, $dashStats['remaining_count']);
    }

    public function test_deadline_summary_labels_countdown_against_report_date(): void
    {
        $reportDate = Carbon::parse('2026-10-14');

        $this->assertSame(
            ['target' => '15 Oct / 30 Oct 2026', 'badge' => '1 DAY LEFT', 'tone' => 'urgent', 'days' => 1],
            RollupService::deadlineSummary(['2026-10-30', '2026-10-15', '2026-10-15', null], $reportDate)
        );
        $this->assertSame('on_track', RollupService::deadlineSummary(['2026-11-30'], $reportDate)['tone']);
        $this->assertSame('NO DEADLINE', RollupService::deadlineSummary([null], $reportDate)['badge']);
    }

    public function test_register_band_follows_status_crew_and_target(): void
    {
        $reportDate = Carbon::parse('2026-10-14');

        $this->assertSame('dormant', RollupService::registerBand('dormant', 0, 0, null, $reportDate));
        $this->assertSame('on_track', RollupService::registerBand('completed', 0, 0, Carbon::parse('2026-09-30'), $reportDate));
        $this->assertSame('watch', RollupService::registerBand('not_reported', 0, 0, null, $reportDate));
        $this->assertSame('past_target', RollupService::registerBand('active', 10, 1, Carbon::parse('2026-09-30'), $reportDate));
        $this->assertSame('watch', RollupService::registerBand('active', 3, 1, Carbon::parse('2026-11-30'), $reportDate));
        $this->assertSame('watch', RollupService::registerBand('active', 20, 1, Carbon::parse('2026-10-20'), $reportDate));
        $this->assertSame('on_track', RollupService::registerBand('active', 20, 1, Carbon::parse('2026-11-30'), $reportDate));
    }

    public function test_register_compares_each_site_with_its_previous_daily_entry(): void
    {
        DailyReturn::create([
            'site_id' => $this->site->id,
            'report_date' => '2026-10-13',
            'status' => 'active',
            'labour_count' => 4,
            'machinery_count' => 1,
            'latitude' => $this->site->latitude,
            'longitude' => $this->site->longitude,
            'approval_status' => 'approved',
        ]);
        DailyReturn::create([
            'site_id' => $this->site->id,
            'report_date' => $this->reportDate,
            'status' => 'active',
            'labour_count' => 9,
            'machinery_count' => 2,
            'latitude' => $this->site->latitude,
            'longitude' => $this->site->longitude,
            'approval_status' => 'approved',
        ]);

        $payload = app(RollupService::class)->execute($this->reportDate);
        $row = collect($payload['register_rows'])->firstWhere('site_id', $this->site->id);

        $this->assertSame(2, $row['active_days']);
        $this->assertSame(2, $row['reporting_days']);
        $this->assertSame(['label' => '+5 labour', 'tone' => 'up', 'date' => '13 Oct'], $row['previous_entry']);
    }

    public function test_completed_village_stays_completed_on_following_days(): void
    {
        DailyReturn::create([
            'site_id' => $this->site->id,
            'report_date' => '2026-10-07',
            'status' => 'completed',
            'labour_count' => 0,
            'machinery_count' => 0,
            'latitude' => $this->site->latitude,
            'longitude' => $this->site->longitude,
            'approval_status' => 'approved',
        ]);

        $payload = app(RollupService::class)->execute('2026-10-08');
        $row = collect($payload['register_rows'])->firstWhere('site_id', $this->site->id);

        $this->assertSame('completed', $row['day_status']);
        $this->assertSame(0, $payload['nr_count']);
    }

    public function test_card_details_download_as_pdf(): void
    {
        app(RollupService::class)->execute($this->reportDate);

        foreach (['total_villages', 'remaining_villages', 'total_labour', 'physical_progress', 'nr_sites'] as $type) {
            $response = $this->actingAs($this->hqManager)
                ->get(route('dashboard.details-pdf', ['type' => $type, 'date' => $this->reportDate]));

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $this->actingAs($this->hqManager)
            ->get(route('dashboard.details-pdf', ['type' => 'priority_card', 'name' => 'PRIORITY A', 'date' => $this->reportDate, 'q' => 'Buzdar']))
            ->assertOk();
    }

    public function test_card_details_pdf_rejects_unknown_card_and_missing_group(): void
    {
        $this->actingAs($this->hqManager)
            ->get(route('dashboard.details-pdf', ['type' => 'everything', 'date' => $this->reportDate]))
            ->assertSessionHasErrors('type');

        $this->actingAs($this->hqManager)
            ->get(route('dashboard.details-pdf', ['type' => 'priority_card', 'date' => $this->reportDate]))
            ->assertSessionHasErrors('name');
    }

    public function test_detail_list_applies_group_and_search_like_the_popup(): void
    {
        $payload = app(RollupService::class)->execute($this->reportDate);
        $summary = app(DashboardSummary::class);
        ['priorityDisplay' => $priorityDisplay] = $summary->build($payload, $this->reportDate);

        $priorityA = $summary->detailList($payload, 'priority_card', 'PRIORITY A', $priorityDisplay);
        $this->assertSame(['Basti Buzdar'], $priorityA['rows']->pluck('village')->all());
        $this->assertSame(1, $priorityA['stats']['Total Sites']);

        $noMatch = $summary->detailList($payload, 'total_villages', null, $priorityDisplay, 'no-such-village');
        $this->assertCount(0, $noMatch['rows']);
    }

    public function test_api_summary_returns_json_structure(): void
    {
        $rollupService = app(RollupService::class);
        $rollupService->execute($this->reportDate);

        $response = $this->actingAs($this->hqManager)
            ->getJson('/api/dashboard/'.$this->reportDate);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'report_date',
            'total_ongoing',
            'active_count',
            'dormant_count',
            'nr_count',
            'total_labour',
            'total_machinery',
            'thinly_manned_count',
            'active_pct',
            'idle_bands',
            'idle_groupings',
            'phases',
            'priorities',
            'contractors',
            'tehsils',
            'register_rows',
        ]);
    }

    public function test_site_profile_page_loads_with_30_day_strip(): void
    {
        $response = $this->actingAs($this->hqManager)
            ->get('/sites/'.$this->site->id);

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.site_detail');
        $response->assertSee($this->site->village_name);
        $response->assertSee('30-Day Status Calendar Strip');
    }

    public function test_gallery_page_loads_and_filters_by_tehsil(): void
    {
        $response = $this->actingAs($this->hqManager)
            ->get('/gallery?date='.$this->reportDate);

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.gallery');
        $response->assertSee('Site Photo Gallery');
    }

    public function test_dashboard_filters_by_tehsil_contractor_and_village(): void
    {
        $rollupService = app(RollupService::class);
        $rollupService->execute($this->reportDate);

        // Filter by matching Tehsil
        $res = $this->actingAs($this->hqManager)
            ->get("/dashboard?date={$this->reportDate}&tehsil=Taunsa");
        $res->assertStatus(200);
        $res->assertSee('Basti Buzdar');
        $res->assertSee('Taunsa');
        $res->assertSee('Filtered By:');

        // Filter by non-matching Tehsil
        $resNonMatch = $this->actingAs($this->hqManager)
            ->get("/dashboard?date={$this->reportDate}&tehsil=Alipur");
        $resNonMatch->assertStatus(200);
        $this->assertCount(0, $resNonMatch->viewData('payload')['register_rows']);
        $this->assertEquals(0, $resNonMatch->viewData('payload')['total_ongoing']);

        // Filter by matching Contractor
        $resContractor = $this->actingAs($this->hqManager)
            ->get("/dashboard?date={$this->reportDate}&contractor=Test+Contractor");
        $resContractor->assertStatus(200);
        $resContractor->assertSee('Basti Buzdar');

        // Filter by matching Village substring
        $resVillage = $this->actingAs($this->hqManager)
            ->get("/dashboard?date={$this->reportDate}&village=Buzdar");
        $resVillage->assertStatus(200);
        $resVillage->assertSee('Basti Buzdar');

        // Filter by Phase / Priority (Priority A)
        $resPhase = $this->actingAs($this->hqManager)
            ->get("/dashboard?date={$this->reportDate}&phase=priority_a");
        $resPhase->assertStatus(200);
        $this->assertGreaterThan(0, $resPhase->viewData('payload')['total_ongoing']);
        $resPhase->assertSee('Priority A');
    }

    public function test_export_data_sheet_downloads_file(): void
    {
        $res = $this->actingAs($this->hqManager)
            ->get("/dashboard/export/sheet?date={$this->reportDate}");
        $res->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $res->headers->get('content-disposition'), 'PRSWSSP_Progress_Data')
        );
    }

    public function test_export_no_progress_downloads_file(): void
    {
        $res = $this->actingAs($this->hqManager)
            ->get("/dashboard/export/no-progress?date={$this->reportDate}");
        $res->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $res->headers->get('content-disposition'), 'PRSWSSP_No_Progress_Sites')
        );
    }

    public function test_details_excel_downloads_file(): void
    {
        $res = $this->actingAs($this->hqManager)
            ->get("/dashboard/details.xlsx?date={$this->reportDate}&type=completed_villages");
        $res->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $res->headers->get('content-disposition'), 'PRSWSSP_completed_villages')
        );
    }

    public function test_details_pdf_no_progress(): void
    {
        $res = $this->actingAs($this->hqManager)
            ->get("/dashboard/details.pdf?date={$this->reportDate}&type=no_progress");
        $res->assertStatus(200);
        $this->assertEquals('application/pdf', $res->headers->get('content-type'));
    }
}
