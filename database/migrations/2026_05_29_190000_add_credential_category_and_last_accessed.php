<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_credentials', function (Blueprint $table) {
            $table->string('category', 32)->default('Other')->after('portal_name');
            $table->timestamp('last_accessed_at')->nullable()->after('notes');
            $table->foreignId('last_accessed_by')->nullable()->after('last_accessed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_credentials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_accessed_by');
            $table->dropColumn(['category', 'last_accessed_at']);
        });
    }
};
