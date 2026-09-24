<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MmtHotelTable extends Model
{
    protected $table = 'mmt_hotel_table';
    
    protected $fillable = [
        'hotel_code'
    ];
}