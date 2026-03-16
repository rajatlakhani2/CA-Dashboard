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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->string('frequency'); // monthly, quarterly, semi-annually, annually
            $table->integer('billing_day')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('last_billed_at')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->string('status')->default('active'); // active, paused, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
