@extends('shop-layout')

@section('title', $product->name.' — Вольт')

@section('content')
    <section class="store-page">
        <a class="back-link" href="{{ route('shop', ['category' => $product->category_slug]) }}#catalog">← {{ $product->category_name }}</a>

        <div class="product-detail">
            <div>
                <h1>{{ $product->name }}</h1>
                @if ($product->description)
                    <p class="detail-description">{{ $product->description }}</p>
                @endif
            </div>

            <aside class="store-panel">
                <p class="detail-price">{{ number_format((float) $product->price, 2, ',', ' ') }} €</p>
                @if ($product->stock > 0)
                    <p class="stock stock-available">В наличии · {{ $product->stock }} шт.</p>
                    <form class="store-form" method="post" action="{{ route('cart.add', $product->id) }}">
                        @csrf
                        <label class="filter-field quantity-field">
                            <span>Количество</span>
                            <input name="quantity" type="number" value="{{ old('quantity', 1) }}" min="1" max="{{ min($product->stock, 999) }}" required>
                        </label>
                        <button type="submit" class="button button-accent">Добавить в корзину</button>
                    </form>
                @else
                    <p class="stock">Нет в наличии</p>
                @endif
            </aside>
        </div>
    </section>
@endsection
