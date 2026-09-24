<?php

namespace App\Http\Controllers\PaymentStatus;

use App\Http\Controllers\Controller;
use App\MasterCaravan;
use App\Traits\CaravanTraits;
use App\Traits\EmailTraits;
use App\Traits\SmsTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PaymentUpdate extends Controller
{
    use CaravanTraits, EmailTraits, SmsTraits;

    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function initializes the PaymentUpdate controller. It sets the application environment, configures HDFC payment gateway keys, and initializes rental details and confirmation templates. This setup is essential for processing payment status updates for caravan bookings.
    *
    * *************************************************************************************************************************/
    public function __construct()
    {
        // Set Environment
        $this->setAppEnv('PROD'); // or 'PROD' or 'TEST'
        $paymentGatewayKeys = $this->getKeys();
        /* Set up hdfc config */
        $HDFC_KEY = $paymentGatewayKeys['HDFC_KEY'];
        $HDFC_SALT = $paymentGatewayKeys['HDFC_SALT'];
        $MERCHANT_ID = $paymentGatewayKeys['MERCHANT_ID'];
        $VERIFY_URL = $paymentGatewayKeys['VERIFY_URL'];
        $this->setHdfcConfig($HDFC_KEY, $HDFC_SALT, $MERCHANT_ID);
        $this->setVerifyUrl($VERIFY_URL);
        /* Setup Rentail Details */
        $this->setRentalDetails();
        /* Setup Confirmation Template */
        $this->setComfirmationTemplate();
    }

    /**************************************************************************************************************************
     * @author : Saikat Mohanty
     * @date: 23/04/2026
     * Description: This function checks the payment status of pending caravan orders and updates the order status, inventory, and generates invoice accordingly. It also handles failed payments by updating the order status to cancelled and adjusting the inventory.
     * @param Request $request
     * Request Parameters:
     * - order_id (optional): Filter by specific order ID
     * - vendor_id (optional): Filter by specific vendor ID
     * ********************************************************************************************************************* */
    public function updatePaymentCaravanStatus(Request $request)
    {
        $orderId = $request->order_id ?? null;
        $vendorId = $request->vendor_id ?? null;
        $status = [];
        if ($orderId) {
            $this->setOrderIdCondition($orderId);
        }

        if ($vendorId) {
            $this->setOrderVendorIdCondition($vendorId);
        }


        $pendingOrders = $this->getPendingOrder()->where('service_type', 'caravan')->get();

        if ($pendingOrders->isEmpty()) {
            return response()->json(['message' => 'No pending orders found.']);
        }


        $emailInvoices = [];
        $confirmationJobs = [];

        foreach ($pendingOrders as $orders) {
            if (!empty($orders->hdfc_key) && !empty($orders->hdfc_salt) && $this->getAppEnv() == 'PROD') {
                $this->setHdfcConfig($orders->hdfc_key, $orders->hdfc_salt);
            }

            $responses = $this->checkPaymentStatus($orders->transaction_id);

            //dd($responses, $orders->transaction_id);


            if ($responses['status'] == 1 && $responses['transaction_details'][$orders->transaction_id]['status'] == 'success') {

                $txn_response = $responses['transaction_details'][$orders->transaction_id];

                /* Response setup */
                $mode = isset($txn_response['mode']) ? $txn_response['mode'] : '';
                $unmappedstatus = isset($txn_response['unmappedstatus']) ? $txn_response['unmappedstatus'] : '';
                $card_category = isset($txn_response['cardCategory']) ? $txn_response['cardCategory'] : '';
                $discount = isset($txn_response['disc']) ? $txn_response['disc'] : 0;
                $net_amount_debit = isset($txn_response['net_amount_debit']) ? $txn_response['net_amount_debit'] : '';
                $addedon = isset($txn_response['addedon']) ? $txn_response['addedon'] : '';
                $field1 = isset($txn_response['field1']) ? $txn_response['field1'] : '';
                $field2 = isset($txn_response['field2']) ? $txn_response['field2'] : '';
                $field3 = isset($txn_response['field3']) ? $txn_response['field3'] : '';
                $field4 = isset($txn_response['field4']) ? $txn_response['field4'] : '';
                $field5 = isset($txn_response['field5']) ? $txn_response['field5'] : '';
                $field6 = isset($txn_response['field6']) ? $txn_response['field6'] : '';
                $field7 = isset($txn_response['field7']) ? $txn_response['field7'] : '';
                $field8 = isset($txn_response['field8']) ? $txn_response['field8'] : '';
                $field9 = isset($txn_response['field9']) ? $txn_response['field9'] : '';
                $payment_source = isset($txn_response['payment_source']) ? $txn_response['payment_source'] : '';
                $PG_TYPE = isset($txn_response['PG_TYPE']) ? $txn_response['PG_TYPE'] : '';
                $bank_ref_num = isset($txn_response['bank_ref_num']) ? $txn_response['bank_ref_num'] : '';
                $bankcode = isset($txn_response['bankcode']) ? $txn_response['bankcode'] : '';
                $error_code = isset($txn_response['error_code']) ? $txn_response['error_code'] : '';
                $error_Message = isset($txn_response['error_Message']) ? $txn_response['error_Message'] : '';
                $name_on_card = isset($txn_response['name_on_card']) ? $txn_response['name_on_card'] : '';
                $card_no = isset($txn_response['card_no']) ? $txn_response['card_no'] : '';
                $cardhash = isset($txn_response['cardhash']) ? $txn_response['cardhash'] : '';

                $payment_response = json_encode($responses);

                $payment_method = '';
                if (!empty($bankcode))
                    $payment_method .= $bankcode;
                else if (!empty($mode))
                    $payment_method .= $mode;
                else if (!empty($PG_TYPE))
                    $payment_method .= $PG_TYPE;
                else
                    $payment_method .= 'N/A';

                if (!empty($field8) && $payment_method == 'UPI') {
                    $payment_method .= '(' . $field8 . ')';
                }

                /* Payment History Update */
                DB::table('payment_history')
                    ->where('id', $orders->payment_id)
                    ->update([
                        'mihpayid'         => $txn_response['mihpayid'],
                        'mode'             => $mode,
                        'status'           => $txn_response['status'],
                        'unmapped_status'  => $unmappedstatus,
                        'card_category'    => $card_category,
                        'discount'         => $discount,
                        'net_amount_debit' => $net_amount_debit,
                        'added_on'         => $addedon,
                        'field1'           => $field1,
                        'field2'           => $field2,
                        'field3'           => $field3,
                        'field4'           => $field4,
                        'field5'           => $field5,
                        'field6'           => $field6,
                        'field7'           => $field7,
                        'field8'           => $field8,
                        'field9'           => $field9,
                        'payment_source'   => $payment_source,
                        'PG_TYPE'          => $PG_TYPE,
                        'bank_ref_num'     => $bank_ref_num,
                        'bank_code'        => $bankcode,
                        'error'            => $error_code,
                        'error_Message'    => $error_Message,
                        'name_on_card'     => $name_on_card,
                        'card_number'      => $card_no,
                        'cardhash'         => $cardhash,
                        'payment_response' => $payment_response,
                        'updated_at'       => now(),
                    ]);



                $QrCodeData = array(
                    'invoiceId' => $orders->invoice_id,
                    'orderId' => $orders->order_id,
                    'txnId' => $orders->transaction_id,
                    'serviceType' => $orders->service_type,
                );

                $QrCode = $this->generateQrCode($QrCodeData);
                $qr_base64 = $this->siteUrl . $QrCode;

                $RentalInvoice = $this->getRentalDetails();

                $Subject = $RentalInvoice->subject . ' - ' . $orders->service_name . ' - Invoice ID - ' . $orders->invoice_id;

                $confirm_voucher = '';
                $invoice_serial = '';

                $caravan_details = DB::table('master_caravans')->where('id', $orders->service_name_id)->first();

                $this->sendCaravanInvoiceEmail($orders, $txn_response, $payment_method, $caravan_details);

                $Message = $this->getMessage();
                $EmailBody = $this->getEmailBody();
                $confirmationMailData = $this->confirmationMail();
                $to = $confirmationMailData['to'];

                $emailInvoices[] = array(
                    'to' => $to,
                    'body' => $EmailBody,
                    'subject' => $Subject
                );

                $confirmationJobs[] = array(
                    'to' => $to,
                    'body' => $confirmationMailData['emailBody'],
                    'subject' => $confirmationMailData['subject']
                );

                $smsData = [
                    'ref_code' => 'RegistrationSuccess',
                    'variables' => [
                        '~var1~' => $orders->customer_name ?? 'Customer',
                        '~var2~' => 'Booking ID: ' . ($orders->order_id ?? '1234567890'),
                        '~var3~' => 'Odisha Tourism',
                        '~var4~' => date('d-m-Y'),
                    ],
                    'mobiles' => [
                        '6370805585'
                    ]
                ];

                DB::table('order_details')->where('order_master_id', $orders->id)
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now()
                    ]);


                DB::table('order_masters')->where('id', $orders->id)
                    ->update([
                        'payment_status' => $txn_response['status'],
                        'payment_method' => $payment_method,
                        'status' => 'completed',
                        'invoice' => addslashes($Message),
                        'confimation_voucher' => addslashes($confirm_voucher),
                        'qr_code' => $QrCode,
                        'qr_base64' => $qr_base64,
                        'qr_verified' => 0,
                        'updated_at' => now()
                    ]);

                $status[] = [
                    'order_id' => $orders->id,
                    'transaction_id' => $orders->transaction_id,
                    'payment_status' => $responses['transaction_details'][$orders->transaction_id]['status'] ?? 'N/A',
                    'message' => 'Payment successful. Inventory updated and invoice generated.',
                ];

                sleep(1); // Sleep for 1 second to avoid overwhelming the email service
                $this->sendEmail($confirmationJobs);
                $this->SmsSend($smsData);
                sleep(1); // Sleep for 1 second before sending the next batch of emails
                $this->sendEmail($emailInvoices);
            } else {
                /* Update caravan inventory for failed payment */
                $this->updateCaravanForFailedPayment($orders->id);

                $status[] = [
                    'order_id' => $orders->id,
                    'transaction_id' => $orders->transaction_id,
                    'payment_status' => $responses['transaction_details'][$orders->transaction_id]['status'] ?? 'N/A',
                    'message' => 'Payment failed or pending. Inventory updated if necessary.',
                ];
            }
        }
        return response()->json(['message' => 'Payment status updated successfully.', 'data' => $status]);
    }

    public function testsmsApi()
    {
        $smsData = [
            'ref_code' => 'RegistrationSuccess',
            'variables' => [
                '~var1~' => 'Test User',
                '~var2~' => 'Booking ID: 123456',
                '~var3~' => 'Odisha Tourism',
                '~var4~' => date('d-m-Y'),
            ],
            'mobiles' => [
                '6370805585'
            ]
        ];
        if (empty($smsData['mobiles'])) {
            return response()->json(['status' => false, 'message' => 'Mobile number is required']);
        }

        if (empty($smsData['ref_code'])) {
            return response()->json(['status' => false, 'message' => 'Template reference code is required']);
        }
        $response = $this->SmsSend($smsData);
        return response()->json($response);




    }
    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-28
    * Description: This function add or insert records in inventory for next 90 days and checks existing inventory from current dates
    * @return void
    ************************************************************************************************************************* */
    public function caravanInventoryManage(){
        $current_date = date('Y-m-d');
        $caravan_details = MasterCaravan::get();
        try{
            if($caravan_details->count() > 0){
                foreach($caravan_details as $caravan){

                    $inventory_details = DB::table('caravan_master_inventory')->where('caravan_id', $caravan->id)->where('date','>=',$current_date)->pluck('date')->toArray();
                    if(count($inventory_details) > 0){
                        $this->setExistingInventory($inventory_details);
                        $this->addInventoryNext90Days($caravan);
                    }
                }
            }
            return response()->json(['status'=>true, 'message'=>'Inventory add successfully']);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'message'=>$e->getMessage()]);
        }
    }
}
