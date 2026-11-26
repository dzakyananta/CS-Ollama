<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $products = Product::where('is_active', true)
            ->orderByRaw('(CAST(SUBSTR(sku, 5) AS INTEGER) - 1) % 10')
            ->orderBy('price')
            ->orderBy('brand')
            ->orderBy('name')
            ->get();

        return view('storefront', [
            'products' => $products,
            'categories' => $products->pluck('category')->unique()->sort()->values(),
        ]);
    }
}