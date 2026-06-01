<?php

use App\Support\SafeSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        if (! Schema::hasColumn('clients', 'approval_status')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('approval_status', 20)->default('approved')->after('status');
            });
        }

        if (! Schema::hasColumn('clients', 'created_by_user_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('manager_id');
            });
        }

        if (! Schema::hasColumn('clients', 'approved_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('created_by_user_id');
            });
        }

        if (! Schema::hasColumn('clients', 'approved_by_user_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_at');
            });
        }

        SafeSchema::addForeignKey('clients', 'created_by_user_id', 'users', 'clients_created_by_fk', 'set null');
        SafeSchema::addForeignKey('clients', 'approved_by_user_id', 'users', 'clients_approved_by_fk', 'set null');
    }

    public function down(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            if (SafeSchema::hasForeignKey('clients', 'clients_approved_by_fk')) {
                $table->dropForeign('clients_approved_by_fk');
            }
            if (SafeSchema::hasForeignKey('clients', 'clients_created_by_fk')) {
                $table->dropForeign('clients_created_by_fk');
            }
            foreach (['approved_by_user_id', 'approved_at', 'created_by_user_id', 'approval_status'] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
