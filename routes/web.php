<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopAuthController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'index'])->block()->name('shop');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'shop-login')->block()->name('login');
    Route::post('/login', [ShopAuthController::class, 'login'])
        ->middleware('throttle:10,1,shop-login')->block()->name('login.store');
    Route::view('/register', 'shop-register')->block()->name('register');
    Route::post('/register', [ShopAuthController::class, 'register'])
        ->middleware('throttle:5,1,shop-register')->block()->name('register.store');
});

Route::post('/logout', [ShopAuthController::class, 'logout'])->middleware('auth')->block()->name('logout');

Route::get('/products/{product:slug}', [ProductController::class, 'show'])->block()->name('products.show');

Route::controller(CartController::class)->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', 'index')->block()->name('show');
    Route::post('/{product}', 'store')->whereNumber('product')->block()->name('add');
    Route::patch('/{product}', 'update')->whereNumber('product')->block()->name('update');
    Route::delete('/{product}', 'destroy')->whereNumber('product')->block()->name('remove');
});

Route::controller(CheckoutController::class)->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', 'show')->block()->name('show');
    Route::post('/', 'store')->block()->name('place');
    Route::get('/complete', 'complete')->block()->name('complete');
});
