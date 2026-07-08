<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Account deletion anonymizes + soft-deletes rather than hard-deleting:
     * transactions.created_by/approved_by are restrict-on-delete FKs to
     * users, so a hard delete would fail for anyone who ever recorded a
     * transaction in a family they don't own.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
