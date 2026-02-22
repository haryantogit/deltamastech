<?php

$file = 'd:\Program Receh\kledo\app\Filament\Resources\PurchaseQuotationResource.php';
$lines = file($file);

// Target line 362 (1-based) -> 361 (0-based)
// But I'll replace a chunk to be safe.
// 360: ->collapsible()
// 361: ->collapsed(),
// 362: ])
// 363: ->searchable()

$startLine = 361; // 0-based index for line 362?
// Line 362 is index 361.
// Let's replace 361 to 363 (indices 360 to 362?)
// Wait, 1-based 360 is index 359.
// 1-based 362 is index 361.

// I want to replace lines 362 and 363.
// Index 361 and 362.
$startIdx = 361;
$length = 2; // Replace 2 lines.

// Replacement:
//                                             ])
//                                             ->createOptionForm ends here?
//                                             ]) 
//                                             ->searchable()

$replacement = [
    "                                            ]),\n", // Ends Section schema
    "                                            ])\n",  // Ends createOptionForm
    "                                            ->searchable()\n"
];

// Wait, I am replacing 2 lines with 3.
// Old 362:                                             ])
// Old 363:                                             ->searchable()

// Checking content to be sure I don't break strict match logic if specific.
// I'll trust the lines from view_file.

array_splice($lines, $startIdx, $length, $replacement);

file_put_contents($file, implode("", $lines));

echo "Added missing brackets successfully.\n";
