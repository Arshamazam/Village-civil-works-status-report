<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DailyReturn;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Tehsil;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $request->get('date', Carbon::now($tz)->toDateString());

        // Scope by assigned tehsils unless Super Admin or HQ Manager
        $assignedTehsilIds = ($user->hasRole('Super Admin') || $user->hasRole('HQ Manager'))
            ? Tehsil::pluck('id')->toArray()
            : $user->tehsils->pluck('id')->toArray();

        $selectedTehsilId = $request->get('tehsil_id', $assignedTehsilIds[0] ?? null);

        $tehsils = Tehsil::whereIn('id', $assignedTehsilIds)->get();

        $query = DailyReturn::with(['site.phase', 'site.package', 'site.priority', 'site.contractor', 'submitter', 'photos', 'reason'])
            ->where(function ($q) use ($date) {
                $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
            })
            ->whereHas('site', function ($q) use ($selectedTehsilId, $assignedTehsilIds) {
                if ($selectedTehsilId) {
                    $q->where('tehsil_id', $selectedTehsilId);
                } else {
                    $q->whereIn('tehsil_id', $assignedTehsilIds);
                }
            });

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        $returns = $query->latest('id')->get();

        // Identify unsubmitted ongoing sites for this tehsil
        $submittedSiteIds = $returns->pluck('site_id')->toArray();
        $unsubmittedSites = Site::where('status', 'ongoing')
            ->where(function ($q) use ($selectedTehsilId, $assignedTehsilIds) {
                if ($selectedTehsilId) {
                    $q->where('tehsil_id', $selectedTehsilId);
                } else {
                    $q->whereIn('tehsil_id', $assignedTehsilIds);
                }
            })
            ->whereNotIn('id', $submittedSiteIds)
            ->with(['phase', 'package', 'priority', 'contractor'])
            ->get();

        $pendingApprovalCount = $returns->where('approval_status', 'pending')->count();
        $approvedCount = $returns->where('approval_status', 'approved')->count();
        $returnedCount = $returns->where('approval_status', 'returned')->count();

        return view('review.index', compact(
            'returns',
            'unsubmittedSites',
            'tehsils',
            'selectedTehsilId',
            'date',
            'pendingApprovalCount',
            'approvedCount',
            'returnedCount'
        ));
    }

    public function approve(DailyReturn $return)
    {
        $before = $return->toArray();
        $return->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        AuditLog::log('approved_daily_return', $return, $before, $return->toArray());

        try {
            app(RollupService::class)->execute(Carbon::parse($return->report_date)->toDateString());
        } catch (\Throwable $e) {
            Log::warning('Rollup failed after return approval: '.$e->getMessage());
        }

        return back()->with('success', "Return for {$return->site->village_name} approved.");
    }

    public function returnItem(Request $request, DailyReturn $return)
    {
        $request->validate([
            'review_comment' => ['required', 'string', 'min:3'],
        ]);

        $before = $return->toArray();
        $return->update([
            'approval_status' => 'returned',
            'review_comment' => $request->review_comment,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        AuditLog::log('returned_daily_return_for_revision', $return, $before, $return->toArray());

        try {
            app(RollupService::class)->execute(Carbon::parse($return->report_date)->toDateString());
        } catch (\Throwable $e) {
            Log::warning('Rollup failed after return rejection: '.$e->getMessage());
        }

        return back()->with('success', "Return for {$return->site->village_name} returned for revision with comment.");
    }

    public function approveAll(Request $request)
    {
        $date = $request->date;
        $tehsilId = $request->tehsil_id;

        $returns = DailyReturn::where(function ($q) use ($date) {
                $q->where('report_date', $date)->orWhere('report_date', 'like', "$date%");
            })
            ->where('approval_status', 'pending')
            ->whereHas('site', function ($q) use ($tehsilId) {
                if ($tehsilId) $q->where('tehsil_id', $tehsilId);
            })
            ->get();

        $count = $returns->count();
        foreach ($returns as $ret) {
            $ret->update([
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        }

        AuditLog::log('bulk_approved_daily_returns', null, null, ['count' => $count, 'date' => $date]);

        if ($date) {
            try {
                app(RollupService::class)->execute(Carbon::parse($date)->toDateString());
            } catch (\Throwable $e) {
                Log::warning('Rollup failed after bulk approval: '.$e->getMessage());
            }
        }

        return back()->with('success', "Bulk approved {$count} daily returns.");
    }

    public function unlock(DailyReturn $return)
    {
        $before = $return->toArray();
        $return->update([
            'review_comment' => '[UNLOCKED]',
            'approval_status' => 'pending',
        ]);

        AuditLog::log('unlocked_daily_return_for_late_edit', $return, $before, $return->toArray());

        try {
            app(RollupService::class)->execute(Carbon::parse($return->report_date)->toDateString());
        } catch (\Throwable $e) {
            Log::warning('Rollup failed after return unlock: '.$e->getMessage());
        }

        return back()->with('success', "Return for {$return->site->village_name} has been unlocked for editing.");
    }
}
