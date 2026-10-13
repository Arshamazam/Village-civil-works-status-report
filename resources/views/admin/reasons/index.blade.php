@extends('layouts.app', ['title' => 'Idle Reasons Tree'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Idle Reason Hierarchy (Form DSM-1 Codes)</h1>
            <p class="mt-1 text-sm text-slate-500">Configure reasons for dormant sites (Contractor, PRMSC, External, VLD's, Govt. Land, Other).</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Add Sub-Reason</h2>
            <form method="POST" action="{{ route('admin.reasons.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Top Level Category</label>
                    <select name="parent_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                        @foreach($topReasons as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->top_level_group }} ({{ $parent->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Reason Code (e.g. C4, P4)</label>
                    <input type="text" name="code" required
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Label / Description</label>
                    <input type="text" name="label" required
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <input type="hidden" name="top_level_group" value="Contractor">
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition">
                    Save Sub-Reason
                </button>
            </form>
        </div>

        <div class="md:col-span-2 space-y-4">
            @foreach($topReasons as $top)
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-xs">{{ $top->code }}</span>
                        <h3 class="font-bold text-slate-900 text-sm">{{ $top->label }}</h3>
                    </div>
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-700">{{ $top->top_level_group }}</span>
                </div>
                <div class="mt-3 divide-y divide-slate-50">
                    @forelse($top->children as $child)
                    <div class="py-2 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ $child->code }}</span>
                            <span class="text-slate-700">{{ $child->label }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="text-xs text-slate-400 italic py-1">Top-level item with direct reason selection.</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
