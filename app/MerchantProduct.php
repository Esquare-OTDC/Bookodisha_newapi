<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MerchantProduct extends Model
{
    protected $table = 'merchant_products';
    
    protected $fillable = [
        'vendor_id', 'name', 'slug', 'sku', 'category', 'descriptions', 'feature_image', 'gallery_image', 'price', 'max_quantity', 'properties', 'review_count', 'review_score', 'status', 'create_user', 'update_user'
    ];
}