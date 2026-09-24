<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PropertyAccount extends Model
{
    protected $table = 'property_accounts';
    
    protected $fillable = [
        'vendor_id', 'service_type', 'service_id', 'hdfc_key', 'hdfc_salt', 'hdfc_mid', 'created_by', 'modified_by'
    ];
}