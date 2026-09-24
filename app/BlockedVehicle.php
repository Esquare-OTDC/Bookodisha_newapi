<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockedVehicle extends Model
{
    protected $table = 'blocked_vehicles';
    
    protected $fillable = [
        'vendor_id', 'vehicle_id', 'vehicle_name', 'block_date', 'block_reason', 'created_by'
    ];
}