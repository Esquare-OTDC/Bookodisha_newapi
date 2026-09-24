<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TempOtp extends Model
{
    protected $table = 'temp_otp';
    
    protected $fillable = [
        'device_detail', 'otp'
    ];
}


