<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
Use App\PasswordRemQuestion;
Use App\User;
Use App\Country;
Use App\State;
Use App\City;
Use App\Service;
use Session;
Use App\ServiceAttribute;
Use App\AttributeValue;
Use App\OrderDetail;
Use App\OrderMaster;
Use App\Tour;
Use App\MasterHotel;
Use App\HotelRoom;
Use App\SightSeenPricing;
Use App\TourAvailability;
Use App\MasterInventory;
Use App\OrderLog;
Use App\GstDetail;
Use App\GstTable;
Use App\Coupon;
use App\EmailTemplate;
Use App\SmsTemplate;
Use App\PropertyAccount;
Use App\PaymentHistory;
use App\SubuserAccess;
use App\BlockedMmtInventory;
use App\BlockedHotel;
use App\CtpRatePlans;

class TourController extends Controller {

    public $site;
    public $frontendUrl;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    public function allTours() {
        if (!(parent::checkViewPrivilege(20))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');

        return view('tours.all-tours', compact('Vendors'));
    }

    public function getTourDetails(Request $request) {
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'name', 'category', 'city', 'contact_email', 'contact_number', 'status');
        } else {
            $aColumns = array('id', 'name', 'category', 'city', 'contact_email', 'contact_number', 'status');
        }

        $sIndexColumn = "id";
        $sTable = "tours";
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
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                $staff_condition = '';
                if (!empty($SubuserAccess)) {
                    $staff_condition = ' AND id in (' . implode(',', $SubuserAccess) . ')';
                }
                $vendor_condtition .= $staff_condition;
            }
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;
        $searchColumns = array('name', 'category', 'city');
        if (!empty($_POST['searchValue1']) || (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3']))) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND vendor_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3'])) {
                if (in_array($_POST['searchValue2'], $searchColumns)) {
                    $_POST['searchValue3'] = parent::cleanString($_POST['searchValue3']);
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

            $User = User::find($aRow->vendor_id);
            $route_link = ($aRow->category == 'package') ? '<li><a href="' . url('manage-tour-routes', $aRow->id) . '">Manage Routes</a></li>' : '';
            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            if (Auth::user()->access_type == 'superadmin') {
                $row[] = !empty($User) ? $User->company : 'N/A';
            }
            $row[] = $aRow->name;
            $row[] = strtoupper($aRow->category);
            $row[] = $aRow->city;
            $row[] = $aRow->contact_email;
            $row[] = $aRow->contact_number;
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('tour-edit', $aRow->id) . '">Edit Tour</a></li>'
                    . $route_link . '
                    <li><a href="javascript:void(0)" class="deleteTour" data-id="'. $aRow->id .'">Delete</a></li>
                </ul>
            </div>';
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function tourOprsn(Request $request) {
        if ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(20))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tours')->whereIn('id', $item_array)->update(['status' => 'publish', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Tours publish successful.';
            }
        }
        elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(20))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tours')->whereIn('id', $item_array)->update(['status' => 'draft', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Tours moved to draft successfully.';
            }
        }
        elseif ($request->request_type == 'show_price') {
            if (!(parent::checkWritePrivilege(7))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tours')->whereIn('id', $item_array)->update(['show_price' => 1, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Tour price shown successfully.';
            }
        }
        elseif ($request->request_type == 'hide_price') {
            if (!(parent::checkWritePrivilege(7))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tours')->whereIn('id', $item_array)->update(['show_price' => 0, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Tour price hidden successfully.';
            }
        }
        elseif ($request->request_type == 'delete_tour') {
            if (!(parent::checkWritePrivilege(20))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Tour = Tour::find($request->Id);
                if (!empty($Tour)) {
                    $banner_image = public_path($Tour->banner_image);
                    if (file_exists($banner_image) && !empty($Tour->banner_image)) {
                        unlink($banner_image);
                    }
                    $feature_image = public_path($Tour->feature_image);
                    if (file_exists($feature_image) && !empty($Tour->feature_image)) {
                        unlink($feature_image);
                    }
                    $gallery = json_decode($Tour->gallery, 1);
                    foreach($gallery as $images) {
                        $image = public_path($images);
                        if (file_exists($image)) {
                            unlink($image);
                        }
                    }
                    $Tour->delete();

                    SightSeenPricing::where('tour_id', $Tour->id)->delete();
                    TourAvailability::where('tour_id', $Tour->id)->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Tour deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid tour id.';
                }

                // $item_array = json_decode($request->IdArray);
                // $Ids = '(' . implode(",", $item_array) . ')';
                // $update = DB::select("DELETE FROM `tours` WHERE id IN " . $Ids);
                // $responce['status'] = 1;
                // $responce['message'] = 'Tours deleted successfully.';
            }
        }
        elseif ($request->request_type == 'delete-attribute') {
            if (!(parent::checkWritePrivilege(22))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('service_attributes')->whereIn('id', $item_array)->delete();
                $AttrTerms = AttributeValue::whereIn('attr_id', $item_array)->get();
                if (!empty($AttrTerms)) {
                    foreach ($AttrTerms as $terms) {
                        $image = public_path($terms->icon);
                        if (file_exists($image) && !empty($terms->icon)) {
                            unlink($image);
                        }
                    }
                }
                AttributeValue::whereIn('attr_id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Attributes deleted successfully.';
            }
        }
        elseif ($request->request_type == 'delete-attribute-term') {
            if (!(parent::checkWritePrivilege(22))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                $AttrTerms = AttributeValue::whereIn('id', $item_array)->get();
                if (!empty($AttrTerms)) {
                    foreach ($AttrTerms as $terms) {
                        $image = public_path($terms->icon);
                        if (file_exists($image) && !empty($terms->icon)) {
                            unlink($image);
                        }
                    }
                }
                AttributeValue::whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Attribute terms deleted successfully.';
            }
        }
        elseif ($request->request_type == 'save-attribute-changes') {
            if (!(parent::checkWritePrivilege(22))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $ServiceSttribute = ServiceAttribute::find($request->Id);
                $ServiceSttribute->name = $request->attrName;
                // $ServiceSttribute->status = $request->attrStatus;
                if ($ServiceSttribute->save()) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Changes saved successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to save cheanges.';
                }
            }
        }
        elseif ($request->request_type == 'save-terms-changes') {
            if (!(parent::checkWritePrivilege(22))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $AttributeTerm = AttributeValue::find($request->id);
                if (!empty($AttributeTerm)) {
                    $validate = Validator::make($request->all(), [
                        'name' => 'required|string',
                        // 'attrStatus' => 'required|string',
                        'icon' => 'mimes:jpeg,png,jpg',
                    ]);
                    if ($validate->fails()) {
                        $errors = $validate->errors();
                        $responce['status'] = 0;
                        if ($errors->has('name')) {
                            $responce['message'] = $errors->first('name');
                        } elseif ($errors->has('attrStatus')) {
                            $responce['message'] = $errors->first('attrStatus');
                        } elseif ($errors->has('icon')) {
                            $responce['message'] = $errors->first('icon');
                        }
                    } else {
                        $UploadDir = 'images/attributes/';
                        if ($request->hasFile('icon')) {
                            if ($request->file('icon')->isValid()) {
                                $img = public_path($AttributeTerm->icon);
                                if (file_exists($img) && !empty($AttributeTerm->icon)) {
                                    unlink($img);
                                }
                                $filenameWithExt = str_replace(' ', '-', $request->file('icon')->getClientOriginalName());
                                $icon = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->icon->extension();
                                $request->icon->move(public_path($UploadDir), $icon);
                                $AttributeTerm->icon = $UploadDir . $icon;
                            }
                        }
                        $AttributeTerm->name = $request->name;
                        // $AttributeTerm->status = $request->attrStatus;

                        $AttributeTerm->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Changes saved successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid attribute id.';
                }
            }
        }
        elseif ($request->request_type == 'get_tour_bookings') {
            $date = date("Y-m", strtotime($request->Date));
            $OrderMaster = DB::table('order_masters')
                            ->select('start_date', DB::raw('SUM(total_guests) as totQty'))
                            ->where('service_name_id', $request->tourId)
                            ->where('service_type', 'tour')
                            ->where('start_date', 'like', '%' . $date . '%')
                            ->where('status', 'completed')
                            ->groupBy('start_date')
                            ->get()->toArray();

            if (!empty($OrderMaster)) {
                $responce['status'] = 1;
                $responce['data'] = $OrderMaster;
            } else {
                $responce['status'] = 0;
                $responce['data'] = [];
            }
        }
        elseif ($request->request_type == 'get_city') {
            $CityDetail = City::where(['state_id' => 4013])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            if (!empty($CityDetail)) {
                $city = array();
                foreach ($CityDetail as $key => $value) {
                    $city[$value] = $value;
                }
                $responce['status'] = 1;
                $responce['data'] = $city;
            } else {
                $responce['status'] = 0;
                $responce['data'] = array();
            }
        }
        elseif ($request->request_type == 'get_tour_route') {
            $TourDetails = Tour::find($request->tourId);
            $default_map = array();
            if (!empty($TourDetails->route_map)) {
                $route_map = json_decode($TourDetails->route_map, 1);
                if(array_key_exists($request->route, $route_map)) {
                    $default_map = $route_map[$request->route];
                    $responce['data'] = $default_map;
                } else {
                    $responce['data'] = array();
                }
                $responce['status'] = 1;
            } else {
                $responce['status'] = 0;
                $responce['data'] = array();
            }
        }
        elseif ($request->request_type == 'get_hotel_rooms') {
            $HotelDetails = MasterHotel::find($request->hotelName);
            if (!empty($HotelDetails)) {
                $roomData = '<option value="">Select Room</option>';
                $HotelRooms = HotelRoom::where(['hotel_id' => $HotelDetails->id, 'status' => 'publish'])->get();
                foreach ($HotelRooms as $value) {
                    $roomData .= '<option value="' . $value->title . '~' . $value->id . '">' . $value->title . '</option>';
                }
                $responce['status'] = 1;
                $responce['data'] = $roomData;
            } else {
                $responce['status'] = 0;
                $responce['data'] = '';
            }
        }
        elseif ($request->request_type == 'delete-tour-pricing') {
            if (!(parent::checkWritePrivilege(61))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                DB::table('sight_seen_pricing')->where('price_plan', $request->plan)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Delete successful.';
            }
        }
        elseif ($request->request_type == 'delete-blocked-tour') {
            if (!(parent::checkWritePrivilege(62))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = TourAvailability::find($request->Id);
                if (!empty($BlockedData)) {
                    $BlockedData->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        }
        elseif ($request->request_type == 'delete_blocked_tours') {
            if (!(parent::checkWritePrivilege(62))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray, 1);
                $DeleteData = DB::table('tour_availability')->whereIn('id', $item_array)->delete();
                if ($DeleteData) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        }
        elseif ($request->request_type == 'get_tour_list') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $TourQry = Tour::where(['status' => 'publish', 'vendor_id' => $vendor_id, 'category' => $request->type]);
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $TourQry->whereIn('id', array_values($SubuserAccess));
                }
            }
            $Tour = $TourQry->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
            $html = '<option value="">Select Tour</option>';
            if (!empty($Tour)) {
                foreach ($Tour as $key => $value) {
                    $html .= '<option value="'. $key .'">'. $value .'</option>';
                }
            }
            echo $html;exit;
        }
        elseif ($request->request_type == 'get_tour_details') {
            $TourData = Tour::find($request->tourId);
            if (!empty($TourData)) {
                $BlockData = TourAvailability::where(['tour_id' => $TourData->id, 'block_date' => date('Y-m-d', strtotime($request->checkinDate))])->first();
                if (!empty($BlockData)) {
                    $responce['message'] = 'No tickets available for this date. Please choose different date!';
                    $responce['status'] = 0;
                } else {
                    $OrderDetails = DB::table('order_masters')
                            ->select(DB::raw('SUM(total_guests) as totQty'))
                            ->where('service_name_id', $TourData->id)
                            ->where('service_type', 'tour')
                            ->where('start_date', date("Y-m-d", strtotime($request->checkinDate)))
                            ->where('status', '!=', 'cancelled')
                            ->first();

                    if ($OrderDetails->totQty > 0 && ($TourData->vendor_id != 1 || $TourData->category != 'package')) {
                        $TourData->max_people = $TourData->max_people - $OrderDetails->totQty;
                        if ($TourData->max_people < 1) {
                            $responce['message'] = 'No tickets available for this date. Please choose different date!';
                            $responce['status'] = 0;
                            echo json_encode($responce);
                            exit;
                        }
                    } elseif ($TourData->max_people < 1) {
                        $responce['message'] = 'No tickets available for this date. Please choose different date!';
                        $responce['status'] = 0;
                        echo json_encode($responce);
                        exit;
                    }
                    if ($TourData->category == 'sight seeing') {
                        $weekDay = date("l", strtotime($request->checkinDate));
                        $TourData->not_available = !empty($TourData->not_available) ? json_decode($TourData->not_available, 1) : [];
                        if (in_array($weekDay, $TourData->not_available)) {
                            $responce['message'] = 'Tour is not available on ' . $weekDay . '. Please choose different date!';
                            $responce['status'] = 0;
                            echo json_encode($responce);
                            exit;
                        }
                        $SightSeenPricing = SightSeenPricing::where(['tour_id' => $TourData->id, 'start_date' => date("Y-m-d", strtotime($request->checkinDate))])->first();
                        if (!empty($SightSeenPricing)) {
                            $discount_amount = $SightSeenPricing->offer_percentage; // $TourData->single_share_price * ($SightSeenPricing->offer_percentage / 100);
                            if ($SightSeenPricing->offer_type == 'plus') {
                                $TourData->single_share_price = round($TourData->single_share_price + $discount_amount);
                            } else {
                                $TourData->single_share_price = round($TourData->single_share_price - $discount_amount);
                            }
                        }
                        $responce['status'] = 1;
                        $responce['maxTicket'] = $TourData->max_people;
                        $responce['ticketPrice'] = $TourData->single_share_price;
                    } else {
                        $checkoutDate = date('Y-m-d', strtotime($request->checkinDate . ' + ' . ($TourData->duration_end - 1) . ' days'));
                        $difference = strtotime($checkoutDate) - strtotime($request->checkinDate);
                        $days = round($difference / (60 * 60 * 24));
                        $Itinerary = json_decode($TourData->itinerary, 1);
                        foreach ($Itinerary as $itnrs) {
                            if ($itnrs['hotel'] != 'other' && $itnrs['hotel'] != 'No Accommodation') {
                                for ($i = 0; $i < $days; $i++) {
                                    $checkDate = date("Y-m-d", strtotime($request->checkinDate . ' + ' . $i . ' days'));
                                    $MasterInventory = MasterInventory::where(["date" => $checkDate, 'room_id' => $itnrs['hotelRoom']])->first();
                                    if ($MasterInventory->total_available < 1) {
                                        $responce['message'] = 'No tickets available for this date. Please choose different date!';
                                        $responce['status'] = 0;
                                        echo json_encode($responce);
                                        exit;
                                    }
                                }
                            }
                        }

                        $responce['status'] = 1;
                        $responce['maxTicket'] = $TourData->max_people;
                        $responce['single_share_price'] = $TourData->single_share_price;
                        $responce['double_share_price'] = $TourData->double_share_price;
                        $responce['triple_share_price'] = $TourData->triple_share_price;
                        $responce['child_price'] = $TourData->child_price;
                    }
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'No tickets available.';
            }
        }
        elseif ($request->request_type == 'confirm_tour') {
            $TourData = Tour::find($request->tourId);
            $GstDetails = array();$total_gst = 0;
            if ($TourData->gst_applicable == 1) {
                $TourData->category = ($TourData->category == 'sight seeing') ? 'sight-seeing' : $TourData->category;
                $GstTable = GstTable::where(['vendor_id' => $TourData->vendor_id, 'service_type' => $TourData->category])
                        ->where('min_amount', '<=', $request->orderTotal)
                        ->orderBy('min_amount', 'DESC')
                        ->first();
                if (!empty($GstTable)) {
                    $GstData = json_decode($GstTable->gst, 1);
                    foreach ($GstData as $key => $val) {
                        $gst_val = ceil($request->orderTotal * ((float) $val / 100));
                        $GstDetails[] = [
                            'gst_name' => $key,
                            'gst_percentage' => $val,
                            'gst_value' => round($gst_val, 2)
                        ];
                        $total_gst += $gst_val;
                    }
                } else {
                    $GSTData = GstDetail::pluck('value', 'name')->toArray();
                    foreach ($GSTData as $key => $val) {
                        $gst_val = ceil($request->orderTotal * ((float) $val / 100));
                        $GstDetails[] = [
                            'gst_name' => $key,
                            'gst_percentage' => $val,
                            'gst_value' => round($gst_val, 2)
                        ];
                        $total_gst += $gst_val;
                    }
                }
            }
            $responce['grossPrice'] = round($request->orderTotal, 2);
            $responce['subTotalPrice'] = round($request->orderTotal, 2);
            $responce['totalOrderPrice'] = round($request->orderTotal + $total_gst + $TourData->service_fee, 2);
            $responce['gst_data'] = $GstDetails;
        }
        elseif ($request->request_type == 'verify_coupon') {
            $TourData = Tour::find($request->tourId);
            $TourData->category = ($TourData->category == 'sight seeing') ? 'sight-seeing' : $TourData->category;
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $coupons = Coupon::where(['service_type' => 'tour', 'coupon_code' => $request->coupon_code, 'vendor_id' => $vendor_id])
                    ->where('start_date', '<=', date("Y-m-d", strtotime($request->checkIn)))
                    ->where('end_date', '>=', date("Y-m-d", strtotime($request->checkIn)))
                    ->where('status', 'publish')
                    ->first();
            if (!empty($coupons)) {
                if ($coupons->frequency > $coupons->already_used && $coupons->min_order_amount <= $request->order_value) {
                    $coupon_data = array(
                        'coupon_name' => $coupons->coupon_name,
                        'coupon_code' => $coupons->coupon_code,
                        'coupon_value' => $coupons->coupon_amount
                    );
                    $grossPrice = $request->order_value;
                    $total_coupon_amt = ceil($grossPrice * ($coupon_data['coupon_value'] / 100));
                    $SubTotal = ceil($grossPrice - $total_coupon_amt);
                    $GstDetails = array();$total_gst = 0;
                    if ($TourData->gst_applicable == 1) {
                        $GstTable = GstTable::where(['vendor_id' => $TourData->vendor_id, 'service_type' => $TourData->category])
                                ->where('min_amount', '<=', $SubTotal)
                                ->orderBy('min_amount', 'DESC')
                                ->first();
                        if (!empty($GstTable)) {
                            $GstData = json_decode($GstTable->gst, 1);
                            foreach ($GstData as $key => $val) {
                                $gst_val = ceil($SubTotal * ((float) $val / 100));
                                $GstDetails[] = [
                                    'gst_name' => $key,
                                    'gst_percentage' => $val,
                                    'gst_value' => ceil($gst_val)
                                ];
                                $total_gst += $gst_val;
                            }
                        } else {
                            $GSTData = GstDetail::pluck('value', 'name')->toArray();
                            foreach ($GSTData as $key => $val) {
                                $gst_val = ceil($SubTotal * ((float) $val / 100));
                                $GstDetails[] = [
                                    'gst_name' => $key,
                                    'gst_percentage' => $val,
                                    'gst_value' => ceil($gst_val)
                                ];
                                $total_gst += $gst_val;
                            }
                        }
                    }
                    $responce['status'] = 1;
                    $responce['gst_data'] = $GstDetails;
                    $responce['grossPrice'] = ceil($grossPrice);
                    $responce['subTotalPrice'] = ceil($SubTotal);
                    $responce['discount_amount'] = ceil($total_coupon_amt);
                    $responce['totalOrderPrice'] = ceil($SubTotal + $total_gst + $TourData->service_fee);
                    $responce['coupon_data'] = $coupon_data;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Coupon is not applicable.';
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Invalid coupon code.';
            }
        }
        elseif ($request->request_type == 'print_booking_report') {
            if (!(parent::checkWritePrivilege(101))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $TourQuery = Tour::where('status', 'publish');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $TourQuery->where('vendor_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $TourQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $ticketId = $request->ticket_id;
                if ($ticketId != 0) {
                    $TourQuery->where('id', $ticketId);
                }
                $Tours = $TourQuery->pluck('id')->toArray();
                $check_date = date("Y-m-d", strtotime($request->filter_date));
                $html = '';

                if ($request->report_type == 'book_date') {
                    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;">
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;" colspan="11"><h2>(Booking Date - '. date("d-M-Y", strtotime($check_date)) .')</h2></th></tr>
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking ID</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking Date</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Guest</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Tour Name</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Tour Date</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Tour Type</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Occupancy</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Seat No</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Total Amount</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking Source</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking Status</strong></th></tr>';

                    $OrderMaster = OrderMaster::where(['service_type' => 'tour', 'payment_status' => 'success'])
                                    ->whereIn('service_name_id', $Tours)
                                    ->where('created_at', 'LIKE', $check_date .'%')
                                    ->where('status', '!=', 'partially-cancelled')
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
                    if (!empty($OrderMaster->toArray())) {
                        foreach ($OrderMaster as $value) {
                            $status = ($value->status == 'cancelled') ? 'Cancelled<br>('. date("d-M-Y", strtotime($value->cancel_date)) .')' : 'Confirmed';
                            $seat_no = !empty($value->gate_number) ? $value->gate_number : 'N/A';
                            $html .= '<tr style="font-size:14px;">
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->invoice_id .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . date("d-M-Y h:i a", strtotime($value->created_at)) . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $value->customer_name .'<br>'. $value->customer_phone .'<br>'. $value->customer_email . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $value->service_name . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . date("d-M-Y", strtotime($value->start_date)) . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->service_category .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->total_guests .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $seat_no .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->total_order_price .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->order_type .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $status .'</td>
                            </tr>';
                        }
                    }
                    $html .= '</table></div></body></html>';
                } elseif ($request->report_type == 'stay_date') {
                    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">';

                    $Orders = OrderMaster::where(['status' => 'completed'])
                        ->where('service_type', 'tour')
                        ->whereIn('service_name_id', $Tours)
                        ->where('start_date', $check_date)
                        ->get();
                    if (!empty($Orders->toArray())) {
                        $MisHotelData = array();
                        foreach ($Orders as $key => $value) {
                            $hotelId = $hotelName = $start_date = $time = '';
                            $start_date = date("d-M-Y", strtotime($value->start_date));
                            $time = $value->start_time .' - '. $value->end_time;
                            $hotelId = $value->service_name_id;
                            $hotelName = $value->service_name;


                            $MisHotelData[$hotelId]['data'][] = array(
                                'invoice_id' => $value->invoice_id,
                                'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                                'guest_name' => $value->customer_name,
                                'guest_phone' => $value->customer_phone,
                                'guest_email' => $value->customer_email,
                                'unit_name' => $value->service_name,
                                'check_in' => $start_date,
                                'time' => $time,
                                'service_category' => $value->service_category,
                                'occupancy' => $value->total_guests,
                                'seat_no' => !empty($value->gate_number) ? $value->gate_number : 'N/A',
                                'total_amount' => $value->total_order_price,
                                'order_type' => $value->order_type,
                            );

                            $MisHotelData[$hotelId]['name'] = $hotelName;
                            $MisHotelData[$hotelId]['total_book'] = (isset($MisHotelData[$hotelId]['total_book'])) ? $MisHotelData[$hotelId]['total_book'] + 1 : 1;
                            $MisHotelData[$hotelId]['total_occupancy'] = (isset($MisHotelData[$hotelId]['total_occupancy'])) ? $MisHotelData[$hotelId]['total_occupancy'] + $value->total_guests : $value->total_guests;
                        }
                        foreach ($MisHotelData as $key => $val) {
                            $html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px; margin-bottom: 20px;">
                            <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;" colspan="9"><h2>'. $val['name'] .' (Stay by Date - '. date("d-M-Y", strtotime($check_date)) .')</h2></th>
                            <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Booking ID</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Guest</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Booking Date</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Tour Date</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Tour Type</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Occupancy</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Seat No</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Total Amount</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Booking Source</strong></th></tr>';
                            foreach($val['data'] as $details) {
                                $html .= '<tr style="font-size:14px;">
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['invoice_id'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['guest_name'] .'<br>'. $details['guest_phone'] .'<br>'. $details['guest_email'] . '</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['book_date'] . '</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['check_in'] . '</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['service_category'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['occupancy'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['seat_no'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['total_amount'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['order_type'] .'</td></tr>';
                            }
                            $html .= '<tr style="font-size:14px;"><th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="5">Total Bookings:'. $val['total_book'] .'</th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $val['total_occupancy'] .'</th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="3"></th>
                            </tr></table>';

                        }
                    }
                    $html .= '</div></body></html>';
                }

                $file = 'documents/Tour_booking_report_' . date("d-m-Y h-i-a") . '.pdf';
                $pdfname = public_path($file);
                PDF::loadHTML(html_entity_decode($html))->save($pdfname);
                // echo $this->site . $file;
                // exit;
                $responce['status'] = 1;
                $responce['file_path'] = $file;
                // return response()->download($pdfname)->deleteFileAfterSend(true);
            }
        }
        elseif ($request->request_type == 'export_booking_report') {
            if (!(parent::checkWritePrivilege(101))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $TourQuery = Tour::where('status', 'publish');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $TourQuery->where('vendor_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $TourQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $ticketId = $request->ticket_id;
                if ($ticketId != 0) {
                    $TourQuery->where('id', $ticketId);
                }
                $Tours = $TourQuery->pluck('id')->toArray();
                $check_date = date("Y-m-d", strtotime($request->filter_date));

                if ($request->report_type == 'book_date') {

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $total_column = 11;

                    $sheet->mergeCellsByColumnAndRow(1, 1, $total_column, 1);
                    $sheet->setCellValueByColumnAndRow(1,1,'(Booking Date - '. date("d-M-Y", strtotime($check_date)) .')');

                    $sheet->setCellValueByColumnAndRow(1,2,'Booking ID');
                    $sheet->setCellValueByColumnAndRow(2,2,'Booking Date');
                    $sheet->setCellValueByColumnAndRow(3,2,'Guest');
                    $sheet->setCellValueByColumnAndRow(4,2,'Tour Name');
                    $sheet->setCellValueByColumnAndRow(5,2,'Tour Date');
                    $sheet->setCellValueByColumnAndRow(6,2,'Tour Type');
                    $sheet->setCellValueByColumnAndRow(7,2,'Occupancy');
                    $sheet->setCellValueByColumnAndRow(8,2,'Seat No');
                    $sheet->setCellValueByColumnAndRow(9,2,'Total Amount');
                    $sheet->setCellValueByColumnAndRow(10,2,'Booking Source');
                    $sheet->setCellValueByColumnAndRow(11,2,'Booking Status');

                    $OrderMaster = OrderMaster::where(['service_type' => 'tour', 'payment_status' => 'success'])
                                    ->whereIn('service_name_id', $Tours)
                                    ->where('created_at', 'LIKE', $check_date .'%')
                                    ->where('status', '!=', 'partially-cancelled')
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
                    if (!empty($OrderMaster->toArray())) {
                        $i = 3;
                        foreach ($OrderMaster as $value) {
                            $status = ($value->status == 'cancelled') ? 'Cancelled , ('. date("d-M-Y", strtotime($value->cancel_date)) .')' : 'Confirmed';
                            $seat_no = !empty($value->gate_number) ? $value->gate_number : 'N/A';

                            $sheet->setCellValueByColumnAndRow(1,$i,$value->invoice_id);
                            $sheet->setCellValueByColumnAndRow(2,$i,date("d-M-Y h:i a", strtotime($value->created_at)));
                            $sheet->setCellValueByColumnAndRow(3,$i,$value->customer_name .' , '. $value->customer_phone .' , '. $value->customer_email);
                            $sheet->setCellValueByColumnAndRow(4,$i,$value->service_name);
                            $sheet->setCellValueByColumnAndRow(5,$i,date("d-M-Y", strtotime($value->start_date)));
                            $sheet->setCellValueByColumnAndRow(6,$i,$value->service_category);
                            $sheet->setCellValueByColumnAndRow(7,$i,$value->total_guests);
                            $sheet->setCellValueByColumnAndRow(8,$i,$seat_no);
                            $sheet->setCellValueByColumnAndRow(9,$i,$value->total_order_price);
                            $sheet->setCellValueByColumnAndRow(10,$i,$value->order_type);
                            $sheet->setCellValueByColumnAndRow(11,$i,$status);
                            $i++;
                        }
                    }
                    $file_name = 'documents/Tour_booking_report_'. date("d-m-Y h-i-s") .'.xlsx';
                    $xsl_name = public_path($file_name);

                    $writer = new Xlsx($spreadsheet);
                    $writer->save($xsl_name);

                    $responce['status'] = 1;
                    $responce['file_path'] = $this->site . $file_name;
                } elseif ($request->report_type == 'stay_date') {
                    // $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">';

                    $Orders = OrderMaster::where(['status' => 'completed'])
                            ->where('service_type', 'tour')
                            ->whereIn('service_name_id', $Tours)
                            ->where('start_date', $check_date)
                            ->get();
                    if (!empty($Orders->toArray())) {
                        $MisHotelData = array();
                        foreach ($Orders as $key => $value) {
                            $hotelId = $hotelName = $start_date = $time = '';

                            $start_date = date("d-M-Y", strtotime($value->start_date));
                            $time = $value->start_time .' - '. $value->end_time;
                            $hotelId = $value->service_name_id;
                            $hotelName = $value->service_name;

                            $MisHotelData[$hotelId]['data'][] = array(
                                'invoice_id' => $value->invoice_id,
                                'book_date' => date("d-M-Y h i a", strtotime($value->created_at)),
                                'guest_name' => $value->customer_name,
                                'guest_phone' => $value->customer_phone,
                                'guest_email' => $value->customer_email,
                                'unit_name' => $value->service_name,
                                'check_in' => $start_date,
                                'time' => $time,
                                'service_category' => $value->service_category,
                                'occupancy' => $value->total_guests,
                                'seat_no' => !empty($value->gate_number) ? $value->gate_number : 'N/A',
                                'total_amount' => $value->total_order_price,
                                'order_type' => $value->order_type,
                            );

                            $MisHotelData[$hotelId]['name'] = $hotelName;
                            $MisHotelData[$hotelId]['total_book'] = (isset($MisHotelData[$hotelId]['total_book'])) ? $MisHotelData[$hotelId]['total_book'] + 1 : 1;
                            $MisHotelData[$hotelId]['total_occupancy'] = (isset($MisHotelData[$hotelId]['total_occupancy'])) ? $MisHotelData[$hotelId]['total_occupancy'] + $value->total_guests : $value->total_guests;
                        }

                        $spreadsheet = new Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $total_column = 9;

                        $i = 1;
                        foreach ($MisHotelData as $key => $val) {

                            $sheet->mergeCellsByColumnAndRow(1, $i, $total_column, $i);
                            $sheet->setCellValueByColumnAndRow(1,$i,$val['name'] .' (Stay by Date - '. date("d-M-Y", strtotime($check_date)) .')');
                            $i++;

                            $sheet->setCellValueByColumnAndRow(1,$i,'Booking ID');
                            $sheet->setCellValueByColumnAndRow(2,$i,'Guest');
                            $sheet->setCellValueByColumnAndRow(3,$i,'Booking Date');
                            $sheet->setCellValueByColumnAndRow(4,$i,'Tour Date');
                            $sheet->setCellValueByColumnAndRow(5,$i,'Tour Type');
                            $sheet->setCellValueByColumnAndRow(6,$i,'Occupancy');
                            $sheet->setCellValueByColumnAndRow(7,$i,'Seat No');
                            $sheet->setCellValueByColumnAndRow(8,$i,'Total Amount');
                            $sheet->setCellValueByColumnAndRow(9,$i,'Booking Source');
                            $i++;

                            foreach($val['data'] as $details) {
                                $sheet->setCellValueByColumnAndRow(1,$i,$details['invoice_id']);
                                $sheet->setCellValueByColumnAndRow(2,$i,$details['guest_name'] .' , '. $details['guest_phone'] .' , '. $details['guest_email']);
                                $sheet->setCellValueByColumnAndRow(3,$i,$details['book_date']);
                                $sheet->setCellValueByColumnAndRow(4,$i,$details['check_in']);
                                $sheet->setCellValueByColumnAndRow(5,$i,$details['service_category']);
                                $sheet->setCellValueByColumnAndRow(6,$i,$details['occupancy']);
                                $sheet->setCellValueByColumnAndRow(7,$i,$details['seat_no']);
                                $sheet->setCellValueByColumnAndRow(8,$i,$details['total_amount']);
                                $sheet->setCellValueByColumnAndRow(9,$i,$details['order_type']);
                                $i++;
                            }
                            $sheet->mergeCellsByColumnAndRow(1, $i, 5, $i);
                            $sheet->setCellValueByColumnAndRow(1,$i,'Total Bookings:'. $val['total_book']);
                            // $i++;
                            $sheet->setCellValueByColumnAndRow(6,$i,$val['total_occupancy']);
                            $i += 2;
                            // $html .= '<tr style="font-size:14px;"><th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="6">Total Bookings:'. $val['total_book']  .' | '. implode(' | ', array_map(function ($v, $k) { return $k.':'.$v; },$MisHotelData[$key]['rooms'],array_keys($MisHotelData[$key]['rooms']))) .' | Total Booked Rooms:'. $val['total_rooms'] .'</th>
                            // <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $val['total_occupancy'] .'</th>
                            // <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="4"></th>
                            // </tr></table>';

                        }
                    }
                    $file_name = 'documents/Tour_stay_by_date_report_'. date("d-m-Y h-i-s") .'.xlsx';
                    $xsl_name = public_path($file_name);

                    $writer = new Xlsx($spreadsheet);
                    $writer->save($xsl_name);

                    $responce['status'] = 1;
                    $responce['file_path'] = $this->site . $file_name;
                    // $html .= '</div></body></html>';
                }

                // $file = 'documents/Booking_report_' . date("d-m-Y h-i-a") . '.pdf';
                // $pdfname = public_path($file);
                // PDF::loadHTML(html_entity_decode($html))->save($pdfname);
                // // echo $this->site . $file;
                // // exit;
                // $responce['status'] = 1;
                // $responce['file_path'] = $file;
                // // return response()->download($pdfname)->deleteFileAfterSend(true);
            }
        }
        elseif ($request->request_type == 'get_invoice_html') {
            $OrderMaster = OrderMaster::find($request->orderID);
            if (!empty($OrderMaster) && !empty($OrderMaster->invoice)) {

                $file = 'documents/Invoice_'. $OrderMaster->invoice_id .'_'. time() . '.pdf';
                $pdfname = public_path($file);
                $final_html = parent::convert_image_base64($OrderMaster->invoice);
                PDF::loadHTML(html_entity_decode($final_html))->save($pdfname);
                // echo $this->site . $file;
                // exit;

                $responce['status'] = 1;
                $responce['data'] = $this->site . $file;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Invoice for this order is not available.';
            }
        }
        elseif ($request->request_type == 'get_confirm_html') {
            $OrderMaster = OrderMaster::find($request->orderID);
            if (!empty($OrderMaster) && !empty($OrderMaster->confimation_voucher)) {

                $file = 'documents/Confirm_voucher_'. $OrderMaster->invoice_id .'_'. time() . '.pdf';
                $pdfname = public_path($file);
                $final_html = parent::convert_image_base64($OrderMaster->confimation_voucher);
                PDF::loadHTML(html_entity_decode($final_html))->save($pdfname);
                // echo $this->site . $file;
                // exit;

                $responce['status'] = 1;
                $responce['data'] = $this->site . $file;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Confirmation voucher for this order is not available.';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function addTour() {
        if (!(parent::checkWritePrivilege(20))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $Attributes = ServiceAttribute::where('service', 'tour')->pluck('name', 'id');
        $CarAttributes = array();
        foreach ($Attributes as $key => $value) {
            $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
            $CarAttributes[$value] = $AttributeValue;
        }
        $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();
        if (Auth::user()->access_type == 'superadmin') {
            $MasterHotel = MasterHotel::where('status', 'publish')->get();
        } else {
            $vendorId = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterHotel = MasterHotel::where(['status' => 'publish', 'vender_id' => $vendorId])->get();
        }
        $hotel_list = '';
        foreach ($MasterHotel as $value) {
            $hotel_list .= '<option value="' . $value->name . '~' . $value->id . '">' . $value->name . '</option>';
        }
        $Days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

        $SubUser = array();
        if (Auth::user()->role == 2) {
            $SubUserData = User::where('vendor_id', Auth::user()->id)->Where('access_type', '<>', 'agent')->orderBy('first_name', 'asc')->get();
            if (!empty($SubUserData)) {
                foreach ($SubUserData as $value) {
                    $SubUser[$value->id] = $value->first_name . ' ' . $value->last_name;
                }
            }
        }

        return view('tours.add-tour', compact('Vendors', 'CarAttributes', 'CityDetail', 'hotel_list', 'Days', 'SubUser'));
    }

    public function tourAddRequest(Request $request) {
    //    echo "<pre>";print_r($request->all());exit;
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required',
                    'name' => 'required|string|min:3|max:100',
                    'content' => 'required|string',
                    'feature_image' => 'required|mimes:jpeg,png,jpg',
                    'banner_image' => 'required|mimes:jpeg,png,jpg',
                    'images.*' => 'required|mimes:jpeg,png,jpg',
                    'address' => 'string',
                    'city' => 'required|string',
                    'status' => 'required|string',
                    'single_share_price' => 'required|numeric',
                    'double_share_price' => 'numeric',
                    'triple_share_price' => 'numeric',
                    'child_price' => 'numeric',
                    'terms_conditions' => 'required|string',
                    'contact_number' => 'required|digits:10',
                    'contact_email' => 'required|email',
                    'show_price' => 'required',
                    'minimum_people_for_single_booking' => 'numeric',
                    'maximum_people_for_single_booking' => 'numeric',
                    'is_special_tour' => 'numeric',
                    'days_from_start_date' => 'numeric',
                //    'start_date' => 'required',
                //    'gst_number' => 'string',
                //    'gst_legal_name' => 'string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('tour-add')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/tours/';
            $gallery_images = array();
            $banner_image = '';
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                }
            }
            $feature_image = '';
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                    $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $file->extension();
                    $file->move(public_path($UploadDir), $gallery_image);
                    array_push($gallery_images, $UploadDir . $gallery_image);
                }
            }
            $itinerary = '';
            if ($request->itinerary) {
                $data = array();
                $count = 0;
                foreach ($request->itinerary as $key => $value) {
                    $data[$count]['title'] = $value['title'];
                    $data[$count]['content'] = $value['content'];
                    if ($value['hotel'] == '') {
                        $data[$count]['hotel'] = "No Accommodation";
                    } elseif ($value['hotel'] == 'other') {
                        $data[$count]['hotelName'] = $value['hotelName'];
                        $data[$count]['hotel'] = $value['hotel'];
                        $data[$count]['roomName'] = $value['room'];
                    } else {
                        $hotel = explode('~', $value['hotel']);
                        $data[$count]['hotelName'] = $hotel[0];
                        $data[$count]['hotel'] = $hotel[1];
                        $room = explode('~', $value['room']);
                        $data[$count]['hotelRoom'] = $room[1];
                        $data[$count]['roomName'] = $room[0];
                    }
                    $data[$count]['meals'] = $value['meals'];
                    $data[$count]['places'] = $value['places'];
                    $count++;
                }
                $itinerary = json_encode($data);
            }
            $include = '';
            if (isset($request->include)) {
                $data = array();
                foreach ($request->include as $value) {
                    array_push($data, array('title' => $value['title'], 'content' => $value['content']));
                }
                $include = json_encode($data);
            }
            $exclude = '';
            if (isset($request->exclude)) {
                $data = array();
                foreach ($request->exclude as $value) {
                    array_push($data, array('title' => $value['title'], 'content' => $value['content']));
                }
                $exclude = json_encode($data);
            }
            $slug = str_replace(['  ', ' '], '-', strtolower(parent::cleanStringSlug($request->name)));
            $AttributeValues = AttributeValue::pluck('icon', 'id');
            $Property = $request->property;
            $property_slug_array = array();
            foreach ($Property as $key1 => $attribute) {
                foreach ($attribute as $key2 => $terms) {
                    $temp = explode('~', $terms);
                    $Property[$key1][$key2] = array('name' => $temp[1], 'icon' => $AttributeValues[$temp[0]]);
                    $property_slug_array[] = $temp[1];
                }
            }
            $duration_start = $request->duration_start;
            $duration_end = $request->duration_end;
            $duration_start_text = $duration_end_text = '';
            if ($request->category == 'package') {
                $duration_start_text = $request->duration_start_text;
                $duration_end_text = $request->duration_end_text;
            } else {
                $duration_start = date("h:i", strtotime($request->duration_start));
                $duration_end = date("h:i", strtotime($request->duration_end));
                $duration_start_text = date("a", strtotime($request->duration_start));
                $duration_end_text = date("a", strtotime($request->duration_end));
            }
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) { return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) { return !empty(trim($n)); });

            $request->minimum_people_for_single_booking = (isset($request->minimum_people_for_single_booking) && !empty($request->minimum_people_for_single_booking)) ? $request->minimum_people_for_single_booking : 0;
            $request->maximum_people_for_single_booking = (isset($request->maximum_people_for_single_booking) && !empty($request->maximum_people_for_single_booking)) ? $request->maximum_people_for_single_booking : 0;

            if($request->maximum_people_for_single_booking < $request->minimum_people_for_single_booking){
                $request->maximum_people_for_single_booking = $request->minimum_people_for_single_booking;
            }

            $Tour = new Tour([
                'vendor_id' => $request->vendor_id,
                'name' => trim($request->name),
                'slug' => $slug,
                'content' => addslashes($request->content),
                'feature_image' => $UploadDir . $feature_image,
                'banner_image' => $UploadDir . $banner_image,
                'gallery' => json_encode($gallery_images),
                'category' => $request->category,
                'city' => $request->city,
                'address' => addslashes($request->address),
                'terms_conditions' => $request->terms_conditions,
                'contact_email' => $request->contact_email,
                'contact_number' => $request->contact_number,
                'additional_email' => implode(",", $add_email),
                'additional_phone' => implode(",", $add_phone),
                'map_lat' => $request->map_lat,
                'map_lng' => $request->map_lng,
                'video' => $request->video,
                'single_share_price' => $request->single_share_price,
                'double_share_price' => $request->double_share_price,
                'triple_share_price' => $request->triple_share_price,
                'child_price' => $request->child_price,
                'single_share_policy' => ($request->sop) ? json_encode(array_values($request->sop)) : json_encode([]),
                'double_share_policy' => ($request->dop) ? json_encode(array_values($request->dop)) : json_encode([]),
                'triple_share_policy' => ($request->top) ? json_encode(array_values($request->top)) : json_encode([]),
                'child_price_policy' => ($request->cp) ? json_encode(array_values($request->cp)) : json_encode([]),
                'duration_start' => $request->duration_start,
                'duration_end' => $request->duration_end,
                'duration_start_text' => $duration_start_text,
                'duration_end_text' => $duration_end_text,
                'max_people' => $request->max_people,
                'minimum_people_for_single_booking' => $request->minimum_people_for_single_booking,
                'maximum_people_for_single_booking' => $request->maximum_people_for_single_booking,
                'is_special_tour' => $request->is_special_tour,
                'itinerary' => $itinerary,
                'include' => $include,
                'exclude' => $exclude,
                'property' => json_encode($Property),
                'property_slug' => implode("~", $property_slug_array),
                'gst_applicable' => $request->gst_applicable,
                'not_available' => !empty($request->not_available) ? json_encode($request->not_available) : json_encode([]),
                'status' => $request->status,
                'create_user' => Auth::user()->id,
                'show_price' => $request->show_price,
                'gst_number' => $request->gst_number,
                'gst_legal_name' => $request->gst_legal_name,
            //    'paytm_mid' => $request->paytm_mid,
            //    'hdfc_mid' => $request->hdfc_mid,
                'start_date' => (!empty($request->start_date)) ? date("Y-m-d", strtotime($request->start_date)) : '',
                'days_from_start_date' => (!empty($request->days_from_start_date)) ? $request->days_from_start_date : 1
            ]);
            if ($Tour->save()) {
                $TourId = $Tour->id;
                if (Auth::user()->role == 2) {
                    if (!empty($request->sub_user)) {
                        $userAccess = array();
                        foreach ($request->sub_user as $value) {
                            $userAccess[] = array(
                                'user_id' => $value,
                                'service' => 'tour',
                                'service_id' => $TourId
                            );
                        }
                        DB::table('subuser_access')->insertOrIgnore($userAccess);
                    }
                }


                Session::flash('success', 'Tour added successful.');
                return Redirect::to('all-tours');
            } else {
                Session::flash('success', 'Unable to add Tour');
                return Redirect::to('tour-add');
            }
        }
    }

    public function editTour($id = null) {
        if (!(parent::checkWritePrivilege(20))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $TourDetailsQry = Tour::where('id', $id);
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $TourDetailsQry->where('vendor_id', $vendor_id);
        }
        $TourDetails = $TourDetailsQry->first();
        if (!empty($TourDetails)) {
            $TourDetails->feature_image = $this->site . $TourDetails->feature_image;
            $TourDetails->banner_image = $this->site . $TourDetails->banner_image;
            $TourDetails->gallery = json_decode($TourDetails->gallery, 1);
            $TourDetails->single_share_policy = !empty($TourDetails->single_share_policy) ? json_decode($TourDetails->single_share_policy, 1) : [];
            $TourDetails->double_share_policy = !empty($TourDetails->double_share_policy) ? json_decode($TourDetails->double_share_policy, 1) : [];
            $TourDetails->triple_share_policy = !empty($TourDetails->triple_share_policy) ? json_decode($TourDetails->triple_share_policy, 1) : [];
            $TourDetails->child_price_policy = !empty($TourDetails->child_price_policy) ? json_decode($TourDetails->child_price_policy, 1) : [];
            $property = json_decode($TourDetails->property, 1);
            $data = array();
            if (!empty($property)) {
                foreach ($property as $key1 => $attribute) {
                    foreach ($attribute as $key2 => $terms) {
                        $data[$key1][$key2] = $terms['name'];
                    }
                }
            }
            $TourDetails->property = $data;
            $TourDetails->faqs = !empty($TourDetails->faqs) ? json_decode($TourDetails->faqs, 1) : [];
            $TourDetails->start_date = !empty($TourDetails->start_date) ? date("d-m-Y", strtotime($TourDetails->start_date)) : '';

            $TourDetails->include = !empty($TourDetails->include) ? json_decode($TourDetails->include, 1) : [];
            $TourDetails->exclude = !empty($TourDetails->exclude) ? json_decode($TourDetails->exclude, 1) : [];
            $gallery = array();
            $count = 1;
            foreach ($TourDetails->gallery as $value) {
                $gallery[] = ['id' => $count, 'src' => $this->site . $value];
                $count++;
            }
            $gallery = json_encode($gallery);
            $itinerary = !empty($TourDetails->itinerary) ? json_decode($TourDetails->itinerary, 1) : [];
            if (!empty($itinerary)) {
                foreach ($itinerary as $key => $val) {
                    if ($val['hotel'] != 'other') {
                        $roomData = array();
                        $HotelDetails = MasterHotel::find($val['hotel']);
                        if (!empty($HotelDetails)) {
                            $HotelRooms = HotelRoom::where(['hotel_id' => $HotelDetails->id, 'status' => 'publish'])->get();
                            foreach ($HotelRooms as $rooms) {
                                $selected = ($rooms->id == $val['hotelRoom']) ? 1 : 0;
                                array_push($roomData, array('title' => ($rooms->title . '~' . $rooms->id), 'selected' => $selected));
                            }
                        }
                        $itinerary[$key]['hotelRoom'] = $roomData;
                    }
                }
            }
            $TourDetails->itinerary = $itinerary;

            $Vendors = User::where('role', '2')->pluck('company', 'id');
            $Attributes = ServiceAttribute::where('service', 'tour')->pluck('name', 'id');
            $CarAttributes = array();
            foreach ($Attributes as $key => $value) {
                $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
                $CarAttributes[$value] = $AttributeValue;
            }
            $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();

            if (Auth::user()->access_type == 'superadmin') {
                $MasterHotel = MasterHotel::where('status', 'publish')->get();
            } else {
                $vendorId = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                $MasterHotel = MasterHotel::where(['status' => 'publish', 'vender_id' => $vendorId])->get();
            }
            $hotel_list = '';
            $hotel_array = array();
            foreach ($MasterHotel as $value) {
                $hotel_list .= '<option value="' . $value->name . '~' . $value->id . '">' . $value->name . '</option>';
                array_push($hotel_array, $value->name . '~' . $value->id);
            }
            $TourDetails->not_available = !empty($TourDetails->not_available) ? json_decode($TourDetails->not_available, 1) : [];
            $Days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

            $SubUser = array();
            if (Auth::user()->role == 2) {
                $SubUserData = User::where('vendor_id', Auth::user()->id)->Where('access_type', '<>', 'agent')->orderBy('first_name', 'asc')->get();
                if (!empty($SubUserData)) {
                    foreach ($SubUserData as $value) {
                        $checked = 0;
                        $SubuserAccess = SubuserAccess::where(['user_id' => $value->id, 'service' => 'tour', 'service_id' => $id])->first();
                        if (!empty($SubuserAccess)) {
                            $checked = 1;
                        }
                        $SubUser[] = array('id' => $value->id, 'name' => $value->first_name . ' ' . $value->last_name, 'checked' => $checked);
                    }
                }
            }
            $total_booked = $total_available = 0;
            if ($TourDetails->id == '34' || $TourDetails->id == '35' || $TourDetails->id == '36') {
                $BookData = OrderMaster::select(DB::raw('SUM(total_guests) as totQty'))
                            ->where(['service_type' => 'tour', 'service_name_id' => $TourDetails->id, 'start_date' => '2026-07-07'])
                            ->where('status', '!=', 'cancelled')
                            ->first();
                if (!empty($BookData->totQty)) {
                    $total_booked = $BookData->totQty;
                }
                $total_available = $TourDetails->max_people - $total_booked;
            }
            $TourDetails->total_booked = $total_booked;
            $TourDetails->total_available = $total_available;

            return view('tours.edit-tour', compact('Vendors', 'CarAttributes', 'CityDetail', 'TourDetails', 'gallery', 'hotel_list', 'hotel_array', 'Days', 'SubUser'));
        } else {
            return redirect()->back();
        }
    }

    public function tourEditRequest(Request $request) {

        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required',
                    'name' => 'required|string|min:3|max:100',
                    'content' => 'required|string',
                    'feature_image' => 'mimes:jpeg,png,jpg',
                    'banner_image' => 'mimes:jpeg,png,jpg',
                    'images.*' => 'mimes:jpeg,png,jpg',
                    'address' => 'string',
                    'city' => 'required|string',
                    'status' => 'required|string',
                    'single_share_price' => 'required|numeric',
                    'double_share_price' => 'numeric',
                    'triple_share_price' => 'numeric',
                    'child_price' => 'numeric',
                    'contact_number' => 'required|digits:10',
                    'contact_email' => 'required|email',
                    'show_price' => 'required',
                    'minimum_people_for_single_booking' => 'numeric',
                    'maximum_people_for_single_booking' => 'numeric',
                    'is_special_tour' => 'numeric',
                    'days_from_start_date' => 'numeric',
//                    'gst_number' => 'string',
//                    'gst_legal_name' => 'string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('tour-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $slug = str_replace(['  ', ' '], '-', strtolower(parent::cleanStringSlug($request->name)));

            $Tour = Tour::find($request->id);

            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) { return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) { return !empty(trim($n)); });

            $Tour->vendor_id = $request->vendor_id;
            $Tour->name = trim($request->name);
            $Tour->slug = $slug;
            $Tour->content = addslashes($request->content);
            $Tour->category = $request->category;
            $Tour->city = $request->city;
            $Tour->address = addslashes($request->address);
            $Tour->map_lat = $request->map_lat;
            $Tour->map_lng = $request->map_lng;
            $Tour->video = $request->video;
            $Tour->single_share_price = $request->single_share_price;
            $Tour->double_share_price = $request->double_share_price;
            $Tour->triple_share_price = $request->triple_share_price;
            $Tour->child_price = $request->child_price;
            $Tour->single_share_policy = ($request->sop) ? json_encode(array_values($request->sop)) : json_encode([]);
            $Tour->double_share_policy = ($request->dop) ? json_encode(array_values($request->dop)) : json_encode([]);
            $Tour->triple_share_policy = ($request->top) ? json_encode(array_values($request->top)) : json_encode([]);
            $Tour->child_price_policy = ($request->cp) ? json_encode(array_values($request->cp)) : json_encode([]);
            $Tour->max_people = $request->max_people;
            $Tour->status = $request->status;
            $Tour->update_user = Auth::user()->id;
            $Tour->duration_start = $request->duration_start;
            $Tour->duration_end = $request->duration_end;
            $Tour->terms_conditions = $request->terms_conditions;
            $Tour->contact_email = $request->contact_email;
            $Tour->contact_number = $request->contact_number;
            $Tour->additional_email = implode(',', $add_email);
            $Tour->additional_phone = implode(',', $add_phone);
            $Tour->gst_applicable = $request->gst_applicable;
            $Tour->not_available = !empty($request->not_available) ? json_encode($request->not_available) : json_encode([]);
            $Tour->show_price = $request->show_price;
            $Tour->gst_number = $request->gst_number;
            $Tour->gst_legal_name = $request->gst_legal_name;
//            $Tour->paytm_mid = $request->paytm_mid;
//            $Tour->hdfc_mid = $request->hdfc_mid;
            $Tour->start_date = (!empty($request->start_date)) ? date("Y-m-d", strtotime($request->start_date)) : '';
            $Tour->days_from_start_date = (!empty($request->days_from_start_date)) ? $request->days_from_start_date : 1;

            $Tour->minimum_people_for_single_booking = (isset($request->minimum_people_for_single_booking) && !empty($request->minimum_people_for_single_booking)) ? $request->minimum_people_for_single_booking : 0;
            $Tour->maximum_people_for_single_booking = (isset($request->maximum_people_for_single_booking) && !empty($request->maximum_people_for_single_booking)) ? $request->maximum_people_for_single_booking : 0;
            $Tour->is_special_tour = $request->is_special_tour;

            if($Tour->maximum_people_for_single_booking < $Tour->minimum_people_for_single_booking){
                $Tour->maximum_people_for_single_booking = $Tour->minimum_people_for_single_booking;
            }

            $duration_start_text = $duration_end_text = '';
            if ($request->category == 'package') {
                $Tour->duration_start_text = $request->duration_start_text;
                $Tour->duration_end_text = $request->duration_end_text;
            } else {
                $Tour->duration_start = date("h:i", strtotime($request->duration_start));
                $Tour->duration_end = date("h:i", strtotime($request->duration_end));
                $Tour->duration_start_text = date("a", strtotime($request->duration_start));
                $Tour->duration_end_text = date("a", strtotime($request->duration_end));
            }

            $UploadDir = 'images/tours/';
            $gallery_images = array();
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $old_banner = public_path($Tour->banner_image);
                    if (file_exists($old_banner)) {
                        unlink($old_banner);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                    $Tour->banner_image = $UploadDir . $banner_image;
                }
            }
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $old_feature = public_path($Tour->feature_image);
                    if (file_exists($old_feature)) {
                        unlink($old_feature);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                    $Tour->feature_image = $UploadDir . $feature_image;
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                    $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $file->extension();
                    $file->move(public_path($UploadDir), $gallery_image);
                    array_push($gallery_images, $UploadDir . $gallery_image);
                }
            }
            $old_gallery = !empty($Tour->gallery) ? json_decode($Tour->gallery) : [];
            $preload_data = ($request->oldimage) ? $request->oldimage : [];
            if (count($preload_data) != count($old_gallery)) {
                $temp = 1;
                foreach ($old_gallery as $key => $value) {
                    if (!in_array($temp, $preload_data)) {
                        if (file_exists(public_path($value))) {
                            unlink(public_path($value));
                            unset($old_gallery[$key]);
                        }
                    }
                    $temp++;
                }
            }
            $gallery_images = array_merge($old_gallery, $gallery_images);
            $Tour->gallery = json_encode($gallery_images);

            $itinerary = '';
            if ($request->itinerary) {
                $data = array();
                $count = 0;
                foreach ($request->itinerary as $key => $value) {
                    $data[$count]['title'] = $value['title'];
                    $data[$count]['content'] = $value['content'];
                    if ($value['hotel'] == '') {
                        $data[$count]['hotel'] = "No Accommodation";
                    } elseif ($value['hotel'] == 'other') {
                        $data[$count]['hotelName'] = $value['hotelName'];
                        $data[$count]['hotel'] = $value['hotel'];
                        $data[$count]['roomName'] = $value['room'];
                    } else {
                        $hotel = explode('~', $value['hotel']);
                        $data[$count]['hotelName'] = $hotel[0];
                        $data[$count]['hotel'] = $hotel[1];
                        $room = explode('~', $value['room']);
                        $data[$count]['hotelRoom'] = $room[1];
                        $data[$count]['roomName'] = $room[0];
                    }
                    $data[$count]['meals'] = $value['meals'];
                    $data[$count]['places'] = $value['places'];
                    $count++;
                }
                $itinerary = json_encode($data);
            }
            $Tour->itinerary = $itinerary;
            $Tour->include = '';
            if (isset($request->include)) {
                $data = array();
                foreach ($request->include as $value) {
                    array_push($data, array('title' => $value['title'], 'content' => $value['content']));
                }
                $Tour->include = json_encode($data);
            }
            $Tour->exclude = '';
            if (isset($request->exclude)) {
                $data = array();
                foreach ($request->exclude as $value) {
                    array_push($data, array('title' => $value['title'], 'content' => $value['content']));
                }
                $Tour->exclude = json_encode($data);
            }

            $AttributeValues = AttributeValue::pluck('icon', 'id');
            $Property = $request->property;
            $property_slug_array = array();
            if (!empty($Property)) {
                foreach ($Property as $key1 => $attribute) {
                    foreach ($attribute as $key2 => $terms) {
                        $temp = explode('~', $terms);
                        $Property[$key1][$key2] = array('name' => $temp[1], 'icon' => $AttributeValues[$temp[0]]);
                        $property_slug_array[] = $temp[1];
                    }
                }
            }
            $Tour->property_slug = implode("~", $property_slug_array);
            $Tour->property = json_encode($Property);
            // echo "<pre>";print_r($Tour);exit;
            if ($Tour->save()) {
                $TourId = $Tour->id;
                if (Auth::user()->role == 2) {
                    DB::table('subuser_access')->where(['service' => 'tour', 'service_id' => $TourId])->delete();
                    if (!empty($request->sub_user)) {
                        $userAccess = array();
                        foreach ($request->sub_user as $value) {
                            $userAccess[] = array(
                                'user_id' => $value,
                                'service' => 'tour',
                                'service_id' => $TourId
                            );
                        }
                        DB::table('subuser_access')->insertOrIgnore($userAccess);
                    }
                }

                Session::flash('success', 'Tour details updated successful.');
                return Redirect::to('all-tours');
            } else {
                Session::flash('success', 'Unable to update Tour details!');
                return Redirect::to('tour-edit/' . $request->id);
            }
        }
    }

    public function manageTourRoutes($id = null) {
        if (!(parent::checkViewPrivilege(20))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $TourDetails = Tour::find($id);
        $itinerary = !empty($TourDetails->itinerary) ? json_decode($TourDetails->itinerary, 1) : [];
        $TourId = $TourDetails->id;
        $default_map = array();
        if (!empty($TourDetails->route_map)) {
            $route_map = json_decode($TourDetails->route_map, 1);
            $default_map = $route_map[array_key_first($route_map)];
        }

        return view('tours.manage-routes', compact('itinerary', 'TourId', 'default_map'));
    }

    public function tourRoutesrequest(Request $request) {
        if (!(parent::checkWritePrivilege(20))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        if (!isset($request->route)) {
            Session::flash('success', 'Unable to add Tour route!');
            return Redirect::to('manage-tour-routes/' . $request->id);
        } else {
            $Tour = Tour::find($request->id);
            $routeData = json_decode($Tour->route_map, 1);
            $routeData[$request->route_map] = array_merge($request->route);
            $Tour->route_map = json_encode($routeData);

            if ($Tour->save()) {
                Session::flash('success', 'Tour route saved successfully.');
                return Redirect::to('manage-tour-routes/' . $request->id);
            } else {
                Session::flash('success', 'Unable to update Tour route!');
                return Redirect::to('manage-tour-routes/' . $request->id);
            }
        }
    }

    public function tourAttribute() {
        if (!(parent::checkViewPrivilege(22))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $TourAttributes = ServiceAttribute::where('service', 'tour')->get();

        return view('tours.tour-attributes', compact('TourAttributes'));
    }

    public function tourAttributeAddRequest(Request $request) {
        if (!(parent::checkWritePrivilege(22))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
                    'name' => 'required|string',
//                    'status' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('tour-attribute')->withErrors($validate)->withInput();
        } else {
            $CarAttribute = new ServiceAttribute([
                'name' => $request->name,
                'service' => 'tour',
//                'status' => $request->status
            ]);
            if ($CarAttribute->save()) {
                Session::flash('success', 'Tour attribute added successful.');
                return Redirect::to('tour-attribute');
            } else {
                Session::flash('success', 'Unable to add attribute');
                return Redirect::to('tour-attribute');
            }
        }
    }

    public function tourAttributeTerm($id = null) {
        if (!(parent::checkViewPrivilege(22))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $ServiceAttribute = ServiceAttribute::find($id);
        $AttributeTerms = AttributeValue::where('attr_id', $id)->get();
        $site_url = $this->site;

        return view('tours.attribute-terms', compact('ServiceAttribute', 'AttributeTerms', 'site_url'));
    }

    public function tourAttributeTermAddRequest(Request $request) {
        if (!(parent::checkWritePrivilege(22))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
//            'status' => 'required|string',
            'attr_id' => 'required|numeric',
            'icon' => 'mimes:jpeg,png,jpg'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('tour-attribute-terms/' . $request->attr_id)->withErrors($validate)->withInput();
        } else {
            $icon_image = '';
            $UploadDir = 'images/attributes/';
            if ($request->hasFile('icon')) {
                if ($request->file('icon')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('icon')->getClientOriginalName());
                    $icon = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->icon->extension();
                    $request->icon->move(public_path($UploadDir), $icon);
                    $icon_image = $UploadDir . $icon;
                }
            }

            $AttributeTerm = new AttributeValue([
                'name' => $request->name,
                'attr_id' => $request->attr_id,
                'icon' => $icon_image,
//                'status' => $request->status
            ]);
            if ($AttributeTerm->save()) {
                Session::flash('success', 'Attribute term added successful.');
                return Redirect::to('tour-attribute-terms/' . $request->attr_id);
            } else {
                Session::flash('success', 'Unable to add attribute term');
                return Redirect::to('tour-attribute-terms/' . $request->attr_id);
            }
        }
    }

    public function sightSeenPricing() {
        if (!(parent::checkViewPrivilege(61))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $Tour = Tour::where(['status' => 'publish', 'vendor_id' => $vendor_id])->where('category', 'sight seeing')->pluck('name', 'id');

        return view('tours.sight-seen-pricing', compact('Tour'));
    }

    public function getSightSeenPricing(Request $request) {

        $aColumns = array('tour_name', 'price_plan', 'offer_percentage', 'end_date', 'id');
        $sIndexColumn = "id";
        $sTable = "sight_seen_pricing";
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
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $sWhere .= ' AND tour_id in (' . implode(',', $SubuserAccess) . ')';
            }
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
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere GROUP BY price_plan $sOrder $sLimit";
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

            $discount_type = ($aRow->offer_type == 'plus') ? '+' : '-';
            $row[] = $aRow->tour_name;
            $row[] = $aRow->price_plan;
            $row[] = "(" . $discount_type . ")" . $aRow->offer_percentage;
            $row[] = $aRow->end_date; //date("M d Y", strtotime($aRow->start_date));
            $row[] = '<a href="javascript:void(0)" class="delete-data" data-plan="' . $aRow->price_plan . '">Delete</a>';
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function addSightSeenPricing() {
        if (!(parent::checkWritePrivilege(61))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TourQry = Tour::where(['status' => 'publish', 'vendor_id' => $vendor_id])->where('category', 'sight seeing');
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TourQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Tour = $TourQry->orderBy('name', 'ASC')->pluck('name', 'id');

        return view('tours.add-sight-seen-pricing', compact('Tour'));
    }

    public function addSightSeenPricingRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'tour_id' => 'required|numeric',
                    'tour_name' => 'required|string',
                    'price_plan' => 'required|string|unique:sight_seen_pricing',
                    'offer_percentage' => 'required|numeric',
                    'offer_type' => 'required|string',
                    'check_date' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-sight-seen-pricing')->withErrors($validate)->withInput();
        } else {
            $check_date = explode(' - ', $request->check_date);
            $start_date = date("Y-m-d", strtotime($check_date[0]));
            $end_date = date("Y-m-d", strtotime($check_date[1]));
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;

            $OldPricing = SightSeenPricing::where('tour_id', $request->tour_id)
                            ->where('start_date', '>=', $start_date)
                            ->where('start_date', '<=', $end_date)
                            ->get()->toArray();
            if (empty($OldPricing)) {
                $save_status = 0;
                if ($start_date != $end_date) {
                    $difference = strtotime($end_date) - strtotime($start_date);
                    $days = round($difference / (60 * 60 * 24));
                    $Pricing_data = array();
                    for ($i = 0; $i <= $days; $i++) {
                        $date = date("Y-m-d", strtotime($start_date . ' + ' . $i . ' days'));
                        $Pricing_data[$i] = [
                            'vendor_id' => $vendor_id,
                            'tour_id' => $request->tour_id,
                            'tour_name' => $request->tour_name,
                            'price_plan' => $request->price_plan,
                            'offer_percentage' => $request->offer_percentage,
                            'offer_type' => $request->offer_type,
                            'start_date' => $date,
                            'end_date' => $request->check_date,
                            'created_by' => Auth::user()->id
                        ];
                    }
                    $save_status = SightSeenPricing::insert($Pricing_data);
                } else {
                    $SightSeenPricing = new SightSeenPricing([
                        'vendor_id' => $vendor_id,
                        'tour_id' => $request->tour_id,
                        'tour_name' => $request->tour_name,
                        'price_plan' => $request->price_plan,
                        'offer_percentage' => $request->offer_percentage,
                        'offer_type' => $request->offer_type,
                        'start_date' => $date,
                        'end_date' => $request->check_date,
                        'created_by' => Auth::user()->id
                    ]);
                    $save_status = $SightSeenPricing->save();
                }
                if ($save_status) {
                    Session::flash('success', 'Sight Seen Pricing saved successful.');
                    return Redirect::to('sight-seen-pricing');
                } else {
                    Session::flash('success', 'Unable to add pricing.');
                    return Redirect::to('add-sight-seen-pricing');
                }
            } else {
                Session::flash('success', 'There is already a pricing present on this date. please change the date and try again!');
                return Redirect::to('add-sight-seen-pricing');
            }
        }
    }

    public function tourBlockData() {
        if (!(parent::checkViewPrivilege(62))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('tours.tour-block-data');
    }

    public function getTourBlockData(Request $request) {

        $aColumns = array('id', 'tour_name', 'block_date', 'block_reason');
        $sIndexColumn = "id";
        $sTable = "tour_availability";
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
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $sWhere .= ' AND tour_id in (' . implode(',', $SubuserAccess) . ')';
            }
        }
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $condition1 .= ' AND vender_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $condition2 .= ' AND name LIKE "%' . $_POST['searchValue2'] . '%"';
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

            // $status = ($aRow->status == '1') ? 'Activate' : 'Deactivate';

            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            $row[] = $aRow->tour_name;
            $row[] = date("d M Y", strtotime($aRow->block_date));
            $row[] = $aRow->block_reason;
            // $row[] = ($aRow->status == '1') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #717171;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Blocked</span>' : '<span style="background-color: #28a745;font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">Available</span>';
            $row[] =  '<a href="javascript:void(0);" class="btn btn-danger delete-data" data-id="' . $aRow->id . '"><i class="fa fa-trash"></i> Delete</a>';
            // $row[] = '<div class="btn-group">
            //     <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
            //     <ul role="menu" class="dropdown-menu">
            //         <li></li>
            //     </ul>
            // </div>';
//            <li><a href="javascript:void(0);" class="change-status" data-id="'. $aRow->id .'" data-status="'. $aRow->status .'">'. $status .'</a></li>
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function blockTour() {
        if (!(parent::checkWritePrivilege(62))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TourQry = Tour::where('status', 'publish')->where('vendor_id', $vender_id);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TourQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Tour = $TourQry->orderBy('name', 'ASC')->pluck('name', 'id');

        return view('tours.block-tour', compact('Tour'));
    }

    public function blockTourRequest(Request $request) {
        $validate = Validator::make($request->all(), [
            'tour_id' => 'required|numeric',
            'block_date' => 'required|string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('block-tour')->withErrors($validate)->withInput();
        } else {
            $Tour = Tour::find($request->tour_id);
            $block_date = explode(' - ', $request->block_date);

            $difference = strtotime(date("Y-m-d", strtotime($block_date[1]))) - strtotime(date("Y-m-d", strtotime($block_date[0])));
            $days = round($difference / (60 * 60 * 24));
            $AvailabilityData = array();
            for ($i = 0; $i <= $days; $i++) {
                $date = date("Y-m-d", strtotime($block_date[0] . ' + ' . $i . ' days'));
                $AvailabilityData = [
                    'vendor_id' => $Tour->vendor_id,
                    'tour_id' => $request->tour_id,
                    'tour_name' => $Tour->name,
                    'block_date' => $date,
                    'block_reason' => addslashes($request->block_reason),
                    'created_by' => Auth::user()->id,
                    'status' => 1
                ];
                DB::table('tour_availability')->insertOrIgnore($AvailabilityData);
            }
            Session::flash('success', 'Tour blocked successful.');
            return Redirect::to('tour-block-data');
        }
    }

    public function tourBooking() {
        if (!(parent::checkViewPrivilege(63))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $tour_id = '';
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime('+1 days'));
        $OrderData = array();
        if (isset($_POST['tour_id']) && isset($_POST['check_date'])) {
            $tour_id = $_POST['tour_id'];
            $check_date = explode(" - ", $_POST['check_date']);
            $start_date = date("Y-m-d", strtotime($check_date[0]));
            $end_date = date("Y-m-d", strtotime($check_date[1]));
            $difference = strtotime($end_date) - strtotime($start_date);
            $days = floor($difference / (60 * 60 * 24));
            for($i = 0; $i <= $days; $i++) {
                $checkDate = date("Y-m-d", strtotime($start_date .' + '. $i .' days'));
                $CompleteData = DB::table('order_masters')->select(DB::raw('SUM(total_guests) as totQty'))
                        ->where(['service_type' => 'tour', 'service_name_id' => $tour_id, 'start_date' => $checkDate, 'status' => 'completed'])
                        ->first();
                $PendingData = DB::table('order_masters')->select(DB::raw('SUM(total_guests) as totQty'))
                        ->where(['service_type' => 'tour', 'service_name_id' => $tour_id, 'start_date' => $checkDate, 'status' => 'pending'])
                        ->first();
                $CancelData = DB::table('order_masters')->select(DB::raw('SUM(total_guests) as totQty'))
                        ->where(['service_type' => 'tour', 'service_name_id' => $tour_id, 'start_date' => $checkDate, 'status' => 'cancelled', 'payment_status' => 'success'])
                        ->first();

                $OrderData[] = array(
                    'date' => $checkDate,
                    'completeQty' => !is_null($CompleteData->totQty) ? $CompleteData->totQty : 0,
                    'pendingQty' => !is_null($PendingData->totQty) ? $PendingData->totQty : 0,
                    'cancelQty' => !is_null($CancelData->totQty) ? $CancelData->totQty : 0
                );
            }
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TourQry = Tour::where('vendor_id', $vender_id);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TourQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Tour = $TourQry->orderBy('name', 'ASC')->pluck('name', 'id');
        return view('tours.tour-booking', compact('Tour', 'tour_id', 'start_date', 'end_date', 'OrderData'));
    }

    public function tourOfflineOrder() {
        if (!(parent::checkViewPrivilege(64))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $CountryData = Country::pluck('name', 'id');
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TourQry = Tour::where(['status' => 'publish', 'vendor_id' => $vendor_id, 'category' => 'sight seeing']);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TourQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Tour = $TourQry->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();

        return view('tours.tour-offline-order', compact('Tour', 'CountryData'));
    }

    public function createTourOrder(Request $request) {
        if (!(parent::checkWritePrivilege(64))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        // echo "<pre>";print_r($request->all());exit;
        $state = !empty($request->customer_state) ? explode('~', $request->customer_state) : [];
        $country = !empty($request->customer_country) ? explode('~', $request->customer_country) : [];

        $TourData = Tour::find($request->service_name_id);
        $total_guests = ($TourData->category == 'sight seeing') ? $request->total_adults : $request->total_adults + $request->total_child;

        $BlockData = TourAvailability::where(['tour_id' => $TourData->id, 'block_date' => date('Y-m-d', strtotime($request->start_date))])->first();
        if (!empty($BlockData)) {
            Session::flash('success', 'Unable to create order. Sorry!, No tickets available for this date. Please choose different date.');
            return Redirect::to('tour-offline-order');
        }

        $OrderDetails = DB::table('order_masters')
                ->select(DB::raw('SUM(total_guests) as totQty'))
                ->where('service_name_id', $TourData->id)
                ->where('start_date', date("Y-m-d", strtotime($request->start_date)))
                ->where('status', '!=', 'cancelled')
                ->first();
        if (!empty($OrderDetails->totQty) && $OrderDetails->totQty > 0 && ($TourData->vendor_id != 1 || $TourData->category != 'package')) {
            $TourData->max_people = $TourData->max_people - $OrderDetails->totQty;
            if ($TourData->max_people < $total_guests) {
                Session::flash('success', 'Unable to create order. Sorry!, No tickets available for this date. Please choose different date.');
                return Redirect::to('tour-offline-order');
            }
        }
        $price_break = array();
        if ($TourData->category == 'package') {
            $Itinerary = !empty($TourData->itinerary) ? json_decode($TourData->itinerary, 1) : [];
            $checkoutDate = date('Y-m-d', strtotime($request->start_date . ' + ' . ($TourData->duration_end - 1) . ' days'));
            $difference = strtotime($checkoutDate) - strtotime($request->start_date);
            $days = round($difference / (60 * 60 * 24));
            $check_room = ($request->adult > 3) ? 2 : 1;
            $i = 0;
            foreach ($Itinerary as $itnrs) {
                if ($itnrs['hotel'] != 'other' && $itnrs['hotel'] != 'No Accommodation') {
                    $MaterHotel = MasterHotel::where(['id' => $itnrs['hotel'], 'status' => 'publish'])->first();
                    if (!empty($MaterHotel)) {
                        // for ($i = 0; $i < $days; $i++) {
                            $checkDate = date("Y-m-d", strtotime($request->start_date . ' + ' . $i . ' days'));
                            $HotelRoom = HotelRoom::where(['id' => $itnrs['hotelRoom'], 'status' => 'publish'])->first();
                            if (!empty($HotelRoom)) {
                                $MasterInventory = MasterInventory::where(["date" => $checkDate, 'room_id' => $itnrs['hotelRoom']])->first();
                                $room_qty = (!empty($MasterInventory->total_available)) ? $MasterInventory->total_available : 0;
                                if ($room_qty < $check_room) {
                                    Session::flash('success', "Sorry!, couldn't book tour as hotels not available. Please change date and try again.");
                                    return Redirect::to('tour-offline-order');
                                }
                            } else {
                                Session::flash('success', "Sorry!, couldn't book tour as hotels not available. Please change date and try again.");
                                return Redirect::to('tour-offline-order');
                            }
                        // }
                    } else {
                        Session::flash('success', "Sorry!, couldn't book tour as hotels not available. Please change date and try again.");
                        return Redirect::to('tour-offline-order');
                    }
                }
                $i++;
            }
        }
        else {
            $weekDay = date("l", strtotime($request->start_date));
            $TourData->not_available = !empty($TourData->not_available) ? json_decode($TourData->not_available, 1) : [];
            if (in_array($weekDay, $TourData->not_available)) {
                Session::flash('success', 'Tour is not available on ' . $weekDay . '. Please choose different date!');
                return Redirect::to('tour-offline-order');
            }
            $price_break['actual_price'] = $TourData->single_share_price * $request->total_adults;
            $price_break['seasonal_type'] = 'none';
            $SightSeenPricing = SightSeenPricing::where(['tour_id' => $TourData->id, 'start_date' => date("Y-m-d", strtotime($request->start_date))])->first();
            if (!empty($SightSeenPricing)) {
                $discount_amount = $SightSeenPricing->offer_percentage; // $TourData->single_share_price * ($SightSeenPricing->offer_percentage / 100);
                if ($SightSeenPricing->offer_type == 'plus') {
                    $TourData->single_share_price = round($TourData->single_share_price + $discount_amount);
                    $price_break['seasonal_type'] = 'plus';
                } else {
                    $TourData->single_share_price = round($TourData->single_share_price - $discount_amount);
                    $price_break['seasonal_type'] = 'minus';
                }
            }
            $price_break['seasonal_price'] = round($TourData->single_share_price * $request->total_adults, 2);
        }

        $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))->where('service_type', 'tour')->where('status', '!=', 'partially-cancelled')->first();
        if ($LastOrder->totOrder > 0) {
            $LastInvoiceId = (int) $LastOrder->totOrder + 1;
            $invoice_id = date('dmY') . 'T00' . $LastInvoiceId;
        } else {
            $invoice_id = date('dmY') . 'T001';
        }
        $vendor_id = $TourData->vendor_id;
        $vendorData = User::find($vendor_id);
        $vendor_name = $vendorData->company;
        $start_date = date("Y-m-d", strtotime($request->start_date));
        $end_date = null;
        if ($TourData->category == 'package') {
            $end_date = date('Y-m-d', strtotime($request->start_date . ' + ' . ($TourData->duration_end - 1) . ' days'));
        }
        $lastIdData = OrderMaster::orderBy('id', 'desc')->first();
        $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
        $booking_id = 'OT-' . time() . '-' . $vendor_id . '-' . $lastId;

        $userId = null;
        if ((Auth::user()->user_role == 'agent_staff')) {
            $userId = Auth::user()->id;
        } else {
            $UserDetails = User::where('email', $request->customer_email)->orWhere('phone', $request->customer_phone)->first();
            if (!empty($UserDetails)) {
                $userId = $UserDetails->id;
            } else {
                $NewUser = new User([
                    'email' => $request->customer_email,
                    'password' => bcrypt(rand(10000000, 99999999)),
                    'first_name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'pincode' => $request->zipcode,
                    'address' => $request->customer_address1,
                    'address2' => $request->address2,
                    'country' => !empty($country) ? $country[1] : null,
                    'state' => !empty($state) ? $state[1] : null,
                    'city' => $request->customer_city,
                    'vendor_id' => 0,
                    'role' => 4,
                    'access_type' => 'customer',
                    'status' => 1,
                    'create_account_approval' => 0
                ]);
                if ($NewUser->save()) {
                    $userId = $NewUser->id;
                }
            }
        }

        $status = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'completed' : 'pending';
        $payment_status = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'success' : 'pending';
        $payment_method = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'cash' : '';
        $Paytm_Mid = ''; $split_status = 0;
        if ($request->payment_gateway == 'paytm') {
            $Paytm_Mid = $TourData->paytm_mid;
            $split_status = 1;
        } elseif ($request->payment_gateway == 'hdfc') {
            $Paytm_Mid = $TourData->hdfc_mid;
            $split_status = 0;
        }
        $admin_amount = $vendor_amount = 0;
        if (!empty($vendorData)) {
            $admin_percent = $vendorData->admin_commission;
            if (!empty($Paytm_Mid) && $admin_percent > 0) {
                $vendor_amount = $request->total_order_price - ceil($request->total_order_price * ($admin_percent / 100));
                $admin_amount = $request->total_order_price - $vendor_amount;
            } else {
                $admin_amount = $request->total_order_price;
                $vendor_amount = 0;
            }
        }
        $order_type = (Auth::user()->user_role == 'agent_staff') ? Auth::user()->first_name : 'offline';
        $seat_no = 'N/A';
        if ($payment_status == 'success' && $TourData->category == 'sight seeing') {
            $seatDate = parent::maxDateForSeat($TourData->id);
            if ($seatDate < $start_date) {
                $CustomerSeat = OrderMaster::select(DB::raw('sum(total_guests) as totSeat'))
                    ->where(['service_type' => 'tour', 'service_name_id' => $TourData->id, 'payment_status' => 'success'])
                    // ->where('status', '!=', 'partially-cancelled')
                    ->where('start_date', date("Y-m-d", strtotime($start_date)))
                    ->first();
                $seatArr = array();
                $i = 1;
                while ($i <=  $total_guests) {
                    array_push($seatArr, $CustomerSeat->totSeat + $i);
                    $i++;
                }
                $seat_no = implode(',', $seatArr);
            }
        }

        $order_master = new OrderMaster([
            'order_id' => $booking_id,
            'invoice_id' => $invoice_id,
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendorData->company,
            'order_type' => $order_type,
            'customer_id' => $userId,
            'customer_name' => trim($request->customer_name),
            'customer_email' => trim($request->customer_email),
            'customer_phone' => trim($request->customer_phone),
            'customer_address1' => trim($request->customer_address1),
            'customer_city' => trim($request->customer_city),
            'customer_country' => !empty($country) ? $country[1] : null,
            'customer_state' => !empty($state) ? $state[1] : null,
            'customer_zipcode' => trim($request->customer_zipcode),
            'book_naration' => addslashes(trim($request->book_naration)),
            'service_type' => 'tour',
            'service_category' => $TourData->category,
            'service_name' => $TourData->name,
            'service_name_id' => $TourData->id,
            'service_city' => $TourData->city,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_adults' => $request->total_adults,
            'total_child' => $request->total_child,
            'total_guests' => $total_guests,
            'adult_price' => $request->adult_price,
            'child_price' => $request->child_price,
            'rental_breakdown' => json_encode($price_break),
            'total_service_price' => $request->total_service_price,
            'sub_total_price' => $request->sub_total_price,
            'coupon_name' => $request->coupon_name,
            'coupon_code' => $request->coupon_code,
            'coupon_amount' => $request->coupon_amount,
            'tax_percentage' => ($request->tax_amount > 0) ? $request->tax_percentage : 0,
            'tax_amount' => $request->tax_amount,
            'service_charge' => $request->service_charge,
            'total_order_price' => $request->total_order_price,
            'status' => $status,
            'payment_gateway' => $request->payment_gateway,
            'payment_method' => $payment_method,
            'split_initiate_status' => $split_status,
            'vendor_amount' => $vendor_amount,
            'payment_status' => $payment_status,
            'request_from' => 'web',
            'gate_number' => $seat_no
        ]);
        // echo "<pre>";print_r($order_master);exit;
        if ($order_master->save()) {
            $OrderMasterId = $order_master->id;
            $OrderMaster = $OrderMasterNew = OrderMaster::find($OrderMasterId);

            if($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                // Generate Qr Code
                require_once public_path('QrCode/generateQrCode.php');
                $QrCodeData = array(
                    'invoiceId' => $OrderMasterNew->invoice_id,
                    'orderId' => $OrderMasterNew->order_id,
                    'txnId' => $OrderMasterNew->transaction_id,
                    'serviceType' => $OrderMasterNew->service_type,
                );
                $QrCode = generateQrCode(json_encode($QrCodeData));
                $OrderMaster->qr_code = $QrCode;
                $OrderMaster->qr_verified = 0;
            }

            require_once public_path('paytm_lib/config_paytm.php');
            if ($request->payment_gateway == 'hdfc') {
                $PropertyAccount = PropertyAccount::where(['service_type' => $OrderMasterNew->service_type, 'service_id' => $OrderMasterNew->service_name_id])->first();
                if (!empty($PropertyAccount) && PAYTM_ENVIRONMENT == 'PROD') {
                    $HDFC_KEY = $PropertyAccount->hdfc_key;
                    $HDFC_SALT = $PropertyAccount->hdfc_salt;
                    $MERCHANT_ID = $PropertyAccount->hdfc_mid;
                }
                $txn_id = "TXN". time() . rand(10000, 99999999);

                $command = "create_invoice";
                $var1_arr = array(
                    'amount' => $OrderMasterNew->total_order_price,
                    'txnid' => $txn_id,
                    'productinfo' => parent::cleanString($OrderMasterNew->service_name),
                    'firstname' => $OrderMasterNew->customer_name,
                    'email' => $OrderMasterNew->customer_email,
                    'phone' => $OrderMasterNew->customer_phone,
                    'address1' => $OrderMasterNew->customer_address1,
                    'city' => $OrderMasterNew->customer_city,
                    'state' => $OrderMasterNew->customer_state,
                    'country' => $OrderMasterNew->customer_country,
                    'zipcode' => $OrderMasterNew->customer_zipcode,
                    'validation_period' => 30,
                    'send_email_now' => '1',
                    'send_sms' => '1',
                    'time_unit' => 'M'
                );
                $var1 = json_encode($var1_arr);
                $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                $hash = strtolower(hash('sha512', $hash_str));
                $r = array('key' => $HDFC_KEY, 'hash' => $hash, 'command' => $command, 'var1' => $var1);
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
                // if (curl_errno($c)) {
                //     $sad = curl_error($c);
                //     throw new Exception($sad);
                // }
                curl_close($c);
                $verify_response = json_decode($o, true);
                if (isset($verify_response['Status']) && $verify_response['Status'] == 'Success' && !empty($verify_response['URL'])) {
                    $link_expiry = date("Y-m-d H:i:s", strtotime('+30 minutes'));
                    $PaymentHistory = new PaymentHistory([
                        'order_id' => $OrderMasterNew->order_id,
                        'vendor_id' => $OrderMasterNew->vendor_id,
                        'user_id' => $OrderMasterNew->customer_id,
                        'payment_method' => 'hdfc',
                        'transaction_id' => $txn_id,
                        'amount' => $OrderMasterNew->total_order_price,
                        'product_info' => parent::cleanString($OrderMasterNew->service_name),
                        'first_name' => $OrderMasterNew->customer_name,
                        'email' => $OrderMasterNew->customer_email,
                        'phone' => $OrderMasterNew->customer_phone,
                        'address1' => $OrderMasterNew->customer_address1,
                        'city' => $OrderMasterNew->customer_city,
                        'state' => $OrderMasterNew->customer_state,
                        'country' => $OrderMasterNew->customer_country,
                        'zipcode' => $OrderMasterNew->customer_zipcode,
                        'udf1' => $OrderMasterNew->invoice_id,
                        'udf2' => $OrderMasterNew->vendor_id,
                        'udf3' => $OrderMasterNew->vendor_name,
                        'udf4' => $OrderMasterNew->service_type,
                        'status' => 'pending'
                    ]);
                    $PaymentHistory->save();
                    $payment_id = $PaymentHistory->id;

                    $OrderMasterNew->offline_short_url = $verify_response['URL'];
                    $OrderMasterNew->offline_long_url = $verify_response['URL'];
                    $OrderMasterNew->offline_link_expiry = $link_expiry;
                    $OrderMasterNew->payment_id = $payment_id;
                    $OrderMasterNew->transaction_id = $txn_id;
                    $OrderMasterNew->hdfc_key = $HDFC_KEY;
                    $OrderMasterNew->hdfc_salt = $HDFC_SALT;
                    $OrderMasterNew->paytm_mid = $MERCHANT_ID;
                } else {
                    OrderMaster::find($OrderMasterId)->delete();
                    Session::flash('success', "Sorry!, Could not able to place order due to some technical issue in generating payment link. Please try again after some time.");
                    return Redirect::to('offline-order');
                }
            }
            // require_once public_path('paytm_lib/encdec_paytm.php');
            // if ($request->payment_gateway == 'paytm') {
            //     $paytmParams = array();
            //     $expiryDate = date("Y-m-d H:i:s", strtotime("+15 minutes"));
            //     $paytmParams["body"] = array(
            //         "mid" => PAYTM_MERCHANT_MID,
            //         "linkType" => "GENERIC",
            //         "linkDescription" => "Odisha Tourism Payment",
            //         "linkName" => $invoice_id,
            //         "amount" => $OrderMasterNew->total_order_price,
            //         "sendSms" => true,
            //         "sendEmail" => true,
            //         "expiryDate" => date("d/m/Y H:i:s", strtotime($expiryDate)),
            //         "partialPayment" => false,
            //         "maxPaymentsAllowed" => 1,
            //         "customerContact" => array("customerName" => $OrderMasterNew->customer_name, "customerEmail" => $OrderMasterNew->customer_email, "customerMobile" => $OrderMasterNew->customer_phone)
            //     );
            //     if (!empty($OrderMasterNew->paytm_mid)) {
            //         $splitSettlementInfo['splitMethod'] = 'AMOUNT';
            //         $splitSettlementInfo['splitInfo'][] = array('mid' => $OrderMasterNew->paytm_mid, 'amount' => $OrderMasterNew->vendor_amount);
            //         $paytmParams["body"]["splitSettlementInfo"] = $splitSettlementInfo;
            //     }

            //     $checksum = generateSign($paytmParams["body"], PAYTM_MERCHANT_KEY);
            //     $paytmParams["head"] = array(
            //         "tokenType" => "AES",
            //         "signature" => $checksum
            //     );
            //     $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
            //     $url = "https://securegw-stage.paytm.in/link/create";
            //     if (PAYTM_ENVIRONMENT == 'PROD') {
            //         $url = "https://securegw.paytm.in/link/create";
            //     }
            //     $ch = curl_init($url);
            //     curl_setopt($ch, CURLOPT_POST, true);
            //     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //     curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            //     $response = curl_exec($ch);
            //     $response = json_decode($response, 1);
            //     echo "<pre>";print_r($response);exit;

            //     if ($response['body']['resultInfo']['resultStatus'] == 'SUCCESS') {
            //         $linkId = $response['body']['linkId'];
            //         $longUrl = $response['body']['longUrl'];
            //         $shortUrl = $response['body']['shortUrl'];

            //         $OrderMaster->offline_link_id = $linkId;
            //         $OrderMaster->offline_short_url = $shortUrl;
            //         $OrderMaster->offline_long_url = $longUrl;
            //         $OrderMaster->offline_link_expiry = $expiryDate;
            //     } else {
            //         OrderMaster::find($OrderMasterId)->delete();
            //         Session::flash('success', "Sorry!, Could not able to place order due to some technical issue in generating payment link. Please try again after some time.");
            //         return Redirect::to('tour-offline-order');
            //     }
            // }

            if ($TourData->category == 'package') {
                $Itinerary = json_decode($TourData->itinerary, 1);
                $difference = strtotime($end_date) - strtotime($start_date);
                $days = round($difference / (60 * 60 * 24));
                $total_adult = $request->total_adults;
                $temp = 0;
                $count = 0;
                foreach ($Itinerary as $itnr) {
                    if ($itnr['hotel'] != 'No Accommodation') {
                        $checkInDate = date("Y-m-d", strtotime($start_date . ' + ' . $temp . ' days'));
                        $checkOutDate = date("Y-m-d", strtotime($start_date . ' + ' . ($temp + 1) . ' days'));
                        $room_quantity = ($total_adult > 3) ? 2 : 1;
                        $MasterHotel = array();
                        if ($itnr['hotel'] != 'other' && $itnr['hotel'] != 'No Accommodation' && !empty($itnr['hotel'])) {
                            $MasterHotel = MasterHotel::find($itnr['hotel']);
                            $HotelRoom = HotelRoom::find($itnr['hotelRoom']);
                            $MasterInventory = MasterInventory::where(['hotel_id' => $itnr['hotel'], 'room_id' => $itnr['hotelRoom'], 'date' => $checkInDate])->first();
                            if (!empty($MasterInventory)) {
                                $MasterInventory->total_available -= $room_quantity;
                                $MasterInventory->total_booked += $room_quantity;
                                $MasterInventory->total_tour_booking += $room_quantity;
                                $MasterInventory->save();

                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id)) {
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                    <AvailRateUpdate locatorID="1">
                                                        <DateRange from="'. $checkInDate . '" to="' . $checkInDate .'"/>
                                                        <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                    </AvailRateUpdate>
                                                </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('online_package_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id)) {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                ->get()->toArray();
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('online_package_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '". date('Y-m-d H:i:s') ."', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                            $OrderLogData[$temp] = [
                                'vendor_id' => $vendor_id,
                                'order_id' => $booking_id,
                                'service_id' => $itnr['hotel'],
                                'room_id' => $itnr['hotelRoom'],
                                'date' => $checkInDate,
                                'total_booked' => $room_quantity,
                                'created_by' => $userId
                            ];
                        }
                        $temp++;
                        $hotelName = $itnr['hotelName'];
                        $hotelId = ($itnr['hotel'] == 'other' || $itnr['hotel'] == 'No Accommodation') ? '' : $itnr['hotel'];
                        $roomId = ($itnr['hotel'] == 'other' || $itnr['hotel'] == 'No Accommodation') ? '' : $itnr['hotelRoom'];
                        $roomName = ($itnr['hotel'] == 'other' || $itnr['hotel'] == 'No Accommodation') ? '' : $itnr['roomName'];

                        $OrderDetailsData[$count] = [
                            'order_id' => $booking_id,
                            'order_master_id' => $OrderMasterId,
                            'service_type' => 'package',
                            'service_name' => $hotelName,
                            'service_name_id' => $hotelId,
                            'service_city' => ($itnr['hotel'] == 'other' || $itnr['hotel'] == 'No Accommodation') ? '' : $MasterHotel->city,
                            'start_date' => $checkInDate,
                            'end_date' => $checkOutDate,
                            'start_time' => ($itnr['hotel'] == 'other' || $itnr['hotel'] == 'No Accommodation') ? '' : $MasterHotel->check_in_time,
                            'end_time' => ($itnr['hotel'] == 'other' || $itnr['hotel'] == 'No Accommodation') ? '' : $MasterHotel->check_out_time,
                            'service_item_id' => $roomId,
                            'service_item_name' => $roomName,
                            'service_item_quantity' => 1,
                            'status' => $status
                        ];
                        if ($room_quantity == 2) {
                            $count++;
                            $OrderDetailsData[$count] = $OrderDetailsData[$count - 1];
                        }
                        $count++;
                    }
                }
                OrderLog::insert($OrderLogData);
                OrderDetail::insert($OrderDetailsData);
            }
            $txn_id = !empty($OrderMasterNew->transaction_id) ? $OrderMasterNew->transaction_id : 'N/A';
            $discount = !empty($OrderMasterNew->coupon_amount) ? $OrderMasterNew->coupon_amount : '0.00';

            $vendorGSTNo = (!empty($TourData->gst_number)) ? $TourData->gst_number : 'N/A';
            $vendorRegdCompany = (!empty($TourData->gst_legal_name)) ? $TourData->gst_legal_name : 'N/A';
            $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? 'GSTN No: '. $OrderMaster->gst_regd_no : '';
            $customerGSTCompany = (!empty($OrderMaster->gst_company_name)) ? 'Company Name: '. $OrderMaster->gst_company_name : '';
            $tspinword = parent::AmountInWords($OrderMasterNew->total_service_price);
            if ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                $sms_txt = $sms_txt_admin = $manager_contact = $reception_contact = '';
                $SmsTemplate = SmsTemplate::where('ref_code', 'BookingConfirmUser')->first();
                if (!empty($SmsTemplate)) {
                    $var1 = $OrderMaster->customer_name;
                    $var2 = $OrderMaster->invoice_id;
                    $var4 = $OrderMaster->service_name;
                    $var4 = (strlen($var4) > 30) ? substr(utf8_encode($var4), 0, 27) .'...' : $var4;
                    $var6 = "\n". $OrderMaster->vendor_name;
                    $var8 = "\n\n";
                    $var3 = $var5 = $var7 = '';
                    if ($OrderMaster->service_type == 'tour' && $OrderMaster->service_category == 'sight seeing') {
                        $var5 = date("d M Y", strtotime($OrderMaster->start_date));
                        $var7 = $TourData->contact_number;
                        $manager_contact = $TourData->contact_number;
                    } elseif ($OrderMaster->service_type == 'tour' && $OrderMaster->service_category == 'package') {
                        $var5 = date("d M Y", strtotime($OrderMaster->start_date)) .' to '. date("d M Y", strtotime($OrderMaster->end_date));
                        $var7 = $TourData->contact_number;
                        $manager_contact = $TourData->contact_number;
                    }
                    $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~", "~var8~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8), $SmsTemplate->source);
                    parent::sendSms($OrderMaster->customer_phone, $sms_txt, $SmsTemplate->templete_id);
                }
                if ($OrderMaster->service_category == 'sight seeing') {
                    $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmSightSeeing')->first();
                    if (!empty($SmsTemplateAdmin)) {
                        $var1 = $OrderMaster->total_guests .' persons';
                        $var2 = $OrderMaster->service_name;
                        $var2 = (strlen($var2) > 30) ? substr(utf8_encode($var2), 0, 27) .'...' : $var2;
                        $var3 = $OrderMaster->customer_name;
                        $var4 = $OrderMaster->customer_phone;
                        $var5 = date("d M Y", strtotime($OrderMaster->start_date));
                        $var6 = $OrderMaster->invoice_id;
                        $var7 = "\n\n";

                        $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7), $SmsTemplateAdmin->source);
                        $sms_recipient =  array();
                        if (!empty($manager_contact))
                            array_push($sms_recipient, $manager_contact);
                        if (!empty($TourData->additional_phone))
                            $sms_recipient = array_merge($sms_recipient, explode(",", $TourData->additional_phone));
                        if (!empty($sms_recipient)) {
                            $to_sms = implode(',', array_slice($sms_recipient,0,3));
                            parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                        }
                    }
                } else {
                    $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmPackageRental')->first();
                    if (!empty($SmsTemplateAdmin)) {
                        $var1 = $OrderMaster->service_name;
                        $var1 = (strlen($var1) > 30) ? substr(utf8_encode($var1), 0, 27) .'...' : $var1;
                        $var2 = $OrderMaster->customer_name;
                        $var3 = $OrderMaster->customer_phone;
                        $var4 = date("d M Y", strtotime($OrderMaster->start_date));
                        $var5 = date("d M Y", strtotime($OrderMaster->end_date));
                        $var6 = $OrderMaster->invoice_id;

                        $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($var1, $var2, $var3, $var4, $var5, $var6), $SmsTemplateAdmin->source);
                        $sms_recipient =  array();
                        if (!empty($manager_contact))
                            array_push($sms_recipient, $manager_contact);
                        if (!empty($TourData->additional_phone))
                            $sms_recipient = array_merge($sms_recipient, explode(",", $TourData->additional_phone));
                        if (!empty($sms_recipient)) {
                            $to_sms = implode(',', array_slice($sms_recipient,0,3));
                            parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                        }
                    }
                }
            }
            if ($OrderMaster->service_category == 'sight seeing') {
                $SightseenInvoice = EmailTemplate::where('ref_code', 'sightseenInvoice')->first();
                $Subject = $SightseenInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                $check_date = date("d M Y", strtotime($OrderMaster->start_date));
                $tour_include = json_decode($TourData->include, 1);
                $tour_exclude = json_decode($TourData->exclude, 1);
                $inc_html = $exc_html = '';
                foreach ($tour_include as $inc) {
                    $inc_html .= '<tr>';
                    if (!is_null($inc['title'])) {
                        $inc_html .= '<th align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000"><strong>' . $inc['title'] . '</strong></th>'
                                . '<td align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000">' . $inc['content'] . '</td>';
                    } else {
                        $inc_html .= '<td colspan="2" align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000">' . $inc['content'] . '</td>';
                    }
                    $inc_html .= '</tr>';
                }
                foreach ($tour_exclude as $exc) {
                    $exc_html .= '<tr>';
                    if (!is_null($exc['title'])) {
                        $exc_html .= '<th align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000"><strong>' . $exc['title'] . '</strong></th>'
                                . '<td align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $exc['content'] . '</td>';
                    } else {
                        $exc_html .= '<td colspan="2" align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $exc['content'] . '</td>';
                    }
                    $exc_html .= '</tr>';
                }
                $gst_amount = $OrderMaster->tax_amount;
                $cgst = $sgst = number_format($gst_amount / 2, 2);
                $unit_price = number_format($OrderMaster->total_service_price / $OrderMaster->total_guests, 2);
                $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~ticketquantity~", "~unitprice~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~tspinword~", "~payuid~"),
                        array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $vendorData->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date, $OrderMaster->total_guests, $unit_price, $OrderMaster->total_service_price, $OrderMaster->coupon_name, $discount, $OrderMaster->sub_total_price, $OrderMaster->tax_amount, $OrderMaster->total_order_price, strtoupper($OrderMaster->payment_gateway), $txn_id, $tspinword, 'N/A'), $SightseenInvoice->source);

                if ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                    if ($TourData->id == '34' || $TourData->id == '35' || $TourData->id == '36'){
                        $seat_no = 'N/A';
                    }
                    $ConfirmTemplate = EmailTemplate::where('ref_code','sightseenConfirmMail')->first();
                    if (!empty($ConfirmTemplate)) {
                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkintime~", "~totalguest~", "~checkouttime~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~tourinclude~", "~tourexclude~", "~invoiceid~", "~seatno~"),
                        array($this->site . $vendorData->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), date("h:i a", strtotime($TourData->duration_start .' '. $TourData->duration_start_text)), $OrderMaster->total_guests, date("h:i a", strtotime($TourData->duration_end .' '. $TourData->duration_end_text)), $OrderMaster->total_order_price, $txn_id, strtoupper($OrderMaster->payment_gateway), $TourData->terms_conditions, $inc_html, $exc_html, $OrderMaster->invoice_id, $seat_no), $ConfirmTemplate->source);
                        $OrderMaster->confimation_voucher = $msg;
                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                        Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                    }
                } else {
                    $Message = str_replace('This is an electronically generated invoice, hence does not require a signature.', 'This is an estimated invoice for payment. You will get a confirmation voucher and invoice after payment.', $Message);
                    $extraMsg = 'Dear '. $OrderMasterNew->customer_name .',<br><br> Please <a href="'. $OrderMasterNew->offline_short_url .'"><strong>click here</strong></a> to complete the payment for booking '. $OrderMasterNew->invoice_id .' of amount Rs. '. $OrderMasterNew->total_order_price;
                    $extraMsg .= '<br><br>If the above link is not working, please go through the following url for payment. <br><br>'. $OrderMasterNew->offline_long_url;
                    $extraMsg .= '<br><br><strong>Note: Please complete the payment as soon as possible.</strong><br><br>';

                    $Message = $extraMsg . $Message;
                    $Subject = 'Payment link for - ' . $OrderMasterNew->service_name . ' - Booking ID - ' . $OrderMasterNew->invoice_id;
                }
                $OrderMaster->invoice = $Message;
            }
            else {
                $PackageInvoice = EmailTemplate::where('ref_code', 'packageInvoice')->first();
                $Subject = $PackageInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                $check_date = date("d M Y", strtotime($OrderMaster->start_date)) . ' - ' . date("d M Y", strtotime($OrderMaster->end_date));
                $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                $OrderDetail = OrderDetail::select('service_name', 'service_name_id', 'start_date', 'end_date', 'service_item_name', DB::raw('SUM(service_item_quantity)as totQty'))
                        ->where('order_master_id', $OrderMaster->id)
                        ->groupBy('start_date')
                        ->get();
                $room_details = '';
                $hotel_emails = array();
                foreach ($OrderDetail as $value) {
                    $Hotels = MasterHotel::find($value->service_name_id);
                    if (!empty($Hotels) && !empty($Hotels->contact_email)) {
                        array_push($hotel_emails, $Hotels->contact_email);
                    }
                    $room_details .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->service_name . '</td>'
                            . '<td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($value->start_date)) . ' - ' . date("d M Y", strtotime($value->end_date)) . '</td>'
                            . '<td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->service_item_name . '</td>'
                            . '<td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->totQty . '</td></tr>';
                }
                $childData = '';
                if ($OrderMaster->total_child > 0) {
                    $childData = '<tr style="font-size:14px;"><td colspan="3" align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;"></td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->total_child .' Child</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->child_price .'</td><td align="right" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. number_format($OrderMaster->total_child * $OrderMaster->child_price, 2) .'</td></tr>';
                }
                $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~totaladult~", "~adultprice~", "~totaladultprice~", "~childdata~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~tspinword~", "~payuid~"),
                        array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $vendorData->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date, $OrderMaster->total_adults, $OrderMaster->adult_price, number_format($OrderMaster->total_adults * $OrderMaster->adult_price, 2), $childData, $OrderMaster->total_service_price, $OrderMaster->coupon_name, $discount, $OrderMaster->sub_total_price, $OrderMaster->tax_amount, $OrderMaster->total_order_price, strtoupper($OrderMaster->payment_gateway), $txn_id, $tspinword, 'N/A'), $PackageInvoice->source);

                if ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                    $ConfirmTemplate = EmailTemplate::where('ref_code', 'packageConfirmMail')->first();
                    if (!empty($ConfirmTemplate)) {
                        $SubjConfirm = $ConfirmTemplate->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkoutdate~", "~adult~", "~child~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~accommodation~", "~invoiceid~"),
                                array($this->site . $vendorData->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), date("d M Y", strtotime($OrderMaster->end_date)), $OrderMaster->total_adults, $OrderMaster->total_child, $OrderMaster->total_order_price, $OrderMaster->transaction_id, strtoupper($OrderMaster->payment_gateway), $TourData->terms_conditions, $room_details, $OrderMaster->invoice_id), $ConfirmTemplate->source);
                        $OrderMaster->confimation_voucher = $msg;
                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                        Mail::to($OrderMaster->customer_email)->bcc($hotel_emails)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                    }
                } else {
                    $Message = str_replace('This is an electronically generated invoice, hence does not require a signature.', 'This is an estimated invoice for payment. You will get a confirmation voucher and invoice after payment.', $Message);

                    $extraMsg = 'Dear '. $OrderMasterNew->customer_name .',<br><br> Please <a href="'. $OrderMasterNew->offline_short_url .'"><strong>click here</strong></a> to complete the payment for booking '. $OrderMasterNew->invoice_id .' of amount Rs. '. $OrderMasterNew->total_order_price;
                    $extraMsg .= '<br><br>If the above link is not working, please go through the following url for payment. <br><br>'. $OrderMasterNew->offline_long_url;
                    $extraMsg .= '<br><br><strong>Note: Please complete the payment as soon as possible.<br>The provisional invoice for your booking is as follows.</strong><br><br>';

                    $Message = $extraMsg . $Message;
                    $Subject = 'Payment link for - ' . $OrderMasterNew->service_name . ' - Booking ID - ' . $OrderMasterNew->invoice_id;
                }
                $OrderMaster->invoice = $Message;
            }

            $service_mail = $TourData->contact_email;
            if (!empty($TourData->additional_email)) {
                $service_mail = !empty($service_mail) ? $service_mail .','. $TourData->additional_email : $TourData->additional_email;
            }
            $OrderMaster->save();
            $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

            // $admin = User::where('role', 1)->first();
            $receipent = [$vendorData->email];
            if (!empty($service_mail)) {
                $receipent = array_merge($receipent, explode(',', $service_mail));
            }
            if (Auth::user()->user_role == 'agent_staff') {
                Mail::to($receipent)
                    ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
            } else {
                Mail::to($OrderMaster->customer_email)
                    ->bcc($receipent)
                    ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
            }
            Session::flash('success', 'Order created successfully.');
            return Redirect::to('tour-offline-order');
        } else {
            Session::flash('success', 'Unable to create order.');
            return Redirect::to('tour-offline-order');
        }
    }

    public function tourBookingReport(Request $request)
    {
        if (!(parent::checkViewPrivilege(101))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $TourQuery = DB::table('tours');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $TourQuery->where('vendor_id', $vender_id);
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $TourQuery->whereIn('id', array_values($SubuserAccess));
                }
            }
        }
        $Tours = $TourQuery->pluck('name', 'id')->toArray();
        $report_type = 'book_date';
        $MisTicketData = array();
        $check_date = date("Y-m-d");
        $TicketId = 0;
        if (isset($_GET['check_date']) && isset($_GET['report_type']) && isset($_GET['ticket_id'])) {
            $TicketId = $_GET['ticket_id'];

            $TourList = array();
            if ($TicketId != 0 && isset($Tours[$TicketId])) {
                $TourList[$TicketId] = $Tours[$TicketId];
            } else {
                $TourList = $Tours;
            }
            $check_date = date("Y-m-d", strtotime($_GET['check_date']));
            if ($_GET['report_type'] == 'book_date') {
                $report_type = 'book_date';

                $Orders = OrderMaster::where(['service_type' => 'tour', 'payment_status' => 'success'])
                            ->whereIn('service_name_id', array_keys($TourList))
                            ->where('created_at', 'LIKE', $check_date .'%')
                            ->where('status', '!=', 'partially-cancelled')
                            ->orderBy('created_at', 'DESC')
                            ->get();
                if (!empty($Orders->toArray())) {
                    foreach ($Orders as $key => $value) {
                        $status = ($value->status == 'cancelled') ? 'Cancelled' : 'Confirmed';

                        $MisTicketData[] = array(
                            'invoice_id' => $value->invoice_id,
                            'invoice_slno' => !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A',
                            'oderID' => $value->id,
                            'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                            'guest_name' => $value->customer_name,
                            'guest_phone' => $value->customer_phone,
                            'guest_email' => $value->customer_email,
                            'unit_name' => $value->service_name,
                            'check_in' => date("d-M-Y", strtotime($value->start_date)),
                            'time' => $value->start_time .' - '. $value->end_time,
                            'service_category' => $value->service_category,
                            'occupancy' => $value->total_guests,
                            'total_amount' => $value->total_order_price,
                            'order_type' => $value->order_type,
                            'seat_no' => $value->gate_number,
                            'status' => $status,
                            'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                        );
                    }
                }
            } elseif ($_GET['report_type'] == 'stay_date') {
                $report_type = 'stay_date';
                $Orders = OrderMaster::where(['status' => 'completed'])
                            ->where('service_type', 'tour')
                            ->whereIn('service_name_id', array_keys($TourList))
                            ->where('start_date', $check_date)
                            ->get();
                if (!empty($Orders->toArray())) {
                    foreach ($Orders as $key => $value) {
                        $hotelId = $hotelName = $rooms = $start_date = $end_date = '';$night = 0;

                        $start_date = date("d-M-Y", strtotime($value->start_date));
                        $time = $value->start_time .' - '. $value->end_time;
                        $hotelId = $value->service_name_id;
                        $hotelName = $value->service_name;

                        $MisTicketData[$hotelId]['data'][] = array(
                            'invoice_id' => $value->invoice_id,
                            'oderID' => $value->id,
                            'invoice_slno' => !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A',
                            'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                            'guest_name' => $value->customer_name,
                            'guest_phone' => $value->customer_phone,
                            'guest_email' => $value->customer_email,
                            'unit_name' => $hotelName,
                            'check_in' => $start_date,
                            'time' => $time,
                            'service_category' => $value->service_category,
                            'occupancy' => $value->total_guests,
                            'total_amount' => $value->total_order_price,
                            'order_type' => $value->order_type,
                            'seat_no' => $value->gate_number,
                        );
                        $MisTicketData[$hotelId]['name'] = $hotelName;
                        $MisTicketData[$hotelId]['total_book'] = (isset($MisTicketData[$hotelId]['total_book'])) ? $MisTicketData[$hotelId]['total_book'] + 1 : 1;
                        $MisTicketData[$hotelId]['total_occupancy'] = (isset($MisTicketData[$hotelId]['total_occupancy'])) ? $MisTicketData[$hotelId]['total_occupancy'] + $value->total_guests : $value->total_guests;
                    }
                }
            }
        } else {
            $Orders = OrderMaster::where(['service_type' => 'tour', 'payment_status' => 'success'])
                            ->whereIn('service_name_id', array_keys($Tours))
                            ->where('created_at', 'LIKE', $check_date .'%')
                            ->where('status', '!=', 'partially-cancelled')
                            ->orderBy('created_at', 'DESC')
                            ->get();
            if (!empty($Orders->toArray())) {
                foreach ($Orders as $key => $value) {
                    $status = ($value->status == 'cancelled') ? 'Cancelled' : 'Confirmed';

                    $MisTicketData[] = array(
                        'invoice_id' => $value->invoice_id,
                        'invoice_slno' => !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A',
                        'oderID' => $value->id,
                        'invoice' => $value->invoice,
                        'confirm_voucher' => $value->confimation_voucher,
                        'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                        'guest_name' => $value->customer_name,
                        'guest_phone' => $value->customer_phone,
                        'guest_email' => $value->customer_email,
                        'unit_name' => $value->service_name,
                        'check_in' => date("d-M-Y", strtotime($value->start_date)),
                        'time' => $value->start_time .' - '. $value->end_time,
                        'service_category' => $value->service_category,
                        'occupancy' => $value->total_guests,
                        'total_amount' => $value->total_order_price,
                        'order_type' => $value->order_type,
                        'seat_no' => $value->gate_number,
                        'status' => $status,
                        'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                    );
                }
            }
        }
        // echo "<pre>";print_r($MisTicketData);exit;
        return view('tours.tour-booking-report', compact('MisTicketData', 'check_date', 'report_type', 'Tours', 'TicketId'));
    }

    public function cancelOptions($Id = null, $orderId = null)
    {
        if (!(parent::checkWritePrivilege(31))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $OrderMasterData = OrderMaster::where('id', $Id)
            ->where('order_id', $orderId)
            // ->where('start_date', '>', date("Y-m-d"))
            ->where('status', '!=', 'partially-cancelled')
            ->where('status', '!=', 'cancelled')
            ->first();
        if (!empty($OrderMasterData)) {

            return view('tours.cancel-option', compact('OrderMasterData'));
        } else {
            return Redirect::to('tour-orders');
        }
    }

    public function tourCancelOprsn(Request $request)
    {
        if ($request->request_type == 'shift_order_date') {
            $status = 1;
            $OrderMaster = OrderMaster::find($request->orderId);
            $days = 0;

            $start_date = date("Y-m-d", strtotime($request->startDate));

            $BlockData = TourAvailability::where(['tour_id' => $OrderMaster->service_name_id, 'block_date' => $start_date])->first();
            if (!empty($BlockData)) {
                $responce['status'] = 0;
                $responce['message'] = 'Tour not available for this date. Please choose different date!';
            } else {
                $TourDetails = Tour::find($OrderMaster->service_name_id);

                $OrderMasterNew = $OrderMaster->toArray();
                $OrderMaster->status = 'partially-cancelled';
                $OrderMaster->save();
                unset($OrderMasterNew['id']);
                $OrderMasterNew['start_date'] = $start_date;
                $OrderMasterNew['created_at'] = date("Y-m-d H:i:s", strtotime($OrderMaster->created_at));
                $OrderMasterNew['updated_at'] = date("Y-m-d H:i:s");

                $seat_no = 'N/A';

                $seatDate = parent::maxDateForSeat($TourDetails->id);
                if ($seatDate < $start_date) {
                    $CustomerSeat = OrderMaster::select(DB::raw('sum(total_guests) as totSeat'))
                        ->where(['service_type' => 'tour', 'service_name_id' => $TourDetails->id, 'payment_status' => 'success'])
                        ->where('start_date', $start_date)
                        ->first();
                    $seatArr = array();
                    $i = 1;
                    while ($i <= $OrderMaster->total_guests) {
                        array_push($seatArr, $CustomerSeat->totSeat + $i);
                        $i++;
                    }
                    $seat_no = implode(',', $seatArr);
                }
                $OrderMasterNew['gate_number'] = $seat_no;
                $OrderMasterId = DB::table('order_masters')->insertGetId($OrderMasterNew);

                $calculate_date = $start_date;
                $Vendor = User::find($OrderMaster->vendor_id);

                $Subject = $Message = '';
                $PaymentHistory = PaymentHistory::find($OrderMaster->payment_id);
                $SightseenInvoice = EmailTemplate::where('ref_code','sightseenInvoice')->first();

                $Subject = 'Booking Shifting - ' . $OrderMasterNew['service_name'] . ' - Booking ID - ' . $OrderMasterNew['invoice_id'];
                $check_date = date("d M Y", strtotime($OrderMasterNew['start_date']));
                $tour_include = json_decode($TourDetails->include, 1);
                $tour_exclude = json_decode($TourDetails->exclude, 1);
                $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? '<u><b>GSTN No: '. $OrderMaster->gst_regd_no .'</b></u>' : '';
                $customerGSTCompany = (!empty($OrderMaster->gst_company_name)) ? '<u><b>Company Name: '. $OrderMaster->gst_company_name .'</b></u>' : '';
                $vendorGSTNo = (!empty($TourDetails->gst_number)) ? $TourDetails->gst_number : 'N/A';
                $vendorRegdCompany = (!empty($TourDetails->gst_legal_name)) ? $TourDetails->gst_legal_name : 'N/A';

                $inc_html = $exc_html = '';
                foreach ($tour_include as $inc) {
                    $inc_html .= '<tr>';
                    if (!is_null($inc['title'])) {
                        $inc_html .= '<th align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000"><strong>'. $inc['title'] .'</strong></th>'
                                    .'<td align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000">'. $inc['content'] .'</td>';
                    } else {
                        $inc_html .= '<td colspan="2" align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000">'. $inc['content'] .'</td>';
                    }
                    $inc_html .= '</tr>';
                }
                foreach ($tour_exclude as $exc) {
                    $exc_html .= '<tr>';
                    if (!is_null($exc['title'])) {
                        $exc_html .= '<th align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000"><strong>'. $exc['title'] .'</strong></th>'
                                    .'<td align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $exc['content'] .'</td>';
                    } else {
                        $exc_html .= '<td colspan="2" align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $exc['content'] .'</td>';
                    }
                    $exc_html .= '</tr>';
                }
                $gst_amount = $OrderMasterNew['tax_amount'];
                $cgst = $sgst = number_format($gst_amount / 2, 2);
                $unit_price = number_format($OrderMaster->total_service_price / $OrderMaster->total_guests, 2);
                $tspinword = parent::AmountInWords($OrderMaster->total_service_price);

                $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~ticketquantity~", "~unitprice~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~payuid~", '~tspinword~'),
                        array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date, $OrderMaster->total_guests, $unit_price, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount. 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, $PaymentHistory->mihpayid, $tspinword), $SightseenInvoice->source);

                $new_voucher = '';
                $ConfirmTemplate = EmailTemplate::where('ref_code','sightseenConfirmMail')->first();
                if (!empty($ConfirmTemplate)) {
                    $new_voucher = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkintime~", "~totalguest~", "~checkouttime~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~tourinclude~", "~tourexclude~", "~invoiceid~", "~seatno~"),
                            array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMasterNew['start_date'])), date("h:i a", strtotime($TourDetails->duration_start .' '. $TourDetails->duration_start_text)), $OrderMaster->total_guests, date("h:i a", strtotime($TourDetails->duration_end .' '. $TourDetails->duration_end_text)), number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $TourDetails->terms_conditions, $inc_html, $exc_html, $OrderMaster->invoice_id, $seat_no), $ConfirmTemplate->source);
                }
                OrderMaster::find($OrderMasterId)->update(['invoice' => $Message, 'confimation_voucher' => $new_voucher]);

                $EmailMessage = "<p style='color:#000000;'>Dear " . $OrderMasterNew['customer_name'] . ",</p>";
                $EmailMessage .= "<p style='color:#000000;'>Your reservation booking at " . $OrderMasterNew['service_name'] . " for date <b>" . date("M d Y", strtotime($OrderMaster->start_date)) ."</b> has been successfully changed to <b>" . $check_date . "</b>.</p>";
                $EmailMessage .= "<p style='color:#000000;'>For more login to <a href='" . $this->frontendUrl . "' target='_blank'>website</a> and check booking history.</p>";
                $EmailMessage .= "<p style='color:#000000;'>Thanks & Regards, <br>Odisha Tourism</p>";
                $EmailMessage .= $Message;

                $service_mail = $TourDetails->contact_email;
                if (!empty($TourDetails->additional_email)) {
                    $service_mail = !empty($service_mail) ? $service_mail .','. $TourDetails->additional_email : $TourDetails->additional_email;
                }
                $recepient = $OrderMaster->customer_email;
                $admin = User::where('role', 1)->first();
                $bcc = array($Vendor->email, $admin->email);
                if (!empty($service_mail) && $Vendor->id == 1) {
                    $bcc = array_merge($bcc, explode(',', $service_mail));
                }
                try {
                    Mail::to($recepient)
                        ->bcc($bcc)
                        ->send(new \App\Mail\RegistrationMailUser($EmailMessage, $Subject));
                }
                catch(\Exception $e) {}

                // parent::updateMmtInventory($OrderMasterNew['vendor_id'], $OrderMasterNew['service_name_id']);

                $responce['status'] = 1;
                $responce['message'] = 'Order placed successfully';
            }
        }
        echo json_encode($responce);
        exit;
    }


}
