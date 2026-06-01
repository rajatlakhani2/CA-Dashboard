<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_credentials')) {
            return;
        }

        if (! Schema::hasColumn('client_credentials', 'category')) {
            Schema::table('client_credentials', function (Blueprint $table) {
                $table->string('category', 32)->default('Other')->after('portal_name');
            });
        }

        if (! Schema::hasColumn('client_credentials', 'last_accessed_at')) {
            Schema::table('client_credentials', function (Blueprint $table) {
                $table->timestamp('last_accessed_at')->nullable()->after('notes');
            });
        }

        if (! Schema::hasColumn('client_credentials', 'last_accessed_by')) {
            Schema::table('client_credentials', function (Blueprint $table) {
                $table->foreignId('last_accessed_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_credentials')) {
            return;
        }

        Schema::table('client_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('client_credentials', 'last_accessed_by')) {
                $table->dropConstrainedForeignId('last_accessed_by');
            }
            foreach (['category', 'last_accessed_at'] as $column) {
                if (Schema::hasColumn('client_credentials', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
