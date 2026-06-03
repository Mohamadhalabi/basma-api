<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Imports\CustomerPriceImport;
use App\Models\PriceList;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Maatwebsite\Excel\Facades\Excel;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadPrices')
                ->label('رفع قائمة أسعار (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel (عمودان: SKU و Price)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    $customer = $this->record;

                    // Find or create this customer's price list
                    $priceList = $customer->priceLists()->first();
                    if (! $priceList) {
                        $priceList = PriceList::create([
                            'name' => 'أسعار ' . $customer->name,
                            'is_active' => true,
                        ]);
                        $customer->priceLists()->attach($priceList);
                    }

                    $import = new CustomerPriceImport($priceList);
                    Excel::import($import, storage_path('app/' . $data['file']));

                    Notification::make()
                        ->title("تم تحديث {$import->updated} سعر، وتم تجاهل {$import->skipped}")
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}