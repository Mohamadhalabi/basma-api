<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ImportSupplierSheet extends Command
{
    protected $signature = 'import:supplier {file : Absolute path to the .xlsx file}';
    protected $description = 'Import products, stock, prices, categories and images from the supplier sheet';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info('Loading spreadsheet (this can take a minute)...');
        $reader = IOFactory::createReaderForFile($path);
        if (method_exists($reader, 'setReadDataOnly')) {
            // We still need drawings, so DON'T set read-data-only; just load normally
        }
        $spreadsheet = $reader->load($path);
        // Read cached formula results (what Excel shows) instead of recalculating
        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)
            ->setCalculationCacheEnabled(false);
        $sheet = $spreadsheet->getActiveSheet();

        // --- 1. Extract all embedded images, keyed by their anchor row ---
        $this->info('Extracting embedded images...');
        $imagesByRow = [];
        $tmpDir = storage_path('app/supplier-images');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        foreach ($sheet->getDrawingCollection() as $drawing) {
            try {
                $coords = $drawing->getCoordinates();          // e.g. "C10"
                $row = (int) preg_replace('/[^0-9]/', '', $coords);

                $filename = $tmpDir . '/row_' . $row . '_' . Str::random(6);

                if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                    $filename .= '.png';
                    $img = $drawing->getImageResource();
                    ob_start();
                    imagepng($img);
                    file_put_contents($filename, ob_get_clean());
                } else {
                    $ext = strtolower($drawing->getExtension() ?: 'png');
                    $filename .= '.' . $ext;
                    $zipReader = fopen($drawing->getPath(), 'r');
                    $contents = stream_get_contents($zipReader);
                    fclose($zipReader);
                    file_put_contents($filename, $contents);
                }

                $imagesByRow[$row] = $filename;
            } catch (\Throwable $e) {
                // skip an image that can't be read
            }
        }
        $this->info('Images extracted: ' . count($imagesByRow));

        // --- 2. Walk rows: track current category, create/update products ---
        $highestRow = $sheet->getHighestDataRow();
        $currentCategory = null;
        $created = 0; $updated = 0; $catCount = 0;

        $this->info("Processing {$highestRow} rows...");
        $bar = $this->output->createProgressBar($highestRow);

        for ($row = 2; $row <= $highestRow; $row++) {
            $bar->advance();

            $a     = trim((string) $sheet->getCell("A{$row}")->getValue()); // index or category text
            $sku   = trim((string) $sheet->getCell("B{$row}")->getValue()); // SKU
            $title = trim((string) $sheet->getCell("D{$row}")->getValue()); // title
            $stockRaw = $this->cellValue($sheet, "E{$row}");  // stock
            $priceRaw = $this->cellValue($sheet, "O{$row}");  // default price (retail)

            // Category row: text in A, no SKU in B
            if ($sku === '' && $a !== '') {
                $currentCategory = Category::firstOrCreate(
                    ['name' => $a],
                    ['slug' => Str::slug($a) ?: Str::random(8)]
                );
                $catCount++;
                continue;
            }

            // Not a product row
            if ($sku === '') {
                continue;
            }

            $stock = is_numeric($stockRaw) ? (int) $stockRaw : 0;
            $priceHalalas = is_numeric($priceRaw) ? (int) round(((float) $priceRaw) * 100) : 0;

            $existing = Product::where('sku', $sku)->first();

            if ($existing) {
                // Update price + stock only; keep title/category/images
                $existing->update([
                    'default_price'  => $priceHalalas ?: $existing->default_price,
                    'stock_quantity' => $stock,
                ]);
                $updated++;
                continue;
            }

            // Create new product
            $product = Product::create([
                'sku'             => $sku,
                'title'           => $title ?: $sku,
                'slug'            => Str::slug($title ?: $sku) . '-' . Str::random(5),
                'default_price'   => $priceHalalas,
                'stock_quantity'  => $stock,
                'vat_rate'        => 0,
                'low_stock_threshold' => 0,
            ]);

            // Assign category
            if ($currentCategory) {
                $product->categories()->syncWithoutDetaching([$currentCategory->id]);
            }

            // Attach image if one is anchored on this row
            if (isset($imagesByRow[$row]) && is_file($imagesByRow[$row])) {
                try {
                    $product->addMedia($imagesByRow[$row])
                        ->preservingOriginal()
                        ->toMediaCollection('gallery');
                } catch (\Throwable $e) {
                    // ignore image failures, keep going
                }
            }

            $created++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Categories: {$catCount}, Created: {$created}, Updated: {$updated}");

        return self::SUCCESS;
    }
    
    private function cellValue($sheet, string $coord)
    {
        $cell = $sheet->getCell($coord);
        // If it's a formula, prefer the value Excel cached (what you see in the file)
        if ($cell->isFormula()) {
            $cached = $cell->getOldCalculatedValue();
            if ($cached !== null && $cached !== '') {
                return $cached;
            }
        }
        return $cell->getValue();
    }
}