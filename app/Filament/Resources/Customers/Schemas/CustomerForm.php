<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required(),
                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('الجوال')
                    ->tel(),
                TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->revealable()
                    // Only required when creating a new customer
                    ->required(fn (string $operation) => $operation === 'create')
                    // Hash only if a value was entered; otherwise keep the old one
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),
                TextInput::make('company_name')
                    ->label('اسم الشركة'),
                TextInput::make('vat_number')
                    ->label('الرقم الضريبي'),
                Toggle::make('is_active')
                    ->label('مفعّل')
                    ->default(true),
                Repeater::make('addresses')
                    ->label('العناوين')
                    ->relationship('addresses')
                    ->schema([
                        TextInput::make('label')
                            ->label('التسمية (مثل: المنزل، المستودع)'),
                        TextInput::make('line1')
                            ->label('العنوان')
                            ->required(),
                        TextInput::make('city')
                            ->label('المدينة'),
                        TextInput::make('region')
                            ->label('المنطقة'),
                        TextInput::make('postal_code')
                            ->label('الرمز البريدي'),
                        Toggle::make('is_default')
                            ->label('العنوان الافتراضي'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['line1'] ?? 'عنوان جديد')
                    ->addActionLabel('إضافة عنوان')
                    ->columnSpanFull(),
            ]);
    }
}