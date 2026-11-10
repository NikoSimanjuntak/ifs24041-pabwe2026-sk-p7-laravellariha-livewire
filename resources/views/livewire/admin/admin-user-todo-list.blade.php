<div>
    <a href="{{ route('admin.users.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Kembali ke pengguna</a>

    <div class="mt-3 mb-6">
        <h1 class="text-2xl font-bold">Todolist {{ $user->name }}</h1>
        <p class="text-sm text-gray-500">&#64;{{ $user->username }} &middot; mode baca saja (read-only)</p>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari todo..." id="admin-todo-search"
               class="flex-1 min-w-48 rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <select wire:model.live="statusFilter" id="admin-todo-status-filter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" wire:key="admin-filter-{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-3">
        @forelse ($todos as $todo)
            <div wire:key="admin-todo-{{ $todo->id }}" class="flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-4">
                @if ($todo->coverUrl())
                    <img src="{{ $todo->coverUrl() }}" alt="Cover" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-semibold {{ $todo->isCompleted() ? 'line-through text-gray-400' : '' }}">{{ $todo->title }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full px-2 py-0.5 {{ $todo->status->badgeClass() }}">{{ $todo->status->label() }}</span>
                        @if ($todo->due_date)
                            <span class="text-gray-500">Jatuh tempo: {{ $todo->due_date->format('d M Y') }}</span>
                        @endif
                    </div>
                    @if ($todo->description)
                        <div class="markdown-body mt-2 text-sm">{!! $todo->descriptionHtml() !!}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">Pengguna ini belum memiliki todo.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $todos->links() }}</div>
</div>
