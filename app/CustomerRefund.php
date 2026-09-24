<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerRefund extends Model
{
    protected $table = 'customer_refunds';
    
    protected $fillable = [
        'vendor_id', 'order_id', 'invoice_id', 'order_type', 'service_type', 'service_id', 'customer_id', 'order_date', 'cancel_date', 'paid_amount', 'refund_amount', 'refund_percent', 'payment_method', 'client_txn_id', 'pg_txn_id', 'reference_id', 'response_timestamp', 'signature', 'txn_timestamp', 'result_code', 'result_msg', 'refund_txn_id', 'response_json', 'payment_id', 'refund_status'
    ];
}
