<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class InvoiceController extends Controller
{
    public function download(Order $order)
    {
        $order->load('items', 'customer');

        // Resolve a local file path for each item's thumbnail (mpdf needs a path, not URL)
        $images = [];
        foreach ($order->items as $item) {
            $path = null;
            if ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $media = $product->getFirstMedia('gallery');
                    if ($media) {
                        $candidate = $media->getPath('thumb'); // absolute path to thumb conversion
                        if (is_file($candidate)) {
                            $path = $candidate;
                        }
                    }
                }
            }
            $images[$item->id] = $path;
        }

        $isProforma = $order->type === 'proforma';
        $docTitle = $isProforma ? 'عرض سعر' : 'فاتورة ضريبية';

        $pdf = PDF::loadView('pdf.invoice', [
            'order'    => $order,
            'docTitle' => $docTitle,
            'images'   => $images,
        ], [], [
            'format'       => 'A4',
            'default_font' => 'xbriyaz',
        ]);

        $pdf->autoScriptToLang = true;
        $pdf->autoArabic = true;
        $pdf->autoLangToFont = true;

        return $pdf->download($order->number . '.pdf');
    }
}