<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('automation_logs')) {
            Schema::create('automation_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->string('type', 40);               // morning_brief, payment_reminder, document_request, weekly_summary
                $table->string('dedupe_key', 160);         // makes each message go out only once
                $table->string('channel', 20);             // whatsapp, email
                $table->string('recipient', 190)->nullable();
                $table->string('subject_label', 190)->nullable();
                $table->string('status', 20);              // sent, failed, skipped
                $table->text('detail')->nullable();
                $table->timestamps();
                $table->unique(['organization_id', 'dedupe_key', 'channel'], 'automation_logs_dedupe');
            });
        }

        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'auto_reminders')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->boolean('auto_reminders')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'auto_reminders')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('auto_reminders');
            });
        }
    }
};
