@extends('shop-layout')

@section('content')
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-copy">
            <h1 id="hero-title">Техника<br>на каждый<br>день<span>.</span></h1>
            <p>Смартфоны, компьютеры, звук и аксессуары в одном каталоге.</p>
            <a class="button button-accent" href="#catalog">Смотреть каталог <span aria-hidden="true">↘</span></a>
        </div>
        <div class="hero-art" aria-hidden="true"></div>
    </section>

    <section id="catalog" class="catalog" aria-labelledby="catalog-title">
        <div class="catalog-heading">
            <h2 id="catalog-title">Каталог</h2>
            <p class="result-count">Товаров в подборке · {{ $products->total() }}</p>
        </div>

        <form class="filters" action="{{ route('shop') }}#catalog" method="get" role="search">
            <label class="filter-field">
                <span>Название товара</span>
                <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Например, iPhone">
            </label>
            <label class="filter-field">
                <span>Категория</span>
                <select name="category">
                    <option value="">Все категории</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->slug }}" @selected($category === $option->slug)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-dark" type="submit">Найти</button>
            @if ($search !== '' || $category !== '')
                <a class="reset-link" href="{{ route('shop') }}#catalog">Сбросить</a>
            @endif
        </form>

        <div class="products">
            @forelse ($products as $product)
                <article class="product">
                    <a class="product-category" href="{{ route('shop', ['category' => $product->category_slug]) }}#catalog">{{ $product->category_name }}</a>
                    <h3><a class="product-name" href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h3>
                    @if ($product->description)
                        <p class="product-description">{{ $product->description }}</p>
                    @endif
                    <div class="product-meta">
                        <strong>{{ number_format((float) $product->price, 2, ',', ' ') }} €</strong>
                        <span class="stock {{ $product->stock > 0 ? 'stock-available' : '' }}">{{ $product->stock > 0 ? 'В наличии' : 'Нет в наличии' }}</span>
                    </div>
                </article>
            @empty
                <p class="empty">Товары не найдены.</p>
            @endforelse
        </div>

        @if ($products->hasPages())
            <nav class="pagination" aria-label="Страницы каталога">
                @if ($products->previousPageUrl())
                    <a rel="prev" href="{{ $products->previousPageUrl() }}">← Назад</a>
                @endif
                <span>Страница {{ $products->currentPage() }} из {{ $products->lastPage() }}</span>
                @if ($products->nextPageUrl())
                    <a rel="next" href="{{ $products->nextPageUrl() }}">Вперёд →</a>
                @endif
            </nav>
        @endif
    </section>
@endsection
