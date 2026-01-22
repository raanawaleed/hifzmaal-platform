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
        Schema::create('zakat_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->integer('hijri_year');
            $table->date('calculation_date');
            
            // Assets
            $table->decimal('cash_in_hand', 15, 2)->default(0);
            $table->decimal('cash_in_bank', 15, 2)->default(0);
            $table->decimal('gold_value', 15, 2)->default(0);
            $table->decimal('silver_value', 15, 2)->default(0);
            $table->decimal('business_inventory', 15, 2)->default(0);
            $table->decimal('investments', 15, 2)->default(0);
            $table->decimal('loans_receivable', 15, 2)->default(0);
            $table->decimal('other_assets', 15, 2)->default(0);
            
            // Liabilities
            $table->decimal('debts', 15, 2)->default(0);
            
            // Calculations
            $table->decimal('total_assets', 15, 2)->default(0);
            $table->decimal('nisab_amount', 15, 2);
            $table->enum('nisab_type', ['gold', 'silver'])->default('silver');
            $table->decimal('zakatable_amount', 15, 2)->default(0);
            $table->decimal('zakat_due', 15, 2)->default(0);
            $table->decimal('zakat_paid', 15, 2)->default(0);
            $table->decimal('zakat_remaining', 15, 2)->default(0);
            
            $table->json('asset_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['family_id', 'hijri_year']);
            $table->index('calculation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_calculations');
    }
};
