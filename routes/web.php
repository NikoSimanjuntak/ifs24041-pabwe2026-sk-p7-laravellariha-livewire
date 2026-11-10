<?php

use App\Features\Admin\Livewire\AdminUserTodoList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Profile\Livewire\Profile;
use App\Features\Todo\Livewire\TodoDetail;
use App\Features\Todo\Livewire\TodoList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:1000-per-second')->group(function (): void {
    Route::get('/', function () {
        $user = Auth::user();

        if ($user === null) {
            return redirect()->route('login');
        }

        return redirect()->route($user->isAdmin() ? 'admin.users.index' : 'todos.index');
    })->name('home');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');

        Route::get('/profile', Profile::class)->name('profile');

        Route::middleware('role:member')->group(function (): void {
            Route::get('/todos', TodoList::class)->name('todos.index');
            Route::get('/todos/{todo}', TodoDetail::class)->middleware('owns.todo')->name('todos.show');
        });

        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/users', UserList::class)->name('users.index');
            Route::get('/users/{user}/todos', AdminUserTodoList::class)->name('users.todos');
        });
    });
});
