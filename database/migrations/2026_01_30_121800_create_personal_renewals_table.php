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
        Schema::create('personal_renewals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['LIC', 'Loan', 'Medical', 'Other']);
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['Pending', 'Paid'])->default('Pending');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Link to user
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_renewals');
    }
};
