<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FoodCategory extends Model
{
    protected $table = 'food_categories';
    
    protected $fillable = [
        'vendor_id', 'name', 'status', 'slot', 'created_by', 'updated_by'
    ];
}