<?php

namespace App\Filament\Pages;

use App\Models\Contact;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UmurHutang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|\UnitEnum|null $navigationGroup = 'Kontak';
    protected static ?string $title = 'Laporan Umur Hutang';
    protected static ?string $navigationLabel = 'Umur Hutang';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.umur-hutang';

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            url('/admin/kontak-page') => 'Kontak',
            '#' => 'Umur Hutang',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cetak')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn() => $this->js('window.print()')),
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(url('/admin/kontak-page')),
        ];
    }

    public function table(Table $table): Table
    {
        $subquery = DB::table('debts')
            ->selectRaw('supplier_id')
            ->selectRaw('SUM(total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0)) as total_outstanding')
            ->selectRaw('SUM(CASE WHEN due_date >= CURDATE() THEN total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0) ELSE 0 END) as current_amount')
            ->selectRaw('SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 1 AND 30 THEN total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0) ELSE 0 END) as bucket_1')
            ->selectRaw('SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 31 AND 60 THEN total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0) ELSE 0 END) as bucket_2')
            ->selectRaw('SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) BETWEEN 61 AND 90 THEN total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0) ELSE 0 END) as bucket_3')
            ->selectRaw('SUM(CASE WHEN DATEDIFF(CURDATE(), due_date) > 90 THEN total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0) ELSE 0 END) as bucket_4')
            ->whereRaw('(total_amount - COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0)) > 0')
            ->groupBy('supplier_id');

        return $table
            ->query(
                Contact::query()
                    ->joinSub($subquery, 'aging', 'contacts.id', '=', 'aging.supplier_id')
                    ->select('contacts.*', 'aging.total_outstanding', 'aging.current_amount', 'aging.bucket_1', 'aging.bucket_2', 'aging.bucket_3', 'aging.bucket_4')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_amount')
                    ->label('Belum Jatuh Tempo')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('bucket_1')
                    ->label('1 - 30 Hari')
                    ->money('IDR')
                    ->sortable()
                    ->color('warning')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('bucket_2')
                    ->label('31 - 60 Hari')
                    ->money('IDR')
                    ->sortable()
                    ->color('warning')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('bucket_3')
                    ->label('61 - 90 Hari')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('bucket_4')
                    ->label('> 90 Hari')
                    ->money('IDR')
                    ->sortable()
                    ->color('danger')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('total_outstanding')
                    ->label('Total Hutang')
                    ->money('IDR')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->summarize(Sum::make()->money('IDR')->label('Grand Total')),
            ])
            ->defaultSort('total_outstanding', 'desc');
    }
}
