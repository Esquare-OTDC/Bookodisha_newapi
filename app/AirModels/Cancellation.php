<?php
namespace App\AirModels;

use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model{
    protected $table = 'cancellations';

    protected $fillable = [
        'passenger_id',
        'cancellation_reason',
        'cancelled_by',
        'gross_fare_paid',
        'transaction_id',
        'refund_txn_id',
        'refund_id',
        'reference_id',
        'cancellation_fee',
        'refundable_tax_amount',
        'ancillary_refund_amount',
        'net_refund_amount',
        'refund_status',
        'cancelled_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    public function passenger(){
        return $this->belongsTo(PassengerDetail::class,'passenger_id');
    }
}
