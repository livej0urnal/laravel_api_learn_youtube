<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Services\OrderService;
use App\Services\ShoppingCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly ShoppingCart $cart,
        private readonly OrderService $orders,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cart->details($request->session());

        if ($cart['lines']->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return view('checkout', $cart + [
            'checkoutToken' => $request->session()->get('cart.token'),
        ]);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse
    {
        $items = $this->cart->items($request->session());

        if ($items === []) {
            return redirect()->route('cart.show');
        }

        $validated = $request->validated();

        if ($validated['checkout_token'] !== $request->session()->get('cart.token')) {
            throw ValidationException::withMessages([
                'checkout_token' => 'Корзина изменилась. Проверьте заказ и отправьте форму снова.',
            ]);
        }

        $order = $this->orders->place($items, $validated);

        $request->session()->forget('cart');
        $request->session()->put('last_order_id', $order->getKey());

        return redirect()->route('checkout.complete');
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $orderId = $request->session()->get('last_order_id');

        return $orderId
            ? view('checkout-complete', compact('orderId'))
            : redirect()->route('shop');
    }
}
