@extends('shop-layout')

@section('title', 'Регистрация — Вольт')

@section('content')
    <section class="store-page auth-page">
        <h1>Регистрация</h1>

        <form class="store-panel store-form" action="{{ route('register.store') }}" method="post">
            @csrf
            <label class="filter-field">
                <span>Имя и фамилия</span>
                <input name="name" value="{{ is_string(old('name')) ? old('name') : '' }}" maxlength="255" autocomplete="name" required>
            </label>
            <label class="filter-field">
                <span>Электронная почта</span>
                <input type="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" maxlength="255" autocomplete="username" required>
            </label>
            <label class="filter-field">
                <span>Пароль (не менее 8 символов)</span>
                <input type="password" name="password" minlength="8" autocomplete="new-password" required>
            </label>
            <label class="filter-field">
                <span>Повторите пароль</span>
                <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
            </label>
            <button class="button button-accent" type="submit">Зарегистрироваться</button>
            <a class="back-link" href="{{ route('login') }}">Уже есть аккаунт? Войти</a>
        </form>
    </section>
@endsection
