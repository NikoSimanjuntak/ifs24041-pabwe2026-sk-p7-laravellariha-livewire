<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold">Todolist Saya</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openCreate" id="todo-create"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Tambah</button>
            <button type="button" wire:click="openAi" id="todo-ai"
                    class="rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Generate AI</button>
            <button type="button" wire:click="export" id="todo-export"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-100">Export Excel</button>
        </div>
    </div>

    <x-flash />

    <form wire:submit="import" class="mb-6 flex flex-wrap items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-white p-4 text-sm">
        <label for="todo-import-file" class="font-medium">Import Excel/CSV:</label>
        <input id="todo-import-file" type="file" wire:model="importFile" accept=".xlsx,.xls,.csv">
        <button type="submit" id="todo-import" class="rounded-lg bg-gray-800 px-3 py-1.5 text-white hover:bg-gray-900" wire:loading.attr="disabled" wire:target="importFile">Import</button>
        <span class="text-xs text-gray-500">Kolom: Judul, Deskripsi, Status, Jatuh Tempo</span>
        @error('importFile') <p class="w-full text-sm text-red-600">{{ $message }}</p> @enderror
        @if ($importErrors !== [])
            <ul class="w-full list-disc pl-5 text-sm text-red-600">
                @foreach ($importErrors as $error)
                    <li wire:key="import-error-{{ $loop->index }}">{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </form>

    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari todo..." id="todo-search"
               class="flex-1 min-w-48 rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <select wire:model.live="statusFilter" id="todo-status-filter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" wire:key="filter-{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-3">
        @forelse ($todos as $todo)
            <div wire:key="todo-{{ $todo->id }}" class="flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-4">
                <button type="button" wire:click="toggleComplete({{ $todo->id }})" title="Tandai selesai/belum"
                        class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 {{ $todo->isCompleted() ? 'border-green-500 bg-green-500 text-white' : 'border-gray-300' }}">
                    @if ($todo->isCompleted()) &#10003; @endif
                </button>
                @if ($todo->coverUrl())
                    <img src="{{ $todo->coverUrl() }}" alt="Cover" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @endif
                <div class="min-w-0 flex-1">
                    <a href="{{ route('todos.show', $todo) }}" class="font-semibold hover:text-indigo-600 {{ $todo->isCompleted() ? 'line-through text-gray-400' : '' }}">{{ $todo->title }}</a>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full px-2 py-0.5 {{ $todo->status->badgeClass() }}">{{ $todo->status->label() }}</span>
                        @if ($todo->due_date)
                            <span class="{{ $todo->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-500' }}">Jatuh tempo: {{ $todo->due_date->format('d M Y') }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 gap-2 text-sm">
                    <button type="button" wire:click="openEdit({{ $todo->id }})" class="rounded-lg border border-gray-300 px-3 py-1 hover:bg-gray-100">Edit</button>
                    <button type="button" wire:click="delete({{ $todo->id }})" wire:confirm="Hapus todo ini?" class="rounded-lg border border-red-200 px-3 py-1 text-red-600 hover:bg-red-50">Hapus</button>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">Belum ada todo.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $todos->links() }}</div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" id="todo-form-modal">
            <form wire:submit="save" class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold">{{ $editingId ? 'Edit Todo' : 'Tambah Todo' }}</h2>
                <div>
                    <label for="todo-title" class="block text-sm font-medium mb-1">Judul</label>
                    <input id="todo-title" type="text" wire:model="title" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="todo-description" class="block text-sm font-medium mb-1">Deskripsi (Markdown)</label>
                    <textarea id="todo-description" rows="4" wire:model="description" class="w-full rounded-lg border border-gray-300 px-3 py-2"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="todo-status" class="block text-sm font-medium mb-1">Status</label>
                        <select id="todo-status" wire:model="status" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" wire:key="form-status-{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="todo-due-date" class="block text-sm font-medium mb-1">Jatuh tempo</label>
                        <input id="todo-due-date" type="date" wire:model="dueDate" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                        @error('dueDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Batal</button>
                    <button type="submit" id="todo-save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan</button>
                </div>
            </form>
        </div>
    @endif

    @if ($showAi)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" id="todo-ai-modal">
            <div class="max-h-[90vh] w-full max-w-2xl space-y-4 overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold">Generate Todo dengan AI</h2>
                <form wire:submit="generateAi" class="space-y-3">
                    <div>
                        <label for="ai-prompt" class="block text-sm font-medium mb-1">Permintaan</label>
                        <textarea id="ai-prompt" rows="3" wire:model="aiPrompt" placeholder="Contoh: Rencana belajar Laravel selama 3 hari"
                                  class="w-full rounded-lg border border-gray-300 px-3 py-2"></textarea>
                        @error('aiPrompt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end gap-3">
                        <div>
                            <label for="ai-count" class="block text-sm font-medium mb-1">Jumlah item (1-10)</label>
                            <input id="ai-count" type="number" min="1" max="10" wire:model="aiCount" class="w-28 rounded-lg border border-gray-300 px-3 py-2">
                            @error('aiCount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" id="ai-generate" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="generateAi">
                            <span wire:loading.remove wire:target="generateAi">Generate</span>
                            <span wire:loading wire:target="generateAi">Memproses...</span>
                        </button>
                    </div>
                </form>

                @if ($aiItems !== [])
                    <div class="space-y-2" id="ai-preview">
                        <h3 class="font-semibold">Preview - pilih item yang ingin disimpan</h3>
                        @foreach ($aiItems as $index => $item)
                            <label wire:key="ai-item-{{ $index }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3">
                                <input type="checkbox" wire:model="aiSelected" value="{{ $index }}" class="mt-1">
                                <span class="min-w-0">
                                    <span class="block font-medium">{{ $item['title'] ?? '' }}</span>
                                    @if (! empty($item['description']))
                                        <span class="block text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($item['description'], 160) }}</span>
                                    @endif
                                    <span class="block text-xs text-gray-500">{{ \App\Features\Todo\Enums\TodoStatus::tryFrom($item['status'] ?? '')?->label() }}@if (! empty($item['due_date'])) &middot; {{ $item['due_date'] }} @endif</span>
                                </span>
                            </label>
                        @endforeach
                        @error('aiSelected') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="closeAi" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Tutup</button>
                    @if ($aiItems !== [])
                        <button type="button" wire:click="saveAi" id="ai-save" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">Simpan terpilih</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
