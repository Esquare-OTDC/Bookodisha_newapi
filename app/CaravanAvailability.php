<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaravanAvailability extends Model
{
    protected $table = 'caravan_availability';
    
    protected $fillable = [
        'vendor_id', 'caravan_id', 'caravan_name', 'block_reason', 'quantity', 'block_date', 'status', 'created_by'
    ];
}