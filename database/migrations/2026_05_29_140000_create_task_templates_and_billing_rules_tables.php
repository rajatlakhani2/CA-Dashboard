<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('due_days_offset')->default(7);
            $table->string('priority')->default('Medium');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('billing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_type')->default('fixed_fee');
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->boolean('use_due_amount')->default(true);
            $table->boolean('auto_draft_invoice')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_rules');
        Schema::dropIfExists('task_templates');
    }
};
