<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use App\Models\Contact;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContactListStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalContacts = Contact::count();
        $totalCustomers = Contact::where('type', 'customer')->count();
        $totalVendors = Contact::where('type', 'vendor')->count();
        $totalEmployees = Contact::where('type', 'employee')->count();
        $totalOthers = Contact::whereNotIn('type', ['customer', 'vendor', 'employee'])->count();

        // Trends (Placeholder logic or simple counts for now as requested "sesuaikan saja")
        // We'll use the counts and professional icons/colors

        return [
            Stat::make('Total Kontak', number_format($totalContacts, 0, ',', '.'))
                ->description('Semua tipe kontak')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pelanggan', number_format($totalCustomers, 0, ',', '.'))
                ->description('Total customer aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Vendor', number_format($totalVendors, 0, ',', '.'))
                ->description('Total supplier/vendor')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning'),

            Stat::make('Karyawan', number_format($totalEmployees, 0, ',', '.'))
                ->description('Total personel internal')
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),

            Stat::make('Lainnya', number_format($totalOthers, 0, ',', '.'))
                ->description('Tipe kontak lainnya')
                ->descriptionIcon('heroicon-m-ellipsis-horizontal-circle')
                ->color('gray'),
        ];
    }
}
