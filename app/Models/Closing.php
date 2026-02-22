<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Closing extends Model
{
    protected $guarded = ['id'];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
