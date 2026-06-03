<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a cart (customer + product/quantity lines) into a persisted Order.
 *
 * Used by BOTH the storefront checkout and the admin "create order for
 * customer" screen, so the logic lives in one place.
 *
 * Everything runs inside a single DB transaction:
 *   - resolve prices + totals via OrderCalculator (integer halalas)
 *   - create the order with snapshotted subtotal / VAT / total
 *   - create order items with snapshotted sku / title / unit_price
 *   - decrement stock and write a stock_movements ledger entry per line
 * If anything fails (e.g. insufficient stock), the whole thing rolls back.
 */
class CreateOrder
{
    public function __construct(private OrderCalculator $calculator) {}

    /**
     * @param  array<int,array{product_id:int,quantity:int}>  $lines
     * @param  array{
     *     type?: string, status?: string, payment_method?: string|null,
     *     shipping_address_id?: int|null, notes?: string|null
     * }  $options
     */
    public function handle(Customer $customer, array $lines, array $options = []): Order
    {
        // Load products once, keyed by id.
        $productIds = array_column($lines, 'product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Build calculator input: [['product' => Product, 'quantity' => n], ...]
        $calcLines = [];
        foreach ($lines as $line) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                throw new \InvalidArgumentException("Product {$line['product_id']} not found.");
            }
            $calcLines[] = ['product' => $product, 'quantity' => (int) $line['quantity']];
        }

        return DB::transaction(function () use ($customer, $calcLines, $products, $options) {
            // VAT rate: take the first product's rate (all 15% by default).
            $vatRate = isset($options['vat_rate'])
                ? (float) $options['vat_rate']
                : (float) ($calcLines[0]['product']->vat_rate ?? 15.0);

            $totals = $this->calculator->calculate($calcLines, $customer, $vatRate);

            $order = Order::create([
                'number'              => $this->generateNumber(),
                'customer_id'         => $customer->id,
                'shipping_address_id' => $options['shipping_address_id'] ?? null,
                'type'                => $options['type']           ?? 'order',
                'status'              => $options['status']         ?? 'pending',
                'payment_method'      => $options['payment_method'] ?? null,
                'payment_status'      => 'pending',
                'subtotal'            => $totals['subtotal'],
                'vat_rate'            => $totals['vat_rate'],
                'vat_amount'          => $totals['vat_amount'],
                'total'               => $totals['total'],
                'notes'               => $options['notes'] ?? null,
            ]);

            foreach ($totals['items'] as $item) {
                $product = $products->get($item['product_id']);

$isProforma = ($options['type'] ?? 'order') === 'proforma';

                // For catalog items on a real order, check + decrement stock.
                if ($product && ! $isProforma) {
                    if (! $product->allow_backorder && $product->stock_quantity < $item['quantity']) {
                        throw new InsufficientStockException(
                            $item['sku'], $item['quantity'], $product->stock_quantity
                        );
                    }
                }

                $order->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'sku'        => $item['sku'],
                    'title'      => $item['title'],
                    'unit_price' => $item['unit_price'],
                    'quantity'   => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);

                if ($product && ! $isProforma) {
                    $product->decrement('stock_quantity', $item['quantity']);
                    $product->stockMovements()->create([
                        'change'         => -$item['quantity'],
                        'reason'         => 'order',
                        'reference_type' => Order::class,
                        'reference_id'   => $order->id,
                        'note'           => "Order {$order->number}",
                    ]);
                }
            }

            return $order->load('items');
        });
    }

    /**
     * Sequential, year-prefixed order number: BSM-2026-00001
     */
    private function generateNumber(): string
    {
        $year = date('Y');
        $count = Order::whereYear('created_at', $year)->count() + 1;

        return sprintf('BSM-%s-%05d', $year, $count);
    }
}
