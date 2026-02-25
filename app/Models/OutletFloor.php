<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletFloor extends Model
{
    use HasFactory;

    protected $table = 'outlet_floors';

    protected $fillable = [
        'outlet_id',
        'name',
        'total_tables',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
