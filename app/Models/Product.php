<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory;

    protected $fillable = [
        'manufacturer_id', 'sku', 'title', 'slug', 'description',
        'seo_title', 'seo_description', 'default_price', 'vat_rate',
        'stock_quantity', 'low_stock_threshold', 'allow_backorder', 'is_active',
    ];

    protected $casts = [
        'default_price'   => 'integer', // halalas
        'vat_rate'        => 'decimal:2',
        'stock_quantity'  => 'integer',
        'allow_backorder' => 'boolean',
        'is_active'       => 'boolean',
    ];

    // ---- Relationships ----
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ---- Media: gallery + auto thumbnail/frontend sizes ----
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(150)->height(150)->sharpen(10)->nonQueued();
        $this->addMediaConversion('card')->width(400)->height(400)->nonQueued();
        $this->addMediaConversion('full')->width(1200)->height(1200)->nonQueued();
    }

    // Helper: is this product low on stock?
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function thumbUrl(): ?string
    {
        $media = $this->getFirstMedia('gallery');
        return $media ? $media->getUrl('thumb') : null;
    }
}
