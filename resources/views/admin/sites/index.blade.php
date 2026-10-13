@extends('layouts.app', ['title' => 'Sites & Villages Master'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header with Actions -->
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sites & Schemes Master Data</h1>
            <p class="mt-1 text-sm text-slate-500">Manage all registered civil works schemes, GPS coordinates, priority deadlines and completion statuses.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
            <a href="{{ route('admin.import-export.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                Import / Export Excel
            </a>
            <a href="{{ route('admin.sites.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition shadow">
                + Add New Site
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="mt-6 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.sites.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search site code or village..."
                       class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 bg-slate-50">
            </div>
            <div>
                <select name="phase_id" class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 bg-slate-50">
                    <option value="">All Phases</option>
                    @foreach($phases as $ph)
                        <option value="{{ $ph->id }}" {{ request('phase_id') == $ph->id ? 'selected' : '' }}>{{ $ph->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="tehsil_id" class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 bg-slate-50">
                    <option value="">All Tehsils</option>
                    @foreach($tehsils as $t)
                        <option value="{{ $t->id }}" {{ request('tehsil_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 bg-slate-50">
                    <option value="">All Statuses</option>
                    <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Ongoing (Daily Active List)</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 transition">Filter</button>
                <a href="{{ route('admin.sites.index') }}" class="px-3 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- Sites Table -->
    <div class="mt-6 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Site ID</th>
                        <th class="px-4 py-3">Village Name</th>
                        <th class="px-4 py-3">Phase</th>
                        <th class="px-4 py-3">Tehsil</th>
                        <th class="px-4 py-3">Package</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Contractor</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">GPS Coords</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sites as $site)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $site->site_code }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $site->village_name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $site->phase->code === 'pilot' ? 'bg-indigo-50 text-indigo-700' : 'bg-purple-50 text-purple-700' }}">
                                {{ $site->phase->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $site->tehsil->name ?? '-' }}</td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">{{ $site->package->code ?? 'Not listed' }}</td>
                        <td class="px-4 py-3">
                            @if($site->priority)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background-color: {{ $site->priority->colour }}">
                                    {{ $site->priority->name }}
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $site->contractor->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($site->status === 'ongoing')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Ongoing</span>
                            @elseif($site->status === 'completed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Completed ({{ $site->completed_on?->format('d M Y') }})</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Suspended</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                            @if($site->latitude && $site->longitude)
                                {{ number_format($site->latitude, 4) }}, {{ number_format($site->longitude, 4) }}
                            @else
                                <span class="text-rose-500 font-sans italic">Not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.sites.edit', $site) }}" class="text-emerald-700 hover:text-emerald-900 font-semibold">Edit</a>
                            @if($site->status === 'ongoing')
                            <form method="POST" action="{{ route('admin.sites.mark-completed', $site) }}" class="inline" onsubmit="return confirm('Mark site {{ $site->site_code }} as completed? This removes it from future daily reporting lists.')">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-800 font-semibold">Complete</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-slate-400">No sites matching filters found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $sites->links() }}
        </div>
    </div>
</div>
@endsection
