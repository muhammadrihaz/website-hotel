<x-layouts.guest title="Masuk">
    <div class="grid min-h-screen lg:grid-cols-[1.1fr_0.9fr]">
        <section class="relative hidden overflow-hidden bg-hotel-950 p-12 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-hotel-500/10"></div>
            <div class="relative flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-hotel-500 text-xl font-black text-white">D</div>
                <div>
                    <p class="font-bold text-white">{{ config('hotel.property.name') }}</p>
                    <p class="text-sm text-hotel-200/60">Internal Management System</p>
                </div>
            </div>
            <div class="relative max-w-xl">
                <p class="mb-5 text-sm font-bold uppercase tracking-[0.22em] text-hotel-300">Operate with clarity</p>
                <h1 class="text-5xl font-black leading-tight text-white">Satu pusat kendali untuk operasional hotel setiap hari.</h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-slate-400">Kelola kamar, staf, dan aktivitas penting dengan alur yang aman, jelas, dan mudah digunakan.</p>
            </div>
            <p class="relative text-xs text-slate-600">Akses hanya untuk staf berwenang.</p>
        </section>

        <section class="flex items-center justify-center bg-slate-50 px-5 py-12 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-hotel-700 text-xl font-black text-white">D</div>
                    <p class="font-bold text-slate-900">{{ config('hotel.property.short_name') }}</p>
                </div>
                <p class="text-sm font-semibold text-hotel-700">Selamat datang kembali</p>
                <h2 class="mt-2 text-3xl font-black text-slate-950">Masuk ke sistem</h2>
                <p class="mt-2 text-sm text-slate-500">Gunakan akun staf yang diberikan administrator.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-input" autocomplete="username" required autofocus placeholder="nama@hotel.com">
                        @error('email')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="form-label">Kata sandi</label>
                        <input id="password" name="password" type="password" class="form-input" autocomplete="current-password" required placeholder="Minimal 10 karakter">
                        @error('password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-hotel-700 focus:ring-hotel-500">
                        Ingat saya di perangkat ini
                    </label>
                    <button class="btn-primary w-full" type="submit">Masuk</button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.guest>
