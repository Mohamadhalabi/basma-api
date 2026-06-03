<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;

/**
 * Single source of truth for "what price does THIS customer pay for THIS product?"
 *
 * Both the storefront cart and admin order creation MUST call this, so the
 * pricing rule lives in exactly one place.
 *
 * Rule:
 *   1. If the customer has an active price list with an entry for this product
 *      -> use that net price.
 *   2. Otherwise -> use the product's default net price.
 *
 * All prices are integer halalas (1 SAR = 100 halalas), VAT-exclusive.
 */
class PriceResolver
{
    /**
     * Resolve the net unit price (halalas) for a product, optionally for a customer.
     */
public function resolve(Product $product, ?Customer $customer = null): int
    {
        if ($customer) {
            $priceList = $customer->activePriceList();
            if ($priceList) {
                $item = $priceList->items()->where('product_id', $product->id)->first();
                if ($item) {
                    return (int) $item->price; // customer price wins
                }
            }
        }
        return $this->basePrice($product); // sale price or default
    }

    public function resolveMany(iterable $products, ?Customer $customer = null): array
    {
        $overrides = [];
        if ($customer && ($priceList = $customer->activePriceList())) {
            $overrides = $priceList->items()->pluck('price', 'product_id')->toArray();
        }

        $out = [];
        foreach ($products as $product) {
            $out[$product->id] = isset($overrides[$product->id])
                ? (int) $overrides[$product->id]
                : $this->basePrice($product);
        }
        return $out;
    }

    private function basePrice(Product $product): int
    {
        if (! empty($product->sale_price) && $product->sale_price > 0) {
            return (int) $product->sale_price;
        }
        return (int) $product->default_price;
    }
}
