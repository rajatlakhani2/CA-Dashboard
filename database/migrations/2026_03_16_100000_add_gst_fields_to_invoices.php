<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('place_of_supply')->nullable()->after('notes');
            $table->decimal('cgst', 15, 2)->default(0)->after('tax');
            $table->decimal('sgst', 15, 2)->default(0)->after('cgst');
            $table->decimal('igst', 15, 2)->default(0)->after('sgst');
            $table->boolean('reverse_charge')->default(false)->after('igst');
            $table->string('financial_year')->nullable()->after('reverse_charge');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('hsn_sac_code')->nullable()->after('description');
            $table->decimal('gst_rate', 5, 2)->default(18)->after('hsn_sac_code');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['place_of_supply', 'cgst', 'sgst', 'igst', 'reverse_charge', 'financial_year']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['hsn_sac_code', 'gst_rate']);
        });
    }
};
