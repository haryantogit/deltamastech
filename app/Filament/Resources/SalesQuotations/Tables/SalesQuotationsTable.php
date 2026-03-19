<?php

namespace App\Filament\Resources\SalesQuotations\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;


class SalesQuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->with(['contact', 'paymentTerm', 'tags']))
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor')
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn($record) => route('filament.admin.resources.sales-quotations.view', $record)),
                \Filament\Tables\Columns\TextColumn::make('contact.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->label('Referensi')
                    ->formatStateUsing(function ($state) {
                        if (is_numeric($state) && strpos(strtoupper((string) $state), 'E') !== false) {
                            return number_format((float) $state, 0, '', '');
                        }
                        return $state;
                    }),
                \Filament\Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Tgl. Jatuh Tempo')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('paymentTerm.name')
                    ->label('Termin')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('tags.name')
                    ->badge()
                    ->separator(',')
                    ->label('Tag'),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'Draf',
                        'approved' => 'Disetujui',
                        'accepted' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'finished' => 'Selesai',
                        'sent' => 'Terkirim',
                        'expired' => 'Kedaluwarsa',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn($state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'approved' => 'success',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'finished' => 'info',
                        'sent' => 'primary',
                        'expired' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),
                \Filament\Tables\Columns\TextColumn::make('dp')
                    ->money('IDR')
                    ->label('DP')
                    ->default(0)
                    ->state(fn() => 0),
                \Filament\Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Total'),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari')
                            ->default(now()->subMonths(3)),
                        DatePicker::make('until')
                            ->label('Sampai')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    TableAction::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($record) => $record->update(['status' => 'approved']))
                        ->visible(fn($record) => $record->status === 'draft')
                        ->requiresConfirmation(),
                    TableAction::make('reject')
                        ->label('Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($record) => $record->update(['status' => 'rejected']))
                        ->visible(fn($record) => $record->status === 'draft')
                        ->requiresConfirmation(),
                    TableAction::make('convertToInvoice')
                        ->label('Convert to Invoice')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->action(function (\App\Models\SalesQuotation $record) {
                            $record->update(['status' => 'finished']);
                            $invoice = \App\Models\SalesInvoice::create([
                                'contact_id' => $record->contact_id,
                                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                                'transaction_date' => now(),
                                'due_date' => now()->addDays(30),
                                'status' => 'Draft',
                                'total_amount' => $record->total_amount,
                            ]);

                            foreach ($record->items as $item) {
                                \App\Models\SalesInvoiceItem::create([
                                    'sales_invoice_id' => $invoice->id,
                                    'product_id' => $item->product_id,
                                    'description' => $item->product->name ?? 'Imported Item',
                                    'qty' => $item->quantity,
                                    'price' => $item->unit_price,
                                    'subtotal' => $item->total_price,
                                ]);
                            }

                            // Redirect to the edit page of the new invoice
                            return redirect(\App\Filament\Resources\SalesInvoiceResource::getUrl('edit', ['record' => $invoice]));
                        })
                        ->visible(fn($record) => $record->status === 'approved')
                        ->requiresConfirmation(),
                    \Filament\Actions\EditAction::make(),
                    TableAction::make('print')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn($record) => route('print.sales-quotation', $record))
                        ->openUrlInNewTab(),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }
}
