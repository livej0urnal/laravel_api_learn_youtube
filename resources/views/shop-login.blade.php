@extends('shop-layout')

@section('title', 'Вход — Вольт')

@section('content')
    <section class="store-page auth-page">
        <h1>Вход</h1>

        <form class="store-panel store-form" action="{{ route('login.store') }}" method="post">
            @csrf
            <label class="filter-field">
                <span>Электронная почта</span>
                <input type="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" maxlength="255" autocomplete="username" required>
            </label>
            <label class="filter-field">
                <span>Пароль</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button class="button button-accent" type="submit">Войти</button>
            <a class="back-link" href="{{ route('register') }}">Создать аккаунт</a>
        </form>
    </section>
@endsection
