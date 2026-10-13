<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Phase;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    public function index()
    {
        $phases = Phase::withCount('sites')->get();
        return view('admin.phases.index', compact('phases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'code' => ['required', 'string', 'unique:phases,code'],
            'is_active' => ['boolean'],
        ]);

        $phase = Phase::create($validated);
        AuditLog::log('created_phase', $phase, null, $phase->toArray());

        return back()->with('success', 'Phase added successfully.');
    }

    public function update(Request $request, Phase $phase)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'code' => ['required', 'string', 'unique:phases,code,' . $phase->id],
            'is_active' => ['boolean'],
        ]);

        $before = $phase->toArray();
        $phase->update($validated);
        AuditLog::log('updated_phase', $phase, $before, $phase->toArray());

        return back()->with('success', 'Phase updated successfully.');
    }

    public function destroy(Phase $phase)
    {
        $before = $phase->toArray();
        $phase->delete();
        AuditLog::log('deleted_phase', $phase, $before, null);

        return back()->with('success', 'Phase deleted.');
    }
}
