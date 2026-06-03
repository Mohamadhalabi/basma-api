<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/admin/invoice/{order}', [InvoiceController::class, 'download'])
    ->name('invoice.download')
    ->middleware('auth');