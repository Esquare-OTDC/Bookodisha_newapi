<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvailableSlot extends Model
{
    protected $table = 'available_slots';
    
    protected $fillable = [
        'name', 'start_time', 'end_time', 'availability', 'city'
    ];
}