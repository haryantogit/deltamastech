<?php

namespace App\Console\Commands;

use App\Models\SalesOrder;
use Illuminate\Console\Command;

class SyncOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kledo:sync-order-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Sales Order statuses based on deliveries and invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = SalesOrder::with(['deliveries', 'invoices'])->get();
        $bar = $this->output->createProgressBar(count($orders));

        $this->info("Syncing statuses for " . count($orders) . " orders...");
        $bar->start();

        foreach ($orders as $order) {
            $statusChanged = false;
            $newStatus = $order->status;

            // 1. Check for Completed (Selesai) via Paid Invoices
            $totalInvoiceAmount = $order->invoices->sum('total_amount');
            $paidInvoiceAmount = $order->invoices->where('status', 'paid')->sum('total_amount');

            // If has invoices and all are paid (and covers the order total approximately)
            // Or simpler: if linked invoices exist and distinct invoice status is 'paid'
            if ($order->invoices->isNotEmpty()) {
                $allPaid = $order->invoices->every(fn($inv) => $inv->status === 'paid');
                if ($allPaid) {
                    $newStatus = 'completed';
                }
            }

            // 2. Check for Delivered (Terkirim) via Deliveries
            // Only if not already completed/cancelled
            if ($newStatus !== 'completed' && $newStatus !== 'cancelled') {
                if ($order->deliveries->isNotEmpty()) {
                    $newStatus = 'delivered';
                }
            }

            // Apply change if different and not already that status
            if ($order->status !== $newStatus) {
                // Only update if currently draft, confirmed, or processing (don't revert completed/cancelled unless intended)
                // Actually user said "yang sudah terikat pengiriman harusnya terkirim"
                // So we force update 'confirmed' -> 'delivered'

                if (in_array($order->status, ['draft', 'confirmed', 'processing', 'shipped'])) {
                    $order->status = $newStatus;
                    $order->save();
                    $statusChanged = true;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Order statuses synced successfully.");
    }
}
