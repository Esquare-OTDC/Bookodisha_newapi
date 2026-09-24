<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RentalMasterInventory extends Model
{
    protected $table = 'rental_master_inventory';
    
    protected $fillable = [
        'vendor_id', 'car_id', 'date', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked', 'total_online_completed', 'total_online_pending', 'total_offline_completed', 'total_offline_pending'
    ];
}