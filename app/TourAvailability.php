<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TourAvailability extends Model
{
    protected $table = 'tour_availability';
    
    protected $fillable = [
        'vendor_id', 'tour_id', 'tour_name', 'block_reason', 'block_date', 'status', 'created_by'
    ];
}