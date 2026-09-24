<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;

class HallBooking extends Model
{
    protected $table = 't_booking';

    protected $fillable = ['id','hall_id','slot_id','slot_type','quantity','booking_date','participant_count','route','status','created_by','updated_by','start_date','end_date','is_deleted','booking_id','vendor_id','service_charge','totalPrice','price_breakup','user_type','create_user','end_time','start_time'];

    public function halls(){
        return $this->belongsTo(Hall::class, 'hall_id', 'id');
    }

    public function slots(){
        return $this->belongsTo(Slot::class, 'slot_id', 'id');
    }

    public function property(){
        return $this->halls->property;
    }

    public $slotType = ['FIRST_HALF','SECOND_HALF','FULL_DAY'];

}
