<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TmpUser extends Model
{
    protected $table = 'temp_users';
    
    protected $fillable = [
        'email', 'attempt'
    ];
}