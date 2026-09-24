<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StaticReview extends Model
{
    protected $table = 'static_reviews';
    
    protected $fillable = [
        'name', 'service_type', 'status'
    ];
}