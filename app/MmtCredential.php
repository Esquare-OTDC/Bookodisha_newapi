<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MmtCredential extends Model
{
    protected $table = 'mmt_credentials';
    
    protected $fillable = [
        'vendor_id', 'gateway_type', 'sandbox_bearer_token', 'sandbox_channel_token', 'live_bearer_token', 'live_channel_token', 'sandbox_listing_url', 'sandbox_ari_url', 'live_listing_url', 'live_ari_url'
    ];
}