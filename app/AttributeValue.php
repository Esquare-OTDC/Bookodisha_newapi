<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $table = 'attributes_values';
    
    protected $fillable = [
        'name', 'slug', 'icon', 'attr_id', 'status'
    ];
}


