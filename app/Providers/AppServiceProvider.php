<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        // Register Gate::before to handle string-based permission checks
        // This enables @can('penjualan.quote.view') etc. in Blade templates
        Gate::before(function ($user, $ability) {
            // Super Admin bypasses all checks
            if ($user->role?->name === 'Super Admin') {
                return true;
            }

            // Check if the ability matches a permission in the user's role
            if ($user->role?->permissions->contains('name', $ability)) {
                return true;
            }

            // Return null to let other gates/policies handle it
            return null;
        });

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
        \App\Models\SalesReturn::observe(\App\Observers\SalesReturnObserver::class);
        \App\Models\PurchaseReturn::observe(\App\Observers\PurchaseReturnObserver::class);

        // Global pagination options for all Filament tables
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table
                ->paginated([5, 10, 20, 50, 100, 'all'])
                ->defaultPaginationPageOption(50);
        });
    }
}
