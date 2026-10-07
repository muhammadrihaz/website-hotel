<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body x-data="{ sidebarOpen: false, profileOpen: false }" class="min-h-screen">
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" @click="sidebarOpen = false"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 transform bg-slate-950 text-slate-200 transition-transform duration-200 lg:translate-x-0">
        <x-sidebar />
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Buka navigasi">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium uppercase tracking-wider text-hotel-700">Hotel Internal Management</p>
                        <h1 class="truncate text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                </div>

                <div class="relative" @click.outside="profileOpen = false">
                    <button type="button" class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left hover:bg-slate-100" @click="profileOpen = !profileOpen">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-hotel-100 text-sm font-bold text-hotel-800">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
                        <span class="hidden sm:block">
                            <span class="block max-w-40 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                            <span class="block max-w-40 truncate text-xs text-slate-500">{{ auth()->user()->getRoleNames()->join(', ') }}</span>
                        </span>
                    </button>
                    <div x-cloak x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-700 hover:bg-rose-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>

    <x-confirmation-dialog />

    @livewireScripts
</body>
</html>
