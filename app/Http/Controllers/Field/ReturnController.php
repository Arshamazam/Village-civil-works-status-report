<?php

namespace App\Http\Controllers\Field;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DailyReturn;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\Reason;
use App\Models\ReturnPhoto;
use App\Models\Setting;
use App\Models\Site;
use App\Services\GeoService;
use App\Services\RollupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReturnController extends Controller
{
    /**
     * Today's return site list for Field Officer (FORM-1, FORM-14).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $today = Carbon::now($tz)->toDateString();
        $date = $request->get('date', $today);

        // Scope by assigned tehsils for field staff, or all for Super Admin / HQ Manager testing
        $tehsilIds = $user->hasRole(['Super Admin', 'HQ Manager']) ? [] : $user->tehsils->pluck('id')->toArray();

        // Retrieve Category Definitions
        $phase0 = Phase::where('code', 'phase-0')->orWhere('name', 'Phase-0')->first();
        $priA = Priority::where('code', 'A')->orWhere('code', '1')->first();
        $priB = Priority::where('code', 'B')->orWhere('code', '2')->first();
        $priC = Priority::where('code', 'C')->orWhere('code', '3')->first();
        $priD = Priority::where('code', 'D')->orWhere('code', '4')->first();

        $query = Site::where('status', 'ongoing')
            ->with([
                'phase', 'tehsil', 'package', 'priority', 'contractor',
                'dailyReturns' => fn ($q) => $q->whereDate('report_date', $date),
            ]);

        if (! empty($tehsilIds)) {
            $query->whereIn('tehsil_id', $tehsilIds);
        }

        $allSites = $query->orderBy('village_name')->get();

        // Villages whose latest earlier return was "completed" stay completed and need no daily return
        $completedEarlierIds = DailyReturn::whereIn('site_id', $allSites->pluck('id'))
            ->whereDate('report_date', '<', $date)
            ->orderBy('report_date')
            ->get(['site_id', 'status'])
            ->keyBy('site_id')
            ->filter(fn ($previous) => $previous->status === 'completed')
            ->keys();
        $isCompletedVillage = fn (Site $site) => $site->dailyReturns->isEmpty()
            ? $completedEarlierIds->contains($site->id)
            : $site->dailyReturns->first()->status === 'completed';

        // 5 Categories: Phase 0, Priority A, Priority B, Priority C, Remaining Villages
        $categories = [
            'phase-0' => [
                'key' => 'phase-0',
                'title' => 'Phase 0',
                'badge' => 'Phase 0',
                'theme' => 'emerald',
                'color' => '#15803d',
            ],
            'priority-a' => [
                'key' => 'priority-a',
                'title' => 'Priority A',
                'badge' => 'Priority A',
                'theme' => 'rose',
                'color' => '#dc2626',
            ],
            'priority-b' => [
                'key' => 'priority-b',
                'title' => 'Priority B',
                'badge' => 'Priority B',
                'theme' => 'amber',
                'color' => '#d97706',
            ],
            'priority-c' => [
                'key' => 'priority-c',
                'title' => 'Priority C',
                'badge' => 'Priority C',
                'theme' => 'blue',
                'color' => '#2563eb',
            ],
            'remaining' => [
                'key' => 'remaining',
                'title' => 'Remaining Villages',
                'badge' => 'Remaining Pilot',
                'theme' => 'slate',
                'color' => '#475569',
            ],
        ];

        // Group sites into category -> tehsil -> villages
        $hierarchy = [
            'phase-0' => [],
            'priority-a' => [],
            'priority-b' => [],
            'priority-c' => [],
            'remaining' => [],
        ];

        foreach ($allSites as $site) {
            $catKey = null;
            if ($phase0 && $site->phase_id == $phase0->id) {
                $catKey = 'phase-0';
            } elseif ($priA && $site->priority_id == $priA->id) {
                $catKey = 'priority-a';
            } elseif ($priB && $site->priority_id == $priB->id) {
                $catKey = 'priority-b';
            } elseif ($priC && $site->priority_id == $priC->id) {
                $catKey = 'priority-c';
            } elseif ($priD && $site->priority_id == $priD->id) {
                $catKey = 'remaining';
            }

            if (! $catKey) {
                continue;
            }

            $tId = $site->tehsil_id;
            $tName = $site->tehsil->name ?? ('Tehsil '.$tId);

            if (! isset($hierarchy[$catKey][$tId])) {
                $hierarchy[$catKey][$tId] = [
                    'id' => $tId,
                    'name' => $tName,
                    'villages' => [],
                ];
            }

            $ret = $site->dailyReturns->first();

            $hierarchy[$catKey][$tId]['villages'][] = [
                'id' => $site->id,
                'name' => $site->village_name,
                'code' => $site->site_code,
                'settlements' => $site->settlements ?? 1,
                'physical_progress' => (int) ($site->physical_progress ?? 0),
                'tehsil_id' => $tId,
                'tehsil_name' => $tName,
                'category_key' => $catKey,
                'category_title' => $categories[$catKey]['title'],
                'contractor' => $site->contractor->name ?? 'Unassigned',
                'package' => $site->package->code ?? '—',
                'lat' => $site->latitude ?? 30.71,
                'lng' => $site->longitude ?? 70.65,
                'is_submitted' => ! is_null($ret) || $isCompletedVillage($site),
                'is_completed' => $isCompletedVillage($site),
                'status' => $ret ? $ret->status : ($isCompletedVillage($site) ? 'completed' : 'active'),
                'labour' => $ret ? $ret->labour_count : 0,
                'machinery' => $ret ? $ret->machinery_count : 0,
                'machinery_types' => $ret ? ($ret->machinery_types ?? []) : [],
                'work_done' => $ret ? ($ret->work_done ?? '') : '',
                'reason_id' => $ret ? $ret->reason_id : '',
                'idle_since' => $ret ? ($ret->idle_since ? Carbon::parse($ret->idle_since)->toDateString() : '') : '',
                'remarks' => $ret ? ($ret->remarks ?? '') : '',
            ];
        }

        // Convert keyed tehsils into arrays for front-end
        $cleanTree = [];
        foreach ($hierarchy as $k => $tList) {
            // Sort tehsils alphabetically
            usort($tList, fn ($a, $b) => strcmp($a['name'], $b['name']));
            $cleanTree[$k] = $tList;
        }

        // Summary counts
        $totalOngoing = $allSites->count();
        $submittedCount = $allSites->filter(fn ($s) => $s->dailyReturns->isNotEmpty() || $isCompletedVillage($s))->count();
        $pendingCount = $totalOngoing - $submittedCount;

        $cutoffTimeStr = Setting::get('cutoff_time', '13:00');
        $cutoffTime = Carbon::parse("{$date} {$cutoffTimeStr}", $tz);
        $isPastCutoff = Carbon::now($tz)->greaterThan($cutoffTime);

        // Pre-select initial category, tehsil, and village (nothing pre-selected until the officer picks a phase)
        $initialCat = (string) $request->get('cat', '');
        if ($initialCat !== '' && empty($cleanTree[$initialCat])) {
            foreach (['phase-0', 'priority-a', 'priority-b', 'priority-c', 'remaining'] as $ck) {
                if (! empty($cleanTree[$ck])) {
                    $initialCat = $ck;
                    break;
                }
            }
        }

        $initialTehsilId = (int) $request->get('tehsil_id', 0);
        $initialSiteId = (int) $request->get('site_id', 0);

        if (! $initialTehsilId && ! empty($cleanTree[$initialCat])) {
            $initialTehsilId = $cleanTree[$initialCat][0]['id'];
        }

        if (! $initialSiteId && ! empty($cleanTree[$initialCat])) {
            $foundTeh = collect($cleanTree[$initialCat])->firstWhere('id', $initialTehsilId);
            if ($foundTeh && ! empty($foundTeh['villages'])) {
                // Pick first unsubmitted, or first
                $unsub = collect($foundTeh['villages'])->firstWhere('is_submitted', false);
                $initialSiteId = $unsub ? $unsub['id'] : $foundTeh['villages'][0]['id'];
            }
        }

        $reasons = Reason::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('field.today', compact(
            'categories',
            'cleanTree',
            'initialCat',
            'initialTehsilId',
            'initialSiteId',
            'reasons',
            'date',
            'totalOngoing',
            'submittedCount',
            'pendingCount',
            'cutoffTimeStr',
            'isPastCutoff'
        ));
    }

    /**
     * Entry screen for a specific site on a given date (FORM-2).
     */
    public function showForm(Site $site, ?string $date = null)
    {
        $user = Auth::user();
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $date ?? Carbon::now($tz)->toDateString();

        // Check tehsil authorization
        if (! $user->hasRole(['Super Admin', 'HQ Manager']) && ! $user->tehsils->contains($site->tehsil_id)) {
            abort(403, 'Unauthorized. You are not assigned to this site’s tehsil.');
        }

        $dailyReturn = DailyReturn::with(['photos', 'reason'])->where('site_id', $site->id)
            ->whereDate('report_date', $date)
            ->first();

        // Last working day prefill (FORM-5)
        $lastWorkingDate = DailyReturn::where('site_id', $site->id)
            ->where('report_date', '<', $date)
            ->where('status', 'active')
            ->latest('report_date')
            ->value('report_date') ?? Carbon::parse($date)->subDay()->toDateString();

        $reasons = Reason::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $cutoffTimeStr = Setting::get('cutoff_time', '13:00');
        $cutoffTime = Carbon::parse("{$date} {$cutoffTimeStr}", $tz);
        $isPastCutoff = Carbon::now($tz)->greaterThan($cutoffTime);

        return view('field.entry', compact(
            'site',
            'date',
            'dailyReturn',
            'lastWorkingDate',
            'reasons',
            'cutoffTimeStr',
            'isPastCutoff'
        ));
    }

    /**
     * Submit or update a daily return (FORM-3 to FORM-15).
     */
    public function saveReturn(Request $request, Site $site, ?string $date = null)
    {
        $user = Auth::user();
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $date = $date ?? Carbon::now($tz)->toDateString();

        if (! $user->hasRole(['Super Admin', 'HQ Manager']) && ! $user->tehsils->contains($site->tehsil_id)) {
            abort(403, 'Unauthorized for this tehsil.');
        }

        $dailyReturn = DailyReturn::where('site_id', $site->id)->whereDate('report_date', $date)->first();

        // Check if locked by approval (FORM-13)
        if ($dailyReturn && $dailyReturn->approval_status === 'approved' && ! $user->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'status' => ['This return has already been approved and cannot be edited.'],
            ]);
        }

        // Check cut-off time (FORM-12)
        $cutoffTimeStr = Setting::get('cutoff_time', '13:00');
        $cutoffTime = Carbon::parse("{$date} {$cutoffTimeStr}", $tz);
        $isPastCutoff = Carbon::now($tz)->greaterThan($cutoffTime);
        $isLate = false;

        if ($isPastCutoff) {
            $unlocked = $dailyReturn && $dailyReturn->review_comment === '[UNLOCKED]';
            if (! $unlocked && ! $user->hasRole('Super Admin')) {
                throw ValidationException::withMessages([
                    'status' => ["Cut-off time ({$cutoffTimeStr} PKT) has passed. Please contact your Tehsil In-charge or Admin to unlock this submission."],
                ]);
            }
            $isLate = true;
        }

        // Validation based on status
        $status = $request->input('status');
        $rules = [
            'status' => ['required', 'in:active,dormant,completed'],
            'physical_progress' => ['nullable', 'integer', 'between:0,100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy_m' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string'],
        ];

        if ($status === 'active') {
            $rules['labour_count'] = ['required', 'integer', 'min:0'];
            $rules['machinery_count'] = ['required', 'integer', 'min:0'];
            $rules['work_done'] = ['required', 'string', 'min:5'];
            $rules['machinery_types'] = ['nullable', 'array'];
        } elseif ($status === 'dormant') {
            $rules['reason_id'] = ['required', 'exists:reasons,id'];
            $rules['idle_since'] = ['required', 'date'];
        } elseif ($status === 'completed') {
            $rules['completed_on'] = ['nullable', 'date'];
            $rules['remarks'] = ['nullable', 'string'];
        }

        if (! $request->has('latitude') || empty($request->latitude)) {
            $request->merge(['latitude' => $site->latitude ?: 30.7100]);
        }
        if (! $request->has('longitude') || empty($request->longitude)) {
            $request->merge(['longitude' => $site->longitude ?: 70.6500]);
        }

        // Photos validation (optional, max 5 per return)
        $rules['photos'] = ['nullable', 'array', 'max:5'];
        $rules['photos.*'] = ['nullable', 'image', 'max:10240'];

        $validated = $request->validate($rules);

        // Active rule: Labour and machinery cannot both be 0 (FORM-4)
        if ($status === 'active') {
            if ((int) $validated['labour_count'] === 0 && (int) $validated['machinery_count'] === 0) {
                throw ValidationException::withMessages([
                    'labour_count' => ['An Active site must have at least 1 labourer or 1 machinery unit deployed.'],
                ]);
            }
        }

        // Dormant remarks check if reason requires remark
        if ($status === 'dormant') {
            $reason = Reason::find($validated['reason_id']);
            if ($reason && $reason->requires_remark && empty($validated['remarks'])) {
                throw ValidationException::withMessages([
                    'remarks' => ['Remarks are mandatory for the selected idle reason.'],
                ]);
            }
        }

        // Distance and Geofence (FORM-8)
        $deviceLat = (float) $validated['latitude'];
        $deviceLng = (float) $validated['longitude'];
        $distM = GeoService::distanceInMeters($site->latitude, $site->longitude, $deviceLat, $deviceLng);
        $geofenceOk = ($distM === null) || ($distM <= $site->geofence_m);

        if (! $geofenceOk && empty($validated['remarks']) && $status !== 'completed') {
            throw ValidationException::withMessages([
                'remarks' => ["GPS reading is outside the site's {$site->geofence_m}m geofence (".round($distM).'m away). A remark explaining why you are outside the site is required.'],
            ]);
        }

        $data = [
            'site_id' => $site->id,
            'report_date' => $date,
            'submitted_by' => $user->id,
            'visit_time' => Carbon::now($tz)->format('H:i:s'),
            'latitude' => $deviceLat,
            'longitude' => $deviceLng,
            'gps_accuracy_m' => $request->gps_accuracy_m ?? 10.0,
            'distance_from_site_m' => $distM,
            'geofence_ok' => $geofenceOk,
            'status' => $status,
            'labour_count' => $status === 'active' ? (int) $validated['labour_count'] : 0,
            'machinery_count' => $status === 'active' ? (int) $validated['machinery_count'] : 0,
            'machinery_types' => $status === 'active' ? ($validated['machinery_types'] ?? []) : null,
            'work_done' => $status === 'active' ? $validated['work_done'] : ($status === 'completed' ? 'Civil works 100% completed on site.' : null),
            'reason_id' => $status === 'dormant' ? $validated['reason_id'] : null,
            'idle_since' => $status === 'dormant' ? $validated['idle_since'] : null,
            'remarks' => $validated['remarks'] ?? ($status === 'completed' ? 'Civil works 100% completed on site.' : null),
            'approval_status' => 'pending',
            'submitted_at' => now(),
            'is_late' => $isLate,
            'client_uuid' => $request->client_uuid ?? (string) Str::uuid(),
        ];

        $before = $dailyReturn ? $dailyReturn->toArray() : null;

        if ($dailyReturn) {
            $dailyReturn->update($data);
            $return = $dailyReturn;
        } else {
            $return = DailyReturn::create(array_merge([
                'site_id' => $site->id,
                'report_date' => Carbon::parse($date)->toDateString(),
            ], $data));
        }

        // Handle uploaded photos (FORM-9, FORM-10)
        if ($request->hasFile('photos')) {
            $year = Carbon::parse($date)->format('Y');
            $month = Carbon::parse($date)->format('m');
            $dir = "public/returns/{$year}/{$month}";

            foreach ($request->file('photos') as $photoFile) {
                $filename = Str::random(32).'.'.$photoFile->getClientOriginalExtension();
                $path = $photoFile->storeAs($dir, $filename);
                $sizeKb = round($photoFile->getSize() / 1024);

                ReturnPhoto::create([
                    'daily_return_id' => $return->id,
                    'path' => $path,
                    'size_kb' => $sizeKb,
                    'latitude' => $deviceLat,
                    'longitude' => $deviceLng,
                    'taken_at' => now(),
                ]);
            }
        }

        // Update site physical progress percentage if submitted
        if ($status === 'completed') {
            $site->update([
                'physical_progress' => 100,
                'completed_on' => $validated['completed_on'] ?? $date,
            ]);
        } elseif ($request->has('physical_progress') && $request->input('physical_progress') !== null) {
            $site->update([
                'physical_progress' => min(100, max(0, (int) $request->input('physical_progress'))),
            ]);
        }

        AuditLog::log($dailyReturn ? 'updated_daily_return' : 'submitted_daily_return', $return, $before, $return->toArray());

        // Automatic Rollup re-run so dashboard reflects submitted data immediately (ROLL-3)
        try {
            app(RollupService::class)->execute($date);
        } catch (\Throwable $e) {
            Log::warning('Rollup execution failed after return save: '.$e->getMessage());
        }

        // Return to the same phase / priority and tehsil so the officer can continue with the next pending village
        return redirect()->route('field.today', array_filter([
            'date' => $date,
            'cat' => $site->dsmCategory(),
            'tehsil_id' => $site->tehsil_id,
        ]))
            ->with('success', "✓ Daily return for {$site->village_name} ({$site->site_code}) submitted successfully.");
    }

    /**
     * Offline batch sync API endpoint (FORM-11).
     */
    public function batchSync(Request $request)
    {
        $items = $request->input('returns', []);
        $tz = Setting::get('timezone', 'Asia/Karachi');
        $user = Auth::user();

        $synced = [];
        $errors = [];

        foreach ($items as $item) {
            $clientUuid = $item['client_uuid'] ?? null;
            $siteId = $item['site_id'] ?? null;
            $date = $item['report_date'] ?? Carbon::now($tz)->toDateString();

            if (! $siteId) {
                $errors[] = ['uuid' => $clientUuid, 'error' => 'Missing site_id'];

                continue;
            }

            $site = Site::find($siteId);
            if (! $site) {
                $errors[] = ['uuid' => $clientUuid, 'error' => 'Site not found'];

                continue;
            }

            $deviceLat = (float) ($item['latitude'] ?? 0);
            $deviceLng = (float) ($item['longitude'] ?? 0);
            $distM = GeoService::distanceInMeters($site->latitude, $site->longitude, $deviceLat, $deviceLng);
            $geofenceOk = ($distM === null) || ($distM <= $site->geofence_m);

            $data = [
                'site_id' => $site->id,
                'report_date' => $date,
                'submitted_by' => $user->id,
                'visit_time' => $item['visit_time'] ?? Carbon::now($tz)->format('H:i:s'),
                'latitude' => $deviceLat,
                'longitude' => $deviceLng,
                'gps_accuracy_m' => $item['gps_accuracy_m'] ?? 10.0,
                'distance_from_site_m' => $distM,
                'geofence_ok' => $geofenceOk,
                'status' => $item['status'] ?? 'active',
                'labour_count' => (int) ($item['labour_count'] ?? 0),
                'machinery_count' => (int) ($item['machinery_count'] ?? 0),
                'machinery_types' => $item['machinery_types'] ?? [],
                'work_done' => $item['work_done'] ?? null,
                'reason_id' => $item['reason_id'] ?? null,
                'idle_since' => $item['idle_since'] ?? null,
                'remarks' => $item['remarks'] ?? null,
                'approval_status' => 'pending',
                'submitted_at' => now(),
            ];

            // Match by client_uuid first if provided, then by (site_id, report_date)
            $existing = null;
            if ($clientUuid) {
                $existing = DailyReturn::where('client_uuid', $clientUuid)->first();
            }
            if (! $existing) {
                $existing = DailyReturn::where('site_id', $site->id)->whereDate('report_date', $date)->first();
            }

            if ($existing) {
                $existing->update($data);
                $ret = $existing;
            } else {
                $data['client_uuid'] = $clientUuid ?: (string) Str::uuid();
                $ret = DailyReturn::create($data);
            }

            $synced[] = ['uuid' => $ret->client_uuid, 'site_id' => $site->id, 'id' => $ret->id];
        }

        if (! empty($synced)) {
            try {
                $today = Carbon::now($tz)->toDateString();
                app(RollupService::class)->execute($today);
            } catch (\Throwable $e) {
            }
        }

        return response()->json([
            'success' => true,
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }
}
