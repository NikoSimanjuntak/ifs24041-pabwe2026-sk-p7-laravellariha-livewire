<div>
    <h1 class="mb-6 text-2xl font-bold">Manajemen Pengguna</h1>

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, username, atau email..." id="user-search"
           class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Username</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3 text-right">Todo</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->username }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->role }}</td>
                        <td class="px-4 py-3 text-right">{{ $user->todos_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.todos', $user) }}" class="text-indigo-600 hover:underline">Lihat todo</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</div>
