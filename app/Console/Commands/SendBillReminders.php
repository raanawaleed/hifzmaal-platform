<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Events\BillDueReminder;
use Illuminate\Console\Command;

class SendBillReminders extends Command
{
    protected $signature = 'bills:send-reminders';

    protected $description = 'Send reminders for upcoming bills';

    public function handle(): int
    {
        $this->info('Sending bill reminders...');

        $bills = Bill::where('status', 'pending')
            ->where('is_active', true)
            ->get()
            ->filter(fn($bill) => $bill->shouldRemind());

        $sent = 0;

        foreach ($bills as $bill) {
            try {
                event(new BillDueReminder($bill));
                $sent++;
                $this->info("Sent reminder for bill: {$bill->name} (Due: {$bill->due_date->format('Y-m-d')})");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for bill {$bill->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} bill reminders.");

        return self::SUCCESS;
    }
}