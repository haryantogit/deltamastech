<?php
$files = [
    'app/Filament/Resources/SalesInvoiceResource.php',
    'app/Filament/Resources/PurchaseInvoiceResource.php',
];

foreach ($files as $file) {
    if (!file_exists($file))
        continue;
    $content = file_get_contents($file);

    // Remove duplicate Hidden fields that match Select fields
    // We match the pattern: Hidden::make('field_id')->hidden(fn...(!filled(...)))
    $patterns = [
        '/Hidden::make\(\'contact_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'(?:sales|purchase)_order_id\'\)\)\),/s',
        '/Hidden::make\(\'supplier_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'purchase_order_id\'\)\)\),/s',
        '/Hidden::make\(\'sales_order_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'sales_order_id\'\)\)\),/s',
        '/Hidden::make\(\'purchase_order_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'purchase_order_id\'\)\)\),/s',
        '/Hidden::make\(\'warehouse_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'(?:sales|purchase)_order_id\'\)\)\),/s',
        '/Hidden::make\(\'shipping_method_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'(?:sales|purchase)_order_id\'\)\)\),/s',
    ];

    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }

    file_put_contents($file, $content);
}
echo "Cleaned up duplicate Hidden fields.\n";
