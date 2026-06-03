<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\PriceResolver;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOrderBuilder extends Page
{
    protected static ?string $navigationLabel = 'إنشاء طلب';
    protected static ?string $title = 'إنشاء طلب جديد';
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected string $view = 'filament.pages.create-order-builder';

    public array $customers = [];
    public array $products = [];
    public array $priceLists = [];

    // Edit mode
    public ?int $editOrderId = null;
    public array $editData = [];   // pre-fill data passed to Alpine

    public function mount(): void
    {
        $this->customers = Customer::where('is_active', true)
            ->orderBy('name')->get(['id', 'name'])->toArray();

        $this->products = Product::where('is_active', true)
            ->with('media')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'sku'   => $p->sku,
                'title' => $p->title,
                'def'   => (int) $p->default_price,
                'thumb' => $p->thumbUrl(),
            ])->toArray();

        $resolver = app(PriceResolver::class);
        $allProducts = Product::where('is_active', true)->get();
        foreach (Customer::where('is_active', true)->get() as $customer) {
            $map = $resolver->resolveMany($allProducts, $customer);
            $overrides = [];
            foreach ($allProducts as $p) {
                if ($map[$p->id] !== (int) $p->default_price) {
                    $overrides[$p->id] = $map[$p->id];
                }
            }
            if ($overrides) {
                $this->priceLists[(string) $customer->id] = $overrides;
            }
        }

        // Edit mode: ?order=ID
        $orderId = request()->query('order');
        if ($orderId) {
            $order = Order::with('items')->find($orderId);
            if ($order) {
                $this->editOrderId = $order->id;
                static::$title = 'تعديل الطلب: ' . $order->number;

                $this->editData = [
                    'docType'       => $order->type,
                    'status'        => $order->status,
                    'paymentStatus' => $order->payment_status,
                    'customerId'    => (string) $order->customer_id,
                    'vat'           => (float) $order->vat_rate,
                    'discountType'  => $order->discount_type,
                    'discountValue' => (float) $order->discount_value,
                    'shipping'      => $order->shipping / 100,
                    'serviceFees'   => $order->service_fees / 100,
                    'notes'         => $order->notes,
                    'items'         => $order->items->map(function ($it) {
                        $isCustom = empty($it->product_id);
                        return [
                            'uid'          => $it->id,
                            'cid'          => $it->product_id,
                            'sku'          => $it->sku,
                            'custom'       => $isCustom,
                            'title'        => $it->title,
                            'note'         => $it->note,
                            'thumb'        => $it->product_id ? optional(Product::find($it->product_id))->thumbUrl() : null,
                            'priceHalalas' => $isCustom ? 0 : $it->unit_price,
                            'priceSar'     => $isCustom ? $it->unit_price / 100 : 0,
                            'qty'          => $it->quantity,
                        ];
                    })->toArray(),
                ];
            }
        }
    }

    public function save(array $payload): void
    {
        $docType = $payload['docType'] ?? 'order';
        $vatRate = (float) ($payload['vat'] ?? 0);
        $items   = $payload['items'] ?? [];

        if (empty($items)) {
            $this->notify('danger', 'أضف بنداً واحداً على الأقل');
            return;
        }

        try {
            DB::transaction(function () use ($payload, $docType, $vatRate, $items) {
                $customer = $this->resolveCustomer($payload);

                // Build lines + subtotal
                $subtotal = 0;
                $lines = [];
                foreach ($items as $it) {
                    $qty  = max(1, (int) ($it['qty'] ?? 1));
                    $unit = !empty($it['custom'])
                        ? (int) round(((float) ($it['priceSar'] ?? 0)) * 100)
                        : (int) ($it['priceHalalas'] ?? 0);
                    $lineTotal = $unit * $qty;
                    $subtotal += $lineTotal;
                    $lines[] = [
                        'product_id' => $it['cid'] ?? null,
                        'sku'        => $it['sku'] ?? 'CUSTOM',
                        'title'      => $it['title'] ?? 'بند مخصص',
                        'note'       => $it['note'] ?? null,
                        'unit_price' => $unit,
                        'quantity'   => $qty,
                        'line_total' => $lineTotal,
                    ];
                }

                $discountType  = $payload['discountType'] ?? 'fixed';
                $discountValue = (float) ($payload['discountValue'] ?? 0);
                $discountAmount = $discountType === 'percent'
                    ? (int) round($subtotal * $discountValue / 100)
                    : (int) round($discountValue * 100);
                $discountAmount = min($discountAmount, $subtotal);

                $shipping = (int) round(((float) ($payload['shipping'] ?? 0)) * 100);
                $serviceFees = (int) round(((float) ($payload['serviceFees'] ?? 0)) * 100);
                $vatAmount = (int) round($subtotal * $vatRate / 100);
                $total = $subtotal - $discountAmount + $shipping + $serviceFees + $vatAmount;

                $isProforma = $docType === 'proforma';

                $orderData = [
                    'customer_id'     => $customer->id,
                    'type'            => $docType,
                    'status'          => $isProforma ? 'draft' : ($payload['status'] ?? 'pending'),
                    'payment_status'  => $payload['paymentStatus'] ?? 'pending',
                    'subtotal'        => $subtotal,
                    'discount_type'   => $discountType,
                    'discount_value'  => $discountValue,
                    'discount_amount' => $discountAmount,
                    'shipping'        => $shipping,
                    'service_fees'    => $serviceFees,
                    'vat_rate'        => $vatRate,
                    'vat_amount'      => $vatAmount,
                    'total'           => $total,
                    'notes'           => $payload['notes'] ?? null,
                ];

                if ($this->editOrderId) {
                    // ---- EDIT MODE ----
                    $order = Order::with('items')->findOrFail($this->editOrderId);
                    $wasProforma = $order->type === 'proforma';

                    // Reverse stock from OLD items (only if the old order was a real order)
                    if (! $wasProforma) {
                        foreach ($order->items as $old) {
                            if ($old->product_id) {
                                $p = Product::find($old->product_id);
                                if ($p) {
                                    $p->increment('stock_quantity', $old->quantity);
                                    $p->stockMovements()->create([
                                        'change'         => $old->quantity,
                                        'reason'         => 'adjustment',
                                        'reference_type' => Order::class,
                                        'reference_id'   => $order->id,
                                        'note'           => "Edit reverse {$order->number}",
                                    ]);
                                }
                            }
                        }
                    }

                    // Delete old items, update order, write new items
                    $order->items()->delete();
                    $order->update($orderData);

                    foreach ($lines as $line) {
                        $order->items()->create($line);
                        if (! $isProforma && $line['product_id']) {
                            $p = Product::find($line['product_id']);
                            if ($p) {
                                $p->decrement('stock_quantity', $line['quantity']);
                                $p->stockMovements()->create([
                                    'change'         => -$line['quantity'],
                                    'reason'         => 'order',
                                    'reference_type' => Order::class,
                                    'reference_id'   => $order->id,
                                    'note'           => "Edit apply {$order->number}",
                                ]);
                            }
                        }
                    }

                    $this->notify('success', 'تم تحديث الطلب: ' . $order->number);
                } else {
                    // ---- CREATE MODE ----
                    $order = Order::create(array_merge($orderData, [
                        'number' => $this->generateNumber(),
                    ]));

                    foreach ($lines as $line) {
                        $order->items()->create($line);
                        if (! $isProforma && $line['product_id']) {
                            $p = Product::find($line['product_id']);
                            if ($p) {
                                $p->decrement('stock_quantity', $line['quantity']);
                                $p->stockMovements()->create([
                                    'change'         => -$line['quantity'],
                                    'reason'         => 'order',
                                    'reference_type' => Order::class,
                                    'reference_id'   => $order->id,
                                    'note'           => "Order {$order->number}",
                                ]);
                            }
                        }
                    }

                    $this->notify('success', 'تم الحفظ بنجاح: ' . $order->number);
                }
            });

            // After edit, go back to the orders list; after create, stay & reset
            if ($this->editOrderId) {
                $this->redirect('/admin/orders');
            } else {
                $this->dispatch('order-saved');
            }
        } catch (\Throwable $e) {
            $this->notify('danger', 'تعذّر الحفظ: ' . $e->getMessage());
        }
    }

    private function resolveCustomer(array $payload): Customer
    {
        if (! empty($payload['customerId'])) {
            return Customer::findOrFail($payload['customerId']);
        }
        $nc = $payload['nc'] ?? [];
        if (empty($nc['name'])) {
            throw new \RuntimeException('يجب اختيار العميل أو إدخال اسم عميل جديد');
        }
        return Customer::create([
            'name'      => $nc['name'],
            'phone'     => $nc['phone'] ?? null,
            'email'     => $nc['email'] ?: Str::uuid() . '@placeholder.local',
            'password'  => Hash::make(Str::random(16)),
            'is_active' => true,
        ]);
    }

    private function generateNumber(): string
    {
        $year = date('Y');
        $count = Order::whereYear('created_at', $year)->count() + 1;
        return sprintf('BSM-%s-%05d', $year, $count);
    }

    private function notify(string $type, string $msg): void
    {
        Notification::make()->title($msg)->{$type}()->send();
    }
}