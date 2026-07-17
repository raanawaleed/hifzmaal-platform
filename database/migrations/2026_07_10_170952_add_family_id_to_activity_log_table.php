<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not a foreign key on purpose: activitylog entries should outlive a
     * deleted family (that's exactly the kind of event an audit log needs
     * to keep a record of), so this stays a plain indexed column rather
     * than a constrained/cascading reference.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('family_id')->nullable()->index()->after('log_name');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('family_id');
        });
    }
};
