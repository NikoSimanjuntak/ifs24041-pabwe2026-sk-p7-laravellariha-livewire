<div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm border border-gray-200">
    <h1 class="text-2xl font-bold mb-1">Masuk</h1>
    <p class="text-sm text-gray-500 mb-6">Gunakan username atau email Anda.</p>

    <form wire:submit="authenticate" class="space-y-4">
        <div>
            <label for="login-username" class="block text-sm font-medium mb-1">Username atau Email</label>
            <input id="login-username" type="text" wire:model="username" autocomplete="username" autofocus
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none">
            @error('username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="login-password" class="block text-sm font-medium mb-1">Password</label>
            <input id="login-password" type="password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember" class="rounded border-gray-300"> Ingat saya
        </label>
        <button id="login-submit" type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Masuk</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Belum punya akun? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Daftar</a>
    </p>
</div>
