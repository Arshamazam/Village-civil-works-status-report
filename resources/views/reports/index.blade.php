@extends('layouts.app', ['title' => 'Daily Mobilization Reports Hub'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Daily Mobilization Reports</h1>
            <p class="text-xs text-slate-500">Official Form DSM-1 daily rollups, executive PDF generation, and automated email dispatches</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('dashboard.export-sheet', ['date' => $selectedDate]) }}"
               class="px-3.5 py-2 rounded-lg bg-emerald-800 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download Data Sheet
            </a>

            <a href="{{ route('dashboard.export-no-progress', ['date' => $selectedDate]) }}"
               class="px-3.5 py-2 rounded-lg bg-rose-900 hover:bg-rose-800 text-rose-200 text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Sites with No Progress
            </a>

            <a href="{{ route('reports.html', ['date' => $selectedDate]) }}" target="_blank"
               class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                Preview HTML
            </a>

            <a href="{{ route('reports.pdf', ['date' => $selectedDate]) }}" target="_blank"
               class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Download PDF (Executive 1-Pager)
            </a>
        </div>
    </div>

    <!-- Date Selector & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Date Selection Card -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Select Report Date</h2>
            <form method="GET" action="{{ route('reports.view') }}" class="space-y-3">
                <div>
                    <label for="date-select" class="block text-xs font-semibold text-slate-600 mb-1">Report Date</label>
                    <input type="date" id="date-select" name="date" value="{{ $selectedDate }}"
                           class="w-full text-xs font-semibold rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 p-2.5 border">
                </div>
                <button type="submit" class="w-full py-2 bg-slate-900 text-white text-xs font-bold rounded-lg hover:bg-slate-800 transition">
                    View Selected Date
                </button>
            </form>

            @if($availableDates->isNotEmpty())
            <div class="pt-3 border-t border-slate-100">
                <span class="text-[11px] font-semibold text-slate-400 block mb-2">Available Snapshots:</span>
                <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto">
                    @foreach($availableDates->take(10) as $ad)
                    <a href="{{ route('reports.view', ['date' => $ad]) }}" 
                       class="text-[11px] px-2 py-1 rounded border {{ $ad === $selectedDate ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                        {{ \Carbon\Carbon::parse($ad)->format('d M') }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Immediate Dispatch (Send Now) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Manual Email Dispatch</h2>
            <p class="text-xs text-slate-500">Trigger immediate PDF rendering and email dispatch to all configured stakeholders for {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.</p>
            
            <form method="POST" action="{{ route('reports.send-now') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate }}">
                <button type="submit" onclick="return confirm('Dispatch emails for {{ $selectedDate }} right now?')"
                        class="w-full py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold rounded-lg transition shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    Send Report Now (14:00 PKT Job)
                </button>
            </form>

            <!-- Custom Resend -->
            <div class="pt-3 border-t border-slate-100">
                <span class="text-[11px] font-semibold text-slate-700 block mb-2">Resend Single Copy to Email:</span>
                <form method="POST" action="{{ route('reports.resend') }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="email" name="email" required placeholder="recipient@prmsc.punjab.gov.pk"
                           class="flex-1 text-xs rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 p-2 border">
                    <button type="submit" class="px-3 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-slate-700 transition">
                        Resend
                    </button>
                </form>
            </div>
        </div>

        <!-- Schedule & Cut-off Information -->
        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3 text-xs">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Automated Routine (PKT)</h2>
            <div class="space-y-2 text-slate-600">
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <span class="font-mono font-bold text-slate-800">12:00 PKT</span>
                    <span>Submission reminders to pending field officers</span>
                </div>
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <span class="font-mono font-bold text-slate-800">13:00 PKT</span>
                    <span>Daily return cut-off (unsubmitted marked NR)</span>
                </div>
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <span class="font-mono font-bold text-slate-800">13:30 PKT</span>
                    <span>Rollup engine snapshot calculation</span>
                </div>
                <div class="flex items-center justify-between pb-1 border-b border-slate-200/60">
                    <span class="font-mono font-bold text-emerald-700">14:00 PKT</span>
                    <span class="font-semibold text-emerald-900">PDF generated & emailed to leadership</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400 italic mt-2">
                * Reporting runs Monday through Saturday; Sundays and holidays are automatically skipped.
            </p>
        </div>
    </div>

    <!-- Delivery Logs Table -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Email Delivery & Run Logs (Report Runs)</h2>
            <span class="text-xs text-slate-500">Showing recent 20 runs</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Run ID</th>
                        <th class="p-3">Report Date</th>
                        <th class="p-3">Scope</th>
                        <th class="p-3">Recipients</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Sent At</th>
                        <th class="p-3 text-right">PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reportRuns as $run)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 font-mono font-bold text-slate-700">#{{ $run->id }}</td>
                        <td class="p-3 font-semibold text-slate-900">{{ \Carbon\Carbon::parse($run->report_date)->format('D, d M Y') }}</td>
                        <td class="p-3">{{ $run->tehsil ? $run->tehsil->name : 'All Tehsils' }}</td>
                        <td class="p-3 font-bold">{{ $run->recipients_count }} sent</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $run->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($run->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ $run->status }}
                            </span>
                        </td>
                        <td class="p-3 text-slate-500">{{ $run->sent_at ? $run->sent_at->format('d M H:i:s') : 'N/A' }}</td>
                        <td class="p-3 text-right">
                            @if($run->pdf_path)
                            <a href="{{ route('reports.pdf', ['date' => $run->report_date, 'tehsil_id' => $run->tehsil_id]) }}" target="_blank"
                               class="text-brand-600 hover:text-brand-700 font-bold">
                                View PDF
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">No report runs recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
