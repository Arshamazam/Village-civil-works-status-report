@extends('layouts.app', ['title' => 'Excel Import & Export'])

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Village & Site Master Excel Tools</h1>
        <p class="text-sm text-slate-500">Bulk import civil works sites with preview and error checking, or export current site registers.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Export Box -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Export Master Sites List</h2>
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    Download an Excel spreadsheet (.xlsx) containing all active, completed, and suspended sites with assigned phases, packages, tehsils, priorities, and GPS coordinates.
                </p>
            </div>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.import-export.export') }}" class="w-full inline-flex justify-center items-center px-4 py-3 rounded-lg bg-blue-600 text-white font-semibold text-sm hover:bg-blue-500 transition shadow">
                    Download Master Sites (.xlsx)
                </a>
            </div>
        </div>

        <!-- Import Box -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Upload Village Master Excel</h2>
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    Upload an Excel (.xlsx / .csv) sheet. The system will perform row-level pre-validation before committing any records to the database.
                </p>
                <div class="mt-3 bg-slate-50 p-3 rounded-lg border border-slate-100 text-[11px] font-mono text-slate-600">
                    Expected columns: Phase, Tehsil, Package, Village, Priority, Deadline, Contractor, Status, Latitude, Longitude
                </div>
            </div>

            <form method="POST" action="{{ route('admin.import-export.preview') }}" enctype="multipart/form-data" class="mt-6 pt-6 border-t border-slate-100">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Select Spreadsheet</label>
                    <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                           class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                </div>
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-3 rounded-lg bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-500 transition shadow">
                    Preview and Validate Import &rarr;
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
