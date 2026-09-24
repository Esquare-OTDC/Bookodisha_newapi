<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubuserAccess extends Model
{
    protected $table = 'subuser_access';
    
    protected $fillable = [
        'user_id', 'service', 'service_id'
    ];
}