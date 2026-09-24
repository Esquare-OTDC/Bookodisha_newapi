<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    protected $table = 'food_items';
    
    protected $fillable = [
        'vendor_id', 'category_id', 'slot', 'item_name', 'short_description', 'image', 'price', 'max_cart_qty', 'max_qty', 'is_veg', 'status', 'created_by', 'updated_by'
    ];
}