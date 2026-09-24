<?php
namespace App\Http\Controllers\Air;

use App\AirModels\Cancellation;
use App\AirModels\FlightBooking;
use App\AirModels\FlightMaster;
use App\AirModels\PassengerDetail;
use App\AirModels\Reschedule;
use App\AirModels\SeatInventory;
use App\CustomerRefund;
use App\EmailTemplate;
use App\Http\Controllers\Controller;
use App\OrderMaster;
use App\PropertyAccount;
use App\Traits\AirTravelTraits;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

class FlightCronController extends Controller{

    use AirTravelTraits;

    public $site = '';
    public $frontendUrl = '';
    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    /* GENERATE INVENTORY FOR 90 DAYS */
    public function generateInventory90daysCron()
    {
        try{
            $this->generateInventory90days();
            return [
                'cron_type'=>'Inventory',
                'status'=>'success',
                'message'=>'Inventory added successfully'
            ];
        }catch(\Exception $e){
            return [
                'cron_type'=>'Inventory',
                'status'=>'error',
                'message'=>$e->getMessage()
            ];
        }
    }

    /* CANCEL PAYMENTS */
    public function orderCancellation(Request $request){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '768M');
        require_once public_path('PHPMailer/send_mail.php');
        require_once public_path('paytm_lib/config_paytm.php');

        $orders = DB::table('order_masters')->where('service_type','flight')->where('status','pending')->where('order_type','online')->get();
        $paymentArray = [];
        if($orders->isNotEmpty()){
            foreach($orders as $order){
                $count = 1;
                $OrderMasterNew = (array) $order;
                $hourdiff = floor((time() - strtotime($OrderMasterNew['created_at'])) / 60);
                if ($hourdiff > 30) {
                    if (!empty($OrderMasterNew['hdfc_key']) && !empty($OrderMasterNew['hdfc_salt']) && PAYTM_ENVIRONMENT == 'PROD') {
                        $HDFC_KEY = $OrderMasterNew['hdfc_key'];
                        $HDFC_SALT = $OrderMasterNew['hdfc_salt'];
                    }

                    $key = $HDFC_KEY;
                    $salt = $HDFC_SALT;

                    $command = "verify_payment";
                    $var1 = $OrderMasterNew['transaction_id'];
                    $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                    $hash_verify_payment = strtolower(hash('sha512', $hash_str));


                    $r = array('key' => $HDFC_KEY, 'hash' => $hash_verify_payment, 'var1' => $var1, 'command' => $command);
                    $qs = http_build_query($r);
                    $wsUrl = VERIFY_URL;
                    $c = curl_init();
                    curl_setopt($c, CURLOPT_URL, $wsUrl);
                    curl_setopt($c, CURLOPT_POST, 1);
                    curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                    $o = curl_exec($c);
                    curl_close($c);

                    $valueSerialized = @unserialize($o);
                    $response = json_decode($o, 1);
                    $paymentArray[$OrderMasterNew['transaction_id']] = $response;

                    if ($response['status'] == 1 && $response['transaction_details'][$OrderMasterNew['transaction_id']]['status'] == 'success') {
                        $payment_method = '';
                        if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['bankcode']))
                            $payment_method .= $response['transaction_details'][$OrderMasterNew['transaction_id']]['bankcode'];
                        else if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['mode']))
                            $payment_method .= $response['transaction_details'][$OrderMasterNew['transaction_id']]['mode'];
                        else if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['PG_TYPE']))
                            $payment_method .= $response['transaction_details'][$OrderMasterNew['transaction_id']]['PG_TYPE'];
                        else
                            $payment_method .= 'N/A';

                        if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['field8']) && $payment_method == 'UPI')
                            $payment_method .= '(' . $response['transaction_details'][$OrderMasterNew['transaction_id']]['field8'] . ')';

                        DB::table('order_masters')
                            ->where('id', $OrderMasterNew['id'])
                            ->update([
                                'payment_method'         => $payment_method,
                                'payment_status'         => 'success',
                                'status'                 => 'completed',
                                'payment_gateway_error'  => '2',
                                'payment_error_response' => json_encode($response),
                            ]);
                        $this->updateFlightRelatedRecords($order, 'success');
                    }else{
                        if($order->payment_status == 'pending' && $response['status'] == 0){
                            DB::table('order_masters')
                            ->where('id', $OrderMasterNew['id'])
                            ->update([
                                'status'        => 'cancelled',
                                'cancel_reason' => 'Auto cancel for non-payment',
                                'refund_amount' => 0,
                                'refund_tax'    => 0,
                                'cancel_date'   => now(),
                                'payment_error_response' => json_encode($response),
                            ]);

                            $this->updateFlightRelatedRecords($order, 'failure');
                        }elseif($order->payment_status == 'success'){
                            DB::table('order_masters')
                            ->where('id', $OrderMasterNew['id'])
                            ->update([
                                'status'                 => 'completed',
                                'payment_gateway_error'  => '2',
                                'payment_error_response' => json_encode($response),
                            ]);
                            $this->updateFlightRelatedRecords($order, 'success');
                        }
                    }
                }
            }
        }
        return $paymentArray;
    }

    /* CHECK ONLINE PAYMENT STATUS */
    public function checkOnlinePaymentStatus(Request $request){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '768M');
        require_once public_path('PHPMailer/send_mail.php');
        require_once public_path('paytm_lib/config_paytm.php');
        require_once public_path('cron/send_sms.php');
        require_once public_path('QrCode/generateQrCode.php');
        #require_once public_path('s3_file_upload/s3_file_upload.php');

        $NOWDATETIME = date('Y-m-d H:i:s');

        /* $site = 'https://admin.bookodisha.com/';
        $frontUrl = 'https://www.bookodisha.com/'; */
        $site = $this->site;
        $frontUrl = $this->frontendUrl;
        $orders = OrderMaster::where('vendor_id', '!=', 58672)
            ->where('payment_gateway', 'hdfc')
            ->where('order_type', 'online')
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('service_type','flight')
            //->where('payment_gateway_error', '2')
            ->where('created_at', '<', $NOWDATETIME)
            ->orderBy('created_at', 'ASC')
            ->get();
        $orderDetails = [];
        if($orders->isNotEmpty()){
            $orders = $orders->toArray();
            foreach($orders as $order){
                $OrderMasterNew = (array) $order;
                if (!empty($OrderMasterNew['hdfc_key']) && !empty($OrderMasterNew['hdfc_salt']) && PAYTM_ENVIRONMENT == 'PROD') {
                    $HDFC_KEY = $OrderMasterNew['hdfc_key'];
                    $HDFC_SALT = $OrderMasterNew['hdfc_salt'];
                }
                $order = (object) $order;

                $key = $HDFC_KEY;
                $salt = $HDFC_SALT;

                $command = "verify_payment";
                $var1 = $OrderMasterNew['transaction_id'];
                $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                $hash_verify_payment = strtolower(hash('sha512', $hash_str));


                $r = array('key' => $HDFC_KEY, 'hash' => $hash_verify_payment, 'var1' => $var1, 'command' => $command);
                $qs = http_build_query($r);
                $wsUrl = VERIFY_URL;
                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, $wsUrl);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                $o = curl_exec($c);
                if (curl_errno($c)) {
                    $sad = curl_error($c);
                    throw new Exception($sad);
                }
                curl_close($c);
                $valueSerialized = @unserialize($o);
                $response = json_decode($o, 1);

                if ($response['status'] == 1 && $response['transaction_details'][$OrderMasterNew['transaction_id']]['status'] == 'success') {
                    $txn_response = $response['transaction_details'][$OrderMasterNew['transaction_id']];

                    $Vendor = DB::table('users')->where('id', $OrderMasterNew['vendor_id'])->first();
                    $mode = isset($txn_response['mode']) ? $txn_response['mode'] : '';

                    $unmappedstatus = isset($txn_response['unmappedstatus']) ?$txn_response['unmappedstatus'] : '';
                    $card_category = isset($txn_response['cardCategory']) ?$txn_response['cardCategory'] : '';
                    $discount = isset($txn_response['disc']) ?$txn_response['disc'] : 0;
                    $net_amount_debit = isset($txn_response['net_amount_debit']) ?$txn_response['net_amount_debit'] : '';
                    $addedon = isset($txn_response['addedon']) ?$txn_response['addedon'] : '';
                    $field1 = isset($txn_response['field1']) ?$txn_response['field1'] : '';
                    $field2 = isset($txn_response['field2']) ?$txn_response['field2'] : '';
                    $field3 = isset($txn_response['field3']) ?$txn_response['field3'] : '';
                    $field4 = isset($txn_response['field4']) ?$txn_response['field4'] : '';
                    $field5 = isset($txn_response['field5']) ?$txn_response['field5'] : '';
                    $field6 = isset($txn_response['field6']) ?$txn_response['field6'] : '';
                    $field7 = isset($txn_response['field7']) ?$txn_response['field7'] : '';
                    $field8 = isset($txn_response['field8']) ?$txn_response['field8'] : '';
                    $field9 = isset($txn_response['field9']) ?$txn_response['field9'] : '';
                    $payment_source = isset($txn_response['payment_source']) ?$txn_response['payment_source'] : '';
                    $PG_TYPE = isset($txn_response['PG_TYPE']) ?$txn_response['PG_TYPE'] : '';
                    $bank_ref_num = isset($txn_response['bank_ref_num']) ?$txn_response['bank_ref_num'] : '';
                    $bankcode = isset($txn_response['bankcode']) ?$txn_response['bankcode'] : '';
                    $error_code = isset($txn_response['error_code']) ?$txn_response['error_code'] : '';
                    $error_Message = isset($txn_response['error_Message']) ?$txn_response['error_Message'] : '';
                    $name_on_card = isset($txn_response['name_on_card']) ?$txn_response['name_on_card'] : '';
                    $card_no = isset($txn_response['card_no']) ?$txn_response['card_no'] : '';
                    $cardhash = isset($txn_response['cardhash']) ?$txn_response['cardhash'] : '';

                    // UPDATE PAYMENT HISTORY
                    DB::table('payment_history')
                    ->where('id', $OrderMasterNew['payment_id'])
                    ->update([
                        'mihpayid'         => $txn_response['mihpayid'] ?? null,
                        'mode'             => $mode ?? null,
                        'status'           => $txn_response['status'] ?? null,
                        'unmapped_status'  => $unmappedstatus ?? null,
                        'card_category'    => $card_category ?? null,
                        'discount'         => $discount ?? null,
                        'net_amount_debit' => $net_amount_debit ?? null,
                        'added_on'         => $addedon ?? null,
                        'field1'           => $field1 ?? null,
                        'field2'           => $field2 ?? null,
                        'field3'           => $field3 ?? null,
                        'field4'           => $field4 ?? null,
                        'field5'           => $field5 ?? null,
                        'field6'           => $field6 ?? null,
                        'field7'           => $field7 ?? null,
                        'field8'           => $field8 ?? null,
                        'field9'           => $field9 ?? null,
                        'payment_source'   => $payment_source ?? null,
                        'PG_TYPE'          => $PG_TYPE ?? null,
                        'bank_ref_num'     => $bank_ref_num ?? null,
                        'bank_code'        => $bankcode ?? null,
                        'error'            => $error_code ?? null,
                        'error_Message'    => $error_Message ?? null,
                        'name_on_card'     => $name_on_card ?? null,
                        'card_number'      => $card_no ?? null,
                        'cardhash'         => $cardhash ?? null,
                        'payment_response' => json_encode($response),
                        'updated_at'       => now(),
                    ]);

                    $Message = $manager_contact = $reception_contact = '';
                    $MasterHotel = $CarDetails = $TourData = $TicketData = array();

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
                        $payment_method .= '('. $request->field8 .')';
                    }

                    // Generate Qr Code
                    $QrCodeData = array(
                        'invoiceId' => $OrderMasterNew['invoice_id'],
                        'orderId' => $OrderMasterNew['order_id'],
                        'txnId' => $OrderMasterNew['transaction_id'],
                        'serviceType' => $OrderMasterNew['service_type'],
                    );
                    $QrCode = generateQrCode(json_encode($QrCodeData));
                    $qr_base64 = $site . $QrCode;

                    $tspinword = parent::AmountInWords($OrderMasterNew['total_service_price']);
                    $confirm_voucher = '';$invoice_serial = '';

                    DB::table('order_details')->where('order_master_id', $OrderMasterNew['id'])->update([
                        'status'=>'completed',
                        'updated_at'=>date("Y-m-d H:i:s")
                    ]);


                    DB::table('order_masters')
                    ->where('id', $OrderMasterNew['id'])
                    ->update([
                        'status'              => 'completed',
                        'payment_status'      => $txn_response['status'] ?? 'success',
                        'payment_method'      => $payment_method,
                        'invoice'             => $Message,
                        'confimation_voucher' => $confirm_voucher,
                        'qr_code'             => $QrCode,
                        'qr_base64'           => $qr_base64,
                        'qr_verified'         => 0,
                        'updated_at'          => now(),
                    ]);

                    $orderDetails[$OrderMasterNew['id']] = $OrderMasterNew['order_id'];

                    $sms_txt = $sms_txt_admin = $user_templete_id = '';
                    $SmsTemplate = DB::table('sms_templates')
                                    ->where('ref_code', 'BookingConfirmUser')
                                    ->first();

                    $User = DB::table('users')->where('id', $OrderMasterNew['customer_id'])->first();
                    $To = $OrderMasterNew['customer_email'];

                    $RentalInvoice = DB::table('email_templates')->where('ref_code','flightInvoice')->first();
                    if(!empty($RentalInvoice)){
                        $OrderMaster = (object) $OrderMasterNew;
                        $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;

                        $flightBookingDetails = FlightBooking::with('scheduleOnward','scheduleReturn')->where('booking_id',$OrderMaster->order_id)->first();
                        $departureSchedule = $flightBookingDetails->scheduleOnward;
                        $flightDetails = FlightMaster::where('id', $departureSchedule->flight_id)->first();
                        $vendorGSTNo = '';
                        $vendorRegdCompany = '';
                        $guide_text = 'N/A';
                        $routes = '';$routes_agent = ''; $route_confirm = '';$count = 1;
                        $orderdetailsHtml = '';
                        $departureHtml = $returnHtml = '';

                        $check_date = date("d M Y", strtotime($flightBookingDetails->onward_flight_date)) .' '. date("h:i a", strtotime($departureSchedule->departure_time)) .' - <br>' . date("d M Y", strtotime($flightBookingDetails->onward_flight_date)) .' '. date("h:i a", strtotime($departureSchedule->arrival_time));
                        $passengerDetails = PassengerDetail::where('booking_id', $OrderMaster->order_id)->where('order_status','!=',2)->get();
                        $departureSourceFrom = $this->getSourceDetails($departureSchedule->source_airport_id);
                        $departureSourceTo = $this->getSourceDetails($departureSchedule->destination_airport_id);
                        $departure_flight = $departureSourceFrom->city_name.' --- '.$departureSourceTo->city_name;
                        $return_flight = '';
                        $departure_date = $flightBookingDetails->onward_flight_date;
                        $outbound_time = date('d-m-Y', strtotime($departure_date)).' '.date('h:i A', strtotime($departureSchedule->departure_time));
                        $arrival_time = date('d-m-Y', strtotime($departure_date)).' '.date('h:i A', strtotime($departureSchedule->arrival_time));
                        $adultPrice = $this->getFarePrice($departureSchedule->flight_id, $departureSchedule->id, 'ADULT');
                        $childPrice = $this->getFarePrice($departureSchedule->flight_id, $departureSchedule->id, 'INFANT');

                        $return_time = '';
                        if($flightBookingDetails->booking_type == 'roundtrip'){
                            $returnFlight = $flightBookingDetails->scheduleReturn;
                            if(!empty($returnFlight->return_flight_date)){
                                $departureSourceFrom = $this->getSourceDetails($returnFlight->source_airport_id);
                                $departureSourceTo = $this->getSourceDetails($returnFlight->destination_airport_id);
                                $return_flight = $departureSourceFrom->city_name.' --- '.$departureSourceTo->city_name;
                            }
                        }

                        $passengerName = '<table border="1" cellspacing="0"><thead><tr><th>Passenger Name</th><th>Passenger Type</th><th>Journey Type</th></tr></thead><tbody>';

                        foreach($passengerDetails as $passenger){
                                            /* Departure */
                            if($passenger->journey_type == 'DEPARTURE'){
                                $price = $passenger->passenger_type == 'ADULT' ? $adultPrice : $childPrice;
                                $departureHtml .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$OrderMaster->invoice_id.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$passenger->first_name.' '.$passenger->last_name.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$passenger->passenger_type.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$departure_flight.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$passenger->journey_type.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$outbound_time.' - '.$arrival_time.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$price.'</td>';
                            }

                            if($flightBookingDetails->booking_type == 'roundtrip' && $passenger->journey_type == 'RETURN'){
                                $returnFlight = $flightBookingDetails->scheduleReturn;
                                $return_date = $flightBookingDetails->return_flight_date;
                                $return_time = date('d-m-Y', strtotime($return_date)).' '.date('h:i A', strtotime($returnFlight->departure_time));
                                $arrival_time = date('d-m-Y', strtotime($return_date)).' '.date('h:i A', strtotime($returnFlight->arrival_time));
                                $price = $passenger->passenger_type == 'ADULT' ? $adultPrice : $childPrice;
                                $returnHtml .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$OrderMaster->invoice_id.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$passenger->first_name.' '.$passenger->last_name.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$passenger->passenger_type.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$return_flight.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$passenger->journey_type.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$return_time.' - '.$arrival_time.'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'.$price.'</td>';
                            }

                            $passengerName .= '<tr><td>'.$passenger->first_name.' '.$passenger->last_name.'</td><td>'.$passenger->passenger_type.'</td><td>'.$passenger->journey_type.'</td></tr>';

                            PassengerDetail::where('booking_id', $passenger->id)->update([
                                'order_status'=>1
                            ]);

                            if($flightBookingDetails->booking_status == 'RESCHEDULE'){
                                Reschedule::where('new_booking_id', $flightBookingDetails->booking_id)->where('passenger_id', $passenger->id)->update([
                                    'status'=>'COMPLETED'
                                ]);
                            }
                        }
                        FlightBooking::where('id', $flightBookingDetails->id)
                        ->update([
                            'payment_status' => 'CONFIRM',
                            'booking_status' => DB::raw("
                                CASE
                                    WHEN booking_status = 'RESCHEDULE' THEN booking_status
                                    ELSE 'CONFIRM'
                                END
                            "),
                        ]);

                        $passengerName .= '</tbody></table>';
                        $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~orderdetails~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~guidecharge~", "~payuid~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $orderdetailsHtml, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, 0, number_format($OrderMaster->sub_total_price, 2), 0, number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, number_format($OrderMaster->guide_charge, 2), $txn_response['mihpayid']), $RentalInvoice->source);

                        $service_mail = $flightDetails->contact_email;
                        if (!empty($flightDetails->additional_email)) {
                            $service_mail = !empty($service_mail) ? $service_mail .','. $flightDetails->additional_email : $flightDetails->additional_email;
                        }

                        $OrderMaster->invoice = $Message;

                        $admin = User::where('role', 1)->first();
                        $receipent = array_merge($Vendor_mail, array($admin->email));
                        if (!empty($service_mail)) {
                            $receipent = array_merge($receipent, explode(',', $service_mail));
                        }
                        $tspinword = parent::AmountInWords($OrderMaster->total_service_price);
                        $Message = str_replace('~tspinword~', $tspinword, $Message);
                        $frontUrl = str_replace('/tourism/', '/', $this->frontendUrl);
                        $EmailBody = '<div style="display:flex;gap:10px;justify-content:space-between;"><p style="width:70%;">Dear '. $OrderMaster->customer_name .',<br><br> please <a href="'. $frontUrl . 'user/booking-history"><b>click here</b></a> to check booking details / cancel booking.<br>Please copy the following url and paste it in your browser if you are unable to click the link. <br><br>'. $frontUrl . 'user/booking-history </p></div>';
                        $EmailBody .= $Message;
                        $EmailBody .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

                        try {
                            Mail::to($To)
                                ->bcc($receipent)
                                ->send(new \App\Mail\RegistrationMailUser($EmailBody, $Subject));
                        }
                        catch(\Exception $e) {}

                        $ConfirmTemplate = EmailTemplate::where('ref_code','flightConfirmMail')->first();

                        if (!empty($ConfirmTemplate)) {
                            $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                            $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~guideservice~", "~invoiceid~"),
                                    array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, $passengerName, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $flightDetails->terms_conditions, $guide_text, $OrderMaster->invoice_id), $ConfirmTemplate->source);
                            $OrderMaster->confimation_voucher = $msg;
                            $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                            try {
                                Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                            }
                            catch(\Exception $e) {}
                        }
                    }

                    if(!empty($SmsTemplate)){
                        $user_templete_id = $SmsTemplate->templete_id;
                        $var1 = $OrderMasterNew['customer_name'];
                        $var2 = $OrderMasterNew['invoice_id'];
                        $var4 = preg_replace('/[^A-Za-z0-9\-\_]/', ' ', $OrderMasterNew['service_name']);
                        $var5 = $OrderMasterNew['invoice_id'];
                        $var6 = "\n". $OrderMasterNew['vendor_name'];
                        $var8 = "\n\n";
                        $var3 = $var5 = $var7 = '';

                        $var5 = ($OrderMasterNew['service_name_id']) ?  date("d M Y", strtotime($OrderMasterNew['start_date'])) .'('. $OrderMasterNew['start_time'] .'-'. $OrderMasterNew['end_time'] .')' : date("d M Y", strtotime($OrderMasterNew['start_date']));
                        $var7 = $TicketData['contact_number'];

                        $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~", "~var8~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8), $SmsTemplate->source);
                        sendSms($OrderMasterNew['customer_phone'], $sms_txt, $user_templete_id);
                    }
                }
            }
        }else{
           $orders = OrderMaster::where('vendor_id', '!=', 58672)
                                ->where('payment_gateway', 'hdfc')
                                ->where('order_type', 'online')
                                ->where('status', 'pending')
                                ->where('payment_status','!=', 'pending')
                                ->where('service_type','flight')
                                //->where('payment_gateway_error', '2')
                                ->where('created_at', '<', $NOWDATETIME)
                                ->orderBy('created_at', 'ASC')
                                ->get();
            $orderDetails = $this->privateOnesidePayment($orders);
        }
        return [
            'status'=>'success',
            'data'=>$orderDetails
        ];
    }

    private function privateOnesidePayment($orders){
        $orderDetails = [];
        if($orders->isNotEmpty()){
            foreach ($orders as $key => $value) {
                if($value->payment_status == 'success'){
                    OrderMaster::where('id',$value->id)->update([
                        'status'=>'completed',
                        'payment_gateway_error'=>2
                    ]);
                    $orderDetails[$value->id] = $value->order_id;
                }
                if($value->payment_status == 'failure'){
                    $orderDetails[$value->id] = $value->order_id;
                    if($value->price_type == 'reschedule'){
                        $order = $value;
                        $reschedules = Reschedule::where('new_booking_id', $order->order_id)->get();
                        $departure_adult_count = 0;
                        $return_adult_count = 0;
                        $old_booking_id = '';
                        foreach($reschedules as $reschedule){
                            $oldPassengerDetails = DB::table('passengers_audit')->where('booking_id', $reschedule->old_booking_id)->where('passenger_id',$reschedule->passenger_id)->first();
                            $old_booking_id = $reschedule->old_booking_id;
                            $passDetails = PassengerDetail::where('booking_id', $order->order_id)->where('id', $reschedule->passenger_id)->first();

                            $passDetails->order_status = 1;
                            $passDetails->schedule_id = $oldPassengerDetails->schedule_id;
                            $passDetails->booking_id = $reschedule->old_booking_id;

                            $passenger_type = $passDetails->passenger_type;
                            $journey_type = $passDetails->journey_type;

                            if($passDetails->save()){
                                if($passenger_type == 'ADULT' && $journey_type == 'DEPARTURE'){
                                    $departure_adult_count += 1;
                                }
                                if($passenger_type == 'ADULT' && $journey_type == 'RETURN'){
                                    $return_adult_count += 1;
                                }
                            }
                            $passDetails->save();

                        }
                        $oldFlightBooking = FlightBooking::where('booking_id', $old_booking_id)->first();
                        Reschedule::where('new_booking_id', $order->order_id)->update([
                            'status'=>'CANCEL'
                        ]);
                        $oldFlightBooking->is_reschedule = null;
                        $oldFlightBooking->reschedule_id = null;
                        if($departure_adult_count > 0){
                            /*$departure_seat = SeatInventory::where('date', $flightBooking->onward_flight_date)->where('schedule_id', $flightBooking->onward_schedule_id)->first();
                            $departure_seat->online_booked -= $departure_adult_count;
                            $departure_seat->save();*/

                            /* $departure_old_seat = SeatInventory::where('date', $oldFlightBooking->onward_flight_date)->where('schedule_id', $oldFlightBooking->onward_schedule_id)->first(); */
                            $statSp = $this->updateSeatBySp($oldFlightBooking->onward_schedule_id,$oldFlightBooking->onward_flight_date, $departure_adult_count);
                            if($statSp['status'] != 'SUCCESS'){
                               return [
                                    'status'=>'error',
                                    'message'=>$statSp['message'],
                                    'data'=>[
                                        'oldFlightBooking'=>$oldFlightBooking,
                                        'departure_adult_count'=>$departure_adult_count,
                                        'old_booking_id'=>$old_booking_id,
                                        'reschedules'=>'STATUS CHANGED'
                                    ]
                                ];
                            }
                            /* $departure_old_seat->online_booked += $departure_adult_count;
                            $departure_old_seat->save(); */
                        }
                        if($return_adult_count > 0){
                            /*$return_seat = SeatInventory::where('date', $flightBooking->return_flight_date)->where('schedule_id', $flightBooking->return_schedule_id)->first();
                            $return_seat->online_booked -= $return_adult_count;
                            $return_seat->save();*/

                            /* $return_old_seat = SeatInventory::where('date', $oldFlightBooking->return_flight_date)->where('schedule_id', $oldFlightBooking->return_schedule_id)->first();
                            $return_old_seat->online_booked += $return_adult_count;
                            $return_old_seat->save(); */

                            $statSp = $this->updateSeatBySp($oldFlightBooking->return_schedule_id,$oldFlightBooking->return_flight_date, $return_adult_count);
                            if($statSp['status'] != 'SUCCESS'){
                               return [
                                    'status'=>'error',
                                    'message'=>$statSp['message'],
                                    'data'=>[
                                        'oldFlightBooking'=>$oldFlightBooking,
                                        'return_adult_count'=>$return_adult_count,
                                        'old_booking_id'=>$old_booking_id,
                                        'reschedules'=>'STATUS CHANGED'
                                    ]
                                ];
                            }
                        }
                        $oldFlightBooking->save();
                        OrderMaster::where('id',$value->id)->update([
                            'status'=>'cancelled',
                            'payment_gateway_error'=>2
                        ]);
                    }else{
                        OrderMaster::where('id',$value->id)->update([
                            'status'=>'cancelled',
                            'payment_gateway_error'=>2
                        ]);
                    }
                }
            }
        }
        return $orderDetails;
    }

    /* LATE ORDER PENDING CANCEL */
    public function latePendingOrderCancle(Request $request){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '768M');
        require_once public_path('PHPMailer/send_mail.php');
        require_once public_path('paytm_lib/config_paytm.php');

        $orderId = $request->order_id;
        $OrderMaster = OrderMaster::where('payment_late_captured', 0)
                        ->where('status', 'cancelled')
                        ->whereIn('payment_status', ['pending', 'failure'])
                        ->where('payment_gateway', 'hdfc')
                        ->when(!empty($orderId), function ($query) use ($orderId) {
                            $query->where('id', $orderId);
                        }, function ($query) {
                            $query->where('created_at', '>=', now()->subDays(4));
                        })
                        ->orderBy('created_at', 'ASC')
                        ->get();

        if($OrderMaster->isNotEmpty()){
            $count = 1;
            foreach($OrderMaster as $order){
                $OrderMasterNew = (array)$order;
                $hourdiff = floor((time() - strtotime($OrderMasterNew['created_at'])) / 60);

                if (!empty($OrderMasterNew['hdfc_key']) && !empty($OrderMasterNew['hdfc_salt']) && PAYTM_ENVIRONMENT == 'PROD') {
                    $HDFC_KEY = $OrderMasterNew['hdfc_key'];
                    $HDFC_SALT = $OrderMasterNew['hdfc_salt'];
                }
                $key = $HDFC_KEY;
                $salt = $HDFC_SALT;

                $command = "verify_payment";
                $var1 = $OrderMasterNew['transaction_id'];
                $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                $hash_verify_payment = strtolower(hash('sha512', $hash_str));

                $r = array('key' => $HDFC_KEY, 'hash' => $hash_verify_payment, 'var1' => $var1, 'command' => $command);
                $qs = http_build_query($r);
                $wsUrl = VERIFY_URL;
                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, $wsUrl);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                $o = curl_exec($c);
                curl_close($c);
                $valueSerialized = @unserialize($o);
                $response = json_decode($o, 1);

                 if ($response['status'] == 1 && $response['transaction_details'][$OrderMasterNew['transaction_id']]['status'] == 'success') {

                    $payment_method = '';
                    if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['bankcode']))
                        $payment_method .= $response['transaction_details'][$OrderMasterNew['transaction_id']]['bankcode'];
                    else if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['mode']))
                        $payment_method .= $response['transaction_details'][$OrderMasterNew['transaction_id']]['mode'];
                    else if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['PG_TYPE']))
                        $payment_method .= $response['transaction_details'][$OrderMasterNew['transaction_id']]['PG_TYPE'];
                    else
                        $payment_method .= 'N/A';

                    if (!empty($response['transaction_details'][$OrderMasterNew['transaction_id']]['field8']) && $payment_method == 'UPI')
                        $payment_method .= '(' . $response['transaction_details'][$OrderMasterNew['transaction_id']]['field8'] . ')';


                    DB::table('order_masters')
                    ->where('id', $OrderMasterNew['id'])
                    ->update([
                        'payment_method'         => $payment_method,
                        'payment_late_captured'  => 1,
                        'status'                 =>'completed',
                        'payment_status'         =>'success',
                        'payment_error_response' => json_encode($response),
                    ]);

                    $this->updateFlightRelatedRecords($order, 'success');
                }
            }
        }
    }

    /* REFUND PAYMENT */
    public function refundOrCancellation(Request $request){
        require_once public_path('paytm_lib/config_paytm.php');
        $refunds = CustomerRefund::where('service_type','flight')->whereIn('refund_status', ['PENDING','queued','requested','processing'])->get();
        $paymentLog = [];
        if($refunds->isNotEmpty()){
            foreach($refunds as $cancel){
                $orderMaster = OrderMaster::where('id', $cancel->order_id)->first();
                $AccountData = PropertyAccount::where('service_type', 'LIKE', 'flight')->where('service_id', $cancel->service_id)->first();
                if (!empty($AccountData)) {
                    $HDFC_KEY = $AccountData->hdfc_key;
                    $HDFC_SALT = $AccountData->hdfc_salt;
                }
                $key = $HDFC_KEY;
                $salt = $HDFC_SALT;

                $command = "check_action_status";
                $var1 = $cancel->reference_id;

                $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
                $hash = strtolower(hash('sha512', $hash_str));

                $r = array('key' => $key, 'hash' => $hash, 'command' => $command, 'var1' => $var1);
                $qs = http_build_query($r);
                $wsUrl = VERIFY_URL;

                $c = curl_init();
                curl_setopt($c, CURLOPT_URL, $wsUrl);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                $o = curl_exec($c);

                curl_close($c);

                $valueSerialized = @unserialize($o);
                $response = json_decode($o, 1);

                $result_status = (isset($response['transaction_details'][$var1][$var1]['status'])) ? $response['transaction_details'][$var1][$var1]['status'] : '';
                $bank_reference_num = (isset($response['transaction_details'][$var1][$var1]['bank_ref_num'])) ? $response['transaction_details'][$var1][$var1]['bank_ref_num'] : '';
                $settlement_id = (isset($response['transaction_details'][$var1][$var1]['settlement_id'])) ? $response['transaction_details'][$var1][$var1]['settlement_id'] : '';

                DB::table('refund_status_history')->insert([
                    'vendor_id'      => $cancel->vendor_id,
                    'refund_id'      => $cancel->id,
                    'payment_method' => $cancel->payment_method,
                    'reference_id'   => $cancel->reference_id,
                    'result_status'  => $result_status,
                    'response_data'  => json_encode($response),
                ]);

                $paymentLog[] = $response;

                if(isset($response['status']) && $response['status'] == 1){
                    DB::table('customer_refunds')
                    ->where('id', $cancel->id)
                    ->update([
                        'refund_status'      => $result_status,
                        'bank_reference_num' => $bank_reference_num,
                        'refund_txn_id'      => $settlement_id,
                        'updated_at'         => now(),
                    ]);

                    Cancellation::where('refund_id', $cancel->id)->update([
                        'refund_status'=>'SETTLED'
                    ]);
                }
            }
        }
        return $paymentLog;
    }

    /* UPDATE SEAT INVENTORY, BOOKING TABLE, RESCHEDULE & PASSENGER TABLE */
    private function updateFlightRelatedRecords(OrderMaster $order, $payment_status = ''){
        $response = [];
        $flightBooking = FlightBooking::where('booking_id', $order->order_id)->with('scheduleOnward','scheduleReturn')->first();
        if($payment_status == 'success'){
            PassengerDetail::where('booking_id', $order->order_id)->where('order_status','!=',2)->update([
                'order_status'=>1
            ]);
            if($flightBooking->booking_status == 'RESCHEDULE'){
                $flightBooking->payment_status = 'CONFIRM';
                Reschedule::where('new_booking_id', $order->order_id)->update([
                    'status'=>'COMPLETED'
                ]);
            }else{
                $flightBooking->booking_status = 'CONFIRM';
                $flightBooking->payment_status = 'CONFIRM';
            }
            $flightBooking->save();
        }elseif($payment_status == 'failure'){

            $flightBooking->payment_status = 'CANCEL';
            if($flightBooking->booking_status == 'RESCHEDULE'){
                $reschedules = Reschedule::where('new_booking_id', $order->order_id)->get();
                $departure_adult_count = 0;
                $return_adult_count = 0;
                $old_booking_id = '';
                foreach($reschedules as $reschedule){
                    $oldPassengerDetails = DB::table('passengers_audit')->where('booking_id', $reschedule->old_booking_id)->where('passenger_id',$reschedule->passenger_id)->first();
                    $old_booking_id = $reschedule->old_booking_id;
                    $passDetails = PassengerDetail::where('booking_id', $order->order_id)->where('id', $reschedule->passenger_id)->first();

                    $passDetails->order_status = 1;
                    $passDetails->schedule_id = $oldPassengerDetails->schedule_id;
                    $passDetails->booking_id = $reschedule->old_booking_id;

                    $passenger_type = $passDetails->passenger_type;
                    $journey_type = $passDetails->journey_type;

                    if($passDetails->save()){
                        if($passenger_type == 'ADULT' && $journey_type == 'DEPARTURE'){
                            $departure_adult_count += 1;
                        }
                        if($passenger_type == 'ADULT' && $journey_type == 'RETURN'){
                            $return_adult_count += 1;
                        }
                    }
                    $passDetails->save();

                }
                $oldFlightBooking = FlightBooking::where('booking_id', $old_booking_id)->first();
                Reschedule::where('new_booking_id', $order->order_id)->update([
                    'status'=>'CANCEL'
                ]);
                $oldFlightBooking->is_reschedule = null;
                $oldFlightBooking->reschedule_id = null;
                if($departure_adult_count > 0){
                    /*$departure_seat = SeatInventory::where('date', $flightBooking->onward_flight_date)->where('schedule_id', $flightBooking->onward_schedule_id)->first();
                    $departure_seat->online_booked -= $departure_adult_count;
                    $departure_seat->save();*/

                    /* $departure_old_seat = SeatInventory::where('date', $oldFlightBooking->onward_flight_date)->where('schedule_id', $oldFlightBooking->onward_schedule_id)->first();
                    $departure_old_seat->online_booked += $departure_adult_count;
                    $departure_old_seat->save(); */

                    $statSp = $this->updateSeatBySp($oldFlightBooking->onward_schedule_id,$oldFlightBooking->onward_flight_date, $departure_adult_count);
                    if($statSp['status'] != 'SUCCESS'){
                        return [
                            'status'=>'error',
                            'message'=>$statSp['message'],
                            'data'=>[
                                'oldFlightBooking'=>$oldFlightBooking,
                                'departure_adult_count'=>$departure_adult_count,
                                'old_booking_id'=>$old_booking_id,
                                'reschedules'=>'STATUS CHANGED'
                            ]
                        ];
                    }

                }
                if($return_adult_count > 0){
                    /*$return_seat = SeatInventory::where('date', $flightBooking->return_flight_date)->where('schedule_id', $flightBooking->return_schedule_id)->first();
                    $return_seat->online_booked -= $return_adult_count;
                    $return_seat->save();*/

                    // $return_old_seat = SeatInventory::where('date', $oldFlightBooking->return_flight_date)->where('schedule_id', $oldFlightBooking->return_schedule_id)->first();
                    // $return_old_seat->online_booked += $return_adult_count;
                    // $return_old_seat->save();

                    $statSp = $this->updateSeatBySp($oldFlightBooking->return_schedule_id,$oldFlightBooking->return_flight_date, $return_adult_count);
                    if($statSp['status'] != 'SUCCESS'){
                        return [
                            'status'=>'error',
                            'message'=>$statSp['message'],
                            'data'=>[
                                'oldFlightBooking'=>$oldFlightBooking,
                                'departure_adult_count'=>$return_adult_count,
                                'old_booking_id'=>$old_booking_id,
                                'reschedules'=>'STATUS CHANGED'
                            ]
                        ];
                    }
                }
                $oldFlightBooking->save();
            }else{
                $flightBooking->booking_status = 'CANCEL';
                $passengers = PassengerDetail::where('booking_id', $order->order_id)->where('order_status','!=',2)->get();
                if($passengers->isNotEmpty()){
                    $departure_adult_count = 0;
                    $return_adult_count = 0;
                    foreach($passengers as $passenger){
                        $passenger_type = $passenger->passenger_type;
                        $journey_type = $passenger->journey_type;
                        if($passenger->save()){
                            if($passenger_type == 'ADULT' && $journey_type == 'DEPARTURE'){
                                $departure_adult_count += 1;
                            }
                            if($passenger_type == 'ADULT' && $journey_type == 'RETURN'){
                                $return_adult_count += 1;
                            }
                        }
                    }
                    if($departure_adult_count > 0){
                        /* $departure_seat = SeatInventory::where('date', $flightBooking->onward_flight_date)->where('schedule_id', $flightBooking->onward_schedule_id)->first();
                        $departure_seat->online_booked -= $departure_adult_count;
                        $departure_seat->save(); */

                        $statSp = $this->updateSeatBySp($flightBooking->onward_schedule_id,$flightBooking->onward_flight_date, $departure_adult_count);
                        if($statSp['status'] != 'SUCCESS'){
                            return [
                                'status'=>'error',
                                'message'=>$statSp['message'],
                                'data'=>[
                                    'flightBooking'=>$flightBooking,
                                    'departure_adult_count'=>$departure_adult_count,
                                    'booking_id'=>$flightBooking->booking_id,
                                ]
                            ];
                        }
                    }

                    if($return_adult_count > 0){
                        /* $return_seat = SeatInventory::where('date', $flightBooking->return_flight_date)->where('schedule_id', $flightBooking->return_schedule_id)->first();
                        $return_seat->online_booked -= $return_adult_count;
                        $return_seat->save(); */

                        $statSp = $this->updateSeatBySp($flightBooking->return_schedule_id,$flightBooking->return_flight_date, $return_adult_count);
                        if($statSp['status'] != 'SUCCESS'){
                            return [
                                'status'=>'error',
                                'message'=>$statSp['message'],
                                'data'=>[
                                    'flightBooking'=>$flightBooking,
                                    'return_adult_count'=>$return_adult_count,
                                    'booking_id'=>$flightBooking->booking_id
                                ]
                            ];
                        }
                    }

                }

            }
            $flightBooking->save();
        }
        return $response;
    }

}
