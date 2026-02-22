<?php

namespace App\Filament\Resources\SalesQuotations\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;

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
                    ->label('Nomor'),
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
                        'rejected' => 'Ditolak',
                        'finished' => 'Selesai',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'finished' => 'info',
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
                \Filament\Tables\Filters\SelectFilter::make('contact_id')
                    ->relationship('contact', 'name', modifyQueryUsing: fn($query) => $query->where('type', 'customer'))
                    ->label('Pelanggan')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draf',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'finished' => 'Selesai',
                    ])
                    ->label('Status'),
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
                    EditAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
