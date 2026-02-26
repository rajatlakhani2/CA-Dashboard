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
        // Drop existing tables if they exist to ensure clean schema for new engine
        Schema::dropIfExists('service_dues');
        Schema::dropIfExists('client_services');
        Schema::dropIfExists('services');

        // 1. Master Services Table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "GSTR-1", "Income Tax Filing"
            $table->string('code')->unique(); // e.g., "GSTR1", "ITR"
            $table->string('description')->nullable();
            $table->enum('frequency', ['Monthly', 'Quarterly', 'Half-Yearly', 'Annually', 'One-Time']);
            $table->integer('due_day')->nullable(); // e.g., 11 for GSTR-1, 20 for GSTR-3B
            $table->integer('due_month')->nullable(); // e.g., 7 (July) for ITR, null for Monthly
            $table->boolean('is_statutory')->default(true); // True for Govt compliance
            $table->timestamps();
        });

        // 2. Client Services Mapping Table (Which client has opted for which service)
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->integer('custom_due_day')->nullable(); // Override master due day if needed
            $table->timestamps();

            $table->unique(['client_id', 'service_id']); // Prevent duplicate mapping
        });

        // 3. Service Dues Table (Actual tracking instances)
        Schema::create('service_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_service_id')->constrained()->onDelete('cascade');
            $table->date('due_date');
            $table->enum('status', ['Pending', 'Overdue', 'Completed', 'Extended'])->default('Pending');
            $table->date('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Index for dashboard queries
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_dues');
        Schema::dropIfExists('client_services');
        Schema::dropIfExists('services');
    }
};
