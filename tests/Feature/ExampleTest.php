<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Товары не найдены.');
    }

    public function test_the_catalog_displays_and_filters_seeded_products(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->total() === 500 && $products->count() === 12)
            ->assertSee('iPhone 17 Pro 256 ГБ');

        $this->get('/?category=smartphones')
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->total() === 5);

        $this->get('/?category=smartphones&q=iPhone')
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->total() === 1)
            ->assertSee('iPhone 17 Pro 256 ГБ')
            ->assertDontSee('Зарядное устройство USB-C 30 Вт');
    }
}
