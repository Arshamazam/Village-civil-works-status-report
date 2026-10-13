@extends('layouts.app', ['title' => 'Import Preview & Validation'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Excel Import Preview</h1>
            <p class="mt-1 text-sm text-slate-500">Review rows and validation errors before importing to the database.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <a href="{{ route('admin.import-export.index') }}" class="px-4 py-2 text-sm font-semibold rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">Cancel</a>
            <form method="POST" action="{{ route('admin.import-export.commit') }}">
                @csrf
                <button type="submit" class="px-5 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow" {{ $hasErrors ? '' : '' }}>
                    Commit Valid Rows ({{ count(array_filter($previewRows, fn($r) => empty($r['errors']))) }})
                </button>
            </form>
        </div>
    </div>

    @if($hasErrors)
    <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
        <div>
            <strong>Notice:</strong> Some rows contain validation issues (highlighted in red below). Invalid rows will be skipped while valid rows can still be imported.
        </div>
    </div>
    @endif

    <div class="mt-6 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto max-h-[600px]">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold sticky top-0 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3">Row #</th>
                        <th class="px-3 py-3">Phase</th>
                        <th class="px-3 py-3">Tehsil</th>
                        <th class="px-3 py-3">Package</th>
                        <th class="px-3 py-3">Village Name</th>
                        <th class="px-3 py-3">Priority</th>
                        <th class="px-3 py-3">Contractor</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Coordinates</th>
                        <th class="px-3 py-3">Validation Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @foreach($previewRows as $row)
                    <tr class="{{ !empty($row['errors']) ? 'bg-rose-50/60' : 'hover:bg-slate-50' }}">
                        <td class="px-3 py-2 font-mono text-slate-500">{{ $row['row_number'] }}</td>
                        <td class="px-3 py-2 font-medium">{{ $row['phase'] }}</td>
                        <td class="px-3 py-2">{{ $row['tehsil'] }}</td>
                        <td class="px-3 py-2 font-mono text-[11px]">{{ $row['package'] }}</td>
                        <td class="px-3 py-2 font-semibold text-slate-900">{{ $row['village'] }}</td>
                        <td class="px-3 py-2">{{ $row['priority'] ?: '-' }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $row['contractor'] ?: '-' }}</td>
                        <td class="px-3 py-2">{{ ucfirst($row['status']) }}</td>
                        <td class="px-3 py-2 font-mono text-[11px] text-slate-500">
                            {{ $row['lat'] && $row['lng'] ? round($row['lat'], 4) . ', ' . round($row['lng'], 4) : '-' }}
                        </td>
                        <td class="px-3 py-2">
                            @if(!empty($row['errors']))
                                <div class="text-rose-700 font-medium">
                                    @foreach($row['errors'] as $err)
                                        <div>&bull; {{ $err }}</div>
                                    @endforeach
                                </div>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    Valid
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
