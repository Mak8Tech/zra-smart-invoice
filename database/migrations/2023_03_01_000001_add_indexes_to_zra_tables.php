<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToZraTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes to ZraConfig table
        Schema::table('zra_configs', function (Blueprint $table) {
            $table->index('tpin');
            $table->index('branch_id');
            $table->index('device_serial');
            $table->index('environment');
            $table->index('is_active');
        });

        // Add indexes to ZraTransactionLog table
        Schema::table('zra_transaction_logs', function (Blueprint $table) {
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
    public function down()
    {
        // Remove indexes from ZraConfig table
        Schema::table('zra_configs', function (Blueprint $table) {
            $table->dropIndex(['tpin']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['device_serial']);
            $table->dropIndex(['environment']);
            $table->dropIndex(['is_active']);
        });

        // Remove indexes from ZraTransactionLog table
        Schema::table('zra_transaction_logs', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['reference']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });
    }
}
