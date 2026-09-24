<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorRequest extends Model
{
    protected $table = 'vendor_requests';
    
    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'designation', 'address', 'enterprise_name', 'gst_number', 'gst_certificate', 'service_offered', 'property_image', 'admin_commission', 'status'
    ];
}

