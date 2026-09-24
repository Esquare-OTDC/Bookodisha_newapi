<?php
namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class PassengerDetail extends Model{
    protected $table = 'passengers';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'passenger_type',
        'order_status',
        'journey_type',
        'booking_id',
        'flight_booking_id',
        'assistance_id',
        'dob',
        'phone',
        'gender',
        'is_active',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];
}
