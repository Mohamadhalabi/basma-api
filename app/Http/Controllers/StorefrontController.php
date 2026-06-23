<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StorefrontController
{
    /**
     * Show product listing page
     */
    public function products(Request $request)
    {
        // Fetch all filter options for the frontend
        $categories = Category::where('is_active', true)->get();
        $manufacturers = Manufacturer::where('is_active', true)->get();

        // The frontend will handle filtering via Alpine.js and API calls
        return view('storefront.products.index', [
            'categories' => $categories,
            'manufacturers' => $manufacturers,
        ]);
    }

    /**
     * Show single product page
     */
    public function productDetail($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            abort(404);
        }

        $product->load(['manufacturer', 'categories', 'attributeValues']);
        $gallery = $product->getMedia('gallery')->map(function ($media) {
            return [
                'thumb' => $media->getUrl('thumb'),
                'card' => $media->getUrl('card'),
                'full' => $media->getUrl('full'),
                'original' => $media->getUrl(),
            ];
        });

        return view('storefront.products.show', [
            'product' => $product,
            'gallery' => $gallery,
        ]);
    }

    /**
     * Show cart page
     */
    public function cart()
    {
        return view('storefront.cart.index');
    }

    /**
     * Show checkout page
     */
    public function checkout()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect('/login')->with('message', 'Please login to checkout');
        }

        $customer->load('addresses');

        return view('storefront.checkout.index', [
            'customer' => $customer,
            'addresses' => $customer->addresses,
        ]);
    }

    /**
     * Show customer account page
     */
    public function account()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect('/login');
        }

        $customer->load('addresses', 'orders');

        return view('storefront.account.profile', [
            'customer' => $customer,
        ]);
    }

    /**
     * Show customer orders page
     */
    public function orders()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect('/login');
        }

        $orders = $customer->orders()->orderByDesc('created_at')->paginate(10);

        return view('storefront.account.orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show login page
     */
    public function showLogin()
    {
        return view('storefront.auth.login');
    }

    /**
     * Show registration page
     */
    public function showRegister()
    {
        return view('storefront.auth.register');
    }

    /**
     * Show homepage
     */
    public function index()
    {
        // You can add featured products, categories, etc here
        return view('storefront.index');
    }
}
