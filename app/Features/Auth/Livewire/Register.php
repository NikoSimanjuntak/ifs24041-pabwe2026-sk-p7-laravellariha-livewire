<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\RegisterUserAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Daftar')]
class Register extends Component
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(RegisterUserAction $action)
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $action->execute($data);

        Auth::login($user);
        session()->regenerate();

        return $this->redirectRoute('todos.index');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
