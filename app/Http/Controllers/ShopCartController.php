<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShopCartController extends Controller
{
    public function product(string $slug): View
    {
        $product = DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->select('products.*', 'categories.name as category_name', 'categories.slug as category_slug')
            ->where('products.slug', $slug)
            ->first();

        abort_if($product === null, 404);

        return view('product', compact('product'));
    }

    public function cart(Request $request): View
    {
        return view('cart', $this->cartDetails($request));
    }

    public function add(Request $request, int $product): RedirectResponse
    {
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:999']]);
        $items = $request->session()->get('cart.items', []);
        $quantity = ($items[$product] ?? 0) + (int) $validated['quantity'];

        $this->checkStock($product, $quantity);
        $items[$product] = $quantity;
        $this->saveCart($request, $items);

        return redirect()->route('cart.show')->with('status', 'Товар добавлен в корзину.');
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $items = $request->session()->get('cart.items', []);
        abort_unless(isset($items[$product]), 404);

        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:999']]);
        $this->checkStock($product, (int) $validated['quantity']);
        $items[$product] = (int) $validated['quantity'];
        $this->saveCart($request, $items);

        return redirect()->route('cart.show')->with('status', 'Количество обновлено.');
    }

    public function remove(Request $request, int $product): RedirectResponse
    {
        $items = $request->session()->get('cart.items', []);
        unset($items[$product]);
        $this->saveCart($request, $items);

        return redirect()->route('cart.show');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $cart = $this->cartDetails($request);

        if ($cart['lines']->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return view('checkout', $cart + ['checkoutToken' => $request->session()->get('cart.token')]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $items = $request->session()->get('cart.items', []);

        if ($items === []) {
            return redirect()->route('cart.show');
        }

        $validated = $request->validate([
            'checkout_token' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        if ($validated['checkout_token'] !== $request->session()->get('cart.token')) {
            throw ValidationException::withMessages(['checkout_token' => 'Корзина изменилась. Проверьте заказ и отправьте форму снова.']);
        }

        $orderId = DB::transaction(function () use ($items, $validated): int {
            $existingOrder = DB::table('orders')->where('checkout_token', $validated['checkout_token'])->value('id');

            if ($existingOrder !== null) {
                return (int) $existingOrder;
            }

            $products = DB::table('products')
                ->whereIn('id', array_keys($items))
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name', 'stock', 'price'])
                ->keyBy('id');

            $total = 0;
            $orderItems = [];

            foreach ($items as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product === null || $quantity > $product->stock) {
                    throw ValidationException::withMessages(['cart' => 'Остатки изменились. Проверьте товары в корзине.']);
                }

                $unitPrice = $this->priceInCents((string) $product->price);
                $total += $unitPrice * $quantity;
                $orderItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPrice,
                ];
            }

            $now = now();
            $orderId = DB::table('orders')->insertGetId($validated + [
                'total_cents' => $total,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($orderItems as $index => $item) {
                $affected = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->where('stock', '>=', $item['quantity'])
                    ->decrement('stock', $item['quantity'], ['updated_at' => $now]);

                if ($affected !== 1) {
                    throw ValidationException::withMessages(['cart' => 'Остатки изменились. Проверьте товары в корзине.']);
                }

                $orderItems[$index]['order_id'] = $orderId;
            }

            DB::table('order_items')->insert($orderItems);

            return (int) $orderId;
        }, attempts: 3);

        $request->session()->forget('cart');
        $request->session()->put('last_order_id', $orderId);

        return redirect()->route('checkout.complete');
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $orderId = $request->session()->get('last_order_id');

        return $orderId ? view('checkout-complete', compact('orderId')) : redirect()->route('shop');
    }

    private function checkStock(int $productId, int $quantity): void
    {
        $stock = DB::table('products')->where('id', $productId)->value('stock');
        abort_if($stock === null, 404);

        if ($quantity > min((int) $stock, 999)) {
            throw ValidationException::withMessages(['quantity' => 'Такого количества нет в наличии. Укажите меньшее количество.']);
        }
    }

    private function saveCart(Request $request, array $items): void
    {
        ksort($items);
        $request->session()->put('cart', ['items' => $items, 'token' => (string) Str::uuid()]);
    }

    private function cartDetails(Request $request): array
    {
        $items = collect($request->session()->get('cart.items', []));
        $products = DB::table('products')
            ->whereIn('id', $items->keys()->all())
            ->get(['id', 'name', 'slug', 'stock', 'price'])
            ->keyBy('id');

        $lines = $items->map(function (int $quantity, int $productId) use ($products): array {
            $product = $products->get($productId);
            $price = $this->priceInCents((string) ($product->price ?? '0'));

            return [
                'product' => $product,
                'quantity' => $quantity,
                'available' => $product !== null && $quantity <= $product->stock,
                'unit_price' => $price,
                'subtotal' => $price * $quantity,
            ];
        });

        return [
            'lines' => $lines,
            'total' => $lines->sum('subtotal'),
            'canCheckout' => $lines->isNotEmpty() && $lines->every('available', true),
        ];
    }

    private function priceInCents(string $price): int
    {
        [$euros, $cents] = array_pad(explode('.', $price, 2), 2, '');

        return (int) $euros * 100 + (int) str_pad($cents, 2, '0');
    }
}
