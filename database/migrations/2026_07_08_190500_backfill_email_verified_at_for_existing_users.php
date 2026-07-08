<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification is being turned on for the first time. Anyone who
     * registered before this point already trusted us with real transaction
     * data — don't retroactively lock them out; treat them as verified.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally irreversible — we can't tell which rows we touched.
    }
};
