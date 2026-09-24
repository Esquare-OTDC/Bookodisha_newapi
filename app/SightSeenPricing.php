<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SightSeenPricing extends Model
{
    protected $table = 'sight_seen_pricing';
    
    protected $fillable = [
        'vendor_id', 'tour_id', 'tour_name', 'price_plan', 'offer_percentage', 'offer_type', 'start_date', 'end_date', 'created_by'
    ];
}