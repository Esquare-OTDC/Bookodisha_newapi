<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;
use App\HallModels\Hall;

class HallMasterInventory extends Model
{
    protected $table = 't_hall_inventory';

    protected $hall_id;

    protected $existing_inventory = [];

    protected $fillable = [
        'vendor_id',
        'hall_id',
        'inventory_date',
        'inventory_slot_type',
        'first_half_available',
        'second_half_available',
        'created_at',
        'updated_at',
    ];

    public function setHallId($hall_id)
    {
        $this->hall_id = $hall_id;

        return $this;
    }

    public function setExistingInventory()
    {
        $this->existing_inventory = self::where('hall_id', $this->hall_id)
            ->pluck('inventory_date')
            ->toArray();

        return $this;
    }

    public function isInventoryExist($date)
    {
        return in_array($date, $this->existing_inventory);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id', 'id');
    }
}