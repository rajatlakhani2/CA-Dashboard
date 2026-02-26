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
            $table->foreignId('client_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            $table->string('document_path')->nullable()->after('notes');

            // Allow user_id to be nullable if it's a client-specific renewal (optional, but good for flexibility)
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('personal_renewals')->whereNull('user_id')->delete();

        Schema::table('personal_renewals', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'document_path']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
