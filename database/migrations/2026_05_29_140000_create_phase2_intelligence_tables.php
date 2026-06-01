<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 32)->default('phone');
            $table->text('notes')->nullable();
            $table->date('promise_date')->nullable();
            $table->string('next_action')->nullable();
            $table->timestamp('contacted_at');
            $table->timestamps();

            $table->index(['client_id', 'contacted_at']);
        });

        Schema::create('compliance_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('level', 16)->default('low');
            $table->json('signals')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('fingerprint', 64)->unique();
            $table->timestamp('scored_at');
            $table->timestamps();

            $table->index(['level', 'score']);
            $table->index(['client_id', 'scored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_risk_scores');
        Schema::dropIfExists('collection_follow_ups');
    }
};
