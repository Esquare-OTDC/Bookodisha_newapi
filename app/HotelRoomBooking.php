<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HotelRoomBooking extends Model
{
    protected $table = 'hotel_room_bookings';
    
    protected $fillable = [
        'booking_id', 'vendor_id', 'hotel_id', 'room_details', 'room_request', 'start_date', 'end_date', 'total_rooms', 'adult', 'children', 'extra_person_price', 'pricing_details', 'totalPrice', 'request_for', 'user_type', 'create_user', 'update_user', 'status', 'payment_id', 'payment_method', 'payment_status'
    ];
}