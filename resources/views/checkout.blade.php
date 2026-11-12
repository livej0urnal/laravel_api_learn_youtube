@extends('shop-layout')

@section('title', 'Оформление заказа — Вольт')

@section('content')
    <section class="store-page">
        <a class="back-link" href="{{ route('cart.show') }}">← В корзину</a>
        <h1>Оформление заказа</h1>

        <div class="store-grid">
            <form class="store-panel store-form" action="{{ route('checkout.place') }}" method="post">
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
                <h2>Контакты и адрес</h2>
                <label class="filter-field">
                    <span>Имя и фамилия</span>
                    <input name="customer_name" value="{{ old('customer_name') }}" maxlength="255" autocomplete="name" required>
                </label>
                <label class="filter-field">
                    <span>Электронная почта</span>
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
                </label>
                <label class="filter-field">
                    <span>Телефон</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" required>
                </label>
                <label class="filter-field">
                    <span>Адрес доставки</span>
                    <textarea name="address" maxlength="500" rows="3" autocomplete="street-address" required>{{ old('address') }}</textarea>
                </label>
                @if ($canCheckout)
                    <button class="button button-accent" type="submit">Подтвердить заказ</button>
                @else
                    <p class="line-warning">Остатки изменились. Проверьте товары в корзине.</p>
                @endif
            </form>

            <aside class="store-panel summary">
                <h2>Ваш заказ</h2>
                <ul class="order-summary">
                    @foreach ($lines as $line)
                        <li>{{ $line['product']->name ?? 'Товар удалён из каталога' }} × {{ $line['quantity'] }}</li>
                    @endforeach
                </ul>
                <p class="detail-price">{{ number_format($total / 100, 2, ',', ' ') }} €</p>
                <p class="result-count">Демо-заказ без онлайн-оплаты.</p>
            </aside>
        </div>
    </section>
@endsection
