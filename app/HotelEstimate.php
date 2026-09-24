<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HotelEstimate extends Model
{
    protected $table = 'hotel_estimates';
    
    protected $fillable = [
        'invoice_id', 'invoice_serial', 'vendor_id', 'estimate_type', 'vendor_name', 'service_type', 'service_category', 'customer_name', 'customer_email', 'customer_phone', 'customer_address1', 'customer_address2', 'customer_city', 'customer_state', 'customer_zipcode', 'customer_country', 'gst_regd_no', 'gst_company_name', 'gst_company_address', 'service_name', 'service_name_id', 'service_city', 'start_date', 'end_date', 'start_time', 'end_time', 'travel_distance', 'travel_hour', 'halt_hour', 'pickup_address', 'drop_location', 'travel_route', 'halt_charge', 'travel_trip', 'service_quantity', 'room_request', 'room_details', 'room_slug', 'request_for_room', 'total_room_category', 'total_rooms', 'total_guests', 'total_adults', 'total_child', 'extra_guests', 'extra_person_price', 'total_service_price', 'adult_price', 'child_price', 'price_type', 'coupon_name', 'coupon_code', 'coupon_amount', 'sub_total_price', 'tax_percentage', 'tax_amount', 'service_charge', 'guide_charge', 'days_for_guide', 'total_order_price', 'status', 'rental_breakdown', 'payment_gateway', 'payment_method', 'payment_status', 'transaction_id', 'invoice', 'confimation_voucher', 'book_naration'
    ];
}