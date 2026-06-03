<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Imports\CustomersImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importCustomers')
                ->label('رفع العملاء (Excel)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel (الأعمدة: Name, Phone, Email, Address)')
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
                    $import = new CustomersImport();
                    $path = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                    Excel::import($import, $path);

                    Notification::make()
                        ->title("تم إضافة {$import->created} عميل، وتم تجاهل {$import->skipped}")
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('إضافة عميل'),
        ];
    }
}