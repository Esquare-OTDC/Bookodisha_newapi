<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketBooking extends Model
{
    protected $table = 'ticket_bookings';
    
    protected $fillable = [
        'booking_id', 'vendor_id', 'ticketing_id', 'book_from', 'category', 'total_seats', 'total_adults', 'total_child', 'start_date', 'start_time', 'end_time', 'adult_price', 'child_price', 'service_charge', 'totalPrice', 'user_type', 'create_user', 'status', 'extra_services'
    ];
}