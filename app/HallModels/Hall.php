<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    protected $table = 'm_hall';

    protected $fillable = ['id','slug','hall_name','hcategory_id','property_id','hfacilities_id','room_capacity','quantity','feature_image','gallery','publish_status','status','created_by','created_at','updated_by','updated_at','is_deleted'];

    public function property()
    {
        return $this->belongsTo(HallProperty::class, 'property_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(HallCategory::class, 'hcategory_id', 'id');
    }

    public function inventory()
    {
        return $this->hasMany(HallMasterInventory::class, 'hall_id', 'id');
    }

    public function blockedInventory()
    {
        return $this->hasMany(BlockedHallInventory::class, 'hall_id', 'id');
    }

    public function slots()
    {
        return $this->hasMany(Slot::class, 'hall_id', 'id');
    }

    public function facilities(){
        return HallRoomFacility::whereIn('id', explode(",", $this->hfacilities_id))->get();
    }

    public function facilitiesByattributes(){
        $attributes = HallRoomFacility::select('id', 'facility_name', 'attribute_id')->whereIn('id', explode(",", $this->hfacilities_id))->with(['hallAttributes'])->get();
        $facilitiesByattributes = array();
        foreach($attributes as $attr){
            $facilitiesByattributes[strtoupper($attr->hallAttributes->attribute_name)][] = $attr->facility_name;
        }
        return $facilitiesByattributes;
    }

}
