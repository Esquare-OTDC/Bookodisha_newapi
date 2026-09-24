<?php

namespace App\HallModels;

use Illuminate\Database\Eloquent\Model;

class HallCategory extends Model
{
    protected $table = 'm_hcategory';

    protected $fillable = [
        'hcategory_name', 'status', 'created_by', 'updated_by', 'is_deleted', 'created_at', 'updated_at'
    ];

    protected $enumStatus = ['0', '1'];
}
