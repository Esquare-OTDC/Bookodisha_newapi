<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MmtRatePlans extends Model
{
    protected $table = 'mmt_rate_plans';
    
    protected $fillable = [
        'hotel_code', 'room_type_code', 'rate_plan_code', 'room_type_name', 'rate_plan_name', 'is_active', 'IsEditable', 'meal_plan', 'is_linked_rate_plan', 'lead_occupancy'
    ];
}