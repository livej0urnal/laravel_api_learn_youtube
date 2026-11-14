<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShoppingCart
{
    public function add(Session $session, Product $product, int $quantity): void
    {
        $items = $this->items($session);
        $newQuantity = ($items[$product->id] ?? 0) + $quantity;

        $this->ensureStockIsAvailable($product, $newQuantity);
        $items[$product->id] = $newQuantity;
        $this->save($session, $items);
    }

    public function update(Session $session, Product $product, int $quantity): void
    {
        $items = $this->items($session);
        abort_unless(isset($items[$product->id]), 404);

        $this->ensureStockIsAvailable($product, $quantity);
        $items[$product->id] = $quantity;
        $this->save($session, $items);
    }

    public function remove(Session $session, Product $product): void
    {
        $items = $this->items($session);
        unset($items[$product->id]);
        $this->save($session, $items);
    }

    /**
     * @return array<int, int>
     */
    public function items(Session $session): array
    {
        return $session->get('cart.items', []);
    }

    /**
     * @return array{lines: Collection<int, array{product: Product|null, quantity: int, available: bool, unit_price: int, subtotal: int}>, total: int, canCheckout: bool}
     */
    public function details(Session $session): array
    {
        $items = collect($this->items($session));
        $products = Product::query()
            ->whereKey($items->keys())
            ->get(['id', 'name', 'slug', 'stock', 'price'])
            ->keyBy('id');

        $lines = $items->map(function (int $quantity, int $productId) use ($products): array {
            $product = $products->get($productId);
            $price = $product?->priceInCents() ?? 0;

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

    private function ensureStockIsAvailable(Product $product, int $quantity): void
    {
        if ($quantity > min($product->stock, 999)) {
            throw ValidationException::withMessages([
                'quantity' => 'Такого количества нет в наличии. Укажите меньшее количество.',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $items
     */
    private function save(Session $session, array $items): void
    {
        ksort($items);
        $session->put('cart', ['items' => $items, 'token' => (string) Str::uuid()]);
    }
}
