<?php
$csvPath = 'D:\Program Receh\kledo\data-baru\pesanan-pembelian_18-Feb-2026_halaman-1.csv';
$content = file_get_contents($csvPath);
// Remove BOM
if (strpos($content, "\xEF\xBB\xBF") === 0) {
    $content = substr($content, 3);
}
$tempFile = tmpfile();
fwrite($tempFile, $content);
fseek($tempFile, 0);

$header = fgetcsv($tempFile);
// Map headers
$mapping = [];
foreach ($header as $index => $name) {
    if (str_contains($name, 'Nomor Pesanan'))
        $mapping['order_number'] = $index;
    if (str_contains($name, 'Nama Produk'))
        $mapping['product_name'] = $index;
    if (str_contains($name, 'Kode Produk'))
        $mapping['sku'] = $index;
}

while (($data = fgetcsv($tempFile)) !== false) {
    if (isset($data[$mapping['order_number']]) && trim($data[$mapping['order_number']]) === 'PO/00137') {
        echo "Found PO/00137 Item:\n";
        echo "  SKU: '" . ($data[$mapping['sku']] ?? 'MISSING') . "'\n";
        echo "  Name: '" . ($data[$mapping['product_name']] ?? 'MISSING') . "'\n";
        // echo "Full Row: " . json_encode($data) . "\n";
    }
}
fclose($tempFile);
