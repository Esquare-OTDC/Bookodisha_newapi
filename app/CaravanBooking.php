<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaravanBooking extends Model
{
    protected $table = 'caravan_bookings';

    protected $fillable = [
        'booking_id', 'vendor_id', 'caravan_id', 'total_caravans', 'start_date', 'end_date', 'start_time', 'end_time', 'pickup_address', 'drop_location', 'route', 'rental_type', 'trip_type', 'totalPrice', 'user_type', 'create_user', 'status', 'need_guide', 'days_for_guide','no_of_days'
    ];

    /* protected $hidden = [
        'booking_id', 'vendor_id', 'caravan_id', 'total_caravans'
    ]; */

    public function caravanDetails(){
        return $this->hasOne(MasterCaravan::class, 'id', 'caravan_id');
    }


}
