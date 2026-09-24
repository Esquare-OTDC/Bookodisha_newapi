<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HotelSale extends Model
{
    protected $table = 'hotel_sales';
    
    protected $fillable = [
        'vendor_id', 'hotel_id', 'hotel_name', 'room_id', 'room_name', 'start_date', 'end_date', 'created_by'
    ];
}