<?php
namespace App\AirModels;

use App\AirModels\FlightSchedule;
use Illuminate\Database\Eloquent\Model;

class SeatInventory extends Model{
    protected $table = 'seat_inventory';

    protected $fillable = [
        'schedule_id',
        'date',
        'online_capacity',
        'offline_capacity',
        'online_booked',
        'offline_booked',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function schedule()
    {
        return $this->belongsTo(FlightSchedule::class, 'schedule_id');
    }
}
