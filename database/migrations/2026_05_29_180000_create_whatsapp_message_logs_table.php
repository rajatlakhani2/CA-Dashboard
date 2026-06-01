<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('direction', 8);
            $table->text('body')->nullable();
            $table->string('intent', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
        });

        Schema::table('compliance_risk_scores', function (Blueprint $table) {
            $table->boolean('predicted_miss')->default(false)->after('level');
            $table->string('model_version', 16)->default('v2')->after('predicted_miss');
        });
    }

    public function down(): void
    {
        Schema::table('compliance_risk_scores', function (Blueprint $table) {
            $table->dropColumn(['predicted_miss', 'model_version']);
        });

        Schema::dropIfExists('whatsapp_message_logs');
    }
};
