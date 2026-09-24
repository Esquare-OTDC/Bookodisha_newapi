<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceReview extends Model
{
    protected $table = 'service_reviews';
    
    protected $fillable = [
        'vendor_id', 'user_id', 'service', 'service_id', 'content', 'rate_number', 'status', 'publish_date'
    ];
}


