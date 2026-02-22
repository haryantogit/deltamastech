<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'npwp',
        'logo_path',
        'notification_settings',
        'invoice_layout_settings',
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'invoice_layout_settings' => 'array',
    ];
}
