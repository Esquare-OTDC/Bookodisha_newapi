<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HotelRoomPricing extends Model
{
    protected $table = 'hotel_room_pricing';
    
    protected $fillable = [
        'vendor_id', 'hotel_id', 'room_id', 'hotel_name', 'room_name', 'price_plan', 'offer_percentage', 'offer_type', 'start_date', 'end_date', 'created_by'
    ];
}