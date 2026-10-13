@extends('layouts.app', ['title' => 'Photo Evidence Gallery'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="{ activePhoto: null, activeMeta: null }">
    <!-- Header & Filter Bar -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Site Photo Gallery</h1>
            <p class="text-xs text-slate-500">Visual proof and progress verification from daily site returns</p>
        </div>

        <form method="GET" action="{{ route('dashboard.gallery') }}" class="flex flex-wrap items-center gap-3">
            <div>
                <label for="date" class="sr-only">Date</label>
                <input type="date" id="date" name="date" value="{{ $date }}"
                       class="text-xs font-semibold rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 p-2 border">
            </div>

            <div>
                <label for="tehsil_id" class="sr-only">Tehsil</label>
                <select id="tehsil_id" name="tehsil_id" class="text-xs font-semibold rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 p-2 border">
                    <option value="">All Tehsils</option>
                    @foreach($tehsils as $t)
                    <option value="{{ $t->id }}" {{ $tehsilId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-slate-800 transition shadow-sm">
                Filter
            </button>
            <a href="{{ route('dashboard') }}" class="px-3 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50 transition">
                Back to Dashboard
            </a>
        </form>
    </div>

    <!-- Gallery Grid -->
    @if($photos->isEmpty())
    <div class="bg-white rounded-2xl p-12 border border-slate-200 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <p class="font-medium text-slate-600">No photos found for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</p>
        <p class="text-xs mt-1">Try selecting another date or viewing all tehsils.</p>
    </div>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($photos as $photo)
            @php
                $site = $photo->dailyReturn->site ?? null;
                $photoUrl = asset('storage/' . $photo->path);
                $thumbUrl = asset('storage/' . ($photo->thumb_path ?? $photo->path));
                $meta = [
                    'village' => $site->village_name ?? 'Unknown',
                    'code' => $site->site_code ?? 'N/A',
                    'tehsil' => $site->tehsil->name ?? 'N/A',
                    'date' => \Carbon\Carbon::parse($photo->dailyReturn->report_date)->format('d M Y'),
                    'time' => $photo->dailyReturn->visit_time ?? 'N/A',
                    'lat' => $photo->latitude ?? $photo->dailyReturn->latitude,
                    'lng' => $photo->longitude ?? $photo->dailyReturn->longitude,
                    'url' => $photoUrl,
                    'site_url' => $site ? route('sites.show', $site->id) : '#'
                ];
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition group flex flex-col">
                <div class="relative aspect-square overflow-hidden bg-slate-100 cursor-pointer"
                     @click="activePhoto = '{{ $photoUrl }}'; activeMeta = {{ json_encode($meta) }}">
                    <img src="{{ $thumbUrl }}" 
                         alt="{{ $site->village_name ?? 'Site' }}" 
                         loading="lazy"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                    </div>
                    @if($site && $site->priority)
                    <span class="absolute top-2 left-2 text-[10px] font-bold px-1.5 py-0.5 rounded text-white shadow" style="background-color: {{ $site->priority->colour }}">
                        P-{{ $site->priority->code }}
                    </span>
                    @endif
                </div>

                <div class="p-2.5 flex-1 flex flex-col justify-between text-xs">
                    <div>
                        <div class="font-bold text-slate-900 truncate" title="{{ $site->village_name ?? 'Unknown' }}">
                            {{ $site->village_name ?? 'Unknown' }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono flex items-center justify-between">
                            <span>{{ $site->site_code ?? 'N/A' }}</span>
                            <span>{{ $site->tehsil->name ?? '' }}</span>
                        </div>
                    </div>

                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>{{ \Carbon\Carbon::parse($photo->dailyReturn->report_date)->format('d M') }}</span>
                        <a href="{{ $photoUrl }}" download class="text-brand-600 hover:text-brand-700 font-semibold flex items-center gap-0.5">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Save
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $photos->links() }}
    </div>
    @endif

    <!-- Lightbox Modal -->
    <div x-show="activePhoto" x-cloak class="fixed inset-0 z-50 bg-black/95 flex flex-col items-center justify-center p-4" @click.self="activePhoto = null">
        <div class="relative max-w-4xl max-h-[85vh] bg-slate-950 rounded-2xl overflow-hidden shadow-2xl flex flex-col">
            <div class="p-3 bg-slate-900/90 text-white flex items-center justify-between border-b border-slate-800">
                <template x-if="activeMeta">
                    <div class="flex items-center gap-3 text-xs">
                        <span class="font-bold text-sm text-white" x-text="activeMeta.village"></span>
                        <span class="text-slate-400 font-mono" x-text="'(' + activeMeta.code + ')'"></span>
                        <span class="text-emerald-400" x-text="activeMeta.tehsil"></span>
                        <span class="text-slate-400" x-text="activeMeta.date + ' ' + activeMeta.time"></span>
                    </div>
                </template>
                <div class="flex items-center gap-2">
                    <template x-if="activeMeta">
                        <a :href="activeMeta.site_url" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200">
                            Site Page
                        </a>
                    </template>
                    <a :href="activePhoto" download class="px-2.5 py-1 rounded bg-brand-600 hover:bg-brand-500 text-xs font-bold text-white flex items-center gap-1">
                        Download
                    </a>
                    <button @click="activePhoto = null" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <div class="p-2 flex items-center justify-center bg-black">
                <img :src="activePhoto" alt="Site Photo Preview" class="max-w-full max-h-[75vh] object-contain">
            </div>
            <template x-if="activeMeta && activeMeta.lat">
                <div class="p-2 bg-slate-900 text-slate-400 text-[11px] font-mono text-center border-t border-slate-800">
                    GPS Coordinates: <span class="text-slate-200 font-semibold" x-text="activeMeta.lat + ', ' + activeMeta.lng"></span>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
