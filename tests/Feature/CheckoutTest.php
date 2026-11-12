<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_create_an_order_and_stock_is_decremented_once(): void
    {
        $this->seed(CatalogSeeder::class);

        $product = DB::table('products')->where('slug', 'iphone-17-pro-256gb')->first();
        $charger = DB::table('products')->where('slug', 'usb-c-charger-30w')->first();

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Добавить в корзину');

        $this->post(route('cart.add', $product->id), ['quantity' => 1])
            ->assertRedirectToRoute('cart.show');

        $this->patch(route('cart.update', $product->id), ['quantity' => 2])
            ->assertRedirectToRoute('cart.show');

        $this->post(route('cart.add', $charger->id), ['quantity' => 1])
            ->assertRedirectToRoute('cart.show');

        $this->delete(route('cart.remove', $charger->id))
            ->assertRedirectToRoute('cart.show');

        $this->get(route('cart.show'))->assertOk()->assertSee('2 398,00 €');
        $this->get(route('checkout.show'))->assertOk();

        $data = $this->checkoutData();

        $this->post(route('checkout.place'), $data)
            ->assertRedirectToRoute('checkout.complete')
            ->assertSessionMissing('cart');

        $this->get(route('checkout.complete'))->assertOk()->assertSee('Заказ оформлен');
        $this->post(route('checkout.place'), $data)->assertRedirectToRoute('cart.show');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('orders', ['email' => 'customer@example.com', 'total_cents' => 239800]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'unit_price_cents' => 119900]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => $product->stock - 2]);
    }

    public function test_checkout_rejects_changed_stock_without_creating_an_order(): void
    {
        $this->seed(CatalogSeeder::class);

        $product = DB::table('products')->where('slug', 'iphone-17-pro-256gb')->first();

        $this->post(route('cart.add', $product->id), ['quantity' => 2])
            ->assertRedirectToRoute('cart.show');

        DB::table('products')->where('id', $product->id)->update(['stock' => 1]);

        $this->from(route('checkout.show'))
            ->post(route('checkout.place'), $this->checkoutData())
            ->assertRedirectToRoute('checkout.show')
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
    }

    private function checkoutData(): array
    {
        return [
            'checkout_token' => session('cart.token'),
            'customer_name' => 'Тестовый Покупатель',
            'email' => 'customer@example.com',
            'phone' => '+382 67 000 000',
            'address' => 'Bijela, test address',
        ];
    }
}
