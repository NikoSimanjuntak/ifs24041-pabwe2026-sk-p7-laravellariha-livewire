<div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm border border-gray-200">
    <h1 class="text-2xl font-bold mb-1">Daftar</h1>
    <p class="text-sm text-gray-500 mb-6">Buat akun member baru.</p>

    <form wire:submit="register" class="space-y-4">
        @foreach ([
            ['name', 'Nama lengkap', 'text', 'name'],
            ['username', 'Username', 'text', 'username'],
            ['email', 'Email', 'email', 'email'],
            ['password', 'Password', 'password', 'new-password'],
            ['password_confirmation', 'Konfirmasi password', 'password', 'new-password'],
        ] as [$field, $label, $type, $autocomplete])
            <div wire:key="register-{{ $field }}">
                <label for="register-{{ $field }}" class="block text-sm font-medium mb-1">{{ $label }}</label>
                <input id="register-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none">
                @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <button id="register-submit" type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Sudah punya akun? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Masuk</a>
    </p>
</div>
