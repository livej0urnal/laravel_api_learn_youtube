<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @param  array<int, int>  $items
     * @param  array{checkout_token: string, customer_name: string, email: string, phone: string, address: string}  $customer
     */
    public function place(array $items, array $customer): Order
    {
        return DB::transaction(function () use ($items, $customer): Order {
            $existingOrder = Order::query()
                ->where('checkout_token', $customer['checkout_token'])
                ->first();

            if ($existingOrder !== null) {
                return $existingOrder;
            }

            $products = Product::query()
                ->whereKey(array_keys($items))
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name', 'stock', 'price'])
                ->keyBy('id');

            $total = 0;
            $orderItems = [];

            foreach ($items as $productId => $quantity) {
                $product = $products->get($productId);

                if ($product === null || $quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'cart' => 'Остатки изменились. Проверьте товары в корзине.',
                    ]);
                }

                $unitPrice = $product->priceInCents();
                $total += $unitPrice * $quantity;
                $orderItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPrice,
                ];
            }

            $order = Order::query()->create($customer + ['total_cents' => $total]);

            foreach ($orderItems as $item) {
                $affected = Product::query()
                    ->whereKey($item['product_id'])
                    ->where('stock', '>=', $item['quantity'])
                    ->decrement('stock', $item['quantity']);

                if ($affected !== 1) {
                    throw ValidationException::withMessages([
                        'cart' => 'Остатки изменились. Проверьте товары в корзине.',
                    ]);
                }
            }

            $order->items()->createMany($orderItems);

            return $order;
        }, attempts: 3);
    }
}
