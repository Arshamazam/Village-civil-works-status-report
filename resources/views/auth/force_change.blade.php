@extends('layouts.app', ['title' => 'Set New Password'])

@section('content')
<div class="min-h-[80vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-900 text-slate-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center px-4">
        <h2 class="text-2xl font-bold text-white">Change Temporary Password</h2>
        <p class="mt-2 text-sm text-slate-400">
            For security, your first login requires you to set a personal password (minimum 8 characters).
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-slate-800 border border-slate-700 py-8 px-6 sm:px-10 shadow-2xl rounded-2xl">
            <form class="space-y-6" action="{{ route('password.force_change.update') }}" method="POST">
                @csrf

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">New Password</label>
                    <div class="mt-2">
                        <input id="password" name="password" type="password" required minlength="8"
                               placeholder="Minimum 8 characters"
                               class="block w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Confirm New Password</label>
                    <div class="mt-2">
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                               placeholder="Re-enter password"
                               class="block w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition">
                        Save Password and Continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
