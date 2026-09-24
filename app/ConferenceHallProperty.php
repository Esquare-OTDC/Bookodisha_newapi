<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConferenceHallProperty extends Model
{
    protected $table = 'm_property';

    protected $fillable = [
        'property_name', 'status', 'created_by', 'updated_by', 'is_deleted', 'created_at', 'updated_by'
    ];

    protected $enumStatus = ['0', '1'];
}
