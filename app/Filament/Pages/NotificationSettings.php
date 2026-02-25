<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';
    protected static ?string $title = 'Notifikasi';
    protected static string|null $navigationLabel = 'Notifikasi';
    protected static string|\UnitEnum|null $navigationGroup = 'Lainnya';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $company = Company::first();

        if ($company) {
            $this->form->fill($company->notification_settings ?? []);
        } else {
            $this->form->fill([]);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('stok_habis_frequency')
                            ->label('Notifikasi stok habis')
                            ->options([
                                'Harian' => 'Harian',
                                'Mingguan' => 'Mingguan',
                                'Bulanan' => 'Bulanan',
                            ])
                            ->default('Mingguan'),

                        Toggle::make('marketplace_sync_finished')
                            ->label('Notifikasi sinkronisasi marketplace selesai')
                            ->default(true),

                        Toggle::make('bank_sync_finished')
                            ->label('Notifikasi sinkronisasi bank selesai')
                            ->default(true),

                        Toggle::make('monthly_financial_report_email')
                            ->label('Email laporan keuangan bulanan')
                            ->default(true),

                        Toggle::make('live_webinar_event')
                            ->label('Notifikasi event live webinar')
                            ->default(true),

                        Toggle::make('billing_price_change')
                            ->label('Notifikasi perubahan harga billing')
                            ->default(true),

                        Toggle::make('import_success')
                            ->label('Notifikasi berhasil import')
                            ->default(true),

                        Select::make('mute_survey_duration')
                            ->label('Mute survei rekomendasi')
                            ->options([
                                '1 bulan' => '1 bulan',
                                '2 bulan' => '2 bulan',
                                '3 bulan' => '3 bulan',
                                '4 bulan' => '4 bulan',
                            ])
                            ->default('4 bulan'),

                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->color('primary')
                                ->action('save'),
                        ])->alignEnd(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
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
                    'notification_settings' => $data,
                ]);
            }

            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body('Pengaturan notifikasi berhasil disimpan.')
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
            'Notifikasi',
        ];
    }
}
