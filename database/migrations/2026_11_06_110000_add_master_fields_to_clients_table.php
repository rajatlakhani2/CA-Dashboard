<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Client Master: personal details, address split and WhatsApp consent. */
return new class extends Migration
{
    private array $columns = [
        'date_of_birth', 'whatsapp_number', 'whatsapp_opt_in', 'city', 'state', 'pincode', 'referred_by',
    ];

    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (! Schema::hasColumn('clients', 'whatsapp_number')) {
                $table->string('whatsapp_number', 20)->nullable();
            }
            if (! Schema::hasColumn('clients', 'whatsapp_opt_in')) {
                $table->boolean('whatsapp_opt_in')->default(false);
            }
            if (! Schema::hasColumn('clients', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (! Schema::hasColumn('clients', 'state')) {
                $table->string('state', 100)->nullable();
            }
            if (! Schema::hasColumn('clients', 'pincode')) {
                $table->string('pincode', 10)->nullable();
            }
            if (! Schema::hasColumn('clients', 'referred_by')) {
                $table->string('referred_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
