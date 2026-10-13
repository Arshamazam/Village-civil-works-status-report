<?php

namespace Tests\Feature;

use App\Models\DailyReturn;
use App\Models\DailySummary;
use App\Models\Holiday;
use App\Models\Site;
use App\Models\SiteDaySnapshot;
use App\Models\Tehsil;
use App\Models\User;
use App\Services\IdleCalculator;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RollupAndReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_rollup_command_runs_idempotently_and_generates_summary(): void
    {
        $this->artisan('progress:rollup', ['date' => '2026-10-14'])
            ->assertExitCode(0);

        $summary = DailySummary::whereDate('report_date', '2026-10-14')->first();
        $this->assertNotNull($summary);

        $payload = $summary->payload;

        $expectedOngoing = Site::where('status', 'ongoing')->count();
        $this->assertEquals($expectedOngoing, $payload['total_ongoing']);
        $this->assertArrayHasKey('idle_bands', $payload);
        $this->assertArrayHasKey('contractors', $payload);
        $this->assertArrayHasKey('tehsils', $payload);

        // Re-running for same date does not duplicate records
        $this->artisan('progress:rollup', ['date' => '2026-10-14'])
            ->assertExitCode(0);

        $this->assertEquals(1, DailySummary::whereDate('report_date', '2026-10-14')->count());
        $this->assertEquals($expectedOngoing, SiteDaySnapshot::whereDate('report_date', '2026-10-14')->count());
    }

    public function test_idle_days_calculation_skips_holidays_and_sundays(): void
    {
        // 2026-10-11 is a Sunday
        $sunday = '2026-10-11';
        $this->assertTrue(Carbon::parse($sunday)->isSunday());
        $this->assertTrue(IdleCalculator::isNonReportingDay($sunday));

        // Register a gazetted holiday on Monday 2026-10-12
        Holiday::create([
            'date' => '2026-10-12',
            'title' => 'Gazetted Holiday',
        ]);
        $this->assertTrue(IdleCalculator::isNonReportingDay('2026-10-12'));

        // If site last worked on Friday 2026-10-09, and report date is Tuesday 2026-10-13:
        // Days between:
        // Sat 10th (working day) -> 1
        // Sun 11th (Sunday) -> skipped
        // Mon 12th (Gazetted holiday) -> skipped
        // Tue 13th (working day) -> 1
        // Total idle days = 2
        $idleDays = IdleCalculator::countReportingDaysBetween('2026-10-09', '2026-10-13');
        $this->assertEquals(2, $idleDays);
    }

    public function test_tehsil_incharge_can_approve_return(): void
    {
        $incharge = User::where('email', 'incharge.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $incharge->tehsils()->sync([$tehsil1->id]);

        $site = Site::where('tehsil_id', $tehsil1->id)->first();
        $return = DailyReturn::create([
            'site_id' => $site->id,
            'report_date' => '2026-10-14',
            'status' => 'active',
            'labour_count' => 10,
            'machinery_count' => 1,
            'work_done' => 'Sample work done',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($incharge)->post("/review/{$return->id}/approve");

        $response->assertSessionHas('success');
        $return->refresh();
        $this->assertEquals('approved', $return->approval_status);
        $this->assertEquals($incharge->id, $return->approved_by);
    }

    public function test_tehsil_incharge_can_return_submission_with_comment(): void
    {
        $incharge = User::where('email', 'incharge.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $incharge->tehsils()->sync([$tehsil1->id]);

        $site = Site::where('tehsil_id', $tehsil1->id)->first();
        $return = DailyReturn::create([
            'site_id' => $site->id,
            'report_date' => '2026-10-14',
            'status' => 'active',
            'labour_count' => 10,
            'machinery_count' => 1,
            'work_done' => 'Sample work done',
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($incharge)->post("/review/{$return->id}/return", [
            'review_comment' => 'Please provide detailed breakdown of trenching vs soling.',
        ]);

        $response->assertSessionHas('success');
        $return->refresh();
        $this->assertEquals('returned', $return->approval_status);
        $this->assertEquals('Please provide detailed breakdown of trenching vs soling.', $return->review_comment);
    }

    public function test_tehsil_incharge_can_bulk_approve_pending_returns(): void
    {
        $incharge = User::where('email', 'incharge.t01@prmsc.gov.pk')->first();
        $tehsil1 = Tehsil::where('name', 'Taunsa')->first() ?: Tehsil::first();
        $incharge->tehsils()->sync([$tehsil1->id]);

        $sites = Site::where('tehsil_id', $tehsil1->id)->take(3)->get();
        foreach ($sites as $site) {
            DailyReturn::where('site_id', $site->id)->whereDate('report_date', '2026-10-14')->delete();
            DailyReturn::create([
                'site_id' => $site->id,
                'report_date' => '2026-10-14',
                'status' => 'active',
                'labour_count' => 8,
                'machinery_count' => 1,
                'work_done' => 'Work',
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'approval_status' => 'pending',
            ]);
        }

        $response = $this->actingAs($incharge)->post('/review/approve-all', [
            'date' => '2026-10-14',
            'tehsil_id' => $tehsil1->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, DailyReturn::whereDate('report_date', '2026-10-14')->where('approval_status', 'pending')->count());
    }
}
