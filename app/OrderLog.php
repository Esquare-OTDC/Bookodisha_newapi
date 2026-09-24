<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $table = 'order_log';
    
    protected $fillable = [
        'vendor_id', 'order_id', 'service_id', 'room_id', 'date', 'total_booked', 'total_blocked', 'total_cancelled', 'created_by'
    ];
}


