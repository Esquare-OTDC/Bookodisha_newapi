<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;
use App\HallModels\Hall;

class BlockedHallInventory extends Model
{
    protected $table = 't_blocked_hall_inventory';

    protected $fillable = [
        'vendor_id',
        'hall_id',
        'property_id',
        'property_name',
        'slot_type',
        'block_date',
        'block_reason',
        'created_by',
        'created_at',
        'updated_at',
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id', 'id');
    }
}