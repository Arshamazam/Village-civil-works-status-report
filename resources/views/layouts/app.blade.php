<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'PRSWSSP Daily Site Progress & Reporting System' }} | PRMSC</title>
    
    <!-- PWA Manifest & Meta -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0F172A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/png" href="https://upload.wikimedia.org/wikipedia/commons/4/41/Flag_of_Punjab%2C_Pakistan.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <!-- Tailwind Play CDN for immediate flawless aesthetics + Alpine & ChartJS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        prmsc: {
                            dark: '#0f172a',
                            navy: '#1e293b',
                            accent: '#0284c7',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .touch-action-manipulation { touch-action: manipulation; }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-brand-500 selection:text-white" x-data="{ mobileMenuOpen: false }">
    @auth
    <!-- Top Navigation Header -->
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center font-bold text-white shadow-inner">
                        PW
                    </div>
                    <div>
                        <div class="font-extrabold text-sm sm:text-base tracking-tight flex items-center gap-2">
                            <span>PRSWSSP</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 font-medium border border-emerald-800/60 hidden sm:inline-block">PRMSC</span>
                        </div>
                        <p class="text-[10px] text-slate-400 leading-none">Daily Site Mobilization & Progress</p>
                    </div>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-medium">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('dashboard*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        Dashboard
                    </a>
                    
                    @hasanyrole('Super Admin|HQ Manager|Tehsil In-charge')
                    <a href="{{ route('review.index') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('review*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        Tehsil Review
                    </a>
                    @endhasanyrole

                    @hasanyrole('Super Admin|HQ Manager|Field Officer|Tehsil In-charge')
                    <a href="{{ route('field.today') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('field*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        Daily Return (Form DSM-1)
                    </a>
                    @endhasanyrole

                    <a href="{{ route('reports.view') }}" class="px-3 py-2 rounded-md transition {{ request()->routeIs('reports*') ? 'bg-slate-800 text-white font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        Reports
                    </a>

                    @hasrole('Super Admin')
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="px-3 py-2 rounded-md transition text-slate-300 hover:text-white hover:bg-slate-800/60 flex items-center gap-1">
                            <span>Admin</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 rounded-xl shadow-2xl bg-white border border-slate-100 py-2 text-slate-800 z-50">
                            <a href="{{ route('admin.sites.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Villages / Sites</a>
                            <a href="{{ route('admin.phases.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Phases & Priorities</a>
                            <a href="{{ route('admin.tehsils.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Tehsils & Contractors</a>
                            <a href="{{ route('admin.reasons.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Idle Reason Tree</a>
                            <a href="{{ route('admin.import-export.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium text-emerald-700">Excel Import / Export</a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <a href="{{ route('admin.users.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Users & Roles</a>
                            <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Cut-off & Settings</a>
                            <a href="{{ route('admin.audit-logs.index') }}" class="block px-4 py-2 text-sm hover:bg-slate-50 font-medium">Audit Logs</a>
                        </div>
                    </div>
                    @endhasrole
                </nav>

                <!-- User profile & Logout -->
                <div class="hidden md:flex items-center space-x-3">
                    <div class="text-right">
                        <div class="text-xs font-semibold text-white">{{ Auth::user()->name }}</div>
                        <div class="text-[11px] text-emerald-400 font-medium">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Logout" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 text-slate-400 hover:text-white rounded-lg focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-slate-900 border-b border-slate-800 px-4 pt-2 pb-4 space-y-1">
            <div class="pb-2 border-b border-slate-800 mb-2">
                <div class="text-sm font-semibold text-white">{{ Auth::user()->name }}</div>
                <div class="text-xs text-emerald-400">{{ Auth::user()->roles->pluck('name')->first() }} • {{ Auth::user()->email }}</div>
            </div>
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-slate-800">Dashboard</a>
            <a href="{{ route('field.today') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-emerald-400 bg-emerald-950/40 border border-emerald-900/40">Today's Daily Return (Form DSM-1)</a>
            <a href="{{ route('review.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-slate-800">Tehsil Review</a>
            <a href="{{ route('reports.view') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-slate-800">Daily Mobilization Report</a>
            @hasrole('Super Admin')
            <a href="{{ route('admin.sites.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-slate-800">Master Data & Sites</a>
            <a href="{{ route('admin.import-export.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-slate-800">Excel Import / Export</a>
            <a href="{{ route('admin.settings.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium text-slate-200 hover:bg-slate-800">Settings & Cut-off</a>
            @endhasrole
            <form method="POST" action="{{ route('logout') }}" class="pt-2">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-rose-400 hover:bg-slate-800">Sign Out</button>
            </form>
        </div>
    </header>
    @endauth

    <!-- Flash message alerts -->
    @if(session('success'))
    <div class="bg-emerald-50 border-b border-emerald-200 text-emerald-800 px-4 py-3 text-sm flex items-center justify-between">
        <div class="flex items-center gap-2 max-w-7xl mx-auto w-full">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error') || $errors->any())
    <div class="bg-rose-50 border-b border-rose-200 text-rose-800 px-4 py-3 text-sm">
        <div class="max-w-7xl mx-auto flex items-start gap-2">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <div>
                @if(session('error')) <div>{{ session('error') }}</div> @endif
                @if($errors->any())
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 text-slate-500 text-xs py-4 px-4 text-center">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="font-medium text-slate-600">
                Punjab Rural Municipal Services Company (PRMSC) • PRSWSSP
            </div>
            <div class="text-[11px] text-slate-400">
                Time Zone: Asia/Karachi (PKT, UTC+5) • Form DSM-1 Daily Mobilization System
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
