<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $table = 'tours';
    
    protected $fillable = [
        'vendor_id', 'name', 'slug', 'content', 'feature_image', 'banner_image', 'gallery', 'short_desc', 'category', 'city', 'address', 'map_lat', 'map_lng', 'map_zoom', 'video', 'single_share_price', 'double_share_price', 'triple_share_price', 'child_price', 'single_share_policy', 'double_share_policy', 'triple_share_policy', 'child_price_policy', 'service_fee', 'gst_applicable', 'duration_start', 'duration_start_text', 'duration_end', 'duration_end_text', 'max_people', 'minimum_people_for_single_booking', 'maximum_people_for_single_booking', 'is_special_tour', 'faqs', 'terms_conditions', 'itinerary', 'route_map', 'contact_email', 'contact_number', 'additional_email', 'additional_phone', 'include', 'exclude', 'property', 'property_slug', 'review_count', 'review_score', 'not_available', 'status', 'show_price', 'gst_number', 'gst_legal_name', 'paytm_mid', 'hdfc_mid', 'start_date', 'days_from_start_date', 'create_user', 'update_user'
    ];
}


