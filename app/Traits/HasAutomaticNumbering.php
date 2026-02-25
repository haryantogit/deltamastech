<?php

namespace App\Traits;

use App\Models\NumberingSetting;

trait HasAutomaticNumbering
{
    /**
     * Boot the trait.
     */
    protected static function bootHasAutomaticNumbering()
    {
        static::creating(function ($model) {
            $key = $model->getNumberingSettingKey();
            if ($key) {
                $newNumber = NumberingSetting::getNextNumber($key);
                if ($newNumber) {
                    $model->{$model->getNumberingField()} = $newNumber;
                    NumberingSetting::commitNextNumber($key);
                }
            }
        });
    }

    /**
     * Get the database column name that holds the generated number.
     * Default is 'number', override in model if different (e.g., 'invoice_number').
     */
    protected function getNumberingField(): string
    {
        return 'number';
    }

    /**
     * The key matching the `numbering_settings` table.
     * Must be implemented by the using class.
     */
    abstract public function getNumberingSettingKey(): ?string;
}
