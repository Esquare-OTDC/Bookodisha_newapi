<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FoodBooking extends Model
{
    protected $table = 'food_bookings';
    
    protected $fillable = [
        'booking_id', 'vendor_id', 'food_request', 'food_details', 'service_city', 'total_price', 'user_type', 'create_user', 'update_user', 'status'
    ];
}