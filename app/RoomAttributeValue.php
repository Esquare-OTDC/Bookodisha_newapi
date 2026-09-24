<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomAttributeValue extends Model
{
    protected $table = 'room_attribute_values';
    
    protected $fillable = [
        'name', 'icon', 'attr_id', 'status'
    ];
}


