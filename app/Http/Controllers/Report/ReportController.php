<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Mail\DailyReportMail;
use App\Models\DailySummary;
use App\Models\ReportRecipient;
use App\Models\ReportRun;
use App\Models\Setting;
use App\Services\DashboardSummary;
use App\Services\ReportBuilder;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ReportController extends Controller
{
    /**
     * Reports hub: list runs, recipients, and generation tools (MAIL-6, RPT-2).
     */
    public function index(Request $request)
    {
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $availableDates = DailySummary::orderBy('report_date', 'desc')->pluck('report_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique();

        $selectedDate = $request->get('date', $availableDates->first() ?? Carbon::now($tz)->toDateString());

        $reportRuns = ReportRun::with('tehsil')
            ->latest('id')
            ->take(20)
            ->get();

        $recipients = ReportRecipient::with('tehsil')->orderBy('name')->get();

        return view('reports.index', compact(
            'availableDates',
            'selectedDate',
            'reportRuns',
            'recipients'
        ));
    }

    /**
     * View daily mobilization report in browser as HTML (RPT-2).
     */
    public function showHtml(string $date, RollupService $rollupService, DashboardSummary $dashboardSummary)
    {
        $summaryRecord = DailySummary::where(function ($q) use ($date) {
            $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
        })->first();

        if (! $summaryRecord) {
            $payload = $rollupService->execute($date);
        } else {
            $payload = $summaryRecord->payload;
        }

        ['priorityDisplay' => $priorityDisplay, 'dashStats' => $dashStats] = $dashboardSummary->build($payload, $date);

        return view('reports.daily', compact('payload', 'date', 'priorityDisplay', 'dashStats'));
    }

    /**
     * Download or stream the generated PDF report (RPT-1, RPT-2).
     */
    public function downloadPdf(string $date, Request $request, ReportBuilder $reportBuilder)
    {
        $tehsilId = $request->get('tehsil_id') ? (int) $request->get('tehsil_id') : null;
        $filters = array_filter([
            'phase' => $request->get('phase'),
            'tehsil' => $request->get('tehsil'),
            'contractor' => $request->get('contractor'),
            'village' => $request->get('village'),
        ]);
        $result = $reportBuilder->buildDailyPdf($date, $tehsilId, $filters);

        return response($result['content'])
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$result['filename']}\"");
    }

    /**
     * Trigger immediate generation and email dispatch (MAIL-6).
     */
    public function sendNow(Request $request)
    {
        $date = $request->input('date', Carbon::now()->toDateString());

        Artisan::call('report:daily', [
            'date' => $date,
            '--force' => true,
        ]);

        return redirect()->back()->with('success', "Daily report for {$date} queued for immediate delivery.");
    }

    /**
     * Resend report to a custom recipient email (MAIL-6).
     */
    public function resend(Request $request, ReportBuilder $reportBuilder, RollupService $rollupService)
    {
        $request->validate([
            'email' => 'required|email',
            'date' => 'required|date',
        ]);

        $date = $request->input('date');
        $email = $request->input('email');

        $summaryRecord = DailySummary::where(function ($q) use ($date) {
            $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
        })->first();

        $payload = $summaryRecord ? $summaryRecord->payload : $rollupService->execute($date);
        $pdfResult = $reportBuilder->buildDailyPdf($date);

        try {
            Mail::to($email)->queue(
                new DailyReportMail($payload, $date, $pdfResult['full_path'], $pdfResult['filename'])
            );
        } catch (TransportExceptionInterface $e) {
            report($e);

            return redirect()->back()->with('error', "Report for {$date} could not be sent to {$email}: the mail server rejected the connection. Check the mail settings (MAIL_USERNAME / MAIL_PASSWORD in .env).");
        }

        return redirect()->back()->with('success', config('queue.default') === 'sync'
            ? "Report for {$date} sent to {$email}."
            : "Report for {$date} successfully queued to {$email}.");
    }
}
