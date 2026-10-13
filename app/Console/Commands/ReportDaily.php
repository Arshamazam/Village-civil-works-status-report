<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Models\DailySummary;
use App\Models\Holiday;
use App\Models\ReportRecipient;
use App\Models\ReportRun;
use App\Models\Setting;
use App\Services\ReportBuilder;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReportDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:daily {date?} {--force : Run even on holidays or non-reporting days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the daily PDF mobilization report and email it to stakeholders (RPT-1, MAIL-1, MAIL-2)';

    public function handle(ReportBuilder $reportBuilder, RollupService $rollupService): int
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $this->argument('date') ?? Carbon::now($tz)->toDateString();
        $dateCarbon = Carbon::parse($date, $tz);

        // Check if Sunday or Holiday (MAIL-7)
        $isHoliday = Holiday::where('date', $dateCarbon->toDateString())->exists();
        $isSunday = $dateCarbon->isSunday();

        if (($isHoliday || $isSunday) && !$this->option('force') && !$this->argument('date')) {
            $this->info("Skipping daily report for non-reporting day ({$dateCarbon->format('l, Y-m-d')}).");
            return Command::SUCCESS;
        }

        $this->info("Generating Daily Report for: {$date}");

        // 1. Ensure rollup snapshot exists
        $summaryRecord = DailySummary::where(function ($q) use ($date) {
            $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
        })->first();

        if (!$summaryRecord) {
            $this->info("Running rollup engine first...");
            $payload = $rollupService->execute($date);
        } else {
            $payload = $summaryRecord->payload;
        }

        // 2. Generate master PDF report
        try {
            $pdfResult = $reportBuilder->buildDailyPdf($date);
            $this->info("Master PDF generated: {$pdfResult['filename']}");
        } catch (\Throwable $e) {
            $this->error("Failed to generate PDF: {$e->getMessage()}");
            Log::error("PDF generation failure: " . $e->getMessage(), ['exception' => $e]);
            ReportRun::create([
                'report_date' => $date,
                'type' => 'daily',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }

        // 3. Create ReportRun record
        $reportRun = ReportRun::create([
            'report_date' => $date,
            'type' => 'daily',
            'pdf_path' => $pdfResult['path'],
            'status' => 'processing',
            'recipients_count' => 0,
        ]);

        // 4. Dispatch emails to recipients (MAIL-1, MAIL-2, MAIL-5, MAIL-8)
        $recipients = ReportRecipient::where('is_active', true)->get();
        $sentCount = 0;

        foreach ($recipients as $recipient) {
            try {
                if ($recipient->scope === 'all') {
                    // Send Master PDF
                    Mail::to($recipient->email)->queue(
                        new DailyReportMail($payload, $date, $pdfResult['full_path'], $pdfResult['filename'])
                    );
                    $sentCount++;
                } else {
                    // Tehsil-scoped recipient (MAIL-2, RPT-3)
                    $tehsilId = (int)$recipient->scope;
                    $tehsilPdf = $reportBuilder->buildDailyPdf($date, $tehsilId);
                    $tehsilName = $recipient->tehsil->name ?? "Tehsil-{$tehsilId}";

                    Mail::to($recipient->email)->queue(
                        new DailyReportMail($payload, $date, $tehsilPdf['full_path'], $tehsilPdf['filename'], $tehsilName)
                    );
                    $sentCount++;
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to queue report email to {$recipient->email}: " . $e->getMessage());
            }
        }

        $reportRun->update([
            'status' => 'completed',
            'recipients_count' => $sentCount,
            'sent_at' => now(),
        ]);

        $this->info("Daily report successfully built and dispatched to {$sentCount} recipients.");
        return Command::SUCCESS;
    }
}
