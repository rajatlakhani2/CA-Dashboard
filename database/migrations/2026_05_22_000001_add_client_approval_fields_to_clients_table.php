<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('approved')->after('status');
            $table->foreignId('created_by_user_id')->nullable()->after('manager_id')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('created_by_user_id');
            $table->foreignId('approved_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn(['approval_status', 'approved_at']);
        });
    }
};
