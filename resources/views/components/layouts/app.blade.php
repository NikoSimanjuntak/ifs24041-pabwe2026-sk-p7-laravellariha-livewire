<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .markdown-body h1,.markdown-body h2,.markdown-body h3{font-weight:700;margin:.75rem 0 .5rem}
        .markdown-body h1{font-size:1.5rem}.markdown-body h2{font-size:1.25rem}.markdown-body h3{font-size:1.1rem}
        .markdown-body p{margin:.5rem 0}.markdown-body ul{list-style:disc;padding-left:1.5rem;margin:.5rem 0}
        .markdown-body ol{list-style:decimal;padding-left:1.5rem;margin:.5rem 0}
        .markdown-body a{color:#4f46e5;text-decoration:underline}
        .markdown-body code{background:#f3f4f6;padding:.1rem .3rem;border-radius:.25rem}
        .markdown-body pre{background:#f3f4f6;padding:.75rem;border-radius:.5rem;overflow:auto}
        .markdown-body blockquote{border-left:4px solid #d1d5db;padding-left:1rem;color:#4b5563}
    </style>
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
@auth
    <div class="min-h-screen md:flex">
        <aside class="bg-white border-b md:border-b-0 md:border-r border-gray-200 md:w-64 md:min-h-screen p-4 flex md:flex-col gap-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-indigo-600 text-lg">
                <img src="{{ asset('favicon.svg') }}" alt="" class="h-7 w-7"> {{ config('app.name') }}
            </a>
            <nav class="flex md:flex-col gap-1 flex-1 text-sm">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('admin.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">Pengguna</a>
                @else
                    <a href="{{ route('todos.index') }}" class="rounded-lg px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('todos.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">Todolist</a>
                @endif
                <a href="{{ route('profile') }}" class="rounded-lg px-3 py-2 hover:bg-gray-100 {{ request()->routeIs('profile') ? 'bg-indigo-50 text-indigo-700 font-semibold' : '' }}">Profil</a>
            </nav>
            <div class="flex items-center gap-3 text-sm">
                @if (auth()->user()->profilePhotoUrl())
                    <img src="{{ auth()->user()->profilePhotoUrl() }}" alt="Foto profil" class="h-9 w-9 rounded-full object-cover">
                @else
                    <span class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">{{ auth()->user()->initial() }}</span>
                @endif
                <div class="min-w-0">
                    <p class="font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->role }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" id="logout-button" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-100">Keluar</button>
            </form>
        </aside>
        <main class="flex-1 p-4 md:p-8 max-w-5xl w-full mx-auto">
            {{ $slot }}
        </main>
    </div>
@else
    <main class="min-h-screen flex items-center justify-center p-4">
        {{ $slot }}
    </main>
@endauth
@livewireScripts
</body>
</html>
