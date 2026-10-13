@extends('layouts.app', ['title' => 'Site Profile: ' . $site->village_name])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ activePhoto: null }">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <nav class="flex items-center space-x-2 text-sm text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-800 transition">Dashboard</a>
            <span>/</span>
            <span class="text-slate-400">Sites</span>
            <span>/</span>
            <span class="text-slate-900 font-semibold">{{ $site->village_name }} ({{ $site->site_code }})</span>
        </nav>
        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-sm">
            &larr; Back to Dashboard
        </a>
    </div>

    <!-- Header / Profile Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $site->village_name }}</h1>
                <span class="font-mono text-sm px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                    {{ $site->site_code }}
                </span>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider
                    {{ $site->status === 'ongoing' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                    {{ ucfirst($site->status) }}
                </span>
                @if($site->priority)
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold text-white shadow-sm" style="background-color: {{ $site->priority->colour }}">
                    Priority {{ $site->priority->code }}
                </span>
                @endif
            </div>
            <p class="text-sm text-slate-500">
                Phase: <strong class="text-slate-700">{{ $site->phase->name ?? 'N/A' }}</strong> &bull;
                Tehsil: <strong class="text-slate-700">{{ $site->tehsil->name ?? 'N/A' }}</strong> &bull;
                Package: <strong class="text-slate-700 font-mono">{{ $site->package->code ?? 'Not listed' }}</strong> &bull;
                Contractor: <strong class="text-slate-700">{{ $site->contractor->name ?? 'Unassigned' }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs">
            <div>
                <span class="text-slate-400 block font-medium">GPS Geofence</span>
                @if($site->latitude && $site->longitude)
                <span class="font-mono text-slate-700 font-semibold">{{ number_format($site->latitude, 5) }}, {{ number_format($site->longitude, 5) }}</span>
                <span class="text-slate-500">(&plusmn;{{ $site->geofence_m ?? 500 }}m)</span>
                @else
                <span class="text-amber-600 font-medium">Not locked yet</span>
                @endif
            </div>
            <div class="h-8 w-px bg-slate-200"></div>
            <div>
                <span class="text-slate-400 block font-medium">Operational Status</span>
                <span class="font-semibold {{ $site->is_operational ? 'text-emerald-700' : 'text-slate-600' }}">
                    {{ $site->is_operational ? 'Fully Operational' : 'Under Execution' }}
                </span>
            </div>
            @if($site->completed_on)
            <div class="h-8 w-px bg-slate-200"></div>
            <div>
                <span class="text-slate-400 block font-medium">Completed Date</span>
                <span class="font-semibold text-slate-700">{{ $site->completed_on->format('d M Y') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- 30-Day Status Calendar Strip (DASH-9) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">30-Day Status Calendar Strip</h2>
                <p class="text-xs text-slate-500">Daily reporting history for the past 30 days</p>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> Active</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-400"></span> Watch (1-2d)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-orange-400"></span> Warn (3-4d)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-400"></span> Escalate (5-6d)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-600"></span> Critical (7+d)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-300"></span> Not Reported</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-200"></span> Holiday</span>
            </div>
        </div>

        <div class="grid grid-cols-10 sm:grid-cols-15 md:grid-cols-30 gap-1.5">
            @foreach($calendarStrip as $day)
                @php
                    $bgColor = 'bg-slate-200 text-slate-600';
                    if ($day['status'] === 'active') {
                        $bgColor = 'bg-emerald-500 text-white';
                    } elseif ($day['status'] === 'dormant') {
                        $bgColor = match($day['band']) {
                            'watch' => 'bg-amber-400 text-slate-900',
                            'warn' => 'bg-orange-400 text-white',
                            'escalate' => 'bg-red-400 text-white',
                            'critical' => 'bg-red-600 text-white font-bold',
                            default => 'bg-amber-400 text-slate-900',
                        };
                    } elseif ($day['status'] === 'holiday') {
                        $bgColor = 'bg-sky-200 text-sky-900';
                    } elseif ($day['status'] === 'not_reported') {
                        $bgColor = 'bg-slate-300 text-slate-700 border border-slate-400';
                    }
                @endphp
                <div class="group relative flex flex-col items-center justify-center p-2 rounded-lg {{ $bgColor }} cursor-pointer transition hover:scale-105 shadow-sm">
                    <span class="text-[10px] font-mono leading-tight">{{ \Carbon\Carbon::parse($day['date'])->format('d') }}</span>
                    <span class="text-[9px] font-mono uppercase leading-tight">{{ \Carbon\Carbon::parse($day['date'])->format('M') }}</span>

                    <!-- Tooltip -->
                    <div class="absolute bottom-full mb-2 hidden group-hover:block z-30 w-44 p-2 bg-slate-900 text-white text-[11px] rounded-lg shadow-xl pointer-events-none">
                        <div class="font-bold border-b border-slate-700 pb-1 mb-1">{{ \Carbon\Carbon::parse($day['date'])->format('D, d M Y') }}</div>
                        <div class="capitalize">Status: <strong>{{ str_replace('_', ' ', $day['status']) }}</strong></div>
                        @if($day['status'] === 'active')
                        <div>Labour: <strong>{{ $day['labour'] }}</strong></div>
                        <div>Machinery: <strong>{{ $day['machinery'] }}</strong></div>
                        @elseif($day['status'] === 'dormant')
                        <div>Band: <strong>{{ ucfirst($day['band']) }}</strong></div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Returns History & Evidence (DASH-9, DASH-10) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Daily Return History & Evidence</h2>
                <p class="text-xs text-slate-500">Submission records, site inspection photos, and GPS validation</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-600">
                Showing last {{ $returns->count() }} returns
            </span>
        </div>

        @if($returns->isEmpty())
        <div class="p-8 text-center text-slate-400 text-sm">
            No returns filed yet for this site.
        </div>
        @else
        <div class="space-y-4">
            @foreach($returns as $ret)
            <div class="border border-slate-200 rounded-xl p-4 transition hover:border-slate-300 bg-white">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase
                            {{ $ret->status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($ret->status === 'dormant' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                            {{ ucfirst($ret->status) }}
                        </span>
                        <div class="font-mono text-sm font-bold text-slate-800">
                            {{ \Carbon\Carbon::parse($ret->report_date)->format('D, d M Y') }}
                        </div>
                        <span class="text-xs text-slate-400">Visit at: {{ $ret->visit_time ?? 'N/A' }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                            By: {{ $ret->submitter->name ?? 'Unknown' }}
                        </span>
                        @if($ret->geofence_ok)
                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium flex items-center gap-1">
                            <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            Geofence OK ({{ round($ret->distance_from_site_m ?? 0) }}m)
                        </span>
                        @else
                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium">
                            Outside Geofence ({{ round($ret->distance_from_site_m ?? 0) }}m)
                        </span>
                        @endif

                        <span class="px-2 py-0.5 rounded font-medium
                            {{ $ret->approval_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($ret->approval_status === 'returned' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-600') }}">
                            {{ ucfirst($ret->approval_status) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 text-xs">
                    <div>
                        @if($ret->status === 'active')
                        <div class="flex items-center gap-4 text-slate-700 mb-2">
                            <span>Labour: <strong class="text-slate-900 font-bold text-sm">{{ $ret->labour_count }}</strong></span>
                            <span>Machinery: <strong class="text-slate-900 font-bold text-sm">{{ $ret->machinery_count }}</strong></span>
                        </div>
                        <div class="text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Work Done</span>
                            <p class="font-medium text-slate-800">{{ $ret->work_done ?? 'No notes entered.' }}</p>
                        </div>
                        @else
                        <div class="text-slate-600 bg-amber-50/60 p-2.5 rounded-lg border border-amber-100">
                            <span class="text-amber-800 block text-[10px] uppercase font-bold tracking-wider mb-0.5">Idle Reason</span>
                            <p class="font-semibold text-amber-900">{{ $ret->reason->label ?? 'Unstated reason' }}</p>
                            @if($ret->idle_since)
                            <p class="text-xs text-amber-700 mt-1">Idle since: {{ \Carbon\Carbon::parse($ret->idle_since)->format('d M Y') }}</p>
                            @endif
                            @if($ret->remarks)
                            <p class="text-xs text-slate-600 mt-1 italic">"{{ $ret->remarks }}"</p>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Photos -->
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider mb-2">
                            Attached Photos ({{ $ret->photos->count() }})
                        </span>
                        @if($ret->photos->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($ret->photos as $photo)
                            <div class="relative cursor-pointer group" @click="activePhoto = '{{ asset('storage/' . $photo->path) }}'">
                                <img src="{{ asset('storage/' . ($photo->thumb_path ?? $photo->path)) }}" 
                                     alt="Site Photo" 
                                     class="w-16 h-16 object-cover rounded-lg border border-slate-200 group-hover:opacity-80 transition shadow-sm">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-lg transition text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-slate-400 italic">No photos attached.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Lightbox Modal -->
    <div x-show="activePhoto" x-cloak class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" @click.self="activePhoto = null">
        <div class="relative max-w-4xl max-h-[90vh] bg-slate-950 rounded-xl overflow-hidden shadow-2xl">
            <button @click="activePhoto = null" class="absolute top-3 right-3 text-white bg-slate-800/80 hover:bg-slate-700 p-2 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img :src="activePhoto" alt="Full Preview" class="max-w-full max-h-[85vh] object-contain">
        </div>
    </div>
</div>
@endsection
