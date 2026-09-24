<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterInventory extends Model
{
    protected $table = 'master_inventory';
    
    protected $fillable = [
        'vendor_id', 'hotel_id', 'room_id', 'date', 'total_available', 'total_booked', 'total_blocked', 'total_online_completed', 'total_online_pending', 'total_offline_completed', 'total_offline_pending', 'total_tour_booking', 'total_mmt_booking'
    ];
}