<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProgressRollup extends Command
{
    protected $signature = 'progress:rollup {date? : Date to compute rollup for (YYYY-MM-DD)}';
    protected $description = 'Compute daily site snapshots and summary aggregates (idempotent)';

    public function handle(RollupService $rollupService): int
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $this->argument('date') ?? Carbon::now($tz)->toDateString();

        $this->info("Starting PRSWSSP Rollup Computation for {$date} (PKT)...");

        try {
            $summary = $rollupService->execute($date);

            $this->info("✓ Rollup completed successfully.");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Report Date', $summary['report_date']],
                    ['Day of Tracking', $summary['day_of_tracking']],
                    ['Total Ongoing Sites', $summary['total_ongoing']],
                    ['Active Sites', "{$summary['active_count']} ({$summary['active_pct']}%)"],
                    ['Dormant Sites', $summary['dormant_count']],
                    ['Not Reported Sites', $summary['nr_count']],
                    ['Total Labour', number_format($summary['total_labour'])],
                    ['Total Machinery', number_format($summary['total_machinery'])],
                    ['Thinly Manned Units', $summary['thinly_manned_count']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Rollup failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
