<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MmtMasterInventory extends Model
{
    protected $table = 'mmt_master_inventory';
    
    protected $fillable = [
        'vendor_id', 'hotel_id', 'room_id', 'date', 'initial_quantity', 'total_available', 'total_booked'
    ];
}