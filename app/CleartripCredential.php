<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CleartripCredential extends Model
{
    protected $table = 'cleartrip_credentials';
    
    protected $fillable = [
        'status', 'vendor_id', 'hotel_id', 'hotel_code', 'gateway_type', 'sandbox_username', 'live_username', 'sandbox_password', 'live_password', 'sandbox_listing_url', 'live_listing_url', 'sandbox_availability_url', 'live_availability_url', 'sandbox_rate_url', 'live_rate_url'
    ];
}