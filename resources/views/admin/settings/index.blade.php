@extends('layouts.app', ['title' => 'System Settings'])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">System Cut-off & Configuration Settings</h1>
        <p class="text-sm text-slate-500">Configure operational deadlines, automated reporting schedules, and idle escalation thresholds.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 shadow-sm">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Field Return Cut-off Time (PKT)</label>
                    <input type="text" name="cutoff_time" value="{{ $settings['cutoff_time'] ?? '13:00' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Unsubmitted sites become Not Reported (NR) after this time.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Rollup Engine Computation Time (PKT)</label>
                    <input type="text" name="rollup_time" value="{{ $settings['rollup_time'] ?? '13:30' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Computes idle days, priority countdowns, and scorecards.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Automated PDF & Email Dispatch Time (PKT)</label>
                    <input type="text" name="report_time" value="{{ $settings['report_time'] ?? '14:00' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Sends 3-page mobilization report to management.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Field Reminder Email Time (PKT)</label>
                    <input type="text" name="reminder_time" value="{{ $settings['reminder_time'] ?? '12:00' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Alerts officers with pending site returns.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Thinly Manned Threshold (Labour Count)</label>
                    <input type="number" name="thin_manned_threshold" value="{{ $settings['thin_manned_threshold'] ?? '5' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                    <p class="text-[11px] text-slate-400 mt-1">Active sites with fewer laborers are flagged as thinly manned.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Default Geofence Radius (Meters)</label>
                    <input type="number" name="default_geofence_m" value="{{ $settings['default_geofence_m'] ?? '500' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Sender Email Address</label>
                    <input type="email" name="sender_email" value="{{ $settings['sender_email'] ?? 'reports@prmsc.punjab.gov.pk' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Sender Display Name</label>
                    <input type="text" name="sender_name" value="{{ $settings['sender_name'] ?? 'PRMSC Daily Reports' }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
            </div>

            <div class="pt-6 border-t border-slate-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-500 transition shadow">
                    Save System Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
