<?php

namespace App\Filament\Pages\Pos;

use App\Models\AppSetting;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Tag;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class PosSettings extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.pos.pos-settings';

    protected static ?string $title = 'Pengaturan POS';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = AppSetting::where('key', 'like', 'pos_%')->get()->pluck('value', 'key')->toArray();

        // Handle JSON decoded values for multi-selects
        if (isset($settings['pos_tags'])) {
            $settings['pos_tags'] = json_decode($settings['pos_tags'], true);
        }

        $this->form->fill($settings);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('General')
                    ->schema([
                        Select::make('pos_custom_product_id')
                            ->label('Custom Produk')
                            ->options(Product::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Anda dapat membuat transaksi di POS untuk produk yang belum tercatat, atau disebut custom produk.'),

                        Select::make('pos_default_customer_id')
                            ->label('Default Pelanggan')
                            ->options(Contact::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Anda dapat mencatatkan penjualan di POS dengan tanpa mencatatkan pelanggannya.'),
                    ]),

                Section::make('Default Akun')
                    ->schema([
                        Select::make('pos_acc_send_money')
                            ->label('Default Akun Kirim Dana')
                            ->options(Account::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Saat mencatatkan keluar masuk kas di POS, maka di akunting akan dicatatkan pada akun COA ini.'),

                        Select::make('pos_acc_receive_money')
                            ->label('Default Akun Terima Dana')
                            ->options(Account::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Saat mencatatkan keluar masuk kas di POS, maka di akunting akan dicatatkan pada akun COA ini.'),

                        Select::make('pos_acc_open_cashier')
                            ->label('Default Akun Buka Kasir')
                            ->options(Account::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Saat melakukan buka kasir POS, maka di akunting akan dicatatkan pada akun COA ini.'),

                        Select::make('pos_acc_close_cashier')
                            ->label('Default Akun Tutup Kasir')
                            ->options(Account::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Saat melakukan tutup kasir POS, maka di akunting akan dicatatkan pada akun COA ini.'),

                        Select::make('pos_acc_receive_payment')
                            ->label('Default Akun Terima Pembayaran')
                            ->options(Account::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Saat melakukan pembayaran transaksi di POS, maka di akunting akan dicatatkan pada akun COA ini.'),
                    ]),

                Section::make('Lainnya')
                    ->schema([
                        Select::make('pos_tags')
                            ->label('Tags Penjualan')
                            ->multiple()
                            ->options(Tag::pluck('name', 'id'))
                            ->searchable()
                            ->helperText('Penjualan melalui POS akan otomatis menggunakan tags berikut.'),

                        // Note: For service charge, the screenshot shows "Service Charge Pada Biaya Tagihan"
                        // I'll assume it's a field to select an expense or related model.
                        // For now, let's use a placeholder if the exact model isn't clear, or just a text input if it refers to a label.
                        // Actually, looking at the screenshot, it looks like a dropdown.
                        Select::make('pos_service_charge_id')
                            ->label('Service Charge Pada Biaya Tagihan')
                            ->options(Product::pluck('name', 'id')) // Often service charges are products/services
                            ->searchable()
                            ->helperText('Pada penjualan yang terdapat service charge, biaya dicatatkan pada master biaya tagihan ini.'),

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
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->outlined()
                ->size('sm')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\PosPage::getUrl()),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                AppSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body('Pengaturan POS berhasil disimpan.')
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
            \App\Filament\Pages\PosPage::getUrl() => 'POS',
            'Pengaturan POS',
        ];
    }
}
