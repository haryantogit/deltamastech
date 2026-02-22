<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\SalesInvoice::observe(\App\Observers\SalesInvoiceObserver::class);
        \App\Models\PurchaseInvoice::observe(\App\Observers\PurchaseInvoiceObserver::class);
        \App\Models\ManufacturingOrder::observe(\App\Observers\ManufacturingOrderObserver::class);
        \App\Models\WarehouseTransferItem::observe(\App\Observers\WarehouseTransferItemObserver::class);
        \App\Models\StockAdjustmentItem::observe(\App\Observers\StockAdjustmentItemObserver::class);
        \App\Models\WarehouseTransfer::observe(\App\Observers\WarehouseTransferObserver::class);
        \App\Models\StockAdjustment::observe(\App\Observers\StockAdjustmentObserver::class);
        \App\Models\PurchaseDelivery::observe(\App\Observers\PurchaseDeliveryObserver::class);
        \App\Models\SalesDelivery::observe(\App\Observers\SalesDeliveryObserver::class);
        \App\Models\DebtPayment::observe(\App\Observers\DebtPaymentObserver::class);
        \App\Models\ReceivablePayment::observe(\App\Observers\ReceivablePaymentObserver::class);
        \App\Models\Expense::observe(\App\Observers\ExpenseObserver::class);

        // Global pagination options for all Filament tables
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->paginated([5, 10, 20, 50, 100, 'all'])
                ->defaultPaginationPageOption(50);
        });
    }
}
