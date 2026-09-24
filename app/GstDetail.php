<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GstDetail extends Model
{
    protected $table = 'gst_details';
    
    protected $fillable = [
        'name', 'value', 'modified_by'
    ];
}