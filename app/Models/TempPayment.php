<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempPayment extends Model
{
    //
    protected $fillable = [
        'wallet_name',
        'reference_id',
        'wallet_reference_id',
        'status',
    ];
}
