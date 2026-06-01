<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'name']);
        });

        Schema::create('client_service_document_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_document_requirement_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_received')->default(false);
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['client_service_id', 'service_document_requirement_id'],
                'client_service_doc_req_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service_document_checks');
        Schema::dropIfExists('service_document_requirements');
    }
};
