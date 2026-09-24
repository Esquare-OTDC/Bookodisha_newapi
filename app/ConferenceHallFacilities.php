<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConferenceHallFacilities extends Model
{
    protected $table = 'm_hfacilities';

    protected $fillable = [
        'amenities', 'status', 'created_by', 'updated_by', 'is_deleted', 'created_at', 'updated_by'
    ];

    protected $enumStatus = ['0', '1'];
}
