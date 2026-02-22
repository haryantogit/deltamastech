<?php

$file = 'd:\Program Receh\kledo\app\Filament\Resources\PurchaseQuotationResource.php';
$content = file_get_contents($file);

$tokens = token_get_all($content);
$stack = [];

foreach ($tokens as $token) {
    if (is_string($token)) {
        $char = $token;
        if (in_array($char, ['(', '[', '{'])) {
            $stack[] = ['char' => $char, 'line' => -1]; // Line number harder for string tokens
        } elseif (in_array($char, [')', ']', '}'])) {
            if (empty($stack)) {
                echo "Error: Unexpected $char\n";
                exit(1);
            }
            $last = array_pop($stack);
            $expected = match ($last['char']) {
                '(' => ')',
                '[' => ']',
                '{' => '}',
            };
            if ($char !== $expected) {
                echo "Error: Expected $expected but found $char\n";
                exit(1);
            }
        }
    } elseif (is_array($token)) {
        // Handle T_CURLY_OPEN etc if necessary, but usually simple brackets suffice
    }
}

if (!empty($stack)) {
    $last = array_pop($stack);
    echo "Error: Unclosed " . $last['char'] . "\n";
    exit(1);
}

echo "All brackets balanced.\n";
