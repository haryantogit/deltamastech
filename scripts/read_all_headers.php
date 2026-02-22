<?php
function getHeaders($path)
{
    if (!file_exists($path))
        return "File not found: $path";
    $content = file_get_contents($path);
    if (strpos($content, "\xEF\xBB\xBF") === 0)
        $content = substr($content, 3);
    $lines = explode("\n", $content);
    $header = str_getcsv($lines[0]);
    $header = array_map(function ($h) {
        return trim($h, " \t\n\r\0\x0B\""); }, $header);
    return $header;
}

$p1 = 'D:\Program Receh\kledo\data-baru\pengiriman-pembelian_18-Feb-2026_halaman-1.csv';
$p2 = 'D:\Program Receh\kledo\data-baru\tagihan-pembelian_18-Feb-2026_halaman-1.csv';

echo "Delivery Headers:\n";
print_r(getHeaders($p1));

echo "\nInvoice Headers:\n";
print_r(getHeaders($p2));
