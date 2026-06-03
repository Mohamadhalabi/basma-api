<?php

namespace Tests\Feature;

use App\Actions\CreateOrder;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Services\OrderCalculator;
use App\Services\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    private function action(): CreateOrder
    {
        return new CreateOrder(new OrderCalculator(new PriceResolver()));
    }

    public function test_creates_order_with_correct_totals_and_snapshot(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'default_price'  => 10000, // 100.00 SAR
            'stock_quantity' => 50,
            'vat_rate'       => 15.0,
        ]);

        $order = $this->action()->handle($customer, [
            ['product_id' => $product->id, 'quantity' => 3],
        ]);

        $this->assertSame(30000, $order->subtotal);   // 3 x 100.00
        $this->assertSame(4500, $order->vat_amount);   // 15%
        $this->assertSame(34500, $order->total);
        $this->assertStringStartsWith('BSM-', $order->number);

        // Item snapshot
        $item = $order->items->first();
        $this->assertSame($product->sku, $item->sku);
        $this->assertSame(10000, $item->unit_price);
        $this->assertSame(30000, $item->line_total);

        // Stock decremented + ledger written
        $this->assertSame(47, $product->fresh()->stock_quantity);
        $this->assertSame(1, $product->stockMovements()->count());
    }

    public function test_uses_customer_price_list_when_creating_order(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['default_price' => 10000, 'stock_quantity' => 10]);

        $list = PriceList::create(['name' => 'B2B', 'is_active' => true]);
        $customer->priceLists()->attach($list);
        PriceListItem::create([
            'price_list_id' => $list->id,
            'product_id'    => $product->id,
            'price'         => 8000, // special 80.00
        ]);

        $order = $this->action()->handle($customer->fresh(), [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $this->assertSame(16000, $order->subtotal); // 2 x 80.00, not default
    }

    public function test_rolls_back_when_stock_insufficient(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create([
            'default_price'   => 5000,
            'stock_quantity'  => 2,
            'allow_backorder' => false,
        ]);

        $this->expectException(InsufficientStockException::class);

        try {
            $this->action()->handle($customer, [
                ['product_id' => $product->id, 'quantity' => 5],
            ]);
        } finally {
            // Nothing should have been written.
            $this->assertSame(0, Order::count());
            $this->assertSame(2, $product->fresh()->stock_quantity);
        }
    }

    public function test_handles_100_item_order(): void
    {
        $customer = Customer::factory()->create();
        $products = Product::factory()->count(100)->create([
            'default_price'  => 2500,
            'stock_quantity' => 1000,
        ]);

        $lines = $products->map(fn ($p) => [
            'product_id' => $p->id,
            'quantity'   => 4,
        ])->all();

        $order = $this->action()->handle($customer, $lines);

        $this->assertCount(100, $order->items);
        // 100 lines x 4 x 25.00 = 10,000.00 net -> 1,000,000 halalas
        $this->assertSame(1000000, $order->subtotal);
        $this->assertSame(150000, $order->vat_amount);
        $this->assertSame(1150000, $order->total);
    }
}
