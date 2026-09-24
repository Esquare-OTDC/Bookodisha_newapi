<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterCar extends Model
{
    protected $table = 'master_cars';

    protected $fillable = [
        'vendor_id', 'title', 'slug', 'content', 'feature_image', 'banner_image', 'city', 'address', 'map_lat', 'map_lng', 'map_zoom', 'is_featured', 'gallery', 'video', 'faqs', 'property', 'property_slug', 'quantity', 'price_per_hour', 'max_distance', 'price_per_km', 'price_for_halt', 'halt_hour', 'dist_cover_per_hour', 'detention_charge_per_hour', 'free_km_per_hour', 'min_duration', 'passenger', 'gear', 'baggage', 'door', 'status', 'create_user', 'update_user', 'deleted_at', 'contact_email', 'contact_number', 'additional_email', 'additional_phone', 'review_score', 'review_count', 'service_fee', 'guide_price_per_day', 'gst_applicable', 'terms_conditions', 'show_price', 'gst_number', 'gst_legal_name', 'paytm_mid', 'hdfc_mid','car_book_type'
    ];
}
