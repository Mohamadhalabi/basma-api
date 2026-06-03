<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label('رقم الطلب')
                    ->required(),
                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->required(),
                Select::make('shipping_address_id')
                    ->label('عنوان الشحن')
                    ->relationship('shippingAddress', 'id'),
                Select::make('type')
                    ->label('النوع')
                    ->options(['proforma' => 'عرض سعر مبدئي', 'invoice' => 'فاتورة', 'order' => 'طلب'])
                    ->default('order')
                    ->required(),
                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'قيد الانتظار',
                        'confirmed' => 'مؤكد',
                        'shipped' => 'تم الشحن',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->default('draft')
                    ->required(),
                Select::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options(['transfer' => 'تحويل بنكي', 'card' => 'بطاقة']),
                Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(['pending' => 'قيد الانتظار', 'paid' => 'مدفوع', 'failed' => 'فشل', 'refunded' => 'مسترد'])
                    ->default('pending')
                    ->required(),
                TextInput::make('subtotal')
                    ->label('المجموع الفرعي')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('vat_rate')
                    ->label('نسبة الضريبة (%)')
                    ->required()
                    ->numeric()
                    ->default(15.0),
                TextInput::make('vat_amount')
                    ->label('قيمة الضريبة')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->label('الإجمالي')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }
}