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
        Schema::table('service_dues', function (Blueprint $table) {
            $table->enum('billing_status', ['Pending', 'Unbilled', 'Billed', 'Non-Billable'])->default('Pending')->after('status');
            $table->decimal('billing_amount', 10, 2)->nullable()->after('billing_status');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null')->after('billing_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_dues', function (Blueprint $table) {
            //
        });
    }
};
