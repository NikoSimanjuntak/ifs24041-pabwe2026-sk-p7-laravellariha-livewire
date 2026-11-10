<?php

namespace App\Features\Auth\Actions;

use Illuminate\Support\Facades\Auth;

class LoginUserAction
{
    /**
     * Login menggunakan username atau email.
     */
    public function execute(string $identity, string $password, bool $remember = false): bool
    {
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $identity, 'password' => $password], $remember)) {
            return false;
        }

        session()->regenerate();

        return true;
    }
}
