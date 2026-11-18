<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        //
        $orders = $request->user()->orders()
        ->with('items.product')
        ->latest()
        ->paginate(10);

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(StoreOrderRequest $request): JsonResponse
    {
    $validated = $request->validated();

    $order = DB::transaction(function () use ($request, $validated): Order {
        $products = Product::query()
            ->whereIn(
                'id',
                collect($validated['items'])->pluck('product_id')
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $totalCents = 0;

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);

            if ($product === null || $product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "Недостаточно товара с ID {$item['product_id']}.",
                ]);
            }

            $totalCents += $product->priceInCents() * $item['quantity'];
        }

        $order = $request->user()->orders()->create([
            'checkout_token' => (string) Str::uuid(),
            'customer_name' => $validated['customer_name'],
            'email' => $request->user()->email,
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'total_cents' => $totalCents,
        ]);

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price_cents' => $product->priceInCents(),
            ]);

            $product->decrement('stock', $item['quantity']);
        }

        return $order;
    });

    return response()->json([
        'message' => 'Order created successfully.',
        'order' => $order->load('items.product'),
    ], 201);
}

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
