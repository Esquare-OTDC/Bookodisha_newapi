<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CtpRoomList extends Model
{
    protected $table = 'ctp_room_list';
    
    protected $fillable = [
        'hotel_code', 'room_type_name', 'room_type_code'
    ];
}