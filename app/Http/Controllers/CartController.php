<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ShoppingCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly ShoppingCart $cart) {}

    public function index(Request $request): View
    {
        return view('cart', $this->cart->details($request->session()));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $this->cart->add($request->session(), $product, (int) $validated['quantity']);

        return redirect()->route('cart.show')->with('status', 'Товар добавлен в корзину.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $this->cart->update($request->session(), $product, (int) $validated['quantity']);

        return redirect()->route('cart.show')->with('status', 'Количество обновлено.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->cart->remove($request->session(), $product);

        return redirect()->route('cart.show');
    }
}
