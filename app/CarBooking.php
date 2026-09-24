<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CarBooking extends Model
{
    protected $table = 'car_bookings';
    
    protected $fillable = [
        'booking_id', 'vendor_id', 'car_id', 'total_cars', 'start_date', 'end_date', 'start_time', 'end_time', 'pickup_address', 'drop_location', 'route', 'price_breakup', 'rental_type', 'day_breakup', 'trip_type', 'travel_distance', 'travel_hour', 'halt_hour', 'halt_charge', 'service_charge', 'totalPrice', 'user_type', 'create_user', 'status', 'need_guide', 'days_for_guide'
    ];
}