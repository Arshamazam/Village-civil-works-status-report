<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contractor;
use App\Models\Package;
use App\Models\Phase;
use App\Models\Priority;
use App\Models\Site;
use App\Models\Tehsil;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $query = Site::with(['phase', 'tehsil', 'package', 'priority', 'contractor'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('phase_id')) {
            $query->where('phase_id', $request->phase_id);
        }
        if ($request->filled('tehsil_id')) {
            $query->where('tehsil_id', $request->tehsil_id);
        }
        if ($request->filled('priority_id')) {
            $query->where('priority_id', $request->priority_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('site_code', 'like', "%{$search}%")
                  ->orWhere('village_name', 'like', "%{$search}%");
            });
        }

        $sites = $query->paginate(25)->withQueryString();

        $phases = Phase::all();
        $tehsils = Tehsil::all();
        $priorities = Priority::all();
        $contractors = Contractor::all();

        return view('admin.sites.index', compact('sites', 'phases', 'tehsils', 'priorities', 'contractors'));
    }

    public function create()
    {
        $phases = Phase::all();
        $tehsils = Tehsil::all();
        $packages = Package::all();
        $priorities = Priority::all();
        $contractors = Contractor::all();

        return view('admin.sites.form', compact('phases', 'tehsils', 'packages', 'priorities', 'contractors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_code' => ['required', 'string', 'unique:sites,site_code'],
            'village_name' => ['required', 'string'],
            'phase_id' => ['required', 'exists:phases,id'],
            'tehsil_id' => ['required', 'exists:tehsils,id'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'contractor_id' => ['nullable', 'exists:contractors,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_m' => ['nullable', 'integer', 'min:50', 'max:5000'],
            'status' => ['required', 'in:ongoing,completed,suspended'],
        ]);

        $validated['geofence_m'] = $validated['geofence_m'] ?? 500;
        if ($validated['status'] === 'completed') {
            $validated['completed_on'] = now()->toDateString();
            $validated['is_operational'] = true;
        }

        $site = Site::create($validated);

        AuditLog::log('created_site', $site, null, $site->toArray());

        return redirect()->route('admin.sites.index')->with('success', "Site {$site->site_code} created successfully.");
    }

    public function edit(Site $site)
    {
        $phases = Phase::all();
        $tehsils = Tehsil::all();
        $packages = Package::all();
        $priorities = Priority::all();
        $contractors = Contractor::all();

        return view('admin.sites.form', compact('site', 'phases', 'tehsils', 'packages', 'priorities', 'contractors'));
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'site_code' => ['required', 'string', 'unique:sites,site_code,' . $site->id],
            'village_name' => ['required', 'string'],
            'phase_id' => ['required', 'exists:phases,id'],
            'tehsil_id' => ['required', 'exists:tehsils,id'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'contractor_id' => ['nullable', 'exists:contractors,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_m' => ['nullable', 'integer', 'min:50', 'max:5000'],
            'status' => ['required', 'in:ongoing,completed,suspended'],
        ]);

        $before = $site->toArray();

        // MD-3: Marking completed
        if ($validated['status'] === 'completed' && $site->status !== 'completed') {
            $validated['completed_on'] = $request->completed_on ?? now()->toDateString();
            $validated['is_operational'] = true;
        }

        $site->update($validated);

        // MD-4: Audit log with changes
        AuditLog::log('updated_site', $site, $before, $site->fresh()->toArray());

        return redirect()->route('admin.sites.index')->with('success', "Site {$site->site_code} updated successfully.");
    }

    public function markCompleted(Request $request, Site $site)
    {
        $before = $site->toArray();
        $site->update([
            'status' => 'completed',
            'completed_on' => $request->completed_on ?? now()->toDateString(),
            'is_operational' => true,
        ]);

        AuditLog::log('marked_completed_site', $site, $before, $site->fresh()->toArray());

        return back()->with('success', "Site {$site->site_code} marked as completed and removed from future daily lists.");
    }

    public function updateGps(Request $request, Site $site)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'geofence_m' => ['nullable', 'integer', 'min:50'],
        ]);

        $before = $site->only(['latitude', 'longitude', 'geofence_m']);
        $site->update($validated);

        AuditLog::log('updated_gps_coordinates', $site, $before, $site->only(['latitude', 'longitude', 'geofence_m']));

        return back()->with('success', "GPS coordinates updated for {$site->site_code}.");
    }

    public function destroy(Site $site)
    {
        $before = $site->toArray();
        $site->delete();

        AuditLog::log('deleted_site', $site, $before, null);

        return redirect()->route('admin.sites.index')->with('success', "Site {$site->site_code} deleted successfully.");
    }
}
