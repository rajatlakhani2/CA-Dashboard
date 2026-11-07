<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Firms can keep several bank accounts (Settings → Company & invoices) and
 * pick which one prints on each invoice. The single set of bank_* settings
 * each firm had before becomes its first (default) account.
 */
return new class extends Migration
{
    private const LEGACY_KEYS = [
        'bank_name' => 'bank_name',
        'bank_account_name' => 'account_name',
        'bank_account_number' => 'account_number',
        'bank_ifsc' => 'ifsc',
        'bank_upi' => 'upi_id',
    ];

    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('label', 60)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('account_name', 255)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('ifsc', 20)->nullable();
            $table->string('branch', 100)->nullable();
            $table->string('upi_id', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_default']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('payment_url')
                ->constrained('bank_accounts')->nullOnDelete();
        });

        $this->copyLegacyBankSettings();
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
        });
        Schema::dropIfExists('bank_accounts');
    }

    private function copyLegacyBankSettings(): void
    {
        $hasOrgColumn = Schema::hasColumn('settings', 'organization_id');

        $read = function (?int $orgId) use ($hasOrgColumn): array {
            $values = [];
            foreach (self::LEGACY_KEYS as $key => $column) {
                $value = null;
                if ($hasOrgColumn && $orgId) {
                    $value = DB::table('settings')->where('organization_id', $orgId)->where('key', $key)->value('value');
                }
                // Same fallback Setting::get() uses: firm row first, then the shared row.
                if ($value === null || $value === '') {
                    $query = DB::table('settings')->where('key', $key);
                    if ($hasOrgColumn) {
                        $query->whereNull('organization_id');
                    }
                    $value = $query->value('value');
                }
                $values[$column] = $value !== null && trim((string) $value) !== '' ? trim((string) $value) : null;
            }

            return $values;
        };

        $orgIds = Schema::hasTable('organizations') ? DB::table('organizations')->pluck('id')->all() : [];
        if ($orgIds === []) {
            $orgIds = [null];
        }

        foreach ($orgIds as $orgId) {
            $values = $read($orgId);
            if (! array_filter($values)) {
                continue;
            }
            DB::table('bank_accounts')->insert($values + [
                'organization_id' => $orgId,
                'label' => 'Primary',
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
