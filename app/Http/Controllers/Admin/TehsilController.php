<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tehsil;
use Illuminate\Http\Request;

class TehsilController extends Controller
{
    public function index()
    {
        $tehsils = Tehsil::withCount(['sites', 'users'])->get();
        return view('admin.tehsils.index', compact('tehsils'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'district' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
        ]);

        $tehsil = Tehsil::create($validated);
        AuditLog::log('created_tehsil', $tehsil, null, $tehsil->toArray());

        return back()->with('success', 'Tehsil created.');
    }

    public function update(Request $request, Tehsil $tehsil)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'district' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
        ]);

        $before = $tehsil->toArray();
        $tehsil->update($validated);
        AuditLog::log('updated_tehsil', $tehsil, $before, $tehsil->toArray());

        return back()->with('success', 'Tehsil updated.');
    }

    public function destroy(Tehsil $tehsil)
    {
        $before = $tehsil->toArray();
        $tehsil->delete();
        AuditLog::log('deleted_tehsil', $tehsil, $before, null);

        return back()->with('success', 'Tehsil deleted.');
    }
}
