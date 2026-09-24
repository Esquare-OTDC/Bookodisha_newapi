<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CtpHotelTable extends Model
{
    protected $table = 'ctp_hotel_table';
    
    protected $fillable = [
        'vendor_id', 'hotel_code'
    ];
}