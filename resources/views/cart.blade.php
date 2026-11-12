@extends('shop-layout')

@section('title', 'Корзина — Вольт')

@section('content')
    <section class="store-page">
        <a class="back-link" href="{{ route('shop') }}#catalog">← В каталог</a>
        <h1>Корзина</h1>

        @if ($lines->isEmpty())
            <div class="store-panel">
                <p>Корзина пуста.</p>
                <a class="button button-dark" href="{{ route('shop') }}#catalog">Посмотреть товары</a>
            </div>
        @else
            <div class="store-grid">
                <div class="cart-lines">
                    @foreach ($lines as $productId => $line)
                        <article class="store-panel cart-line">
                            <div>
                                @if ($line['product'])
                                    <h2><a class="product-name" href="{{ route('products.show', $line['product']->slug) }}">{{ $line['product']->name }}</a></h2>
                                    <p>{{ number_format($line['unit_price'] / 100, 2, ',', ' ') }} € за шт.</p>
                                @else
                                    <h2>Товар удалён из каталога</h2>
                                @endif
                                @unless ($line['available'])
                                    <p class="line-warning">Доступно · {{ $line['product']->stock ?? 0 }} шт.</p>
                                @endunless
                            </div>
                            <strong>{{ number_format($line['subtotal'] / 100, 2, ',', ' ') }} €</strong>
                            <div class="cart-actions">
                                @if ($line['product'] && $line['product']->stock > 0)
                                    <form class="cart-actions" action="{{ route('cart.update', $productId) }}" method="post">
                                        @csrf
                                        @method('PATCH')
                                        <label class="filter-field quantity-field">
                                            <span>Количество</span>
                                            <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ min($line['product']->stock, 999) }}" required>
                                        </label>
                                        <button class="button button-outline" type="submit">Обновить</button>
                                    </form>
                                @endif
                                <form action="{{ route('cart.remove', $productId) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button-outline" type="submit">Удалить</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="store-panel summary">
                    <h2>Итого</h2>
                    <p class="detail-price">{{ number_format($total / 100, 2, ',', ' ') }} €</p>
                    @if ($canCheckout)
                        <a class="button button-accent" href="{{ route('checkout.show') }}">Оформить заказ</a>
                    @else
                        <p class="line-warning">Обновите количество или удалите недоступные товары.</p>
                    @endif
                </aside>
            </div>
        @endif
    </section>
@endsection
