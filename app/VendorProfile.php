<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorProfile extends Model
{
    protected $table = 'vendor_profile';
    
    protected $fillable = [
        'vendor_id', 'slug', 'profile_type', 'profile_url', 'banner_image', 'details'
    ];
}


