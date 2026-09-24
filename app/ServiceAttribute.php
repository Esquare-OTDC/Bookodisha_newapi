<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceAttribute extends Model
{
    protected $table = 'service_attributes';
    
    protected $fillable = [
        'name', 'slug', 'service', 'status'
    ];
}


