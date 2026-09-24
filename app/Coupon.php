<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';
    
    protected $fillable = [
        'vendor_id', 'service_type', 'access_type', 'coupon_name', 'coupon_code', 'coupon_amount', 'min_order_amount', 'description', 'coupon_use_type', 'frequency_per_user', 'frequency', 'already_used', 'start_date', 'end_date', 'status', 'multi_usage', 'created_by', 'modified_by'
    ];
}