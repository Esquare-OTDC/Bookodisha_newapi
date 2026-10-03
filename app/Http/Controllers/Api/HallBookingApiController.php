<?php

namespace App\Http\Controllers\Api;

use App\HallModels\BlockedHallInventory;
use App\Http\Controllers\Controller;
use App\Traits\ConferenceTraits;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\HallModels\HallProperty;
use App\HallModels\Hall;
use App\HallModels\HallBooking;
use App\HallModels\HallMasterInventory;
use App\HallModels\Slot;
use App\OrderMaster;

class HallBookingApiController extends Controller
{
    use ConferenceTraits;

    public $site;
    public $frontendUrl;

    public function __construct(Request $request)
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
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');

        $this->setRequest($request);
    }

    /* *****************************************************************************************************************
    * @title Property and Hall Api
    * @authur Saikat Mohanty
    * @date 22/06/2026
    * @params request_type ['get_property_list','check_hall_availability','get_property_details','get_calculate_price']
    * @return ResponseJson
    * Description: This api function will give data according to request type
    ******************************************************************************************************************** */
    public function getPropertyHallDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'request_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'statusCode' => 'error',
                'message' => $validator->errors()->first(),
            ], 200);
        }

        /* Get Property Details */
        if($request->request_type === 'get_property_list') {

            $validator = Validator::make($request->all(), [
                'checkinDate' => 'required|date',
                'checkoutDate' => 'required|date'
            ],[
                'checkinDate.required' => 'Check-in date is required.',
                'checkinDate.date' => 'Check-in date is not a valid date.',
                'checkoutDate.required' => 'Check-out date is required.',
                'checkoutDate.date' => 'Check-out date is not a valid date.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $request->merge([
                'checkinDate' => date('Y-m-d', strtotime($request->checkinDate)),
                'checkoutDate' => date('Y-m-d', strtotime($request->checkoutDate)),
            ]);

            return $this->getPropertyDetails($request);
        }elseif ($request->request_type == 'check_hall_availability') {
            $validator = Validator::make($request->all(), [
                'checkinDate' => 'required|date',
                'checkoutDate' => 'required|date',
                'hall_slug' => 'required|exists:m_hall,slug',
                'property_slug' => 'required|exists:m_property,slug',
                'slot_type' => 'required|in:full_day,first_half,second_half',
            ],[
                'checkinDate.required' => 'Check-in date is required.',
                'checkinDate.date' => 'Check-in date is not a valid date.',
                'checkoutDate.required' => 'Check-out date is required.',
                'checkoutDate.date' => 'Check-out date is not a valid date.',
                'hall_slug.required' => 'Hall slug is required.',
                'hall_slug.exists' => 'The selected hall does not exist.',
                'property_slug.required' => 'Property slug is required.',
                'property_slug.exists' => 'The selected property does not exist.',
                'slot_type.required' => 'Slot type is required.',
                'slot_type.in' => 'Invalid slot type.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $request->merge([
                'checkinDate' => date('Y-m-d', strtotime($request->checkinDate)),
                'checkoutDate' => date('Y-m-d', strtotime($request->checkoutDate)),
            ]);

            return $this->checkRoomAvailability($request);
        }elseif ($request->request_type == 'get_property_details') {
            $validator = Validator::make($request->all(), [
                'property_slug' => 'required|exists:m_property,slug',
                'checkinDate' => 'required|date',
                'checkoutDate' => 'required|date'
            ],[
                'property_slug.required' => 'Property slug is required.',
                'property_slug.exists' => 'The selected property does not exist.',
                'checkinDate.required' => 'Check-in date is required.',
                'checkinDate.date' => 'Check-in date is not a valid date.',
                'checkoutDate.required' => 'Check-out date is required.',
                'checkoutDate.date' => 'Check-out date is not a valid date.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $request->merge([
                'checkinDate' => date('Y-m-d', strtotime($request->checkinDate)),
                'checkoutDate' => date('Y-m-d', strtotime($request->checkoutDate)),
            ]);

            return $this->getPropertyDetails($request);
        }elseif($request->request_type == 'get_calculate_price'){
            $validator = Validator::make($request->all(), [
                'checkinDate' => 'required|date',
                'checkoutDate' => 'required|date',
                'hall_slug' => 'required|exists:m_hall,slug',
                'property_slug' => 'required|exists:m_property,slug',
                'slot_type' => 'required|in:full_day,first_half,second_half',
            ],[
                'checkinDate.required' => 'Check-in date is required.',
                'checkinDate.date' => 'Check-in date is not a valid date.',
                'checkoutDate.required' => 'Check-out date is required.',
                'checkoutDate.date' => 'Check-out date is not a valid date.',
                'hall_slug.required' => 'Hall slug is required.',
                'hall_slug.exists' => 'The selected hall does not exist.',
                'property_slug.required' => 'Property slug is required.',
                'property_slug.exists' => 'The selected property does not exist.',
                'slot_type.required' => 'Slot type is required.',
                'slot_type.in' => 'Invalid slot type.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => $validator->errors()->first(),
                ], 200);
            }

            $request->merge([
                'checkinDate' => date('Y-m-d', strtotime($request->checkinDate)),
                'checkoutDate' => date('Y-m-d', strtotime($request->checkoutDate)),
            ]);

            return $this->fetchCalculatedPrice($request);
        } else {
            return response()->json([
                'status' => 0,
                'statusCode' => 'error',
                'message' => 'Invalid request type.',
            ], 200);
        }
    }

    /* **************************************************************************************
    * @title Check Hall Availabilty
    * @authur Saikat Mohanty
    * @date 22/06/2026
    * Description: This function will check hall availabilty for booking
    **************************************************************************************** */
    private function checkRoomAvailability(Request $request){

        $this->setHallIdBySlug($request->hall_slug);
        $this->setPropertyIdBySlug($request->property_slug);

        $requestedSlot = $request->slot_type;

        $days = $this->calculateDays($request->checkinDate, $request->checkoutDate);

        for($i = 0; $i < $days; $i++){
            $dateToCheck = date("Y-m-d", strtotime($request->checkinDate . ' + ' . $i . ' days'));
            if (!$this->isSlotAvailable($dateToCheck, $requestedSlot)) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => "The requested slot is not available for the date: " . date("Y-m-d", strtotime($dateToCheck)),
                ], 200);
            }
        }
        return response()->json([
            'status' => 1,
            'statusCode' => 'success',
            'message' => "Slot is available for booking ",
        ], 200);
    }

    /* **************************************************************************************
    * @title Property & Hall Details
    * @authur Saikat Mohanty
    * @date 22/06/2026
    * Description: This function will provide details of property and their halls
    **************************************************************************************** */
    private function getPropertyDetails(Request $request){
        /* Get Property Details */
        $propertyDetails = HallProperty::with(['halls'=> function($query) use ($request) {
            $query->select('id','property_id','hall_name','hfacilities_id','room_capacity','feature_image','gallery','publish_status','hcategory_id','slug')->where('publish_status', 'PUBLISH')->where('is_deleted',0)->when($request->filled('hall_slug'), function($q) use ($request){
                  $q->where('slug', $request->hall_slug);
            });
        }])->when($request->filled('property_slug'), function($query) use ($request) {
            $query->where('slug', $request->property_slug);
        })->where('publish_status', 'PUBLISH')->where('is_deleted', 0);
        $sort_column = 'property_name';
        $sort_direction = 'asc';
        if (!empty($request->hall_star)) {
            $star_rate = json_decode($request->hall_star, 1);
            $propertyDetails->whereIn('star_rate', $star_rate);
        }
        if (!empty($request->prop_name)) {
            $propertyDetails->where('property_name', 'like', '%'. $request->prop_name .'%');
        }
        if (!empty($request->review_score)) {
            $reviews = json_decode($request->review_score, 1);
            $propertyDetails->whereIn('review_score', $reviews);
        }

        if ($request->city != '') {
            $propertyDetails->where('place', 'like', '%'. $request->city .'%');
        }
        if ($request->sortColumn != '' && $request->direction != '') {
            $sort_column = $request->sortColumn;
            $sort_direction = $request->direction;
        }

        $start_date = '';
        $end_date = '';
        if($request->checkinDate != '' && $request->checkoutDate!=''){
            $start_date = $request->checkinDate;
            $end_date = $request->checkoutDate;
        }
        $hall_Ids = [];
        $prop = [];

        $property_ids = $this->propertyQuery()
                    ->when($request->filled('category_type'), function($query) use ($request){
                        $hcategoryId = DB::table('m_hcategory')->where('slug', $request->category_type)->first()->id;
                        $query->where('MH.hcategory_id', $hcategoryId);
                    });

        if($request->filled('slot_type')){
            switch($request->slot_type){
                case 'full_day':
                    $property_ids = $property_ids->where(function($query){
                            $query->where('HI.first_half_available','0')
                                ->where('HI.second_half_available','0');
                        });
                    break;

                case 'first_half':
                    $property_ids = $property_ids->where(function($query){
                            $query->where('HI.first_half_available','0')->orWhereNull('HI.inventory_slot_type');
                        });
                    break;

                case 'second_half':
                        $property_ids = $property_ids->where(function($query){
                            $query->where('HI.second_half_available','0')->orWhereNull('HI.inventory_slot_type');
                        });
                    break;
                default:
                        $property_ids = $property_ids->where(function($query){
                            $query->where('HI.first_half_available','0')
                                ->orWhere('HI.second_half_available','0');
                        });
                    break;
            }
        }else{
            $property_ids = $property_ids->where(function($query){
                        $query->where('HI.first_half_available','0')
                            ->orWhere('HI.second_half_available','0');
                    });
        }
        if($request->filled('startPrice') && $request->startPrice != '' && $request->filled('endPrice') && $request->endPrice != '' && $request->endPrice != 0){
            $property_ids = $property_ids->where('MS.slot', 'FULL_DAY')->whereBetween('MS.price',[(float)$request->startPrice, (float)$request->endPrice]);
        }
        if(!empty($start_date) && !empty($end_date)){
             $property_ids = $property_ids->whereBetween('HI.inventory_date', [$start_date, $end_date]);
        }
        if(!$request->filled('property_slug')){
            $property_ids = $property_ids->get();
            if($property_ids->count() > 0){
                foreach($property_ids as $property){
                    $prop[] = $property->id;
                    $hall_Ids[] = $property->hall_id;
                }
                $property_ids = array_unique($prop);
                $hall_Ids = array_unique($hall_Ids);
            }
            $propertyDetails = $propertyDetails->whereIn('id',$property_ids);
        }else{
            if($request->filled('hall_slug')){
                $property_ids = $property_ids->where('MH.slug', $request->hall_slug);
            }
            $property_ids = $property_ids->where('P.slug', $request->property_slug)->get();
            if($property_ids->count() > 0){
                foreach($property_ids as $property){
                    $hall_Ids[] = $property->hall_id;
                }
                $hall_Ids = array_unique($hall_Ids);
            }
        }

        $result = $this->getCategoryLowestCost();

        $maxPrice = DB::table('m_slot')->where('slot', 'FULL_DAY')->max('price');

        $totalData = clone $propertyDetails;
        $relatedData = clone $propertyDetails;
        $take = 9;
        $skip = ($request->pageno == 0) ? 0 : ($request->pageno) * $take;
        $totalData = $totalData->where('status', '1')->get()->count();

        $propertyDetails = $propertyDetails->where('status', '1')->skip($skip)->take($take)->orderBy($sort_column, $sort_direction)->get()->map(function ($property) use ($hall_Ids, $request, $result) {
            $gallery = json_decode($property->gallery, 1);
            $vendor = $property->vendor($this->site);
            return array_merge($vendor,[
                'property_id'=>$property->id,
                'name' => $property->property_name,
                'vendor_id' => $property->vender_id,
                'feature_image' => $this->site.$property->feature_image,
                'banner_image' => $this->site.$property->banner_image,
                'youtube_video' => $property->youtube_video,
                'gallery' => count($gallery) > 0 ? array_map(function($image){ return $this->site.$image; }, $gallery) : [],
                'address' => $property->address,
                'slug' => $property->slug,
                'city'=>$property->cityName(),
                'place'=>$property->place,
                'real_address'=>$property->hotel_address,
                'manager_name'=>$property->manager_name,
                'contact_email'=>$property->contact_email,
                'contact_number'=>$property->manager_contact,
                'terms_conditions'=>$property->terms_condition,
                'gst_applicable'=>$property->gst_applicable,
                'gst_number'=>$property->gst_number,
                'gst_legal_name'=>$property->gst_legal_name,
                'lowest_price'=>$result[$property->id],
                'review_score'=> !empty($property->review_score) ? round($property->review_score, 2) : 0,
                'review_count'=> !empty($property->review_count) ? round($property->review_count, 2) : 0,
                'star_rate'=> !empty($property->star_rate) ? round($property->star_rate, 2) : 0,
                'halls' => $property->halls->whereIn('id',$hall_Ids)->where('is_deleted',0)->map(function ($hall) use ($request) {
                    $facilities = $hall->facilities()->count() > 0 ? $hall->facilitiesByattributes() : [];
                    $gallery = json_decode($hall->gallery, 1);
                    $slot_type = $request->filled('slot_type') ? $this->getSlotType($request->slot_type) : 'FULL_DAY';
                    $slots = $hall->slots->where('slot', $slot_type)->first()->price;
                    return [
                        'name' => $hall->hall_name,
                        'category' => $hall->category ? $hall->category->hcategory_name : null,
                        'room_capacity' => $hall->room_capacity,
                        'feature_image' => $this->site.$hall->feature_image,
                        'slug' => $hall->slug,
                        'request_slot'=> $request->filled('slot_type') ? str_replace('_',' ',strtoupper($request->slot_type)) : 'FULL DAY',
                        'slot_type'=> $request->filled('slot_type') ? str_replace('_',' ',$this->getSlotType($request->slot_type)) : 'FULL DAY',
                        'slot_time'=> $request->filled('slot_type') ? $this->getTime($request->slot_type) : $this->getTime('full_day'),
                        'prices'=> $slots,
                        'gallery' => count($gallery) > 0 ? array_map(function($image){ return $this->site.$image; }, $gallery) : [],

                        'facilities' => $facilities,
                    ];
                }),
            ]);
        });
        $relatedProperty = array();
        if($totalData > 0){
            if (!empty(isset($request->relatedKey) && !empty($request->relatedKey))) {
                $relatedProperty = $relatedData->where('property_name', '<>', $request->prop_name)->where('property_name', 'like', '%'. $request->relatedKey .'%')->where('status', '1')->get()->map(function ($property) {
                    $gallery = json_decode($property->gallery, 1);
                    return [
                        'name' => $property->property_name,
                        'vendor_id' => $property->vender_id,
                        'feature_image' => $property->feature_image,
                        'gallery' => count($gallery) > 0 ? array_map(function($image){ return $this->site.$image; }, $gallery) : [],
                        'address' => $property->address,
                        'slug' => $property->slug,
                        'review_score'=> !empty($property->review_score) ? round($property->review_score, 2) : 0,
                        'review_count'=> !empty($property->review_count) ? round($property->review_count, 2) : 0,
                        'halls' => $property->halls->map(function ($hall) {
                            $facilities = $hall->facilities()->count() > 0 ? $hall->facilities()->pluck('facility_name')->toArray() : [];
                            $gallery = json_decode($hall->gallery, 1);
                            return [
                                'name' => $hall->hall_name,
                                'category' => $hall->category ? $hall->category->hcategory_name : null,
                                'room_capacity' => $hall->room_capacity,
                                'feature_image' => $this->site.$hall->feature_image,
                                'gallery' => count($gallery) > 0 ? array_map(function($image){ return $this->site.$image; }, $gallery) : [],
                                'slug' => $hall->slug,
                                'facilities' => $facilities,
                            ];
                        }),
                    ];
                });
            }
        }
        if($totalData > 0){
            return response()->json([
                'status' => 1,
                'totalPage' => ceil($totalData/$take),
                'length' => $totalData,
                'statusCode' => 'success',
                'message' => 'Property details fetched successfully.',
                'max_price'=> $maxPrice,
                'data' => $propertyDetails,
                'related_data'=>$relatedProperty
            ], 200);
        }else{
            return response()->json([
                'status' => 0,
                'statusCode' => 'success',
                'message' => 'No Property available.',
                'max_price'=> 0,
                'data' =>[],
                'related_data'=>$relatedProperty
            ], 200);
        }

    }

    /* **************************************************************************************
    * @title Calculate Price
    * @authur Saikat Mohanty
    * @date 22/06/2026
    * Description: This function will calculate the hall price
    **************************************************************************************** */
    private function fetchCalculatedPrice(Request $request){
        $this->setHallIdBySlug($request->hall_slug);
        $this->setPropertyIdBySlug($request->property_slug);

        $requestedSlot = $request->slot_type;

        $days = $this->calculateDays($request->checkinDate, $request->checkoutDate);
        $totalPrice = $sub_total_price = 0;

        $slots = Slot::where('hall_id', $this->getHallId())->where('slot', $this->getSlotType($requestedSlot))->where('booking_type', $this->getSlotType($requestedSlot))->where('status','1')->where('is_deleted',0)->first();

        $price_breakup = array();
        for($i = 0; $i < $days; $i++){
            $dateToCheck = date("Y-m-d", strtotime($request->checkinDate . ' + ' . $i . ' days'));
            if (!$this->isSlotAvailable($dateToCheck, $requestedSlot)) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => "The requested slot is not available for the date: " . date("Y-m-d", strtotime($dateToCheck)),
                ], 200);
            }
            $totalPrice = $slots->price;
            $sub_total_price += $totalPrice;
            $price_breakup[] = array(
                "price_per_day" => $slots->price,
                "totaldays" => $i+1,
                "slot_type" => $requestedSlot,
                "booking_date" => array(
                    "startDate" => date("d-m-Y", strtotime($request->checkinDate)),
                    "endDate" => date("d-m-Y", strtotime($dateToCheck))
                ),
                "totalPrice" => $sub_total_price
            );
        }

        return response()->json([
            "status"=>1,
            "price_breakup"=>$price_breakup,
            "totalPrice"=>$sub_total_price,
            "message"=>"Success"
        ],200);

    }

    /* **************************************************************************************
    * @title Booking Hall
    * @authur Saikat Mohanty
    * @date 22/06/2026
    * Description: This api function will book hall according user selection
    **************************************************************************************** */
    public function bookingHall(Request $request){
        $validator = Validator::make($request->all(), [
            'hall_slug' => 'required|exists:m_hall,slug',
            'property_slug' => 'required|exists:m_property,slug',
            'checkinDate' => 'required|date',
            'checkoutDate' => 'required|date',
            'slot_type' => 'required|in:full_day,first_half,second_half',
            //'participant_count'=>'required',
            'totalPrice'=>'required'
        ],[
            'hall_slug.exists' => 'The selected hall is invalid.',
            'hall_slug.required' => 'The hall slug is required.',
            'property_slug.required' => 'The property slug is required.',
            'property_slug.exists' => 'The selected property is invalid.',
            'checkinDate.date' => 'The check-in date is not a valid date.',
            'checkoutDate.date' => 'The check-out date is not a valid date.',
            'slot_type.in' => 'The selected slot type is invalid. It must be one of: Full Day, First Half, Second Half.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'statusCode' => 'error',
                'message' => $validator->errors()->first(),
            ], 200);
        }
        $this->setHallIdBySlug($request->hall_slug);
        $this->setPropertyIdBySlug($request->property_slug);

        $requestedSlot = $request->slot_type;
        $days = $this->calculateDays($request->checkinDate, $request->checkoutDate);

        for($i = 0; $i < $days; $i++){
            $dateToCheck = date("Y-m-d", strtotime($request->checkinDate . ' + ' . $i . ' days'));
            if (!$this->isSlotAvailable($dateToCheck, $requestedSlot)) {
                return response()->json([
                    'status' => 0,
                    'statusCode' => 'error',
                    'message' => "The requested slot is not available for the date: " . date("Y-m-d", strtotime($dateToCheck)),
                ], 200);
            }
        }
        $start_date = $request->checkinDate;
        $end_date = $request->checkoutDate;

        $slots = Slot::where('hall_id', $this->getHallId())->where('slot', $this->getSlotType($requestedSlot))->where('booking_type', $this->getSlotType($requestedSlot))->where('status','1')->where('is_deleted',0)->first();

        DB::beginTransaction();
        try {
            $user = $request->user();
            $lastIdData = OrderMaster::orderBy('id', 'desc')->first();
            $property = HallProperty::where('id', $this->getPropertyId())->where('publish_status','PUBLISH')->where('status','1')->first();
            $halls = Hall::where('id', $this->getHallId())->where('publish_status','PUBLISH')->where('status','1')->first();
            $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
            $booking_id = 'OT-'. time() .'-'. $property->vender_id .'-'. $lastId;
            $BlockedHall = BlockedHallInventory::where('hall_id', $this->getHallId())->where('property_id', $this->getPropertyId())->whereBetween('block_date', [$start_date, $end_date]);
            if($requestedSlot == 'full_day'){
                $BlockedHall = $BlockedHall->whereIn('slot_type',['1','2','3'])->get()->toArray();
            }
            if($requestedSlot == 'first_half'){
                $BlockedHall = $BlockedHall->whereIn('slot_type',['1','2'])->get()->toArray();
            }
            if($requestedSlot == 'second_half'){
                $BlockedHall = $BlockedHall->whereIn('slot_type',['1','3'])->get()->toArray();
            }
            if(!empty($BlockedHall) && count($BlockedHall) > 0) {
                $responce['status'] = 0;
                $responce['message'] = 'Sorry! The hall is not available between selected dates.';
                return response()->json($responce);
            }
            $route_map = array();
            $quantity_array = array();
            $bookFlag = 1;
            $cal_day = $days;
            $totalPrice = $sub_total_price = 0;
            for($i = 0; $i < $cal_day; $i++) {
                $checkDate = date("Y-m-d", strtotime($request->checkinDate .' + '. $i .' days'));
                if (!$this->isSlotAvailable($checkDate, $requestedSlot)) {
                    $bookFlag = 0;
                    array_push($quantity_array, date("d-m-Y", strtotime($checkDate)));
                }else{
                    $totalPrice = $slots->price;
                    $sub_total_price += (float)$totalPrice;
                }
            }
            if ($bookFlag == 0) {
                $responce['status'] = 0;
                $responce['message'] = 'Sorry! The hall is not available on date '. implode(", ", $quantity_array);
                return response()->json($responce);
            }
            if(empty($property) && empty($halls)){
                $responce['status'] = 0;
                $responce['message'] = 'Sorry! The hall is not available for booking.';
                return response()->json($responce);
            }

            /* if((int)$halls->room_capacity < (int)$request->participant_count){
                $responce['status'] = 0;
                $responce['message'] = 'Sorry! The hall capacity is less than available participant';
                return response()->json($responce);
            } */
            $route = $request->route_map;
            $time = $this->getTime($requestedSlot);
            $booking = new HallBooking([
                'hall_id'=>$this->getHallId(),
                'slot_id'=> $slots->id,
                'slot_type'=> strtoupper($requestedSlot),
                'quantity'=>1,
                'start_date'=> $request->checkinDate,
                'end_date'=> $request->checkoutDate,
                'start_time' =>$time['start_time'],
                'end_time'=>$time['end_time'],
                'booking_date'=>date('Y-m-d'),
                'participant_count'=>$request->participant_count ?? 0,
                'route'=>$route,
                'status'=>'0',
                'created_by'=> !empty($user) ? $user->id : null,
                'booking_id'=>$booking_id,
                'vendor_id'=> $property->vender_id,
                'totalPrice'=> $request->totalPrice,
                'price_breakup'=>!empty($request->price_breakup) ? $request->price_breakup:null,
                'user_type'=> !empty($user) ? 'registered' : 'guest',
                'create_user' => !empty($user) ? $user->id : null,
            ]);
            if ($booking->save()) {
                $responce['status'] = 1;
                $responce['booking_id'] = $booking_id;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to book.';
            }
            DB::commit();
            return response()->json($responce, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'statusCode' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /****************************************************************************
    * @title Hall Inventory creation
    * @authur Saikat Mohanty
    * @date 22/06/2026
    * Description: This function will create hall inventory for booking
    * ***************************************************************************/
    public function createInventoryApi(){
        $hall_ids = HallMasterInventory::select('hall_id','vendor_id')->distinct()->orderBy('hall_id', 'desc')->pluck('vendor_id','hall_id')->toArray();

        $response = array();
        if(count($hall_ids) > 0){
            foreach($hall_ids as $hall_id => $hallKey){
                $this->setHallId($hall_id);
                $response[$hall_id] = $this->addInventoryNext90Days($hallKey, new HallMasterInventory());
            }
        }
        return response()->json(['status'=>'OK','data'=>$response]);
    }
}


