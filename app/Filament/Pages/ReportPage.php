<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ReportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'filament.pages.report-page';

    protected static ?string $title = 'Halaman Laporan';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            'Laporan',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static ?int $navigationSort = 7;

    protected static string|null $navigationLabel = 'Laporan';

    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            'filament.admin.pages.report-page',
            'filament.admin.pages.ringkasan-bank',
            'filament.admin.pages.ringkasan-inventori',
            'filament.admin.pages.ringkasan-stok-gudang',
            'filament.admin.pages.pergerakan-stok-inventori',
            'filament.admin.pages.pergerakan-stok-gudang',
            'filament.admin.pages.laporan-produksi',
            'filament.admin.pages.laporan-penyesuaian-stok',
            'filament.admin.pages.laporan-transfer-gudang',
            'filament.admin.pages.perputaran-persediaan',
            'filament.admin.pages.ringkasan-aset-tetap',
            'filament.admin.pages.detil-aset-tetap',
            'filament.admin.pages.pelepasan-aset',
            'filament.admin.pages.buku-besar',
            'filament.admin.pages.jurnal-umum',
            'filament.admin.pages.trial-balance',
            'filament.admin.pages.laba-rugi',
            'filament.admin.pages.neraca',
            'filament.admin.pages.arus-kas',
            'filament.admin.pages.perubahan-modal',
            'filament.admin.pages.hutang-piutang-per-kontak',
            'filament.admin.pages.daftar-penjualan',
            'filament.admin.pages.profitabilitas-produk',
            'filament.admin.pages.profitabilitas-tagihan',
            'filament.admin.pages.pendapatan-pelanggan',
            'filament.admin.pages.penjualan-produk',
            'filament.admin.pages.pemesanan-produk',
            'filament.admin.pages.pengiriman-penjualan',
            'filament.admin.pages.ongkos-kirim-ekspedisi',
            'filament.admin.pages.pelunasan-pembayaran-tagihan',
            'filament.admin.pages.penjualan-per-kategori-produk',
            'filament.admin.pages.penjualan-produk-per-pelanggan',
            'filament.admin.pages.penjualan-per-periode',
            'filament.admin.pages.penjualan-per-region',
            'filament.admin.pages.detail-pembelian',
            'filament.admin.pages.pembelian-per-produk',
            'filament.admin.pages.pemesanan-pembelian-per-produk',
            'filament.admin.pages.pembelian-per-vendor',
            'filament.admin.pages.pengiriman-pembelian',
            'filament.admin.pages.pelunasan-pembayaran-tagihan-pembelian',
            'filament.admin.pages.pembelian-produk-per-vendor',
            'filament.admin.pages.pembelian-per-periode',
            'filament.admin.pages.pembelian-per-region',
            'filament.admin.pages.pajak-penjualan',
            'filament.admin.pages.biaya-per-kontak',
            'filament.admin.pages.manajemen-anggaran',
            'filament.admin.pages.anggaran-laba-rugi',
            'filament.admin.pages.laporan-aktivitas-tim',
        ];
    }


    protected static string|\UnitEnum|null $navigationGroup = null;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin')),
        ];
    }
}
