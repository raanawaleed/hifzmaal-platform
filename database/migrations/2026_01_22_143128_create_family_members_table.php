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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('relationship', [
                'owner', 'spouse', 'son', 'daughter', 
                'father', 'mother', 'brother', 'sister', 'dependent'
            ]);
            $table->enum('role', ['owner', 'editor', 'viewer', 'approver'])->default('viewer');
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('spending_limit', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['family_id', 'user_id']);
            $table->unique(['family_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
