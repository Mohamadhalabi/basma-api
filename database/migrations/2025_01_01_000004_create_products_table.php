<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->nullable()
                  ->constrained()->nullOnDelete();

            $table->string('sku')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            // PRICE: stored as integer halalas (1 SAR = 100 halalas). NEVER float.
            // This is the DEFAULT price used when a customer has no price-list entry.
            $table->unsignedBigInteger('default_price')->default(0);

            // VAT: prices stored VAT-EXCLUSIVE (net). VAT applied at order time.
            $table->decimal('vat_rate', 5, 2)->default(15.00);

            // Stock
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->boolean('allow_backorder')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
