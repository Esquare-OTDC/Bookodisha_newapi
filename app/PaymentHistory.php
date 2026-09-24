<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';
    
    protected $fillable = [
        'order_id', 'vendor_id', 'user_id', 'payment_method', 'transaction_id', 'amount', 'product_info', 'first_name', 'last_name', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'country', 'zipcode', 'udf1', 'udf2', 'udf3', 'udf4', 'udf5', 'mihpayid', 'mode', 'status', 'unmapped_status', 'card_category', 'discount', 'net_amount_debit', 'added_on', 'field1', 'field2', 'field3', 'field4', 'field5', 'field6', 'field7', 'field8', 'field9', 'payment_source', 'PG_TYPE', 'bank_ref_num', 'bank_code', 'error', 'error_Message', 'name_on_card', 'card_number', 'cardhash', 'payment_response', 'split_response'
    ];
}


