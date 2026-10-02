<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '900000M');
ini_set("pcre.backtrack_limit", "5000000");

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function maxDateForSeat ($service_id) {
        $maxDates = array(
            '19' => '2024-03-09',
            '26' => '2024-04-13',
            '27' => '2024-02-15',
            '28' => '2024-02-15',
            '29' => '2024-05-28',
            '30' => '2024-05-20',
            '31' => '2024-02-23',
            '32' => '2023-12-03',
            '33' => '2023-12-03',
            '34' => '2023-06-20',
            '35' => '2023-06-20',
            '36' => '2023-06-20',
        );
        return $maxDates[$service_id];
    }

    public function serviceCategory() {
        $ServiceCategory = array(
            'hotel' => 'Hotel',
            'rental' => 'Rental',
            'sight-seeing' => 'Sight Seeing',
            'package' => 'Package',
            'events' => 'Event',
            'experience-ticketing' => 'Experience Ticketing',
            'entry-ticket' => 'Entry Ticket',
            'food-ordering' => 'Restaurant',
            // 'merchandise' => 'Merchandise'
        );
        return $ServiceCategory;
    }

    public function serviceReviews() {
        $ServiceReview = array(
            [
                'star' => '5',
                'value' => 0,
                'text' => 'Excellent'
            ],
            [
                'star' => '4',
                'value' => 0,
                'text' => 'Very Good'
            ],
            [
                'star' => '3',
                'value' => 0,
                'text' => 'Good'
            ],
            // [
            //     'star' => '2',
            //     'value' => 0,
            //     'text' => 'Poor'
            // ],
            // [
            //     'star' => '1',
            //     'value' => 0,
            //     'text' => 'Terrible'
            // ]
        );
        return $ServiceReview;
    }

    public function checkViewPrivilege($id = null) {
        if (Auth::user()) {
            $UserPrivilege = !empty(Auth::user()->privilege) ? json_decode(Auth::user()->privilege, 1) : [];
            if (Auth::user()->role != 3 || (Auth::user()->role == 3 && array_key_exists($id, $UserPrivilege) && $UserPrivilege[$id] != 0)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function checkWritePrivilege($id = null) {
        if (Auth::user()) {
            $UserPrivilege = !empty(Auth::user()->privilege) ? json_decode(Auth::user()->privilege, 1) : [];
            if (Auth::user()->role != 3 || (Auth::user()->role == 3 && array_key_exists($id, $UserPrivilege) && $UserPrivilege[$id] == 2)) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function cleanString($string) {
        $str = trim(preg_replace('/[^A-Za-z0-9\-\_\,]/', ' ', $string));// Removes special chars.
        return $str;
    }

    public function cleanStringSlug($string) {
        $str = trim(preg_replace('/[^A-Za-z0-9\s]/', '', $string));// Removes special chars.
        return $str;
    }

    public function updateMmtInventory($vendor_id = null, $hotel_id = null, $room_id = null, $start_date = null, $end_date = null) {
        if (!is_null($vendor_id) && !is_null($hotel_id)) {
            $post_data = array('userId' => $vendor_id, 'hotelId' => $hotel_id);
            if (!empty($room_id)) {
                $post_data['roomId'] = $room_id;
            }
            if (!empty($start_date)) {
                $post_data['start_date'] = $start_date;
            }
            if (!empty($end_date)) {
                $post_data['end_date'] = $end_date;
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->site .'cron/updateMmtInventory.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $post_data,
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        }
    }

    public function sendSms($mobilenumber = null, $msg = null, $template_id = null) {
        require_once public_path('paytm_lib/config_paytm.php');
        //if (PAYTM_ENVIRONMENT == 'PROD') {
            $url = "https://govtsms.odisha.gov.in/api/api.php";
            // $msg = $this->cleanString($msg);
            $msg = str_replace(array("&"), array(""), $msg);
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $headers = array(
                "Content-Type: application/x-www-form-urlencoded",
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $data = "action=sendOTPSMS&department_id=D022001&template_id=$template_id&sms_content=$msg&phonenumber=$mobilenumber";

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($curl);
            curl_close($curl);
        //}

        // $response = json_decode($resp, 1);
        // if ($response['success'] == 'true') {
        //     echo '<pre>';
        //     print_r($response);
        // }

    }

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

    public function convert_image_base64($html) {
        $site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xml = simplexml_import_dom($doc); // just to make xpath more simple
        $images = $xml->xpath('//img');
        foreach ($images as $img) {
            $path = $img['src'];
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $path = str_replace($site, '', $path);
            $data = (file_exists(public_path($path))) ? file_get_contents(public_path($path)) : $img['src'];
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $html = str_replace($img['src'], $base64, $html);
        }
        return $html;
    }
}
