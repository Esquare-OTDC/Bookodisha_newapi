<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RentalAvailability extends Model
{
    protected $table = 'rental_availability';
    
    protected $fillable = [
        'vendor_id', 'car_id', 'car_name', 'block_reason', 'quantity', 'block_date', 'status', 'created_by'
    ];
}