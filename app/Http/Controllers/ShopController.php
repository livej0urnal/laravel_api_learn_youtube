<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->limit(100, '')->toString());
        $category = trim($request->string('category')->limit(255, '')->toString());

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $products = Product::query()
            ->with('category:id,name,slug')
            ->when($category !== '', function (Builder $query) use ($category): void {
                $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category));
            })
            ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
            ->orderBy('id')
            ->paginate(12)
            ->appends(array_filter([
                'q' => $search,
                'category' => $category,
            ], fn (string $value): bool => $value !== ''))
            ->fragment('catalog');

        return view('shop', compact('categories', 'products', 'search', 'category'));
    }
}
