<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMobileReturnRequest;
use App\Models\AuditLog;
use App\Models\DailyReturn;
use App\Models\Reason;
use App\Models\ReturnPhoto;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use App\Services\GeoService;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileFieldController extends Controller
{
    /**
     * Everything the app needs to work offline: assigned villages, idle reasons and reporting settings.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user();
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $today = Carbon::now($tz)->toDateString();

        $sites = Site::where('status', 'ongoing')
            ->when(! $this->seesAllTehsils($user), fn ($q) => $q->whereIn('tehsil_id', $user->tehsils()->pluck('tehsils.id')))
            ->with(['phase', 'priority', 'tehsil', 'package', 'contractor'])
            ->orderBy('village_name')
            ->get();

        $latestReturns = DailyReturn::whereIn('site_id', $sites->pluck('id'))
            ->orderBy('report_date')
            ->get(['site_id', 'report_date', 'status', 'labour_count', 'machinery_count', 'approval_status'])
            ->keyBy('site_id');

        $reasons = Reason::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Reason $group) => [
                'id' => $group->id,
                'code' => $group->code,
                'name' => $group->name,
                'group' => $group->top_level_group,
                'requires_remark' => (bool) $group->requires_remark,
                'children' => $group->children->map(fn (Reason $reason) => [
                    'id' => $reason->id,
                    'code' => $reason->code,
                    'name' => $reason->name,
                    'requires_remark' => (bool) $reason->requires_remark,
                ])->values(),
            ])->values();

        return response()->json([
            'server_date' => $today,
            'server_time' => Carbon::now($tz)->toIso8601String(),
            'settings' => [
                'timezone' => $tz,
                'cutoff_time' => Setting::get('cutoff_time', '13:00'),
                'thin_manned_threshold' => (int) Setting::get('thin_manned_threshold', 5),
                'max_photos' => 5,
            ],
            'sites' => $sites->map(function (Site $site) use ($latestReturns) {
                $latest = $latestReturns->get($site->id);

                return [
                    'id' => $site->id,
                    'code' => $site->site_code,
                    'village' => $site->village_name,
                    'category' => $site->dsmCategory(),
                    'phase' => $site->dsmCategory() === 'phase-0' ? 'phase-0' : 'pilot',
                    'tehsil_id' => $site->tehsil_id,
                    'tehsil' => $site->tehsil->name ?? '',
                    'package' => $site->package->code ?? null,
                    'contractor' => $site->contractor->name ?? null,
                    'settlements' => (int) ($site->settlements ?? 1),
                    'physical_progress' => (int) ($site->physical_progress ?? 0),
                    'latitude' => $site->latitude !== null ? (float) $site->latitude : null,
                    'longitude' => $site->longitude !== null ? (float) $site->longitude : null,
                    'geofence_m' => (int) $site->geofence_m,
                    'is_completed' => $latest?->status === 'completed',
                    'last_return' => $latest ? [
                        'date' => $latest->report_date->toDateString(),
                        'status' => $latest->status,
                        'labour' => (int) $latest->labour_count,
                        'machinery' => (int) $latest->machinery_count,
                        'approval_status' => $latest->approval_status,
                    ] : null,
                ];
            })->values(),
            'reasons' => $reasons,
        ]);
    }

    /**
     * Receive one return queued on the device. Re-sending the same client_uuid updates the same record.
     */
    public function storeReturn(StoreMobileReturnRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $site = Site::findOrFail($validated['site_id']);
        $date = $validated['report_date'];

        if (! $this->seesAllTehsils($user) && ! $user->tehsils->contains($site->tehsil_id)) {
            return response()->json(['message' => 'You are not assigned to this village\'s tehsil.'], 403);
        }

        $existing = DailyReturn::where('client_uuid', $validated['client_uuid'])->first()
            ?? DailyReturn::where('site_id', $site->id)->whereDate('report_date', $date)->first();

        if ($existing && $existing->approval_status === 'approved' && ! $user->hasRole('Super Admin')) {
            return response()->json(['message' => 'This return has already been approved and cannot be edited.'], 409);
        }

        // Cut-off is judged on when the entry was captured on the device, so officers without signal are not penalised
        $capturedAt = isset($validated['captured_at'])
            ? Carbon::parse($validated['captured_at'])->setTimezone($tz)->min(Carbon::now($tz))
            : Carbon::now($tz);
        $cutoffTimeStr = Setting::get('cutoff_time', '13:00');
        $isLate = $capturedAt->greaterThan(Carbon::parse("{$date} {$cutoffTimeStr}", $tz));
        $unlocked = $existing && $existing->review_comment === '[UNLOCKED]';

        if ($isLate && ! $unlocked && ! $user->hasRole('Super Admin')) {
            return response()->json([
                'message' => "Cut-off time ({$cutoffTimeStr} PKT) had passed when this return was recorded. Ask your Tehsil In-charge or Admin to unlock it, then sync again.",
            ], 422);
        }

        $deviceLat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $deviceLng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $distM = ($deviceLat !== null && $deviceLng !== null)
            ? GeoService::distanceInMeters($site->latitude, $site->longitude, $deviceLat, $deviceLng)
            : null;
        $isActive = $validated['status'] === 'active';

        $data = [
            'site_id' => $site->id,
            'report_date' => $date,
            'submitted_by' => $user->id,
            'visit_time' => $capturedAt->format('H:i:s'),
            'latitude' => $deviceLat ?? $site->latitude,
            'longitude' => $deviceLng ?? $site->longitude,
            'gps_accuracy_m' => $validated['gps_accuracy_m'] ?? null,
            'distance_from_site_m' => $distM,
            // Recorded away from the site (or without a GPS fix) is accepted but flagged for review
            'geofence_ok' => $distM !== null && $distM <= $site->geofence_m,
            'status' => $validated['status'],
            'labour_count' => $isActive ? (int) $validated['labour_count'] : 0,
            'machinery_count' => $isActive ? (int) $validated['machinery_count'] : 0,
            'machinery_types' => $isActive ? ($validated['machinery_types'] ?? []) : null,
            'work_done' => $isActive ? $validated['work_done'] : null,
            'reason_id' => $isActive ? null : $validated['reason_id'],
            'idle_since' => $isActive ? null : $validated['idle_since'],
            'remarks' => $validated['remarks'] ?? null,
            'approval_status' => 'pending',
            'submitted_at' => $capturedAt,
            'is_late' => $isLate,
            'client_uuid' => $validated['client_uuid'],
        ];

        $before = $existing?->toArray();
        $return = $existing ? tap($existing)->update($data) : DailyReturn::create($data);

        foreach ($request->file('photos', []) as $photoFile) {
            $path = $photoFile->storeAs(
                'public/returns/'.Carbon::parse($date)->format('Y/m'),
                Str::random(32).'.'.$photoFile->getClientOriginalExtension()
            );

            ReturnPhoto::create([
                'daily_return_id' => $return->id,
                'path' => $path,
                'size_kb' => (int) round($photoFile->getSize() / 1024),
                'latitude' => $deviceLat,
                'longitude' => $deviceLng,
                'taken_at' => $capturedAt,
            ]);
        }

        if (isset($validated['physical_progress'])) {
            $site->update(['physical_progress' => (int) $validated['physical_progress']]);
        }

        AuditLog::log($existing ? 'updated_daily_return_mobile' : 'submitted_daily_return_mobile', $return, $before, $return->toArray());

        try {
            app(RollupService::class)->execute($date);
        } catch (\Throwable $e) {
            Log::warning('Rollup execution failed after mobile return sync: '.$e->getMessage());
        }

        return response()->json([
            'id' => $return->id,
            'client_uuid' => $return->client_uuid,
            'site_id' => $site->id,
            'report_date' => $date,
            'geofence_ok' => (bool) $return->geofence_ok,
            'is_late' => (bool) $return->is_late,
            'photos' => $return->photos()->count(),
        ], $existing ? 200 : 201);
    }

    private function seesAllTehsils(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'HQ Manager']);
    }
}
