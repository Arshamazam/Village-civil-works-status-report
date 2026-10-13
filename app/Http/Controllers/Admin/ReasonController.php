<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reason;
use Illuminate\Http\Request;

class ReasonController extends Controller
{
    public function index()
    {
        $topReasons = Reason::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('admin.reasons.index', compact('topReasons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'label' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:reasons,id'],
            'top_level_group' => ['required', 'string'],
            'requires_remark' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $reason = Reason::create($validated);
        AuditLog::log('created_reason', $reason, null, $reason->toArray());

        return back()->with('success', 'Reason created.');
    }

    public function update(Request $request, Reason $reason)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'label' => ['required', 'string'],
            'top_level_group' => ['required', 'string'],
            'requires_remark' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $before = $reason->toArray();
        $reason->update($validated);
        AuditLog::log('updated_reason', $reason, $before, $reason->toArray());

        return back()->with('success', 'Reason updated.');
    }

    public function destroy(Reason $reason)
    {
        $before = $reason->toArray();
        $reason->delete();
        AuditLog::log('deleted_reason', $reason, $before, null);

        return back()->with('success', 'Reason deleted.');
    }
}
