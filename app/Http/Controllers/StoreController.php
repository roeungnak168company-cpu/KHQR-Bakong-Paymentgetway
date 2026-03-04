<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StoreController extends Controller
{
    // Cheap demo prices (KHR) to match Node demo convenience
    private array $products = [
        'laptop' => ['id' => 'laptop', 'name' => 'Laptop', 'price_khr' => 100],
        'phone' => ['id' => 'phone', 'name' => 'Phone', 'price_khr' => 500],
        'headphones' => ['id' => 'headphones', 'name' => 'Headphones', 'price_khr' => 100],
    ];

    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        return view('store.index', [
            'products' => array_values($this->products),
            'cartTotal' => $this->cartTotal($cart),
        ]);
    }

    public function cart(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        return view('store.cart', [
            'items' => array_values($cart),
            'total' => $this->cartTotal($cart),
        ]);
    }

    public function add(Request $request)
    {
        $productId = (string)$request->input('productId', '');
        $qty = (int)$request->input('qty', 1);
        $qty = max(1, min(99, $qty));

        if (!isset($this->products[$productId])) {
            return redirect()->route('store')->with('error', 'Unknown product');
        }

        $p = $this->products[$productId];
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['qty'] += $qty;
        } else {
            $cart[$productId] = [
                'product_id' => $p['id'],
                'name' => $p['name'],
                'price_khr' => $p['price_khr'],
                'qty' => $qty,
            ];
        }

        $request->session()->put('cart', $cart);
        return redirect()->route('cart');
    }

    public function remove(Request $request)
    {
        $productId = (string)$request->input('productId', '');
        $cart = $request->session()->get('cart', []);
        unset($cart[$productId]);
        $request->session()->put('cart', $cart);
        return redirect()->route('cart');
    }

    public function clear(Request $request)
    {
        $request->session()->forget('cart');
        return redirect()->route('cart');
    }

    private function cartTotal(array $cart): int
    {
        $sum = 0;
        foreach ($cart as $i) {
            $sum += ((int)($i['price_khr'] ?? 0)) * ((int)($i['qty'] ?? 1));
        }
        return $sum;
    }
}
