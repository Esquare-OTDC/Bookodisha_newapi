<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderMaster extends Model
{
    protected $table = 'order_masters';
    
    protected $fillable = [
        'order_id', 'invoice_id', 'invoice_serial', 'vendor_id', 'vendor_name', 'order_type', 'book_from', 'service_type', 'service_category', 'customer_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address1', 'customer_address2', 'customer_city', 'customer_state', 'customer_zipcode', 'customer_country', 'customer_notes', 'senior_citizen', 'gst_regd_no', 'gst_company_name', 'gst_company_address', 'expected_arrival_time', 'need_pickup', 'service_name', 'service_name_id', 'service_city', 'start_date', 'end_date', 'start_time', 'end_time', 'customer_checkin', 'customer_checkout', 'travel_distance', 'travel_hour', 'halt_hour', 'pickup_address', 'drop_location', 'travel_route', 'halt_charge', 'travel_trip', 'service_quantity', 'room_request', 'room_details', 'room_slug', 'request_for_room', 'total_room_category', 'total_rooms', 'total_guests', 'total_adults', 'total_child', 'extra_guests', 'extra_person_price', 'total_service_price', 'adult_price', 'child_price', 'price_type', 'coupon_name', 'coupon_code', 'coupon_amount', 'sub_total_price', 'tax_percentage', 'tax_amount', 'service_charge', 'guide_charge', 'days_for_guide', 'total_order_price', 'status', 'cancel_reason', 'refund_amount', 'refund_tax', 'rental_breakdown', 'payment_gateway', 'payment_method', 'paytm_mid', 'hdfc_key', 'hdfc_salt', 'split_initiate_status', 'admin_amount', 'vendor_amount', 'request_from', 'payment_status', 'payment_id', 'offline_link_id', 'offline_short_url', 'offline_long_url', 'offline_link_expiry', 'transaction_id', 'invoice', 'confimation_voucher', 'cancel_voucher', 'qr_code', 'qr_base64', 'qr_verified', 'cancel_date', 'payment_gateway_error', 'payment_late_captured', 'payment_error_response', 'book_naration', 'book_ip', 'gate_number', 'foreign_visitor', 'coupon_commison_type'
    ];
}