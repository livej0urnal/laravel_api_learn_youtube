<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product->loadMissing('category:id,name,slug');

        return view('product', compact('product'));
    }
}
