<?php

namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class FlightSchedule extends Model
{
    protected $table = 'flight_schedule';

    protected $fillable = [
        'flight_id',
        'source_airport_id',
        'destination_airport_id',
        'departure_day',
        'departure_time',
        'arrival_time',
        'booking_start_date',
        'booking_end_time',
        'days_from_current',
        'per_transaction_ticket_limit',
        'per_day_ticket_limit',
        'total_capacity',
        'online_capacity',
        'offline_capacity',
        'slot_key',
        'created_by',
        'updated_by',
        'status',
    ];

    public function flight()
    {
        return $this->belongsTo(
            FlightMaster::class,
            'flight_id',
            'id'
        );
    }

    public function sourceAirport()
    {
        return $this->belongsTo(
            AirportMaster::class,
            'source_airport_id',
            'id'
        );
    }

    public function destinationAirport()
    {
        return $this->belongsTo(
            AirportMaster::class,
            'destination_airport_id',
            'id'
        );
    }

    public function fares()
    {
        return $this->hasMany(
            FareMaster::class,
            'schedule_id'
        );
    }
}
