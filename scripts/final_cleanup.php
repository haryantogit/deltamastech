<?php
$files = [
    'app/Filament/Resources/SalesInvoiceResource.php',
    'app/Filament/Resources/PurchaseInvoiceResource.php',
];

foreach ($files as $file) {
    if (!file_exists($file))
        continue;
    $content = file_get_contents($file);

    // Patterns to remove duplicate Hidden fields
    $patterns = [
        // Pattern 1: Basic Hidden field
        '/Hidden::make\(\'(contact|supplier|warehouse|shipping_method|purchase_order|sales_order)_id\'\)\s+->hidden\(fn\(Get \$get\) => !filled\(\$get\(\'(sales|purchase)_order_id\'\)\)\),/s',
        // Pattern 2: Hidden field with complex condition (Locked PO)
        '/Hidden::make\(\'purchase_order_id\'\)\s+->hidden\(fn\(Get \$get\) => !\(filled\(\$get\(\'purchase_order_id\'\)\) && \$get\(\'is_locked_po\'\)\)\),/s',
        // Pattern 3: Hidden field with another complex condition
        '/Hidden::make\(\'sales_order_id\'\)\s+->hidden\(fn\(Get \$get\) => !\(filled\(\$get\(\'sales_order_id\'\)\) && \$get\(\'is_locked_so\'\)\)\),/s',
    ];

    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }

    // Final cleanup of accidental leading/trailing commas or artifacts if any
    file_put_contents($file, $content);
}
echo "Final cleanup complete.\n";
