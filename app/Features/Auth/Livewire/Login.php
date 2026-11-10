<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\LoginUserAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Masuk')]
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate(LoginUserAction $action)
    {
        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($this->username).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('username', 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.');

            return null;
        }

        if (! $action->execute($this->username, $this->password, $this->remember)) {
            RateLimiter::hit($throttleKey);
            $this->addError('username', 'Username/email atau password salah.');

            return null;
        }

        RateLimiter::clear($throttleKey);

        return $this->redirectRoute(Auth::user()->isAdmin() ? 'admin.users.index' : 'todos.index');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
