<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDailyEntryRequest;
use App\Models\AuditLog;
use App\Models\DailyReturn;
use App\Models\Reason;
use App\Models\Setting;
use App\Models\Site;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Lets Super Admin / HQ Manager correct (or enter on behalf of an officer) a village's daily return from the dashboard.
 */
class EntryCorrectionController extends Controller
{
    public function edit(Site $site, string $date): View
    {
        $date = $this->normaliseDate($date);
        $site->load(['tehsil', 'package', 'contractor', 'priority', 'phase']);
        $entry = $this->entryFor($site, $date)?->load(['submitter', 'approver', 'photos', 'reason']);

        $reasons = Reason::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('dashboard.entry_edit', compact('site', 'date', 'entry', 'reasons'));
    }

    public function update(UpdateDailyEntryRequest $request, Site $site, string $date): RedirectResponse
    {
        $date = $this->normaliseDate($date);
        $validated = $request->validated();
        $user = $request->user();
        $entry = $this->entryFor($site, $date);
        $before = $entry?->toArray();
        $status = $validated['status'];
        $isActive = $status === 'active';

        $data = [
            'status' => $status,
            'labour_count' => $isActive ? (int) $validated['labour_count'] : 0,
            'machinery_count' => $isActive ? (int) $validated['machinery_count'] : 0,
            'machinery_types' => $isActive ? ($validated['machinery_types'] ?? []) : null,
            'work_done' => $isActive ? $validated['work_done'] : null,
            'reason_id' => $status === 'dormant' ? $validated['reason_id'] : null,
            'idle_since' => $status === 'dormant' ? $validated['idle_since'] : null,
            'remarks' => $validated['remarks'] ?? null,
        ];

        if ($entry) {
            $entry->update($data);
        } else {
            // Return reported to HQ by phone/WhatsApp: entered on the officer's behalf and approved by the editor
            $entry = DailyReturn::create($data + [
                'site_id' => $site->id,
                'report_date' => $date,
                'submitted_by' => $user->id,
                'visit_time' => Carbon::now(Setting::get('timezone', 'Asia/Karachi'))->format('H:i:s'),
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'geofence_ok' => false,
                'approval_status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'submitted_at' => now(),
                'client_uuid' => (string) Str::uuid(),
            ]);
        }

        if (isset($validated['physical_progress'])) {
            $site->update(['physical_progress' => (int) $validated['physical_progress']]);
        }

        AuditLog::log(
            $before ? 'corrected_daily_return' : 'entered_daily_return_for_officer',
            $entry,
            $before,
            $entry->fresh()->toArray() + ['correction_note' => $validated['correction_note'] ?? null]
        );

        try {
            app(RollupService::class)->execute($date);
        } catch (\Throwable $e) {
            Log::warning('Rollup execution failed after entry correction: '.$e->getMessage());
        }

        return redirect()->route('dashboard', ['date' => $date])
            ->with('success', "Daily entry for {$site->village_name} on ".Carbon::parse($date)->format('d M Y').' was '.($before ? 'updated' : 'added').'.');
    }

    private function entryFor(Site $site, string $date): ?DailyReturn
    {
        return DailyReturn::where('site_id', $site->id)->whereDate('report_date', $date)->first();
    }

    private function normaliseDate(string $date): string
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1, 404);

        return Carbon::parse($date)->toDateString();
    }
}
