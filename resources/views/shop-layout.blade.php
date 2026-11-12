<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Каталог смартфонов, компьютеров и другой электроники.">
    <title>@yield('title', 'Вольт — магазин электроники')</title>
    <link rel="stylesheet" href="{{ asset('shop.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand" href="{{ route('shop') }}">
                <span class="brand-mark" aria-hidden="true">V</span>
                <span>Вольт<span class="brand-dot">.</span></span>
            </a>
            <nav class="header-nav" aria-label="Магазин">
                <a class="header-link" href="{{ route('shop') }}#catalog">Каталог</a>
                <a class="header-link header-cart" href="{{ route('cart.show') }}">Корзина <span>{{ array_sum(session('cart.items', [])) }}</span></a>
            </nav>
        </div>
    </header>

    <main class="shell">
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul class="notice notice-error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @yield('content')
    </main>
</body>
</html>
