<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_deposit_details', function (Blueprint $table) {
            // List of columns to drop if they exist
            $columnsToDrop = [
                'network',
                'min_deposit',
                'deposit_account',
                'deposit_arrival_time',
                'withdraw_enabled_time',
                'warning',
                'contract_address',
            ];

            // Check which columns exist in the table
            $existingColumns = Schema::getColumnListing('crypto_deposit_details');

            // Only drop columns that actually exist
            $columnsToRemove = array_intersect($columnsToDrop, $existingColumns);

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }

            // Ensure qr_code and address are correct
            $table->string('qr_code')->nullable()->change();
            $table->string('address')->change();
        });
    }

    public function down(): void
    {
        Schema::table('crypto_deposit_details', function (Blueprint $table) {
            // Add the columns back if rolling back
            if (!Schema::hasColumn('crypto_deposit_details', 'network')) {
                $table->string('network');
            }
            if (!Schema::hasColumn('crypto_deposit_details', 'min_deposit')) {
                $table->decimal('min_deposit', 18, 8);
            }
            if (!Schema::hasColumn('crypto_deposit_details', 'deposit_account')) {
                $table->string('deposit_account');
            }
            if (!Schema::hasColumn('crypto_deposit_details', 'deposit_arrival_time')) {
                $table->string('deposit_arrival_time');
            }
            if (!Schema::hasColumn('crypto_deposit_details', 'withdraw_enabled_time')) {
                $table->string('withdraw_enabled_time');
            }
            if (!Schema::hasColumn('crypto_deposit_details', 'warning')) {
                $table->text('warning')->nullable();
            }
            if (!Schema::hasColumn('crypto_deposit_details', 'contract_address')) {
                $table->string('contract_address')->nullable();
            }
        });
    }
};