@extends('shop-layout')

@section('title', 'Заказ оформлен — Вольт')

@section('content')
    <section class="store-page">
        <h1>Заказ оформлен</h1>
        <div class="store-panel">
            <p>Номер заказа — <strong>№{{ $orderId }}</strong>.</p>
            <a class="button button-dark" href="{{ route('shop') }}#catalog">Вернуться в каталог</a>
        </div>
    </section>
@endsection
