<?php
$file = 'app/Filament/Resources/SalesInvoiceResource.php';
$content = file_get_contents($file);

// 1. Add contact_id hidden persistence
$content = preg_replace(
    '/TextInput::make\(\'contact_name\'\)\s+->label\(\'Pelanggan\'\)\s+->disabled\(\)\s+->dehydrated\(false\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'sales_order_id\'\)\)\),/',
    "TextInput::make('contact_name')
                                    ->label('Pelanggan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get \$get) => !filled(\$get('sales_order_id'))),
                                Hidden::make('contact_id')
                                    ->dehydrated(),",
    $content
);

// 2. Update sales_order_id Select visibility logic and add persistence
$content = preg_replace(
    '/Select::make\(\'sales_order_id\'\)\s+->relationship\(\'salesOrder\', \'number\'\)\s+->label\(\'Nomor Pesanan\'\)\s+->searchable\(\)\s+->preload\(\)\s+->live\(\)\s+->hidden\(fn\(Get \$get\) => filled\(\$get\(\'sales_order_id\'\)\)\)\s+->dehydrated\(\)/',
    "Select::make('sales_order_id')
                                    ->relationship('salesOrder', 'number')
                                    ->label('Nomor Pesanan')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->hidden(fn(Get \$get) => (filled(\$get('sales_order_id')) && \$get('is_locked_so')))
                                    ->dehydrated()",
    $content
);

// 3. Update sales_order_number visibility logic and add persistence + locked flag
$content = preg_replace(
    '/TextInput::make\(\'sales_order_number\'\)\s+->label\(\'Nomor Pesanan\'\)\s+->disabled\(\)\s+->dehydrated\(false\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'sales_order_id\'\)\)\),/',
    "TextInput::make('sales_order_number')
                                    ->label('Nomor Pesanan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get \$get) => !(filled(\$get('sales_order_id')) && \$get('is_locked_so'))),
                                Hidden::make('sales_order_id')
                                    ->dehydrated(),
                                Hidden::make('is_locked_so')
                                    ->default(fn() => request()->has('sales_order_id'))
                                    ->dehydrated(false),",
    $content
);

// 4. Add warehouse_id and shipping_method_id persistence
$content = preg_replace(
    '/TextInput::make\(\'warehouse_name\'\)\s+->label\(\'Gudang\'\)\s+->disabled\(\)\s+->dehydrated\(false\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'sales_order_id\'\)\)\),/',
    "TextInput::make('warehouse_name')
                                    ->label('Gudang')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get \$get) => !filled(\$get('sales_order_id'))),
                                Hidden::make('warehouse_id')
                                    ->dehydrated(),",
    $content
);

$content = preg_replace(
    '/TextInput::make\(\'shipping_method_name\'\)\s+->label\(\'Ekspedisi\'\)\s+->disabled\(\)\s+->dehydrated\(false\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'sales_order_id\'\)\)\),/',
    "TextInput::make('shipping_method_name')
                                    ->label('Ekspedisi')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn(Get \$get) => !filled(\$get('sales_order_id'))),
                                Hidden::make('shipping_method_id')
                                    ->dehydrated(),",
    $content
);

// 5. Fix repeater hidden logic (crucial fix)
$content = preg_replace(
    '/Select::make\(\'product_id_select\'\)\s+->label\(\'Produk\'\)\s+->relationship\(\'product\', \'name\', modifyQueryUsing: fn\(\$query\) => \$query->active\(\)\)\s+->searchable\(\)\s+->preload\(\)\s+->required\(\)\s+->columnSpan\(3\)\s+->hidden\(fn\(Get \$get\) => filled\(\$get\(\'..\/..\/sales_order_id\'\)\)\)/',
    "Select::make('product_id_select')
                                    ->label('Produk')
                                    ->relationship('product', 'name', modifyQueryUsing: fn(\$query) => \$query->active())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3)
                                    ->hidden(fn(Get \$get) => (filled(\$get('../../sales_order_id')) && \$get('../../is_locked_so')))",
    $content
);

$content = preg_replace(
    '/TextInput::make\(\'product_name\'\)\s+->label\(\'Produk\'\)\s+->disabled\(\)\s+->dehydrated\(false\)\s+->columnSpan\(3\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'..\/..\/sales_order_id\'\)\)\),/',
    "TextInput::make('product_name')
                                    ->label('Produk')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(3)
                                    ->hidden(fn(Get \$get) => !(filled(\$get('../../sales_order_id')) && \$get('../../is_locked_so'))),",
    $content
);

$content = preg_replace(
    '/Hidden::make\(\'product_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'..\/..\/sales_order_id\'\)\)\),/',
    "Hidden::make('product_id')
                                    ->dehydrated(),",
    $content
);

$content = preg_replace(
    '/Select::make\(\'unit_id_select\'\)\s+->label\(\'Satuan\'\)\s+->placeholder\(\'Pilih\'\)\s+->relationship\(\'unit\', \'name\'\)\s+->searchable\(\)\s+->preload\(\)\s+->columnSpan\(2\)\s+->hidden\(fn\(Get \$get\) => filled\(\$get\(\'..\/..\/sales_order_id\'\)\)\)/',
    "Select::make('unit_id_select')
                                    ->label('Satuan')
                                    ->placeholder('Pilih')
                                    ->relationship('unit', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2)
                                    ->hidden(fn(Get \$get) => (filled(\$get('../../sales_order_id')) && \$get('../../is_locked_so')))",
    $content
);

$content = preg_replace(
    '/TextInput::make\(\'unit_name\'\)\s+->label\(\'Satuan\'\)\s+->disabled\(\)\s+->dehydrated\(false\)\s+->columnSpan\(2\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'..\/..\/sales_order_id\'\)\)\),/',
    "TextInput::make('unit_name')
                                    ->label('Satuan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2)
                                    ->hidden(fn(Get \$get) => !(filled(\$get('../../sales_order_id')) && \$get('../../is_locked_so'))),",
    $content
);

$content = preg_replace(
    '/Hidden::make\(\'unit_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'..\/..\/sales_order_id\'\)\)\),/',
    "Hidden::make('unit_id')
                                    ->dehydrated(),",
    $content
);

file_put_contents($file, $content);
echo "Refactored SalesInvoiceResource.php\n";
