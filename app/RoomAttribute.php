<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoomAttribute extends Model
{
    protected $table = 'room_attributes';
    
    protected $fillable = [
        'vendor_id', 'name', 'status'
    ];
}


