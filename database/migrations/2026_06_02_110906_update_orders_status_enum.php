<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','pending','processing','on_hold','confirmed','shipped','completed','cancelled') NOT NULL DEFAULT 'draft'");
    }
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft','pending','confirmed','shipped','completed','cancelled') NOT NULL DEFAULT 'draft'");
    }
};