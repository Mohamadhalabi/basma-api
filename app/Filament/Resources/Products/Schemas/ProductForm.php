<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Attribute;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('اسم المنتج')->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')->label('الرابط (Slug)')->required(),
                TextInput::make('sku')->label('رمز المنتج (SKU)')->required(),
                Select::make('manufacturer_id')
                    ->label('الشركة المصنعة')
                    ->relationship('manufacturer', 'name')->searchable()->preload(),
                Select::make('categories')
                    ->label('التصنيفات')
                    ->relationship('categories', 'name')
                    ->multiple()->searchable()->preload(),
                // Grouped by attribute: Buttons -> 3/4/5, Frequency -> 433/315...
                Select::make('attributeValues')
                    ->label('الخصائص')
                    ->multiple()->searchable()->preload()
                    ->relationship('attributeValues', 'value')
                    ->options(function () {
                        return Attribute::with('values')->orderBy('sort_order')->get()
                            ->mapWithKeys(fn ($attr) => [
                                $attr->name => $attr->values->pluck('value', 'id')->toArray(),
                            ])->toArray();
                    }),
                TextInput::make('default_price')
                    ->label('السعر الافتراضي')->required()->numeric()->prefix('ر.س')->default(0)
                    ->dehydrateStateUsing(fn ($state) => (int) round($state * 100))
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0),
                TextInput::make('sale_price')
                    ->label('سعر التخفيض (اختياري)')->numeric()->prefix('ر.س')
                    ->helperText('اتركه فارغاً إن لم يكن هناك تخفيض')
                    ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round($state * 100) : null)
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : null),
                TextInput::make('vat_rate')->label('نسبة الضريبة (%)')->required()->numeric()->default(15.0),
                TextInput::make('stock_quantity')->label('الكمية في المخزون')->required()->numeric()->default(0),
                TextInput::make('low_stock_threshold')->label('حد التنبيه للمخزون')->required()->numeric()->default(0),
                Textarea::make('description')->label('الوصف')->columnSpanFull(),
                TextInput::make('seo_title')->label('عنوان SEO'),
                Textarea::make('seo_description')->label('وصف SEO')->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('gallery')
                    ->label('معرض الصور')->collection('gallery')
                    ->multiple()->reorderable()->image()->columnSpanFull(),
                Toggle::make('allow_backorder')->label('السماح بالطلب المسبق'),
                Toggle::make('is_active')->label('مفعّل')->default(true),
            ]);
    }
}