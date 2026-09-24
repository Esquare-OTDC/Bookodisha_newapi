<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MmtChildDetails extends Model
{
    protected $table = 'mmt_child_details';
    
    protected $fillable = [
        'hotel_code', 'from_value', 'to_value', 'agerange'
    ];
}