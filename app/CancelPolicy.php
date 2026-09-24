<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CancelPolicy extends Model
{
    protected $table = 'cancel_policy';
    
    protected $fillable = [
        'vendor_id', 'service_type', 'start', 'end', 'percent'
    ];
}


