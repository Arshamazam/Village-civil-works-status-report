<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token']);
        $before = Setting::all()->pluck('value', 'key')->toArray();

        foreach ($inputs as $key => $val) {
            Setting::set($key, (string)$val);
        }

        AuditLog::log('updated_settings', null, $before, $inputs);

        return back()->with('success', 'System settings saved successfully.');
    }
}
