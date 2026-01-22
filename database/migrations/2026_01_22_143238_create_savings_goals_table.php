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
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained();
            $table->string('name');
            $table->enum('type', [
                'hajj', 'umrah', 'education', 'marriage', 
                'emergency', 'business', 'other'
            ]);
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->decimal('monthly_contribution', 15, 2)->nullable();
            $table->date('target_date')->nullable();
            $table->date('start_date');
            $table->text('description')->nullable();
            $table->string('dua_reminder')->nullable();
            $table->boolean('auto_contribute')->default(false);
            $table->integer('contribution_day')->nullable(); // Day of month
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('family_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_goals');
    }
};
