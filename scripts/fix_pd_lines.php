<?php
$file = 'd:/Program Receh/deltamas/app/Filament/Resources/PurchaseDeliveryResource.php';
$lines = file($file);

$startLine = 495; // 1-indexed
$endLine = 597;   // 1-indexed

$newLines = array_slice($lines, 0, $startLine - 1);

$newTableMethod = "    public static function table(Table \$table): Table
    {
        return \$table
            ->modifyQueryUsing(fn(Builder \$query) => \$query->with(['supplier', 'purchaseOrder.paymentTerm', 'warehouse', 'tags', 'shippingMethod']))
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn(\$record) => self::getUrl('view', ['record' => \$record])),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Vendor')
                    ->sortable()
                    ->description(fn(\$record) => \$record->supplier?->company),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Nama Gudang')
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.paymentTerm.name')
                    ->label('Termin')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Tag')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string \$state): string => match (\$state) {
                        'draft' => 'Draf',
                        'pending' => 'Menunggu',
                        'received' => 'Diterima',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst(\$state),
                    })
                    ->color(fn(string \$state): string => match (\$state) {
                        'draft' => 'gray',
                        'pending' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('shippingMethod.name')
                    ->label('Ekspedisi')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('shipping_cost')
                    ->label('Biaya Kirim')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }\n";

$newLines[] = $newTableMethod;

$afterLines = array_slice($lines, $endLine);
$finalLines = array_merge($newLines, $afterLines);

file_put_contents($file, implode("", $finalLines));
echo "Fixed PurchaseDeliveryResource.php using line numbers successfully.\n";
