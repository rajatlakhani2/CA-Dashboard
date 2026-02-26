<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Main Clients Table
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('client_code')->unique();
            $table->string('name');
            $table->string('entity_type')->nullable(); // Prop, LLP, Pvt Ltd etc
            $table->string('industry')->nullable();
            
            // Legal
            $table->string('pan')->nullable()->unique();
            $table->string('gstin')->nullable()->unique();
            $table->string('cin')->nullable();
            $table->string('tan')->nullable();
            
            // Contact
            $table->text('registered_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->boolean('is_same_address')->default(true);
            
            // Primary Person
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_phone')->nullable();
            $table->string('primary_contact_email')->nullable();
            
            // Engagement
            $table->enum('category', ['A', 'B', 'C'])->default('C');
            $table->date('onboarding_date')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // User FK
            
            // Billing
            $table->string('billing_cycle')->default('Monthly'); // Monthly, Quarterly, Annual
            $table->integer('payment_terms_days')->default(30);
            $table->boolean('gst_applicable')->default(true);
            $table->string('currency')->default('INR');
            $table->string('invoice_email')->nullable();
            
            // Status & Meta
            $table->enum('status', ['Active', 'On-Hold', 'Closed'])->default('Active');
            $table->json('tags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Client Contacts (Alternate)
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('designation')->nullable();
            $table->timestamps();
        });

        // 3. Client Services
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('service_name'); // GST, Income Tax, etc.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Closed'])->default('Active');
            $table->timestamps();
        });

        // 4. Client Documents
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('document_type'); // KYC, Agreement, etc.
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->date('uploaded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
        Schema::dropIfExists('client_services');
        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
