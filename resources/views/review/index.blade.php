@extends('layouts.app', ['title' => 'Tehsil Returns Review'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ commentModalOpen: false, returnActionUrl: '', returnVillageName: '', activePhotoUrl: null }">
    <!-- Header with Tehsil Selector -->
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tehsil Returns Quality Review</h1>
            <p class="mt-1 text-sm text-slate-500">Review field returns, inspect geotagged photos, approve valid records, or return for revision.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-3">
            @if($pendingApprovalCount > 0)
            <form method="POST" action="{{ route('review.approve-all') }}" onsubmit="return confirm('Approve all {{ $pendingApprovalCount }} pending returns for this Tehsil?')">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <input type="hidden" name="tehsil_id" value="{{ $selectedTehsilId }}">
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 shadow-md transition">
                    ✓ Approve All Pending ({{ $pendingApprovalCount }})
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="mt-6 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('review.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-700">Date:</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                       class="text-xs rounded-xl border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 bg-slate-50 font-mono">
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-700">Tehsil:</label>
                <select name="tehsil_id" onchange="this.form.submit()"
                        class="text-xs rounded-xl border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 bg-slate-50 font-semibold">
                    @foreach($tehsils as $t)
                        <option value="{{ $t->id }}" {{ $selectedTehsilId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-slate-700">Approval:</label>
                <select name="approval_status" onchange="this.form.submit()"
                        class="text-xs rounded-xl border-slate-300 shadow-sm focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 bg-slate-50">
                    <option value="">All</option>
                    <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending ({{ $pendingApprovalCount }})</option>
                    <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved ({{ $approvedCount }})</option>
                    <option value="returned" {{ request('approval_status') === 'returned' ? 'selected' : '' }}>Returned ({{ $returnedCount }})</option>
                </select>
            </div>
        </form>

        <div class="flex items-center gap-3 text-xs font-semibold">
            <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">Pending: {{ $pendingApprovalCount }}</span>
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Approved: {{ $approvedCount }}</span>
            <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200">Unsubmitted: {{ $unsubmittedSites->count() }}</span>
        </div>
    </div>

    <!-- Returns List -->
    <div class="mt-6 space-y-4">
        @forelse($returns as $ret)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-slate-300 transition">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Site & Return Info -->
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs font-bold bg-slate-900 text-white px-2 py-0.5 rounded">
                            {{ $ret->site->site_code }}
                        </span>
                        <h3 class="text-base font-bold text-slate-900">{{ $ret->site->village_name }}</h3>
                        @if($ret->site->priority)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white shadow-xs" style="background-color: {{ $ret->site->priority->colour }}">
                            {{ $ret->site->priority->name }}
                        </span>
                        @endif

                        <!-- Status Badge -->
                        @if($ret->status === 'active')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                Active • {{ $ret->labour_count }} Labour • {{ $ret->machinery_count }} Machinery
                            </span>
                        @elseif($ret->status === 'dormant')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                Dormant • {{ $ret->reason->label ?? 'Unstated reason' }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                Completed
                            </span>
                        @endif

                        <!-- Geofence Flag -->
                        @if(!$ret->geofence_ok)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                ⚠ Outside Geofence ({{ round($ret->distance_from_site_m) }}m)
                            </span>
                        @endif

                        @if($ret->is_late)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                Late Submission
                            </span>
                        @endif
                    </div>

                    <!-- Work Done or Idle Reason Details -->
                    <div class="mt-2 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        @if($ret->status === 'active')
                            <div><strong>Work Done:</strong> {{ $ret->work_done }}</div>
                            @if(!empty($ret->machinery_types))
                                <div class="mt-1 text-slate-500"><strong>Machinery deployed:</strong> {{ implode(', ', $ret->machinery_types) }}</div>
                            @endif
                        @elseif($ret->status === 'dormant')
                            <div><strong>Idle Reason:</strong> [{{ $ret->reason->code ?? 'N/A' }}] {{ $ret->reason->label ?? 'Unstated' }}</div>
                            <div class="mt-0.5 text-slate-500"><strong>Idle since:</strong> {{ $ret->idle_since?->format('d M Y') ?? 'N/A' }}</div>
                        @endif
                        @if($ret->remarks)
                            <div class="mt-1 text-slate-500"><strong>Remarks:</strong> {{ $ret->remarks }}</div>
                        @endif
                        @if($ret->review_comment && $ret->approval_status === 'returned')
                            <div class="mt-1 text-rose-700 font-medium"><strong>In-Charge Revision Note:</strong> {{ $ret->review_comment }}</div>
                        @endif
                    </div>

                    <!-- Submitter metadata -->
                    <div class="mt-2 text-[11px] text-slate-400 flex flex-wrap gap-x-4">
                        <span>Submitted by: <strong class="text-slate-600">{{ $ret->submitter->name ?? 'Field Staff' }}</strong></span>
                        <span>Visit Time: <strong class="text-slate-600">{{ $ret->visit_time ?? '-' }}</strong></span>
                        <span>GPS: <strong class="text-slate-600 font-mono">{{ round($ret->latitude, 4) }}, {{ round($ret->longitude, 4) }}</strong> (±{{ round($ret->gps_accuracy_m) }}m)</span>
                    </div>
                </div>

                <!-- Photos thumbnails (REV-1) -->
                <div class="flex items-center gap-2 overflow-x-auto py-1">
                    @forelse($ret->photos as $ph)
                    <div class="w-16 h-16 rounded-xl overflow-hidden border border-slate-200 cursor-pointer shadow-xs shrink-0 hover:opacity-80 transition"
                         @click="activePhotoUrl = '{{ Storage::url($ph->path) }}'">
                        <img src="{{ Storage::url($ph->path) }}" class="w-full h-full object-cover">
                    </div>
                    @empty
                    <span class="text-[11px] text-slate-400 italic">No photos</span>
                    @endforelse
                </div>

                <!-- Approval Actions (REV-1, REV-2, REV-4) -->
                <div class="flex sm:flex-col items-center justify-end gap-2 shrink-0">
                    @if($ret->approval_status === 'approved')
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 text-center">
                            ✓ Approved
                        </span>
                    @elseif($ret->approval_status === 'returned')
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-100 text-rose-800 text-center">
                            ↺ Returned
                        </span>
                        <form method="POST" action="{{ route('review.unlock', $ret) }}">
                            @csrf
                            <button type="submit" class="text-[11px] text-slate-600 hover:text-slate-900 underline font-medium">
                                Re-unlock
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('review.approve', $ret) }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-500 shadow-sm transition">
                                Approve
                            </button>
                        </form>
                        <button type="button"
                                @click="returnActionUrl = '{{ route('review.return', $ret) }}'; returnVillageName = '{{ $ret->site->village_name }}'; commentModalOpen = true"
                                class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 text-rose-700 hover:bg-rose-50 transition border border-slate-200">
                            Return...
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
            No returns found for the selected tehsil and date filter.
        </div>
        @endforelse
    </div>

    <!-- Return with Comment Modal (REV-1, REV-2) -->
    <div x-show="commentModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100" @click.outside="commentModalOpen = false">
            <h3 class="text-base font-bold text-slate-900">Return for Field Revision</h3>
            <p class="text-xs text-slate-500 mt-1">Specify what needs to be corrected in the return for <strong x-text="returnVillageName"></strong>.</p>

            <form :action="returnActionUrl" method="POST" class="mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Reason / Instructions for Field Officer <span class="text-rose-500">*</span></label>
                    <textarea name="review_comment" required rows="3" placeholder="e.g. Photograph unclear, please retake geotagged photo showing working labour..."
                              class="w-full rounded-xl border-slate-300 shadow-sm text-xs p-3 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50"></textarea>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="commentModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-500 shadow-sm">Confirm & Return</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div x-show="activePhotoUrl" x-cloak class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" @click="activePhotoUrl = null">
        <div class="relative max-w-4xl max-h-[90vh]">
            <img :src="activePhotoUrl" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl object-contain">
            <button type="button" class="absolute top-2 right-2 p-2 text-white bg-slate-900/80 rounded-full hover:bg-slate-800" @click="activePhotoUrl = null">
                ✕
            </button>
        </div>
    </div>
</div>
@endsection
