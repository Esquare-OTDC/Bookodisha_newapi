<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketAvailability extends Model
{
    protected $table = 'ticket_availability';
    
    protected $fillable = [
        'vendor_id', 'ticket_id', 'ticket_name', 'block_reason', 'block_date', 'status', 'created_by'
    ];
}