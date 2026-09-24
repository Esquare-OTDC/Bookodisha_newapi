<?php
namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class FlightBooking extends Model{
    protected $table = 'flight_bookings';

    protected $fillable = [
        'booking_id',
        'pnr_code',
        'booking_type',
        'onward_schedule_id',
        'return_schedule_id',
        'is_reschedule',
        'reschedule_id',
        'adult_count',
        'infant_count',
        'total_passengers',
        'base_amount',
        'service_charge_amount',
        'departure_adult_count',
        'departure_infant_count',
        'return_adult_count',
        'return_infant_count',
        'gst_amount',
        'other_tax_amount',
        'ancillary_amount',
        'total_amount',
        'payment_status',
        'booking_status',
        'booked_at',
        'cancelled_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'onward_flight_date',
        'return_flight_date',
        'adult_fare',
        'child_fare',
        'gst_company_address',
        'gst_company_name',
        'gst_regd_no',
    ];

    public function scheduleOnward(){
        return $this->belongsTo(FlightSchedule::class,'onward_schedule_id');
    }

    public function scheduleReturn(){
        return $this->belongsTo(FlightSchedule::class,'return_schedule_id');
    }
}
