<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete()->after('is_billed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'invoice_id')) {
                $table->dropConstrainedForeignId('invoice_id');
            }
        });
    }
};
