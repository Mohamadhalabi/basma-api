<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;

/**
 * Calculates order/cart totals using INTEGER MATH ONLY (halalas).
 *
 * Floats are never used for money: 0.1 + 0.2 !== 0.3 in floating point,
 * which silently corrupts invoice totals. Everything here is integer halalas
 * until the very end, where formatting (for display) happens elsewhere.
 *
 * Handles 100+ line items trivially - this is plain integer arithmetic.
 */
class OrderCalculator
{
    public function __construct(private PriceResolver $priceResolver) {}

    /**
     * Calculate totals for a set of cart lines.
     *
     * @param  array<int,array{product:Product,quantity:int}>  $lines
     * @param  Customer|null  $customer
     * @return array{
     *   items: array<int,array{product_id:int,sku:string,title:string,unit_price:int,quantity:int,line_total:int}>,
     *   subtotal:int, vat_rate:float, vat_amount:int, total:int
     * }
     */
    public function calculate(array $lines, ?Customer $customer = null, float $vatRate = 15.0): array
    {
        // Batch-resolve prices so a 100-item cart is 1 query, not 100.
        $products = array_map(fn ($l) => $l['product'], $lines);
        $prices   = $this->priceResolver->resolveMany($products, $customer);

        $items    = [];
        $subtotal = 0; // net, halalas

        foreach ($lines as $line) {
            /** @var Product $product */
            $product   = $line['product'];
            $quantity  = max(1, (int) $line['quantity']);
            $unitPrice = $prices[$product->id];        // integer halalas
            $lineTotal = $unitPrice * $quantity;       // integer
            $subtotal += $lineTotal;

            $items[] = [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'title'      => $product->title,
                'unit_price' => $unitPrice,
                'quantity'   => $quantity,
                'line_total' => $lineTotal,
            ];
        }

        // VAT computed once on the subtotal, then rounded to nearest halala.
        // intdiv-style rounding: (subtotal * rateBp + 5000) / 10000
        // rateBp = rate in basis points (15.00% -> 1500).
        $rateBp    = (int) round($vatRate * 100);          // 15.00 -> 1500
        $vatAmount = intdiv($subtotal * $rateBp + 5000, 10000);
        $total     = $subtotal + $vatAmount;

        return [
            'items'      => $items,
            'subtotal'   => $subtotal,
            'vat_rate'   => $vatRate,
            'vat_amount' => $vatAmount,
            'total'      => $total,
        ];
    }
}
