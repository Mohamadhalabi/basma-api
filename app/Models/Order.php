<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'shipping_address_id', 'type', 'status',
        'payment_method', 'payment_status', 'subtotal', 'vat_rate',
        'vat_amount', 'total', 'notes',
    ];

    protected $casts = [
        'subtotal'   => 'integer', // halalas
        'vat_amount' => 'integer',
        'total'      => 'integer',
        'vat_rate'   => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
