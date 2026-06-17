<?php

namespace App\Filament\Resources\PriceLists\RelationManagers;

use App\Models\PriceListItem;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    // We point at the price list's items, but display per-product rows.
    protected static string $relationship = 'items';

    protected static ?string $title = 'أسعار المنتجات';

    public function table(Table $table): Table
    {
        $priceList = $this->getOwnerRecord();

        return $table
            // Show ALL products, not just ones already priced
            ->query(Product::query())
            ->columns([
                TextColumn::make('sku')
                    ->label('رمز المنتج')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('المنتج')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('default_price')
                    ->label('السعر الافتراضي')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ر.س'),
                // The customer/list-specific price, pulled from price_list_items
                TextColumn::make('custom_price')
                    ->label('السعر الخاص')
                    ->state(function (Product $record) use ($priceList) {
                        $item = PriceListItem::where('price_list_id', $priceList->id)
                            ->where('product_id', $record->id)
                            ->first();
                        return $item
                            ? number_format($item->price / 100, 2) . ' ر.س'
                            : '—';
                    }),
            ])
            ->recordActions([
                Action::make('setPrice')
                    ->label('تعيين السعر')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->fillForm(function (Product $record) use ($priceList) {
                        $item = PriceListItem::where('price_list_id', $priceList->id)
                            ->where('product_id', $record->id)
                            ->first();
                        return [
                            'price' => $item ? $item->price / 100 : null,
                        ];
                    })
                    ->form([
                        TextInput::make('price')
                            ->label('السعر الخاص (ر.س)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('اتركه فارغاً ثم احفظ لإزالة السعر الخاص (سيعود للسعر الافتراضي)'),
                    ])
                    ->action(function (array $data, Product $record) use ($priceList) {
                        $price = $data['price'] ?? null;

                        if ($price === null || $price === '') {
                            // Remove the custom price -> falls back to default
                            PriceListItem::where('price_list_id', $priceList->id)
                                ->where('product_id', $record->id)
                                ->delete();
                            return;
                        }

                        PriceListItem::updateOrCreate(
                            ['price_list_id' => $priceList->id, 'product_id' => $record->id],
                            ['price' => (int) round(((float) $price) * 100)],
                        );
                    }),
            ])
            ->paginated([25, 50, 100]);
    }
}