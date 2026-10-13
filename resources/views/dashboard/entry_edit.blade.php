@extends('layouts.app', ['title' => 'Edit Entry — '.$site->village_name])

@php
    $machineryOptions = ['Excavator', 'Concrete Mixer', 'Tractor Trolley', 'Plate Compactor', 'Road Roller', 'Dewatering Pump', 'Generator', 'Other'];
    $selectedTypes = old('machinery_types', $entry->machinery_types ?? []);
    $initialStatus = old('status', $entry->status ?? 'active');
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-4" x-data="{ status: '{{ $initialStatus }}' }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-900">{{ $entry ? 'Edit daily entry' : 'Add daily entry' }}</h1>
            <p class="text-sm text-slate-500">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }} &bull; changes update the dashboard immediately and are recorded in the audit log</p>
        </div>
        <a href="{{ route('dashboard', ['date' => $date]) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 whitespace-nowrap">&larr; Back to dashboard</a>
    </div>

    <!-- Village + current entry -->
    <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5">
        <div class="flex items-center gap-2">
            <span class="font-mono text-xs font-bold bg-emerald-600 px-2 py-0.5 rounded">{{ $site->site_code }}</span>
            <h2 class="text-lg font-bold">{{ $site->village_name }}</h2>
        </div>
        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-xs text-slate-300">
            <span>Tehsil: <strong class="text-white">{{ $site->tehsil->name ?? '—' }}</strong></span>
            <span>Package: <strong class="text-amber-300">{{ $site->package->code ?? '—' }}</strong></span>
            <span>Contractor: <strong class="text-white">{{ $site->contractor->name ?? '—' }}</strong></span>
            <span>Settlements: <strong class="text-white">{{ $site->settlements ?? 1 }}</strong></span>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-700 text-xs">
            @if($entry)
                <span class="text-slate-300">Current entry:</span>
                <strong class="uppercase">{{ $entry->status }}</strong>
                @if($entry->status === 'active') &bull; {{ $entry->labour_count }} labour &bull; {{ $entry->machinery_count }} machinery @endif
                &bull; submitted by <strong>{{ $entry->submitter->name ?? 'unknown' }}</strong>
                @if($entry->submitted_at) at {{ $entry->submitted_at->timezone('Asia/Karachi')->format('d M, H:i') }} PKT @endif
                &bull; {{ ucfirst($entry->approval_status) }}
                @if($entry->photos->isNotEmpty()) &bull; {{ $entry->photos->count() }} photo(s) kept @endif
            @else
                <span class="text-amber-300 font-semibold">No entry was submitted for this day.</span>
                <span class="text-slate-300">Saving will add one on the officer's behalf (marked as entered by you).</span>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
            <strong>Please fix the following:</strong>
            <ul class="list-disc ml-5 mt-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('entries.update', [$site, $date]) }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="physical_progress" class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-1.5">Physical progress (%)</label>
            <input type="number" id="physical_progress" name="physical_progress" min="0" max="100"
                   value="{{ old('physical_progress', (int) $site->physical_progress) }}"
                   class="w-32 rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
        </div>

        <div>
            <span class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-1.5">Site status</span>
            <div class="grid grid-cols-3 gap-2">
                @foreach(['active' => ['Active', 'bg-[#15803d]'], 'dormant' => ['Dormant', 'bg-[#dc2626]'], 'completed' => ['Completed', 'bg-[#14532d]']] as $value => [$label, $colour])
                    <label class="cursor-pointer">
                        <input type="radio" name="status" value="{{ $value }}" x-model="status" class="sr-only">
                        <span class="block text-center py-2.5 rounded-xl text-sm font-black border transition"
                              :class="status === '{{ $value }}' ? '{{ $colour }} text-white border-transparent shadow' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Active -->
        <div x-show="status === 'active'" class="space-y-4" @if($initialStatus !== 'active') style="display: none;" @endif>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="labour_count" class="block text-xs font-semibold text-slate-700 mb-1">Labour</label>
                    <input type="number" id="labour_count" name="labour_count" min="0" value="{{ old('labour_count', $entry->labour_count ?? 0) }}"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500" :disabled="status !== 'active'">
                </div>
                <div>
                    <label for="machinery_count" class="block text-xs font-semibold text-slate-700 mb-1">Machinery</label>
                    <input type="number" id="machinery_count" name="machinery_count" min="0" value="{{ old('machinery_count', $entry->machinery_count ?? 0) }}"
                           class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500" :disabled="status !== 'active'">
                </div>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-700 mb-1">Machinery types</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($machineryOptions as $type)
                        <label class="inline-flex items-center gap-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5">
                            <input type="checkbox" name="machinery_types[]" value="{{ $type }}" @checked(in_array($type, $selectedTypes))
                                   class="rounded text-emerald-600 focus:ring-emerald-500" :disabled="status !== 'active'">
                            {{ $type }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label for="work_done" class="block text-xs font-semibold text-slate-700 mb-1">Work done</label>
                <textarea id="work_done" name="work_done" rows="2" :disabled="status !== 'active'"
                          class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">{{ old('work_done', $entry->work_done ?? '') }}</textarea>
            </div>
        </div>

        <!-- Dormant -->
        <div x-show="status === 'dormant'" class="grid grid-cols-1 sm:grid-cols-3 gap-3" @if($initialStatus !== 'dormant') style="display: none;" @endif>
            <div class="sm:col-span-2">
                <label for="reason_id" class="block text-xs font-semibold text-slate-700 mb-1">Reason site is idle</label>
                <select id="reason_id" name="reason_id" :disabled="status !== 'dormant'"
                        class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Choose reason</option>
                    @foreach($reasons as $group)
                        @if($group->children->isEmpty())
                            <option value="{{ $group->id }}" @selected(old('reason_id', $entry->reason_id ?? null) == $group->id)>{{ $group->name }}</option>
                        @else
                            <optgroup label="{{ $group->name }}">
                                @foreach($group->children as $reason)
                                    <option value="{{ $reason->id }}" @selected(old('reason_id', $entry->reason_id ?? null) == $reason->id)>{{ $reason->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label for="idle_since" class="block text-xs font-semibold text-slate-700 mb-1">Idle since</label>
                <input type="date" id="idle_since" name="idle_since" max="{{ $date }}" :disabled="status !== 'dormant'"
                       value="{{ old('idle_since', $entry?->idle_since?->toDateString() ?? $date) }}"
                       class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
            </div>
        </div>

        <div>
            <label for="remarks" class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-1.5">Remarks</label>
            <textarea id="remarks" name="remarks" rows="2"
                      class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">{{ old('remarks', $entry->remarks ?? '') }}</textarea>
        </div>

        <div>
            <label for="correction_note" class="block text-xs font-black uppercase tracking-wider text-slate-800 mb-1.5">Reason for this change <span class="font-normal normal-case text-slate-500">(optional, saved in the audit log)</span></label>
            <input type="text" id="correction_note" name="correction_note" maxlength="500" value="{{ old('correction_note') }}"
                   placeholder="e.g. Labour figure confirmed by phone with SDO"
                   class="w-full rounded-lg border border-slate-300 bg-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <a href="{{ route('dashboard', ['date' => $date]) }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-sm font-bold text-slate-700">Cancel</a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-[#15803d] hover:bg-[#166534] text-sm font-bold text-white">Save entry</button>
        </div>
    </form>
</div>
@endsection
