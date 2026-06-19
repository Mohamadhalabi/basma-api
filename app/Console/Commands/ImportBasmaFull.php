<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class ImportBasmaFull extends Command
{
    protected $signature = 'import:basma-full {file : Absolute path to the .xlsx file}';
    protected $description = 'Full import: categories, products, images, stock, default price (G), and price lists (F, H)';

    private array $imageMap = [];   // row number => absolute path of extracted image

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $this->info('Extracting embedded images...');
        $this->extractImages($path);
        $this->info('Mapped ' . count($this->imageMap) . ' images to rows.');

        $this->info('Loading spreadsheet...');
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        // Price lists + customers
        $wholesaleList = PriceList::firstOrCreate(['name' => 'عماد + رحاب + أسامة'], ['is_active' => true]);
        $salmanList    = PriceList::firstOrCreate(['name' => 'سلمان'], ['is_active' => true]);
        $emad   = $this->customerByName('عماد');
        $rehab  = $this->customerByName('رحاب');
        $osama  = $this->customerByName('أسامة');
        $salman = $this->customerByName('سلمان');
        $wholesaleList->customers()->syncWithoutDetaching([$emad->id, $rehab->id, $osama->id]);
        $salmanList->customers()->syncWithoutDetaching([$salman->id]);

        $bar = $this->output->createProgressBar($highestRow);
        $currentCategory = null;
        $created = 0; $updated = 0; $withImage = 0; $cats = 0;
        $wholesalePrices = 0; $salmanPrices = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $bar->advance();

            $a = trim((string) $sheet->getCell("A{$row}")->getValue());
            $sku = trim((string) $sheet->getCell("B{$row}")->getValue());

            // Category row: text in A, empty B
            if ($a !== '' && $sku === '') {
                $currentCategory = Category::firstOrCreate(
                    ['name' => $a],
                    ['slug' => Str::slug($a) . '-' . Str::random(4)]
                );
                $cats++;
                continue;
            }

            if ($sku === '') {
                continue;
            }

            $title    = trim((string) $sheet->getCell("D{$row}")->getValue());
            $stockRaw = $this->cellValue($sheet, "E{$row}");
            $retailRaw    = $this->cellValue($sheet, "G{$row}");
            $wholesaleRaw = $this->cellValue($sheet, "F{$row}");
            $salmanRaw    = $this->cellValue($sheet, "H{$row}");

            $stock      = is_numeric($stockRaw) ? (int) $stockRaw : 0;
            $retailH    = is_numeric($retailRaw) ? (int) round(((float) $retailRaw) * 100) : 0;
            $wholesaleH = is_numeric($wholesaleRaw) ? (int) round(((float) $wholesaleRaw) * 100) : null;
            $salmanH    = is_numeric($salmanRaw) ? (int) round(((float) $salmanRaw) * 100) : null;

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

            // Category link
            if ($currentCategory) {
                $product->categories()->syncWithoutDetaching([$currentCategory->id]);
            }

            // Image (only add if product has none yet, to keep re-runs idempotent)
            if (isset($this->imageMap[$row]) && is_file($this->imageMap[$row])) {
                if ($product->getMedia('gallery')->isEmpty()) {
                    try {
                        $product->addMedia($this->imageMap[$row])
                            ->preservingOriginal()
                            ->toMediaCollection('gallery');
                        $withImage++;
                    } catch (\Throwable $e) {
                        // skip bad image, keep going
                    }
                }
            }

            // Prices
            if ($wholesaleH !== null) {
                PriceListItem::updateOrCreate(
                    ['price_list_id' => $wholesaleList->id, 'product_id' => $product->id],
                    ['price' => $wholesaleH],
                );
                $wholesalePrices++;
            }
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
        $this->info("Categories: {$cats}");
        $this->info("Products created: {$created}, updated: {$updated}, with image: {$withImage}");
        $this->info("Wholesale prices: {$wholesalePrices}, Salman prices: {$salmanPrices}");

        return self::SUCCESS;
    }

    private function extractImages(string $xlsxPath): void
    {
        $tmpDir = storage_path('app/basma-import-images');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            return;
        }

        // Read rels: rId -> media path
        $relsXml = $zip->getFromName('xl/drawings/_rels/drawing1.xml.rels');
        $drawingXml = $zip->getFromName('xl/drawings/drawing1.xml');
        if ($relsXml === false || $drawingXml === false) {
            $zip->close();
            return;
        }

        $relMap = [];
        preg_match_all('/Id="(rId\d+)"[^>]*Target="([^"]+)"/', $relsXml, $m, PREG_SET_ORDER);
        foreach ($m as $r) {
            $relMap[$r[1]] = basename($r[2]); // e.g. image1.png
        }

        // Parse anchors for row + embed id
        $dom = new \DOMDocument();
        $dom->loadXML($drawingXml);
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $xp->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xp->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        foreach ($xp->query('//xdr:oneCellAnchor | //xdr:twoCellAnchor') as $anchor) {
            $rowNode = $xp->query('.//xdr:from/xdr:row', $anchor)->item(0);
            $blip = $xp->query('.//a:blip', $anchor)->item(0);
            if (! $rowNode || ! $blip) {
                continue;
            }
            $row = ((int) $rowNode->textContent) + 1; // 0-indexed -> 1-indexed
            $embed = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
            if (! isset($relMap[$embed])) {
                continue;
            }
            $mediaName = $relMap[$embed]; // image1.png
            $data = $zip->getFromName('xl/media/' . $mediaName);
            if ($data === false) {
                continue;
            }
            $dest = $tmpDir . '/row_' . $row . '_' . $mediaName;
            file_put_contents($dest, $data);
            $this->imageMap[$row] = $dest;
        }

        $zip->close();
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