<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Customers. Kept separate from the admin `users` table.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');                 // for storefront login
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();    // customer's VAT reg (B2B)
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();         // "Home", "Warehouse"
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('SA');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // A price list can be shared, or tied to one customer.
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Assign a price list to a customer (a customer uses one active list).
        Schema::create('customer_price_list', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->primary(['customer_id', 'price_list_id']);
        });

        // Per-SKU override price inside a list. Integer halalas, VAT-exclusive.
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price'); // net price for this customer
            $table->timestamps();
            $table->unique(['price_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('customer_price_list');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('customers');
    }
};
