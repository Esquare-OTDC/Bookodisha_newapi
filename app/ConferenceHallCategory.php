<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConferenceHallCategory extends Model
{
    protected $table = 'm_hcategory';

    protected $fillable = [
        'hcategory_name', 'status', 'created_by', 'updated_by', 'is_deleted', 'created_at', 'updated_by'
    ];

    protected $enumStatus = ['0', '1'];
}
