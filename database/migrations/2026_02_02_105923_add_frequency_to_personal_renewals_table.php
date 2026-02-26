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
        Schema::table('personal_renewals', function (Blueprint $table) {
            $table->string('frequency')->nullable()->after('amount'); // Monthly, Quarterly, Yearly, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_renewals', function (Blueprint $table) {
            $table->dropColumn('frequency');
        });
    }
};
