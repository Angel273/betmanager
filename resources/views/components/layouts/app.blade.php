<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Bet Manager' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- ApexCharts CDN (For Dashboard Graphs) -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Style & JS Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Instrument Sans', 'Outfit', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }
        .glassmorphism {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glassmorphism-hover:hover {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.6);
        }
    </style>
</head>
<body class="h-full antialiased selection:bg-indigo-500 selection:text-white overflow-x-hidden">

    @auth
        <div x-data="{ mobileMenuOpen: false }" class="min-h-screen flex flex-col md:flex-row bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-950/20 via-slate-950 to-slate-950">
            <!-- Sidebar -->
            <aside class="w-full md:w-64 glassmorphism border-b md:border-b-0 md:border-r border-slate-800 flex flex-col justify-between shrink-0">
                <div>
                    <!-- Logo / Brand & Hamburger -->
                    <div class="p-6 flex items-center justify-between border-b border-slate-800/60">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                                <i class="fa-solid fa-dice-d20 text-lg"></i>
                            </div>
                            <div>
                                <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-white via-indigo-200 to-indigo-400 bg-clip-text text-transparent">BET PATH</span>
                                <span class="block text-[10px] text-indigo-400 font-medium tracking-widest uppercase">App Manager</span>
                            </div>
                        </div>
                        <!-- Hamburger Button (Only Mobile) -->
                        <button x-on:click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/50 transition">
                            <i class="fa-solid text-lg" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
                        </button>
                    </div>

                    <!-- Navigation Links -->
                    <nav :class="mobileMenuOpen ? 'block' : 'hidden md:block'" class="p-4 space-y-1">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200 {{ request()->routeIs('dashboard') ? 'bg-indigo-600/20 text-indigo-300 border-l-4 border-indigo-500 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                            <i class="fa-solid fa-chart-line w-5"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('bets.register') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200 {{ request()->routeIs('bets.register') ? 'bg-indigo-600/20 text-indigo-300 border-l-4 border-indigo-500 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                            <i class="fa-solid fa-receipt w-5"></i>
                            <span>Registrar Apuesta</span>
                        </a>

                        <a href="{{ route('bet-paths') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200 {{ request()->routeIs('bet-paths') ? 'bg-indigo-600/20 text-indigo-300 border-l-4 border-indigo-500 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                            <i class="fa-solid fa-route w-5"></i>
                            <span>Bet Path</span>
                        </a>

                        <!-- Admin Panel Section -->
                        @if(auth()->user()->is_admin)
                            <div class="pt-6 pb-2 px-4">
                                <span class="text-[10px] font-bold tracking-wider text-slate-500 uppercase">Administración</span>
                            </div>

                            <a href="{{ route('admin.allowed-emails') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200 {{ request()->routeIs('admin.allowed-emails') ? 'bg-indigo-600/20 text-indigo-300 border-l-4 border-indigo-500 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                <i class="fa-solid fa-envelope-circle-check w-5"></i>
                                <span>Correos Permitidos</span>
                            </a>

                            <a href="{{ route('admin.catalog') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl transition duration-200 {{ request()->routeIs('admin.catalog') ? 'bg-indigo-600/20 text-indigo-300 border-l-4 border-indigo-500 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                <i class="fa-solid fa-folder-open w-5"></i>
                                <span>Catálogos Deportivos</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <!-- User Profile & Logout -->
                <div :class="mobileMenuOpen ? 'block' : 'hidden md:block'" class="p-4 border-t border-slate-800/60">
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-900/60 border border-slate-800/40">
                        <div class="flex items-center gap-3 min-w-0">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="h-9 w-9 rounded-full ring-2 ring-indigo-500/20">
                            @else
                                <div class="h-9 w-9 rounded-full bg-indigo-500/30 flex items-center justify-center text-indigo-200 font-bold shrink-0">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <span class="block text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</span>
                                <span class="block text-[10px] text-indigo-400 uppercase tracking-widest font-semibold">
                                    {{ auth()->user()->is_admin ? 'Admin' : 'Usuario' }}
                                </span>
                            </div>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="p-2 text-slate-400 hover:text-red-400 rounded-lg hover:bg-red-500/10 transition duration-200" title="Cerrar sesión">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 p-6 md:p-10 overflow-y-auto max-h-screen">
                {{ $slot }}
            </main>
        </div>
    @else
        <!-- Guest View -->
        {{ $slot }}
    @endauth

</body>
</html>
