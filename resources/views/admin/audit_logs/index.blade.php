@extends('layouts.app', ['title' => 'Audit Trail'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">System Audit Trail</h1>
        <p class="text-sm text-slate-500">Immutable ledger of all site edits, status approvals, user lockouts, and return submissions (AUD-1 & AUD-2).</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                <tr>
                    <th class="px-4 py-3">Timestamp (PKT)</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Entity</th>
                    <th class="px-4 py-3">IP Address</th>
                    <th class="px-4 py-3">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-sans">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-slate-500">{{ $log->created_at->setTimezone('Asia/Karachi')->format('Y-m-d H:i:s') }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $log->user->name ?? 'System' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded font-mono text-[10px] bg-slate-100 text-slate-800 font-semibold">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->model ? "{$log->model} #{$log->model_id}" : '-' }}</td>
                    <td class="px-4 py-3 font-mono text-slate-400 text-[11px]">{{ $log->ip ?? '-' }}</td>
                    <td class="px-4 py-3 text-slate-500 font-mono text-[11px] truncate max-w-xs">
                        @if($log->after)
                            {{ json_encode($log->after) }}
                        @elseif($log->before)
                            {{ json_encode($log->before) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-400">No audit logs recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
