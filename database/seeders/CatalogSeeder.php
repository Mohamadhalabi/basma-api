<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Manufacturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Manufacturers
        foreach (['KeyDIY', 'Xhorse', 'Lonsdor', 'Autel', 'OBDSTAR'] as $name) {
            Manufacturer::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            );
        }

        // Attribute: Frequency -> 433MHz, 315MHz, 868MHz
        $frequency = Attribute::firstOrCreate(
            ['slug' => 'frequency'],
            ['name' => 'Frequency', 'is_filterable' => true],
        );

        foreach (['433MHz', '315MHz', '868MHz'] as $value) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $frequency->id, 'slug' => Str::slug($value)],
                ['value' => $value],
            );
        }

        // Attribute: Transponder Chip -> example values
        $chip = Attribute::firstOrCreate(
            ['slug' => 'transponder-chip'],
            ['name' => 'Transponder Chip', 'is_filterable' => true],
        );

        foreach (['ID46', 'ID48', '4D', '8A'] as $value) {
            AttributeValue::firstOrCreate(
                ['attribute_id' => $chip->id, 'slug' => Str::slug($value)],
                ['value' => $value],
            );
        }
    }
}
