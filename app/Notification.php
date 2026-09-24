<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification_table';
    
    protected $fillable = [
        'device_id', 'device_token', 'message', 'status', 'date_time'
    ];
}


