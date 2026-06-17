<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerPricesImport implements ToCollection, WithHeadingRow
{
    public int $updated = 0;
    public int $skipped = 0;
    public array $unmatched = [];

    // Cache each customer's price list so we don't re-create it every row
    private array $listCache = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = trim((string)($row['email'] ?? ''));
            $phone = trim((string)($row['phone'] ?? ''));
            $sku   = trim((string)($row['sku'] ?? ''));
            $price = $row['price'] ?? null;

            if ($sku === '' || $price === null || $price === '' || ($email === '' && $phone === '')) {
                $this->skipped++;
                $this->unmatched[] = "صف ناقص: email={$email}, phone={$phone}, sku={$sku}";
                continue;
            }

            // Find the customer by email first, then phone
            $customer = Customer::query()
                ->when($email !== '', fn ($q) => $q->where('email', $email))
                ->first();

            if (! $customer && $phone !== '') {
                $customer = Customer::where('phone', $phone)->first();
            }

            if (! $customer) {
                $this->skipped++;
                $this->unmatched[] = "عميل غير موجود: {$email} / {$phone}";
                continue;
            }

            // Find the product by SKU
            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                $this->skipped++;
                $this->unmatched[] = "منتج غير موجود: {$sku}";
                continue;
            }

            // Get or create this customer's personal price list
            $priceList = $this->priceListFor($customer);

            // Store price as halalas
            $halalas = (int) round(((float) $price) * 100);

            PriceListItem::updateOrCreate(
                ['price_list_id' => $priceList->id, 'product_id' => $product->id],
                ['price' => $halalas],
            );

            $this->updated++;
        }
    }

    private function priceListFor(Customer $customer): PriceList
    {
        if (isset($this->listCache[$customer->id])) {
            return $this->listCache[$customer->id];
        }

        // Use the customer's existing attached list if they have one
        $list = $customer->priceLists()->first();

        if (! $list) {
            $list = PriceList::create([
                'name'      => 'أسعار ' . $customer->name,
                'is_active' => true,
            ]);
            $customer->priceLists()->attach($list->id);
        }

        return $this->listCache[$customer->id] = $list;
    }
}