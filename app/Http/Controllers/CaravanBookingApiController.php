<?php

namespace App\Http\Controllers;

use App\CaravanAvailability;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DateTime;
use PDF;
use App\User;
use App\EmailTemplate;
Use App\MasterHotel;
Use App\ServiceAttribute;
Use App\AttributeValue;
Use App\HotelRoom;
Use App\Country;
Use App\State;
Use App\City;
Use App\Service;
Use App\OrderMaster;
Use App\OrderDetail;
Use App\ServiceReview;
Use App\PageContent;
Use App\MasterCaravan;
Use App\OrderLog;
Use App\PaymentHistory;
Use App\GstDetail;
Use App\Coupon;
Use App\Subscription;
Use App\StaticReview;
Use App\AvailableSlot;
Use App\CategoryTable;
Use App\MerchantProduct;
Use App\ProductCategory;
Use App\MerchantBooking;
Use App\VendorProfile;
Use App\CancelPolicy;
Use App\CustomerRefund;
Use App\GstTable;
Use App\TempOtp;
Use App\SmsTemplate;
Use App\Notification;
Use App\VendorRequest;
Use App\PropertyAccount;
Use App\BlockedVehicle;
Use App\RentalAvailability;
Use App\TmpUser;
use App\BlockedMmtInventory;
use App\SubuserAccess;
use App\Prebooking;
use App\CtpRatePlans;
use App\CustomerInterest;
use App\CaravanMasterInventory;
use App\BlockedCaravan;
use App\CaravanBooking;
class CaravanBookingApiController extends Controller
{
    public $site;
    public $frontendUrl;
    public $AllCategories = array();
    public $ecoStartDate;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
        $this->ecoStartDate = date("Y-m-d", strtotime("2025-07-03"));
        // $this->ApiUrl = env('API_URL');
    }

    /**
     *  @function caravanDetails - get caravan list and details
     *  @request_type - get_caravan_list, get_caravan_details, check_caravan_availability, get_calculate_price
     *
     */

    public function caravanDetails(Request $request){

        $MasterCaravanMax = DB::table('master_caravans')
                    ->where('status', 'publish')->max('price_per_day');
        $responce = ['status' => 0, 'message' => 'Network error, please try again.'];
        if ($request->request_type == 'get_caravan_list') {
            $responce['message'] = '';
            $sort_column = 'vendor_id';
            $sort_direction = 'asc';

            $MasterCaravanQuery = DB::table('master_caravans')
                    ->select('id', 'title', 'slug', 'feature_image', 'city', 'price_per_day', 'map_lat', 'map_lng', 'review_score', 'review_count', 'show_price','quantity')
                    ->where('status', 'publish');

            if(!empty($request->property)) {
                $property = json_decode($request->property, 1);
                $MasterCaravanQuery = $MasterCaravanQuery->whereIn('property_slug', $property);
            }
            if(!empty($request->review_score)) {
                $reviews = json_decode($request->review_score, 1);
                $MasterCaravanQuery->whereIn('review_score', $reviews);
            }
            if($request->startPrice != '' && $request->endPrice != '') {
                $MasterCaravanQuery->whereBetween('price_per_day', [$request->startPrice, $request->endPrice]);
            }
            if ($request->city != '') {
                $MasterCaravanQuery->where('city', 'like', '%'. $request->city .'%');
            }
            if(!empty($request->vendorId)) {
                $vendors = json_decode($request->vendorId, 1);
                $MasterCaravanQuery->whereIn('vendor_id', $vendors);
            }
            if(!empty($request->capacity)) {
                $passenger = json_decode($request->capacity, 1);
                $MasterCaravanQuery->whereIn('passenger', $passenger);
            }
            if (!empty($request->prop_name)) {
                $MasterCaravanQuery->where('title', 'like', '%'. $request->prop_name .'%');
            }
            if ($request->sortColumn != '' && $request->direction != '') {
                $sort_column = $request->sortColumn;
                $sort_direction = $request->direction;
            }
            $MasterCaravanQuery->orderBy($sort_column, $sort_direction);
            $take = 9;
            $skip = ($request->pageno == 0) ? $request->pageno : $request->pageno * $take;
            $totalData = $MasterCaravanQuery->count();

            $MasterCaravan = $MasterCaravanQuery->skip($skip)->take($take)->get()->toArray();

            if (!empty($MasterCaravan)) {
                foreach ($MasterCaravan as $key => $value) {
                    $MasterCaravan[$key]->feature_image = $this->site . $value->feature_image;
                    $value->review_score = !empty($value->review_score) ? round($value->review_score, 2) : 0;
                    $value->review_count = !empty($value->review_count) ? $value->review_count : 0;
                    $value->price_per_day = number_format($value->price_per_day, 0);
                }
                $RelatedCaravan = array();
                if (!empty(isset($request->relatedKey) && !empty($request->relatedKey))) {
                    $RelatedCaravan = DB::table('master_caravans')
                        ->select('id', 'title', 'slug', 'feature_image', 'city', 'price_per_day', 'map_lat', 'map_lng', 'review_score', 'review_count', 'quantity', 'show_price')
                        ->where('status', 'publish')
                        ->where('title', '<>', $request->prop_name)
                        ->where('title', 'like', '%'. $request->relatedKey .'%')
                        ->get()->toArray();
                    if (!empty($RelatedCaravan)) {
                        foreach ($RelatedCaravan as $key => $value) {
                            $RelatedCaravan[$key]->feature_image = $this->site . $value->feature_image;
                            $value->review_score = !empty($value->review_score) ? round($value->review_score, 2) : 0;
                            $value->review_count = !empty($value->review_count) ? $value->review_count : 0;
                            $value->price_per_day = number_format($value->price_per_day, 0);
                        }
                    }
                }

                $responce['totalPage'] = ceil($totalData/$take);
                $responce['length'] = $totalData;
                $responce['max_price'] = $MasterCaravanMax > 0 ? $MasterCaravanMax:10000;
                $responce['status'] = 1;
                $responce['data'] = $MasterCaravan;
                $responce['related_data'] = $RelatedCaravan;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'No caravans available';
            }
            // $responce['banner_image'] = $this->site . 'images/cars/banner-search-space-2.jpg';
        }
        elseif ($request->request_type == 'get_caravan_details') {

            $responce['message'] = '';
            if (isset($request->slug)) {
                $MasterCaravan = MasterCaravan::where(['slug' => $request->slug, 'status' => 'publish'])->first();
            } else {
                $MasterCaravan = MasterCaravan::where(['id' => $request->caravanId, 'status' => 'publish'])->first();
            }

            if (!empty($MasterCaravan)) {
                $UserData = User::find($MasterCaravan->vendor_id);
                $MasterCaravan->vendor_name = $UserData->company;
                $MasterCaravan->vendor_email = $UserData->email;
                $MasterCaravan->vendor_image = $this->site . $UserData->photo;
                $MasterCaravan->member_since = date("M Y", strtotime($UserData->created_at));

                $vendor_profile = VendorProfile::where('vendor_id', $MasterCaravan->vendor_id)->first();
                if (!empty($vendor_profile)) {
                    $MasterCaravan->vendor_profile = $vendor_profile->profile_type;
                    if ($vendor_profile->profile_type == 'own') {
                        $MasterCaravan->vendor_slug = !empty($vendor_profile->profile_url) ? $vendor_profile->profile_url : 'javascript:void(0)';
                    } else {
                        $MasterCaravan->vendor_slug = $vendor_profile->slug;
                    }
                } else {
                    $MasterCaravan->vendor_slug = '';
                }

                $MasterCaravan->feature_image = !empty($MasterCaravan->feature_image) ? $this->site . $MasterCaravan->feature_image : $MasterCaravan->feature_image;
                $MasterCaravan->banner_image = !empty($MasterCaravan->banner_image) ? $this->site . $MasterCaravan->banner_image : $MasterCaravan->banner_image;
                if (!empty($MasterCaravan->gallery)) {
                    $hotel_gallery = json_decode($MasterCaravan->gallery);
                    $MasterCaravan->gallery = array_map(function($val) { return $this->site . $val; } , $hotel_gallery);
                }
                $MasterCaravan->faqs = !empty($MasterCaravan->faqs) ? json_decode($MasterCaravan->faqs, 1) : $MasterCaravan->faqs;
                $Property = !empty($MasterCaravan->property) ? json_decode($MasterCaravan->property, 1) : [];

                $PropertyApp = array();
                if (!empty($Property)) {
                    foreach ($Property as $key => $parent) {
                        foreach ($parent as $ckey => $val) {
                            $Property[$key][$ckey]['icon'] = !empty($val['icon']) ? $this->site . $val['icon'] : '';
                        }
                        $PropertyApp[] = array(
                            'name' => $key,
                            'data' => $Property[$key]
                        );
                    }
                }
                $MasterCaravan->property = $Property;
                $MasterCaravan->property_app = $PropertyApp;

                $days = 0;
                $MasterCaravan->book_date = date("Y-m-d H:i:s", strtotime('+24 hours'));
                if ($request->checkinDate != '' && $request->checkoutDate != '') {
                    if (date("Y-m-d", strtotime($request->checkinDate)) != date("Y-m-d")) {
                        $MasterCaravan->book_date = date("Y-m-d H:i:s", strtotime($request->checkinDate));
                    }
                    $checkinDate = date("Y-m-d", strtotime($request->checkinDate));
                    $checkoutDate = date("Y-m-d", strtotime($request->checkoutDate));
                    $BlockedVehicle = BlockedCaravan::where('caravan_id', $MasterCaravan->id)
                        ->whereBetween('block_date', [$checkinDate, $checkoutDate])
                        ->get()->toArray();
                    if(empty($BlockedVehicle)) {

                        $quantity_array = array();
                        $cal_day = (int)$request->days;
                        for($i = 0; $i < $cal_day; $i++) {
                            $checkDate = date("Y-m-d", strtotime($request->checkinDate .' + '. $i .' days'));
                            $MasterInventory = CaravanMasterInventory::where(["date" => $checkDate, "caravan_id" => $MasterCaravan->id])->first();
                            if (!empty($MasterInventory)) {
                                array_push($quantity_array, $MasterInventory->total_available);
                            } else {
                                array_push($quantity_array, 0);
                            }
                        }
                        if(empty($quantity_array)){
                            array_push($quantity_array, 0);
                        }
                        $MasterCaravan->quantity = min($quantity_array);
                        if ($MasterCaravan->quantity < 1) {
                            $responce['message'] = 'Vehicle is not available in between the choosen dates. Please choose different dates!';
                        }
                    } else {
                        $MasterCaravan->quantity = 0;
                        $responce['message'] = 'Vehicle is not available in between the choosen dates. Please choose different dates!';
                    }
                }

                $responce['status'] = 1;
                $responce['data'] = $MasterCaravan;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Caravan not available';
            }
        }
        elseif ($request->request_type == 'check_caravan_availability') {
            $responce['message'] = '';
            $MasterCaravan = MasterCaravan::find($request->caravanId);
            if (!empty($MasterCaravan)) {
                if ($request->checkinDate != '' && $request->checkoutDate != '') {
                    $request->checkinDate = date("Y-m-d", strtotime($request->checkinDate));
                    $request->checkoutDate = date("Y-m-d", strtotime($request->checkoutDate));
                    $BlockedVehicle = BlockedCaravan::where('caravan_id', $MasterCaravan->id)
                        ->whereBetween('block_date', [$request->checkinDate, $request->checkoutDate])
                        ->get()->toArray();
                    if(empty($BlockedVehicle)) {
                        if ($request->checkinDate == $request->checkoutDate) {
                            $days = 1;
                        } else {
                            $difference = strtotime($request->checkoutDate) - strtotime($request->checkinDate);
                            $days = round($difference / (60 * 60 * 24));
                        }
                        $quantity_array = array();
                        $cal_day = ($days == 0) ? 1 : $days + 1;
                        for($i = 0; $i < $cal_day; $i++) {
                            $checkDate = date("Y-m-d", strtotime($request->checkinDate .' + '. $i .' days'));
                            $MasterInventory = CaravanMasterInventory::where(["date" => $checkDate, "caravan_id" => $MasterCaravan->id])->first();
                            if (!empty($MasterInventory)) {
                                array_push($quantity_array, $MasterInventory->total_available);
                            } else {
                                array_push($quantity_array, 0);
                            }
                        }
                        $MasterCaravan->quantity = min($quantity_array);

                        if ($MasterCaravan->quantity < 1) {
                            $responce['message'] = 'Vehicle is not available in between the choosen dates. Please choose different dates!';
                        }
                    } else {
                        $MasterCaravan->quantity = 0;
                        $responce['message'] = 'Vehicle is not available in between the choosen dates. Please choose different dates!';
                    }
                    $responce['status'] = 1;
                    $responce['available_caravan'] = $MasterCaravan->quantity;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Enter valid check-in and check-out date';
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Car not available';
            }
        }
        elseif ($request->request_type == 'get_calculate_price') {
            $responce['message'] = '';
            $CaravanDetails = MasterCaravan::find($request->caravan_id);
            $start_date = $end_date = $start_time = $end_time = $drop_location = $pickup_address = $route = '';
            $DayBreak = $route = $price_breakup = array();
            if (!empty($CaravanDetails)) {
                if ($request->rental_type == 'default') {
                    $DayBreak[] = array(
                        'date' => [
                            'startDate' => $request->checkinDate,
                            'endDate' => $request->checkoutDate
                        ],
                        'days' => $request->days
                    );

                } else {
                    $DayBreak = json_decode($request->day_breakup_details, 1);
                }
                $sub_total_price = 0;
                foreach ($DayBreak as $val) {
                    $totalPrice = $totalPriceKm = $totalPriceHr = $totalHour = $totalHaltPrice = $detention_charge = $detention_hour = $extrakm_price = $extrakm = $calculateHr = 0;
                    $difference = strtotime(date("Y-m-d", strtotime($val['date']['endDate']))) - strtotime(date("Y-m-d", strtotime($val['date']['startDate'])));
                    $days_diff = floor($difference / (60 * 60 * 24));
                    $days = $request->days;
                    if($days != ($days_diff + 1)) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Start Date and End Date difference should be same as total days.';
                        return response()->json($responce);
                    }

                    $checkinDate = new DateTime($val['date']['startDate']);
                    $checkoutDate = new DateTime($val['date']['endDate']);

                    $totalPrice = $CaravanDetails->price_per_day * (int)$days;

                    $price_breakup[] = array(
                        "price_per_day" => $CaravanDetails->price_per_day,
                        "totaldays" => $days,
                        "booking_date" => array(
                        "startDate" => date("d-m-Y h:i a", strtotime($val['date']['startDate'])),
                        "endDate" => date("d-m-Y h:i a", strtotime($val['date']['endDate']))
                    ),
                    "totalPrice" => $totalPrice
                    );
                    $sub_total_price += $totalPrice;
                }
                $responce['status'] = 1;
                $responce['price_breakup'] = $price_breakup;
                $responce['totalPrice'] = $sub_total_price;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Invalid vehicle id.';
            }
        }
        return response()->json($responce);
    }


    public function caravanBooking(Request $request) {
        $responce = ['status' => 0, 'message' => 'Network error, please try again.'];

        if ($request->request_type == 'book_caravan') {
            $responce['message'] = '';
            $validate = Validator::make($request->all(), [
                'caravan_id' => 'required|numeric',
                'total_caravan' => 'required|numeric',
                'totalPrice' => 'required|numeric',
                //'service_charge' => 'required',
                'days' => 'numeric',
            ]);

            if ($validate->fails()) {
                $responce['status'] = 0;
                $errors = $validate->errors();

                if ($errors->has('caravan_id')) {
                    $responce['message'] = $errors->first('caravan_id');
                } elseif ($errors->has('checkinDate')) {
                    $responce['message'] = $errors->first('checkinDate');
                } elseif ($errors->has('checkoutDate')) {
                    $responce['message'] = $errors->first('checkoutDate');
                } elseif ($errors->has('total_caravan')) {
                    $responce['message'] = $errors->first('total_caravan');
                } elseif ($errors->has('totalPrice')) {
                    $responce['message'] = $errors->first('totalPrice');
                }  elseif ($errors->has('days')) {
                    $responce['message'] = $errors->first('days');
                }
            } else {
                $user = $request->user();
                $lastIdData = OrderMaster::orderBy('id', 'desc')->first();
                $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
                $booking_id = 'OT-'. time() .'-'. $request->vendorId .'-'. $lastId;

                $CaravanDetails = MasterCaravan::where(['id'=> $request->caravan_id, 'status' => 'publish'])->first(); //find($request->car_id);
                $totalPrice = $travel_distance = $travel_hour = $halt_hour = $halt_charge =  0;
                $start_date = $end_date = $start_time = $end_time = $drop_location = $pickup_address = $route =  '';
                $DayBreak = $route = array();
                if (!empty($CaravanDetails)) {
                    if ($request->rental_type == 'default') {
                        // $routes = json_decode($request->route_map, 1);
                        // $end = end($routes);
                        $DayBreak[] = array(
                            'pickup_city' => $CaravanDetails->city,
                            'drop_city' => null,
                            'pickup_point' => $request->pickup_address,
                            'drop_point' => null,
                            'date' => [
                                'startDate' => $request->checkinDate,
                                'endDate' => $request->checkoutDate
                            ],
                            'days' => $request->days
                        );
                        $start_date = date("Y-m-d", strtotime($request->checkinDate));
                        $end_date = date("Y-m-d", strtotime($request->checkoutDate));
                        $start_time = date("h:i a", strtotime($request->checkinDate));
                        $end_time = date("h:i a", strtotime($request->checkoutDate));
                        $drop_location = $request->drop_location;
                        $pickup_address = $request->pickup_address;

                    }
                    else {
                        $DayBreak = json_decode($request->day_breakup_details, 1);
                        $length = count($DayBreak);
                        $start_date = date("Y-m-d", strtotime($DayBreak[0]['date']['startDate']));
                        $end_date = date("Y-m-d", strtotime($DayBreak[$length - 1]['date']['endDate']));
                        $start_time = date("h:i a", strtotime($DayBreak[0]['date']['startDate']));
                        $end_time = date("h:i a", strtotime($DayBreak[$length - 1]['date']['endDate']));
                        $drop_location = $DayBreak[$length - 1]['drop_city'];
                        $pickup_address = $DayBreak[0]['pickup_point'];
                    }
                    if ($start_date < date("Y-m-d") || $start_date > $end_date) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Invalid check in date.';
                        return response()->json($responce);
                    }
                    $BlockedVehicle = BlockedCaravan::where('caravan_id', $CaravanDetails->id)
                        ->whereBetween('block_date', [$start_date, $end_date])
                        ->get()->toArray();
                    if(!empty($BlockedVehicle)) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Sorry! The vehicle is not available for selected dates.';
                        return response()->json($responce);
                    }
                    $route_map = array();
                    $quantity_array = array();
                    $bookFlag = 1;
                    $days = $request->days;

                    $cal_day = $days;
                    for($i = 0; $i < $cal_day; $i++) {
                        $checkDate = date("Y-m-d", strtotime($request->checkinDate .' + '. $i .' days'));
                        $MasterInventory = CaravanMasterInventory::where(["date" => $checkDate, "caravan_id" => $CaravanDetails->id])->first();
                        if (empty($MasterInventory) || (!empty($MasterInventory) && $MasterInventory->total_available < 1)) {
                            $bookFlag = 0;
                            array_push($quantity_array, date("d-m-Y", strtotime($checkDate)));
                        }
                    }

                    // $route_map[] = array(
                    //     'dropPointCity' => $end['dropPointCity'],
                    //     'dropPonitDetails' => $end['dropPonitDetails']
                    // );
                    $total_caravans = 1;
                    if(!empty($request->total_caravans) && $request->total_caravans > 1) {
                        $total_caravans = $request->total_caravans;
                    }
                    $cover_day = $request->days;
                    $totalPrice += ((float)$CaravanDetails->price_per_day * (float)$cover_day) * (int)$total_caravans;

                    if ($request->rental_type == 'default') {
                        $route = $request->route_map;
                    } else {
                        $route = json_encode($route_map);
                    }
                    if ($bookFlag == 0) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Sorry! The vehicle is not available on date '. implode(", ", $quantity_array);
                        return response()->json($responce);
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Sorry! The vehicle is not available for booking.';
                    return response()->json($responce);
                }
                if ($totalPrice != floatval($request->totalPrice)) {
                    $responce['status'] = 0;
                    $responce['message'] = 'Sorry! Price is tampered, please try again.';
                    return response()->json($responce);
                }
                $day_for_guide = str_replace('"', '', $request->no_of_days_for_guide);
                $price_breakup = $request->price_breakup;

                $CarBooking = new CaravanBooking([
                    'booking_id' => $booking_id,
                    'vendor_id' => $CaravanDetails->vendor_id,
                    'caravan_id' => $request->caravan_id,
                    'total_caravans' => $request->total_caravan,
                    'no_of_days' => $request->days,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'drop_location' => $drop_location,
                    'pickup_address' => $pickup_address,
                    'route' => $route,
                    'price_breakup' => $price_breakup,
                    'trip_type' => $request->tripType,
                    'rental_type' => $request->rental_type,
                    'day_breakup' => json_encode($DayBreak),
                    'travel_distance' => $travel_distance,
                    'service_charge' => $CaravanDetails->service_charge,
                    'need_guide' => $request->need_guide,
                    'days_for_guide' => (int)$day_for_guide,
                    'totalPrice' => floatval($request->totalPrice),
                    'user_type' => !empty($user) ? 'registered' : 'guest',
                    'create_user' => !empty($user) ? $user->id : null,
                ]);
                if ($CarBooking->save()) {
                    $responce['status'] = 1;
                    $responce['booking_id'] = $booking_id;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to book.';
                }
            }
        }

        return response()->json($responce);
    }


    public function caravanInventory(Request $request) {
        $responce = ['status' => 0, 'message' => 'Network error, please try again.'];
        if ($request->request_type == 'vehicle_list') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vendor_id = ($user->role == 2) ? $user->id : $user->vendor_id;
                $MasterCar = MasterCaravan::where('status', 'publish')->where('vendor_id', $vendor_id)->pluck('title', 'id');
                $VehicleList = array();
                if (!empty($MasterCar)) {
                    foreach ($MasterCar as $key => $val) {
                        $VehicleList[] = array(
                            'id' => $key,
                            'name' => $val
                        );
                    }
                }
                $responce['status'] = 1;
                $responce['data'] = $VehicleList;
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'get_inventory') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vendor_id = ($user->role == 2) ? $user->id : $user->vendor_id;

                $MasterInventoryQry = CaravanMasterInventory::select('date', 'caravan_id', 'initial_quantity', 'total_available', 'total_blocked', 'id')
                        ->where(['vendor_id' => $vendor_id, 'car_id' => $request->vehicleId])
                        ->whereBetween('date', [date("Y-m-d", strtotime($request->startDate)), date("Y-m-d", strtotime($request->endDate))]);

                // $take = 20;
                // $skip = ($request->pageno == 0) ? $request->pageno : $request->pageno * $take;

                $MasterInventory = $MasterInventoryQry->get(); //->skip($skip)->take($take)
                if (!empty($MasterInventory)) {
                    $MasterCar = MasterCaravan::where('vendor_id', $vendor_id)->pluck('title', 'id')->toArray();
                    foreach ($MasterInventory as $val) {
                        $val->date = date("d M Y", strtotime($val->date));
                        $val->vehicle_name = $MasterCar[$val->caravan_id];
                    }
                }
                $responce['status'] = 1;
                $responce['data'] = $MasterInventory;
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'change_inventory') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vendor_id = ($user->role == 2) ? $user->id : $user->vendor_id;

                $MasterInventory = CaravanMasterInventory::where(['vendor_id' => $vendor_id, 'id' => $request->inventoryId])->first();
                if (!empty($MasterInventory)) {
                    $changeType = $request->changeType;
                    $changeQty = $request->changeQty;
                    $initial_qty = $total_available = $error = 0;
                    if ($changeType == 'decrease') {
                        if ($changeQty > $MasterInventory->total_available) {
                            $responce['status'] = 0;
                            $responce['message'] = 'You can decrease maximum '. $MasterInventory->total_available .' number of quantity.';
                            $error = 1;
                        } else {
                            $initial_qty = $MasterInventory->initial_quantity - $changeQty;
                            $total_available = $MasterInventory->total_available - $changeQty;
                        }
                    } else {
                        $initial_qty = $MasterInventory->initial_quantity + $changeQty;
                        $total_available = $MasterInventory->total_available + $changeQty;
                    }
                    if ($error == 0) {
                        $MasterInventory->initial_quantity = $initial_qty;
                        $MasterInventory->total_available = $total_available;
                        $MasterInventory->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Inventory updated successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = "Unable to update inventory.";
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'release_block_inventory') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vendor_id = ($user->role == 2) ? $user->id : $user->vendor_id;

                $MasterInventory = CaravanMasterInventory::where(['vendor_id' => $vendor_id, 'id' => $request->inventoryId])->first();
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
                    $responce['message'] = "Unable to update inventory.";
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'get_blocked_vehicle_quantity') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vender_id = ($user->role == 2) ? $user->id : $user->vendor_id;
                $take = 10;
                $skip = ($request->pageIndex == 0) ? $request->pageIndex : $request->pageIndex * $take;
                $BlockDataQuery = CaravanAvailability::Select('caravan_name', 'block_reason', 'quantity', 'block_date')
                        ->where('vendor_id', $vender_id)
                        ->orderBy('id', 'DESC');
                $totalData = $BlockDataQuery->count();
                $BlockData = $BlockDataQuery->skip($skip)->take($take)->get();
                if (!empty($BlockDataQuery)) {
                    foreach ($BlockData as $val) {
                        $val->block_date = date("d M Y", strtotime($val->block_date));
                    }
                }
                $responce['status'] = 1;
                $responce['data'] = $BlockData;
                $responce['totalPage'] = ceil($totalData/$take);
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'get_available_quantity') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vender_id = ($user->role == 2) ? $user->id : $user->vendor_id;

                $MasterInventory = CaravanMasterInventory::where(['vendor_id' => $vender_id, 'caravan_id' => $request->vehicleId, 'date' => date("Y-m-d", strtotime($request->checkDate))])->first();
                if (!empty($MasterInventory)) {
                    $responce['status'] = 1;
                    $responce['maxQuantity'] = $MasterInventory->total_available;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid input data.';
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'block_vehicle_quantity') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vender_id = ($user->role == 2) ? $user->id : $user->vendor_id;

                if ($request->block_type == 'single') {
                    $MasterInventory = CaravanMasterInventory::where(['vendor_id' => $vender_id, 'caravan_id' => $request->vehicleId, 'date' => date("Y-m-d", strtotime($request->checkDate))])->first();
                    if (!empty($MasterInventory)) {
                        if ($MasterInventory->total_available >= $request->blockQty) {
                            $MasterCar = MasterCaravan::find($request->vehicleId);
                            $RentalAvailability = new CaravanAvailability([
                                'vendor_id' => $vender_id,
                                'caravan_id' => $request->vehicleId,
                                'caravan_name' => $MasterCar->title,
                                'block_date' => date("Y-m-d", strtotime($request->checkDate)),
                                'block_reason' => $request->blockReason,
                                'quantity' => $request->blockQty,
                                'created_by' => $user->id,
                                'status' => 1
                            ]);
                            if ($RentalAvailability->save()) {
                                $MasterInventory->total_available -= $request->blockQty;
                                $MasterInventory->total_blocked += $request->blockQty;
                                $MasterInventory->save();
                                $responce['status'] = 1;
                                $responce['message'] = 'Inventory blocked successfully.';
                            } else {
                                $responce['status'] = 0;
                                $responce['message'] = 'Unable to block. Please try again.';
                            }
                        } else {
                            $responce['status'] = 0;
                            $responce['message'] = 'You can block maximum '. $MasterInventory->total_available .' no. of vehicles.';
                        }
                    } else {
                        $responce['status'] = 0;
                        $responce['message'] = 'Invalid input data.';
                    }
                } elseif ($request->block_type == 'multiple') {
                    $vehicles = json_decode($request->vehicles, 1);
                    $block_st_date = date("Y-m-d", strtotime($request->startDate));
                    $block_end_date = date("Y-m-d", strtotime($request->endDate));
                    $BlockedVehicles = BlockedCaravan::whereIn('caravan_id', $vehicles)
                            ->whereBetween('block_date', [$block_st_date, $block_end_date])
                            ->get()->toArray();
                    if (empty($BlockedVehicles)) {
                        $Blocked_data = array();
                        $key = 0;
                        foreach ($vehicles as $value) {
                            $MasterCar = MasterCaravan::find($value);
                            $tmp_date = $block_st_date;

                            while ($tmp_date <= $block_end_date) {
                                $Blocked_data[$key] = [
                                    'vendor_id' => $MasterCar->vendor_id,
                                    'caravan_id' => $value,
                                    'vehicle_name' => $MasterCar->title,
                                    'block_date' => date("Y-m-d", strtotime($tmp_date)),
                                    'block_reason' => $request->blockReason,
                                    'created_by' => $user->id
                                ];
                                $key++;
                                $tmp_date = date("Y-m-d", strtotime($tmp_date .' + 1 day'));
                            }
                        }
                        $save_status = BlockedCaravan::insert($Blocked_data);
                        if ($save_status) {
                            $responce['status'] = 1;
                            $responce['message'] = 'Vehicle blocked successfully';
                        } else {
                            $responce['status'] = 0;
                            $responce['message'] = 'Unable to block Vehicle. Please try again.';
                        }
                    } else {
                        $responce['status'] = 0;
                        $responce['message'] = "Selected vehicles are already blocked for this date. please change date and try again.";
                    }
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'get_blocked_vehicles') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vender_id = ($user->role == 2) ? $user->id : $user->vendor_id;
                $take = 10;
                $skip = ($request->pageIndex == 0) ? $request->pageIndex : $request->pageIndex * $take;
                $BlockDataQuery = BlockedCaravan::Select('vehicle_name', 'block_date', 'block_reason', 'id')
                        ->where('vendor_id', $vender_id)
                        ->orderBy('id', 'DESC');
                $totalData = $BlockDataQuery->count();
                $BlockData = $BlockDataQuery->skip($skip)->take($take)->get();
                if (!empty($BlockData)) {
                    foreach ($BlockData as $val) {
                        $val->block_date = date("d M Y", strtotime($val->block_date));
                    }
                }
                $responce['status'] = 1;
                $responce['data'] = $BlockData;
                $responce['totalPage'] = ceil($totalData/$take);
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        elseif ($request->request_type == 'delete_blocked_vehicles') {
            $responce['message'] = '';
            $user = $request->user();
            if (!empty($user)) {
                $vender_id = ($user->role == 2) ? $user->id : $user->vendor_id;

                $BlockData = BlockedCaravan::where('vendor_id', $vender_id)
                        ->where('id', $request->blockId)
                        ->first();
                if (!empty($BlockData)) {
                    $BlockData->delete();
                    $responce['status'] = 1;
                    $responce['message'] = 'Block data deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid block id.';
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = "Invalid user details.";
            }
        }
        return response()->json($responce);
    }

    public function test(){
        $caravan = MasterCaravan::find(1);
        $booked_caravan = $caravan->bookings()->get();
        $blocked_caravan = $caravan->blockedCaravans()->get();
        $caravanDeatails = CaravanMasterInventory::with('caravan')->where('id', 1)->first();
        return response()->json(['booked_caravan' => $booked_caravan, 'blocked_caravan' => $blocked_caravan, 'caravan_details' => $caravanDeatails]);
    }

}
