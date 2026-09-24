<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterHotel extends Model
{
    protected $table = 'master_hotels';
    
    protected $fillable = [
        'vender_id', 'mmt_hotel_id', 'ctp_hotel_id', 'serial_prefix', 'name', 'slug', 'content', 'feature_image', 'banner_image', 'city', 'place', 'address', 'real_address', 'contact_email', 'manager_name', 'contact_number', 'reception_contact', 'additional_email', 'additional_phone', 'map_lat', 'map_lng', 'map_zoom', 'is_featured', 'gallery', 'video', 'policy', 'terms_conditions', 'property', 'property_slug', 'star_rate', 'price', 'check_in_time', 'check_out_time', 'status', 'create_user', 'update_user', 'review_score', 'review_count', 'gst_applicable', 'gst_number', 'gst_legal_name', 'paytm_mid', 'hdfc_mid', 'service_fee', 'show_price', 'more_nights', 'foreign_visitors', 'weekend_days', 'week_days'
    ];
}


