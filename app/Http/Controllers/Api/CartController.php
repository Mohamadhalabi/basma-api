<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\PriceListItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CartController
{
    /**
     * Calculate cart total with customer-specific pricing
     * 
     * Expects: [
     *   { "product_id": 1, "quantity": 2 },
     *   { "product_id": 3, "quantity": 1 }
     * ]
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $customer = $request->user('sanctum');
        $items = $request->input('items');
        $cartItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) continue;

            // Get customer-specific price, or default price
            $unitPrice = $this->getProductPrice($product, $customer);

            $lineTotal = $unitPrice * $item['quantity'];
            $subtotal += $lineTotal;

            $cartItems[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'title' => $product->title,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        // VAT calculation (assuming 15% for Saudi Arabia)
        $vatRate = 15;
        $vatAmount = intdiv($subtotal * $vatRate, 100);
        $total = $subtotal + $vatAmount;

        return response()->json([
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total' => $total,
        ]);
    }

    /**
     * Create order from cart
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:transfer,card',
            'notes' => 'nullable|string',
        ]);

        $customer = $request->user('sanctum');

        // Verify address belongs to customer
        $address = Address::where('id', $validated['address_id'])
            ->where('customer_id', $customer->id)
            ->first();

        if (!$address) {
            return response()->json([
                'message' => 'Address not found or does not belong to this customer',
            ], 404);
        }

        try {
            return DB::transaction(function () use ($customer, $validated, $address) {
                $items = $validated['items'];
                $subtotal = 0;
                $orderItems = [];

                // Create order items and calculate totals
                foreach ($items as $item) {
                    $product = Product::find($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product {$item['product_id']} not found");
                    }

                    $unitPrice = $this->getProductPrice($product, $customer);
                    $quantity = $item['quantity'];
                    $lineTotal = $unitPrice * $quantity;
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];

                    // Decrement stock
                    $newStock = $product->stock_quantity - $quantity;
                    if ($newStock < 0 && !$product->allow_backorder) {
                        throw new \Exception("Insufficient stock for {$product->title}");
                    }
                    $product->update(['stock_quantity' => $newStock]);
                }

                // Calculate VAT
                $vatRate = 15;
                $vatAmount = intdiv($subtotal * $vatRate, 100);
                $total = $subtotal + $vatAmount;

                // Create order
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'order_number' => $this->generateOrderNumber(),
                    'shipping_address_id' => $address->id,
                    'subtotal' => $subtotal,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'total' => $total,
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Create order items
                foreach ($orderItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'sku' => $item['sku'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'line_total' => $item['line_total'],
                    ]);
                }

                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'subtotal' => $order->subtotal,
                        'vat_amount' => $order->vat_amount,
                        'total' => $order->total,
                        'status' => $order->status,
                        'payment_method' => $order->payment_method,
                        'items' => $order->items,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get customer's order history
     */
    public function orders(Request $request): JsonResponse
    {
        $customer = $request->user('sanctum');

        $orders = Order::where('customer_id', $customer->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Helper: Get product price (customer-specific or default)
     */
    private function getProductPrice(Product $product, ?Customer $customer): int
    {
        if ($customer && $customer->activePriceList()) {
            $priceListItem = PriceListItem::where('price_list_id', $customer->activePriceList()->id)
                ->where('product_id', $product->id)
                ->first();

            if ($priceListItem) {
                return $priceListItem->price;
            }
        }

        return $product->default_price;
    }

    /**
     * Helper: Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $count = Order::whereDate('created_at', now())->count() + 1;

        return "{$prefix}{$date}" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
