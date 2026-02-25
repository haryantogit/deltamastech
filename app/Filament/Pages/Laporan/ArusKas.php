<?php

namespace App\Filament\Pages\Laporan;

use Filament\Pages\Page;

class ArusKas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.arus-kas';

    protected static ?string $title = 'Arus Kas';

    protected static ?string $slug = 'arus-kas';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Arus Kas',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ArusKasStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('panduan')
                ->label('Panduan')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle')
                ->url('#'),
            \Filament\Actions\Action::make('ekspor')
                ->label('Ekspor')
                ->color('gray')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('#'),
            \Filament\Actions\Action::make('bagikan')
                ->label('Bagikan')
                ->color('gray')
                ->icon('heroicon-o-share')
                ->url('#'),
            \Filament\Actions\Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->url('#'),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        return [
            'sections' => [
                'Aktivitas Operasional' => [
                    'Penerimaan dari pelanggan' => 143683173,
                    'Aset lancar lainnya' => 0,
                    'Kartu kredit dan liabilitas jangka pendek lainnya' => 0,
                    'Pendapatan lain-lain' => 294,
                    'Pembayaran biaya operasional' => -23136354,
                ],
                'Aktivitas Investasi' => [
                    'Perolehan/pembelian aset' => 0,
                    'Aktivitas investasi lainnya' => 0,
                ],
                'Aktivitas Pendanaan' => [
                    'Liabilitas Jangka Panjang' => 0,
                    'Modal pemilik' => 0,
                ],
            ],
            'totals' => [
                'Arus kas bersih dari aktivitas operasional' => 37203489 * -1,
                'Arus kas bersih dari aktivitas investasi' => 0,
                'Arus kas bersih dari aktivitas pendanaan' => 0,
                'Arus kas bersih' => 37203489 * -1,
            ],
            'cash_equivalents' => [
                'Kas dan setara kas diawal periode' => 118440558,
                'Kas dan setara kas diakhir periode' => 81237069,
                'Perubahan kas untuk periode' => 37203489 * -1,
            ]
        ];
    }
}
