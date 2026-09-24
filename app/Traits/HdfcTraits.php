<?php
namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;



trait HdfcTraits
{
    private $key = '';
    private $salt = '';
    private $merchantId = '';
    private $command = 'verify_payment';

    protected $env = 'PROD';

    protected $siteUrl = 'https://admin.bookodisha.com/';

    protected $frontUrl = 'https://www.bookodisha.com/';

    private $verifyUrl = '';


    protected function currentDateTimeHdfc(){
        return date('Y-m-d H:i:s');
    }

    protected $orderId_condition = '';
    protected $orderVendorId_condition = '';
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function sets the order ID condition.
    * @param int $orderId The ID of the order.
    * @return void
    * *************************************************************************************************************************/
    public function setOrderIdCondition($orderId){
        $this->orderId_condition = " AND id = $orderId";
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function sets the order vendor ID condition.
    * @param int $orderVendorId The ID of the order vendor.
    * @return void
    * *************************************************************************************************************************/
    public function setOrderVendorIdCondition($orderVendorId){
        $this->orderVendorId_condition = " AND vendor_id = $orderVendorId";
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves pending orders for the HDFC payment gateway.
    * @return \Illuminate\Database\Query\Builder
    * ************************************************************************************************************************/
    public function getPendingOrder(){
        $query = DB::table('order_masters')
            ->where('payment_gateway', 'hdfc')
            ->where('order_type', 'online')
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('payment_gateway_error', '2')
            ->whereNotNull('transaction_id')
            ->where('created_at', '<', date('Y-m-d H:i:s'));

        // Apply optional conditions
        if (!empty($this->orderId_condition)) {
            $query->whereRaw($this->orderId_condition);
        }

        if (!empty($orderVendorId_condition)) {
            $query->whereRaw($this->orderVendorId_condition);
        }

        return $query;
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function checks the status of a payment based on its transaction ID.
    * @param string $transaction_id The transaction ID to check.
    * @return array The response from the payment gateway.
    * *************************************************************************************************************************/
    public function checkPaymentStatus($transaction_id){
        $hash_str = $this->key . '|' . $this->command . '|' . $transaction_id . '|' . $this->salt;
        $hash_verify_payment = strtolower(hash('sha512', $hash_str));
        $r = array('key' => $this->key, 'hash' => $hash_verify_payment, 'var1' => $transaction_id, 'command' => $this->command);
        $qs = http_build_query($r);
        $wsUrl = $this->getVerifyUrl();
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
        return $response;
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function sets the configuration for the HDFC payment gateway.
    * @param string $key The API key for the HDFC payment gateway.
    * @param string $salt The salt for the HDFC payment gateway.
    * @param string $merchantId The merchant ID for the HDFC payment gateway.
    * @return void
    * *************************************************************************************************************************/
    public function setHdfcConfig(string $key, string $salt, string $merchantId = ''){
        $this->key = $key;
        $this->salt = $salt;
        if($merchantId){
            $this->merchantId = $merchantId;
        }
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function sets the application environment.
    * @param string $env The application environment.
    * @return void
    * *************************************************************************************************************************/
    public function setAppEnv(string $env){
        $this->env = $env;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the application environment.
    * @return string The application environment.
    * *************************************************************************************************************************/
    public function getAppEnv(){
        return $this->env;
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the production keys for the HDFC payment gateway.
    * @return array The production keys.
    * *************************************************************************************************************************/
    private function getProdKeys(){
        return [
            'PAYTM_STATUS_QUERY_NEW_URL' => 'https://securegw.paytm.in/merchant-status/getTxnStatus',
            'PAYTM_TXN_URL' => 'https://securegw.paytm.in/theia/processTransaction',
            'PAYTM_MERCHANT_KEY'=> 'm7KcfU0OHYgHw#4t',
            'PAYTM_MERCHANT_MID' => 'Orissa18381935405211',
            'PAYTM_MERCHANT_WEBSITE' => 'WEBPROD',
            'INDUSTRY_TYPE_ID' => 'GovtUtility',
            'CHANNEL_ID' => 'WEB',
            'HDFC_KEY'=>'HgenZq',
            'HDFC_SALT'=>'qhlCOFft0sEwp8HbWFO9KBtkRgiIsJv2',
            'MERCHANT_ID'=>'8530556',
            'PAYU_URL'=>'https://secure.payu.in/_payment',
            'VERIFY_URL'=>'https://info.payu.in/merchant/postservice.php?form=2',
            'PAYU_SPLIT_AUTH'=>'',
            'PAYTM_REFUND_URL'=>''
        ];
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the test keys for the HDFC payment gateway.
    * @return array The test keys.
    * *************************************************************************************************************************/
    private function getTestKeys(){
        return [
            'PAYTM_STATUS_QUERY_NEW_URL' => 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus',
            'PAYTM_TXN_URL' => 'https://securegw-stage.paytm.in/theia/processTransaction',
            'PAYTM_MERCHANT_KEY'=> 'kzKLjMlSEOnuqymg',
            'PAYTM_MERCHANT_MID' => 'Orissa00510655112390',
            'PAYTM_MERCHANT_WEBSITE' => 'WEBSTAGING',
            'INDUSTRY_TYPE_ID' => 'Retail',
            'CHANNEL_ID' => 'WEB',
            'HDFC_KEY'=>'7rnFly',
            'HDFC_SALT'=>'pjVQAWpA',
            'MERCHANT_ID'=>'5960507',
            'PAYU_URL'=>'https://test.payu.in/_payment',
            'VERIFY_URL'=>'https://test.payu.in/merchant/postservice.php?form=2',
            'PAYU_SPLIT_AUTH'=>'QmetgiU8HibANxwgv/8GwF02GElmNG5gRoRM/sVAyWI=',
            'PAYTM_REFUND_URL'=>''
        ];
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the keys for the HDFC payment gateway based on the environment.
    * @return array The keys for the specified environment.
    * *************************************************************************************************************************/
    public function getKeys(){
        if($this->env == 'PROD'){
            return $this->getProdKeys();
        }else{
            return $this->getTestKeys();
        }
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the vendor details based on the vendor ID.
    * @param int $vendorId The ID of the vendor to retrieve.
    * @return object The vendor details.
    * *************************************************************************************************************************/
    public function getVendor($vendorId){
        return DB::table('vendors')->where('id', $vendorId)->first();
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function converts a numeric amount into words.
    * @param float $amount The amount to convert.
    * @return string The amount in words.
    * *************************************************************************************************************************/
    public function AmountInWords(float $amount)
    {
        $amount_after_decimal = round($amount - ($num = floor($amount)), 2) * 100;
        // Check if there is any number after decimal
        $amt_hundred = null;
        $count_length = strlen($num);
        $x = 0;
        $string = array();
        $change_words = array(
            0 => '', 1 => 'One', 2 => 'Two',
            3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        );
        $here_digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
        while ($x < $count_length) {
            $get_divider = ($x == 2) ? 10 : 100;
            $amount = floor($num % $get_divider);
            $num = floor($num / $get_divider);
            $x += $get_divider == 10 ? 1 : 2;
            if ($amount) {
                $add_plural = (($counter = count($string)) && $amount > 9) ? 's' : null;
                $amt_hundred = ($counter == 1 && $string[0]) ? ' and ' : null;
                $string[] = ($amount < 21) ? $change_words[$amount] . ' ' . $here_digits[$counter] . $add_plural . '
            ' . $amt_hundred : $change_words[floor($amount / 10) * 10] . ' ' . $change_words[$amount % 10] . '
            ' . $here_digits[$counter] . $add_plural . ' ' . $amt_hundred;
            } else $string[] = null;
        }
        $implode_to_Rupees = implode('', array_reverse($string));
        $get_paise = ($amount_after_decimal > 0) ? "And " . ($change_words[$amount_after_decimal / 10] . "
        " . $change_words[$amount_after_decimal % 10]) . ' Paise' : '';
        return ($implode_to_Rupees ? $implode_to_Rupees . 'Rupees only' : '') . $get_paise;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the order details based on the order ID.
    * @param int $orderId The ID of the order to retrieve.
    * @return object The order details.
    * *************************************************************************************************************************/
    public function getOrderDetails($orderId){
        return DB::table('order_details')->where('order_master_id', $orderId)->get();
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function debugs a raw SQL query and returns the final executed query with bindings.
    * @param \Illuminate\Database\Query\Builder $query The query builder instance.
    * @return string The final executed query.
    * *************************************************************************************************************************/
    public function rawQueryDebug($query){

        $sql = vsprintf(
            str_replace('?', "'%s'", $query->toSql()),
            $query->getBindings()
        );
        return $sql;
    }

    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function sets the verification URL.
    * @param string $url The verification URL.
    * @return void
    * *************************************************************************************************************************/
    protected function setVerifyUrl(string $url){
        $this->verifyUrl = $url;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the verification URL.
    * @return string The verification URL.
    * *************************************************************************************************************************/
    protected function getVerifyUrl(){
        return $this->verifyUrl;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function retrieves the vendor details based on the vendor ID.
    * @param int $vendorId The ID of the vendor to retrieve.
    * @return object The vendor details.
    * *************************************************************************************************************************/
    protected function getVendors($vendorId){
        return DB::table('users')->where('id', $vendorId)->first();
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Description: This function generates a QR code for the given data.
    * @param array $data The data to encode in the QR code.
    * @return string The generated QR code.
    * *************************************************************************************************************************/
    protected function generateQrCode($data){
        require_once public_path('QrCode/generateQrCode.php');
        return generateQrCode(json_encode($data));
    }


}
