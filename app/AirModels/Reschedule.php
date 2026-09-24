<?php
namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class Reschedule extends Model{
    protected $table = 'reschedules';

    protected $fillable = [
        'passenger_id',
        'old_booking_id',
        'new_booking_id',
        'old_fare_amount',
        'new_fare_amount',
        'fare_difference',
        'reschedule_penalty_fee',
        'net_additional_charge',
        'net_additional_charge',
        'payment_id',
        'reschedule_reason',
        'status',
        'rescheduled_at',
        'created_by',
        'updated_by',
        'updated_at',
        'created_at'
    ];
}
