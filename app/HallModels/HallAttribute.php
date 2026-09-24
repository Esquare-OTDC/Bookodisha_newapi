<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;
use App\HallModels\HallRoomFacility;

class HallAttribute extends Model
{
    protected $table = 'm_attribute';

    protected $fillable = [
        'attribute_name', 'status', 'created_by', 'updated_by', 'is_deleted', 'created_at', 'updated_at'
    ];

    protected $enumStatus = ['0', '1'];

    public function facilities()
    {
        return $this->hasMany(HallRoomFacility::class, 'attribute_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }
}
