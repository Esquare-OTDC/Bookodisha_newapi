<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CtpRatePlans extends Model
{
    protected $table = 'ctp_rate_plans';
    
    protected $fillable = [
        'hotel_code', 'room_type_code', 'rate_plan_code', 'rate_plan_name'
    ];
}