<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contractor;
use Illuminate\Http\Request;

class ContractorController extends Controller
{
    public function index()
    {
        $contractors = Contractor::withCount('sites')->get();
        return view('admin.contractors.index', compact('contractors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'contact_person' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $contractor = Contractor::create($validated);
        AuditLog::log('created_contractor', $contractor, null, $contractor->toArray());

        return back()->with('success', 'Contractor created.');
    }

    public function update(Request $request, Contractor $contractor)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'contact_person' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        $before = $contractor->toArray();
        $contractor->update($validated);
        AuditLog::log('updated_contractor', $contractor, $before, $contractor->toArray());

        return back()->with('success', 'Contractor updated.');
    }

    public function destroy(Contractor $contractor)
    {
        $before = $contractor->toArray();
        $contractor->delete();
        AuditLog::log('deleted_contractor', $contractor, $before, null);

        return back()->with('success', 'Contractor deleted.');
    }
}
