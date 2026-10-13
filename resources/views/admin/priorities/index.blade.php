@extends('layouts.app', ['title' => 'Priorities Master'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Completion Priorities</h1>
            <p class="mt-1 text-sm text-slate-500">Configure delivery deadlines, target days, and colors for tracking panels (Priorities A–D / 1–4).</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
        <!-- Form -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Add Priority Level</h2>
            <form method="POST" action="{{ route('admin.priorities.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Code</label>
                    <input type="text" name="code" required placeholder="e.g. 1 or A"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Priority Name</label>
                    <input type="text" name="name" required placeholder="e.g. Priority 1"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Target Days</label>
                    <input type="number" name="target_days" required min="1" placeholder="15"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Deadline Date</label>
                    <input type="date" name="deadline" required
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Badge Colour</label>
                    <input type="color" name="colour" value="#EF4444" required
                           class="mt-1 block w-full h-10 rounded-lg border-slate-300 shadow-sm px-1 py-1 cursor-pointer">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition">
                    Save Priority
                </button>
            </form>
        </div>

        <!-- Table -->
        <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Target Days</th>
                        <th class="px-4 py-3">Deadline</th>
                        <th class="px-4 py-3">Sites Assigned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($priorities as $p)
                    <tr>
                        <td class="px-4 py-3 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background-color: {{ $p->colour }}"></span>
                            <span class="font-bold text-slate-900">{{ $p->name }}</span>
                            <span class="font-mono text-[10px] text-slate-400">({{ $p->code }})</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $p->target_days }} days</td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $p->deadline?->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-700">{{ $p->sites_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
