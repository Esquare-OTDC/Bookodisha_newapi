<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use DateTime;
Use App\PasswordRemQuestion;
Use App\User;
Use App\Country;
Use App\State;
Use App\City;
Use App\Service;
use Session;
Use App\ServiceAttribute;
Use App\AttributeValue;
Use App\MasterCar;
Use App\RentalMasterInventory;
Use App\RentalAvailability;
Use App\OrderLog;
Use App\OrderMaster;
Use App\OrderDetail;
Use App\GstDetail;
Use App\GstTable;
Use App\Coupon;
use App\EmailTemplate;
Use App\SmsTemplate;
Use App\PropertyAccount;
Use App\PaymentHistory;
Use App\BlockedVehicle;

class CarBookingController extends Controller
{
    public $site;
    public $frontendUrl;
    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    public function allCars() {
        if (!(parent::checkViewPrivilege(16))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');

        return view('car-booking.all-cars', compact('Vendors'));
    }

    public function getCarDetails(Request $request) {
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'title', 'city', 'contact_email', 'contact_number', 'status');
        } else {
            $aColumns = array('id', 'title', 'city', 'contact_email', 'contact_number', 'status');
        }

        $sIndexColumn = "id";
        $sTable = "master_cars";
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
        }
        $sWhere = 'WHERE id != "" '. $vendor_condtition;
        $searchColumns = array('title', 'city');
        if (!empty($_POST['searchValue1']) || (!empty($_POST['searchValue2']) &&  !empty($_POST['searchValue3']))) {
            $condition1 = ''; $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND vendor_id = "'. $_POST['searchValue1'] .'"';
            }
            if (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3'])) {
                if (in_array($_POST['searchValue2'], $searchColumns)) {
                    $_POST['searchValue3'] = parent::cleanString($_POST['searchValue3']);
                    $condition2 .= ' AND '. $_POST['searchValue2'] .' LIKE "'. $_POST['searchValue3'] .'"';
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

            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            if (Auth::user()->access_type == 'superadmin') {
                $row[] = !empty($User) ? $User->company : 'N/A';
            }
            $row[] = $aRow->title;
            $row[] = $aRow->city;
            $row[] = $aRow->contact_email;
            $row[] = $aRow->contact_number;
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">'. $aRow->status .'</span>';
            //            $row[] = '<a href="' . url('car-edit', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('car-edit', $aRow->id) . '">Edit</a></li>
                    <li><a href="javascript:void(0)" class="deleteCar" data-id="'. $aRow->id .'">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function carOprsn(Request $request) {
        if ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_cars')->whereIn('id', $item_array)->update(['status' => 'publish', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicles publish successful.';
            }
        }
        elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_cars')->whereIn('id', $item_array)->update(['status' => 'draft', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicles moved to draft successfully.';
            }
        }
        elseif ($request->request_type == 'show_price') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_cars')->whereIn('id', $item_array)->update(['show_price' => 1, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicle price shown successfully.';
            }
        }
        elseif ($request->request_type == 'hide_price') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_cars')->whereIn('id', $item_array)->update(['show_price' => 0, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicle price hidden successfully.';
            }
        }
        elseif ($request->request_type == 'delete_car') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterCar = MasterCar::find($request->Id);
                if (!empty($MasterCar)) {
                    $banner_image = public_path($MasterCar->banner_image);
                    if (file_exists($banner_image) && !empty($MasterCar->banner_image)) {
                        unlink($banner_image);
                    }
                    $feature_image = public_path($MasterCar->feature_image);
                    if (file_exists($feature_image) && !empty($MasterCar->feature_image)) {
                        unlink($feature_image);
                    }
                    $gallery = json_decode($MasterCar->gallery, 1);
                    foreach($gallery as $images) {
                        $image = public_path($images);
                        if (file_exists($image)) {
                            unlink($image);
                        }
                    }
                    $MasterCar->delete();

                    RentalMasterInventory::where('car_id', $MasterCar->id)->delete();
                    RentalAvailability::where('car_id', $MasterCar->id)->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Vehicle deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid vehicle id.';
                }
            }
        }
        elseif ($request->request_type == 'delete-attribute') {
            if (!(parent::checkWritePrivilege(18))) {
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
            if (!(parent::checkWritePrivilege(18))) {
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
            if (!(parent::checkWritePrivilege(18))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $ServiceSttribute = ServiceAttribute::find($request->Id);
                $ServiceSttribute->name = $request->attrName;
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
            if (!(parent::checkWritePrivilege(18))) {
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
                         //$AttributeTerm->status = $request->attrStatus;

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
        elseif ($request->request_type == 'get_vehicle_quantity') {
            $block_date = date("Y-m-d", strtotime($request->date));
            $OrderData = RentalMasterInventory::where('car_id', $request->carId)
                    ->where('date', $block_date)
                    ->first();
            if (!empty($OrderData)) {
                $responce['status'] = 1;
                $responce['quantity'] = $OrderData->total_available;
            } else {
                $Mastercar = MasterCar::find($request->carId);
                $responce['status'] = 1;
                $responce['quantity'] = $Mastercar->quantity;
            }
        }
        elseif ($request->request_type == 'delete-blocked-vehicle') {
            if (!(parent::checkWritePrivilege(57))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = RentalAvailability::find($request->Id);
                if (!empty($BlockedData)) {
                    if ($BlockedData->block_date >= date("Y-m-d")) {
                        $MasterInventory = RentalMasterInventory::where(['car_id' => $BlockedData->car_id, 'date' => $BlockedData->block_date])->first();
                        $MasterInventory->total_available += $BlockedData->quantity;
                        $MasterInventory->total_blocked -= $BlockedData->quantity;
                        $MasterInventory->save();
                    }
                    $BlockedData->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        }
        elseif ($request->request_type == 'delete-blocked-vehicles') {
            if (!(parent::checkWritePrivilege(57))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = BlockedVehicle::find($request->Id);
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
        elseif ($request->request_type == 'get_availability_data') {
            $month = (strlen($request->month) == 1) ? '0' . $request->month : $request->month;
            $check_date = $request->year . '-' . $month;
            $MasterInventory = RentalMasterInventory::select('total_available', 'total_booked', 'date')
                    ->where(['car_id' => $request->carId])
                    ->where('date', 'like', $check_date . '%')
                    ->get();
            if (!empty($MasterInventory)) {
                $available_data = array();
                foreach ($MasterInventory as $value) {
                    array_push($available_data, array('title' => 'Available: ' . $value->total_available, 'color' => 'green', 'description' => 'Total Available', 'start' => $value->date));
                    array_push($available_data, array('title' => 'Booked: ' . $value->total_booked, 'color' => 'red', 'description' => 'Total Booked', 'start' => $value->date));
                }
                $responce['status'] = 1;
                $responce['data'] = $available_data;
            } else {
                $responce['status'] = 0;
                $responce['data'] = [];
            }
        }
        elseif ($request->request_type == 'get_car_details') {
            $Mastercar = MasterCar::find($request->carId);
            if (!empty($Mastercar)) {
                $responce['status'] = 1;
                $responce['data'] = $Mastercar;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Car details not found';
            }
        }
        elseif ($request->request_type == 'calculate_price') {
            $CarDetails = MasterCar::find($request->carId);
            if (!empty($CarDetails)) {
                $GSTData = GstDetail::pluck('value', 'name')->toArray();
                $taxPrice = 0;
                $GstShowData = $tax_array = array();
                $total_gst = $total_coupon_amt = $SubTotal = 0;
                if ($CarDetails->gst_applicable == 1) {
                    $GstTable = GstTable::where(['vendor_id' => $CarDetails->vendor_id, 'service_type' => 'rental'])
                                    ->orderBy('min_amount', 'asc')
                                    ->pluck('gst', 'min_amount')->toArray();
                    $GstDetails = array();
                    if (!empty($GstTable)) {
                        foreach ($GstTable as $k => $gst) {
                            $GstDetails[$k] = json_decode($gst, 1);
                        }
                    } else {
                        $GstDetails[0] = $GSTData;
                    }
                    $GstMin = array_keys($GstDetails);
                }
                $bookFlag = 1;
                $quantity_array = $price_breakup = array();
                $service_details = json_decode($request->day_breakup_details, 1);
                $start_date = date("Y-m-d", strtotime($service_details[0]['date']['startDate']));
                $end_data = end($service_details);
                $end_date = date("Y-m-d", strtotime($end_data['date']['endDate']));
                $BlockedVehicle = BlockedVehicle::where('vehicle_id', $CarDetails->id)
                    ->whereBetween('block_date', [$start_date, $end_date])
                    ->get()->toArray();
                if(!empty($BlockedVehicle)) {
                    $responce['status'] = 0;
                    $responce['message'] = 'Sorry! The vehicle is not available for selected dates.';
                    echo json_encode($responce);
                    exit;
                }
                $sub_total_price = 0;
                foreach ($service_details as $val) {
                    $totalPrice = $totalPriceKm = $totalPriceHr = $totalHour = $totalHaltPrice = $detention_charge = $detention_hour = $extrakm_price = $extrakm = 0;
                    $difference = strtotime(date("Y-m-d", strtotime($val['date']['endDate']))) - strtotime(date("Y-m-d", strtotime($val['date']['startDate'])));
                    $days = floor($difference / (60 * 60 * 24));

                    $checkinDate = new DateTime($val['date']['startDate']);
                    $checkoutDate = new DateTime($val['date']['endDate']);

                    $interval = $checkinDate->diff($checkoutDate);
                    $totalHour = $interval->format('%h') + ($interval->format('%d') * 24);

                    $cal_day = ($days == 0) ? 1 : $days;
                    for ($i = 0; $i < $cal_day; $i++) {
                        $checkDate = date("Y-m-d", strtotime($val['date']['startDate'] . ' + ' . $i . ' days'));
                        $MasterInventory = RentalMasterInventory::where(["date" => $checkDate, "car_id" => $CarDetails->id])->first();
                        if (!empty($MasterInventory) && $MasterInventory->total_available < 1) {
                            $bookFlag = 0;
                            array_push($quantity_array, date("d-m-Y", strtotime($checkDate)));
                        }
                    }
                    if ($bookFlag == 0) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Sorry! The vehicle is not available on date ' . implode(", ", $quantity_array);
                        echo json_encode($responce);
                        exit;

                    }

                    $travelHr = ceil($val['day_km'] / $CarDetails->dist_cover_per_hour);
                    $cover_day = $days + 1;
                    $cover_distance_day = $val['day_km'] / $cover_day;
                    if ($cover_distance_day >= $CarDetails->max_distance) {
                        $totalPriceKm = $val['day_km'] * $CarDetails->price_per_km;
                        $halthour = $days * $CarDetails->halt_hour;
                        $totalHaltPrice = $days * $CarDetails->price_for_halt;
                        $detention_hour = $totalHour - $travelHr - $halthour;
                        if ($detention_hour > 0) {
                            $detention_charge = $detention_hour * $CarDetails->detention_charge_per_hour;
                        }
                        $totalPrice = ($totalPriceKm + $detention_charge + $totalHaltPrice) * 1;
                    } else {
                        $totalPriceKm = $travelHr * $CarDetails->price_per_hour;
                        $halthour = $days * $CarDetails->halt_hour;
                        $calculateHr = $totalHour - $halthour;
                        if ($val['day_km'] > ($calculateHr * $CarDetails->free_km_per_hour)) {
                            $extrakm = $val['day_km'] - ($calculateHr * $CarDetails->free_km_per_hour);
                        }
                        if ($val['day_km'] > $extrakm) {
                            $extrakm_price = $extrakm * $CarDetails->price_per_km;
                        }
                        $totalPriceHr = $calculateHr * $CarDetails->price_per_hour;
                        $totalHaltPrice = $days * $CarDetails->price_for_halt;
                        $totalPrice = ($totalPriceHr + $totalHaltPrice + $extrakm_price) * 1;
                    }

                    $price_breakup[] = array(
                        "maxKm" => $CarDetails->max_distance,
                        "kmValue" => $val['day_km'],
                        "price_per_km" => $CarDetails->price_per_km,
                        "price_per_hr" => $CarDetails->price_per_hour,
                        "price_for_halt" => $CarDetails->price_for_halt,
                        "totalPriceKm" => $totalPriceKm,
                        "totalPriceHr" => $totalPriceHr,
                        "calculateHr" => $totalHour,
                        "totalHaltPrice" => $totalHaltPrice,
                        "totalhalt" => $days,
                        "detainationCharge" => $detention_charge,
                        "detainationHour" => $detention_hour,
                        "detention_charge_per_hour" => $CarDetails->detention_charge_per_hour,
                        "per_day_km_covered" => $cover_distance_day,
                        "extrakm_price" => $extrakm_price,
                        "extrakm" => $extrakm,
                        "booking_date" => array(
                          "startDate" => date("d-m-Y h:i a", strtotime($val['date']['startDate'])),
                          "endDate" => date("d-m-Y h:i a", strtotime($val['date']['endDate']))
                        ),
                        "totalPrice" => $totalPrice
                    );


                    $sub_total_price += $totalPrice;
                    if ($CarDetails->gst_applicable == 1) {
                        $filter_res = array_filter($GstMin, function($n) use($totalPrice) {
                            return $n <= $totalPrice;
                        });
                        if (!empty($filter_res)) {
                            $tempr = $GstDetails[end($filter_res)];
                            if (!empty($tax_array)) {
                                foreach ($tempr as $g_name => $g_val) {
                                //                                    $taxPercent += $g_val;
                                    $tax_amt = ceil($totalPrice * ($g_val / 100));
                                    $tax_array[$g_name] += $tax_amt;
                                }
                            } else {
                                foreach ($tempr as $g_name => $g_val) {
                                 //                                    $taxPercent += $g_val;
                                    $tax_amt = ceil($totalPrice * ($g_val / 100));
                                    $tax_array[$g_name] = $tax_amt;
                                }
                            }
                        }
                    }
                }
                $GstShowData = array();
                foreach ($tax_array as $key => $val) {
                    $GstShowData[] = [
                            'gst_name' => $key,
                            'gst_percentage' => $tempr[$key],
                            'gst_value' => round($val, 2)
                        ];
                    $total_gst += $val;
                }
                $taxPrice = round($total_gst, 2);
                $total_price = round($sub_total_price + $total_gst + $CarDetails->service_fee, 2);


                $responce['status'] = 1;
                $responce['gst_data'] = $GstShowData;
                $responce['grossPrice'] = $sub_total_price;
                $responce['subTotalPrice'] = $sub_total_price;
                $responce['totalOrderPrice'] = $total_price;
                $responce['price_breakup'] = $price_breakup;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Car details not found';
            }
        }
        elseif ($request->request_type == 'verify_coupon') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $coupons = Coupon::where(['service_type' => 'rental', 'coupon_code' => $request->coupon_code, 'vendor_id' => $vendor_id])
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
                    $days = 0;
                    $Mastercar = MasterCar::find($request->carId);
                    $checkin = date("Y-m-d", strtotime($request->checkIn));
                    $extra_price = 0;
                    if ($Mastercar->gst_applicable == 1) {
                        $GSTData = GstDetail::pluck('value', 'name')->toArray();
                        $GstTable = GstTable::where(['vendor_id' => $Mastercar->vendor_id, 'service_type' => 'rental'])
                                        ->orderBy('min_amount', 'asc')
                                        ->pluck('gst', 'min_amount')->toArray();
                        $GstDetails = array();
                        if (!empty($GstTable)) {
                            foreach ($GstTable as $k => $gst) {
                                $GstDetails[$k] = json_decode($gst, 1);
                            }
                        } else {
                            $GstDetails[0] = $GSTData;
                        }
                        $GstMin = array_keys($GstDetails);
                    }
                    $GstShowData = $tax_array = array();
                    $total_gst = $total_coupon_amt = $SubTotal = $grossPrice = 0;
                    $pricingData = json_decode($request->pricingData, 1);
                    //                    print_r($pricingData[0]);exit;
                    foreach ($pricingData as $value) {
                        $grossPrice += $value['totalPrice'];
                        $coupon_amt = round($value['totalPrice'] * ($coupon_data['coupon_value'] / 100), 2);
                        $total_coupon_amt += $coupon_amt;
                        $price = $value['totalPrice'] - $coupon_amt;
                        $SubTotal += $price;
                        if ($Mastercar->gst_applicable == 1) {
                            $filter_res = array_filter($GstMin, function($n) use($price) {
                                return $n <= $price;
                            });
                            if (!empty($filter_res)) {
                                $tempr = $GstDetails[end($filter_res)];
                                if (!empty($tax_array)) {
                                    foreach ($tempr as $g_name => $g_val) {
                                        $tax_amt = ceil($price * ($g_val / 100));
                                        $tax_array[$g_name] += $tax_amt;
                                    }
                                } else {
                                    foreach ($tempr as $g_name => $g_val) {
                                        $tax_amt = ceil($price * ($g_val / 100));
                                        $tax_array[$g_name] = $tax_amt;
                                    }
                                }
                            }
                        }
                    }
                    foreach ($tax_array as $key => $val) {
                        $GstShowData[] = [
                            'gst_name' => $key,
                            'gst_percentage' => $tempr[$key],
                            'gst_value' => ceil($val)
                        ];
                        $total_gst += $val;
                    }
                    $responce['status'] = 1;
                    $responce['gst_data'] = $GstShowData;
                    $responce['grossPrice'] = ceil($grossPrice);
                    $responce['subTotalPrice'] = ceil($SubTotal);
                    $responce['discount_amount'] = ceil($total_coupon_amt);
                    $responce['totalOrderPrice'] = ceil($SubTotal + $total_gst + $Mastercar->service_fee);
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
        elseif ($request->request_type == 'change_master_inventory') {
            if (!(parent::checkWritePrivilege(69))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterInventory = RentalMasterInventory::find($request->inventoryId);
                if (!empty($MasterInventory)) {
                    $changeType = $request->changeType;
                    $changeQty = $request->changeQty;
                    $initial_qty = $total_available = 0;
                    if ($changeType == 'decrease') {
                        if ($changeQty > $MasterInventory->total_available) {
                            $responce['status'] = 0;
                            $responce['message'] = 'You can decrease maximum '. $MasterInventory->total_available .' number of vehicles.';
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
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid data input.';
                }
            }
        }
        elseif ($request->request_type == 'change_block_inventory') {
            if (!(parent::checkWritePrivilege(69))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterInventory = RentalMasterInventory::find($request->inventoryId);
                if (!empty($MasterInventory)) {
                    $changeQty = $request->releaseQty;
                    $initial_qty = $total_available = 0;
                    if ($changeQty > $MasterInventory->total_blocked) {
                        $responce['status'] = 0;
                        $responce['message'] = 'You can release maximum '. $MasterInventory->total_blocked .' number of vehicles.';
                    } elseif ($changeQty < 1) {
                        $responce['status'] = 0;
                        $responce['message'] = 'You can release minimum 1 vehicle.';
                    } else {
                        $MasterInventory->total_available = $MasterInventory->total_available + $changeQty;
                        $MasterInventory->total_blocked = $MasterInventory->total_blocked - $changeQty;
                        $MasterInventory->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Inventory updated successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid data input.';
                }
            }
        }
        elseif ($request->request_type == 'export_rental_mis_report') {
            $MasterCarQuery = MasterCar::where('status', 'publish');
            if (Auth::user()->access_type == 'vendor') {
                $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                $MasterCarQuery->where('vendor_id', $vender_id);
            }
            $MasterCar = $MasterCarQuery->pluck('title', 'id');

            $headerArr = array('Date');
            $headerArr = array_merge($headerArr, $MasterCar->toArray());

            $csv = "documents/renatl_mis_report". time() .".csv";
            $csvname = public_path($csv);
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);

            $MisData = array();
            foreach ($MasterCar as $key => $value) {
                $MasterInventory = RentalMasterInventory::select('date', DB::raw('SUM(total_booked) as totalBook'))
                        ->where('car_id', $key)
                        ->where('date', '>=', date("Y-m-d"))
                        ->groupBy('date')
                        ->pluck('totalBook', 'date');
                foreach($MasterInventory as $dates => $qty) {
                    $MisData[$dates][$key] = $qty;
                }
            }
            if (!empty($MisData)) {
                foreach ($MisData as $key => $value) {
                    $data['date'] = $key;
                    foreach ($MisData[$key] as $rid => $qty) {
                        $data[$rid] = $qty;
                    }
                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }
        echo json_encode($responce);
        exit;
    }

    public function addCar() {
        if (!(parent::checkWritePrivilege(16))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $Attributes = ServiceAttribute::where('service', 'car')->pluck('name', 'id');
        $CarAttributes = array();
        foreach ($Attributes as $key => $value) {
            $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
            $CarAttributes[$value] = $AttributeValue; //array_values($AttributeValue);
        }
        $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();

        return view('car-booking.add-car', compact('Vendors', 'CarAttributes', 'CityDetail'));
    }

    public function carAddRequest(Request $request) {

        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string',
            'feature_image' => 'required|mimes:jpeg,png,jpg',
            'banner_image' => 'required|mimes:jpeg,png,jpg',
            'images.*' => 'required|mimes:jpeg,png,jpg',
            'address' => 'required|string',
            'city' => 'required|string',
            'status' => 'required|string',
            'quantity' => 'required|numeric',
            'price_per_hour' => 'required|numeric',
            'max_distance' => 'required|numeric',
            'price_per_km' => 'required|numeric',
            'price_for_halt' => 'required|numeric',
            'halt_hour' => 'required|numeric',
            'free_km_per_hour' => 'required|numeric',
            'min_duration' => 'required|numeric',
            'passenger' => 'required|string',
            'gear' => 'required|string',
            'baggage' => 'required|numeric',
            'door' => 'required|numeric',
            'terms_conditions' => 'required|string',
            'contact_number' => 'required|digits:10',
            'contact_email' => 'required|email',
            'show_price' => 'required',
            'guide_price_per_day' => 'required|numeric',
//            'gst_number' => 'string',
//            'gst_legal_name' => 'string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('car-add')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/cars/';
            $gallery_images = array();
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) .'_'. time() .'.'. $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                }
            }
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) .'_'. time() .'.'. $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                    $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) .'_'. time() .'.'. $file->extension();
                    $file->move(public_path($UploadDir), $gallery_image);
                    array_push($gallery_images, $UploadDir . $gallery_image);
                }
            }
            $faqs = '';
            if($request->faqs) {
                $data = array();
                foreach ($request->faqs as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $faqs = json_encode($data);
            }
            $slug = str_replace(' ', '-', trim(strtolower($request->title)));
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
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) { return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) { return !empty(trim($n)); });

            $Car = new MasterCar([
                'vendor_id' => $request->vendor_id,
                'title' => trim($request->title),
                'slug' => $slug,
                'content' => addslashes($request->content),
                'feature_image' => $UploadDir . $feature_image,
                'banner_image' => $UploadDir . $banner_image,
                'gallery' => json_encode($gallery_images),
                'city' => $request->city,
                'address' => addslashes($request->address),
                'map_lat' => $request->map_lat,
                'map_lng' => $request->map_lng,
                'video' => $request->video,
                'faqs' => $faqs,
                'quantity' => $request->quantity,
                'price_per_hour' => $request->price_per_hour,
                'max_distance' => $request->max_distance,
                'price_per_km' => $request->price_per_km,
                'price_for_halt' => $request->price_for_halt,
                'halt_hour' => $request->halt_hour,
                'detention_charge_per_hour' => $request->detention_charge_per_hour,
                'dist_cover_per_hour' => $request->dist_cover_per_hour,
                'free_km_per_hour' => $request->free_km_per_hour,
                'min_duration' => $request->min_duration,
                'passenger' => $request->passenger,
                'gear' => $request->gear,
                'baggage' => $request->baggage,
                'door' => $request->door,
                'contact_email' => $request->contact_email,
                'contact_number' => $request->contact_number,
                'additional_email' => implode(",", $add_email),
                'additional_phone' => implode(",", $add_phone),
                'terms_conditions' => $request->terms_conditions,
                'status' => $request->status,
                'gst_applicable' => $request->gst_applicable,
                'create_user' => Auth::user()->id,
                'property' => json_encode($Property),
                'property_slug' => implode("~",$property_slug_array),
                'show_price' => $request->show_price,
                'gst_number' => $request->gst_number,
                'gst_legal_name' => $request->gst_legal_name,
                'guide_price_per_day' => $request->guide_price_per_day,
//                'paytm_mid' => $request->paytm_mid,
//                'hdfc_mid' => $request->hdfc_mid
            ]);
            if ($Car->save()) {
                $CarId = $Car->id;
                $date = date("Y-m-d");
                $inventory = array();
                for ($i = 0; $i < 120; $i++) {
                    $date1 = date("Y-m-d", strtotime($date . ' + ' . $i . ' days'));
                    $inventory[$i] = [
                        'vendor_id' => $Car->vendor_id,
                        'car_id' => $CarId,
                        'date' => $date1,
                        'initial_quantity' => $request->quantity,
                        'total_available' => $request->quantity,
                        'total_booked' => 0,
                        'total_blocked' => 0,
                        'total_online_completed' => 0,
                        'total_online_pending' => 0,
                        'total_offline_completed' => 0,
                        'total_offline_pending' => 0,
                    ];
                }
                RentalMasterInventory::insert($inventory);

                Session::flash('success', 'Car added successful.');
                return Redirect::to('all-cars');
            } else {
                Session::flash('success', 'Unable to add Hotel');
                return Redirect::to('car-add');
            }
        }
    }

    public function editCar($id = null) {
        if (!(parent::checkWritePrivilege(16))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $CarDetailsQry = MasterCar::where('id', $id);
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $CarDetailsQry->where('vendor_id', $vendor_id);
        }
        $CarDetails = $CarDetailsQry->first();
        if (!empty($CarDetails)) {
            $CarDetails->feature_image = $this->site . $CarDetails->feature_image;
            $CarDetails->banner_image = $this->site . $CarDetails->banner_image;
            $CarDetails->gallery = json_decode($CarDetails->gallery);
            $property = json_decode($CarDetails->property, 1);
            $data = array();
            if (!empty($property)) {
               foreach ($property as $key1 => $attribute) {
                    foreach ($attribute as $key2 => $terms) {
                        $data[$key1][$key2] = $terms['name'];
                    }
                }
            }
            $CarDetails->property = $data;
            $CarDetails->faqs = json_decode($CarDetails->faqs, 1);
            $gallery = array();
            $count = 1;
            foreach ($CarDetails->gallery as $value) {
                $gallery[] = ['id'=> $count, 'src'=> $this->site . $value];
                $count++;
            }
            $gallery = json_encode($gallery);

            $Vendors = User::where('role', '2')->pluck('company', 'id');
            $Attributes = ServiceAttribute::where('service', 'car')->pluck('name', 'id');
            $CarAttributes = array();
            foreach ($Attributes as $key => $value) {
                $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
                $CarAttributes[$value] = $AttributeValue;
            }
            $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();

            return view('car-booking.edit-car', compact('Vendors', 'CarAttributes', 'CityDetail', 'CarDetails', 'gallery'));
        } else {
            return redirect()->back();
        }
    }

    public function carEditRequest(Request $request) {

        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string',
            'feature_image' => 'mimes:jpeg,png,jpg',
            'banner_image' => 'mimes:jpeg,png,jpg',
            'images.*' => 'mimes:jpeg,png,jpg',
            'address' => 'required|string',
            'city' => 'required|string',
            'status' => 'required|string',
            'quantity' => 'required|numeric',
            'price_per_hour' => 'required|numeric',
            'max_distance' => 'required|numeric',
            'price_per_km' => 'required|numeric',
            'price_for_halt' => 'required|numeric',
            'free_km_per_hour' => 'required|numeric',
            'min_duration' => 'required|numeric',
            'passenger' => 'required|numeric',
            'gear' => 'required|string',
            'baggage' => 'required|numeric',
            'door' => 'required|numeric',
            'terms_conditions' => 'required|string',
            'contact_number' => 'required|digits:10',
            'contact_email' => 'required|email',
            'show_price' => 'required',
            'guide_price_per_day' => 'required|numeric',

        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('car-edit/'. $request->id)->withErrors($validate)->withInput();
        } else {
            $slug = str_replace(' ', '-', trim(strtolower($request->title)));
            $Car = MasterCar::find($request->id);

            $master_qty = $change_value = 0;
            if ($Car->quantity < $request->quantity) {
                $master_qty = $request->quantity;
                $change_value = $request->quantity - $Car->quantity;
            }
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) { return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) { return !empty(trim($n)); });

            $Car->vendor_id = $request->vendor_id;
            $Car->title = trim($request->title);
            $Car->slug = $slug;
            $Car->content = addslashes($request->content);
            $Car->city = $request->city;
            $Car->address = addslashes($request->address);
            $Car->map_lat = $request->map_lat;
            $Car->map_lng = $request->map_lng;
            $Car->video = $request->video;
            $Car->quantity = $request->quantity;
            $Car->price_per_hour = $request->price_per_hour;
            $Car->max_distance = $request->max_distance;
            $Car->price_per_km = $request->price_per_km;
            $Car->price_for_halt = $request->price_for_halt;
            $Car->halt_hour = $request->halt_hour;
            $Car->detention_charge_per_hour = $request->detention_charge_per_hour;
            $Car->dist_cover_per_hour = $request->dist_cover_per_hour;
            $Car->free_km_per_hour = $request->free_km_per_hour;
            $Car->min_duration = $request->min_duration;
            $Car->passenger = $request->passenger;
            $Car->gear = $request->gear;
            $Car->baggage = $request->baggage;
            $Car->door = $request->door;
            $Car->contact_email = $request->contact_email;
            $Car->contact_number = $request->contact_number;
            $Car->additional_email = implode(',', $add_email);
            $Car->additional_phone = implode(',', $add_phone);
            $Car->terms_conditions = $request->terms_conditions;
            $Car->status = $request->status;
            $Car->gst_applicable = $request->gst_applicable;
            $Car->update_user = Auth::user()->id;
            $Car->show_price = $request->show_price;
            $Car->gst_number = $request->gst_number;
            $Car->gst_legal_name = $request->gst_legal_name;
            $Car->guide_price_per_day = $request->guide_price_per_day;

            $UploadDir = 'images/cars/';
            $gallery_images = array();
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $old_banner = public_path($Car->banner_image);
                    if (file_exists($old_banner)) {
                        unlink($old_banner);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) .'_'. time() .'.'. $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                    $Car->banner_image = $UploadDir . $banner_image;
                }
            }
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $old_feature = public_path($Car->feature_image);
                    if (file_exists($old_feature)) {
                        unlink($old_feature);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) .'_'. time() .'.'. $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                    $Car->feature_image = $UploadDir . $feature_image;
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                    $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) .'_'. time() .'.'. $file->extension();
                    $file->move(public_path($UploadDir), $gallery_image);
                    array_push($gallery_images, $UploadDir . $gallery_image);
                }
            }
            $old_gallery = !empty($Car->gallery) ? json_decode($Car->gallery) : [];
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
            $gallery_images = array_merge($old_gallery,$gallery_images);
            $Car->gallery = json_encode($gallery_images);

            $faqs = '';
            if($request->faqs) {
                $data = array();
                foreach ($request->faqs as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $faqs = json_encode($data);
            }
            $Car->faqs = $faqs;

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
            $Car->property_slug = implode("~",$property_slug_array);
            $Car->property = json_encode($Property);
            if ($Car->save()) {
                //                if ($master_qty != 0) {
                //                    DB::table('rental_master_inventory')
                //                            ->where(['car_id' => $Car->id])
                //                            ->where('date', '>=', date('Y-m-d'))
                //                            ->increment('total_available', $change_value, ['initial_quantity' => $master_qty]);
                //                }

                Session::flash('success', 'Car details updated successful.');
                return Redirect::to('all-cars');
            } else {
                Session::flash('success', 'Unable to update Car details!');
                return Redirect::to('car-edit/'. $request->id);
            }
        }
    }

    public function carAttribute() {
        if (!(parent::checkViewPrivilege(18))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $CarAttributes = ServiceAttribute::where('service', 'car')->get();

        return view('car-booking.car-attributes', compact('CarAttributes'));
    }

    public function carAttributeAddRequest(Request $request) {
        if (!(parent::checkWritePrivilege(18))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
//            'status' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('car-attribute')->withErrors($validate)->withInput();
        } else {
            $CarAttribute = new ServiceAttribute([
                'name' => $request->name,
                'service' => 'car',
//                'status' => $request->status
            ]);
            if ($CarAttribute->save()) {
                Session::flash('success', 'Hotel attribute added successful.');
                return Redirect::to('car-attribute');
            } else {
                Session::flash('success', 'Unable to add attribute');
                return Redirect::to('car-attribute');
            }
        }
    }

    public function carAttributeTerm($id = null) {
        if (!(parent::checkViewPrivilege(18))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $ServiceAttribute = ServiceAttribute::find($id);
        $AttributeTerms = AttributeValue::where('attr_id', $id)->get();
        $site_url = $this->site;

        return view('car-booking.attribute-terms', compact('ServiceAttribute', 'AttributeTerms', 'site_url'));
    }

    public function carAttributeTermAddRequest(Request $request) {
        if (!(parent::checkWritePrivilege(18))) {
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
            return Redirect::to('car-attribute-terms/'. $request->attr_id)->withErrors($validate)->withInput();
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
                'icon' => $icon_image,
                'attr_id' => $request->attr_id,
//                'status' => $request->status
            ]);
            if ($AttributeTerm->save()) {
                Session::flash('success', 'Attribute term added successful.');
                return Redirect::to('car-attribute-terms/'. $request->attr_id);
            } else {
                Session::flash('success', 'Unable to add attribute term');
                return Redirect::to('car-attribute-terms/'. $request->attr_id);
            }
        }
    }

    public function rentalBlockData() {
        if (!(parent::checkViewPrivilege(57))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('car-booking.rental-block-data');
    }

    public function getRentalBlockData(Request $request) {

        $aColumns = array('car_name', 'quantity', 'block_date', 'block_reason', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "rental_availability";
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

            $status = ($aRow->status == '1') ? 'Activate' : 'Deactivate';
            $row[] = $aRow->car_name;
            $row[] = $aRow->quantity;
            $row[] = $aRow->block_reason;
            $row[] = date("M d Y", strtotime($aRow->block_date));
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function blockRentalVehicle() {
        if (!(parent::checkWritePrivilege(57))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterCar = MasterCar::where('status', 'publish')->where('vendor_id', $vender_id)->orderBy('title', 'ASC')->pluck('title', 'id');
        $carHtml = '';
        foreach ($MasterCar as $key => $value) {
            $carHtml .= '<option value="' . $key . '">' . $value . '</option>';
        }

        return view('car-booking.block-vehicle', compact('MasterCar', 'carHtml'));
    }

    public function blockRentalVehicleRequest(Request $request) {
        if ($request->block_type == 'single') {
            $validate = Validator::make($request->all(), [
                'car_id' => 'required|numeric',
                'block_date' => 'required|string',
                'quantity' => 'required|numeric|min:1',
                'block_reason' => 'required|string'
            ]);
            if ($validate->fails()) {
                $errors = $validate->errors();
                return Redirect::to('block-rental-vehicle')->withErrors($validate)->withInput();
            } else {
                $MasterCar = MasterCar::find($request->car_id);
                $block_date = date("Y-m-d", strtotime($request->block_date));
                $RentalInventory = RentalMasterInventory::where(['car_id' => $request->car_id, 'date' => $block_date])->first();
                if (!empty($RentalInventory)) {
                    $available_quantity = $RentalInventory->total_available;
                        if ($available_quantity >= $request->quantity) {
                            $RentalAvailability = new RentalAvailability([
                                'vendor_id' => $MasterCar->vendor_id,
                                'car_id' => $request->car_id,
                                'car_name' => $MasterCar->title,
                                'block_date' => $block_date,
                                'block_reason' => addslashes($request->block_reason),
                                'quantity' => $request->quantity,
                                'created_by' => Auth::user()->id,
                                'status' => 1
                            ]);
                            if ($RentalAvailability->save()) {
                                $MasterInventory = RentalMasterInventory::where(['car_id' => $request->car_id, 'date' => $block_date])->first();
                                if (!empty($MasterInventory)) {
                                    $MasterInventory->total_available -= $request->quantity;
                                    $MasterInventory->total_blocked += $request->quantity;
                                    $MasterInventory->save();
                                }
                                $OrderLog = new OrderLog([
                                    'vendor_id' => $MasterCar->vendor_id,
                                    'service_id' => $request->car_id,
                                    'date' => $block_date,
                                    'total_blocked' => $request->quantity,
                                    'created_by' => Auth::user()->id
                                ]);
                                $OrderLog->save();

                                Session::flash('success', 'Vehicle blocked successful.');
                                return Redirect::to('rental-block-data');
                            } else {
                                Session::flash('success', 'Unable to block Vehicle.');
                                return Redirect::to('block-rental-vehicle');
                            }
                        } else {
                            Session::flash('success', 'Sorry, Maximum '. $available_quantity .' vehicle can be blocked. please try again.');
                            return Redirect::to('block-rental-vehicle');
                        }
                } else {
                    Session::flash('success', 'Unable to block Vehicle.');
                    return Redirect::to('block-rental-vehicle');
                }
            }
        } else {
            $vehicles = $request->vehicles;
            $check_date = explode(' - ', $request->block_date);
            $block_st_date = date("Y-m-d", strtotime($check_date[0]));
            $block_end_date = date("Y-m-d", strtotime($check_date[1]));
            $BlockedVehicles = BlockedVehicle::whereIn('vehicle_id', $vehicles)
                    ->whereBetween('block_date', [$block_st_date, $block_end_date])
                    ->get()->toArray();
            if (empty($BlockedVehicles)) {
                $Blocked_data = array();
                $key = 0;
                foreach ($vehicles as $value) {
                    $MasterCar = MasterCar::find($value);
                    $tmp_date = $block_st_date;

                    while ($tmp_date <= $block_end_date) {
                        $Blocked_data[$key] = [
                            'vendor_id' => $MasterCar->vendor_id,
                            'vehicle_id' => $value,
                            'vehicle_name' => $MasterCar->title,
                            'block_date' => date("Y-m-d", strtotime($tmp_date)),
                            'block_reason' => $request->block_reason,
                            'created_by' => Auth::user()->id
                        ];
                        $key++;
                        $tmp_date = date("Y-m-d", strtotime($tmp_date .' + 1 day'));
                    }
                }
                $save_status = BlockedVehicle::insert($Blocked_data);

                if ($save_status) {
                    Session::flash('success', 'Vehicles blocked successfully.');
                    return Redirect::to('blocked-vehicles');
                } else {
                    Session::flash('success', 'Unable to block vehicle.');
                    return Redirect::to('block-rental-vehicle');
                }
            } else {
                Session::flash('success', 'Selected vehicles are already blocked for this date. please change date and try again.');
                return Redirect::to('block-rental-vehicle');
            }
        }
    }

    public function blockedVehicles() {
        if (!(parent::checkViewPrivilege(57))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('car-booking.blocked-vehicles');
    }

    public function getBlockedVehicles(Request $request) {

        $aColumns = array('vehicle_name', 'block_date', 'block_reason', 'created_by', 'id');
        $sIndexColumn = "id";
        $sTable = "blocked_vehicles";
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
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $sWhere .= ' AND hotel_id in (' . implode(',', $SubuserAccess) . ')';
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
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
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

            $UserData = User::find($aRow->created_by);

            $row[] = $aRow->vehicle_name;
            $row[] = date("M d Y", strtotime($aRow->block_date));
            $row[] = $aRow->block_reason;
            $row[] = $UserData->first_name . ' ' . $UserData->last_name;
            $row[] = '<a href="javascript:void(0);" class="delete-data" data-id="' . $aRow->id . '">Delete</a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function rentalAvailability() {
        if (!(parent::checkViewPrivilege(59))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterCarQuery = MasterCar::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterCarQuery->where('vendor_id', $vendor_id);
        }
        $MasterCar = $MasterCarQuery->orderBy('title', 'ASC')->pluck('title', 'id');

        return view('car-booking.rental-availability', compact('MasterCar'));
    }

    public function rentalInventory() {
        if (!(parent::checkViewPrivilege(60))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterCar = MasterCar::where('status', 'publish')->where('vendor_id', $vendor_id)->orderBy('title', 'ASC')->pluck('title', 'id');

        return view('car-booking.rental-inventory', compact('MasterCar'));
    }

    public function getRentalInventory(Request $request) {

        $aColumns = array('date', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked');
        $sIndexColumn = "id";
        $sTable = "rental_master_inventory";
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
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND car_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $check_date = explode(' - ', $_POST['searchValue2']);
                $start = date("Y-m-d", strtotime($check_date[0]));
                $end = date("Y-m-d", strtotime($check_date[1]));
                $condition2 .= ' AND date between "' . $start . '" AND "'. $end .'"';
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
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS date, SUM(`initial_quantity`) initial_quantity, SUM(`total_available`) total_available, SUM(`total_booked`) total_booked, SUM(`total_blocked`) total_blocked FROM $sTable $sWhere GROUP BY date $sOrder $sLimit";
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
            $OrderMaster = OrderMaster::select(DB::raw('sum(service_quantity) AS totCancelled'))
                    ->where(['service_type' => 'car','service_name_id' => $_POST['searchValue1'], 'status' => 'cancelled', 'payment_status' => 'success'])
                    ->where('start_date', '<=', $aRow->date)
                    ->where('end_date', '>=', $aRow->date)
                    ->first();
            $total_cancelled = 0;
            if (!empty($OrderMaster)) {
                $total_cancelled = $OrderMaster->totCancelled;
            }

            $row[] = date("d M Y", strtotime($aRow->date));
            $row[] = $aRow->initial_quantity;
            $row[] = $aRow->total_available;
            $row[] = ($aRow->total_booked > 0) ? '<a href="'. url('rental-orders?vehicleId='. $_POST['searchValue1'] .'&date='. $aRow->date) .'" target="_blank" >' . $aRow->total_booked . '</a>' : $aRow->total_booked;
            $row[] = $aRow->total_blocked;
            $row[] = $total_cancelled;

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function rentalOfflineOrder() {
        if (!(parent::checkViewPrivilege(58))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterCar = MasterCar::where('status', 'publish')->where('vendor_id', $vender_id)->orderBy('title', 'ASC')->pluck('title', 'id');
        $CountryData = Country::pluck('name', 'id');
        $CityData = City::where(['state_id' => '4013'])->pluck('name', 'id')->toArray();
        $CityDetail = array_values($CityData);

        return view('car-booking.rental-offline-order', compact('MasterCar', 'CityDetail', 'CountryData'));
    }

    public function createRentalOrder(Request $request) {
        if (!(parent::checkWritePrivilege(58))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
//        echo "<pre>";print_r($request->all());exit;
        $MasterCar = $CarDetails = MasterCar::find($request->service_name_id);
        $state = explode('~', $request->customer_state);
        $country = explode('~', $request->customer_country);

        $bookFlag = 1; $quantity_array = array();
        $service_details = json_decode($request->room_request, 1);
        $travel_distance = $total_travel_hour = 0;$route_map = array();

        $pickup_address = $service_details[0]['pickup_point'];
        $start_date = date("Y-m-d", strtotime($service_details[0]['date']['startDate']));
        $start_time = date("H:i:s", strtotime($service_details[0]['date']['startDate']));
        $end_data = end($service_details);
        $drop_city = $end_data['drop_city'];
        $end_date = date("Y-m-d", strtotime($end_data['date']['endDate']));
        $end_time = date("H:i:s", strtotime($end_data['date']['endDate']));
        $BlockedVehicle = BlockedVehicle::where('vehicle_id', $CarDetails->id)
            ->whereBetween('block_date', [$start_date, $end_date])
            ->get()->toArray();
        if(!empty($BlockedVehicle)) {
            Session::flash('success', 'Unable to create order. Sorry!, the vehicle is not available for dates. Please change date and try again.');
            return Redirect::to('rental-offline-order');
        }

        foreach ($service_details as $val) {
            $travel_distance += $val['day_km'];
            $route_map[] = array(
                'dropPointCity' => $val['drop_city'],
                'dropPonitDetails' => $val['drop_point']
            );

            $totalPrice = 0;
            $difference = strtotime(date("Y-m-d", strtotime($val['date']['endDate']))) - strtotime(date("Y-m-d", strtotime($val['date']['startDate'])));
            $days = floor($difference / (60 * 60 * 24));

            $checkinDate = new DateTime($val['date']['startDate']);
            $checkoutDate = new DateTime($val['date']['endDate']);

            $interval = $checkinDate->diff($checkoutDate);
            $totalHour = $interval->format('%h') + ($interval->format('%d') * 24);
            $total_travel_hour += $totalHour;

            $cal_day = ($days == 0) ? 1 : $days;
            for($i = 0; $i < $cal_day; $i++) {
                $checkDate = date("Y-m-d", strtotime($val['date']['startDate'] .' + '. $i .' days'));
                $MasterInventory = RentalMasterInventory::where(["date" => $checkDate, "car_id" => $MasterCar->id])->first();
                if (!empty($MasterInventory) && $MasterInventory->total_available < 1) {
                    $bookFlag = 0;
                    array_push($quantity_array, date("d-m-Y", strtotime($checkDate)));
                }
            }
        }
        if ($bookFlag == 0) {
            Session::flash('success', 'Unable to create order. Sorry!, the vehicle is not available on date '. implode(", ", $quantity_array) .'. Please change date and try again.');
            return Redirect::to('rental-offline-order');
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $VendorData = User::find($vendor_id);

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
                    'country' => $country[1],
                    'state' => $state[1],
                    'city' => $request->customer_city,
                    'vendor_id' => 0,
                    'role' => 4,
                    'access_type' => 'customer',
                    'login_type' => 'email',
                    'status' => 1,
                    'create_account_approval' => 0
                ]);
                if ($NewUser->save()) {
                    $userId = $NewUser->id;
                }
            }
        }
        $invoice_id = '';
        $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))->where('service_type', 'car')->where('status', '!=', 'partially-cancelled')->first();
        if ($LastOrder->totOrder > 0) {
            $LastInvoiceId = (int)$LastOrder->totOrder + 1;
            $invoice_id = date('dmY'). 'R00'. $LastInvoiceId;
        } else {
            $invoice_id = date('dmY') .'R001';
        }

        $status = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'completed' : 'pending';
        $payment_status = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'success' : 'pending';
        $payment_method = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'cash' : '';
        $Paytm_Mid = ''; $split_status = 0;
        if ($request->payment_gateway == 'paytm') {
            $Paytm_Mid = $CarDetails->paytm_mid;
            $split_status = 1;
        } elseif ($request->payment_gateway == 'hdfc') {
            $Paytm_Mid = $CarDetails->hdfc_mid;
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

        $OrderMaster = new OrderMaster([
            'order_id' => $booking_id,
            'invoice_id' => $invoice_id,
            'vendor_id' => $vendor_id,
            'vendor_name' => $VendorData->company,
            'order_type' => $order_type,
            'customer_id' => $userId,
            'customer_name' => trim($request->customer_name),
            'customer_email' => trim($request->customer_email),
            'customer_phone' => trim($request->customer_phone),
            'customer_address1' => trim($request->customer_address1),
            'customer_city' => trim($request->customer_city),
            'customer_state' => trim($state[0]),
            'customer_zipcode' => trim($request->customer_zipcode),
            'customer_country' => trim($country[0]),
            'book_naration' => addslashes(trim($request->book_naration)),
            'service_type' => 'car',
            'service_name' => $MasterCar->title,
            'service_name_id' => $MasterCar->id,
            'service_city' => $MasterCar->city,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'room_request' => $request->room_request,
            'service_quantity' => $request->service_quantity,
            'pickup_address' => $pickup_address,
            'drop_location' => $drop_city,
            'travel_route' => json_encode($route_map),
            'travel_distance' => $travel_distance,
            'travel_hour' => $total_travel_hour,
            'rental_breakdown' => $request->rental_breakdown,
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
            'request_from' => 'web'
        ]);
        if ($OrderMaster->save()) {
            $OrderMasterId = $OrderMaster->id;
            $OrderMasterNew = OrderMaster::find($OrderMasterId);

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
                $OrderMasterNew->qr_code = $QrCode;
                $OrderMasterNew->qr_verified = 0;
            }
            $count = 0;
            $OrderDetailsData = array();
            if ($CarDetails->gst_applicable == 1) {
                $GstTable = GstTable::where(['vendor_id' => $CarDetails->vendor_id, 'service_type' => 'rental'])
                                ->orderBy('min_amount', 'asc')
                                ->pluck('gst', 'min_amount')->toArray();
                $GstDetails = array();
                if (!empty($GstTable)) {
                    foreach ($GstTable as $k => $gst) {
                        $GstDetails[$k] = json_decode($gst, 1);
                    }
                } else {
                    $GstDetails[0] = $GSTData;
                }
                $GstMin = array_keys($GstDetails);
            }
            $coupon_percent = 0;
            $coupons = Coupon::where(['service_type' => 'rental', 'coupon_code' => $request->coupon_code])
                    ->where('start_date', '<=', $start_date)
                    ->where('end_date', '>=', $start_date)
                    ->first();
            if (!empty($coupons)) {
                $coupon_percent = $coupons->coupon_amount;
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
//            require_once public_path('paytm_lib/encdec_paytm.php');
//            if ($request->payment_gateway == 'paytm') {
//                $paytmParams = array();
//                $expiryDate = date("Y-m-d H:i:s", strtotime("+15 minutes"));
//                $paytmParams["body"] = array(
//                    "mid" => PAYTM_MERCHANT_MID,
//                    "linkType" => "GENERIC",
//                    "linkDescription" => "Odisha Tourism Payment",
//                    "linkName" => $invoice_id,
//                    "amount" => $OrderMasterNew->total_order_price,
//                    "sendSms" => true,
//                    "sendEmail" => true,
//                    "expiryDate" => date("d/m/Y H:i:s", strtotime($expiryDate)),
//                    "partialPayment" => false,
//                    "maxPaymentsAllowed" => 1,
//                    "customerContact" => array("customerName" => $OrderMasterNew->customer_name, "customerEmail" => $OrderMasterNew->customer_email, "customerMobile" => $OrderMasterNew->customer_phone)
//                );
//                if (!empty($OrderMasterNew->paytm_mid)) {
//                    $splitSettlementInfo['splitMethod'] = 'AMOUNT';
//                    $splitSettlementInfo['splitInfo'][] = array('mid' => $OrderMasterNew->paytm_mid, 'amount' => $OrderMasterNew->vendor_amount);
//                    $paytmParams["body"]["splitSettlementInfo"] = $splitSettlementInfo;
//                }
//
//                $checksum = generateSign($paytmParams["body"], PAYTM_MERCHANT_KEY);
//                $paytmParams["head"] = array(
//                    "tokenType" => "AES",
//                    "signature" => $checksum
//                );
//                $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
//                $url = "https://securegw-stage.paytm.in/link/create";
//                if (PAYTM_ENVIRONMENT == 'PROD') {
//                    $url = "https://securegw.paytm.in/link/create";
//                }
//                $ch = curl_init($url);
//                curl_setopt($ch, CURLOPT_POST, true);
//                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
//                $response = curl_exec($ch);
//                $response = json_decode($response, 1);
////                echo "<pre>";print_r($response);exit;
//
//                if ($response['body']['resultInfo']['resultStatus'] == 'SUCCESS') {
//                    $linkId = $response['body']['linkId'];
//                    $longUrl = $response['body']['longUrl'];
//                    $shortUrl = $response['body']['shortUrl'];
//
//                    $OrderMasterNew->offline_link_id = $linkId;
//                    $OrderMasterNew->offline_short_url = $shortUrl;
//                    $OrderMasterNew->offline_long_url = $longUrl;
//                    $OrderMasterNew->offline_link_expiry = $expiryDate;
//                } else {
//                    OrderMaster::find($OrderMasterId)->delete();
//                    Session::flash('success', "Sorry!, Could not able to place order due to some technical issue in generating payment link. Please try again after some time.");
//                    return Redirect::to('rental-offline-order');
//                }
//            }

            foreach ($service_details as $val) {
                $totalPrice = $total_price = $total_gst = $price = 0;
                $tax_array = array();
                $difference = strtotime(date("Y-m-d", strtotime($val['date']['endDate']))) - strtotime(date("Y-m-d", strtotime($val['date']['startDate'])));
                $days = floor($difference / (60 * 60 * 24));

                $checkinDate = new DateTime($val['date']['startDate']);
                $checkoutDate = new DateTime($val['date']['endDate']);

                $interval = $checkinDate->diff($checkoutDate);
                $totalHour = $interval->format('%h') + ($interval->format('%d') * 24);

                $travelHr = ceil($val['day_km'] / $CarDetails->dist_cover_per_hour);
                $cover_day = $days + 1;
                $cover_distance_day = $val['day_km'] / $cover_day;
                if ($cover_distance_day >= $CarDetails->max_distance) {
                    $totalPriceKm = $val['day_km'] * $CarDetails->price_per_km;
                    $halthour = $days * $CarDetails->halt_hour;
                    $totalHaltPrice = $days * $CarDetails->price_for_halt;
//                    $halt_charge += $totalHaltPrice;
                    $detention_hour = $totalHour - $travelHr - $halthour;
                    $detention_charge = 0;
                    if ($detention_hour > 0) {
                        $detention_charge = $detention_hour * $CarDetails->detention_charge_per_hour;
                    }
                    $totalPrice = ($totalPriceKm + $detention_charge + $totalHaltPrice) * $request->service_quantity;
                } else {
                    $extrakm = 0;
                    $totalPriceKm = $travelHr * $CarDetails->price_per_hour;
                    $halthour = $days * $CarDetails->halt_hour;
                    $calculateHr = $totalHour - $halthour;
                    if ($val['day_km'] > ($calculateHr * $CarDetails->free_km_per_hour)) {
                        $extrakm = $val['day_km'] - ($calculateHr * $CarDetails->free_km_per_hour);
                    }
                    $extrakm_price = 0;
                    if ($val['day_km'] > $extrakm) {
                        $extrakm_price = $extrakm * $CarDetails->price_per_km;
                    }
                    $totalPriceHr = $calculateHr * $CarDetails->price_per_hour;
                    $totalHaltPrice = $days * $CarDetails->price_for_halt;
                    $totalPrice = ($totalPriceHr + $totalHaltPrice + $extrakm_price) * $request->service_quantity;
                }

                $coupon_amt = ceil($totalPrice * ($coupon_percent / 100));
                $price = $totalPrice - $coupon_amt;
                $tax_percent = 0;
                if ($CarDetails->gst_applicable == 1) {
                    $filter_res = array_filter($GstMin, function($n) use($price) {
                        return $n <= $price;
                    });
                    if (!empty($filter_res)) {
                        $tempr = $GstDetails[end($filter_res)];
                        if (!empty($tax_array)) {
                            foreach ($tempr as $g_name => $g_val) {
                                $tax_percent += $g_val;
                                $tax_amt = $price * ($g_val / 100);
                                $tax_array[$g_name] += $tax_amt;
                            }
                        } else {
                            foreach ($tempr as $g_name => $g_val) {
                                $tax_percent += $g_val;
                                $tax_amt = $price * ($g_val / 100);
                                $tax_array[$g_name] = $tax_amt;
                            }
                        }
                    }
                }

                foreach ($tax_array as $key => $vals) {
                    $total_gst += $vals;
                }
                $total_price = ceil($price + $total_gst);

                $OrderDetailsData[$count] = [
                    'order_id' => $booking_id,
                    'order_master_id' => $OrderMasterId,
                    'service_type' => 'car',
                    'service_name' => $MasterCar->title,
                    'service_name_id' => $MasterCar->id,
                    'service_city' => $MasterCar->city,
                    'start_date' => date("Y-m-d", strtotime($val['date']['startDate'])),
                    'end_date' => date("Y-m-d", strtotime($val['date']['endDate'])),
                    'start_time' => date("H:i:s", strtotime($val['date']['startDate'])),
                    'end_time' => date("H:i:s", strtotime($val['date']['endDate'])),
                    'pickup_address' => $val['pickup_point'],
                    'pickup_city' => $val['pickup_city'],
                    'drop_address' => $val['drop_point'],
                    'drop_city' => $val['drop_city'],
                    'distance' => $val['day_km'],
                    'service_item_quantity' => $request->service_quantity,
                    'service_item_price' => ceil($totalPrice),
                    'unit_total_price' => ceil($totalPrice),
                    'coupon_amount' => ceil($coupon_amt),
                    'tax_percentage' => ceil($tax_percent),
                    'tax_amount' => ceil($total_gst),
                    'total_room_price' => $total_price,
                    'status' => $status
                ];
                $count++;

                $cal_day = ($days == 0) ? 1 : $days + 1;
                for ($i = 0; $i < $cal_day; $i++) {
                    $checkDate = date("Y-m-d", strtotime($val['date']['startDate'] . ' + ' . $i . ' days'));
                    $MasterInventory = RentalMasterInventory::where(["date" => $checkDate, "car_id" => $MasterCar->id])->first();
//                    if ($MasterInventory->total_available == 0) {
//                        OrderMaster::find($OrderMasterId)->delete();
//                        Session::flash('success', "Sorry!, the vehicle is not available. Please change date and try again.");
//                        return Redirect::to('offline-order');
//                    } else {
                        $MasterInventory->total_available -= 1;
                        $MasterInventory->total_booked += 1;
                        if ($status == 'pending') {
                            $MasterInventory->total_offline_pending += 1;
                        } else {
                            $MasterInventory->total_offline_completed += 1;
                        }
                        $MasterInventory->save();
//                    }
                }
            }
            OrderDetail::insert($OrderDetailsData);
            if (!empty($request->coupon_code)) {
                $Coupon = Coupon::where('coupon_code', $request->coupon_code)->first();
                if (!empty($Coupon)) {
                    $Coupon->already_used = $Coupon->already_used + 1;
                    $Coupon->save();
                }
            }

            $Vendor = User::find($OrderMasterNew->vendor_id);
            $RentalInvoice = EmailTemplate::where('ref_code', 'rentalInvoice')->first();
            if (!empty($RentalInvoice)) {
                $Subject = $RentalInvoice->subject . ' - ' . $OrderMasterNew->service_name . ' - Booking ID - ' . $OrderMasterNew->invoice_id;
                $check_date = date("d M Y", strtotime($OrderMasterNew->start_date)) . ' ' . date("h:i a", strtotime($OrderMasterNew->start_time)) . ' - <br>' . date("d M Y", strtotime($OrderMasterNew->end_date)) . ' ' . date("h:i a", strtotime($OrderMasterNew->end_time));

                $vendorGSTNo = (!empty($CarDetails->gst_number)) ? $CarDetails->gst_number : 'N/A';
                $vendorRegdCompany = (!empty($CarDetails->gst_legal_name)) ? $CarDetails->gst_legal_name : 'N/A';
                $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? 'GSTN No: '. $OrderMaster->gst_regd_no : '';
                $customerGSTCompany = (!empty($OrderMaster->gst_company_name)) ? 'Company Name: '. $OrderMaster->gst_company_name : '';

                $OrderDetails = OrderDetail::where('order_master_id', $OrderMasterNew->id)->get();

                $routes = '';$route_confirm = '';$count = 1;
                foreach ($OrderDetails as $route) {
                    $room_price = $route->unit_total_price;

                    $routes .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMasterNew->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMasterNew->service_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y h:i a", strtotime($route->start_date . ' ' . $route->start_time)) . ' - <br>' . date("d M Y h:i a", strtotime($route->end_date . ' ' . $route->end_time)) . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $route->distance . ' KM</td><td align="right" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($route->unit_total_price, 2) . '</td></tr>';
                    $route_confirm .= '<tr><td width="10%" rowspan="3">' . $count . '</td><td><strong>Pick up Location</strong>: ' . $route->pickup_address . ', ' . $route->pickup_city . '</td><td><strong>Drop Location</strong>: ' . $route->drop_address . ', ' . $route->drop_city . '</td></tr><tr><td><strong>Start Date</strong>: ' . date("d M Y h:i a", strtotime($route->start_date . ' ' . $route->start_time)) . '</td><td><strong>End Date</strong>: ' . date("d M Y h:i a", strtotime($route->end_date . ' ' . $route->end_time)) . '</td></tr><tr><td colspan="2"><strong>Distance</strong>: ' . $route->distance . ' KM</td></tr><tr><td colspan="3">&nbsp;</td></tr>';
                    $count++;
                }
                $txn_id = !empty($OrderMasterNew->transaction_id) ? $OrderMasterNew->transaction_id : 'N/A';
                $discount = !empty($OrderMasterNew->coupon_amount) ? $OrderMasterNew->coupon_amount : '0.00';
                $tspinword = parent::AmountInWords($OrderMasterNew->total_service_price);
                $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~orderdetails~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~tspinword~", "~guidecharge~", "~payuid~"),
                        array($this->site, $OrderMasterNew->customer_name, $OrderMasterNew->customer_phone, $OrderMasterNew->customer_email, $OrderMasterNew->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMasterNew->created_at)), $routes, $OrderMasterNew->total_service_price, $OrderMasterNew->coupon_name, $discount, $OrderMasterNew->sub_total_price, $OrderMasterNew->tax_amount, $OrderMasterNew->total_order_price, strtoupper($OrderMasterNew->payment_gateway), $txn_id, $tspinword, number_format($OrderMasterNew->guide_charge, 2), 'N/A'), $RentalInvoice->source);

                if ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                    $ConfirmTemplate = EmailTemplate::where('ref_code','rentalConfirmMail')->first();
                    if (!empty($ConfirmTemplate)) {
                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMasterNew->service_name .' - Booking ID - '. $OrderMasterNew->invoice_id;
                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~guideservice~", "~invoiceid~"),
                        array($this->site . $Vendor->photo, $OrderMasterNew->customer_name, $OrderMasterNew->service_name, $route_confirm, $OrderMasterNew->total_order_price, $OrderMasterNew->transaction_id, strtoupper($OrderMasterNew->payment_gateway), $CarDetails->terms_conditions, "", $OrderMasterNew->invoice_id), $ConfirmTemplate->source);
                        $OrderMasterNew->confimation_voucher = $msg;
                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                        Mail::to($OrderMasterNew->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                    }

                    $sms_txt = $sms_txt_admin = $manager_contact = $reception_contact = '';
                    $SmsTemplate = SmsTemplate::where('ref_code', 'BookingConfirmUser')->first();
                    if (!empty($SmsTemplate)) {
                        $var3 = $var5 = $var7 = '';
                        $var1 = $OrderMasterNew->customer_name;
                        $var2 = $OrderMasterNew->invoice_id;
                        $var4 = $OrderMasterNew->service_name;
                        $var4 = (strlen($var4) > 30) ? substr(utf8_encode($var4), 0, 27) .'...' : $var4;
                        $var6 = "\n". $OrderMasterNew->vendor_name;
                        $var8 = "\n\n";
                        $var5 = date("d M Y", strtotime($OrderMasterNew->start_date)) .' to '. date("d M Y", strtotime($OrderMasterNew->end_date));
                        $var7 = $CarDetails->contact_number;
                        $manager_contact = $CarDetails->contact_number;

                        $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~", "~var8~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8), $SmsTemplate->source);
                        parent::sendSms($OrderMasterNew->customer_phone, $sms_txt, $SmsTemplate->templete_id);
                    }
                    $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmPackageRental')->first();
                    if (!empty($SmsTemplateAdmin)) {
                        $var1 = $OrderMasterNew->service_name;
                        $var1 = (strlen($var1) > 30) ? substr(utf8_encode($var1), 0, 27) .'...' : $var1;
                        $var2 = $OrderMasterNew->customer_name;
                        $var3 = $OrderMasterNew->customer_phone;
                        $var4 = date("d M Y", strtotime($OrderMasterNew->start_date));
                        $var5 = date("d M Y", strtotime($OrderMasterNew->end_date));
                        $var6 = $OrderMasterNew->invoice_id;

                        $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($var1, $var2, $var3, $var4, $var5, $var6), $SmsTemplateAdmin->source);
                        $sms_recipient =  array();
                        if (!empty($manager_contact))
                            array_push($sms_recipient, $manager_contact);
                        if (!empty($CarDetails->additional_phone))
                            $sms_recipient = array_merge($sms_recipient, explode(",", $CarDetails->additional_phone));
                        if (!empty($sms_recipient)) {
                            $to_sms = implode(',', array_slice($sms_recipient,0,3));
                            parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                        }
                    }
                } else {
                    $Message = str_replace('This is an electronically generated invoice, hence does not require a signature.', 'This is an estimated invoice for payment. You will get a confirmation voucher and invoice after payment.', $Message);
                    $extraMsg = 'Dear '. $OrderMasterNew->customer_name .',<br><br> Please <a href="'. $OrderMasterNew->offline_short_url .'"><strong>click here</strong></a> to complete the payment for booking '. $OrderMasterNew->invoice_id .' of amount Rs. '. $OrderMasterNew->total_order_price;
                    $extraMsg .= '<br><br>If the above link is not working, please go through the following url for payment. <br><br>'. $OrderMasterNew->offline_long_url;
                    $extraMsg .= '<br><br><strong>Note: Please complete the payment as soon as possible.<br>The provisional invoice for your booking is as follows.</strong><br><br>';

                    $Message = $extraMsg . $Message;
                    $Subject = 'Payment link for - ' . $OrderMasterNew->service_name . ' - Booking ID - ' . $OrderMasterNew->invoice_id;
                }
                $service_mail = $CarDetails->contact_email;
                if (!empty($CarDetails->additional_email)) {
                    $service_mail = !empty($service_mail) ? $service_mail .','. $CarDetails->additional_email : $CarDetails->additional_email;
                }
                $OrderMasterNew->invoice = $Message;
                $OrderMasterNew->save();
                $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

//                $admin = User::where('role', 1)->first();
                $receipent = [$Vendor->email];
                if (!empty($service_mail)) {
                    $receipent = array_merge($receipent, explode(',', $service_mail));
                }

                if (Auth::user()->user_role == 'agent_staff') {
                    Mail::to($receipent)
                        ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
                } else {
                    Mail::to($OrderMasterNew->customer_email)
                        ->bcc($receipent)
                        ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
                }
            }
            Session::flash('success', 'Order created successfully.');
            return Redirect::to('rental-offline-order');
        } else {
            Session::flash('success', 'Unable to create order.');
            return Redirect::to('rental-offline-order');
        }
    }

    public function manageRentalInventory() {
        if (!(parent::checkViewPrivilege(69))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterCarQuery = MasterCar::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterCarQuery->where('vendor_id', $vender_id);
//            if ((Auth::user()->role == 3)) {
//                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
//                if (!empty($SubuserAccess)) {
//                    $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
//                }
//            }
        }
        $MasterCar = $MasterCarQuery->orderBy('title', 'ASC')->pluck('title', 'id');

        return view('car-booking.manage-inventory', compact('MasterCar'));
    }

    public function getRentalMasterInventory(Request $request) {

        $aColumns = array('date', 'car_id', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked');
        $sIndexColumn = "id";
        $sTable = "rental_master_inventory";
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
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND car_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $check_date = explode(' - ', $_POST['searchValue2']);
                $start = date("Y-m-d", strtotime($check_date[0]));
                $end = date("Y-m-d", strtotime($check_date[1]));
                $condition2 .= ' AND date between "' . $start . '" AND "'. $end .'"';
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
        $MasterCar = MasterCar::pluck('title', 'id');

        foreach ($rResult as $aRow) {
            $row = array();


            $row[] = date("d M Y", strtotime($aRow->date));
            $row[] = $MasterCar[$aRow->car_id];
            $row[] = '<a href="javascript:void(0)" class="change-qty" title="Click to change quantity" data-toggle="modal" data-target="#changeQtyModal" data-available="'. $aRow->total_available .'" data-id="'. $aRow->id .'" data-qty="'. $aRow->initial_quantity .'">'. $aRow->initial_quantity .'</a>';
            $row[] = $aRow->total_available;
            $row[] = $aRow->total_booked;
            $row[] = ($aRow->total_blocked > 0) ? '<a href="javascript:void(0)" class="release-qty" title="Click to release quantity" data-toggle="modal" data-target="#changeBlockModal" data-blocked="'. $aRow->total_blocked .'" data-id="'. $aRow->id .'">'. $aRow->total_blocked .'</a>': $aRow->total_blocked;

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function rentalMisReport() {
        if (!(parent::checkViewPrivilege(73))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterCarQuery = MasterCar::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterCarQuery->where('vendor_id', $vender_id);
        }
        $MasterCar = $MasterCarQuery->pluck('title', 'id');

        $MisData = array();
        foreach ($MasterCar as $key => $value) {
            $MasterInventory = RentalMasterInventory::select('date', DB::raw('SUM(total_booked) as totalBook'))
                    ->where('car_id', $key)
                    ->where('date', '>=', date("Y-m-d"))
                    ->groupBy('date')
                    ->pluck('totalBook', 'date');
            foreach($MasterInventory as $dates => $qty) {
                $MisData[$dates][$key] = $qty;
            }
        }

        return view('car-booking.rental-mis-report', compact('MisData', 'MasterCar'));
    }

    public function insertRentalInventory() {
        $today = date("Y-m-d");
        $MasterCar = MasterCar::get();
        $count = 0;
        foreach ($MasterCar as $value) {
            $inventory = array();
            $ExistingInventory = RentalMasterInventory::where('car_id', $value->id)->where('date', '>=', $today)->pluck('car_id', 'date')->toArray();
            //for ($i = 0; $i <= 120; $i++) {
            for ($i = 0; $i <= 90; $i++) {
                $date = date("Y-m-d", strtotime($today . ' + ' . $i . ' days'));

                if (!array_key_exists($date, $ExistingInventory)) {
                    $inventory[$count] = [
                        'vendor_id' => $value->vendor_id,
                        'car_id' => $value->id,
                        'date' => $date,
                        'initial_quantity' => $value->quantity,
                        'total_available' => $value->quantity,
                        'total_booked' => 0,
                        'total_blocked' => 0,
                        'total_online_completed' => 0,
                        'total_online_pending' => 0,
                        'total_offline_completed' => 0,
                        'total_offline_pending' => 0
                    ];
                }
                $count++;
            }
            RentalMasterInventory::insert($inventory);
        }
        echo "Inventory added sussceefully.";
        exit;
    }
}
