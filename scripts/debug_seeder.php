<?php
require 'vendor/autoload.php';
use Carbon\Carbon;

$purchasePrice = 2300000;
$salvageValue = 0;
$usefulLifeYears = 10;
$depRate = 10;
$depMethod = 'Straight Line';
$depStartDateStr = '27/09/2024';
$currentDate = Carbon::create(2026, 1, 31);

function parseDate($dateStr)
{
    if (empty($dateStr))
        return null;
    try {
        return Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
    } catch (\Exception $e) {
        return null;
    }
}

$depStartDate = parseDate($depStartDateStr);
echo "Parsed Start Date: $depStartDate\n";

if ($depStartDate) {
    $startDate = Carbon::parse($depStartDate);
    $totalMonths = ($usefulLifeYears * 12);
    $monthlyDep = ($purchasePrice - $salvageValue) / $totalMonths;
    echo "Monthly Dep: $monthlyDep\n";

    if ($monthlyDep > 0) {
        for ($i = 0; $i < $totalMonths; $i++) {
            $loopDate = $startDate->copy()->addMonthsNoOverflow($i)->endOfMonth();
            if ($loopDate->greaterThan($currentDate))
                break;
            echo "Period: " . $loopDate->format('Y-m') . " Amount: $monthlyDep\n";
        }
    }
}
