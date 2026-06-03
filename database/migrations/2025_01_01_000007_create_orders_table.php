<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();          // BSM-2025-00001
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('shipping_address_id')->nullable()
                  ->constrained('addresses')->nullOnDelete();

            // proforma -> invoice -> order lifecycle
            $table->enum('type', ['proforma', 'invoice', 'order'])->default('order');
            $table->enum('status', [
                'draft', 'pending', 'confirmed', 'shipped', 'completed', 'cancelled',
            ])->default('draft');

            $table->enum('payment_method', ['transfer', 'card'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                  ->default('pending');

            // All monetary fields: integer halalas. Snapshotted at creation.
            $table->unsignedBigInteger('subtotal')->default(0);    // net sum of lines
            $table->decimal('vat_rate', 5, 2)->default(15.00);     // snapshot rate
            $table->unsignedBigInteger('vat_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);       // subtotal + vat

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()
                  ->constrained()->nullOnDelete();

            // SNAPSHOT product data so historical invoices never change.
            $table->string('sku');
            $table->string('title');
            $table->unsignedBigInteger('unit_price'); // net, integer halalas
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total'); // unit_price * quantity (net)
            $table->timestamps();
        });

        // Stock ledger - audit trail instead of a bare mutable number.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('change');                 // +restock, -sale
            $table->enum('reason', ['order', 'restock', 'adjustment', 'return']);
            $table->nullableMorphs('reference');       // e.g. links to an order
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
