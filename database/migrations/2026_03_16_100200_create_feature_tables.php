<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature 4: Expenses
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->string('category');
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->date('expense_date');
                $table->string('payment_mode')->default('Cash');
                $table->string('receipt_path')->nullable();
                $table->string('vendor')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->boolean('is_recurring')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Feature 5: Time Tracking
        if (!Schema::hasTable('time_entries')) {
            Schema::create('time_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->decimal('hours', 5, 2);
                $table->string('description')->nullable();
                $table->boolean('is_billable')->default(true);
                $table->timestamps();
            });
        }

        // Feature 8: DSC Tracker
        if (!Schema::hasTable('dscs')) {
            Schema::create('dscs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->string('holder_name');
                $table->string('class_type')->default('Class 2');
                $table->string('provider')->nullable();
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->string('status')->default('Active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Feature 6: RBAC
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('staff')->after('email');
            });
        }

        // Feature 9: Enhanced Documents (check for existing columns)
        Schema::table('client_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('client_documents', 'category')) {
                $table->string('category')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('client_documents', 'financial_year')) {
                $table->string('financial_year')->nullable()->after('category');
            }
            if (!Schema::hasColumn('client_documents', 'version')) {
                $table->integer('version')->default(1)->after('financial_year');
            }
            if (!Schema::hasColumn('client_documents', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('version');
            }
            if (!Schema::hasColumn('client_documents', 'tags')) {
                $table->json('tags')->nullable()->after('expiry_date');
            }
        });

        // Feature 10: TDS Management
        if (!Schema::hasTable('tds_entries')) {
            Schema::create('tds_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                $table->decimal('tds_rate', 5, 2)->default(10);
                $table->decimal('tds_amount', 15, 2);
                $table->boolean('certificate_received')->default(false);
                $table->date('certificate_date')->nullable();
                $table->string('certificate_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Feature 14: Client Onboarding
        if (!Schema::hasTable('onboarding_checklists')) {
            Schema::create('onboarding_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->string('item');
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Feature 12: Multi-Branch
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('address')->nullable();
                $table->string('gstin')->nullable();
                $table->string('state_code')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // Feature 12: Scoping
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('onboarding_checklists');
        Schema::dropIfExists('tds_entries');
        Schema::dropIfExists('dscs');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('expenses');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn(['category', 'financial_year', 'version', 'expiry_date', 'tags']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
