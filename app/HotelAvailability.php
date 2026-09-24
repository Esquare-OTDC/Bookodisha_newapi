<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HotelAvailability extends Model
{
    protected $table = 'hotel_availability';
    
    protected $fillable = [
        'vendor_id', 'hotel_id', 'hotel_name', 'room_id', 'room_name', 'block_reason', 'quantity', 'block_date', 'status', 'created_by'
    ];
}
