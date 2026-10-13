<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest('id');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }
        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.audit_logs.index', compact('logs'));
    }
}
