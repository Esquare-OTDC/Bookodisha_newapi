<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Traits\HdfcTraits;
use App\CaravanMasterInventory;
use App\CaravanBooking;



trait CaravanTraits
{
    use HdfcTraits;

    private $orders;
    private $message;
    private $emailBody;
    private $rentalInvoice;
    private $confirmationTemplate;
    private $route_confirm;
    private $routes;
    private $caravan_details;
    private $existingInventory;
    private $rentalConfirmMail;

    /* *********************************************************************************************************************
    * @author : Saikat Mohanty
    * @date: 23/04/2026
    * Description: This function updates the order status to cancelled for failed payments, adjusts the inventory for the caravan bookings, and ensures that the system reflects the correct availability for future bookings. It is designed to maintain data integrity and provide accurate information to customers and administrators regarding the status of their orders and the availability of caravan services.
    * @param int $order_id - The ID of the order that has failed payment and needs to be updated.
    ************************************************************************************************************************ */

    protected function updateCaravanForFailedPayment($order_id):void
    {
        DB::table('order_masters')->where('id', $order_id)->update([
            'status'=>'cancelled',
            'cancel_reason'=>'Auto cancel for non-payment',
            'payment_status'=>'failure',
            'refund_amount'=>0,
            'refund_tax'=>0,
            'cancel_date'=>now(),
        ]);

        $order_details = DB::table('order_details')->where('order_master_id', $order_id)->orderBy('start_date','ASC')->get();
        if($order_details->count() > 0){
            foreach($order_details as $details){
                $caravan_details = DB::table('caravan_bookings')->where('caravan_id', $details->service_name_id)->first();
                if($caravan_details){
                    $cal_day = $caravan_details->no_of_days;
                    for ($i = 0; $i < $cal_day; $i++) {
                        $checkDate = date("Y-m-d", strtotime($caravan_details->start_date . ' + ' . $i . ' days'));
                        DB::table('caravan_master_inventory')->where('caravan_id', $caravan_details->caravan_id)
                        ->whereDate('date', $checkDate)->update([
                            'total_available'      => DB::raw('total_available + 1'),
                            'total_booked'         => DB::raw('total_booked - 1'),
                            'total_online_pending' => DB::raw('total_online_pending - 1'),
                            'updated_at'           => now(),
                        ]);
                    }
                }

            }
        }
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the confirmation template.
    * @return void
    * *************************************************************************************************************************/
    protected function setComfirmationTemplate(){
        $this->rentalConfirmMail = DB::table('email_templates')->where('ref_code', 'caravanConfirmMail')->first();
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the confirmation template.
    * @return \Illuminate\Database\Eloquent\Model The confirmation template.
    * *************************************************************************************************************************/
    protected function getComfirmationTemplate(){
        return $this->rentalConfirmMail;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the rental details.
    * @return void
    * *************************************************************************************************************************/
    protected function setRentalDetails(){
        $this->rentalInvoice = DB::table('email_templates')->where('ref_code', 'caravanInvoice')->first();
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the rental details.
    * @return \Illuminate\Database\Eloquent\Collection The rental details.
    * *************************************************************************************************************************/
    protected function getRentalDetails(){
        return $this->rentalInvoice;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the orders.
    * @param \Illuminate\Database\Eloquent\Collection $orders The orders.
    * @return void
    * *************************************************************************************************************************/
    protected function setOrders($orders){
        $this->orders = $orders;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the orders.
    * @return \Illuminate\Database\Eloquent\Collection The orders.
    * *************************************************************************************************************************/
    protected function getOrders(){
        return $this->orders;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the message.
    * @param string $message The message.
    * @return void
    * *************************************************************************************************************************/
    protected function setMessage(string $message){
        $this->message = $message;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the message.
    * @return string The message.
    * *************************************************************************************************************************/
    protected function getMessage(){
        return $this->message;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the email body.
    * @param string $emailBody The email body.
    * @return void
    * *************************************************************************************************************************/
    protected function setEmailBody(string $emailBody){
        $this->emailBody = $emailBody;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the email body.
    * @return string The email body.
    * *************************************************************************************************************************/
    protected function getEmailBody(){
        return $this->emailBody;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the route confirmation details.
    * @param string $route_confirm The route confirmation details.
    * @return void
    * *************************************************************************************************************************/
    protected function setRouteConfirm(string $route_confirm){
        $this->route_confirm = $route_confirm;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the route confirmation details.
    * @return string The route confirmation details.
    * *************************************************************************************************************************/
    protected function getRouteConfirm(){
        return $this->route_confirm;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sets the caravan details.
    * @param object $caravan_details The caravan details.
    * @return void
    * *************************************************************************************************************************/
    protected function setCaravanDetails($caravan_details){
        $this->caravan_details = $caravan_details;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function retrieves the caravan details.
    * @return object The caravan details.
    * *************************************************************************************************************************/
    protected function getCaravanDetails(){
        return $this->caravan_details;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function sends the caravan invoice email.
    * @param object $orders The order details.
    * @param array $txn_response The transaction response.
    * @param string $payment_method The payment method.
    * @param object $caravan_details The caravan details.
    * @return void
    * *************************************************************************************************************************/
    protected function sendCaravanInvoiceEmail($orders, array $txn_response, string $payment_method, $caravan_details){

        $this->setCaravanDetails($caravan_details);

        $tspinword = $this->AmountInWords($orders->total_service_price);
        $order_details = $this->getOrderDetails($orders->id);

        $RentalInvoice = $this->getRentalDetails();
        $CaravanBooking = CaravanBooking::where('booking_id', $orders->order_id)->first();
        // $User = DB::table('users')->where('id', $orders->customer_id)->first();
        // $To = $orders->customer_email;

        //$Subject = $RentalInvoice->subject . ' - ' . $orders->service_name . ' - Invoice ID - ' . $orders->invoice_id;

        $invoice_serial = $orders->invoice_serial;

        $vendors = $this->getVendors($orders->vendor_id);

        $discount = isset($txn_response['disc']) ?$txn_response['disc'] : 0;

        $vendorRegdCompany = $caravan_details->gst_legal_name;
        $vendorGSTNo = $caravan_details->gst_number;

        $customerGSTNo = (!empty($orders->gst_regd_no)) ? '<u></b>GSTN No: '. $orders->gst_regd_no .'</b></u>' : '';
        $customerGSTCompany = (!empty($orders->gst_company_name)) ? '<u></b>Company Name: '. $orders->gst_company_name .'</b></u>' : '';

        $routes = '';
        $route_confirm = '';
        if($order_details->count() > 0){
            foreach($order_details as $key => $details){
                $routes .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $orders->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $orders->service_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($details->start_date)) . ' - <br>' . date("d M Y", strtotime($details->end_date)) . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $CaravanBooking->no_of_days . ' days</td><td align="right" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($orders->sub_total_price, 2) . '</td></tr>';

                $route_confirm .= '<tr><td width="10%" rowspan="3">'. ($key + 1) .'</td><td><strong>Pick up Location</strong>: ' . $details->pickup_address .', '. $details->pickup_city . '</td></tr><tr><td><strong>Start Date</strong>: ' . date("d M Y", strtotime($details->start_date)) .'</td><td><strong>End Date</strong>: '. date("d M Y", strtotime($details->end_date)) .'</tr>';
            }
        }

        $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~orderdetails~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~guidecharge~", "~tspinword~", "~payuid~"),
        array($this->siteUrl, $orders->customer_name, $orders->customer_phone, $orders->customer_email, $orders->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->siteUrl . $vendors->photo, date("d M Y h:i a", strtotime($orders->created_at)), $routes, number_format($orders->total_service_price, 2), $orders->coupon_name, $discount, number_format($orders->sub_total_price, 2), number_format($orders->tax_amount, 2), number_format($orders->total_order_price, 2), $payment_method, $orders->transaction_id, number_format($orders->guide_charge, 2), $tspinword, $txn_response['mihpayid']), $RentalInvoice->source);

            $this->setMessage($Message);
            $this->setOrders($orders);
            $this->setRouteConfirm($route_confirm);
            $this->emailBody();

        return $this;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function generates the email body for the confirmation email.
    * @return void
    * *************************************************************************************************************************/
    protected function emailBody(){
        $orders = $this->getOrders();
        $Message = $this->getMessage();

        $EmailBody = '<div style="display:flex;gap:10px;justify-content:space-between;"><p style="width:70%;">Dear '. $orders->customer_name .',<br><br> please <a href="'. $this->frontUrl . 'user/booking-history"><b>click here</b></a> to check booking details / cancel booking.<br>Please copy the following url and paste it in your browser if you are unable to click the link. <br><br>'. $this->frontUrl . 'user/booking-history </p></div>';
        $EmailBody .= $Message;
        $EmailBody .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

        $this->setEmailBody($EmailBody);

        return $this;
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-24
    * Description: This function generates the confirmation email for the booking.
    * @return array The email details.
    * *************************************************************************************************************************/
    protected function confirmationMail(){
        $ConfirmTemplate = $this->getComfirmationTemplate();
        $orders = $this->getOrders();
        $Vendor = $this->getVendors($orders->vendor_id);
        $CaravanDetails = $this->getCaravanDetails();
        $guide_text = '';
        $msg = '';
        $SubjConfirm = '';
        if (!empty($ConfirmTemplate)) {
            $SubjConfirm = $ConfirmTemplate->subject .' - '. $orders->service_name .' - Booking ID - '. $orders->invoice_id;
            $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~guideservice~", "~invoiceid~"),
                    array($this->siteUrl . $Vendor->photo, $orders->customer_name, $orders->service_name, $this->getRouteConfirm(), $orders->total_order_price, $orders->transaction_id, strtoupper($orders->payment_gateway), $CaravanDetails->terms_conditions, $guide_text, $orders->invoice_id), $ConfirmTemplate->source);
            // \Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
        }
        return array('emailBody'=>$msg, 'subject'=>$SubjConfirm, 'to'=>$orders->customer_email);
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-28
    * Description: This function set the existing inventory details
    * @param array $existingInventory
    * @return void
    ***************************************************************************************************************************/
    protected function setExistingInventory(array $existingInventory = []){
        $this->existingInventory = $existingInventory;
    }

    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-28
    * Description: This function get the existing inventory details which set in setExistingInventory function
    * @return array of dates in which inventory exists
    ************************************************************************************************************************* */
    protected function getExistingInventory(){
        return $this->existingInventory;
    }

    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-28
    * Description: This function get the existing inventory details which set in setExistingInventory function
    * @param date $date
    * @return array of dates in which inventory exists
    ************************************************************************************************************************* */
    protected function isInventoryExist($date){
        return in_array($date, $this->existingInventory);
    }
    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-04-28
    * Description: This function add or insert records in inventory for next 90 days and checks existing inventory from current dates
    * @param Collection $caravan
    * @return void
    ************************************************************************************************************************* */
    protected function addInventoryNext90Days($caravan){
        $count = 0;
        $inventory = [];
        $today = date("Y-m-d");
        for($i = 0; $i<= 90; $i++){
            $date = date("Y-m-d", strtotime($today . ' + ' . $i . ' days'));
            if(!$this->isInventoryExist($date)){
                $inventory[$count] = [
                    'vendor_id' => $caravan->vendor_id,
                    'caravan_id' => $caravan->id,
                    'date' => $date,
                    'initial_quantity' => $caravan->quantity,
                    'total_available' => $caravan->quantity,
                    'total_booked' => 0,
                    'total_blocked' => 0,
                    'total_online_completed' => 0,
                    'total_online_pending' => 0,
                    'total_offline_completed' => 0,
                    'total_offline_pending' => 0
                ];
                $count++;
            }
        }
        if(count($inventory) > 0){
            CaravanMasterInventory::insert($inventory);
        }
    }
}
