<?php

namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class FlightMaster extends Model
{
    protected $table = 'flight_master';

     public function schedules(){
        return $this->hasMany(
            FlightSchedule::class,
            'flight_id',
            'id'
        );
     }

    protected $casts = [
    'faq' => 'array',
];
}
