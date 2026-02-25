<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\NumberingSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;

class PenomoranOtomatis extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(url('/admin/pengaturan')),
        ];
    }

    protected string $view = 'filament.pages.penomoran-otomatis';

    protected static ?string $title = 'Penomoran Otomatis';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/pengaturan') => 'Pengaturan',
            '' => 'Penomoran Otomatis',
        ];
    }

    public bool $is_transaction_sync_enabled = false;
    public bool $is_numbering_locked = false;

    public function mount()
    {
        $this->is_transaction_sync_enabled = filter_var(\App\Models\AppSetting::where('key', 'is_transaction_sync_enabled')->value('value') ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $this->is_numbering_locked = filter_var(\App\Models\AppSetting::where('key', 'is_numbering_locked')->value('value') ?? 'false', FILTER_VALIDATE_BOOLEAN);
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'is_transaction_sync_enabled' || $propertyName === 'is_numbering_locked') {
            \App\Models\AppSetting::updateOrCreate(
                ['key' => $propertyName],
                ['value' => $this->$propertyName ? 'true' : 'false']
            );

            Notification::make()
                ->title('Pengaturan berhasil disimpan')
                ->success()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $this->syncCurrentNumbers();

        $settings = NumberingSetting::all()->groupBy(function ($item) {
            return ucfirst($item->module);
        });

        return [
            'groupedSettings' => $settings,
        ];
    }

    protected function syncCurrentNumbers(): void
    {
        $mapping = [
            'sales_invoice' => [\App\Models\SalesInvoice::class, 'number'],
            'sales_delivery' => [\App\Models\SalesDelivery::class, 'number'],
            'sales_order' => [\App\Models\SalesOrder::class, 'number'],
            'sales_quotation' => [\App\Models\SalesQuotation::class, 'number'],
            'purchase_quotation' => [\App\Models\PurchaseQuote::class, 'number'],
            'purchase_invoice' => [\App\Models\PurchaseInvoice::class, 'number'],
            'purchase_delivery' => [\App\Models\PurchaseDelivery::class, 'number'],
            'purchase_order' => [\App\Models\PurchaseOrder::class, 'number'],
            'bank_transaction' => [\App\Models\BankTransaction::class, 'number'],
            'inventory_transfer' => [\App\Models\InventoryTransfer::class, 'number'],
            'inventory_adjustment' => [\App\Models\InventoryAdjustment::class, 'number'],
            'production_order' => [\App\Models\ProductionOrder::class, 'number'],
            'expense' => [\App\Models\Expense::class, 'number'],
            'journal' => [\App\Models\JournalEntry::class, 'number'],
            'fixed_asset' => [\App\Models\FixedAsset::class, 'number'],
            'hutang' => [\App\Models\AccountTransaction::class, 'number'],
            'piutang' => [\App\Models\AccountTransaction::class, 'number'],
        ];

        $settings = NumberingSetting::all();

        foreach ($settings as $setting) {
            if (isset($mapping[$setting->key])) {
                [$modelClass, $column] = $mapping[$setting->key];

                if (class_exists($modelClass)) {
                    // Extract the prefix from the format, e.g. "INV/[NUMBER]" -> "INV/"
                    $prefixParts = explode('[NUMBER]', $setting->format);
                    $prefix = $prefixParts[0] ?? '';

                    if ($prefix) {
                        try {
                            $lastRecord = $modelClass::where($column, 'like', $prefix . '%')
                                ->orderByRaw('LENGTH(' . $column . ') DESC')
                                ->orderBy($column, 'desc')
                                ->first();

                            if ($lastRecord && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastRecord->$column, $matches)) {
                                $actualMaxNumber = intval($matches[1]);
                                if ($actualMaxNumber > $setting->current_number) {
                                    $setting->update(['current_number' => $actualMaxNumber]);
                                }
                            }
                        } catch (\Exception $e) {
                            // Table might not exist or column might be different, gracefully skip
                        }
                    }
                }
            }
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('editFormat')
                ->hiddenLabel()
                ->modalHeading(fn(array $arguments) => $arguments['name'] ?? 'Format Penomoran')
                ->modalWidth('md')
                ->form([
                    TextInput::make('format')
                        ->label('Format Penomoran')
                        ->required()
                        ->hint('Gunakan [NUMBER] untuk posisi angka berurut.')
                        ->live(debounce: 500),
                    \Filament\Forms\Components\Hidden::make('pad_length'),
                    Placeholder::make('preview')
                        ->label('Contoh Output Penomoran Otomatis')
                        ->content(function (Get $get) {
                            $format = $get('format');
                            $pad = $get('pad_length') ?? 5;
                            $num = $get('current_number');
                            if ($format && is_numeric($num)) {
                                $numStr = str_pad((int) $num, $pad, '0', STR_PAD_LEFT);
                                return str_replace('[NUMBER]', $numStr, $format);
                            }
                            return '-';
                        }),
                    TextInput::make('current_number')
                        ->label('Nomor saat ini')
                        ->numeric()
                        ->required()
                        ->live(debounce: 500),
                    Radio::make('reset_behavior')
                        ->label('Reset Nomor Setiap')
                        ->options([
                            'never' => 'Tidak pernah reset',
                            'monthly' => 'Setiap bulan',
                            'yearly' => 'Setiap tahun',
                        ])
                        ->required(),
                ])
                ->fillForm(function (array $arguments): array {
                    $setting = NumberingSetting::find($arguments['id']);
                    if (!$setting)
                        return [];

                    return [
                        'format' => $setting->format,
                        'current_number' => $setting->current_number,
                        'pad_length' => $setting->pad_length,
                        'reset_behavior' => $setting->reset_behavior,
                    ];
                })
                ->action(function (array $data, array $arguments): void {
                    $setting = NumberingSetting::find($arguments['id']);
                    if ($setting) {
                        $setting->update([
                            'format' => $data['format'],
                            'current_number' => $data['current_number'],
                            'reset_behavior' => $data['reset_behavior'],
                        ]);

                        Notification::make()
                            ->title('Pengaturan berhasil diperbarui')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
