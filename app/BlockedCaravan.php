<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class BlockedCaravan extends Model
{
    protected $table = 'blocked_caravans';

    protected $fillable = [
        'vendor_id', 'caravan_id', 'vehicle_name', 'block_date', 'block_reason', 'created_by'
    ];

    /* caravan details */
    public function caravan()
    {
        return $this->belongsTo(MasterCaravan::class, 'caravan_id');
    }
}
