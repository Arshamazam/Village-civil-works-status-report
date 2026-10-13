@extends('layouts.app', ['title' => 'Phases Master'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Project Phases</h1>
            <p class="mt-1 text-sm text-slate-500">Manage PRSWSSP development phases (Pilot, Phase-0, Phase-1).</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
        <!-- Form -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Add Phase</h2>
            <form method="POST" action="{{ route('admin.phases.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Phase Name</label>
                    <input type="text" name="name" required placeholder="e.g. Phase-2"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Code</label>
                    <input type="text" name="code" required placeholder="e.g. phase-2"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition">
                    Save Phase
                </button>
            </form>
        </div>

        <!-- Table -->
        <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Total Sites</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($phases as $ph)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $ph->name }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $ph->code }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-700">{{ $ph->sites_count }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ph->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $ph->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
