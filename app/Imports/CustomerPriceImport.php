<?php

namespace App\Imports;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class CustomerPriceImport implements ToCollection, WithHeadingRow
{
    public int $updated = 0;
    public int $skipped = 0;

    public function __construct(public PriceList $priceList) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // headings are lowercased+underscored by the package: 'sku', 'price'
            $sku   = trim((string)($row['sku'] ?? ''));
            $price = $row['price'] ?? null;

            if ($sku === '' || $price === null || $price === '') {
                $this->skipped++;
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                $this->skipped++;
                continue;
            }

            // price entered in SAR -> store halalas
            $halalas = (int) round(((float) $price) * 100);

            PriceListItem::updateOrCreate(
                ['price_list_id' => $this->priceList->id, 'product_id' => $product->id],
                ['price' => $halalas],
            );
            $this->updated++;
        }
    }
}