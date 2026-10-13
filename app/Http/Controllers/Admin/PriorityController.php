<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Priority;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function index()
    {
        $priorities = Priority::withCount('sites')->orderBy('sort_order')->get();
        return view('admin.priorities.index', compact('priorities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'unique:priorities,code'],
            'name' => ['required', 'string'],
            'target_days' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer'],
            'colour' => ['required', 'string'],
        ]);

        $priority = Priority::create($validated);
        AuditLog::log('created_priority', $priority, null, $priority->toArray());

        return back()->with('success', 'Priority added successfully.');
    }

    public function update(Request $request, Priority $priority)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'unique:priorities,code,' . $priority->id],
            'name' => ['required', 'string'],
            'target_days' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date'],
            'start_date' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer'],
            'colour' => ['required', 'string'],
        ]);

        $before = $priority->toArray();
        $priority->update($validated);
        AuditLog::log('updated_priority', $priority, $before, $priority->toArray());

        return back()->with('success', 'Priority updated successfully.');
    }

    public function destroy(Priority $priority)
    {
        $before = $priority->toArray();
        $priority->delete();
        AuditLog::log('deleted_priority', $priority, $before, null);

        return back()->with('success', 'Priority deleted.');
    }
}
