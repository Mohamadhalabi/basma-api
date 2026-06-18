<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportJuneList extends Command
{
    protected $signature = 'import:june {file : Absolute path to the .xlsx file}';
    protected $description = 'Import products/stock (G=default), wholesale list (F) and Salman list (H) with customers';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info('Loading spreadsheet...');
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        // --- Create the two price lists ---
        $wholesaleList = PriceList::firstOrCreate(
            ['name' => 'عماد + رحاب + أسامة'],
            ['is_active' => true]
        );
        $salmanList = PriceList::firstOrCreate(
            ['name' => 'سلمان'],
            ['is_active' => true]
        );

        // --- Create the customers by name and attach to lists ---
        $emad   = $this->customerByName('عماد');
        $rehab  = $this->customerByName('رحاب');
        $osama  = $this->customerByName('أسامة');
        $salman = $this->customerByName('سلمان');

        // عماد, رحاب, أسامة share the wholesale list
        $wholesaleList->customers()->syncWithoutDetaching([$emad->id, $rehab->id, $osama->id]);
        // سلمان gets his own list
        $salmanList->customers()->syncWithoutDetaching([$salman->id]);

        $this->info('Processing rows...');
        $bar = $this->output->createProgressBar($highestRow);

        $created = 0; $updated = 0; $wholesalePrices = 0; $salmanPrices = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $bar->advance();

            $sku   = trim((string) $sheet->getCell("B{$row}")->getValue());
            if ($sku === '') {
                continue; // category row or blank
            }

            $title    = trim((string) $sheet->getCell("D{$row}")->getValue());
            $stockRaw = $this->cellValue($sheet, "E{$row}");
            $retailRaw    = $this->cellValue($sheet, "G{$row}"); // default price
            $wholesaleRaw = $this->cellValue($sheet, "F{$row}");
            $salmanRaw    = $this->cellValue($sheet, "H{$row}");

            $stock        = is_numeric($stockRaw) ? (int) $stockRaw : 0;
            $retailH      = is_numeric($retailRaw) ? (int) round(((float) $retailRaw) * 100) : 0;
            $wholesaleH   = is_numeric($wholesaleRaw) ? (int) round(((float) $wholesaleRaw) * 100) : null;
            $salmanH      = is_numeric($salmanRaw) ? (int) round(((float) $salmanRaw) * 100) : null;

            $product = Product::where('sku', $sku)->first();

            if ($product) {
                $product->update([
                    'default_price'  => $retailH ?: $product->default_price,
                    'stock_quantity' => $stock,
                ]);
                $updated++;
            } else {
                $product = Product::create([
                    'sku'                 => $sku,
                    'title'               => $title ?: $sku,
                    'slug'                => Str::slug($title ?: $sku) . '-' . Str::random(5),
                    'default_price'       => $retailH,
                    'stock_quantity'      => $stock,
                    'vat_rate'            => 0,
                    'low_stock_threshold' => 0,
                ]);
                $created++;
            }

            // Wholesale price (F) -> shared list
            if ($wholesaleH !== null) {
                PriceListItem::updateOrCreate(
                    ['price_list_id' => $wholesaleList->id, 'product_id' => $product->id],
                    ['price' => $wholesaleH],
                );
                $wholesalePrices++;
            }

            // Salman price (H) -> salman list
            if ($salmanH !== null) {
                PriceListItem::updateOrCreate(
                    ['price_list_id' => $salmanList->id, 'product_id' => $product->id],
                    ['price' => $salmanH],
                );
                $salmanPrices++;
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Products created: {$created}, updated: {$updated}");
        $this->info("Wholesale prices set: {$wholesalePrices}, Salman prices set: {$salmanPrices}");
        $this->info("Customers: عماد, رحاب, أسامة → wholesale list | سلمان → salman list");

        return self::SUCCESS;
    }

    private function customerByName(string $name): Customer
    {
        $existing = Customer::where('name', $name)->first();
        if ($existing) {
            return $existing;
        }
        return Customer::create([
            'name'      => $name,
            'email'     => Str::uuid() . '@placeholder.local',
            'password'  => Hash::make(Str::random(16)),
            'is_active' => true,
        ]);
    }

    private function cellValue($sheet, string $coord)
    {
        $cell = $sheet->getCell($coord);
        if ($cell->isFormula()) {
            $cached = $cell->getOldCalculatedValue();
            if ($cached !== null && $cached !== '') {
                return $cached;
            }
        }
        return $cell->getValue();
    }
}