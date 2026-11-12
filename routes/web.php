<?php

use App\Http\Controllers\ShopAuthController;
use App\Http\Controllers\ShopCartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $search = trim($request->string('q')->limit(100, '')->toString());
    $category = trim($request->string('category')->limit(255, '')->toString());

    $categories = DB::table('categories')
        ->orderBy('name')
        ->get(['id', 'name', 'slug']);

    $products = DB::table('products')
        ->join('categories', 'categories.id', '=', 'products.category_id')
        ->select('products.*', 'categories.name as category_name', 'categories.slug as category_slug')
        ->when($category !== '', fn ($query) => $query->where('categories.slug', $category))
        ->when($search !== '', fn ($query) => $query->whereLike('products.name', "%{$search}%"))
        ->orderBy('products.id')
        ->paginate(12)
        ->appends(array_filter(['q' => $search, 'category' => $category], fn ($value) => $value !== ''))
        ->fragment('catalog');

    return view('shop', compact('categories', 'products', 'search', 'category'));
})->block()->name('shop');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'shop-login')->block()->name('login');
    Route::post('/login', [ShopAuthController::class, 'login'])
        ->middleware('throttle:10,1,shop-login')->block()->name('login.store');
    Route::view('/register', 'shop-register')->block()->name('register');
    Route::post('/register', [ShopAuthController::class, 'register'])
        ->middleware('throttle:5,1,shop-register')->block()->name('register.store');
});

Route::post('/logout', [ShopAuthController::class, 'logout'])->middleware('auth')->block()->name('logout');

Route::get('/products/{slug}', [ShopCartController::class, 'product'])->block()->name('products.show');
Route::get('/cart', [ShopCartController::class, 'cart'])->block()->name('cart.show');
Route::post('/cart/{product}', [ShopCartController::class, 'add'])->whereNumber('product')->block()->name('cart.add');
Route::patch('/cart/{product}', [ShopCartController::class, 'update'])->whereNumber('product')->block()->name('cart.update');
Route::delete('/cart/{product}', [ShopCartController::class, 'remove'])->whereNumber('product')->block()->name('cart.remove');
Route::get('/checkout', [ShopCartController::class, 'checkout'])->block()->name('checkout.show');
Route::post('/checkout', [ShopCartController::class, 'placeOrder'])->block()->name('checkout.place');
Route::get('/checkout/complete', [ShopCartController::class, 'complete'])->block()->name('checkout.complete');
