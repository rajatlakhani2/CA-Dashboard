<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tenantTables = [
        'users',
        'clients',
        'branches',
        'tasks',
        'invoices',
        'service_dues',
        'payments',
        'settings',
        'firm_alerts',
    ];

    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('plan', 32)->default('professional');
            $table->unsignedSmallInteger('seat_limit')->default(25);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('settings')) {
            try {
                Schema::table('settings', function (Blueprint $table) {
                    $table->dropUnique(['key']);
                });
            } catch (\Throwable) {
                // Index name may differ across environments.
            }
            Schema::table('settings', function (Blueprint $table) {
                $table->unique(['organization_id', 'key']);
            });
        }

        if (Schema::hasTable('users')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            } catch (\Throwable) {
            }
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['organization_id', 'email']);
            });
        }

        $this->backfillDefaultOrganization();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }

        if (Schema::hasTable('users')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['organization_id', 'email']);
                    $table->unique('email');
                });
            } catch (\Throwable) {
            }
        }

        if (Schema::hasTable('settings')) {
            try {
                Schema::table('settings', function (Blueprint $table) {
                    $table->dropUnique(['organization_id', 'key']);
                    $table->unique('key');
                });
            } catch (\Throwable) {
            }
        }

        Schema::dropIfExists('organizations');
    }

    private function backfillDefaultOrganization(): void
    {
        $companyName = DB::table('settings')->where('key', 'company_name')->value('value') ?: 'My CA Firm';
        $slug = 'workspace-' . substr(md5($companyName), 0, 8);

        $orgId = DB::table('organizations')->insertGetId([
            'name' => $companyName,
            'slug' => $slug,
            'plan' => 'professional',
            'seat_limit' => 25,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->tenantTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')) {
                DB::table($table)->whereNull('organization_id')->update(['organization_id' => $orgId]);
            }
        }
    }
};
