<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'password',
        'company_name', 'vat_number', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $last = static::orderByDesc('id')->first();
                $next = $last ? ((int) str_replace('BASC', '', $last->code ?? 'BASC0000')) + 1 : 1;
                $customer->code = 'BASC' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function priceLists(): BelongsToMany
    {
        return $this->belongsToMany(PriceList::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // The single active price list driving this customer's prices (if any).
    public function activePriceList(): ?PriceList
    {
        return $this->priceLists()->where('is_active', true)->first();
    }
}
