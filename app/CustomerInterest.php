<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerInterest extends Model
{
    protected $table = 'customer_interests';
    
    protected $fillable = [
        'vendor_id', 'application_no', 'service_name', 'cust_first_name', 'cust_last_name', 'email', 'phone', 'cust_gender', 'cust_country', 'cust_state', 'cust_city'
    ];
}