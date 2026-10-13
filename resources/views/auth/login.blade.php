@extends('layouts.app', ['title' => 'Sign In'])

@section('content')
<div class="min-h-[85vh] flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center px-4">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-600 shadow-xl shadow-emerald-500/20 text-white font-extrabold text-2xl mb-4 border border-emerald-400/30">
            PR
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
            PRSWSSP Civil Works
        </h2>
        <p class="mt-2 text-xs sm:text-sm text-slate-400">
            Daily Site Mobilization & Progress Reporting System (Form DSM-1)
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 space-y-4">
        @if(isset($currentUser) && $currentUser)
        <!-- Active Session Card -->
        <div class="bg-emerald-950/80 border border-emerald-500/40 rounded-2xl p-4 text-emerald-200 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-wider font-bold text-emerald-400">Currently Logged In</div>
                    <div class="font-bold text-white text-base mt-0.5">{{ $currentUser->name }}</div>
                    <div class="text-xs text-emerald-300 font-mono">{{ $currentUser->email }}</div>
                    <div class="text-[11px] text-emerald-400 mt-0.5">Role: <strong>{{ $currentUser->roles->pluck('name')->first() ?? 'User' }}</strong></div>
                </div>
                <div class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></div>
            </div>
            <div class="mt-3.5 pt-3 border-t border-emerald-800/60 flex items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="flex-1 text-center py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow transition">
                    Go to Dashboard &rarr;
                </a>
                <a href="{{ route('logout') }}"
                   class="text-center py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-rose-300 font-semibold text-xs border border-slate-700 transition">
                    Sign Out / Switch
                </a>
            </div>
        </div>
        @endif

        <!-- Login Form Card -->
        <div class="bg-slate-800/90 backdrop-blur border border-slate-700/80 py-8 px-6 sm:px-10 shadow-2xl rounded-2xl">
            <!-- Inline Errors -->
            @if($errors->any())
            <div class="mb-5 p-3.5 rounded-xl bg-rose-950/70 border border-rose-500/50 text-rose-200 text-xs">
                <div class="font-bold flex items-center gap-1.5 text-rose-300 mb-1">
                    <svg class="w-4 h-4 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    Unable to Sign In
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form id="loginForm" class="space-y-5" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                        Official Email Address
                    </label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="{{ old('email') }}"
                               placeholder="e.g. hq@prmsc.punjab.gov.pk"
                               class="block w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Password
                        </label>
                    </div>
                    <div class="mt-2">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               placeholder="••••••••"
                               class="block w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" checked
                               class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-700 rounded bg-slate-900">
                        <label for="remember" class="ml-2 block text-xs text-slate-300">
                            Stay signed in on this device (30 days)
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" id="submitBtn"
                            class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-600/30 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150 active:scale-[0.98] cursor-pointer">
                        Sign In to Reporting Portal
                    </button>
                </div>
            </form>

            <!-- 1-Click Fast Login Cards -->
            <div class="mt-6 pt-6 border-t border-slate-700/60">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs text-slate-400 font-semibold">1-Click Quick Login Accounts:</span>
                    <span class="text-[10px] text-emerald-400 font-mono">Password: Password123!</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px] font-mono text-slate-300">
                    <!-- HQ Manager / CEO -->
                    <button type="button" 
                            onclick="fastLogin('hq@prmsc.punjab.gov.pk', 'Password123!')"
                            class="text-left bg-slate-900/90 p-2.5 rounded-xl border border-emerald-600/40 hover:border-emerald-400 hover:bg-slate-900 transition cursor-pointer group">
                        <div class="text-emerald-400 font-sans font-bold group-hover:underline flex items-center justify-between">
                            <span>CEO / HQ Manager</span>
                            <span class="text-[9px] bg-emerald-950 text-emerald-300 px-1 py-0.5 rounded border border-emerald-800">1-Click</span>
                        </div>
                        <div class="text-slate-300 mt-1 truncate">hq@prmsc.punjab.gov.pk</div>
                    </button>

                    <!-- Super Admin -->
                    <button type="button" 
                            onclick="fastLogin('admin@prmsc.punjab.gov.pk', 'Password123!')"
                            class="text-left bg-slate-900/90 p-2.5 rounded-xl border border-slate-700/60 hover:border-emerald-500 hover:bg-slate-900 transition cursor-pointer group">
                        <div class="text-emerald-400 font-sans font-bold group-hover:underline flex items-center justify-between">
                            <span>Super Admin</span>
                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1 py-0.5 rounded">1-Click</span>
                        </div>
                        <div class="text-slate-300 mt-1 truncate">admin@prmsc.punjab.gov.pk</div>
                    </button>

                    <!-- Tehsil In-charge -->
                    <button type="button" 
                            onclick="fastLogin('incharge@prmsc.punjab.gov.pk', 'Password123!')"
                            class="text-left bg-slate-900/90 p-2.5 rounded-xl border border-slate-700/60 hover:border-emerald-500 hover:bg-slate-900 transition cursor-pointer group">
                        <div class="text-emerald-400 font-sans font-bold group-hover:underline flex items-center justify-between">
                            <span>Tehsil In-charge</span>
                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1 py-0.5 rounded">1-Click</span>
                        </div>
                        <div class="text-slate-300 mt-1 truncate">incharge@prmsc.punjab.gov.pk</div>
                    </button>

                    <!-- Field Officer -->
                    <button type="button" 
                            onclick="fastLogin('field@prmsc.punjab.gov.pk', 'Password123!')"
                            class="text-left bg-slate-900/90 p-2.5 rounded-xl border border-slate-700/60 hover:border-emerald-500 hover:bg-slate-900 transition cursor-pointer group">
                        <div class="text-emerald-400 font-sans font-bold group-hover:underline flex items-center justify-between">
                            <span>Field Operator</span>
                            <span class="text-[9px] bg-slate-800 text-slate-300 px-1 py-0.5 rounded">1-Click</span>
                        </div>
                        <div class="text-slate-300 mt-1 truncate">field@prmsc.punjab.gov.pk</div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fastLogin(email, password) {
    const emailField = document.getElementById('email');
    const pwField = document.getElementById('password');
    const form = document.getElementById('loginForm');
    if (emailField && pwField && form) {
        emailField.value = email;
        pwField.value = password;
        form.submit();
    }
}
</script>
@endsection
