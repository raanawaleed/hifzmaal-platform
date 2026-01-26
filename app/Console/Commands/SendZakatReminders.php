<?php

namespace App\Console\Commands;

use App\Models\ZakatCalculation;
use App\Events\ZakatDueReminder;
use Illuminate\Console\Command;

class SendZakatReminders extends Command
{
    protected $signature = 'zakat:send-reminders';

    protected $description = 'Send reminders for pending zakat payments';

    public function handle(): int
    {
        $this->info('Sending zakat reminders...');

        $calculations = ZakatCalculation::where('zakat_remaining', '>', 0)
            ->with('family')
            ->get();

        $sent = 0;

        foreach ($calculations as $calculation) {
            try {
                event(new ZakatDueReminder($calculation));
                $sent++;
                $this->info("Sent zakat reminder for family: {$calculation->family->name} (Year: {$calculation->hijri_year})");
            } catch (\Exception $e) {
                $this->error("Failed to send zakat reminder for calculation {$calculation->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} zakat reminders.");

        return self::SUCCESS;
    }
}