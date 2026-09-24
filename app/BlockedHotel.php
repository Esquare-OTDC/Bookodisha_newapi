<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockedHotel extends Model
{
    protected $table = 'blocked_hotels';
    
    protected $fillable = [
        'vendor_id', 'hotel_id', 'hotel_name', 'rooms', 'block_date', 'block_reason', 'created_by'
    ];
}