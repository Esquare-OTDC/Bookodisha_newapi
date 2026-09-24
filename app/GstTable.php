<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GstTable extends Model
{
    protected $table = 'gst_table';
    
    protected $fillable = [
        'vendor_id', 'service_type', 'min_amount', 'gst'
    ];
}