<?php

namespace App\Filament\Pages\Laporan;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\EmbeddedSchema;

class ArusKas extends Page
{
    use HasFiltersForm;

    public string $statsFilter = 'bulan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.laporan.arus-kas';

    protected static ?string $title = 'Arus Kas';

    protected static ?string $slug = 'arus-kas';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->filters = [
            'metode' => 'tak_langsung',
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->toDateString(),
        ];
    }


    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Arus Kas',
        ];
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $startFmt = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = \Carbon\Carbon::parse($endDate)->format('d/m/Y');

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateDisplay . '</span>
            </div>
        ');
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
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    \Filament\Forms\Components\Select::make('metode')
                        ->label('Metode')
                        ->options([
                            'tak_langsung' => 'Metode Tak Langsung',
                            'langsung' => 'Metode Langsung',
                        ])
                        ->default($this->filters['metode'] ?? 'tak_langsung')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('startDate')
                        ->hiddenLabel()
                        ->default($this->filters['startDate'])
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('endDate')
                        ->hiddenLabel()
                        ->default($this->filters['endDate'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->filters['metode'] = $data['metode'];
                    $this->filters['startDate'] = $data['startDate'];
                    $this->filters['endDate'] = $data['endDate'];
                    $this->statsFilter = 'custom';
                }),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->url('#'),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $metode = $this->filters['metode'] ?? 'tak_langsung';
        $startFormatted = Carbon::parse($this->filters['startDate'])->format('d/m/Y');
        $endFormatted = Carbon::parse($this->filters['endDate'])->format('d/m/Y');

        $dateDisplay = $startFormatted === $endFormatted
            ? $startFormatted
            : $startFormatted . ' &mdash; ' . $endFormatted;

        if ($metode === 'tak_langsung') {
            return [
                'dateDisplay' => $dateDisplay,
                'sections' => [
                    'Aktivitas Operasional' => [
                        'Net income' => 6166344,
                        'Akun piutang' => 43283526,
                        'Persediaan barang' => -4233900,
                        'Depresiasi & amortisasi' => 0,
                        'Akun hutang' => -81964041,
                        'Liabilitas jangka pendek lainnya' => 4459582,
                    ],
                    'Aktivitas Investasi' => [
                        'Aset tetap' => 0,
                        'Aktivitas investasi lainnya' => 0,
                    ],
                    'Aktivitas Pendanaan' => [
                        'Penerimaan pembayaran pinjaman' => 0,
                        'Ekuitas' => 0,
                    ],
                ],
                'totals' => [
                    'Arus kas bersih dari aktivitas operasional' => -37203489,
                    'Arus kas bersih dari aktivitas investasi' => 0,
                    'Arus kas bersih dari aktivitas pendanaan' => 0,
                    'Arus kas bersih' => -37203489,
                ],
                'cash_equivalents' => [
                    'Kas dan setara kas diawal periode' => 118440558,
                    'Kas dan setara kas diakhir periode' => 81237069,
                    'Perubahan kas untuk periode' => -37203489,
                ]
            ];
        }

        return [
            'startFormatted' => $startFormatted,
            'endFormatted' => $endFormatted,
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
                'Arus kas bersih dari aktivitas operasional' => -37203489,
                'Arus kas bersih dari aktivitas investasi' => 0,
                'Arus kas bersih dari aktivitas pendanaan' => 0,
                'Arus kas bersih' => -37203489,
            ],
            'cash_equivalents' => [
                'Kas dan setara kas diawal periode' => 118440558,
                'Kas dan setara kas diakhir periode' => 81237069,
                'Perubahan kas untuk periode' => -37203489,
            ]
        ];
    }
}

