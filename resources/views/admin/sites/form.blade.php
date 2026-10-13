@extends('layouts.app', ['title' => isset($site) ? 'Edit Site ' . $site->site_code : 'Add New Site'])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ isset($site) ? 'Edit Site ' . $site->site_code : 'Register New Civil Works Site' }}</h1>
            <p class="text-sm text-slate-500">Configure village details, priority deadlines, geofence, and contractor assignment.</p>
        </div>
        <a href="{{ route('admin.sites.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">&larr; Back to list</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ isset($site) ? route('admin.sites.update', $site) : route('admin.sites.store') }}" class="space-y-6">
            @csrf
            @if(isset($site))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Site Code (e.g. PLT-001 / PH1-001)</label>
                    <input type="text" name="site_code" value="{{ old('site_code', $site->site_code ?? '') }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-mono px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Village / Scheme Name</label>
                    <input type="text" name="village_name" value="{{ old('village_name', $site->village_name ?? '') }}" required
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Project Phase</label>
                    <select name="phase_id" required class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                        @foreach($phases as $phase)
                            <option value="{{ $phase->id }}" {{ old('phase_id', $site->phase_id ?? '') == $phase->id ? 'selected' : '' }}>
                                {{ $phase->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Tehsil</label>
                    <select name="tehsil_id" required class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                        @foreach($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ old('tehsil_id', $site->tehsil_id ?? '') == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Contractor</label>
                    <select name="contractor_id" class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                        <option value="">-- No contractor assigned --</option>
                        @foreach($contractors as $contractor)
                            <option value="{{ $contractor->id }}" {{ old('contractor_id', $site->contractor_id ?? '') == $contractor->id ? 'selected' : '' }}>
                                {{ $contractor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Completion Priority</label>
                    <select name="priority_id" class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                        <option value="">-- No priority deadline --</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->id }}" {{ old('priority_id', $site->priority_id ?? '') == $priority->id ? 'selected' : '' }}>
                                {{ $priority->name }} (Target: {{ $priority->deadline?->format('d M Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $site->latitude ?? '') }}" placeholder="e.g. 30.712345"
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-mono px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $site->longitude ?? '') }}" placeholder="e.g. 70.654321"
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-mono px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Geofence Radius (meters)</label>
                    <input type="number" name="geofence_m" value="{{ old('geofence_m', $site->geofence_m ?? 500) }}" required min="50" max="5000"
                           class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                    <p class="text-[11px] text-slate-400 mt-1">Default is 500 meters. Submissions outside trigger an audit flag.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</label>
                    <select name="status" required class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm px-3 py-2">
                        <option value="ongoing" {{ old('status', $site->status ?? 'ongoing') === 'ongoing' ? 'selected' : '' }}>Ongoing (included in daily report)</option>
                        <option value="completed" {{ old('status', $site->status ?? '') === 'completed' ? 'selected' : '' }}>Completed (removed from daily list)</option>
                        <option value="suspended" {{ old('status', $site->status ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-200 flex justify-end gap-3">
                <a href="{{ route('admin.sites.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-500 transition shadow">
                    {{ isset($site) ? 'Update Site Record' : 'Save Site Record' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
