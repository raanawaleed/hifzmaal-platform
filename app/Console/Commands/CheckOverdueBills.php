<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Events\BillOverdue;
use Illuminate\Console\Command;

class CheckOverdueBills extends Command
{
    protected $signature = 'bills:check-overdue';

    protected $description = 'Check and mark overdue bills';

    public function handle(): int
    {
        $this->info('Checking for overdue bills...');

        $bills = Bill::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        $marked = 0;

        foreach ($bills as $bill) {
            try {
                $bill->update(['status' => 'overdue']);
                event(new BillOverdue($bill));
                $marked++;
                $this->warn("Marked bill as overdue: {$bill->name} (Due: {$bill->due_date->format('Y-m-d')})");
            } catch (\Exception $e) {
                $this->error("Failed to mark bill {$bill->id} as overdue: {$e->getMessage()}");
            }
        }

        $this->info("Marked {$marked} bills as overdue.");

        return self::SUCCESS;
    }
}