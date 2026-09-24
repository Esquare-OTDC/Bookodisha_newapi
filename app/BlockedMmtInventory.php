<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockedMmtInventory extends Model
{
    protected $table = 'blocked_mmt_inventory';
    
    protected $fillable = [
        'vendor_id', 'platform', 'hotel_id', 'hotel_name', 'rooms', 'block_date', 'block_reason', 'created_by'
    ];
}