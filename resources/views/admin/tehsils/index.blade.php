@extends('layouts.app', ['title' => 'Tehsils Master'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tehsils Administrative Units</h1>
            <p class="mt-1 text-sm text-slate-500">Configure tehsils for field officer reporting and In-charge reviews.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Add Tehsil</h2>
            <form method="POST" action="{{ route('admin.tehsils.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Tehsil Name</label>
                    <input type="text" name="name" required placeholder="e.g. Taunsa"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Code</label>
                    <input type="text" name="code" placeholder="e.g. TAU"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">District</label>
                    <input type="text" name="district" placeholder="e.g. Dera Ghazi Khan"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition">
                    Save Tehsil
                </button>
            </form>
        </div>

        <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Tehsil Name</th>
                        <th class="px-4 py-3">District</th>
                        <th class="px-4 py-3">Sites</th>
                        <th class="px-4 py-3">Assigned Staff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($tehsils as $t)
                    <tr>
                        <td class="px-4 py-3 font-mono font-bold text-slate-800">{{ $t->code }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $t->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $t->district ?? '-' }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-700">{{ $t->sites_count }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $t->users_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
