<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A family_members row with an email but no user_id is a pending
     * invitation — the invitee hasn't (or hadn't yet) linked their own
     * account to that roster slot.
     */
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->string('invitation_token')->nullable()->unique()->after('email');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropColumn(['invitation_token', 'invitation_expires_at', 'invitation_accepted_at']);
        });
    }
};
