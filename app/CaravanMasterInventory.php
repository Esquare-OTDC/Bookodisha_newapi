<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CaravanMasterInventory extends Model
{
    protected $table = 'caravan_master_inventory';

    protected $fillable = [
        'vendor_id', 'caravan_id','days', 'date', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked', 'total_online_completed', 'total_online_pending', 'total_offline_completed', 'total_offline_pending'
    ];

    public function caravan(){
        return $this->belongsTo(MasterCaravan::class, 'caravan_id', 'id');
    }
}
