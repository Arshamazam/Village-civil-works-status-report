<?php

namespace App\Console\Commands;

use App\Mail\ReminderMail;
use App\Models\DailyReturn;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'returns:remind {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 12:00 PKT reminder emails to field officers with pending daily returns (MAIL-3)';

    public function handle(): int
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $this->argument('date') ?? Carbon::now($tz)->toDateString();
        $dateCarbon = Carbon::parse($date, $tz);

        // Check if Sunday or Holiday (MAIL-7)
        if ($dateCarbon->isSunday() || Holiday::where('date', $dateCarbon->toDateString())->exists()) {
            $this->info("Skipping reminders for non-reporting day ({$dateCarbon->format('l, Y-m-d')}).");
            return Command::SUCCESS;
        }

        $this->info("Checking pending returns for: {$date}");

        // Find submitted site IDs for today
        $submittedSiteIds = DailyReturn::where(function ($q) use ($date) {
            $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
        })->pluck('site_id');

        // Find all ongoing sites not yet submitted
        $pendingSites = Site::where('status', 'ongoing')
            ->whereNotIn('id', $submittedSiteIds)
            ->with('tehsil', 'package')
            ->get();

        if ($pendingSites->isEmpty()) {
            $this->info("All ongoing sites have been submitted! No reminders needed.");
            return Command::SUCCESS;
        }

        $this->info("Found {$pendingSites->count()} pending sites across all tehsils.");

        // Group pending sites by tehsil
        $pendingByTehsil = $pendingSites->groupBy('tehsil_id');

        // Find Field Officers and Tehsil in-charges assigned to these tehsils
        $officers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Field Officer', 'Tehsil In-charge']);
            })
            ->where('is_active', true)
            ->with('tehsils')
            ->get();

        $sentCount = 0;
        foreach ($officers as $officer) {
            $userTehsilIds = $officer->tehsils->pluck('id');
            
            // Gather pending sites for the tehsils assigned to this officer
            $officerPendingSites = $pendingSites->filter(fn($site) => $userTehsilIds->contains($site->tehsil_id));

            if ($officerPendingSites->isNotEmpty()) {
                try {
                    Mail::to($officer->email)->queue(
                        new ReminderMail($officerPendingSites, $date)
                    );
                    $sentCount++;
                } catch (\Throwable $e) {
                    Log::warning("Failed to queue reminder to {$officer->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("Dispatched reminder emails to {$sentCount} field officers.");
        return Command::SUCCESS;
    }
}
