<?php

namespace App\Filament\Resources\PriceLists\Pages;

use App\Filament\Resources\PriceLists\PriceListResource;
use App\Imports\CustomerPricesImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListPriceLists extends ListRecords
{
    protected static string $resource = PriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importPrices')
                ->label('رفع أسعار العملاء (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel (الأعمدة: Email, Phone, SKU, Price)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('imports'),
                ])
                ->action(function (array $data) {
                    $import = new CustomerPricesImport();
                    $path = Storage::disk('local')->path($data['file']);
                    Excel::import($import, $path);

                    $body = "تم تحديث {$import->updated} سعر، وتم تجاهل {$import->skipped}";
                    if (! empty($import->unmatched)) {
                        $sample = implode("\n", array_slice($import->unmatched, 0, 10));
                        $body .= "\n\nأمثلة على الصفوف المتجاهلة:\n" . $sample;
                    }

                    Notification::make()
                        ->title('اكتمل رفع الأسعار')
                        ->body($body)
                        ->success()
                        ->persistent()
                        ->send();
                }),
            CreateAction::make()->label('إضافة قائمة أسعار'),
        ];
    }
}