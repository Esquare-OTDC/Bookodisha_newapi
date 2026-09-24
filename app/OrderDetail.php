<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = 'order_details';

    protected $fillable = [
        'order_id', 'order_master_id', 'vendor_id', 'customer_id', 'service_type', 'service_name', 'service_name_id', 'service_city', 'start_date', 'end_date', 'start_time', 'end_time', 'pickup_address', 'pickup_city', 'drop_address', 'drop_city', 'distance', 'room_number', 'service_item_id', 'service_item_name', 'service_item_price', 'total_guests', 'total_adult', 'total_child', 'extra_bed', 'extra_bed_price', 'total_extrabed_price', 'is_seasonal_added', 'seasonal_type', 'seasonal_price', 'unit_total_price', 'coupon_amount', 'service_item_quantity', 'tax_percentage', 'tax_amount', 'total_room_price', 'refund_amount', 'refund_tax', 'status'
    ];
}
