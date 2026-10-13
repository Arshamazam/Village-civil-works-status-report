<?php

namespace App\Console\Commands;

use App\Models\DailyReturn;
use App\Models\Setting;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkNotReported extends Command
{
    protected $signature = 'returns:mark-not-reported {date? : Date to evaluate (YYYY-MM-DD)}';
    protected $description = 'Audit ongoing sites without daily returns after 13:00 PKT cut-off';

    public function handle(): int
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $this->argument('date') ?? Carbon::now($tz)->toDateString();

        $ongoingSites = Site::where('status', 'ongoing')->get();
        $submittedSiteIds = DailyReturn::where('report_date', $date)->pluck('site_id')->toArray();

        $pendingSites = $ongoingSites->filter(fn($s) => !in_array($s->id, $submittedSiteIds, true));

        $this->info("Date: {$date} (PKT)");
        $this->info("Total ongoing sites: {$ongoingSites->count()}");
        $this->info("Submitted returns: " . count($submittedSiteIds));
        $this->warn("Not reported (NR) sites: {$pendingSites->count()}");

        return Command::SUCCESS;
    }
}
