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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('name');
            $table->enum('type', [
                'electricity', 'gas', 'water', 'internet', 
                'mobile', 'rent', 'school_fees', 'other'
            ]);
            $table->decimal('amount', 15, 2);
            $table->decimal('average_amount', 15, 2)->nullable();
            $table->date('due_date');
            $table->enum('frequency', ['monthly', 'quarterly', 'yearly']);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('auto_pay')->default(false);
            $table->foreignId('account_id')->nullable()->constrained();
            $table->string('provider')->nullable();
            $table->string('account_number')->nullable();
            $table->json('split_members')->nullable(); // For bill splitting
            $table->integer('reminder_days')->default(3);
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->date('last_paid_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['family_id', 'due_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
