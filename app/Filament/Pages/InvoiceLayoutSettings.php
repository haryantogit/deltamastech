<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Slider;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class InvoiceLayoutSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $title = 'Layout Invoice';
    protected static string|null $navigationLabel = 'Layout Invoice';
    protected static string|\UnitEnum|null $navigationGroup = 'Lainnya';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.invoice-layout-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $company = Company::first();

        if ($company) {
            $this->form->fill($company->invoice_layout_settings ?? [
                'document_type' => 'Faktur Penjualan',
                'logo_size' => 100,
                'show_company_info' => true,
                'show_billing_info' => true,
                'show_invoice_number' => true,
                'show_date' => true,
                'show_due_date' => true,
                'show_delivery_note' => true,
                'show_order_number' => true,
                'show_quotation_number' => true,
            ]);
        } else {
            $this->form->fill([
                'document_type' => 'Faktur Penjualan',
                'logo_size' => 100,
            ]);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('document_type')
                            ->label('Tipe Dokumen')
                            ->options([
                                'Faktur Penjualan' => 'Faktur Penjualan',
                                'Pesanan Penjualan' => 'Pesanan Penjualan',
                                'Penawaran Penjualan' => 'Penawaran Penjualan',
                            ])
                            ->required(),

                        Slider::make('logo_size')
                            ->label('Ubah ukuran logo')
                            ->minValue(50)
                            ->maxValue(200)
                            ->step(1)
                            ->live(),
                    ]),

                Section::make('Visibilitas Field')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('show_company_info')->label('Informasi Perusahaan')->live(),
                                Toggle::make('show_billing_info')->label('Tagihan Kepada')->live(),
                                Toggle::make('show_invoice_number')->label('Nomor Invoice')->live(),
                                Toggle::make('show_date')->label('Tanggal')->live(),
                                Toggle::make('show_due_date')->label('Tgl. Jatuh Tempo')->live(),
                                Toggle::make('show_delivery_note')->label('Surat Jalan')->live(),
                                Toggle::make('show_order_number')->label('Pemesanan')->live(),
                                Toggle::make('show_quotation_number')->label('Penawaran')->live(),
                            ]),

                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->color('primary')
                                ->action('save'),
                        ])->alignEnd(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guide')
                ->label('Panduan')
                ->color('gray')
                ->icon('heroicon-o-question-mark-circle'),
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(\App\Filament\Pages\Pengaturan::getUrl()),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $company = Company::first();
            if ($company) {
                $company->update([
                    'invoice_layout_settings' => $data,
                ]);
            }

            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body('Layout invoice berhasil disimpan.')
                ->send();
        } catch (\Exception $exception) {
            Notification::make()
                ->danger()
                ->title('Gagal')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            'Layout Invoice',
        ];
    }
}
