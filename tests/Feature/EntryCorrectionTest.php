<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DailyReturn;
use App\Models\DailySummary;
use App\Models\Reason;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private string $date = '2026-10-07';

    private User $hq;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00', 'Asia/Karachi'));
        $this->seed();

        $this->hq = User::where('email', 'hq@prmsc.punjab.gov.pk')->firstOrFail();
        $this->site = Site::where('village_name', 'Mithe Wali')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_hq_manager_corrects_an_existing_entry_and_dashboard_updates(): void
    {
        $this->actingAs($this->hq)->get(route('entries.edit', [$this->site, $this->date]))
            ->assertOk()
            ->assertSee('Edit daily entry')
            ->assertSee('Mithe Wali');

        $this->actingAs($this->hq)->put(route('entries.update', [$this->site, $this->date]), [
            'status' => 'active',
            'labour_count' => 42,
            'machinery_count' => 3,
            'machinery_types' => ['Excavator'],
            'work_done' => 'Soling verified by SDO',
            'physical_progress' => 93,
            'correction_note' => 'Confirmed by phone',
        ])->assertRedirect(route('dashboard', ['date' => $this->date]));

        $entry = DailyReturn::where('site_id', $this->site->id)->whereDate('report_date', $this->date)->sole();
        $this->assertSame(42, $entry->labour_count);
        $this->assertSame(93, $this->site->fresh()->physical_progress);

        $row = collect(DailySummary::whereDate('report_date', $this->date)->sole()->payload['register_rows'])->firstWhere('site_id', $this->site->id);
        $this->assertSame(42, $row['labour']);

        $log = AuditLog::where('action', 'corrected_daily_return')->latest('id')->firstOrFail();
        $this->assertSame($this->hq->id, $log->user_id);
    }

    public function test_hq_manager_can_enter_a_missing_return_reported_by_phone(): void
    {
        $missingDate = '2026-10-08';

        $this->actingAs($this->hq)->put(route('entries.update', [$this->site, $missingDate]), [
            'status' => 'dormant',
            'reason_id' => Reason::where('code', 'P1')->value('id'),
            'idle_since' => '2026-10-08',
            'remarks' => 'IPC pending',
        ])->assertRedirect();

        $entry = DailyReturn::where('site_id', $this->site->id)->whereDate('report_date', $missingDate)->sole();
        $this->assertSame('dormant', $entry->status);
        $this->assertSame('approved', $entry->approval_status);
        $this->assertSame($this->hq->id, $entry->submitted_by);
        $this->assertTrue(AuditLog::where('action', 'entered_daily_return_for_officer')->exists());
    }

    public function test_correction_follows_the_form_rules(): void
    {
        $this->actingAs($this->hq)->put(route('entries.update', [$this->site, $this->date]), [
            'status' => 'active',
            'labour_count' => 0,
            'machinery_count' => 0,
            'work_done' => 'Nothing on site',
        ])->assertSessionHasErrors('labour_count');

        $this->actingAs($this->hq)->put(route('entries.update', [$this->site, $this->date]), [
            'status' => 'dormant',
            'idle_since' => '2026-10-09',
        ])->assertSessionHasErrors(['reason_id', 'idle_since']);
    }

    public function test_field_officers_cannot_edit_entries_from_the_dashboard(): void
    {
        $officer = User::role('Field Officer')->firstOrFail();

        $this->actingAs($officer)->get(route('entries.edit', [$this->site, $this->date]))->assertForbidden();
        $this->actingAs($officer)->put(route('entries.update', [$this->site, $this->date]), ['status' => 'completed'])->assertForbidden();
    }

    public function test_edit_links_show_only_for_editors(): void
    {
        $editLink = route('entries.edit', [$this->site, $this->date]);

        $this->actingAs($this->hq)->get(route('dashboard', ['date' => $this->date]))->assertSee($editLink, false);

        $incharge = User::role('Tehsil In-charge')->firstOrFail();
        $this->actingAs($incharge)->get(route('dashboard', ['date' => $this->date]))->assertDontSee($editLink, false);
    }
}
