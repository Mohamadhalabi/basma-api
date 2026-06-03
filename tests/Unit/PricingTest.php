<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Services\OrderCalculator;
use App\Services\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_default_price_without_customer(): void
    {
        $product = Product::factory()->create(['default_price' => 10000]); // 100.00 SAR

        $price = (new PriceResolver())->resolve($product);

        $this->assertSame(10000, $price);
    }

    public function test_uses_customer_price_list_override(): void
    {
        $product  = Product::factory()->create(['default_price' => 10000]);
        $customer = Customer::factory()->create();

        $list = PriceList::create(['name' => 'Customer A', 'is_active' => true]);
        $customer->priceLists()->attach($list);
        PriceListItem::create([
            'price_list_id' => $list->id,
            'product_id'    => $product->id,
            'price'         => 8500, // 85.00 SAR special price
        ]);

        $price = (new PriceResolver())->resolve($product->fresh(), $customer->fresh());

        $this->assertSame(8500, $price);
    }

    public function test_calculates_vat_with_integer_math(): void
    {
        $product = Product::factory()->create(['default_price' => 10000]); // 100.00

        $calc = new OrderCalculator(new PriceResolver());
        $result = $calc->calculate(
            [['product' => $product, 'quantity' => 3]],
            null,
            15.0,
        );

        // 3 x 100.00 = 300.00 net -> 30000 halalas
        $this->assertSame(30000, $result['subtotal']);
        // 15% VAT = 45.00 -> 4500 halalas
        $this->assertSame(4500, $result['vat_amount']);
        // total 345.00 -> 34500
        $this->assertSame(34500, $result['total']);
    }
}
