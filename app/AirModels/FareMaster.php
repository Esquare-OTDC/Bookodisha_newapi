<?php

namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class FareMaster extends Model
{
    protected $table = 'fare_master';
    protected $fillable = [
        'flight_id',
        'schedule_id',
        'passenger_type',
        'base_fare',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $primaryKey = 'id';

    public $timestamps = true;

    public function flight()
    {
        return $this->belongsTo(
            FlightMaster::class,
            'flight_id',
            'id'
        );
    }
    public function schedule(){
        return $this->belongsTo(
            FlightSchedule::class,
            'schedule_id'
        );
    }

}