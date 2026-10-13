@extends('layouts.app', ['title' => "Form DSM-1: Daily Site Return"])

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-5 space-y-4"
     x-data="cascadingReturnForm({
        cleanTree: {{ json_encode($cleanTree) }},
        categories: {{ json_encode($categories) }},
        initialCat: '{{ $initialCat }}',
        initialTehsilId: {{ $initialTehsilId }},
        initialSiteId: {{ $initialSiteId }},
        date: '{{ $date }}'
     })"
     x-init="initForm()">

    <!-- Top Header -->
    <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-md border border-slate-800">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="text-[11px] uppercase tracking-wider text-emerald-400 font-bold">Form DSM-1 &bull; PRSWSSP</div>
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Daily Site Progress Return</h1>
                <div class="text-xs text-slate-300 mt-0.5">
                    Date: <strong class="text-white">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</strong>
                    &bull; Cut-off: <span class="{{ $isPastCutoff ? 'text-rose-400 font-bold' : 'text-emerald-400 font-semibold' }}">{{ $cutoffTimeStr }} PKT</span>
                </div>
            </div>

            <!-- Action Buttons & Progress Badge -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard.export-sheet', ['date' => $date]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-emerald-800/80 hover:bg-emerald-700 text-white border border-emerald-600/60 shadow-xs transition cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Download Data Sheet</span>
                </a>
                <a href="{{ route('dashboard.export-no-progress', ['date' => $date]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-rose-900/70 hover:bg-rose-800 text-rose-200 border border-rose-700/60 shadow-xs transition cursor-pointer">
                    <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Sites with No Progress</span>
                </a>

                <div class="bg-slate-800/90 px-3.5 py-2 rounded-xl border border-slate-700/80 text-right sm:text-right shrink-0">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Progress Today</div>
                    <div class="text-base font-extrabold text-emerald-400">
                        {{ $submittedCount }} / {{ $totalOngoing }} Done
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CASCADING SELECTOR CARD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">

        <!-- STEP 1: CHOOSE PHASE -->
        <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-2">
                1. Select Phase:
            </label>
            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" @click="selectPhase('pilot')"
                        :class="selectedPhase === 'pilot' ? 'bg-[#14532d] text-white shadow-md ring-2 ring-emerald-700 ring-offset-2' : 'bg-slate-50 text-slate-800 border border-slate-200 hover:bg-slate-100'"
                        class="py-3.5 px-3 rounded-xl font-black flex flex-col items-center justify-center transition cursor-pointer">
                    <span class="text-sm uppercase tracking-wide">Pilot Phase</span>
                    <span class="text-[11px] font-bold opacity-80 mt-0.5" x-text="pilotVillageCount() + ' Villages'"></span>
                </button>
                <button type="button" @click="selectPhase('phase-0')"
                        :class="selectedPhase === 'phase-0' ? 'bg-[#15803d] text-white shadow-md ring-2 ring-emerald-600 ring-offset-2' : 'bg-slate-50 text-slate-800 border border-slate-200 hover:bg-slate-100'"
                        class="py-3.5 px-3 rounded-xl font-black flex flex-col items-center justify-center transition cursor-pointer">
                    <span class="text-sm uppercase tracking-wide">Phase 0</span>
                    <span class="text-[11px] font-bold opacity-80 mt-0.5" x-text="villageCount('phase-0') + ' Villages'"></span>
                </button>
            </div>
        </div>

        <!-- STEP 2 (PILOT ONLY): CHOOSE PRIORITY -->
        <div x-show="selectedPhase === 'pilot'" x-cloak>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-2">
                2. Select Priority (Pilot Phase):
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <template x-for="option in pilotPriorities" :key="option.key">
                    <button type="button" @click="selectCategory(option.key)"
                            :class="selectedCat === option.key ? option.activeClass + ' text-white shadow-md ring-2 ring-offset-2' : 'bg-slate-50 text-slate-800 border border-slate-200 hover:bg-slate-100'"
                            class="py-3 px-3 rounded-xl font-black flex flex-col items-center justify-center transition cursor-pointer">
                        <span class="text-xs uppercase tracking-wide" x-text="option.label"></span>
                        <span class="text-[10px] font-bold opacity-80 mt-0.5" x-text="villageCount(option.key) + ' Villages'"></span>
                    </button>
                </template>
            </div>
            <p x-show="!selectedCat" class="text-[11px] text-slate-500 mt-2">Choose a priority to see its tehsils and villages.</p>
        </div>

        <!-- STEP: CHOOSE TEHSIL -->
        <div x-show="selectedCat" x-cloak>
            <label for="tehsilSelect" class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-1.5 flex items-center justify-between">
                <span x-text="(selectedPhase === 'pilot' ? '3' : '2') + '. Select Tehsil:'"></span>
                <span class="text-[11px] text-slate-500 font-semibold" x-text="availableTehsils.length + ' Tehsils available'"></span>
            </label>
            <select id="tehsilSelect" x-model.number="selectedTehsilId" @change="onTehsilChange()"
                    class="block w-full text-sm font-bold text-slate-900 rounded-xl border-2 border-slate-300 focus:border-emerald-600 focus:ring-emerald-500 bg-slate-50 px-3.5 py-3 shadow-xs">
                <template x-for="t in availableTehsils" :key="t.id">
                    <option :value="t.id" x-text="t.name + ' (' + t.villages.length + ' villages)'"></option>
                </template>
            </select>
        </div>

        <!-- STEP: CHOOSE REVENUE VILLAGE -->
        <div x-show="selectedCat" x-cloak>
            <label for="villageSelect" class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-1.5 flex items-center justify-between">
                <span x-text="(selectedPhase === 'pilot' ? '4' : '3') + '. Select Revenue Village:'"></span>
                <span class="text-[11px] text-slate-500 font-semibold" x-text="availableVillages.length + ' Revenue Villages in Tehsil'"></span>
            </label>
            <select id="villageSelect" x-model.number="selectedSiteId" @change="onVillageChange()"
                    class="block w-full text-sm font-black text-slate-900 rounded-xl border-2 border-emerald-600 focus:border-emerald-700 focus:ring-emerald-500 bg-emerald-50/50 px-3.5 py-3 shadow-xs">
                <template x-for="v in availableVillages" :key="v.id">
                    <option :value="v.id" x-text="(v.is_submitted ? '✓ ' : '⏳ ') + v.name + (v.package && v.package !== '—' ? ' [' + v.package + ']' : '') + (v.settlements > 1 ? ' (' + v.settlements + ' settlements)' : '') + ' — ' + (v.is_completed ? 'Completed' : (v.is_submitted ? 'Submitted (' + v.status.toUpperCase() + ')' : 'Pending'))"></option>
                </template>
            </select>
            <div x-show="currentVillage && !currentVillage.is_completed" x-cloak class="mt-3 space-y-1.5">
                <button type="button"
                        @click="submitAsCompleted()"
                        class="w-full py-3.5 px-6 rounded-xl border-2 border-emerald-600 bg-emerald-50 hover:bg-emerald-100 text-emerald-950 font-black text-sm sm:text-base flex items-center justify-center gap-2 transition active:scale-[0.99] cursor-pointer shadow-xs">
                    <svg class="w-5 h-5 text-emerald-700" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293 1.414 1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Mark <span x-text="currentVillage ? currentVillage.name : ''"></span> as Completed &bull; 100%</span>
                </button>
                <p class="text-[11px] text-center text-slate-600">
                    Submits this selected village immediately as completed; daily labour and work details are not required.
                </p>
            </div>
        </div>
    </div>

    <!-- STEP 4: FURTHER QUESTIONS OF FORM DSM-1 (ACCORDING TO SRS) -->
    <template x-if="currentVillage">
        <div class="space-y-4">
            <!-- Village Information Card -->
            <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-md border border-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold bg-emerald-600 text-white px-2 py-0.5 rounded" x-text="currentVillage.code"></span>
                            <h2 class="text-lg sm:text-xl font-bold tracking-tight text-white" x-text="currentVillage.name"></h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-2 text-xs text-slate-300">
                            <span>Tehsil: <strong class="text-white" x-text="currentVillage.tehsil_name"></strong></span>
                            <span>&bull;</span>
                            <template x-if="currentVillage.package && currentVillage.package !== '—'">
                                <span class="flex items-center gap-1">
                                    Package: <strong class="text-amber-300 font-mono font-bold bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700" x-text="currentVillage.package"></strong>
                                    <span class="text-slate-400">&bull;</span>
                                </span>
                            </template>
                            <span>Settlements: <strong class="text-emerald-400" x-text="currentVillage.settlements"></strong></span>
                            <span>&bull;</span>
                            <span>Progress: <strong class="text-emerald-300 font-bold" x-text="(currentVillage.physical_progress !== undefined ? currentVillage.physical_progress : 0) + '%'"></strong></span>
                            <span>&bull;</span>
                            <span>Category: <strong class="text-white" x-text="currentVillage.category_title"></strong></span>
                            <span>&bull;</span>
                            <span>Contractor: <strong class="text-white" x-text="currentVillage.contractor"></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Already submitted alert -->
                <div x-show="currentVillage.is_completed" class="mt-3 pt-3 border-t border-slate-800 text-xs text-emerald-300 font-semibold">
                    ✓ This village is marked <strong class="text-white">Completed</strong> — no daily return is needed. Submit below only if work has restarted.
                </div>
                <div x-show="currentVillage.is_submitted && !currentVillage.is_completed" class="mt-3 pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-emerald-300">
                    <div class="flex items-center gap-1.5 font-semibold">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span>Already submitted today (<strong class="uppercase text-white" x-text="currentVillage.status"></strong>)</span>
                    </div>
                    <span class="text-[11px] text-slate-400">You can edit and re-save below</span>
                </div>
            </div>

            <!-- Form DSM-1 Submission Box -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-7">
                <form :action="'/returns/' + currentVillage.id + '/' + date" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- PHYSICAL PROGRESS PERCENTAGE BAR & SELECTOR -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                <span>Physical Progress Percentage (%):</span>
                            </label>
                            <span class="text-sm font-black text-emerald-800 bg-emerald-100 border border-emerald-300 px-3 py-0.5 rounded-lg font-mono shadow-2xs"
                                  x-text="formPhysicalProgress + '%'"></span>
                        </div>

                        <!-- Range slider -->
                        <div class="pt-1">
                            <input type="range" min="0" max="100" step="1" x-model.number="formPhysicalProgress"
                                   class="w-full h-2.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <!-- Visual Progress Bar -->
                        <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden p-0.5 shadow-inner">
                            <div class="h-2 rounded-full transition-all duration-200 bg-gradient-to-r from-emerald-500 via-teal-600 to-emerald-700"
                                 :style="'width: ' + formPhysicalProgress + '%'"></div>
                        </div>

                        <!-- Presets & Manual Quick-Select -->
                        <div class="flex flex-wrap items-center justify-between gap-1 pt-1 text-xs">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Quick Presets:</span>
                            <div class="flex gap-1.5">
                                <template x-for="p in [0, 25, 50, 75, 100]" :key="p">
                                    <button type="button" @click="formPhysicalProgress = p"
                                            :class="formPhysicalProgress === p ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 font-semibold'"
                                            class="px-2.5 py-1 rounded-md text-[11px] transition cursor-pointer"
                                            x-text="p + '%'"></button>
                                </template>
                            </div>
                        </div>
                        <input type="hidden" name="physical_progress" :value="formPhysicalProgress">
                    </div>

                    <!-- 1. Site Status Today (SRS FORM-3) -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Site Status Today: <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <!-- Active Button -->
                            <button type="button" @click="formStatus = 'active'"
                                    :class="formStatus === 'active' ? 'bg-[#15803d] text-white shadow-md ring-2 ring-emerald-600 ring-offset-2' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'"
                                    class="py-3 px-3 rounded-xl font-black text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-white inline-block" x-show="formStatus === 'active'"></span>
                                <span>🟢 Active (Working)</span>
                            </button>

                            <!-- Dormant Button -->
                            <button type="button" @click="formStatus = 'dormant'"
                                    :class="formStatus === 'dormant' ? 'bg-[#dc2626] text-white shadow-md ring-2 ring-rose-600 ring-offset-2' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'"
                                    class="py-3 px-3 rounded-xl font-black text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-white inline-block" x-show="formStatus === 'dormant'"></span>
                                <span>🟡 Dormant (Idle)</span>
                            </button>

                            <!-- Completed Button -->
                            <button type="button" @click="formStatus = 'completed'; formPhysicalProgress = 100"
                                    :class="formStatus === 'completed' ? 'bg-emerald-800 text-white shadow-md ring-2 ring-emerald-700 ring-offset-2' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100'"
                                    class="py-3 px-3 rounded-xl font-black text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer col-span-2 sm:col-span-1">
                                <span class="w-2.5 h-2.5 rounded-full bg-white inline-block" x-show="formStatus === 'completed'"></span>
                                <span>🏁 Completed (100%)</span>
                            </button>
                        </div>
                        <input type="hidden" name="status" :value="formStatus">

                        <!-- Completed Info Box -->
                        <div x-show="formStatus === 'completed'" class="mt-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-950 text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-emerald-900">
                                <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span>Site marked 100% physically complete &bull; COC pending</span>
                            </div>
                            <p class="text-emerald-800">
                                This entry will automatically reflect in the <strong>Completed Villages</strong> card on the executive dashboard.
                            </p>
                        </div>
                    </div>

                    <!-- 2A. ACTIVE QUESTIONS (Labour, Machinery, Types, Work Done) -->
                    <div x-show="formStatus === 'active'" class="space-y-5 pt-4 border-t border-slate-100">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Labour Count (Workers) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="labour_count" inputmode="numeric" min="0" x-model.number="formLabour" :required="formStatus === 'active'"
                                       class="mt-1.5 block w-full text-xl font-black rounded-xl border border-slate-300 shadow-xs focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-3 bg-slate-50 text-slate-900">
                                <span class="text-[11px] text-slate-400">Total workers deployed</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Machinery Units <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="machinery_count" inputmode="numeric" min="0" x-model.number="formMachinery" :required="formStatus === 'active'"
                                       class="mt-1.5 block w-full text-xl font-black rounded-xl border border-slate-300 shadow-xs focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-3 bg-slate-50 text-slate-900">
                                <span class="text-[11px] text-slate-400">Total machinery on site</span>
                            </div>
                        </div>

                        <!-- Machinery Types Deployed Checkboxes (SRS Form DSM-1) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                Machinery Types Deployed:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Excavator" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Excavator</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Concrete Mixer" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Concrete Mixer</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Tractor Trolley" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Tractor Trolley</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Plate Compactor" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Compactor</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Road Roller" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Road Roller</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Dewatering Pump" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Dewatering Pump</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Generator" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Generator</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                                    <input type="checkbox" name="machinery_types[]" value="Other" x-model="formMachineryTypes" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-medium text-slate-700">Other</span>
                                </label>
                            </div>
                        </div>

                        <!-- Work Done Description -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Work Done Description <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="work_done" x-model="formWorkDone" :required="formStatus === 'active'"
                                   placeholder="e.g. Tough tiles 250 Rft laid, brick masonry ongoing, excavation completed"
                                   class="mt-1.5 block w-full rounded-xl border border-slate-300 shadow-xs focus:ring-emerald-500 focus:border-emerald-500 px-3.5 py-2.5 text-sm bg-slate-50 text-slate-900">
                        </div>
                    </div>

                    <!-- 2B. DORMANT QUESTIONS (Reason, Idle Date, Remarks) -->
                    <div x-show="formStatus === 'dormant'" class="space-y-4 pt-4 border-t border-slate-100" style="display: none;">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Why is Site Idle? (Primary Blocker Reason) <span class="text-rose-500">*</span>
                            </label>
                            <select name="reason_id" x-model="formReasonId"
                                    class="mt-1.5 block w-full text-sm font-semibold rounded-xl border border-slate-300 shadow-xs focus:ring-rose-500 focus:border-rose-500 px-3.5 py-3 bg-slate-50 text-slate-900">
                                <option value="">-- Choose reason --</option>
                                @foreach($reasons as $parentReason)
                                    <optgroup label="{{ $parentReason->name }}">
                                        @if($parentReason->children->isEmpty())
                                            <option value="{{ $parentReason->id }}">{{ $parentReason->name }}</option>
                                        @else
                                            @foreach($parentReason->children as $child)
                                                <option value="{{ $child->id }}">{{ $child->name }}</option>
                                            @endforeach
                                        @endif
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Idle Since Date
                                </label>
                                <input type="date" name="idle_since" x-model="formIdleSince"
                                       class="mt-1.5 block w-full rounded-xl border border-slate-300 shadow-xs focus:ring-rose-500 focus:border-rose-500 px-3 py-2 text-sm bg-slate-50">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Remarks / Action Required
                                </label>
                                <input type="text" name="remarks" x-model="formRemarks"
                                       placeholder="e.g. IPC submitted, waiting for payment release"
                                       class="mt-1.5 block w-full rounded-xl border border-slate-300 shadow-xs focus:ring-rose-500 focus:border-rose-500 px-3 py-2 text-sm bg-slate-50">
                            </div>
                        </div>
                    </div>

                    <!-- 3. OPTIONAL PHOTO (SRS FORM-6) -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1 flex items-center justify-between">
                            <span>Attach Site Photograph:</span>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase">Optional</span>
                        </label>
                        <input type="file" name="photos[]" accept="image/*" capture="environment"
                               class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                    </div>

                    <!-- Fallback Coordinates -->
                    <input type="hidden" name="latitude" :value="currentVillage.lat">
                    <input type="hidden" name="longitude" :value="currentVillage.lng">

                    <!-- SUBMIT BUTTON -->
                    <div class="pt-2">
                        <button type="submit"
                                class="w-full py-4 px-6 rounded-xl shadow-lg shadow-emerald-700/20 text-base font-black text-white bg-[#15803d] hover:bg-[#16a34a] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-600 transition active:scale-[0.99] cursor-pointer flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span>Submit Daily Return for <span x-text="currentVillage.name"></span></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- QUICK VILLAGE CHIPS IN CURRENT TEHSIL -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-2">
                    Other Villages in this Tehsil (Click to switch):
                </h3>
                <div class="flex flex-wrap gap-2">
                    <template x-for="v in availableVillages" :key="v.id">
                        <button type="button" @click="selectedSiteId = v.id; onVillageChange()"
                                :class="v.id === currentVillage.id ? 'ring-2 ring-emerald-600 bg-emerald-50 text-emerald-900 border-emerald-300' : (v.is_submitted ? 'bg-emerald-50/70 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition cursor-pointer">
                            <span x-text="v.is_submitted ? '✓' : '⏳'"></span>
                            <span x-text="v.name"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </template>

</div>

<script>
function cascadingReturnForm(config) {
    return {
        tree: config.cleanTree,
        categories: config.categories,
        date: config.date,
        selectedCat: config.initialCat,
        selectedPhase: config.initialCat === 'phase-0' ? 'phase-0' : (config.initialCat ? 'pilot' : null),
        pilotPriorities: [
            { key: 'priority-a', label: 'Priority A', activeClass: 'bg-[#dc2626] ring-rose-600' },
            { key: 'priority-b', label: 'Priority B', activeClass: 'bg-[#d97706] ring-amber-600' },
            { key: 'priority-c', label: 'Priority C', activeClass: 'bg-[#2563eb] ring-blue-600' },
            { key: 'remaining', label: 'Remaining', activeClass: 'bg-[#475569] ring-slate-600' },
        ],
        selectedTehsilId: config.initialTehsilId,
        selectedSiteId: config.initialSiteId,

        availableTehsils: [],
        availableVillages: [],
        currentVillage: null,

        // Form fields
        formPhysicalProgress: 0,
        formStatus: 'active',
        formLabour: 5,
        formMachinery: 1,
        formMachineryTypes: ['Excavator'],
        formWorkDone: 'Civil works ongoing',
        formReasonId: '',
        formIdleSince: '',
        formRemarks: '',

        initForm() {
            this.updateTehsils();
        },

        villageCount(catKey) {
            return (this.tree[catKey] || []).reduce((total, tehsil) => total + tehsil.villages.length, 0);
        },

        pilotVillageCount() {
            return this.pilotPriorities.reduce((total, option) => total + this.villageCount(option.key), 0);
        },

        selectPhase(phase) {
            this.selectedPhase = phase;
            if (phase === 'phase-0') {
                this.selectCategory('phase-0');
                return;
            }
            // Pilot: ask for the priority first, keeping a pilot priority that is already chosen
            const keepCurrent = this.pilotPriorities.some(option => option.key === this.selectedCat);
            this.selectCategory(keepCurrent ? this.selectedCat : null);
        },

        selectCategory(catKey) {
            this.selectedCat = catKey;
            this.updateTehsils();
        },

        updateTehsils() {
            this.availableTehsils = this.selectedCat ? (this.tree[this.selectedCat] || []) : [];
            if (this.availableTehsils.length > 0) {
                // Keep tehsil if present, else first
                const exists = this.availableTehsils.some(t => t.id === this.selectedTehsilId);
                if (!exists) {
                    this.selectedTehsilId = this.availableTehsils[0].id;
                }
            } else {
                this.selectedTehsilId = null;
            }
            this.updateVillages();
        },

        onTehsilChange() {
            this.updateVillages();
        },

        updateVillages() {
            const currentTeh = this.availableTehsils.find(t => t.id === this.selectedTehsilId);
            this.availableVillages = currentTeh ? currentTeh.villages : [];

            if (this.availableVillages.length > 0) {
                const siteExists = this.availableVillages.some(v => v.id === this.selectedSiteId);
                if (!siteExists) {
                    // Pick first unsubmitted, or first
                    const unsub = this.availableVillages.find(v => !v.is_submitted);
                    this.selectedSiteId = unsub ? unsub.id : this.availableVillages[0].id;
                }
            } else {
                this.selectedSiteId = null;
            }
            this.loadVillageData();
        },

        onVillageChange() {
            this.loadVillageData();
        },

        loadVillageData() {
            this.currentVillage = this.availableVillages.find(v => v.id === this.selectedSiteId) || null;
            if (this.currentVillage) {
                if (this.currentVillage.is_completed || this.currentVillage.status === 'completed') {
                    this.formStatus = 'completed';
                    this.formPhysicalProgress = 100;
                } else {
                    this.formPhysicalProgress = (this.currentVillage.physical_progress !== undefined) ? Number(this.currentVillage.physical_progress) : 0;
                    this.formStatus = (this.currentVillage.status === 'dormant') ? 'dormant' : 'active';
                }
                this.formLabour = this.currentVillage.is_submitted ? this.currentVillage.labour : 6;
                this.formMachinery = this.currentVillage.is_submitted ? this.currentVillage.machinery : 1;
                this.formMachineryTypes = (this.currentVillage.machinery_types && this.currentVillage.machinery_types.length > 0) ? this.currentVillage.machinery_types : ['Excavator'];
                this.formWorkDone = this.currentVillage.work_done || 'Brick masonry, PCC and civil works in progress';
                this.formReasonId = this.currentVillage.reason_id || '';
                this.formIdleSince = this.currentVillage.idle_since || '';
                this.formRemarks = this.currentVillage.remarks || '';
            }
        },

        submitAsCompleted() {
            if (!this.currentVillage) return;
            if (!confirm('Mark ' + this.currentVillage.name + ' as COMPLETED (100% Progress)?\n\nThis entry will be recorded as completed and added to the "Completed Villages" card on the executive dashboard.')) {
                return;
            }
            this.formStatus = 'completed';
            this.formPhysicalProgress = 100;
            this.formLabour = 0;
            this.formMachinery = 0;
            if (!this.formRemarks || this.formRemarks.trim() === '') {
                this.formRemarks = 'Civil works 100% completed on site.';
            }
            this.$nextTick(() => {
                const form = document.querySelector('form[action*="/returns/"]');
                if (form) {
                    form.submit();
                }
            });
        }
    };
}
</script>
@endsection
