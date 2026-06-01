<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_url', 512)->nullable()->after('notes');
        });

        Schema::create('document_ingestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('firm');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 128)->nullable();
            $table->string('status', 32)->default('pending_review');
            $table->string('document_type')->nullable();
            $table->json('extracted_fields')->nullable();
            $table->json('confirmed_fields')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('client_portal_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('last_accessed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_tokens');
        Schema::dropIfExists('document_ingestions');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_url');
        });
    }
};
