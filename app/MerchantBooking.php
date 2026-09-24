<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantBooking extends Model
{
    protected $table = 'merchant_bookings';
    
    protected $fillable = [
        'booking_id', 'vendor_id', 'item_request', 'item_details', 'total_price', 'user_type', 'create_user', 'status'
    ];
}


