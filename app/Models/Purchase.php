<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    //
    protected $fillable = [
        'user_id',
        'total',
        'wallet_name',
        'reference_id',
        'request_id',
        'items',
    ];
}
