<div class="space-y-6">
    <h1 class="text-2xl font-bold">Profil</h1>

    <x-flash />

    <section class="rounded-2xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 font-semibold">Foto profil</h2>
        <div class="flex flex-wrap items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="Foto profil" class="h-20 w-20 rounded-full object-cover" id="profile-photo">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100 text-2xl font-semibold text-indigo-700">{{ $user->initial() }}</span>
            @endif
            <form wire:submit="savePhoto" class="flex flex-wrap items-center gap-3 text-sm">
                <input id="profile-photo-input" type="file" wire:model="photo" accept="image/*">
                <button type="submit" class="rounded-lg bg-gray-800 px-3 py-1.5 text-white hover:bg-gray-900">Unggah</button>
                @if ($user->profile_photo_path)
                    <button type="button" wire:click="removePhoto" class="rounded-lg border border-red-200 px-3 py-1.5 text-red-600 hover:bg-red-50">Hapus foto</button>
                @endif
                @error('photo') <p class="w-full text-red-600">{{ $message }}</p> @enderror
            </form>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 font-semibold">Data diri</h2>
        <form wire:submit="saveProfile" class="space-y-4">
            @foreach ([['name', 'Nama lengkap', 'text'], ['username', 'Username', 'text'], ['email', 'Email', 'email']] as [$field, $label, $type])
                <div wire:key="profile-{{ $field }}">
                    <label for="profile-{{ $field }}" class="block text-sm font-medium mb-1">{{ $label }}</label>
                    <input id="profile-{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button type="submit" id="profile-save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan</button>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6">
        <h2 class="mb-4 font-semibold">Ubah password</h2>
        <form wire:submit="savePassword" class="space-y-4">
            @foreach ([['current_password', 'Password saat ini'], ['password', 'Password baru'], ['password_confirmation', 'Konfirmasi password baru']] as [$field, $label])
                <div wire:key="profile-{{ $field }}">
                    <label for="profile-{{ $field }}" class="block text-sm font-medium mb-1">{{ $label }}</label>
                    <input id="profile-{{ $field }}" type="password" wire:model="{{ $field }}" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button type="submit" id="password-save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Ubah password</button>
        </form>
    </section>
</div>
