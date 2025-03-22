<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Create ZRA configs table
        Schema::create('zra_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tpin', 10)->comment('Taxpayer Identification Number');
            $table->string('branch_id', 3)->comment('Branch ID');
            $table->string('device_serial', 100)->comment('Device Serial Number');
            $table->string('api_key')->nullable()->comment('API Key from ZRA');
            $table->string('environment')->default('sandbox')->comment('API environment: sandbox or production');
            $table->boolean('is_active')->default(false)->comment('Whether this config is active');
            $table->timestamp('last_initialized_at')->nullable()->comment('Last successful initialization timestamp');
            $table->timestamp('last_sync_at')->nullable()->comment('Last successful data sync timestamp');
            $table->json('additional_config')->nullable()->comment('Additional configuration as JSON');
            $table->timestamps();
            
            // Add indexes directly during table creation
            $table->index('tpin');
            $table->index('branch_id');
            $table->index('device_serial');
            $table->index('environment');
            $table->index('is_active');
        });

        // Create ZRA transaction logs table
        Schema::create('zra_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->comment('Type: initialization, sales, purchase, stock, etc.');
            $table->string('reference')->nullable()->comment('Reference number or ID');
            $table->json('request_payload')->nullable()->comment('API request data');
            $table->json('response_payload')->nullable()->comment('API response data');
            $table->string('status')->comment('Status: success, failed');
            $table->string('error_message')->nullable()->comment('Error message if any');
            $table->timestamps();
            
            // Add indexes directly during table creation
            $table->index('transaction_type');
            $table->index('reference');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('zra_transaction_logs');
        Schema::dropIfExists('zra_configs');
    }
};
