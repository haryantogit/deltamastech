<?php
$files = array_merge(glob('app/Filament/Resources/*.php'), glob('app/Filament/Resources/*/*/Tables/*.php'), glob('app/Filament/Resources/*/*/*.php'));
foreach ($files as $f) {
    if (!is_file($f))
        continue;
    exec('php -l ' . escapeshellarg($f) . ' 2>&1', $out, $ret);
    if ($ret !== 0) {
        echo $f . "\n";
    }
}
