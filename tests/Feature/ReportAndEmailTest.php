<?php

namespace Tests\Feature;

use App\Mail\DailyReportMail;
use App\Mail\ReminderMail;
use App\Models\Contractor;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\ReportRecipient;
use App\Models\Site;
use App\Models\Tehsil;
use App\Models\User;
use App\Services\DashboardSummary;
use App\Services\ReportBuilder;
use App\Services\RollupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportAndEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $fieldOfficer;

    protected Site $site;

    protected Tehsil $tehsil;

    protected string $reportDate = '2026-10-14';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $fieldRole = Role::firstOrCreate(['name' => 'Field Officer']);

        $this->superAdmin = User::factory()->create([
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $this->superAdmin->assignRole($adminRole);

        $this->fieldOfficer = User::factory()->create([
            'email' => 'officer@prmsc.test',
            'is_active' => true,
            'force_password_change' => false,
        ]);
        $this->fieldOfficer->assignRole($fieldRole);

        $phase = Phase::create(['name' => 'Pilot', 'code' => 'PLT', 'is_active' => true]);
        $this->tehsil = Tehsil::create(['name' => 'Taunsa', 'district' => 'DG Khan', 'code' => 'TAU']);
        $priority = Priority::create(['code' => 'A', 'name' => 'Priority A', 'deadline' => '2026-10-20', 'colour' => '#EF4444', 'target_days' => 45]);
        $contractor = Contractor::create(['name' => 'Test Contractor']);

        $this->fieldOfficer->tehsils()->attach($this->tehsil->id);

        $this->site = Site::create([
            'site_code' => 'PLT-001',
            'village_name' => 'Basti Buzdar',
            'phase_id' => $phase->id,
            'tehsil_id' => $this->tehsil->id,
            'priority_id' => $priority->id,
            'contractor_id' => $contractor->id,
            'latitude' => 30.701,
            'longitude' => 70.651,
            'status' => 'ongoing',
        ]);
    }

    public function test_report_builder_generates_valid_pdf(): void
    {
        $rollup = app(RollupService::class);
        $rollup->execute($this->reportDate);

        $reportBuilder = app(ReportBuilder::class);
        $result = $reportBuilder->buildDailyPdf($this->reportDate);

        $this->assertNotEmpty($result['content']);
        $this->assertStringStartsWith('%PDF', $result['content']);
        $this->assertStringEndsWith('.pdf', $result['filename']);
        Storage::disk('local')->assertExists($result['path']);
    }

    public function test_html_report_view_renders(): void
    {
        $rollup = app(RollupService::class);
        $rollup->execute($this->reportDate);

        $response = $this->actingAs($this->superAdmin)
            ->get('/reports/html/'.$this->reportDate);

        $response->assertStatus(200);
        $response->assertSee('DAILY SITE MOBILIZATION REPORT');
        $response->assertSeeInOrder([
            'Villages Under Execution',
            'Position by Completion Priority',
            'Site Status Today',
            'Dormant Sites by Cause',
            'Phase Progress Summary',
            'Site Register',
        ]);
    }

    public function test_pdf_download_route_returns_pdf_stream(): void
    {
        $rollup = app(RollupService::class);
        $rollup->execute($this->reportDate);

        $response = $this->actingAs($this->superAdmin)
            ->get('/reports/daily/'.$this->reportDate.'.pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_report_daily_command_queues_emails_and_logs_run(): void
    {
        ReportRecipient::create([
            'name' => 'Executive Director',
            'email' => 'ed@prmsc.test',
            'scope' => 'all',
            'report_type' => 'daily',
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('report:daily', [
            'date' => $this->reportDate,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Assert report run logged
        $this->assertDatabaseHas('report_runs', [
            'type' => 'daily',
            'status' => 'completed',
            'recipients_count' => 1,
        ]);

        // Assert mail was queued
        Mail::assertQueued(DailyReportMail::class, function ($mail) {
            return $mail->hasTo('ed@prmsc.test') && $mail->date === $this->reportDate;
        });
    }

    public function test_send_reminders_command_alerts_officers_with_pending_sites(): void
    {
        // No return submitted yet for $this->site on $this->reportDate
        $exitCode = Artisan::call('returns:remind', [
            'date' => $this->reportDate,
        ]);

        $this->assertEquals(0, $exitCode);

        Mail::assertQueued(ReminderMail::class, function ($mail) {
            return $mail->hasTo('officer@prmsc.test') && $mail->pendingSites->count() >= 1;
        });
    }

    public function test_pdf_report_excludes_physical_progress_card_and_chunks_register_cleanly(): void
    {
        $builder = app(ReportBuilder::class);
        $result = $builder->buildDailyPdf($this->reportDate);

        $this->assertNotEmpty($result['content']);

        // Render HTML view directly to assert content
        $rollup = app(RollupService::class);
        $payload = $rollup->execute($this->reportDate);
        $summary = app(DashboardSummary::class)->build($payload, $this->reportDate);
        $html = view('reports.daily', ['payload' => $payload, 'date' => $this->reportDate, ...$summary])->render();

        // The PDF executive summary must NOT show the dedicated physical progress card
        $this->assertStringNotContainsString('PHYSICAL PROGRESS', $html);

        // But must show all ongoing villages in the register
        $this->assertStringContainsString($this->site->village_name, $html);
    }
}
