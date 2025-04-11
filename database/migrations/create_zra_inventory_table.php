<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zra_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->unique()->comment('Stock Keeping Unit - unique identifier for the product');
            $table->string('name')->comment('Product name');
            $table->text('description')->nullable()->comment('Product description');
            $table->string('category')->nullable()->comment('Product category');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Base unit price without tax');
            $table->string('tax_category')->default('VAT')->comment('Tax category (VAT, ZERO_RATED, EXEMPT, etc.)');
            $table->decimal('tax_rate', 5, 2)->default(16.00)->comment('Current tax rate percentage');
            $table->string('unit_of_measure', 20)->default('EACH')->comment('Unit of measure (EACH, KG, LITER, etc.)');
            $table->integer('current_stock')->default(0)->comment('Current stock quantity');
            $table->integer('reorder_level')->default(10)->comment('Stock level that triggers reordering');
            $table->boolean('track_inventory')->default(true)->comment('Whether to track inventory for this item');
            $table->boolean('active')->default(true)->comment('Whether the product is active and can be sold');
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for common queries
            $table->index('sku');
            $table->index('name');
            $table->index('category');
            $table->index('tax_category');
            $table->index('active');
        });

        Schema::create('zra_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('zra_inventory')->onDelete('cascade');
            $table->string('reference')->comment('Reference number (invoice, purchase order, etc.)');
            $table->string('movement_type')->comment('Type of movement (SALE, PURCHASE, ADJUSTMENT, RETURN, etc.)');
            $table->integer('quantity')->comment('Quantity moved (positive for in, negative for out)');
            $table->decimal('unit_price', 10, 2)->comment('Unit price at the time of movement');
            $table->json('metadata')->nullable()->comment('Additional information about the movement');
            $table->string('notes')->nullable()->comment('Notes about this movement');
            $table->timestamps();

            // Add indexes for common queries
            $table->index('reference');
            $table->index('movement_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zra_inventory_movements');
        Schema::dropIfExists('zra_inventory');
    }
};
