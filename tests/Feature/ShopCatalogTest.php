<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalog_uses_category_and_product_models(): void
    {
        $phones = Category::factory()->create([
            'name' => 'Смартфоны',
            'slug' => 'smartphones',
        ]);
        $laptops = Category::factory()->create([
            'name' => 'Ноутбуки',
            'slug' => 'laptops',
        ]);

        $phone = Product::factory()->for($phones)->create([
            'name' => 'Тестовый смартфон',
            'slug' => 'test-phone',
            'price' => '199.90',
        ]);
        Product::factory()->for($laptops)->create([
            'name' => 'Тестовый ноутбук',
            'slug' => 'test-laptop',
        ]);

        $this->assertTrue($phones->products()->whereKey($phone)->exists());
        $this->assertTrue($phone->category->is($phones));
        $this->assertSame(19990, $phone->priceInCents());

        $this->get(route('shop', ['category' => $phones->slug]))
            ->assertOk()
            ->assertSee('Тестовый смартфон')
            ->assertDontSee('Тестовый ноутбук');

        $this->get(route('products.show', $phone->slug))
            ->assertOk()
            ->assertSee('Тестовый смартфон')
            ->assertSee('Смартфоны');
    }
}
