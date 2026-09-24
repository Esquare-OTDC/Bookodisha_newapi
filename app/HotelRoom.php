<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HotelRoom extends Model
{
    protected $table = 'hotel_rooms';
    
    protected $fillable = [
        'hotel_id', 'mmt_room_id', 'ctp_room_id', 'title', 'slug', 'content', 'image', 'gallery', 'video', 'price', 'extra_bed_price', 'extra_bed_allowed', 'quantity', 'mmt_quantity', 'beds', 'property', 'bed_type', 'size', 'adults', 'children', 'status', 'ical_import_url', 'create_user', 'update_user', 'deleted_at'
    ];
}


