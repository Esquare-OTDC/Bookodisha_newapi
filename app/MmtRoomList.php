<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MmtRoomList extends Model
{
    protected $table = 'mmt_room_list';
    
    protected $fillable = [
        'hotel_code', 'room_type_name', 'room_type_code', 'is_active', 'base_adult_occupancy', 'max_adult_occupancy', 'base_child_occupancy', 'max_child_occupancy'
    ];
}