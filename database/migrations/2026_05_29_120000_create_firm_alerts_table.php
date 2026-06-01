<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->string('severity', 16)->default('warning');
            $table->string('title');
            $table->text('message');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('fingerprint', 64)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['dismissed_at', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_alerts');
    }
};
