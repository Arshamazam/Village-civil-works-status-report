@extends('layouts.app', ['title' => 'CEO Mobilization Dashboard'])

@section('content')
<div class="max-w-[1440px] mx-auto px-3 sm:px-6 py-4 space-y-4"
     x-data="dashboardApp({
        payload: {{ json_encode($payload) }},
        mapSites: {{ json_encode($mapSites) }},
        priorityCards: {{ json_encode($priorityDisplay) }},
        detailsPdfUrl: '{{ route('dashboard.details-pdf') }}',
        detailsExcelUrl: '{{ route('dashboard.details-excel') }}',
        reportDate: '{{ $date }}',
        canEditEntries: {{ auth()->user()?->hasRole(['Super Admin', 'HQ Manager']) ? 'true' : 'false' }},
        entryEditUrl: '{{ url('/entries') }}',
        activeFilters: {{ json_encode(array_filter(['phase' => $selectedPhase, 'tehsil' => $selectedTehsil, 'contractor' => $selectedContractor, 'village' => $selectedVillage])) }}
     })"
     x-init="initMap();">

    <!-- 1. OFFICIAL REPORT HEADER BANNER (Exact Dark Forest Green Theme from PDF) -->
    <div class="bg-[#14532d] text-white rounded-xl p-4 sm:p-5 shadow-sm border border-[#166534]">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <div class="text-[11px] font-black uppercase tracking-widest text-[#86efac]">
                    PUNJAB RURAL MUNICIPAL SERVICES COMPANY
                </div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight mt-0.5 text-white">
                    PRSWSSP — DAILY SITE MOBILIZATION REPORT
                </h1>
                <div class="text-[10px] text-emerald-200 mt-1 uppercase tracking-wider font-semibold">
                    DAILY SITE MOBILIZATION DASHBOARD &bull; Ongoing Sites Live Monitoring
                </div>
            </div>

            <!-- Date Selector & Meta from PDF -->
            <div class="flex flex-wrap items-center gap-2.5 text-right">
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-1.5">
                    <label for="date-input" class="text-xs text-emerald-200 font-semibold">Report date:</label>
                    <input type="date" id="date-input" name="date" value="{{ $date }}" onchange="this.form.submit()"
                           class="text-xs rounded-lg border-emerald-700 bg-[#0f3d21] text-white px-2.5 py-1.5 font-mono shadow-xs focus:ring-emerald-400">
                </form>

                <div class="bg-[#0f3d21] px-3.5 py-1.5 rounded-lg border border-emerald-800 text-right">
                    <div class="text-xs font-bold text-white">
                        Report date: {{ \Carbon\Carbon::parse($payload['report_date'] ?? $date)->format('D, d M Y') }}
                    </div>
                    <div class="text-[10px] text-emerald-300">
                        Day {{ $payload['day_of_tracking'] ?? 1 }} of tracking &bull; {{ $payload['total_ongoing'] ?? 133 }} villages under execution &bull; {{ $payload['tendering_count'] ?? 18 }} in tendering &bull; Source: daily field sheet
                    </div>
                </div>

                <a href="{{ route('dashboard.export-sheet', array_filter(['date' => $date, 'phase' => $selectedPhase, 'tehsil' => $selectedTehsil, 'contractor' => $selectedContractor, 'village' => $selectedVillage])) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-[#166534] hover:bg-[#15803d] text-white shadow-xs transition">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download Sheet
                </a>

                <a href="{{ route('dashboard.export-no-progress', array_filter(['date' => $date, 'phase' => $selectedPhase, 'tehsil' => $selectedTehsil, 'contractor' => $selectedContractor, 'village' => $selectedVillage])) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-[#991b1b] hover:bg-[#b91c1c] text-white shadow-xs transition">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    No-Progress Sites
                </a>

                <a href="{{ route('reports.pdf', array_filter(['date' => $date, 'phase' => $selectedPhase, 'tehsil' => $selectedTehsil, 'contractor' => $selectedContractor, 'village' => $selectedVillage])) }}" target="_blank"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-[#15803d] hover:bg-[#16a34a] text-white shadow-xs transition">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Print PDF
                </a>
            </div>
        </div>
    </div>

    <!-- TOP FILTER TOOLBAR (PHASE, TEHSIL, VILLAGE, CONTRACTOR, DATE) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4"
         x-data="filterToolbar({
             tehsilVillagesMap: {{ json_encode($tehsilVillagesMap) }},
             allVillages: {{ json_encode($distinctVillages) }},
             initialTehsil: '{{ $selectedTehsil ?? '' }}',
             initialVillage: '{{ $selectedVillage ?? '' }}'
         })">
        <form method="GET" action="{{ route('dashboard') }}" id="dashboard-filter-form">
            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2.5 flex-1">
                    <!-- Phase / Priority Filter -->
                    <div>
                        <label for="filter-phase" class="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1">
                            Phase / Priority
                        </label>
                        <select id="filter-phase" name="phase"
                                class="w-full text-xs rounded-lg border-slate-300 bg-slate-50 text-slate-900 px-2.5 py-2 shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">All Phases &amp; Priorities</option>
                            @foreach($phaseOptions as $k => $label)
                                <option value="{{ $k }}" {{ ($selectedPhase === $k) ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tehsil Filter -->
                    <div>
                        <label for="filter-tehsil" class="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1">
                            Tehsil
                        </label>
                        <select id="filter-tehsil" name="tehsil"
                                x-model="selectedTehsil"
                                @change="onTehsilChange()"
                                class="w-full text-xs rounded-lg border-slate-300 bg-slate-50 text-slate-900 px-2.5 py-2 shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">All Tehsils ({{ count($distinctTehsils) }})</option>
                            @foreach($distinctTehsils as $t)
                                <option value="{{ $t }}">
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Village Filter (Filtered dynamically by chosen Tehsil) -->
                    <div>
                        <label for="filter-village" class="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1 flex items-center justify-between">
                            <span>Village</span>
                            <span x-show="selectedTehsil" class="text-emerald-700 font-bold lowercase text-[9px]" x-text="'(' + availableVillages.length + ' in ' + selectedTehsil + ')'"></span>
                        </label>
                        <select id="filter-village" name="village"
                                x-model="selectedVillage"
                                class="w-full text-xs rounded-lg border-slate-300 bg-slate-50 text-slate-900 px-2.5 py-2 shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="" x-text="selectedTehsil ? 'All ' + selectedTehsil + ' Villages (' + availableVillages.length + ')' : 'All Villages (' + allVillages.length + ')'"></option>
                            <template x-for="v in availableVillages" :key="v">
                                <option :value="v" :selected="v === selectedVillage" x-text="v"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Contractor Filter -->
                    <div>
                        <label for="filter-contractor" class="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1">
                            Contractor
                        </label>
                        <select id="filter-contractor" name="contractor"
                                class="w-full text-xs rounded-lg border-slate-300 bg-slate-50 text-slate-900 px-2.5 py-2 shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="">All Contractors ({{ count($distinctContractors) }})</option>
                            @foreach($distinctContractors as $c)
                                <option value="{{ $c }}" {{ (isset($selectedContractor) && strcasecmp($selectedContractor, $c) === 0) ? 'selected' : '' }}>
                                    {{ $c }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label for="filter-date" class="block text-[10px] font-black uppercase tracking-wider text-slate-700 mb-1">
                            Report Date
                        </label>
                        <input type="date" id="filter-date" name="date" value="{{ $date }}"
                               class="w-full text-xs rounded-lg border-slate-300 bg-slate-50 text-slate-900 px-2.5 py-2 font-mono shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                    </div>
                </div>

                <!-- Submit & Clear Actions -->
                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-bold bg-[#14532d] hover:bg-[#166534] text-white shadow-2xs transition cursor-pointer">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Apply Filter
                    </button>

                    @if(!empty($hasFilters))
                        <a href="{{ route('dashboard', ['date' => $date]) }}"
                           class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition">
                            <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Reset
                        </a>
                    @endif
                </div>
            </div>

            <!-- Active Filter Badges Bar -->
            @if(!empty($hasFilters))
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Filtered By:</span>
                    @if($selectedPhase)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-purple-50 text-purple-800 border border-purple-200 font-semibold text-[11px]">
                            <span>Phase: <strong>{{ $phaseOptions[$selectedPhase] ?? $selectedPhase }}</strong></span>
                            <a href="{{ route('dashboard', array_filter(['date' => $date, 'tehsil' => $selectedTehsil, 'contractor' => $selectedContractor, 'village' => $selectedVillage])) }}" class="text-purple-700 hover:text-purple-900 ml-1 font-bold">&times;</a>
                        </span>
                    @endif
                    @if($selectedTehsil)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold text-[11px]">
                            <span>Tehsil: <strong>{{ $selectedTehsil }}</strong></span>
                            <a href="{{ route('dashboard', array_filter(['date' => $date, 'phase' => $selectedPhase, 'contractor' => $selectedContractor, 'village' => $selectedVillage])) }}" class="text-emerald-700 hover:text-emerald-900 ml-1 font-bold">&times;</a>
                        </span>
                    @endif
                    @if($selectedVillage)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-semibold text-[11px]">
                            <span>Village: <strong>{{ $selectedVillage }}</strong></span>
                            <a href="{{ route('dashboard', array_filter(['date' => $date, 'phase' => $selectedPhase, 'tehsil' => $selectedTehsil, 'contractor' => $selectedContractor])) }}" class="text-amber-700 hover:text-amber-900 ml-1 font-bold">&times;</a>
                        </span>
                    @endif
                    @if($selectedContractor)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-blue-50 text-blue-800 border border-blue-200 font-semibold text-[11px]">
                            <span>Contractor: <strong>{{ $selectedContractor }}</strong></span>
                            <a href="{{ route('dashboard', array_filter(['date' => $date, 'phase' => $selectedPhase, 'tehsil' => $selectedTehsil, 'village' => $selectedVillage])) }}" class="text-blue-700 hover:text-blue-900 ml-1 font-bold">&times;</a>
                        </span>
                    @endif
                    <span class="text-[11px] text-slate-500 font-medium ml-auto">
                        Showing <strong>{{ $payload['total_ongoing'] ?? 0 }}</strong> matching ongoing sites
                    </span>
                </div>
            @endif
        </form>
    </div>

    <!-- 2. SECTION: VILLAGES UNDER EXECUTION (5 KPI CARDS - EXACT PDF REPLICA) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between mb-2.5 pb-2 border-b border-blue-600">
            <h2 class="text-xs font-black uppercase tracking-wider text-[#1e3a8a]">
                VILLAGES UNDER EXECUTION
            </h2>
            <span class="text-[11px] text-slate-500 font-medium">
                Scope: <strong>{{ $payload['total_ongoing'] ?? 133 }} villages</strong> under execution (after COC) &bull; <strong>{{ $payload['tendering_count'] ?? 18 }} villages</strong> in tendering phase
            </span>
        </div>

        @php
            $dashAvgProgress = (int)($payload['avg_physical_progress'] ?? (collect($payload['register_rows'] ?? [])->count() > 0 ? round(collect($payload['register_rows'] ?? [])->avg('physical_progress') ?? 0) : 0));
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-2.5 text-center">
            <!-- Card 1: TOTAL VILLAGES (AFTER COC) -->
            <div @click="openDetail('total_villages')"
                 class="bg-[#f8fafc] p-3 rounded-lg border border-slate-200 flex flex-col justify-between cursor-pointer hover:border-blue-400 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL VILLAGES (AFTER COC)</div>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 my-0.5">{{ $payload['total_ongoing'] ?? 133 }}</div>
                <div class="text-[10px] text-slate-500 font-medium">Pilot {{ $dashStats['pilot_count'] }} &bull; Phase-0 {{ $dashStats['phase0_count'] }}</div>
                <div class="text-[9px] text-slate-400 group-hover:text-blue-600 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-slate-200/60 transition">
                    <span>Click for details</span>
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </div>
            </div>

            <!-- Card 3: COMPLETED VILLAGES -->
            <div @click="openDetail('completed_villages')"
                 class="bg-[#f0fdf4] p-3 rounded-lg border border-emerald-300 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:border-emerald-500 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">COMPLETED VILLAGES</span>
                    <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-emerald-200 text-emerald-900">{{ $dashStats['completed_pct'] }}%</span>
                </div>
                <div class="text-2xl sm:text-3xl font-black text-emerald-700 my-0.5">{{ $dashStats['completed_count'] }}</div>
                <div class="text-[10px] text-emerald-700 font-medium">At 100% progress {{ $dashStats['full_progress_count'] }} &bull; COC pending</div>
                <div class="text-[9px] text-emerald-600/70 group-hover:text-emerald-800 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-emerald-200 transition">
                    <span>Click for details</span>
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </div>
            </div>

            <!-- Card 4: REMAINING VILLAGES -->
            <div @click="openDetail('remaining_villages')"
                 class="bg-[#f0f9ff] p-3 rounded-lg border border-sky-300 flex flex-col justify-between cursor-pointer hover:border-sky-500 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="text-[10px] font-bold uppercase tracking-wider text-sky-800">REMAINING VILLAGES</div>
                <div class="text-2xl sm:text-3xl font-black text-sky-700 my-0.5">{{ $dashStats['remaining_count'] }}</div>
                <div class="text-[10px] text-sky-700 font-medium">{{ $dashStats['remaining_pct'] }}% of scope still to finish</div>
                <div class="text-[9px] text-sky-600/70 group-hover:text-sky-800 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-sky-200 transition">
                    <span>Click for details</span>
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </div>
            </div>

            <!-- Card 4: TOTAL LABOUR -->
            <div @click="openDetail('total_labour')"
                 class="bg-[#f8fafc] p-3 rounded-lg border border-slate-200 flex flex-col justify-between cursor-pointer hover:border-emerald-500 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL LABOUR</div>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 my-0.5">{{ number_format($payload['total_labour'] ?? 692) }}</div>
                <div class="text-[10px] text-slate-500 font-medium">
                    on {{ $payload['active_count'] ?? 58 }} active sites &bull; {{ $payload['thinly_manned_count'] ?? 19 }} thin (&lt;5)
                </div>
                <div class="text-[9px] text-slate-400 group-hover:text-emerald-700 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-slate-200/60 transition">
                    <span>Click for details</span>
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </div>
            </div>

            <!-- Card 5: TOTAL MACHINERY -->
            <div @click="openDetail('total_machinery')"
                 class="bg-[#f8fafc] p-3 rounded-lg border border-slate-200 flex flex-col justify-between cursor-pointer hover:border-slate-500 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">TOTAL MACHINERY</div>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 my-0.5">{{ $payload['total_machinery'] ?? 139 }}</div>
                <div class="text-[10px] text-slate-500 font-medium">
                    avg {{ ($payload['active_count'] ?? 0) > 0 ? round(($payload['total_machinery'] ?? 139) / $payload['active_count'], 1) : 2.4 }} units per active site
                </div>
                <div class="text-[9px] text-slate-400 group-hover:text-slate-700 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-slate-200/60 transition">
                    <span>Click for details</span>
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </div>
            </div>

            <!-- Card 6: PHYSICAL PROGRESS -->
            <div @click="openDetail('physical_progress')"
                 class="bg-[#f0fdf4] p-3 rounded-lg border border-emerald-300 flex flex-col justify-between cursor-pointer hover:border-emerald-500 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">PHYSICAL PROGRESS</span>
                    <span class="text-[9px] font-extrabold px-1.5 py-0.2 rounded bg-emerald-200 text-emerald-900">AVG</span>
                </div>
                <div class="text-2xl sm:text-3xl font-black text-emerald-700 my-0.5">
                    {{ $dashAvgProgress }}%
                </div>
                <div class="w-full bg-emerald-100 rounded-full h-1.5 my-1 overflow-hidden">
                    <div class="bg-emerald-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $dashAvgProgress }}%"></div>
                </div>
                <div class="text-[10px] text-emerald-700 font-medium truncate">
                    Across {{ $payload['total_ongoing'] ?? 0 }} sites
                </div>
                <div class="text-[9px] text-emerald-600/70 group-hover:text-emerald-800 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-emerald-200 transition">
                    <span>Click for details</span>
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. PRESERVED: BY COMPLETION PRIORITY (Requested to keep + Styled like PDF) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-slate-100 gap-2">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">
                POSITION BY COMPLETION PRIORITY
            </h2>
            <div class="flex items-center gap-2.5 text-[10px] text-slate-600 font-medium">
                <span class="text-slate-500 font-semibold">Bar:</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-[#14532d]"></span> dark green completed</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-[#22c55e]"></span> green active</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-[#ef4444]"></span> red dormant</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-[#f59e0b]"></span> amber not reported</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">

            @foreach($priorityDisplay as $p)
            <div @click="openDetail('priority_card', '{{ $p['name'] }}')"
                 class="rounded-lg border border-slate-200 bg-white p-3 flex flex-col justify-between hover:border-emerald-600 hover:shadow-md transition transform hover:-translate-y-0.5 cursor-pointer group">
                <div>
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-black text-slate-900 uppercase">{{ $p['name'] }}</span>
                            <span class="text-[10px] text-slate-500 font-bold ml-1">{{ $p['sites'] }} sites</span>
                        </div>
                    </div>

                    <div class="mt-1 text-[10px] text-slate-500">
                        Target: <span class="font-semibold text-slate-700">{{ $p['target'] }}</span>
                    </div>

                    <!-- Badge -->
                    <div class="my-1.5">
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider text-white {{ $p['badge_bg'] }}">
                            {{ $p['badge'] }}
                        </span>
                    </div>

                    <!-- Breakdown Bar -->
                    <div class="w-full bg-slate-100 rounded-sm h-2.5 my-2 overflow-hidden flex">
                        @php $totalBarSites = max(1, $p['sites']); @endphp
                        @if($p['done'] > 0)
                            <div class="bg-[#14532d] h-2.5" style="width: {{ round(($p['done'] / $totalBarSites) * 100) }}%"></div>
                        @endif
                        @if($p['active'] > 0)
                            <div class="bg-[#22c55e] h-2.5" style="width: {{ round(($p['active'] / $totalBarSites) * 100) }}%"></div>
                        @endif
                        @if($p['dormant'] > 0)
                            <div class="bg-[#ef4444] h-2.5" style="width: {{ round(($p['dormant'] / $totalBarSites) * 100) }}%"></div>
                        @endif
                        @if($p['nr'] > 0)
                            <div class="bg-[#f59e0b] h-2.5" style="width: {{ round(($p['nr'] / $totalBarSites) * 100) }}%"></div>
                        @endif
                    </div>

                    <!-- Breakdown text -->
                    <div class="text-[10px] font-semibold text-slate-700 space-x-1 my-1">
                        @if($p['done'] > 0)
                            <span class="text-emerald-950 font-bold">Done {{ $p['done'] }}</span> &bull;
                        @endif
                        <span class="text-emerald-700 font-bold">Act {{ $p['active'] }}</span> &bull;
                        <span class="text-rose-700 font-bold">Dorm {{ $p['dormant'] }}</span>
                        @if($p['nr'] > 0)
                            &bull; <span class="text-amber-700 font-bold">NR {{ $p['nr'] }}</span>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="pt-2 border-t border-slate-100 text-[10px] text-slate-600 flex justify-between font-medium">
                        <span>Labour <strong>{{ $p['labour'] }}</strong></span>
                        <span>Machinery <strong>{{ $p['machinery'] }}</strong></span>
                    </div>
                    <div class="text-[9px] text-slate-400 group-hover:text-emerald-700 font-semibold flex items-center justify-center gap-1 mt-1 pt-1 border-t border-slate-100/60 transition">
                        <span>Click for details</span>
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- 3. SECTION: SITE STATUS TODAY (ACTIVE / DORMANT / NOT REPORTED WITH BLACK BREAKDOWN PILLS) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between mb-2.5 pb-2 border-b border-slate-100">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">
                SITE STATUS TODAY
            </h2>
            <span class="text-[11px] text-slate-500 font-medium">
                Remaining {{ $dashStats['remaining_count'] }} villages = Active + Dormant + Not reported
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <!-- ACTIVE SITES -->
            <div @click="openDetail('active_sites')"
                 class="rounded-lg border-2 border-emerald-500 bg-white overflow-hidden shadow-2xs cursor-pointer hover:border-emerald-700 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="h-1.5 bg-[#15803d]"></div>
                <div class="p-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-[#15803d]">ACTIVE SITES</span>
                        <span class="text-[11px] font-bold text-[#15803d]">
                            {{ $dashStats['active_pct'] }}% of remaining
                        </span>
                    </div>
                    <div class="flex items-center justify-between my-2">
                        <div class="text-4xl sm:text-5xl font-black text-[#15803d] leading-none">{{ $payload['active_count'] ?? 58 }}</div>
                        <span class="bg-[#0f172a] text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">
                            Pilot {{ $dashStats['phase_split']['active']['pilot'] }} &bull; Phase-0 {{ $dashStats['phase_split']['active']['phase0'] }}
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span>Labour {{ number_format($payload['total_labour'] ?? 692) }} &bull; Machinery {{ $payload['total_machinery'] ?? 139 }}</span>
                        <span class="text-[9px] text-emerald-700 font-bold group-hover:underline flex items-center gap-0.5">Details &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- DORMANT SITES -->
            <div @click="openDetail('dormant_sites')"
                 class="rounded-lg border-2 border-rose-500 bg-white overflow-hidden shadow-2xs cursor-pointer hover:border-rose-700 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="h-1.5 bg-[#dc2626]"></div>
                <div class="p-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-[#dc2626]">DORMANT SITES</span>
                        <span class="text-[11px] font-bold text-[#dc2626]">
                            {{ $dashStats['dormant_pct'] }}% of remaining
                        </span>
                    </div>
                    <div class="flex items-center justify-between my-2">
                        <div class="text-4xl sm:text-5xl font-black text-[#dc2626] leading-none">{{ $payload['dormant_count'] ?? 67 }}</div>
                        <span class="bg-[#0f172a] text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">
                            Pilot {{ $dashStats['phase_split']['dormant']['pilot'] }} &bull; Phase-0 {{ $dashStats['phase_split']['dormant']['phase0'] }}
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span>No labour, no machinery on site</span>
                        <span class="text-[9px] text-rose-700 font-bold group-hover:underline flex items-center gap-0.5">Details &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- NOT REPORTED -->
            <div @click="openDetail('nr_sites')"
                 class="rounded-lg border-2 border-amber-500 bg-white overflow-hidden shadow-2xs cursor-pointer hover:border-amber-700 hover:shadow-md transition transform hover:-translate-y-0.5 group">
                <div class="h-1.5 bg-[#f59e0b]"></div>
                <div class="p-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-[#d97706]">NOT REPORTED</span>
                        <span class="text-[11px] font-bold text-[#d97706]">
                            {{ $dashStats['nr_pct'] }}% of remaining
                        </span>
                    </div>
                    <div class="flex items-center justify-between my-2">
                        <div class="text-4xl sm:text-5xl font-black text-[#d97706] leading-none">{{ $payload['nr_count'] ?? 3 }}</div>
                        <span class="bg-[#0f172a] text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">
                            Pilot {{ $dashStats['phase_split']['not_reported']['pilot'] }} &bull; Phase-0 {{ $dashStats['phase_split']['not_reported']['phase0'] }}
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-medium pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span>Status left blank in field return</span>
                        <span class="text-[9px] text-amber-700 font-bold group-hover:underline flex items-center gap-0.5">Details &rarr;</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. SECTION: DORMANT SITES BY CAUSE (5 Colored Category Boxes - Exact PDF Palette) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
        <div class="flex flex-col sm:flex-row sm:items-baseline justify-between mb-2.5 pb-2 border-b border-slate-100">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">
                DORMANT SITES BY CAUSE
            </h2>
            <span class="text-[11px] text-slate-500 font-medium">
                {{ $dashStats['dormant_count'] }} dormant sites split by responsible side (from the Remarks column)
            </span>
        </div>

        @php
            $causeBoxes = [
                ['key' => 'Contractor', 'label' => 'CONTRACTOR', 'border' => 'border-orange-500', 'text' => 'text-orange-600', 'rule' => 'border-orange-100', 'empty' => 'Not mobilised / labour n/a'],
                ['key' => "VLD's", 'label' => "VLD's", 'border' => 'border-rose-500', 'text' => 'text-rose-600', 'rule' => 'border-rose-100', 'empty' => 'Village-level issues'],
                ['key' => 'Govt. Land', 'label' => 'GOVT LAND', 'border' => 'border-emerald-500', 'text' => 'text-emerald-700', 'rule' => 'border-emerald-100', 'empty' => 'Land / site possession'],
                ['key' => 'PRMSC', 'label' => 'PRMSC', 'border' => 'border-rose-600', 'text' => 'text-rose-700', 'rule' => 'border-rose-100', 'empty' => 'Payment, EOT, advance, testing'],
                ['key' => 'External', 'label' => 'EXTERNAL', 'border' => 'border-orange-500', 'text' => 'text-orange-600', 'rule' => 'border-orange-100', 'empty' => 'NOC, community, weather'],
            ];
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
            @foreach($causeBoxes as $box)
                @php $cause = $dashStats['causes'][$box['key']]; @endphp
                <div class="rounded-lg border-2 {{ $box['border'] }} bg-white p-3 flex flex-col justify-between {{ $loop->last ? 'col-span-2 sm:col-span-1' : '' }}">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black {{ $box['text'] }} uppercase">{{ $box['label'] }}</span>
                            <span class="text-[9px] font-bold {{ $box['text'] }}">{{ $cause['count'] > 0 ? $cause['pct'] . '%' : 'none today' }}</span>
                        </div>
                        <div class="text-3xl font-black {{ $box['text'] }} my-1">{{ $cause['count'] }}</div>
                    </div>
                    <div class="text-[10px] text-slate-700 space-y-0.5 border-t {{ $box['rule'] }} pt-1.5 font-medium">
                        @forelse($cause['lines'] as $lineLabel => $lineCount)
                            <div>{{ $lineLabel }} {{ $lineCount }}</div>
                        @empty
                            <div class="text-slate-400">{{ $box['empty'] }}</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 5. CALLOUT BANNER: PAYMENT-RELATED DORMANCY (Exact PDF Styling) -->
    @if($dashStats['payment_related_count'] > 0)
    <div class="bg-[#fff1f2] rounded-xl border border-[#fecdd3] p-3.5 flex flex-col sm:flex-row items-start sm:items-center gap-3.5 shadow-2xs">
        <div class="bg-[#991b1b] text-white rounded-lg p-2.5 text-center shrink-0 min-w-[140px] shadow-xs">
            <div class="text-[9px] uppercase font-bold text-rose-200 tracking-wider">PAYMENT-RELATED DORMANCY</div>
            <div class="text-3xl font-black text-white leading-none my-1">{{ $dashStats['payment_related_count'] }}</div>
            <div class="text-[9px] text-rose-200 font-semibold">{{ $dashStats['payment_related_pct'] }}% of all dormant sites</div>
        </div>
        <div class="text-xs text-slate-800 leading-relaxed space-y-1">
            <div class="font-bold text-[#881337] text-[12px]">
                {{ $dashStats['payment_count'] }} {{ Str::plural('site', $dashStats['payment_count']) }} halted over pending payments / IPCs ({{ $dashStats['payment_pct'] }}%)@if($dashStats['advance_count'] > 0) + {{ $dashStats['advance_count'] }} awaiting mobilization advance ({{ $dashStats['advance_pct'] }}%)@endif.
            </div>
            @if($dashStats['payment_list'])
                <div class="text-[11px] text-slate-700">
                    <strong>Payments pending:</strong> {{ $dashStats['payment_list'] }}
                </div>
            @endif
            @if($dashStats['advance_list'])
                <div class="text-[11px] text-slate-700">
                    <strong>Mob. advance pending:</strong> {{ $dashStats['advance_list'] }}
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- 7. PRESERVED: PHASE PROGRESS SUMMARY (Requested to keep) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">
                Phase Progress Summary
            </h2>
            <span class="text-xs font-semibold text-slate-500">Scope Overview</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-2.5">Phase Name</th>
                        <th class="p-2.5 text-center">Ongoing Sites</th>
                        <th class="p-2.5 text-center text-[#15803d]">Active</th>
                        <th class="p-2.5 text-center text-[#dc2626]">Dormant</th>
                        <th class="p-2.5 text-center text-[#d97706]">Not Reported</th>
                        <th class="p-2.5 text-center font-black">% Active</th>
                        <th class="p-2.5 text-right font-black text-sky-800">Completed / Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($payload['phases'] ?? [] as $ph)
                    <tr class="hover:bg-slate-50">
                        <td class="p-2.5 font-bold text-slate-900">{{ $ph['name'] ?? '-' }}</td>
                        <td class="p-2.5 text-center font-semibold">{{ $ph['sites'] ?? 0 }}</td>
                        <td class="p-2.5 text-center font-bold text-[#15803d]">{{ $ph['active'] ?? 0 }}</td>
                        <td class="p-2.5 text-center font-bold text-[#dc2626]">{{ $ph['dormant'] ?? 0 }}</td>
                        <td class="p-2.5 text-center font-bold text-[#d97706]">{{ $ph['nr'] ?? 0 }}</td>
                        <td class="p-2.5 text-center font-black text-slate-900">{{ $ph['active_pct'] ?? (($ph['sites'] ?? 0) > 0 ? round((($ph['active'] ?? 0) / $ph['sites']) * 100) : 0) }}%</td>
                        <td class="p-2.5 text-right font-bold text-sky-700">{{ $ph['done_label'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 8. PRESERVED: GEOGRAPHIC SITE MOBILIZATION MAP (OpenStreetMap) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                <span>Geographic Site Mobilization Map (OpenStreetMap)</span>
            </h2>
            <div class="flex items-center gap-3 text-xs font-medium">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Active ({{ $payload['active_count'] ?? 58 }})</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Dormant ({{ $payload['dormant_count'] ?? 67 }})</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Not Reported ({{ $payload['nr_count'] ?? 3 }})</span>
            </div>
        </div>
        <div id="sitesMap" class="w-full h-80 rounded-lg border border-slate-200 overflow-hidden"></div>
    </div>

    <!-- 9. SECTION: SITE REGISTER (2-Column Exact Table Layout from PDF) -->
    @php
        $canEditEntries = auth()->user()?->hasRole(['Super Admin', 'HQ Manager']) ?? false;
        $regRows = collect($payload['register_rows'] ?? []);
        $half = (int) ceil($regRows->count() / 2);
        $registerColumns = [$regRows->slice(0, $half), $regRows->slice($half)];
        $groupSizes = $regRows->countBy('group_name');
        $reportingDays = $regRows->max('reporting_days') ?: 1;
        $bandStyles = [
            'dormant' => ['row' => 'bg-[#fde8e8]', 'edge' => 'border-l-4 border-l-[#dc2626]', 'swatch' => 'bg-[#fde8e8] border-[#dc2626]', 'label' => 'Dormant — nothing on site'],
            'past_target' => ['row' => 'bg-[#fdebd3]', 'edge' => 'border-l-4 border-l-[#ea580c]', 'swatch' => 'bg-[#fdebd3] border-[#ea580c]', 'label' => 'Working, past target'],
            'watch' => ['row' => 'bg-[#fef6d6]', 'edge' => 'border-l-4 border-l-[#eab308]', 'swatch' => 'bg-[#fef6d6] border-[#eab308]', 'label' => 'Watch — not reported, thin (<5), blocker, target ≤10 days'],
            'on_track' => ['row' => 'bg-[#e8f5ec]', 'edge' => 'border-l-4 border-l-[#16a34a]', 'swatch' => 'bg-[#e8f5ec] border-[#16a34a]', 'label' => 'On track / completed'],
        ];
        $trendStyles = [
            'up' => ['class' => 'text-[#15803d]', 'icon' => '▲'],
            'down' => ['class' => 'text-[#dc2626]', 'icon' => '▼'],
            'same' => ['class' => 'text-slate-500', 'icon' => '='],
            'none' => ['class' => 'text-slate-400', 'icon' => ''],
        ];
    @endphp
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3" x-data="{ regFilter: '' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2 border-b border-slate-100">
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">
                    SITE REGISTER
                </h2>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-slate-700 mt-1.5 font-medium">
                    @foreach($bandStyles as $band)
                        <span class="flex items-center gap-1.5"><span class="w-5 h-3 rounded-sm border {{ $band['swatch'] }}"></span> {{ $band['label'] }}</span>
                    @endforeach
                    <span class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-full bg-[#dc2626] text-white font-extrabold text-[9px]">PAYMENT</span>
                        <span class="text-[#b91c1c] font-bold">payment-related</span>
                    </span>
                </div>
            </div>
            <div>
                <input type="text" x-model="regFilter" placeholder="Search village, tehsil, contractor..."
                       class="text-xs rounded-lg border-slate-300 shadow-xs focus:ring-emerald-500 focus:border-emerald-500 px-3 py-1.5 bg-slate-50 w-full sm:w-64">
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @foreach($registerColumns as $columnRows)
                <div class="overflow-x-auto max-h-[650px] border border-slate-200 rounded-lg">
                    <table class="min-w-full text-[11px] text-left">
                        <thead class="bg-[#14532d] text-white font-bold uppercase text-[9px] sticky top-0 z-10">
                            <tr>
                                <th class="p-1.5 pl-2.5">Package</th>
                                <th class="p-1.5">Phase</th>
                                <th class="p-1.5">Village</th>
                                <th class="p-1.5">Tehsil</th>
                                <th class="p-1.5">Status</th>
                                <th class="p-1.5 text-center" title="Days reported Active out of {{ $reportingDays }} daily {{ Str::plural('sheet', $reportingDays) }} supplied">Active days</th>
                                <th class="p-1.5" title="Change since the site's previous daily entry">vs prev. day</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white">
                            @php $currentGroup = null; @endphp
                            @foreach($columnRows as $r)
                                @if(($r['group_name'] ?? null) !== $currentGroup)
                                    @php
                                        $currentGroup = $r['group_name'] ?? null;
                                        [$groupTitle, $groupDetail] = array_pad(explode(' · ', (string) $currentGroup, 2), 2, null);
                                    @endphp
                                    <tr class="bg-[#dcefe2] text-[#14532d] font-bold">
                                        <td colspan="7" class="p-1.5 pl-2.5 text-[10px] tracking-wide uppercase">
                                            {{ $groupTitle }} &middot; {{ $groupSizes[$currentGroup] ?? 0 }} sites @if($groupDetail) &middot; {{ $groupDetail }} @endif
                                        </td>
                                    </tr>
                                @endif
                                @php
                                    $band = $bandStyles[$r['row_band'] ?? 'on_track'] ?? $bandStyles['on_track'];
                                    $trend = $r['previous_entry'] ?? ['label' => '—', 'tone' => 'none', 'date' => null];
                                    $trendStyle = $trendStyles[$trend['tone']] ?? $trendStyles['none'];
                                @endphp
                                <tr class="{{ $band['row'] }} hover:brightness-95" x-show="!regFilter || '{{ strtolower($r['village'] . ' ' . $r['tehsil'] . ' ' . ($r['contractor'] ?? '') . ' ' . ($r['package'] ?? '')) }}'.includes(regFilter.toLowerCase())">
                                    <td class="p-1.5 pl-2 font-mono text-[10px] text-slate-600 whitespace-nowrap {{ $band['edge'] }}" title="{{ $r['package'] ?? '' }}">{{ trim(preg_replace('/\s*\(.*\)/', '', $r['package'] ?? '—')) }}</td>
                                    <td class="p-1.5 text-[10px] font-semibold text-slate-600">{{ $r['phase_abbr'] ?? 'Pilot' }}</td>
                                    <td class="p-1.5 font-bold text-slate-900 whitespace-nowrap">
                                        <a href="{{ route('sites.show', $r['site_id']) }}" class="hover:text-emerald-700 hover:underline">
                                            {{ $r['village'] }}
                                        </a>
                                        @if($canEditEntries)
                                            <a href="{{ route('entries.edit', [$r['site_id'], $date]) }}" title="Edit this day's entry"
                                               class="ml-1 text-[10px] font-bold text-sky-700 hover:text-sky-900 hover:underline">Edit</a>
                                        @endif
                                    </td>
                                    <td class="p-1.5 text-slate-700 whitespace-nowrap">{{ $r['tehsil'] }}</td>
                                    <td class="p-1.5 whitespace-nowrap">
                                        @if($r['day_status'] === 'active')
                                            <span class="text-[#15803d] font-bold">Active {{ $r['labour'] }}L&middot;{{ $r['machinery'] }}M</span>
                                        @elseif($r['day_status'] === 'not_reported')
                                            <span class="text-[#b45309] font-bold">No report</span>
                                        @elseif($r['day_status'] === 'completed')
                                            <span class="text-[#14532d] font-bold">Completed ({{ $r['physical_progress'] ?? 0 }}%)</span>
                                        @else
                                            @php $reasonLabel = \App\Services\DashboardSummary::shortReasonLabel($r); @endphp
                                            <span class="text-[#b91c1c] font-bold">Dormant &middot;</span>
                                            @if(in_array($reasonLabel, ['Payment', 'Mob. advance']))
                                                <span class="px-2 py-0.5 rounded-full bg-[#dc2626] text-white font-extrabold text-[9px] uppercase">{{ $reasonLabel === 'Payment' ? 'Payment pending' : 'Mob. advance' }}</span>
                                            @else
                                                <span class="text-[#b91c1c] font-bold">{{ $reasonLabel }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="p-1.5 text-center font-bold {{ ($r['active_days'] ?? 0) > 0 ? 'text-[#15803d]' : 'text-slate-500' }}">{{ $r['active_days'] ?? 0 }}</td>
                                    <td class="p-1.5 whitespace-nowrap text-[10px] font-semibold {{ $trendStyle['class'] }}" @if($trend['date']) title="Compared with entry of {{ $trend['date'] }}" @endif>
                                        @if($trendStyle['icon'])<span class="mr-0.5">{{ $trendStyle['icon'] }}</span>@endif{{ $trend['label'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
        <p class="text-[10px] text-slate-500">
            <strong>Active days</strong> = daily sheets (of {{ $reportingDays }} supplied) in which the site was Active.
            <strong>vs prev. day</strong> compares today's entry with the site's previous daily entry: ▲ resumed or more labour, ▼ stopped or less labour, = no change.
        </p>
    </div>

    <!-- 10. SECTION: SCORECARDS (CONTRACTOR & TEHSIL) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Contractor Scorecard -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Contractor Scorecard (Worst First)</h3>
                <span class="text-[10px] text-slate-500 font-semibold">Sorted by 7+ idle days</span>
            </div>
            <div class="overflow-x-auto max-h-72">
                <table class="w-full text-[11px] text-left">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[9px] sticky top-0">
                        <tr>
                            <th class="p-1.5">#</th>
                            <th class="p-1.5">Contractor</th>
                            <th class="p-1.5 text-center">Sites</th>
                            <th class="p-1.5 text-center text-emerald-700">Active</th>
                            <th class="p-1.5 text-center text-rose-700">Dorm</th>
                            <th class="p-1.5 text-center text-orange-700">3+d</th>
                            <th class="p-1.5 text-center text-red-700 font-black">7+d</th>
                            <th class="p-1.5 text-right font-black">% Act</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($payload['contractors'] as $idx => $c)
                        <tr class="hover:bg-slate-50">
                            <td class="p-1.5 font-mono text-slate-400 text-[10px]">{{ $idx + 1 }}</td>
                            <td class="p-1.5 font-semibold text-slate-800">{{ $c['name'] }}</td>
                            <td class="p-1.5 text-center">{{ $c['sites'] }}</td>
                            <td class="p-1.5 text-center font-bold text-emerald-700">{{ $c['active'] }}</td>
                            <td class="p-1.5 text-center font-bold text-rose-700">{{ $c['dormant'] }}</td>
                            <td class="p-1.5 text-center font-bold text-orange-700">{{ $c['idle_3_plus'] }}</td>
                            <td class="p-1.5 text-center font-bold text-red-700">{{ $c['idle_7_plus'] }}</td>
                            <td class="p-1.5 text-right font-black text-slate-900">{{ $c['pct_active'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tehsil Scorecard -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Tehsil Scorecard (Worst First)</h3>
                <span class="text-[10px] text-slate-500 font-semibold">Sorted by 7+ idle days</span>
            </div>
            <div class="overflow-x-auto max-h-72">
                <table class="w-full text-[11px] text-left">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[9px] sticky top-0">
                        <tr>
                            <th class="p-1.5">#</th>
                            <th class="p-1.5">Tehsil</th>
                            <th class="p-1.5 text-center">Sites</th>
                            <th class="p-1.5 text-center text-emerald-700">Active</th>
                            <th class="p-1.5 text-center text-rose-700">Dorm</th>
                            <th class="p-1.5 text-center text-orange-700">3+d</th>
                            <th class="p-1.5 text-center text-red-700 font-black">7+d</th>
                            <th class="p-1.5 text-right font-black">% Act</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($payload['tehsils'] as $idx => $t)
                        <tr class="hover:bg-slate-50">
                            <td class="p-1.5 font-mono text-slate-400 text-[10px]">{{ $idx + 1 }}</td>
                            <td class="p-1.5 font-semibold text-slate-800">{{ $t['name'] }}</td>
                            <td class="p-1.5 text-center">{{ $t['sites'] }}</td>
                            <td class="p-1.5 text-center font-bold text-emerald-700">{{ $t['active'] }}</td>
                            <td class="p-1.5 text-center font-bold text-rose-700">{{ $t['dormant'] }}</td>
                            <td class="p-1.5 text-center font-bold text-orange-700">{{ $t['idle_3_plus'] }}</td>
                            <td class="p-1.5 text-center font-bold text-red-700">{{ $t['idle_7_plus'] }}</td>
                            <td class="p-1.5 text-right font-black text-slate-900">{{ $t['pct_active'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 11. FOOTER DEFINITIONS (Exact text from PDF) -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-[10px] text-slate-600 leading-relaxed space-y-1">
        <div>
            <strong>Active</strong> = site reported Active by field staff (L = labour, M = machinery working).
            <strong>Dormant</strong> = reported Inactive.
            <strong>No report</strong> = status left blank.
            <strong>Active days</strong> = number of daily sheets (of 1 supplied) in which the site was Active.
            <strong>Scope</strong> = {{ $payload['total_ongoing'] ?? 133 }} villages under execution after COC (Pilot {{ $dashStats['pilot_count'] }} + Phase-0 {{ $dashStats['phase0_count'] }} work-awarded), plus {{ $payload['tendering_count'] ?? 18 }} in tendering phase.
            <strong>Completed</strong> = 100% without COC + reported completed today.
        </div>
        <div>
            @if(count($dashStats['zero_active_villages']) > 0)
                <strong>Reported Active with 0 labour / 0 machinery:</strong> {{ implode(', ', $dashStats['zero_active_villages']) }}.
            @endif
            <strong>Cause mapping:</strong> PRMSC = payment, EOT pending, mobilization advance, material testing, village cancellation; External = community, NOC; Contractor = labour n/a, not mobilised / not started.
        </div>
    </div>
    <!-- 12. HOVER SCREEN / DETAIL STATS MODAL OVERLAY -->
    <div x-show="showDetailModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/60 backdrop-blur-xs"
         style="display: none;"
         @click.self="closeModal()"
         @keydown.escape.window="closeModal()">

        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-4xl w-full max-h-[88vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="bg-[#14532d] text-white p-4 sm:p-5 flex items-center justify-between border-b border-emerald-900">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded text-white"
                              :class="modalBadgeClass" x-text="modalBadge"></span>
                        <span class="text-xs text-emerald-200 font-semibold" x-text="modalSubtitle"></span>
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-white mt-1 flex items-center gap-2">
                        <span x-text="modalTitle"></span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-800 text-emerald-100"
                              x-text="modalSites.length + ' schemes'"></span>
                    </h3>
                </div>
                <button @click="closeModal()"
                        class="text-emerald-200 hover:text-white p-1.5 rounded-lg hover:bg-emerald-800/80 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Quick Summary Metrics Bar -->
            <div class="bg-slate-50 border-b border-slate-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-4">
                    <template x-for="st in modalStats" :key="st.label">
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 font-medium" x-text="st.label + ':'"></span>
                            <span class="font-black text-slate-800" x-text="st.value"></span>
                        </div>
                    </template>
                </div>
                <!-- Search within modal -->
                <div class="relative w-full sm:w-64">
                    <input type="text" x-model="modalSearch" placeholder="Filter in these sites..."
                           class="w-full text-xs rounded-lg border-slate-300 bg-white text-slate-900 pl-8 pr-3 py-1.5 shadow-2xs focus:ring-emerald-500 focus:border-emerald-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <!-- Scrollable Schemes Table -->
            <div class="overflow-y-auto flex-1 p-4">
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="min-w-full text-[11px] text-left">
                        <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[9px] sticky top-0 border-b border-slate-200">
                            <tr>
                                <th class="p-2">#</th>
                                <th class="p-2">PACKAGE</th>
                                <th class="p-2">VILLAGE</th>
                                <th class="p-2">TEHSIL</th>
                                <th class="p-2">CONTRACTOR</th>
                                <th class="p-2 text-center">STATUS</th>
                                <th class="p-2 text-center">PROGRESS</th>
                                <th class="p-2 text-center">LABOUR</th>
                                <th class="p-2 text-center">MACHINERY</th>
                                <th class="p-2">REMARKS / CAUSE</th>
                                <th class="p-2 text-right">ACTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(s, idx) in filteredModalSites" :key="s.site_id || idx">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-2 font-mono text-slate-400" x-text="idx + 1"></td>
                                    <td class="p-2 font-mono font-semibold text-slate-600" x-text="s.package || '—'"></td>
                                    <td class="p-2 font-bold text-slate-900">
                                        <a :href="'/sites/' + s.site_id" class="hover:text-emerald-700 hover:underline" x-text="s.village"></a>
                                    </td>
                                    <td class="p-2 text-slate-700 font-medium" x-text="s.tehsil"></td>
                                    <td class="p-2 text-slate-600 truncate max-w-[150px]" :title="s.contractor" x-text="s.contractor"></td>
                                    <td class="p-2 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800': s.day_status === 'active',
                                                  'bg-rose-100 text-rose-800': s.day_status === 'dormant',
                                                  'bg-amber-100 text-amber-800': s.day_status === 'not_reported',
                                                  'bg-emerald-900 text-white': s.day_status === 'completed',
                                                  'bg-purple-100 text-purple-800': s.day_status === 'tendering'
                                              }"
                                              x-text="s.day_status === 'active' ? 'ACTIVE' : (s.day_status === 'dormant' ? 'DORMANT' : (s.day_status === 'completed' ? 'DONE' : (s.day_status === 'tendering' ? 'TENDERING' : 'NO REPORT')))">
                                        </span>
                                    </td>
                                    <td class="p-2 text-center whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <div class="w-12 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-emerald-600 h-1.5 rounded-full" :style="'width: ' + (s.physical_progress || 0) + '%'"></div>
                                            </div>
                                            <span class="font-bold text-[11px] text-slate-800" x-text="(s.physical_progress !== undefined ? s.physical_progress : 0) + '%'"></span>
                                        </div>
                                    </td>
                                    <td class="p-2 text-center font-bold" :class="s.labour > 0 ? 'text-emerald-700' : 'text-slate-400'" x-text="s.labour"></td>
                                    <td class="p-2 text-center font-bold" :class="s.machinery > 0 ? 'text-emerald-700' : 'text-slate-400'" x-text="s.machinery"></td>
                                    <td class="p-2 text-slate-500 text-[10px] truncate max-w-[180px]" :title="s.status_label || s.top_level_reason" x-text="s.status_label || s.top_level_reason || '—'"></td>
                                    <td class="p-2 text-right whitespace-nowrap">
                                        <a x-show="canEditEntries && s.day_status !== 'tendering'" :href="entryEditLink(s.site_id)"
                                           class="inline-flex items-center text-xs font-bold text-sky-700 hover:text-sky-900 hover:underline mr-2">
                                            Edit
                                        </a>
                                        <a :href="'/sites/' + s.site_id" class="inline-flex items-center text-xs font-bold text-emerald-700 hover:text-emerald-900 hover:underline">
                                            View &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredModalSites.length === 0">
                                <td colspan="10" class="p-6 text-center text-slate-400 font-medium text-xs">
                                    No schemes matching your search within this card.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-50 border-t border-slate-200 p-3 px-5 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">
                    Showing <strong x-text="filteredModalSites.length"></strong> of <strong x-text="modalSites.length"></strong> schemes in this section
                </span>
                <div class="flex items-center gap-2">
                    <a :href="modalExcelUrl"
                       class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-emerald-800 hover:bg-emerald-700 font-bold text-white transition cursor-pointer"
                       :class="filteredModalSites.length === 0 ? 'pointer-events-none opacity-50' : ''">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Download Sheet
                    </a>
                    <a :href="modalPdfUrl"
                       class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-[#15803d] hover:bg-[#166534] font-bold text-white transition cursor-pointer"
                       :class="filteredModalSites.length === 0 ? 'pointer-events-none opacity-50' : ''">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M6 20h12a2 2 0 002-2V8l-6-6H6a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Download PDF
                    </a>
                    <button @click="closeModal()"
                            class="px-4 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 font-bold text-slate-700 transition cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardApp(config) {
    const allRows = (config.payload && config.payload.register_rows) ? config.payload.register_rows : [];
    const tenderingRows = (config.payload && config.payload.tendering_rows) ? config.payload.tendering_rows : [];
    const priorityCards = config.priorityCards || [];
    const priorityCardSubtitle = (name) => {
        const card = priorityCards.find(c => c.name === name);
        return card ? `Target: ${card.target} · ${card.badge}` : '';
    };
    const priorityCardField = (name, field) => {
        const card = priorityCards.find(c => c.name === name);
        return card ? card[field] : '';
    };

    return {
        showDetailModal: false,
        modalTitle: '',
        modalSubtitle: '',
        modalBadge: '',
        modalBadgeClass: '',
        modalStats: [],
        modalSites: [],
        modalSearch: '',
        modalType: null,
        modalName: null,

        canEditEntries: config.canEditEntries,

        entryEditLink(siteId) {
            return config.entryEditUrl + '/' + siteId + '/' + config.reportDate + '/edit';
        },

        get modalPdfUrl() {
            const params = new URLSearchParams({ ...config.activeFilters, type: this.modalType || '', date: config.reportDate });
            if (this.modalName) params.set('name', this.modalName);
            if (this.modalSearch) params.set('q', this.modalSearch);
            return config.detailsPdfUrl + '?' + params.toString();
        },

        get modalExcelUrl() {
            const params = new URLSearchParams({ ...config.activeFilters, type: this.modalType || '', date: config.reportDate });
            if (this.modalName) params.set('name', this.modalName);
            if (this.modalSearch) params.set('q', this.modalSearch);
            return config.detailsExcelUrl + '?' + params.toString();
        },

        get filteredModalSites() {
            if (!this.modalSearch) return this.modalSites;
            const q = this.modalSearch.toLowerCase();
            return this.modalSites.filter(s =>
                (s.village && s.village.toLowerCase().includes(q)) ||
                (s.tehsil && s.tehsil.toLowerCase().includes(q)) ||
                (s.contractor && s.contractor.toLowerCase().includes(q)) ||
                (s.package && s.package.toLowerCase().includes(q)) ||
                (s.status_label && s.status_label.toLowerCase().includes(q))
            );
        },

        closeModal() {
            this.showDetailModal = false;
            this.modalSearch = '';
        },

        openDetail(type, customName) {
            this.modalSearch = '';
            this.modalType = type;
            this.modalName = customName || null;
            let sites = [];
            let title = '';
            let subtitle = '';
            let badge = '';
            let badgeClass = 'bg-slate-700';
            let stats = [];

            if (type === 'total_villages') {
                title = 'Total Villages Under Execution';
                subtitle = 'All active & dormant schemes under current monitoring';
                badge = 'SCOPE';
                badgeClass = 'bg-[#1e3a8a]';
                sites = [...allRows];
                stats = [
                    { label: 'Total Schemes', value: sites.length },
                    { label: 'Active', value: sites.filter(s => s.day_status === 'active').length },
                    { label: 'Dormant', value: sites.filter(s => s.day_status === 'dormant').length },
                    { label: 'Labour', value: sites.reduce((sum, s) => sum + (s.labour || 0), 0) },
                    { label: 'Machinery', value: sites.reduce((sum, s) => sum + (s.machinery || 0), 0) }
                ];
            } else if (type === 'tendering_phase') {
                title = 'In The Tendering Phase';
                subtitle = 'Phase-0 & Deferred schemes currently in procurement & tendering';
                badge = 'TENDERING';
                badgeClass = 'bg-purple-800';
                sites = [...tenderingRows];
                stats = [
                    { label: 'Tendering Schemes', value: sites.length },
                    { label: 'Phase-0', value: sites.filter(s => s.phase_abbr === 'Ph-0').length },
                    { label: 'Status', value: 'Procurement / Inactive' },
                    { label: 'Civil Works', value: 'Not Started' }
                ];
            } else if (type === 'completed_villages') {
                title = 'Completed Villages';
                subtitle = '100% physically completed & COC pending';
                badge = 'COMPLETED';
                badgeClass = 'bg-emerald-700';
                sites = allRows.filter(s => s.day_status === 'completed' || (s.physical_progress || 0) >= 100);
                stats = [
                    { label: 'Completed Schemes', value: allRows.filter(s => s.day_status === 'completed').length },
                    { label: 'At 100% Progress (COC Pending)', value: allRows.filter(s => (s.physical_progress || 0) >= 100).length }
                ];
            } else if (type === 'remaining_villages') {
                title = 'Remaining Villages To Finish';
                subtitle = 'Schemes currently active, dormant, or awaiting reports';
                badge = 'REMAINING';
                badgeClass = 'bg-sky-700';
                sites = allRows.filter(s => s.day_status !== 'completed');
                stats = [
                    { label: 'Remaining Schemes', value: sites.length },
                    { label: 'Active', value: sites.filter(s => s.day_status === 'active').length },
                    { label: 'Dormant', value: sites.filter(s => s.day_status === 'dormant').length },
                    { label: 'Not Reported', value: sites.filter(s => s.day_status === 'not_reported').length }
                ];
            } else if (type === 'total_labour') {
                title = 'Total Labour Mobilization';
                subtitle = 'Active sites ranked by highest labour workforce on site';
                badge = 'LABOUR';
                badgeClass = 'bg-emerald-800';
                sites = allRows.filter(s => s.day_status === 'active').sort((a,b) => (b.labour || 0) - (a.labour || 0));
                const totalL = sites.reduce((sum, s) => sum + (s.labour || 0), 0);
                stats = [
                    { label: 'Total Labour', value: totalL },
                    { label: 'Active Sites', value: sites.length },
                    { label: 'Avg per Site', value: sites.length ? Math.round(totalL / sites.length) : 0 },
                    { label: 'Thinly Manned (<5)', value: sites.filter(s => s.labour < 5).length }
                ];
            } else if (type === 'total_machinery') {
                title = 'Total Machinery Mobilization';
                subtitle = 'Sites ranked by heavy equipment and machinery units';
                badge = 'MACHINERY';
                badgeClass = 'bg-slate-800';
                sites = allRows.filter(s => (s.machinery || 0) > 0 || s.day_status === 'active').sort((a,b) => (b.machinery || 0) - (a.machinery || 0));
                const totalM = sites.reduce((sum, s) => sum + (s.machinery || 0), 0);
                stats = [
                    { label: 'Total Machinery', value: totalM },
                    { label: 'Active Sites', value: allRows.filter(s => s.day_status === 'active').length },
                    { label: 'Avg per Active Site', value: allRows.filter(s => s.day_status === 'active').length ? (totalM / allRows.filter(s => s.day_status === 'active').length).toFixed(1) : 0 }
                ];
            } else if (type === 'physical_progress') {
                title = 'Physical Progress — Village Status';
                subtitle = 'Cumulative civil works execution progress across monitored schemes';
                badge = 'PROGRESS';
                badgeClass = 'bg-emerald-700';
                sites = [...allRows].sort((a,b) => (b.physical_progress || 0) - (a.physical_progress || 0));
                const totalProgress = sites.reduce((sum, s) => sum + (s.physical_progress || 0), 0);
                const avg = sites.length ? Math.round(totalProgress / sites.length) : 0;
                stats = [
                    { label: 'Avg Progress', value: avg + '%' },
                    { label: 'Completed (100%)', value: sites.filter(s => (s.physical_progress || 0) >= 100).length },
                    { label: 'In Progress (>50%)', value: sites.filter(s => (s.physical_progress || 0) >= 50 && (s.physical_progress || 0) < 100).length },
                    { label: 'Early Stage (<50%)', value: sites.filter(s => (s.physical_progress || 0) < 50).length },
                    { label: 'Total Monitored', value: sites.length }
                ];
            } else if (type === 'active_sites') {
                title = 'Active Sites Today';
                subtitle = 'Sites reported Active with working workforce & machinery';
                badge = 'ACTIVE';
                badgeClass = 'bg-[#15803d]';
                sites = allRows.filter(s => s.day_status === 'active');
                stats = [
                    { label: 'Active Schemes', value: sites.length },
                    { label: 'Total Labour', value: sites.reduce((sum, s) => sum + (s.labour || 0), 0) },
                    { label: 'Total Machinery', value: sites.reduce((sum, s) => sum + (s.machinery || 0), 0) },
                    { label: 'Thinly Manned', value: sites.filter(s => s.labour < 5).length }
                ];
            } else if (type === 'dormant_sites') {
                title = 'Dormant Sites Today';
                subtitle = 'Zero labour and machinery on site · grouped by root cause';
                badge = 'DORMANT';
                badgeClass = 'bg-[#dc2626]';
                sites = allRows.filter(s => s.day_status === 'dormant');
                stats = [
                    { label: 'Dormant Schemes', value: sites.length },
                    { label: 'Payment Issues', value: sites.filter(s => (s.status_label || '').toLowerCase().includes('payment') || (s.status_label || '').toLowerCase().includes('ipc')).length },
                    { label: 'Mob. Advance', value: sites.filter(s => (s.status_label || '').toLowerCase().includes('advance')).length }
                ];
            } else if (type === 'nr_sites') {
                title = 'Not Reported Sites';
                subtitle = 'Status left blank in field return sheets';
                badge = 'NOT REPORTED';
                badgeClass = 'bg-[#d97706]';
                sites = allRows.filter(s => s.day_status === 'not_reported');
                stats = [
                    { label: 'No Report Schemes', value: sites.length },
                    { label: 'Action', value: 'Follow up with Sub-Divisional Field Staff' }
                ];
            } else if (type === 'no_progress') {
                title = 'Sites With No Progress';
                subtitle = 'Dormant, stopped, un-mobilized or un-reported sites requiring executive intervention';
                badge = 'NO PROGRESS';
                badgeClass = 'bg-[#dc2626]';
                sites = allRows.filter(s => s.day_status === 'dormant' || s.day_status === 'not_reported' || ((s.physical_progress || 0) <= 0 && (s.labour || 0) === 0));
                stats = [
                    { label: 'Total No Progress', value: sites.length },
                    { label: 'Dormant (Stopped)', value: sites.filter(s => s.day_status === 'dormant').length },
                    { label: 'Not Reported', value: sites.filter(s => s.day_status === 'not_reported').length },
                    { label: 'Zero Progress (0%)', value: sites.filter(s => (s.physical_progress || 0) <= 0).length }
                ];
            } else if (type === 'priority_card') {
                const nameUpper = (customName || '').toUpperCase();
                title = nameUpper;
                if (nameUpper.includes('PRIORITY A')) {
                    subtitle = priorityCardSubtitle('PRIORITY A');
                    badge = priorityCardField('PRIORITY A', 'badge');
                    badgeClass = priorityCardField('PRIORITY A', 'badge_bg');
                    sites = allRows.filter(s => ['1', 'A'].includes(s.priority_code) || (s.group_name && s.group_name.includes('PRIORITY A')));
                } else if (nameUpper.includes('PRIORITY B')) {
                    subtitle = priorityCardSubtitle('PRIORITY B');
                    badge = priorityCardField('PRIORITY B', 'badge');
                    badgeClass = priorityCardField('PRIORITY B', 'badge_bg');
                    sites = allRows.filter(s => ['2', 'B'].includes(s.priority_code) || (s.group_name && s.group_name.includes('PRIORITY B')));
                } else if (nameUpper.includes('PRIORITY C')) {
                    subtitle = priorityCardSubtitle('PRIORITY C');
                    badge = priorityCardField('PRIORITY C', 'badge');
                    badgeClass = priorityCardField('PRIORITY C', 'badge_bg');
                    sites = allRows.filter(s => ['3', 'C'].includes(s.priority_code) || (s.group_name && s.group_name.includes('PRIORITY C')));
                } else if (nameUpper.includes('REMAINING')) {
                    subtitle = 'Pilot Schemes with No Target Date Set';
                    badge = 'PILOT';
                    badgeClass = 'bg-slate-700';
                    sites = allRows.filter(s => (s.group_name && s.group_name.includes('REMAINING')) || (!['1','2','3','A','B','C'].includes(s.priority_code) && s.phase_abbr !== 'Ph-0' && !(s.group_name && s.group_name.includes('PRIORITY'))));
                } else if (nameUpper.includes('PHASE-0')) {
                    subtitle = 'Work-Awarded Phase-0 Schemes';
                    badge = 'PHASE-0';
                    badgeClass = 'bg-slate-700';
                    sites = allRows.filter(s => s.phase_abbr === 'Ph-0' || (s.group_name && s.group_name.includes('PHASE-0')));
                }

                stats = [
                    { label: 'Total Sites', value: sites.length },
                    { label: 'Active', value: sites.filter(s => s.day_status === 'active').length },
                    { label: 'Dormant', value: sites.filter(s => s.day_status === 'dormant').length },
                    { label: 'Labour', value: sites.reduce((sum, s) => sum + (s.labour || 0), 0) },
                    { label: 'Machinery', value: sites.reduce((sum, s) => sum + (s.machinery || 0), 0) }
                ];
            }

            this.modalTitle = title;
            this.modalSubtitle = subtitle;
            this.modalBadge = badge;
            this.modalBadgeClass = badgeClass;
            this.modalSites = sites;
            this.modalStats = stats;
            this.showDetailModal = true;
        },

        initMap() {
            if (!document.getElementById('sitesMap')) return;
            const map = L.map('sitesMap').setView([30.71, 70.65], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            config.mapSites.forEach(s => {
                if (!s.latitude || !s.longitude) return;

                const status = s.snapshot_on ? s.snapshot_on.day_status : 'not_reported';
                const band = s.snapshot_on ? s.snapshot_on.idle_band : 'watch';

                let color = '#15803d'; // Green active
                if (status === 'dormant') {
                    color = (band === 'critical') ? '#dc2626' : '#ea580c';
                } else if (status === 'not_reported') {
                    color = '#f59e0b';
                }

                const marker = L.circleMarker([s.latitude, s.longitude], {
                    radius: 5.5,
                    fillColor: color,
                    color: '#ffffff',
                    weight: 1.5,
                    opacity: 1,
                    fillOpacity: 0.95
                }).addTo(map);

                marker.bindPopup(`
                    <div style="font-family: sans-serif; font-size: 11px; line-height: 1.4;">
                        <strong style="color: #0f172a;">${s.village_name}</strong> (${s.site_code})<br>
                        Tehsil: <strong>${s.tehsil ? s.tehsil.name : '-'}</strong><br>
                        Contractor: <strong>${s.contractor ? s.contractor.name : '-'}</strong><br>
                        Status: <strong style="color: ${color}; text-transform: uppercase;">${status}</strong>
                    </div>
                `);
            });
        }
    };
}

function filterToolbar(config) {
    return {
        tehsilVillagesMap: config.tehsilVillagesMap || {},
        allVillages: config.allVillages || [],
        selectedTehsil: config.initialTehsil || '',
        selectedVillage: config.initialVillage || '',
        get availableVillages() {
            if (this.selectedTehsil && this.tehsilVillagesMap[this.selectedTehsil]) {
                return this.tehsilVillagesMap[this.selectedTehsil];
            }
            return this.allVillages;
        },
        onTehsilChange() {
            if (this.selectedVillage && !this.availableVillages.includes(this.selectedVillage)) {
                this.selectedVillage = '';
            }
        }
    };
}
</script>
@endpush
@endsection
