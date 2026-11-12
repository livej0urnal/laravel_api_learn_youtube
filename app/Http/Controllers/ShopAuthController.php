<?php

namespace App\Http\Controllers;

use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopAuthController extends Controller
{
    public function register(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['bail', 'required', 'string', 'min:8', 'confirmed', $this->bcryptPasswordLimit(...)],
        ], [
            'required' => 'Заполните поле «:attribute».',
            'email.email' => 'Введите корректный адрес электронной почты.',
            'email.unique' => 'Аккаунт с такой почтой уже существует.',
            'password.min' => 'Пароль должен содержать не менее :min символов.',
            'password.confirmed' => 'Пароли не совпадают.',
            'max' => 'Поле «:attribute» должно содержать не более :max символов.',
        ], ['name' => 'Имя и фамилия', 'email' => 'Электронная почта', 'password' => 'Пароль']);

        try {
            $user = User::create($credentials);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Аккаунт с такой почтой уже существует.']);
        }

        Auth::login($user);

        return redirect()->intended(route('shop'));
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['bail', 'required', 'string', $this->bcryptPasswordLimit(...)],
        ], [
            'email' => 'Введите корректный адрес электронной почты.',
            'password' => 'Введите пароль.',
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Неверная электронная почта или пароль.']);
        }

        return redirect()->intended(route('shop'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop');
    }

    private function normalizeEmail(Request $request): void
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower($email)]);
        }
    }

    private function bcryptPasswordLimit(string $attribute, string $value, Closure $fail): void
    {
        if (strlen($value) > 72 || str_contains($value, "\0")) {
            $fail('Пароль содержит недопустимые символы или превышает 72 байта.');
        }
    }
}
