<div>
    @assets
        <link rel="stylesheet" href="https://uicdn.toastui.com/editor/3.2.2/toastui-editor.min.css">
        <script src="https://uicdn.toastui.com/editor/3.2.2/toastui-editor-all.min.js"></script>
    @endassets

    <a href="{{ route('todos.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Kembali ke daftar</a>

    <x-flash />

    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white">
        @if ($todo->coverUrl())
            <img src="{{ $todo->coverUrl() }}" alt="Cover" class="h-56 w-full object-cover" id="todo-cover">
        @endif

        <div class="p-6 space-y-5">
            @if (! $editing)
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold {{ $todo->isCompleted() ? 'line-through text-gray-400' : '' }}">{{ $todo->title }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full px-2 py-0.5 {{ $todo->status->badgeClass() }}">{{ $todo->status->label() }}</span>
                            @if ($todo->due_date)
                                <span class="{{ $todo->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-500' }}">Jatuh tempo: {{ $todo->due_date->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 text-sm">
                        <button type="button" wire:click="toggleComplete" class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-100">
                            {{ $todo->isCompleted() ? 'Tandai belum selesai' : 'Tandai selesai' }}
                        </button>
                        <button type="button" wire:click="startEdit" id="todo-edit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">Edit</button>
                        <button type="button" wire:click="delete" wire:confirm="Hapus todo ini?" class="rounded-lg border border-red-200 px-3 py-1.5 text-red-600 hover:bg-red-50">Hapus</button>
                    </div>
                </div>

                <div class="markdown-body" id="todo-description">
                    @if ($todo->description)
                        {!! $todo->descriptionHtml() !!}
                    @else
                        <p class="text-gray-400">Belum ada deskripsi.</p>
                    @endif
                </div>
            @else
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="detail-title" class="block text-sm font-medium mb-1">Judul</label>
                        <input id="detail-title" type="text" wire:model="title" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="block text-sm font-medium mb-1">Deskripsi (Markdown)</span>
                        <div wire:ignore x-data="{ editor: null }" x-init="
                            const boot = () => {
                                editor = new toastui.Editor({
                                    el: $refs.editor,
                                    height: '320px',
                                    initialEditType: 'markdown',
                                    previewStyle: 'vertical',
                                    usageStatistics: false,
                                    initialValue: $wire.description || ''
                                });
                                editor.on('change', () => { $wire.description = editor.getMarkdown(); });
                            };
                            const wait = () => (window.toastui ? boot() : setTimeout(wait, 50));
                            wait();
                        ">
                            <div x-ref="editor"></div>
                        </div>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="detail-status" class="block text-sm font-medium mb-1">Status</label>
                            <select id="detail-status" wire:model="status" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                                @foreach ($statuses as $option)
                                    <option value="{{ $option->value }}" wire:key="detail-status-{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="detail-due-date" class="block text-sm font-medium mb-1">Jatuh tempo</label>
                            <input id="detail-due-date" type="date" wire:model="dueDate" class="w-full rounded-lg border border-gray-300 px-3 py-2">
                            @error('dueDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="cancelEdit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100">Batal</button>
                        <button type="submit" id="detail-save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan</button>
                    </div>
                </form>
            @endif

            <div class="border-t border-gray-200 pt-5">
                <h2 class="mb-2 font-semibold">Cover</h2>
                <form wire:submit="uploadCover" class="flex flex-wrap items-center gap-3 text-sm">
                    <input id="detail-cover" type="file" wire:model="cover" accept="image/*">
                    <button type="submit" class="rounded-lg bg-gray-800 px-3 py-1.5 text-white hover:bg-gray-900">Unggah</button>
                    @if ($todo->cover_image_path)
                        <button type="button" wire:click="removeCover" class="rounded-lg border border-red-200 px-3 py-1.5 text-red-600 hover:bg-red-50">Hapus cover</button>
                    @endif
                    @error('cover') <p class="w-full text-red-600">{{ $message }}</p> @enderror
                </form>
            </div>
        </div>
    </div>
</div>
