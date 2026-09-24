<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\PasswordRemQuestion;
use App\User;
use App\Country;
use App\State;
use App\City;
use App\Service;
use Session;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\MasterHotel;
use App\ServiceAttribute;
use App\AttributeValue;
use App\HotelRoom;
use App\HotelRoomPricing;
use App\HotelAvailability;
use App\OrderDetail;
use App\OrderMaster;
use App\MasterInventory;
use App\OrderLog;
use App\HotelSale;
use App\BlockedHotel;
use App\CustomerRefund;
use App\GstDetail;
use App\Coupon;
use App\EmailTemplate;
use App\MmtHotelTable;
use App\MmtChildDetails;
use App\MmtRoomList;
use App\MmtRatePlans;
use App\MmtCredential;
use App\MmtMasterInventory;
use App\SubuserAccess;
use App\BlockedMmtInventory;
use App\CleartripCredential;
use App\CtpHotelTable;
use App\CtpRatePlans;
use App\CtpRoomList;

class IntegrationController extends Controller
{
    public $site;
    public $frontendUrl;
    public $ecoStartDate;

    public function __construct()
    {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
        $this->ecoStartDate = date("Y-m-d", strtotime("2024-09-25"));
    }

    public function mmtSetting()
    {
        if (!(parent::checkViewPrivilege(53))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MmtCredential = MmtCredential::where('vendor_id', $vendor_id)->first();
        $bearer_token = $channel_token = $listing_url = $ari_url = '';
        $gateway = 'sandbox';
        if (!empty($MmtCredential)) {
            $bearer_token = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_bearer_token : $MmtCredential->live_bearer_token;
            $channel_token = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_channel_token : $MmtCredential->live_channel_token;
            $gateway = $MmtCredential->gateway_type;
            $listing_url = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_listing_url : $MmtCredential->live_listing_url;
            $ari_url = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_ari_url : $MmtCredential->live_ari_url;
        }
        return view('Integration.mmt-setting', compact('MmtCredential', 'vendor_id', 'bearer_token', 'channel_token', 'gateway', 'listing_url', 'ari_url'));
    }

    public function saveMmtredentials(Request $request)
    {
        if (!(parent::checkWritePrivilege(53))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'gateway_type' => 'required|string',
            'bearer_token' => 'required|string',
            'channel_token' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('mmt-setting')->withErrors($validate)->withInput();
        } else {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MmtCredential = MmtCredential::where('vendor_id', $vendor_id)->first();
            if (!empty($MmtCredential)) {
                $MmtCredential->gateway_type = $request->gateway_type;
                if ($request->gateway_type == 'sandbox') {
                    $MmtCredential->sandbox_bearer_token = $request->bearer_token;
                    $MmtCredential->sandbox_channel_token = $request->channel_token;
                    $MmtCredential->sandbox_listing_url = $request->listing_url;
                    $MmtCredential->sandbox_ari_url = $request->ari_url;
                } else {
                    $MmtCredential->live_bearer_token = $request->bearer_token;
                    $MmtCredential->live_channel_token = $request->channel_token;
                    $MmtCredential->live_listing_url = $request->listing_url;
                    $MmtCredential->live_ari_url = $request->ari_url;
                }
            } else {
                if ($request->gateway_type == 'sandbox') {
                    $MmtCredential = new MmtCredential([
                        'vendor_id' => $vendor_id,
                        'gateway_type' => $request->gateway_type,
                        'sandbox_bearer_token' => $request->bearer_token,
                        'sandbox_channel_token' => $request->channel_token,
                        'sandbox_listing_url' => $request->listing_url,
                        'sandbox_ari_url' => $request->ari_url
                    ]);
                } else {
                    $MmtCredential = new MmtCredential([
                        'vendor_id' => $vendor_id,
                        'gateway_type' => $request->gateway_type,
                        'live_bearer_token' => $request->bearer_token,
                        'live_channel_token' => $request->channel_token,
                        'live_listing_url' => $request->listing_url,
                        'live_ari_url' => $request->ari_url
                    ]);
                }
            }
            if ($MmtCredential->save()) {
                Session::flash('success', 'MMT credentials saved successfully.');
                return Redirect::to('mmt-setting');
            } else {
                Session::flash('success', 'Unable to save MMT credentials.');
                return Redirect::to('mmt-setting');
            }
        }
    }

    public function hotelMapping($id = null)
    {
        if (!(parent::checkViewPrivilege(47))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $hotel_code = isset($_GET['hotelId']) ? $_GET['hotelId'] : '';  //!is_null($id) ? $id : '';
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotel = MasterHotel::where('vender_id', $vender_id)->get();

        return view('Integration.hotel-mapping', compact('MasterHotel', 'hotel_code'));
    }

    public function hotelMappingRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(47))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'hotel_code' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('hotel-mapping')->withErrors($validate)->withInput();
        } else {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MmtCredential = MmtCredential::where('vendor_id', $vendor_id)->where('hotel_code', $request->hotel_code)->first();
            if (empty($MmtCredential)) {
                Session::flash('error', 'You have not set MMT account yet. Please set up your MMT account and try again.');
                return Redirect::to('hotel-mapping')->with('hotel_code', $request->hotel_code);
            }
            $bearer_token = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_bearer_token : $MmtCredential->live_bearer_token;
            $channel_token = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_channel_token : $MmtCredential->live_channel_token;
            $listing_url = ($MmtCredential->gateway_type == 'sandbox') ? $MmtCredential->sandbox_listing_url : $MmtCredential->live_listing_url;
            $DataToSend = '<?xml version="1.0" encoding="UTF-8" ?>
                <Website Name="ingoibibo" HotelCode="' . $request->hotel_code . '">
                <HotelCode>' . $request->hotel_code . '</HotelCode>
                <IsOccupancyRequired>true</IsOccupancyRequired>
                </Website>';

            $serverUrl = $listing_url . "?bearer_token=" . $bearer_token . "&channel_token=" . $channel_token;
            //            $headers = array(
            //                'bearer_token:'. env('BEARER_TOKEN'),
            //                'channel_token:'. env('CHANNEL_TOKEN')
            //            );
            $connection = curl_init();
            //set the server we are using (could be Sandbox or Production server)
            curl_setopt($connection, CURLOPT_URL, $serverUrl);
            //stop CURL from verifying the peer's certificate
            curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
            //set the headers using the array of headers
            //curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
            //set method as POST
            curl_setopt($connection, CURLOPT_POST, 1);
            //set it to return the transfer as a string from curl_exec
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($connection, CURLOPT_POSTFIELDS, $DataToSend);
            $responseJson = curl_exec($connection);
            $responseData = simplexml_load_string($responseJson);
            curl_close($connection);

            if (isset($responseData->HotelCode)) {
                $HotelCode = $responseData->HotelCode;

                DB::table('mmt_hotel_table')->insertOrIgnore(['vendor_id' => $vendor_id, 'hotel_code' => $HotelCode]);
                $ChildDetails = array();
                $RoomDetails = array();
                $RatePlans = array();

                if (count($responseData->ChildDetails->ChildAge) > 0) {
                    DB::table('mmt_child_details')->where('hotel_code', $HotelCode)->delete();
                    $ChildResponse = $responseData->ChildDetails->ChildAge;
                    foreach ($ChildResponse as $child) {
                        $ChildDetails[] = array(
                            'hotel_code' => $HotelCode,
                            'from_value' => $child['from'],
                            'to_value' => $child['to'],
                            'agerange' => $child['agerange']
                        );
                    }
                    MmtChildDetails::insert($ChildDetails);
                }
                if (count($responseData->RoomList->Room) > 0) {
                    DB::table('mmt_room_list')->where('hotel_code', $HotelCode)->delete();
                    $RoomList = $responseData->RoomList->Room;
                    foreach ($RoomList as $room) {
                        $RoomDetails[] = array(
                            'hotel_code' => $HotelCode,
                            'room_type_name' => $room->RoomTypeName,
                            'room_type_code' => $room->RoomTypeCode,
                            'is_active' => $room->IsActive,
                            'base_adult_occupancy' => $room->AdultOccupancy['base'],
                            'max_adult_occupancy' => $room->AdultOccupancy['max'],
                            'base_child_occupancy' => $room->ChildOccupancy['max'],
                            'max_child_occupancy' => $room->ChildOccupancy['max'],
                        );
                    }
                    MmtRoomList::insert($RoomDetails);
                }
                if (count($responseData->RatePlanList->RatePlan) > 0) {
                    DB::table('mmt_rate_plans')->where('hotel_code', $HotelCode)->delete();
                    $RateList = $responseData->RatePlanList->RatePlan;

                    foreach ($RateList as $rate) {
                        $RatePlans[] = array(
                            'hotel_code' => $HotelCode,
                            'room_type_code' => $rate->RoomTypeCode,
                            'rate_plan_code' => $rate->RatePlanCode,
                            'room_type_name' => $rate->RoomTypeName,
                            'rate_plan_name' => $rate->RatePlanName,
                            'is_active' => $rate->IsActive,
                            'IsEditable' => $rate['IsEditable'],
                            'meal_plan' => $rate->MealPlan,
                            'is_linked_rate_plan' => $rate->LinkedRatePlan['IsLinked'],
                            'lead_occupancy' => $rate->LeadOccupancy,
                        );
                    }
                    MmtRatePlans::insert($RatePlans);
                }
                $RoomList = MmtRoomList::where('hotel_code', $request->hotel_code)->get();
                return Redirect::to('hotel-mapping')->with(['hotel_code' => $request->hotel_code, 'RoomList' => $RoomList]);
            } else {
                Session::flash('success', 'Unable to get details for the provided hotel code.');
                return Redirect::to('hotel-mapping')->with('hotel_code', $request->hotel_code);
            }
        }
    }

    public function mappingOprsn(Request $request)
    {
        if ($request->request_type == 'get_data_for_hotelcode') {
            if (!(parent::checkWritePrivilege(47))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                $hotel_code = $request->hotel_code;
                $MasterHotel = array();
                if (isset($request->hotel_id)) {
                    $MasterHotel = MasterHotel::find($request->hotel_id);
                } else {
                    $MasterHotel = MasterHotel::where(['mmt_hotel_id' => $hotel_code, 'vender_id' => $vender_id])->first();
                }
                $responce['data'] = array();
                if (!empty($MasterHotel)) {
                    $HotelRooms = HotelRoom::select('id', 'title', 'mmt_room_id')->where('hotel_id', $MasterHotel->id)->orderBy('mmt_room_id', 'desc')->get()->toArray();
                    if (!empty($HotelRooms)) {
                        $responce['data'] = $HotelRooms;
                    }
                }
                $responce['status'] = 1;
            }
        } elseif ($request->request_type == 'get_mmt_credentials') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MmtCredential = MmtCredential::where('vendor_id', $vendor_id)->first();
            $responce['data'] = $MmtCredential;
        }
        echo json_encode($responce);
        exit;
    }

    public function saveMappingData(Request $request)
    {
        if (!empty($request->hotelCode)) {
            if (!(parent::checkWritePrivilege(47))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                DB::table("master_hotels")->where(['mmt_hotel_id' => $request->hotelCode, 'vender_id' => $vender_id])->update(['mmt_hotel_id' => '']);

                DB::table("master_hotels")->where('id', $request->hotel_id)->update(['mmt_hotel_id' => $request->hotelCode]);
                for ($i = 1; $i <= $request->room_length; $i++) {
                    $room_code = $_POST['room_code' . $i];
                    $room_id = $_POST['room_id' . $i];
                    DB::table("hotel_rooms")->where('mmt_room_id', $room_code)->update(['mmt_room_id' => '']);

                    DB::table("hotel_rooms")->where('id', $room_id)->update(['mmt_room_id' => $room_code]);
                }
                $responce['status'] = 1;
                $responce['message'] = 'Hotel and rooms mapped successfully.';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function mmtHotelList()
    {
        if (!(parent::checkViewPrivilege(48))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotel = MasterHotel::where('vender_id', $vender_id)->get();

        return view('Integration.hotel-list', compact('MasterHotel'));
    }

    public function getMmtHotelList(Request $request)
    {

        $aColumns = array('hotel_code');
        $sIndexColumn = "id";
        $sTable = "mmt_hotel_table";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $rooms = MmtRoomList::where('hotel_code', $aRow->hotel_code)->pluck('room_type_name', 'room_type_code')->toArray();
            $MasterHotel = MasterHotel::where('mmt_hotel_id', $aRow->hotel_code)->first();
            if (!empty($MasterHotel)) {
                $HotelRoom = HotelRoom::where('hotel_id', $MasterHotel->id)->whereIn('mmt_room_id', array_keys($rooms))->pluck('title')->toArray();
            }

            $row[] = $aRow->hotel_code;
            $row[] = implode(', ', $rooms);
            $row[] = !empty($MasterHotel) ? $MasterHotel->name : 'N/A';
            $row[] = !empty($MasterHotel) ? implode(', ', $HotelRoom) : 'N/A';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('hotel-mapping?hotelId=' . $aRow->hotel_code) . '" target="_blank">Map to Hotel</a></li>                    
                </ul>
            </div>';
            // <li><a href="' . url('availability-details', $aRow->hotel_code) . '" target="_blank">Show Availability</a></li>
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function availabilityDetails($id = null)
    {
        if (!(parent::checkViewPrivilege(48))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $hotel_code = !is_null($id) ? $id : '';
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotel = MasterHotel::where(['vender_id' => $vender_id, 'mmt_hotel_id' => $hotel_code])->first();

        return view('Integration.availability-details', compact('MasterHotel', 'hotel_code'));
    }

    public function getavailabilityDetails(Request $request)
    {

        $aColumns = array('hotel_code', 'hotel_id', 'room_code', 'room_id', 'quantity', 'date', 'created_at');
        $sIndexColumn = "id";
        $sTable = "mmt_availability_logs";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;
        if (!empty($_POST['searchValue1']) || (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3']))) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $condition1 .= ' AND hotel_code = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3'])) {
                if ($_POST['searchValue2'] == 'date') {
                    $dates = explode(' - ', $_POST['searchValue3']);
                    $start = date('Y-m-d', strtotime($dates[0]));
                    $end = date('Y-m-d', strtotime($dates[1]));
                    $condition2 .= ' AND ' . $_POST['searchValue2'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                } else {
                    $condition2 .= ' AND ' . $_POST['searchValue2'] . ' LIKE "' . $_POST['searchValue3'] . '"';
                }
            }
            $sWhere .= $condition1 . $condition2;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $MasterHotel = MasterHotel::find($aRow->hotel_id);
            $HotelRoom = HotelRoom::find($aRow->room_id);

            $row[] = $aRow->hotel_code;
            $row[] = $MasterHotel->name;
            $row[] = $aRow->room_code;
            $row[] = $HotelRoom->title;
            $row[] = $aRow->quantity;
            $row[] = date("d-m-Y", strtotime($aRow->date));
            $row[] = date("d-m-Y H:i:s", strtotime($aRow->created_at));

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function mmtOprsn(Request $request)
    {
        if ($request->request_type == 'change_mmt_inventory') {
            if (!(parent::checkWritePrivilege(88))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterInventory = MmtMasterInventory::find($request->inventoryId);
                if (!empty($MasterInventory)) {
                    $changeType = $request->changeType;
                    $changeQty = $request->changeQty;
                    $initial_qty = $total_available = 0;
                    if ($changeType == 'decrease') {
                        if ($changeQty > $MasterInventory->total_available) {
                            $responce['status'] = 0;
                            $responce['message'] = 'You can decrease maximum ' . $MasterInventory->total_available . ' number of rooms.';
                        } else {
                            $initial_qty = $MasterInventory->initial_quantity - $changeQty;
                            $total_available = $MasterInventory->total_available - $changeQty;
                        }
                    } else {
                        $initial_qty = $MasterInventory->initial_quantity + $changeQty;
                        $total_available = $MasterInventory->total_available + $changeQty;
                    }
                    if (!isset($responce['message'])) {
                        $MasterInventory->initial_quantity = $initial_qty;
                        $MasterInventory->total_available = $total_available;
                        $MasterInventory->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Inventory updated successfully.';
                        parent::updateMmtInventory($MasterInventory->vendor_id, $MasterInventory->hotel_id, $MasterInventory->room_id, $MasterInventory->date, $MasterInventory->date);
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid data input.';
                }
            }
        } elseif ($request->request_type == 'get_mmt_rooms_active') {
            $HotelRoom = HotelRoom::where(['hotel_id' => $request->hotelId, 'status' => 'publish'])->where('mmt_room_id', '!=', '')->get();
            if (!empty($HotelRoom)) {
                $rooms = '';
                foreach ($HotelRoom as $value) {
                    $rooms .= '<option value="' . $value['id'] . '">' . $value['title'] . '</option>';
                }
                echo $rooms;
                exit;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'No rooms available';
            }
        } elseif ($request->request_type == 'print_mmt_report') {
            if (!(parent::checkWritePrivilege(90))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterHotelQuery = MasterHotel::where('mmt_hotel_id', '!=', '');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $MasterHotelQuery->where('vender_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $hotelId = $request->hotel_id;
                if ($hotelId != 0) {
                    $MasterHotelQuery->where('id', $hotelId);
                }
                $MasterHotel = $MasterHotelQuery->pluck('id')->toArray();
                
                $check_date = explode(' - ', $request->filter_date);
                $start_date = date("Y-m-d", strtotime($check_date[0]));
                $end_date = date("Y-m-d", strtotime($check_date[1]));
                $html = ''; 
            
                $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">';
                $MisHotelData = array();
                $filterType = 'booking_date';
                if ($request->filter_type == 'booking_date') {
                    $filterType = 'booking_date';
                } elseif ($request->filter_type == 'start_date') {
                    $filterType = 'start_date';
                }
                
                $MmtOrders = DB::table('mmt_order_masters')
                            ->whereIn('service_name_id', $MasterHotel)
                            ->whereBetween($filterType, [$start_date .' 00:00:00', $end_date .' 23:59:59'])
                            ->orderBy('booking_date', 'DESC')
                            ->get();
                if (!empty($MmtOrders->toArray())) {
                    foreach ($MmtOrders as $key => $value) {
                        // $room_details = json_decode($value->room_details, 1);
                        // $rooms = $room_details[0]['quantity'] .' '. $room_details[0]['name'];
                        $rooms = $value->total_rooms .' '. $value->RoomTypeName;
                        $difference = strtotime($value->end_date) - strtotime($value->start_date);
                        $days = round($difference / (60 * 60 * 24));
                        $night = ($days == 0) ? 1 : $days;
                        $status = ($value->status == 'confirmed') ? 'Confirmed' : 'Cancelled';

                        $MisHotelData[$value->service_name_id]['data'][] = array(
                            'invoice_id' => $value->order_id,
                            'book_date' => date("d-M-Y h:i a", strtotime($value->booking_date)),
                            'guest_name' => $value->customer_name,
                            'guest_phone' => $value->customer_phone,
                            'guest_email' => $value->customer_email,
                            'rooms' => $rooms,
                            'check_in' => date("d-M-Y", strtotime($value->start_date)),
                            'check_out' => date("d-M-Y", strtotime($value->end_date)),
                            'nights' => $night,
                            'total_amount' => $value->total_order_price,
                            'order_type' => $value->booking_vendor_name,
                            'status' => $status,
                            'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                        );
                        $MisHotelData[$value->service_name_id]['name'] = $value->service_name;
                    }
                }
                foreach ($MisHotelData as $key => $val) {
                        
                    $html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px; margin-bottom: 20px;">
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;" colspan="12"><h2>MMT Bookings  - '. $val['name'] .' ('. date("d-M-Y", strtotime($start_date)) .' - '. date("d-M-Y", strtotime($end_date)) .')</h2></th></tr>
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Booking ID</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Booking Date</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Guest</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Room Details</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Check In</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Check Out</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Nights</strong></th>                        
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Total Amount</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Booking Source</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Booking Status</strong></th></tr>';
                    foreach($val['data'] as $details) {
                        $status = ($details['status'] == 'Cancelled') ? 'Cancelled<br>('. date("d-M-Y", strtotime($details['cancel_date'])) .')' : 'Confirmed';
                        $html .= '<tr style="font-size:14px;">
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['invoice_id'] .'</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['book_date'] .'</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['guest_name'] .'</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['rooms'] . '</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . date("d/M/Y", strtotime($details['check_in'])) . '</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. date("d/M/Y", strtotime($details['check_out'])) .'</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $night .'</td>                            
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['total_amount'] .'</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['order_type'] .'</td>
                        <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $status .'</td></tr>';
                    }
                    $html .= '</table>';
                }

                $html .= '</div></body></html>';
                
                $file = 'documents/MMT_booking_report_'. date("d-m-Y h-i-s") .'.pdf';
                $pdfname = public_path($file);
                PDF::loadHTML(html_entity_decode($html))->save($pdfname);
                // echo $this->site . $file;
                $responce['status'] = 1;
                $responce['file_path'] = $file;
                // exit;
            // return response()->download($pdfname)->deleteFileAfterSend(true);
            }
        } elseif ($request->request_type == 'export_mmt_report') {
            if (!(parent::checkWritePrivilege(90))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterHotelQuery = MasterHotel::where('mmt_hotel_id', '!=', '');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $MasterHotelQuery->where('vender_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $hotelId = $request->hotel_id;
                if ($hotelId != 0) {
                    $MasterHotelQuery->where('id', $hotelId);
                }
                $MasterHotel = $MasterHotelQuery->pluck('id')->toArray();
                
                $check_date = explode(' - ', $request->filter_date);
                $start_date = date("Y-m-d", strtotime($check_date[0]));
                $end_date = date("Y-m-d", strtotime($check_date[1]));

                $filterType = 'booking_date';
                if ($request->filter_type == 'booking_date') {
                    $filterType = 'booking_date';
                } elseif ($request->filter_type == 'start_date') {
                    $filterType = 'start_date';
                }
            
                $MisHotelData = array();
                $MmtOrders = DB::table('mmt_order_masters')
                            ->whereIn('service_name_id', $MasterHotel)
                            ->whereBetween($filterType, [$start_date .' 00:00:00', $end_date .' 23:59:59'])
                            ->orderBy('booking_date', 'DESC')
                            ->get();
                if (!empty($MmtOrders->toArray())) {
                    foreach ($MmtOrders as $key => $value) {
                        // $room_details = json_decode($value->room_details, 1);
                        // $rooms = $room_details[0]['quantity'] .' '. $room_details[0]['name'];
                        $rooms = $value->total_rooms .' '. $value->RoomTypeName;
                        $difference = strtotime($value->end_date) - strtotime($value->start_date);
                        $days = round($difference / (60 * 60 * 24));
                        $night = ($days == 0) ? 1 : $days;
                        $status = ($value->status == 'confirmed') ? 'Confirmed' : 'Cancelled';

                        $MisHotelData[$value->service_name_id]['data'][] = array(
                            'invoice_id' => $value->order_id,
                            'book_date' => date("d-M-Y h:i a", strtotime($value->booking_date)),
                            'guest_name' => $value->customer_name,
                            'guest_phone' => $value->customer_phone,
                            'guest_email' => $value->customer_email,
                            'rooms' => $rooms,
                            'check_in' => date("d-M-Y", strtotime($value->start_date)),
                            'check_out' => date("d-M-Y", strtotime($value->end_date)),
                            'nights' => $night,
                            'total_amount' => $value->total_order_price,
                            'order_type' => $value->booking_vendor_name,
                            'status' => $status,
                            'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                        );
                        $MisHotelData[$value->service_name_id]['name'] = $value->service_name;
                    }
                }

                $spreadsheet = new Spreadsheet(); 
                $sheet = $spreadsheet->getActiveSheet();
                $total_column = 10;
                $i = 1;
                foreach ($MisHotelData as $key => $val) {

                    $sheet->mergeCellsByColumnAndRow(1, $i, $total_column, $i);
                    $sheet->setCellValueByColumnAndRow(1,$i,'MMT Bookings  - '. $val['name'] .' ('. date("d-M-Y", strtotime($start_date)) .' - '. date("d-M-Y", strtotime($end_date)) .')');
                    $i++;

                    $sheet->setCellValueByColumnAndRow(1,$i,'Booking ID');
                    $sheet->setCellValueByColumnAndRow(2,$i,'Booking date');
                    $sheet->setCellValueByColumnAndRow(3,$i,'Guest');
                    $sheet->setCellValueByColumnAndRow(4,$i,'Room Details');
                    $sheet->setCellValueByColumnAndRow(5,$i,'Check In');
                    $sheet->setCellValueByColumnAndRow(6,$i,'Check Out');
                    $sheet->setCellValueByColumnAndRow(7,$i,'Nights');
                    $sheet->setCellValueByColumnAndRow(8,$i,'Total Amount');
                    $sheet->setCellValueByColumnAndRow(9,$i,'Booking Source');
                    $sheet->setCellValueByColumnAndRow(10,$i,'Booking Status');
                    $i++;                                            
                    foreach($val['data'] as $details) {
                        $status = ($details['status'] == 'Cancelled') ? 'Cancelled ('. date("d-M-Y", strtotime($details['cancel_date'])) .')' : 'Confirmed';

                        $sheet->setCellValueByColumnAndRow(1,$i,$details['invoice_id']);
                        $sheet->setCellValueByColumnAndRow(2,$i,$details['book_date']);
                        $sheet->setCellValueByColumnAndRow(3,$i,$details['guest_name']);
                        $sheet->setCellValueByColumnAndRow(4,$i,$details['rooms']);
                        $sheet->setCellValueByColumnAndRow(5,$i,date("d/m/Y", strtotime($details['check_in'])));
                        $sheet->setCellValueByColumnAndRow(6,$i,date("d/m/Y", strtotime($details['check_out'])));
                        $sheet->setCellValueByColumnAndRow(7,$i,$night);
                        $sheet->setCellValueByColumnAndRow(8,$i,$details['total_amount']);
                        $sheet->setCellValueByColumnAndRow(9,$i,$details['order_type']);
                        $sheet->setCellValueByColumnAndRow(10,$i,$status);
                        $i++;
                    }
                    $i++;
                }
                $file_name = 'documents/MMT_booking_report_'. date("d-m-Y h-i-s") .'.xlsx';
                $xsl_name = public_path($file_name);

                $writer = new Xlsx($spreadsheet); 
                $writer->save($xsl_name);

                $responce['status'] = 1;
                $responce['file_path'] = $this->site . $file_name;
            }
        } elseif ($request->request_type == 'export_mmt_sales_report') {
            if (!(parent::checkWritePrivilege(90))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterHotelQuery = DB::table("master_hotels"); // MasterHotel::where('status', 'publish');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $MasterHotelQuery->where('vender_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $hotelId = $request->hotel_id;
                if ($hotelId != 0) {
                    $MasterHotelQuery->where('id', $hotelId);
                }
                $MasterHotel = $MasterHotelQuery->pluck('id')->toArray();

                $check_date = explode(' - ', $request->filter_date);
                $start_date = date("Y-m-d", strtotime($check_date[0]));
                $end_date = date("Y-m-d", strtotime($check_date[1]));
                $filter_type = ($request->filter_type == 'booking_date') ? 'created_at' : 'start_date';
                
                $spreadsheet = new Spreadsheet(); 
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->mergeCellsByColumnAndRow(1, 1, 3, 1);
                $sheet->setCellValueByColumnAndRow(1,1,'MMT Sales Report as on ('. date("d-m-Y h:i a") .')');
                $sheet->setCellValueByColumnAndRow(1,2,'Unit Name');
                $sheet->setCellValueByColumnAndRow(2,2,'Booking Source');
                $sheet->setCellValueByColumnAndRow(3,2,'Room Nights');
                // $sheet->setCellValueByColumnAndRow(4,2,'After Discount Price');
                $i = 3;          
                $grand_total = $grand_sub_price = 0;
                foreach ($MasterHotel as $hotelId) {
                    $Orders = DB::table('mmt_order_masters')
                            ->select('service_name', 'booking_vendor_name', DB::raw('SUM(DATEDIFF(end_date, start_date) * total_rooms) as roomNights'))
                            ->where(['status' => 'confirmed', 'fetch_status' => 1, 'service_name_id' => $hotelId])
                            ->whereBetween($filter_type, [$start_date .' 00:00:00', $end_date .' 23:59:59'])
                            ->groupBy('booking_vendor_name')
                            ->get();
                    $unit_total = $unit_sub_price = 0;
                    if (!empty($Orders->toArray())) {
                        foreach($Orders as $data) {
                            $unit_total += $data->roomNights;
                            // $unit_sub_price += $data->subTotal;
                            $j = 1;
                            $sheet->setCellValueByColumnAndRow($j,$i,$data->service_name);
                            $sheet->setCellValueByColumnAndRow(++$j,$i,$data->booking_vendor_name);
                            $sheet->setCellValueByColumnAndRow(++$j,$i,$data->roomNights);
                            // $sheet->setCellValueByColumnAndRow(++$j,$i,$data->subTotal);
                            $i++;
                        }
                        $sheet->mergeCellsByColumnAndRow(1, $i, 2, $i);
                        $sheet->setCellValueByColumnAndRow(1,$i,'UNIT TOTAL');
                        $sheet->setCellValueByColumnAndRow(3,$i,$unit_total);
                        // $sheet->setCellValueByColumnAndRow(4,$i,$unit_sub_price);
                        $i++;
                    }
                    $grand_total += $unit_total;
                    // $grand_sub_price += $unit_sub_price;
                }
                $sheet->mergeCellsByColumnAndRow(1, $i, 2, $i);
                $sheet->setCellValueByColumnAndRow(1,$i,'GRAND TOTAL');
                $sheet->setCellValueByColumnAndRow(3,$i,$grand_total);
                // $sheet->setCellValueByColumnAndRow(4,$i,$grand_sub_price);

                $file_name = 'documents/MMT_sales_report_'. date("d-m-Y h-i-s") .'.xlsx';
                $xsl_name = public_path($file_name);

                $writer = new Xlsx($spreadsheet); 
                $writer->save($xsl_name);

                $responce['status'] = 1;
                $responce['file_path'] = $this->site . $file_name;
            }
        } elseif ($request->request_type == 'delete-blocked-hotel') {
            if (!(parent::checkWritePrivilege(88))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = BlockedMmtInventory::find($request->Id);
                if (!empty($BlockedData)) {
                    $rooms = explode(',', $BlockedData->rooms);
                    $MasterHotel = MasterHotel::find($BlockedData->hotel_id);
                    $MasterInventory = MasterInventory::where('date', date("Y-m-d", strtotime($BlockedData->block_date)))->where('room_id', $rooms)->first();
                    if (!empty($MasterInventory)) {
                        // foreach($MasterInventory as $inventory) {
                            $HotelRoom = HotelRoom::find($MasterInventory->room_id);
                            $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                ->whereRaw("find_in_set('". $MasterInventory->room_id ."',rooms)")
                                                ->first();
                            if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && ($BlockedData->platform == 'all' || $BlockedData->platform == 'mmt')) {
                                $closed = 'false';
                                if (!empty($FullBlockData)) {
                                    $closed = "true";
                                }
                                $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                            <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                <AvailRateUpdate locatorID="1">
                                                    <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                    <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                </AvailRateUpdate>
                                            </AvailRateUpdateRQ>';
                                DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('delete_mmt_block_web', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                            }
                            if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && ($BlockedData->platform == 'all' || $BlockedData->platform == 'cleartrip')) {
                                $CtpRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                if (!empty($CtpRateplanData)) {
                                    $closed = "Open";
                                    if (!empty($FullBlockData)) {
                                        $closed = "Close";
                                    }
                                    $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                    <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $CtpRateplanData->rate_plan_code .'" />
                                                    <RestrictionStatus Status="'. $closed .'" />
                                                </AvailStatusMessage>
                                                </AvailStatusMessages>
                                            </OTA_HotelAvailNotifRQ>';
                                    DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('delete_ctp_block_web', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                            }
                        // }
                    }
                    $BlockedData->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        } elseif ($request->request_type == 'delete_blocked_hotels') {
            if (!(parent::checkWritePrivilege(88))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                $BlockedHotels = DB::table('blocked_mmt_inventory')->whereIn('id', $item_array)->get();
                foreach ($BlockedHotels as $BlockedData) {
                    $rooms = $BlockedData->rooms;
                    $MasterHotel = MasterHotel::find($BlockedData->hotel_id);
                    $MasterInventory = MasterInventory::where('date', date("Y-m-d", strtotime($BlockedData->block_date)))->where('room_id', $rooms)->first();
                    if (!empty($MasterInventory)) {
                        // foreach($MasterInventory as $inventory) {
                            $HotelRoom = HotelRoom::find($MasterInventory->room_id);
                            $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                ->whereRaw("find_in_set('". $MasterInventory->room_id ."',rooms)")
                                                ->first();
                            if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && ($BlockedData->platform == 'all' || $BlockedData->platform == 'mmt')) {
                                $closed = 'false';
                                if (!empty($FullBlockData)) {
                                    $closed = "true";
                                }
                                $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                            <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                <AvailRateUpdate locatorID="1">
                                                    <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                    <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="false" />
                                                </AvailRateUpdate>
                                            </AvailRateUpdateRQ>';
                                DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('delete_mmt_block_web', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                            }
                            if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && ($BlockedData->platform == 'all' || $BlockedData->platform == 'cleartrip')) {
                                $CtpRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                if (!empty($CtpRateplanData)) {
                                    $closed = "Open";
                                    if (!empty($FullBlockData)) {
                                        $closed = "Close";
                                    }
                                    $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                    <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $CtpRateplanData->rate_plan_code .'" />
                                                    <RestrictionStatus Status="Open" />
                                                </AvailStatusMessage>
                                                </AvailStatusMessages>
                                            </OTA_HotelAvailNotifRQ>';
                                    DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('delete_ctp_block_web', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                            }
                        // }
                    }
                }
                DB::table('blocked_mmt_inventory')->whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Block data deleted successfully.';
            }
        } elseif ($request->request_type == 'get_hotel_rooms_active') {
            $HotelRoom = HotelRoom::where(['hotel_id' => $request->hotelId, 'status' => 'publish'])->whereRaw("(mmt_room_id != '' OR ctp_room_id != '')")->get();
            if (!empty($HotelRoom)) {
                $rooms = '';
                foreach ($HotelRoom as $value) {
                    $rooms .= '<option value="' . $value['id'] . '">' . $value['title'] . '</option>';
                }
                echo $rooms;
                exit;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'No rooms available';
            }
        } elseif ($request->request_type == 'print_mmt_block_report') {
            if (!(parent::checkWritePrivilege(90))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockData = DB::select($request->exportQuery);

                $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">';
                $html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px; margin-bottom: 20px;">
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;" colspan="4"><h2>MMT Block Report</h2></th></tr>
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Hotel Name</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Rooms</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Block Date</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;padding:5px;"><strong>Reason</strong></th></tr>';
                foreach ($BlockData as $val) {
                    $HotelRoom = HotelRoom::find($val->rooms);
                    $html .= '<tr style="font-size:14px;">
                    <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $val->hotel_name .'</td>
                    <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $HotelRoom->title .'</td>
                    <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . date("d-M-Y", strtotime($val->block_date)) .'</td>
                    <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $val->block_reason . '</td>
                    </tr>';
                }
                $html .= '</table></div></body></html>';
                
                $file = 'documents/MMT_block_report_'. date("d-m-Y h-i-s") .'.pdf';
                $pdfname = public_path($file);
                PDF::loadHTML(html_entity_decode($html))->save($pdfname);
                // echo $this->site . $file;
                $responce['status'] = 1;
                $responce['file_path'] = $file;
                // exit;
            // return response()->download($pdfname)->deleteFileAfterSend(true);
            }
        }  elseif ($request->request_type == 'get_room_quantity') {
            $block_date = explode(" - ", $request->date);
            $start_date = date("Y-m-d", strtotime($block_date[0]));
            $end_date = date("Y-m-d", strtotime($block_date[1]));
            // print_r($request->roomId);exit;
            $InvData = array();
            foreach ($request->roomId as $roomId) {
                $OrderData = MasterInventory::where('room_id', $roomId)
                            ->whereBetween('date', [$start_date, $end_date])
                            ->pluck('total_available', 'date')->toArray();
                $InvData[] = $OrderData;
            }
            if (!empty($InvData)) {
                $responce['status'] = 1;
                $responce['data'] = $InvData;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get inventory.';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function manageMmtInventory()
    {
        if (!(parent::checkViewPrivilege(88))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterHotelQuery = MasterHotel::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterHotelQuery->where('vender_id', $vender_id);
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
                }
            }
        }
        $MasterHotel = $MasterHotelQuery->where('mmt_hotel_id', '!=', '')->orderBy('name', 'ASC')->pluck('name', 'id');

        return view('Integration.manage-inventory', compact('MasterHotel'));
    }

    public function getMmtInventory(Request $request)
    {
        $aColumns = array('date', 'hotel_id', 'room_id', 'initial_quantity', 'total_available', 'total_booked');
        $sIndexColumn = "id";
        $sTable = "mmt_master_inventory";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = 'WHERE `vendor_id` = ' . $vender_id;
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])  || !empty($_POST['searchValue3'])) {
            $condition1 = '';
            $condition2 = '';
            $condition3 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND hotel_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $check_date = explode(' - ', $_POST['searchValue2']);
                $start = date("Y-m-d", strtotime($check_date[0]));
                $end = date("Y-m-d", strtotime($check_date[1]));
                $condition2 .= ' AND date between "' . $start . '" AND "' . $end . '"';
            }
            if (!empty($_POST['searchValue3'])) {
                $_POST['searchValue3'] = parent::cleanString($_POST['searchValue3']);
                $condition3 .= ' AND room_id = "' . $_POST['searchValue3'] . '"';
            } else {
                $HotelRoom = HotelRoom::where(['hotel_id' => $_POST['searchValue1'], 'status' => 'publish'])->pluck('id')->toArray();
                if (!empty($HotelRoom)) {
                    $condition3 .= ' AND room_id in (' . implode(',', $HotelRoom) . ')';
                }
            }
            $sWhere .= $condition1 . $condition2 . $condition3;
        }
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS *  FROM $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );
        $MasterHotel = MasterHotel::pluck('name', 'id');
        $HotelRoom = HotelRoom::pluck('title', 'id');

        foreach ($rResult as $aRow) {
            $row = array();

            // $BlockedHotels = BlockedHotel::where('hotel_id', $aRow->hotel_id)
            //     ->where('block_date', date("y-m-d", strtotime($aRow->date)))
            //     ->WhereRaw("find_in_set('" . $aRow->room_id . "',rooms)")
            //     ->get()->toArray();
            // $blockStatus = (!empty($BlockedHotels)) ? 1 : 0;

            // $row[] = ($blockStatus == 0) ? date("d M Y", strtotime($aRow->date)) : '<span class="bg-danger">' . date("d M Y", strtotime($aRow->date)) . '</span>';
            // $row[] = ($blockStatus == 0) ? $MasterHotel[$aRow->hotel_id] : '<span class="bg-danger">' . $MasterHotel[$aRow->hotel_id] . '</span>';
            // $row[] = ($blockStatus == 0) ? $HotelRoom[$aRow->room_id] : '<span class="bg-danger">' . $HotelRoom[$aRow->room_id] . '</span>';
            // $row[] = ($blockStatus == 0) ? '<a href="javascript:void(0)" class="change-qty" title="Click to change quantity" data-toggle="modal" data-target="#changeQtyModal" data-available="' . $aRow->total_available . '" data-id="' . $aRow->id . '" data-qty="' . $aRow->initial_quantity . '">' . $aRow->initial_quantity . '</a>' : '<span class="bg-danger">' . $aRow->initial_quantity . '</span>';
            // $row[] = ($blockStatus == 0) ? $aRow->total_available : '<span class="bg-danger">' . $aRow->total_available . '</span>';
            // $row[] = ($blockStatus == 0) ? $aRow->total_booked : '<span class="bg-danger">' . $aRow->total_booked . '</span>';

            $row[] = date("d M Y", strtotime($aRow->date));
            $row[] = $MasterHotel[$aRow->hotel_id];
            $row[] = $HotelRoom[$aRow->room_id];
            $row[] = '<a href="javascript:void(0)" class="change-qty" title="Click to change quantity" data-toggle="modal" data-target="#changeQtyModal" data-available="' . $aRow->total_available . '" data-id="' . $aRow->id . '" data-qty="' . $aRow->initial_quantity . '">' . $aRow->initial_quantity . '</a>';
            $row[] = $aRow->total_available;
            $row[] = $aRow->total_booked;

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function hotelMmtReport(Request $request)
    {
        if (!(parent::checkViewPrivilege(90))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterHotelQuery = MasterHotel::where('mmt_hotel_id', '!=', '');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterHotelQuery->where('vender_id', $vender_id);
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
                }
            }
        }
        $MasterHotel = $MasterHotelQuery->pluck('name', 'id')->toArray();
        $MisHotelData = array();
        $start_date = date("Y-m-d", strtotime('-30 Days'));
        if ($vender_id == 3) {
            $start_date = $this->ecoStartDate;
        }
        $end_date = date("Y-m-d");
        $HotelId = 0;
        $filterType = 'booking_date';
        if (isset($_GET['check_date']) && isset($_GET['hotel_id']) && isset($_GET['filter_type'])) {
            $HotelId = $_GET['hotel_id'];
            $HotelList = array();
            if ($HotelId != 0 && isset($MasterHotel[$HotelId])) {
                $HotelList[$HotelId] = $MasterHotel[$HotelId];
            } else {
                $HotelList = $MasterHotel;
            }
            $check_date = explode(' - ', $_GET['check_date']);
            $start_date = date("Y-m-d", strtotime($check_date[0]));
            $end_date = date("Y-m-d", strtotime($check_date[1]));

            if ($_GET['filter_type'] == 'booking_date') {
                $filterType = 'booking_date';
            } elseif ($_GET['filter_type'] == 'start_date') {
                $filterType = 'start_date';
            }
            $MmtOrders = DB::table('mmt_order_masters')
                        ->whereIn('service_name_id', array_keys($HotelList))
                        ->whereBetween($filterType, [$start_date .' 00:00:00', $end_date .' 23:59:59'])
                        ->orderBy('booking_date', 'DESC')
                        ->get();
            if (!empty($MmtOrders->toArray())) {
                foreach ($MmtOrders as $key => $value) {
                    // $room_details = json_decode($value->room_details, 1);
                    // $rooms = $room_details[0]['quantity'] .' '. $room_details[0]['name'];
                    $rooms = $value->total_rooms .' '. $value->RoomTypeName;
                    $difference = strtotime($value->end_date) - strtotime($value->start_date);
                    $days = round($difference / (60 * 60 * 24));
                    $night = ($days == 0) ? 1 : $days;
                    $status = ($value->status == 'cancelled') ? 'Cancelled' : 'Confirmed';

                    $MisHotelData[$value->service_name_id]['data'][] = array(
                        'invoice_id' => $value->order_id,
                        'invoice_slno' => !empty($value->invoice_id) ? $value->invoice_id : 'N/A',
                        'oderID' => $value->id,
                        'book_date' => date("d-M-Y h:i a", strtotime($value->booking_date)),
                        'guest_name' => $value->customer_name,
                        'guest_phone' => $value->customer_phone,
                        'guest_email' => $value->customer_email,
                        'unit_name' => $value->service_name,
                        'rooms' => $rooms,
                        'check_in' => date("d-M-Y", strtotime($value->start_date)),
                        'check_out' => date("d-M-Y", strtotime($value->end_date)),
                        'nights' => $night,
                        'total_amount' => $value->total_order_price,
                        'order_type' => $value->booking_vendor_name,
                        'status' => $status,
                        'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                    );
                    $MisHotelData[$value->service_name_id]['name'] = $value->service_name;
                }
            }
        } else {
            $MmtOrders = DB::table('mmt_order_masters')
                            ->whereIn('service_name_id', array_keys($MasterHotel))
                            ->whereBetween('booking_date', [$start_date .' 00:00:00', $end_date .' 23:59:59'])
                            ->orderBy('booking_date', 'DESC')
                            ->get();
            if (!empty($MmtOrders->toArray())) {
                foreach ($MmtOrders as $key => $value) {
                    //$room_details = json_decode($value->room_details, 1);
                    //$rooms = $room_details[0]['quantity'] .' '. $room_details[0]['name'];
                    $rooms = $value->total_rooms .' '. $value->RoomTypeName;
                    $difference = strtotime($value->end_date) - strtotime($value->start_date);
                    $days = round($difference / (60 * 60 * 24));
                    $night = ($days == 0) ? 1 : $days;
                    $status = ($value->status == 'cancelled') ? 'Cancelled' : 'Confirmed';

                    $MisHotelData[$value->service_name_id]['data'][] = array(
                        'invoice_id' => $value->order_id,
                        'oderID' => $value->id,
                        'book_date' => date("d-M-Y h:i a", strtotime($value->booking_date)),
                        'guest_name' => $value->customer_name,
                        'guest_phone' => $value->customer_phone,
                        'guest_email' => $value->customer_email,
                        'unit_name' => $value->service_name,
                        'rooms' => $rooms,
                        'check_in' => date("d-M-Y", strtotime($value->start_date)),
                        'check_out' => date("d-M-Y", strtotime($value->end_date)),
                        'nights' => $night,
                        'total_amount' => $value->total_order_price,
                        'order_type' => $value->booking_vendor_name,
                        'status' => $status,
                        'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                    );
                    $MisHotelData[$value->service_name_id]['name'] = $value->service_name;
                }
            }
        }
        
        return view('Integration.hotel-mmt-report', compact('MisHotelData', 'start_date', 'end_date', 'HotelId', 'MasterHotel', 'filterType'));
    }

    public function blockedMmtInventory()
    {
        if (!(parent::checkViewPrivilege(88))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotelQuery = MasterHotel::where('status', 'publish')->where('vender_id', $vender_id)->where('mmt_hotel_id', '!=', '');
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
            }
        }
        $MasterHotel = $MasterHotelQuery->orderBy('name', 'ASC')->pluck('name', 'id');
        $hotelHtml = '';
        foreach ($MasterHotel as $key => $value) {
            $hotelHtml .= '<option value="' . $key . '">' . $value . '</option>';
        }

        return view('Integration.blocked-mmt-inventory', compact('MasterHotel', 'hotelHtml'));
    }

    public function getBlockedInventory(Request $request)
    {

        $aColumns = array('hotel_name', 'rooms', 'block_date', 'block_reason', 'id');
        $sIndexColumn = "id";
        $sTable = "blocked_mmt_inventory";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = 'WHERE `vendor_id` = ' . $vender_id;
        if ($vender_id == 3) {
            $sWhere .= ' AND `created_at` > "'. $this->ecoStartDate .'" ';
        }
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $sWhere .= ' AND hotel_id in (' . implode(',', $SubuserAccess) . ')';
            }
        }

        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || !empty($_POST['searchValue3'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND hotel_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $check_date = explode(' - ', $_POST['searchValue2']);
                $condition2 .= ' AND block_date BETWEEN "' . date("Y-m-d", strtotime($check_date[0])) . '" AND "' . date("Y-m-d", strtotime($check_date[1])) . '"';
            }

            $sWhere .= $condition1 . $condition2;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere $sOrder $sLimit";
        // echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            // $UserData = User::find($aRow->created_by);
            $HotelRoom = HotelRoom::whereIn('id', explode(',', $aRow->rooms))->pluck('title')->toArray();

            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            $row[] = $aRow->hotel_name;
            $row[] = implode(', ', $HotelRoom);
            $row[] = date("M d Y", strtotime($aRow->block_date));
            $row[] = $aRow->block_reason;
            // $row[] = $UserData->first_name . ' ' . $UserData->last_name;
            $row[] = '<a href="javascript:void(0);" class="delete-data" data-id="' . $aRow->id . '">Delete</a>';
            // '<div class="btn-group">
            //     <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
            //     <ul role="menu" class="dropdown-menu">                    
            //         <li></li>
            //     </ul>
            // </div>';
            //            <li><a href="javascript:void(0);" class="change-status" data-id="'. $aRow->id .'" data-status="'. $aRow->status .'">'. $status .'</a></li>
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function blockMmtInventory()
    {
        if (!(parent::checkWritePrivilege(88))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotelQuery = MasterHotel::where('status', 'publish')->where('vender_id', $vender_id)->whereRaw("(mmt_hotel_id != '' OR ctp_hotel_id != '')");
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
            }
        }
        $MasterHotel = $MasterHotelQuery->orderBy('name', 'ASC')->pluck('name', 'id');
        $hotelHtml = '';
        foreach ($MasterHotel as $key => $value) {
            $hotelHtml .= '<option value="' . $key . '">' . $value . '</option>';
        }

        return view('Integration.block-mmt-inventory', compact('MasterHotel', 'hotelHtml'));
    }

    public function blockMmtRequest(Request $request)
    {
        // echo "<pre>";print_r($request->all());exit;
        $hotel = $request->hotel_id;
        $rooms = $request->room_id;
        $check_date = explode(' - ', $request->block_date);
        $block_st_date = date("Y-m-d", strtotime($check_date[0]));
        $block_end_date = date("Y-m-d", strtotime($check_date[1]));
        $BlockedHotels = BlockedMmtInventory::where('hotel_id', $hotel)
            ->whereBetween('block_date', [$block_st_date, $block_end_date])
            ->whereRaw("(platform LIKE 'all' OR platform LIKE '". $request->platform ."')")
            ->whereIn('rooms', $rooms)
            // ->where('rooms', $rooms)
            // ->where(function ($query) use ($rooms) {
            //     foreach ($rooms as $value) {
            //         $query->orWhereRaw("find_in_set('" . $value . "',rooms)");
            //     }
            // })
            ->get()->toArray();
        if (empty($BlockedHotels)) {
            $Blocked_data = array();
            $MasterHotel = MasterHotel::find($hotel);
            $tmp_date = $block_st_date;
            while ($tmp_date <= $block_end_date) {
                foreach ($rooms as $roomId) {
                    $Blocked_data = [
                        'vendor_id' => $MasterHotel->vender_id,
                        'platform' => $request->platform,
                        'hotel_id' => $hotel,
                        'hotel_name' => $MasterHotel->name,
                        'rooms' => $roomId, //implode(',', $roomId),
                        'block_date' => date("Y-m-d", strtotime($tmp_date)),
                        'block_reason' => $request->block_reason,
                        'created_by' => Auth::user()->id
                    ];
                    $HotelRoom = HotelRoom::find($roomId);
                    $MasterInventory = MasterInventory::where(['room_id' => $roomId, 'date' => date("Y-m-d", strtotime($tmp_date))])->first();
                    $total_available = !empty($MasterInventory) ? $MasterInventory->total_available : 0;
                    $CtpRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                    if (((!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && ($request->platform == 'all' || $request->platform == 'mmt')) || (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && !empty($CtpRateplanData) && ($request->platform == 'all' || $request->platform == 'cleartrip')))) {
                        BlockedMmtInventory::insert($Blocked_data);
                    }
                    if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && ($request->platform == 'all' || $request->platform == 'mmt')) {
                        $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                    <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                        <AvailRateUpdate locatorID="1">
                                            <DateRange from="'. $tmp_date . '" to="' . $tmp_date .'"/>
                                            <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $total_available .'" closed="true" />
                                        </AvailRateUpdate>
                                    </AvailRateUpdateRQ>';
                        DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('mmt_block_web', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $total_available ."', '". date("Y-m-d", strtotime($tmp_date)) ."')");
                    }
                    if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && !empty($CtpRateplanData) && ($request->platform == 'all' || $request->platform == 'cleartrip')) {
                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                    <AvailStatusMessage BookingLimit="'. $total_available .'">
                                        <StatusApplicationControl Start="'. $tmp_date .'" End="'. $tmp_date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $CtpRateplanData->rate_plan_code .'" />
                                        <RestrictionStatus Status="Close" />
                                    </AvailStatusMessage>
                                    </AvailStatusMessages>
                                </OTA_HotelAvailNotifRQ>';
                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('ctp_block_web', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $total_available ."', '". date("Y-m-d", strtotime($tmp_date)) ."')");
                    }
                }
                $tmp_date = date("Y-m-d", strtotime($tmp_date . ' + 1 day'));
            }
            Session::flash('success', 'Hotel rooms blocked successfully.');
            return Redirect::to('blocked-mmt-inventory');
        } else {
            Session::flash('success', 'Selected hotel rooms already blocked for this date. please change date and try again.');
            return Redirect::to('block-mmt-inventory');
        }
    }

    public function insertMmtInventory($vendor_id = null, $start_date = null, $end_date = null)
    {
        $today = date("Y-m-d", strtotime($start_date));
        $end_date = date("Y-m-d", strtotime($end_date));
        $AllHotel = MasterHotel::where('vender_id', $vendor_id)->where('mmt_hotel_id', '!=', '')->get();
        if ($end_date >= $today && !empty($AllHotel->toArray())) {
            $difference = strtotime($end_date) - strtotime($today);
            $days = ($end_date == $today) ? 1 : round($difference / (60 * 60 * 24));
            foreach ($AllHotel as $MasterHotel) {
                $HotelRooms = HotelRoom::where('hotel_id', $MasterHotel->id)->where('mmt_room_id', '!=', '')->get();
                $count = 0;
                foreach ($HotelRooms as $value) {
                    $inventory = array();
                    $ExistingInventory = MmtMasterInventory::where('room_id', $value->id)->where('date', '>=', $today)->pluck('room_id', 'date')->toArray();
                    for ($i = 0; $i <= $days; $i++) {
                        $date = date("Y-m-d", strtotime($today . ' + ' . $i . ' days'));
                        if (!array_key_exists($date, $ExistingInventory)) {
                            $inventory[$count] = [
                                'vendor_id' => $MasterHotel->vender_id,
                                'hotel_id' => $MasterHotel->id,
                                'room_id' => $value->id,
                                'date' => $date,
                                'initial_quantity' => $value->mmt_quantity,
                                'total_available' => $value->mmt_quantity,
                                'total_booked' => 0
                            ];
                        }
                        $count++;
                    }
                    $MasterInventory = MmtMasterInventory::insert($inventory);
                }
            }
        }
        echo "Inventory added sussceefully.";
        exit;
    }

    public function hotelMappingCtp($id = null)
    {
        if (!(parent::checkViewPrivilege(99))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $hotel_code = isset($_GET['hotelId']) ? $_GET['hotelId'] : '';  //!is_null($id) ? $id : '';
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotel = MasterHotel::where('vender_id', $vender_id)->get();

        return view('Integration.hotel-mapping-ctp', compact('MasterHotel', 'hotel_code'));
    }

    public function hotelCtpMappingRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(99))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'hotel_code' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('hotel-mapping-ctp')->withErrors($validate)->withInput();
        } else {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $CtpCredential = CleartripCredential::where('vendor_id', $vendor_id)->where('hotel_code', $request->hotel_code)->first();
            if (empty($CtpCredential)) {
                Session::flash('error', 'You have not set cleartrip account yet. Please set up your cleartrip account and try again.');
                return Redirect::to('hotel-mapping-ctp')->with('hotel_code', $request->hotel_code);
            }
            $UserName = ($CtpCredential->gateway_type == 'sandbox') ? $CtpCredential->sandbox_username : $CtpCredential->live_username;
            $Password = ($CtpCredential->gateway_type == 'sandbox') ? $CtpCredential->sandbox_password : $CtpCredential->live_password;
            $listing_url = ($CtpCredential->gateway_type == 'sandbox') ? $CtpCredential->sandbox_listing_url : $CtpCredential->live_listing_url;
            $DataToSend = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope/">
                            <soap:Header>
                                <wsse:Security soap:mustUnderstand="1" xmlns:wsse="http://schemas.xmlsoap.org/ws/2003/06/secext" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                    <wsse:UsernameToken>
                                        <wsse:Username>'. $UserName .'</wsse:Username>
                                        <wsse:Password Type="wsse:PasswordText">'. $Password .'</wsse:Password>
                                    </wsse:UsernameToken>
                                </wsse:Security>
                            </soap:Header>
                            <soap:Body>
                                <OTA_HotelAvailRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                    <AvailRequestSegments>
                                        <AvailRequestSegment AvailReqType="Room">
                                            <HotelSearchCriteria>
                                            <Criterion>
                                                <HotelRef HotelCode="'. $request->hotel_code .'"/>
                                            </Criterion>
                                            </HotelSearchCriteria>
                                        </AvailRequestSegment>
                                    </AvailRequestSegments>
                                </OTA_HotelAvailRQ>
                            </soap:Body>
                        </soap:Envelope>';

            $serverUrl = $listing_url;
            
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $serverUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $DataToSend,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $response);
            $xml = simplexml_load_string($clean_xml);
            $responseData = json_decode(json_encode($xml), 1);
            
            if (isset($responseData['Body']['OTA_HotelAvailRS']['Success']) && count($responseData['Body']['OTA_HotelAvailRS']['RoomStays']['RoomStay']) > 0) {
                $HotelCode = $request->hotel_code;
                DB::table('ctp_hotel_table')->insertOrIgnore(['vendor_id' => $vendor_id, 'hotel_code' => $HotelCode]);
                DB::table('ctp_room_list')->where('hotel_code', $HotelCode)->delete();
                DB::table('ctp_rate_plans')->where('hotel_code', $HotelCode)->delete();

                $RoomResponse = $responseData['Body']['OTA_HotelAvailRS']['RoomStays']['RoomStay'];
                $RoomDetails = array();
                $RatePlans = array();
                foreach ($RoomResponse as $key => $val) {
                    $roomTypeCode = $val['RoomTypes']['RoomType']['@attributes']['RoomTypeCode'];
                    $roomTypeName = $val['RoomTypes']['RoomType']['RoomDescription']['@attributes']['Name'];
                    $ratePlanCode = $val['RatePlans']['RatePlan']['@attributes']['RatePlanCode'];
                    $ratePlanName = $val['RatePlans']['RatePlan']['RatePlanDescription']['@attributes']['Name'];

                    $RoomDetails[] = array(
                        'hotel_code' => $HotelCode,
                        'room_type_name' => $roomTypeName,
                        'room_type_code' => $roomTypeCode,
                    );

                    $RatePlans[] = array(
                        'hotel_code' => $HotelCode,
                        'room_type_code' => $roomTypeCode,
                        'room_type_name' => $roomTypeName,
                        'rate_plan_code' => $ratePlanCode,
                        'rate_plan_name' => $ratePlanName,
                    );
                }
                DB::table('ctp_room_list')->insertOrIgnore($RoomDetails);
                DB::table('ctp_rate_plans')->insertOrIgnore($RatePlans);
                // CtpRatePlans::insert($RatePlans);
                
                $RoomList = CtpRoomList::where('hotel_code', $request->hotel_code)->get();
                return Redirect::to('hotel-mapping-ctp')->with(['hotel_code' => $request->hotel_code, 'RoomList' => $RoomList]);
            } else {
                Session::flash('success', 'Unable to get details for the provided hotel code.');
                return Redirect::to('hotel-mapping-ctp')->with('hotel_code', $request->hotel_code);
            }
        }
    }

    public function saveCtpMappingData(Request $request)
    {
        if (!empty($request->hotelCode)) {
            if (!(parent::checkWritePrivilege(99))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                DB::table("master_hotels")->where(['ctp_hotel_id' => $request->hotelCode, 'vender_id' => $vender_id])->update(['ctp_hotel_id' => '']);

                DB::table("master_hotels")->where('id', $request->hotel_id)->update(['ctp_hotel_id' => $request->hotelCode]);
                for ($i = 1; $i <= $request->room_length; $i++) {
                    $room_code = $_POST['room_code' . $i];
                    $room_id = $_POST['room_id' . $i];
                    DB::table("hotel_rooms")->where('ctp_room_id', $room_code)->update(['ctp_room_id' => '']);

                    DB::table("hotel_rooms")->where('id', $room_id)->update(['ctp_room_id' => $room_code]);
                }
                $responce['status'] = 1;
                $responce['message'] = 'Hotel and rooms mapped successfully.';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function ctpHotelList()
    {
        if (!(parent::checkViewPrivilege(100))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotel = MasterHotel::where('vender_id', $vender_id)->get();

        return view('Integration.ctp-hotel-list', compact('MasterHotel'));
    }

    public function getCtpHotelList(Request $request)
    {
        $aColumns = array('hotel_code');
        $sIndexColumn = "id";
        $sTable = "ctp_hotel_table";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $rooms = CtpRoomList::where('hotel_code', $aRow->hotel_code)->pluck('room_type_name', 'room_type_code')->toArray();
            $MasterHotel = MasterHotel::where('ctp_hotel_id', $aRow->hotel_code)->first();
            if (!empty($MasterHotel)) {
                $HotelRoom = HotelRoom::where('hotel_id', $MasterHotel->id)->whereIn('ctp_room_id', array_keys($rooms))->pluck('title')->toArray();
            }

            $row[] = $aRow->hotel_code;
            $row[] = implode(', ', $rooms);
            $row[] = !empty($MasterHotel) ? $MasterHotel->name : 'N/A';
            $row[] = !empty($MasterHotel) ? implode(', ', $HotelRoom) : 'N/A';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('hotel-mapping-ctp?hotelId=' . $aRow->hotel_code) . '" target="_blank">Map to Hotel</a></li>
                </ul>
            </div>';
            // <li><a href="' . url('availability-details', $aRow->hotel_code) . '" target="_blank">Show Availability</a></li>
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }
}
