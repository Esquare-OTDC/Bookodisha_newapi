<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Prebooking extends Model
{
    protected $table = 'prebookings';
    
    protected $fillable = [
        'application_no', 'vendor_id', 'service_id', 'service_name', 'service_quantity', 'adult', 'child', 'customer_id', 'email', 'phone', 'cust_first_name', 'cust_last_name', 'gender', 'customer_country', 'customer_state', 'customer_city', 'identity_type', 'identity_file', 'type_of_experience', 'year_of_experience', 'publication', 'social_media_handle', 'social_media_handle_url', 'description_experience', 'approve_status', 'book_status', 'modified_by'
    ];
}