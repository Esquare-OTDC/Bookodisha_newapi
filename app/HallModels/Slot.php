<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    protected $table = 'm_slot';
    protected $fillable = ['hall_id','booking_type','slot','time','price','status','created_by','updated_by','is_deleted' ];

    public function hall()
    {
        return $this->belongsTo(Hall::class, 'hall_id', 'id');
    }
}
