<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class TourBooking extends Model
{
    protected $table = 'tour_bookings';
    
    protected $fillable = [        
        'booking_id', 'vendor_id', 'tour_id', 'category', 'total_seats', 'total_adults', 'total_child', 'start_date', 'duration', 'priceType', 'adult_price', 'child_price', 'service_charge', 'totalPrice', 'user_type', 'create_user', 'status', 'price_breakup'
    ];
}