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
        Schema::create('zakat_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->enum('category', [
                'fuqara', // The poor
                'masakin', // The needy
                'amilin', // Zakat administrators
                'muallaf', // New Muslims
                'riqab', // Freeing slaves
                'gharimin', // Those in debt
                'fisabilillah', // In the cause of Allah
                'ibnus_sabil' // Stranded travelers
            ]);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('zakat_recipients');
    }
};
