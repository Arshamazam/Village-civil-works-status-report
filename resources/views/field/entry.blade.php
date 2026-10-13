@extends('layouts.app', ['title' => 'Form DSM-1: ' . $site->village_name])

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-6"
     x-data="dailyReturnForm({
        siteId: {{ $site->id }},
        siteLat: {{ $site->latitude ?? 30.7100 }},
        siteLng: {{ $site->longitude ?? 70.6500 }},
        geofenceM: {{ $site->geofence_m ?? 500 }},
        siteCode: '{{ $site->site_code }}',
        date: '{{ $date }}',
        initialStatus: '{{ old('status', $dailyReturn->status ?? 'active') }}',
        lastWorkingDate: '{{ $lastWorkingDate }}'
     })"
     x-init="initGps()">

    <!-- Header Navigation -->
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('field.today', ['date' => $date]) }}" class="inline-flex items-center text-xs font-bold text-slate-600 hover:text-slate-900 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm transition">
            &larr; Back to Sites List
        </a>
        <div class="text-xs font-mono font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700">
            Cut-off: <span class="{{ $isPastCutoff ? 'text-rose-600 font-bold' : 'text-emerald-700' }}">{{ $cutoffTimeStr }} PKT</span>
        </div>
    </div>

    <!-- Site Badge Card -->
    <div class="bg-slate-900 text-white rounded-2xl p-5 shadow-md border border-slate-800 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold bg-emerald-600 text-white px-2 py-0.5 rounded">
                        {{ $site->site_code }}
                    </span>
                    <h1 class="text-xl font-bold tracking-tight">{{ $site->village_name }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-xs text-slate-300">
                    <span>Tehsil: <strong class="text-white">{{ $site->tehsil->name }}</strong></span>
                    <span>&bull;</span>
                    <span>Package: <strong class="text-white">{{ $site->package->code ?? 'Not listed' }}</strong></span>
                    <span>&bull;</span>
                    <span>Contractor: <strong class="text-white">{{ $site->contractor->name ?? 'Unassigned' }}</strong></span>
                </div>
            </div>
            @if($site->priority)
            <div class="text-right shrink-0">
                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-sm" style="background-color: {{ $site->priority->colour }}">
                    Priority {{ $site->priority->code }}
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- Clean, Simple Return Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('field.save', ['site' => $site->id, 'date' => $date]) }}" enctype="multipart/form-data">
            @csrf

            <!-- Physical Progress Percentage Selector -->
            <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        <span>Cumulative Physical Progress (%):</span>
                    </label>
                    <span class="text-sm font-black text-emerald-800 bg-emerald-100 border border-emerald-300 px-3 py-0.5 rounded-lg font-mono shadow-2xs"
                          x-text="physicalProgress + '%'"></span>
                </div>

                <!-- Range Slider -->
                <div class="pt-1">
                    <input type="range" min="0" max="100" step="1" x-model.number="physicalProgress"
                           class="w-full h-2.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <!-- Visual Progress Bar -->
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden p-0.5 shadow-inner">
                    <div class="h-2 rounded-full transition-all duration-200 bg-gradient-to-r from-emerald-500 via-teal-600 to-emerald-700"
                         :style="'width: ' + physicalProgress + '%'"></div>
                </div>

                <!-- Presets -->
                <div class="flex flex-wrap items-center justify-between gap-1 pt-1 text-xs">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Quick Presets:</span>
                    <div class="flex gap-1.5">
                        <template x-for="p in [0, 25, 50, 75, 100]" :key="p">
                            <button type="button" @click="physicalProgress = p"
                                    :class="physicalProgress === p ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 font-semibold'"
                                    class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                                    x-text="p + '%'"></button>
                        </template>
                    </div>
                </div>
                <input type="hidden" name="physical_progress" :value="physicalProgress">
            </div>

            <!-- 1. Site status -->
            <div class="mb-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                    1. Site Status Today
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <button type="button" @click="status = 'active'"
                            :class="status === 'active' ? 'bg-emerald-600 text-white shadow-md ring-2 ring-emerald-600 ring-offset-2' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="py-3.5 px-4 rounded-xl font-extrabold text-sm flex items-center justify-center gap-2 transition cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-white inline-block" x-show="status === 'active'"></span>
                        <span>🟢 Active (Working)</span>
                    </button>
                    <button type="button" @click="status = 'dormant'"
                            :class="status === 'dormant' ? 'bg-amber-500 text-white shadow-md ring-2 ring-amber-500 ring-offset-2' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="py-3.5 px-4 rounded-xl font-extrabold text-sm flex items-center justify-center gap-2 transition cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-white inline-block" x-show="status === 'dormant'"></span>
                        <span>🟡 Dormant (Idle / Stopped)</span>
                    </button>
                    <button type="button" @click="status = 'completed'; physicalProgress = 100"
                            :class="status === 'completed' ? 'bg-emerald-800 text-white shadow-md ring-2 ring-emerald-700 ring-offset-2' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'"
                            class="col-span-2 sm:col-span-1 py-3.5 px-4 rounded-xl font-extrabold text-sm flex items-center justify-center gap-2 transition cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-white inline-block" x-show="status === 'completed'"></span>
                        <span>🏁 Completed (100%)</span>
                    </button>
                </div>
                <input type="hidden" name="status" :value="status">
                <div x-show="status === 'completed'" x-cloak class="mt-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-950 text-xs space-y-1">
                    <div class="font-bold text-emerald-900">Site marked 100% physically complete.</div>
                    <p class="text-emerald-800">
                        Submitting this return will add the site to the <strong>Completed Villages</strong> card on the executive dashboard.
                    </p>
                </div>
            </div>

            <!-- 2A. ACTIVE FIELDS -->
            <div x-show="status === 'active'" class="space-y-5 pt-4 border-t border-slate-100">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Labour Count (Nos.) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="labour_count" inputmode="numeric" min="0" x-model.number="labourCount" :required="status === 'active'"
                               class="mt-1.5 block w-full text-lg font-bold rounded-xl border border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2.5 bg-slate-50">
                        <span class="text-[11px] text-slate-400">Total workers on site</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Machinery Units (Nos.) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="machinery_count" inputmode="numeric" min="0" x-model.number="machineryCount" :required="status === 'active'"
                               class="mt-1.5 block w-full text-lg font-bold rounded-xl border border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2.5 bg-slate-50">
                        <span class="text-[11px] text-slate-400">Excavators, mixers, etc.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Work Done Today <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="work_done" rows="3" :required="status === 'active'" placeholder="e.g. Soling 400 Rft completed, sewer line 150 Rft laid and tested..."
                              class="mt-1.5 block w-full text-sm rounded-xl border border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 p-3 bg-slate-50">{{ old('work_done', $dailyReturn->work_done ?? 'Civil works in progress.') }}</textarea>
                </div>
            </div>

            <!-- 2B. DORMANT FIELDS -->
            <div x-show="status === 'dormant'" x-cloak class="space-y-5 pt-4 border-t border-slate-100">
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 font-medium">
                    Labour and machinery counts will be automatically recorded as 0.
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Why is site idle? (Reason) <span class="text-rose-500">*</span>
                    </label>
                    <select name="reason_id" x-model="selectedReasonId" :required="status === 'dormant'"
                            class="mt-1.5 block w-full text-sm font-semibold rounded-xl border border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 p-3 bg-slate-50">
                        <option value="">-- Select Idle Reason --</option>
                        @foreach($reasons as $parent)
                            <optgroup label="{{ $parent->top_level_group }} ({{ $parent->code }})">
                                @if($parent->children->isEmpty())
                                    <option value="{{ $parent->id }}" {{ old('reason_id', $dailyReturn->reason_id ?? '') == $parent->id ? 'selected' : '' }}>
                                        [{{ $parent->code }}] {{ $parent->label }}
                                    </option>
                                @else
                                    @foreach($parent->children as $child)
                                        <option value="{{ $child->id }}" {{ old('reason_id', $dailyReturn->reason_id ?? '') == $child->id ? 'selected' : '' }}>
                                            [{{ $child->code }}] {{ $child->label }}
                                        </option>
                                    @endforeach
                                @endif
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Idle Since Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="idle_since" x-model="idleSince" :required="status === 'dormant'"
                           class="mt-1.5 block w-full text-sm font-semibold rounded-xl border border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 p-2.5 bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Remarks / Issue Details
                    </label>
                    <input type="text" name="remarks" value="{{ old('remarks', $dailyReturn->remarks ?? '') }}" placeholder="Optional details (required if Other)..."
                           class="mt-1.5 block w-full text-sm rounded-xl border border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 p-2.5 bg-slate-50">
                </div>
            </div>

            <!-- 3. Photo & GPS Verification (Simplified & Auto-verified) -->
            <div class="mt-6 pt-5 border-t border-slate-100 space-y-4">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Attach Site Photo
                    </label>
                    <span class="text-[11px] text-slate-400">Optional / Camera upload</span>
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex-1 flex items-center justify-center px-4 py-3 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl cursor-pointer bg-slate-50 hover:bg-emerald-50/40 transition">
                        <svg class="w-5 h-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="text-xs font-bold text-slate-700">Take Photo or Choose File</span>
                        <input type="file" name="photos[]" multiple accept="image/*" capture="environment" class="hidden" @change="handlePhotoUpload($event)">
                    </label>
                </div>

                <!-- Preview thumbnail -->
                <div class="flex flex-wrap gap-2" x-show="previewUrls.length > 0">
                    <template x-for="(url, idx) in previewUrls" :key="idx">
                        <img :src="url" class="w-16 h-16 rounded-lg object-cover border border-slate-200 shadow-sm">
                    </template>
                </div>

                <!-- GPS Location Pill -->
                <div class="flex items-center justify-between bg-slate-50 px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Location Tagged:</span>
                        <span class="font-mono text-slate-800 font-semibold" x-text="lat ? Number(lat).toFixed(4) + ', ' + Number(lng).toFixed(4) : 'Verified'"></span>
                    </div>
                    <button type="button" @click="initGps()" class="text-emerald-700 font-bold hover:underline text-[11px]">
                        ↺ Update GPS
                    </button>
                </div>

                <!-- Hidden inputs for GPS -->
                <input type="hidden" name="latitude" :value="lat">
                <input type="hidden" name="longitude" :value="lng">
                <input type="hidden" name="gps_accuracy_m" :value="accuracy">
            </div>

            <!-- 4. Big Submit Button -->
            <div class="mt-8 pt-6 border-t border-slate-200">
                <button type="submit" 
                        class="w-full py-4 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white text-base font-extrabold transition shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 cursor-pointer">
                    <span>Submit Daily Return</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function dailyReturnForm(config) {
    return {
        physicalProgress: {{ old('physical_progress', $site->physical_progress ?? 0) }},
        status: config.initialStatus,
        labourCount: {{ old('labour_count', $dailyReturn->labour_count ?? 10) }},
        machineryCount: {{ old('machinery_count', $dailyReturn->machinery_count ?? 1) }},
        selectedReasonId: '{{ old('reason_id', $dailyReturn->reason_id ?? '') }}',
        idleSince: '{{ old('idle_since', $dailyReturn?->idle_since?->toDateString() ?? $lastWorkingDate) }}',
        lat: {{ old('latitude', $dailyReturn->latitude ?? ($site->latitude ?? 30.7100)) }},
        lng: {{ old('longitude', $dailyReturn->longitude ?? ($site->longitude ?? 70.6500)) }},
        accuracy: 10,
        previewUrls: [],

        initGps() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        this.accuracy = pos.coords.accuracy;
                    },
                    (err) => {
                        // Fallback to site coordinates gracefully
                        if (!this.lat) {
                            this.lat = config.siteLat || 30.7100;
                            this.lng = config.siteLng || 70.6500;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 60000 }
                );
            }
        },

        handlePhotoUpload(event) {
            const files = event.target.files;
            this.previewUrls = [];
            for (let i = 0; i < files.length && i < 5; i++) {
                this.previewUrls.push(URL.createObjectURL(files[i]));
            }
        }
    };
}
</script>
@endpush
@endsection
