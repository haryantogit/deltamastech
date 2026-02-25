<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NumberingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'module',
        'format',
        'current_number',
        'pad_length',
        'reset_behavior',
    ];

    /**
     * Preview or generate the next formatted number without incrementing the DB counter.
     */
    public static function getNextNumber(string $key): ?string
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return null;
        }

        $nextNumber = $setting->current_number + 1;
        $paddedNumber = str_pad($nextNumber, $setting->pad_length, '0', STR_PAD_LEFT);

        // Handle format like "INV/[NUMBER]"
        return str_replace('[NUMBER]', $paddedNumber, $setting->format);
    }

    /**
     * Increment the current number in the database. Call this after a record is successfully saved.
     */
    public static function commitNextNumber(string $key): void
    {
        $setting = self::where('key', $key)->first();

        if ($setting) {
            $setting->increment('current_number');
        }
    }
}
