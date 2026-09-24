<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;
use App\HallModels\HallAttribute;

class HallRoomFacility extends Model
{
    protected $table = 'm_hfacilities';

    protected $fillable = [
        'attribute_id',
        'facility_type',
        'facility_name',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
        'created_at',
        'updated_at'
    ];

    protected $enumStatus = ['0', '1'];

    public function hallAttributes()
    {
        return $this->belongsTo(HallAttribute::class, 'attribute_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function scopeProperty($query)
    {
        return $query->where('facility_type', 'PROPERTY');
    }

    public function scopeHall($query)
    {
        return $query->where('facility_type', 'HALL');
    }
}
