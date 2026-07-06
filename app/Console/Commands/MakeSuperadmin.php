<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeSuperadmin extends Command
{
    protected $signature = 'hifzmaal:superadmin {email : Email of the user to promote}
                            {--revoke : Remove the superadmin role instead}';

    protected $description = 'Grant (or revoke) the superadmin role for a user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        Role::findOrCreate('superadmin', 'web');

        if ($this->option('revoke')) {
            $user->removeRole('superadmin');
            $this->info("Superadmin role revoked from {$email}.");
        } else {
            $user->assignRole('superadmin');
            $this->info("{$email} is now a superadmin.");
        }

        return self::SUCCESS;
    }
}
