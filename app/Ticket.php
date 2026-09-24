<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';
    
    protected $fillable = [
        'vendor_id', 'name', 'slug', 'content', 'feature_image', 'banner_image', 'gallery', 'category', 'booking_mode', 'city', 'place', 'address', 'map_lat', 'map_lng', 'map_zoom', 'video', 'adult_price', 'child_price', 'service_fee', 'gst_applicable', 'terms_conditions', 'gate_no', 'ticket_type', 'slots', 'days_from_start_date', 'start_date', 'end_date', 'start_time', 'end_time', 'max_people', 'max_people_offline', 'not_available', 'max_ticket_per_txn', 'max_ticket_per_user_per_day', 'faqs', 'extra_services', 'property', 'property_slug', 'review_count', 'review_score', 'contact_email', 'contact_number', 'additional_email', 'additional_phone', 'gst_number', 'gst_legal_name', 'paytm_mid', 'hdfc_mid', 'book_start_date', 'book_end_time', 'status', 'show_price', 'create_user', 'update_user'
    ];
}