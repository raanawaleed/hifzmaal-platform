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
        Schema::create('zakat_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakat_calculation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('zakat_recipients');
            $table->foreignId('transaction_id')->nullable()->constrained();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('type', ['zakat', 'sadaqah', 'fitrah']);
            $table->string('recipient_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['family_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_payments');
    }
};
