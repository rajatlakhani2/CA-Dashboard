<?php

use App\Support\SafeSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_document_requirements')) {
            Schema::create('service_document_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['service_id', 'name'], 'svc_doc_req_service_name_uq');
            });
        }

        if (! Schema::hasTable('client_service_document_checks')) {
            Schema::create('client_service_document_checks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('client_service_id');
                $table->unsignedBigInteger('service_document_requirement_id');
                $table->boolean('is_received')->default(false);
                $table->timestamp('received_at')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->unique(
                    ['client_service_id', 'service_document_requirement_id'],
                    'csdc_client_req_uq'
                );
            });

            SafeSchema::addForeignKey('client_service_document_checks', 'client_service_id', 'client_services', 'csdc_client_svc_fk', 'cascade');
            SafeSchema::addForeignKey('client_service_document_checks', 'service_document_requirement_id', 'service_document_requirements', 'csdc_req_fk', 'cascade');
            SafeSchema::addForeignKey('client_service_document_checks', 'received_by', 'users', 'csdc_recv_fk', 'set null');

            return;
        }

        SafeSchema::addForeignKey('client_service_document_checks', 'client_service_id', 'client_services', 'csdc_client_svc_fk', 'cascade');
        SafeSchema::addForeignKey('client_service_document_checks', 'service_document_requirement_id', 'service_document_requirements', 'csdc_req_fk', 'cascade');
        SafeSchema::addForeignKey('client_service_document_checks', 'received_by', 'users', 'csdc_recv_fk', 'set null');
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service_document_checks');
        Schema::dropIfExists('service_document_requirements');
    }
};
