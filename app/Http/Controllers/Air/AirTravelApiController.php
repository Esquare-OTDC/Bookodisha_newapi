<?php
namespace App\Http\Controllers\Air;

use App\Http\Controllers\Controller;
use App\Traits\AirTravelTraits;
use Illuminate\Http\Request;
use App\AirModels\AirportMaster;
use App\AirModels\FareMaster;
use App\AirModels\FlightBooking;
use App\AirModels\FlightMaster;
use App\AirModels\FlightSchedule;
use App\AirModels\PassengerDetail;
use App\AirModels\SeatInventory;
use App\AirModels\Cancellation;
use App\AirModels\Reschedule;
use App\CustomerRefund;
use App\EmailTemplate;
use App\GstDetail;
use App\OrderDetail;
use App\OrderMaster;
use App\PaymentHistory;
use App\PropertyAccount;
use App\SmsTemplate;
use App\User;
use App\VendorProfile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;
use PDF;
use Illuminate\Support\Facades\Log;

class AirTravelApiController extends Controller {

    use AirTravelTraits;

    public $site = '';
    public $frontendUrl = '';
    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    public function getDestination(Request $request){
        $airPorts = AirportMaster::select('id as airportno','airport_code as code', 'airport_name as name', 'district_name')->where('is_active',1)->get();
        return response()->json([
            'status' => 1,
            'message' => 'Airports fetched successfully',
            'data' => $airPorts
        ]);
    }

    public function flightSearch(Request $request){
        $validator = Validator::make($request->all(), [
            'source_airport_id' => 'required',
            'destination_airport_id' => 'required|different:source_airport_id',
            'departure_date' => 'required|date_format:Y-m-d',
            'return_date' => 'nullable|date_format:Y-m-d|after_or_equal:departure_date',
            'trip_type' => 'required|in:oneway,roundtrip',
        ],[
            'destination_airport_id.different'=>'Source and destination cannot be same'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $tripType = $request->input('trip_type');

        if($tripType == 'roundtrip' && !$request->has('return_date')) {
            return response()->json([
                'status' => 0,
                'message' => 'Return date is required for round trip.'
            ], 422);
        }

        if($tripType == 'oneway' && $request->has('return_date')) {
            return response()->json([
                'status' => 0,
                'message' => 'Return date should not be provided for one way trip.'
            ], 422);
        }

        $flights = FlightSchedule::with('flight')->whereHas('flight', function ($query) {
                $query->where('is_active', 1);
            })->where('source_airport_id', $request->input('source_airport_id'))
            ->where('destination_airport_id', $request->input('destination_airport_id'));


        $flights = $flights->where('status', 'OPEN')->get();

        $availableFlights = [];

        $departure_date = date('Y-m-d', strtotime($request->input('departure_date')));

        $return_date = null;
        if($tripType == 'roundtrip') {
            $return_date = date('Y-m-d', strtotime($request->input('return_date')));

            if($departure_date == $return_date){
                return response()->json([
                    'status'=>0,
                    'message'=>'Departure date & Return date cannot be same'
                ], 422);
            }
        }

        $availableFlights = DB::select(
            'CALL sp_search_flights(?, ?, ?, ?, ?)',
            [
                $tripType,
                $request->input('source_airport_id'),
                $request->input('destination_airport_id'),
                $departure_date,
                $return_date
            ]
        );

        /* foreach ($flights as $fl) {
            $flightDetails = $fl->flight;
            $airport = $this->getSourceDetails($fl->source_airport_id);
            $airportArr = $this->getSourceDetails($fl->destination_airport_id);
            $seats = DB::table('seat_inventory')->where('schedule_id', $fl->id)->where('date', $departure_date)->where('status','ACTIVE')->first();
            if($seats){
                if($seats->online_booked < $seats->online_capacity && !empty($flightDetails))
                {
                    $availableFlights[] = [
                        'schedule_id'=>$fl->id,
                        'flight_code'=>$flightDetails->airline_code,
                        'flight_name'=>$flightDetails->operator_name,
                        'departure_time'=> date('h:i A', strtotime($fl->departure_time)),
                        'arrival_time'=> date('h:i A', strtotime($fl->arrival_time)),
                        'departure_from'=> $airport ? $airport->city_name : null,
                        'departure_from_code'=> $airport ? $airport->airport_code : null,
                        'arrival_at'=> $airportArr ? $airportArr->city_name : null,
                        'arrival_at_code'=> $airportArr ? $airportArr->airport_code : null,
                        'adult_fare'=> $this->getFarePrice($flightDetails->id),
                        'child_fare'=>$this->getFarePrice($flightDetails->id, 'INFANT'),
                        'booking_start_date'=>$fl->booking_start_date,
                        'booking_end_time'=> date('h:i A', strtotime($fl->booking_end_time)),
                        'days_from_current'=>$fl->days_from_current,
                        'max_ticket_per_txn'=>$fl->per_transaction_ticket_limit,
                        'max_ticket_per_user_per_day'=>$fl->per_day_ticket_limit,
                        'online_capacity'=>$fl->online_capacity,
                        'available_tikeckets'=> ((int)$seats->online_capacity - (int)$seats->online_booked)
                    ];
                }
            }
        } */

        /* $roundTrip = []; */

        /* if($tripType == 'roundtrip') {
            $flights = FlightSchedule::with('flight')->whereHas('flight', function ($query) {
                $query->where('is_active', 1);
            })->where('source_airport_id', $request->input('destination_airport_id'))
            ->where('destination_airport_id', $request->input('source_airport_id'))->where('status', 'OPEN')->get();

            $return_date = date('Y-m-d', strtotime($request->input('return_date')));

            foreach ($flights as $fl) {
                $flightDetails = $fl->flight;
                $airport = $this->getSourceDetails($fl->source_airport_id);
                $airportArr = $this->getSourceDetails($fl->destination_airport_id);
                $seats = DB::table('seat_inventory')->where('schedule_id', $fl->id)->where('date', $return_date)->where('status','ACTIVE')->first();
                if($seats){
                    if($seats->online_booked < $seats->online_capacity && !empty($flightDetails))
                    {
                        $roundTrip[] = [
                            'schedule_id'=>$fl->id,
                            'flight_code'=>$flightDetails->airline_code,
                            'flight_name'=>$flightDetails->operator_name,
                            'departure_time'=> date('h:i A', strtotime($fl->departure_time)),
                            'arrival_time'=> date('h:i A', strtotime($fl->arrival_time)),
                            'departure_from'=> $airport ? $airport->city_name : null,
                            'departure_from_code'=> $airport ? $airport->airport_code : null,
                            'arrival_at'=> $airportArr ? $airportArr->city_name : null,
                            'arrival_at_code'=> $airportArr ? $airportArr->airport_code : null,
                            'adult_fare'=> $this->getFarePrice($flightDetails->id),
                            'child_fare'=>$this->getFarePrice($flightDetails->id, 'INFANT'),
                            'booking_start_date'=>$fl->booking_start_date,
                            'booking_end_time'=> date('h:i A', strtotime($fl->booking_end_time)),
                            'days_from_current'=>$fl->days_from_current,
                            'max_ticket_per_txn'=>$fl->per_transaction_ticket_limit,
                            'max_ticket_per_user_per_day'=>$fl->per_day_ticket_limit,
                            'online_capacity'=>$fl->online_capacity,
                            'available_tikeckets'=> ((int)$seats->online_capacity - (int)$seats->online_booked)
                        ];
                    }
                }
            }
        } */

        /* if($tripType == 'roundtrip'){
            if(empty($roundTrip)){
                return response()->json([
                    'status' => 0,
                    'message' => 'No, Flights available for round trip',
                    'data' => $roundTrip
                ], 422);
            }
            $availableFlights = array_merge($availableFlights, $roundTrip);
        } */

        if(empty($availableFlights)){
            return response()->json([
                'status' => 0,
                'message' => 'No, Flights available',
                'data' => $availableFlights
            ], 422);
        }

        foreach($availableFlights as $key => $flight) {
            $flight = (object) $flight;
            $availableFlights[$key]->departure_time = date('h:i A', strtotime($flight->departure_time));
            $availableFlights[$key]->arrival_time = date('h:i A', strtotime($flight->arrival_time));
        }
        return response()->json([
            'status' => 1,
            'message' => 'Flights fetched successfully',
            'data' => $availableFlights
        ]);
    }


    public function flightBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'departure_date' => 'required|date_format:Y-m-d',
            'return_date' => 'nullable|date_format:Y-m-d|after_or_equal:departure_date',
            'schedule_id'=>'required|integer',
            'return_schedule_id'=>'nullable|different:schedule_id',
            'trip_type' => 'required|in:oneway,roundtrip',
            'adult_count'=>'required|integer|min:1',
            'infant_count'=>'nullable|integer:min:0',
            'total_passengers'=>'required|integer|min:0',
            'total_amount'=>'required|numeric|min:0',
            'return_infant_count'=>'nullable|min:0',
            'return_adult_count'=>'nullable|min:0',
            'departure_infant_count'=>'required|min:0',
            'departure_adult_count'=>'required|min:0',
        ],[
            'return_schedule_id.different'=>'Source and destination flight schedule should be different'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $tripType = $request->input('trip_type');
        $booking = [];

        if($tripType == 'roundtrip' && !$request->has('return_date')) {
            return response()->json([
                'status' => 0,
                'message' => 'Return date is required for round trip.'
            ], 422);
        }

        if($tripType == 'roundtrip' && !$request->has('return_schedule_id')) {
            return response()->json([
                'status' => 0,
                'message' => 'Return schedule is required for round trip.'
            ], 422);
        }

        if($tripType == 'oneway' && $request->has('return_date') && $request->return_date!='') {
            return response()->json([
                'status' => 0,
                'message' => 'Return date should not be provided for one way trip.'
            ], 422);
        }
        $departureSchedule = FlightSchedule::where('id', $request->schedule_id)->first();
        if(empty($departureSchedule)){
            return response()->json([
                'status' => 0,
                'message' => 'Invalid departure schedule.'
            ], 422);
        }
        if((int)$request->departure_adult_count > $departureSchedule->online_capacity){
            return response()->json([
                'status' => 0,
                'message' => 'No. of ticket exceed total capacity.'
            ], 422);
        }
        $departure_date = date('Y-m-d', strtotime($request->departure_date));

        $seats = DB::table('seat_inventory')->where('schedule_id', $request->schedule_id)->where('date', $departure_date)->where('status','ACTIVE')->first();

        if($seats){
            if($seats->online_booked >= $seats->online_capacity || ($seats->online_booked+$request->departure_adult_count) >  $seats->online_capacity)
            {
                return response()->json([
                    'status' => 0,
                    'message' => 'Sorry, tickets for departure is not available on '.date('d-m-Y', strtotime($request->departure_date))
                ], 422);
            }
        }

        $departureDateTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $departure_date . ' ' . $departureSchedule->departure_time
        );

        $bookingCloseTime = $departureDateTime->copy()->subHours(2)->subMinutes(10);

        if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
            return response()->json([
                'status' => 0,
                'message' => 'Online booking is closed. Booking closes 2 hours prior to scheduled departure.'
            ], 422);
        }

        if($departureSchedule->booking_start_date > date('Y-m-d')){
            return response()->json([
                'status' => 0,
                'message' => 'Online booking is not available for this flight'
            ], 422);
        }

        $returnSchedule = null;
        if($tripType == 'roundtrip'){
            $returnSchedule = FlightSchedule::where('id', $request->return_schedule_id)->first();
            if(empty($returnSchedule)){
                return response()->json([
                    'status' => 0,
                    'message' => 'Invalid return schedule.'
                ], 422);
            }
            if($returnSchedule->booking_start_date > date('Y-m-d')){
                return response()->json([
                    'status' => 0,
                    'message' => 'Online booking is not available for this flight'
                ], 422);
            }
            if((int)$request->return_adult_count > $returnSchedule->online_capacity){
                return response()->json([
                    'status' => 0,
                    'message' => 'No. of ticket exceed total capacity.'
                ], 422);
            }

            $return_date = date('Y-m-d', strtotime($request->return_date));
            $seats = DB::table('seat_inventory')->where('schedule_id', $request->return_schedule_id)->where('date', $return_date)->where('status','ACTIVE')->first();

            if($seats){
                if($seats->online_booked >= $seats->online_capacity || ($seats->online_booked+$request->return_adult_count) >  $seats->online_capacity)
                {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Sorry, tickets for return is not available on '. date('d-m-Y', strtotime($request->return_date))
                    ], 422);
                }
            }

            $departureReturnDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $return_date . ' ' . $returnSchedule->departure_time
            );

            $bookingCloseTime = $departureReturnDateTime->copy()->subHours(2)->subMinutes(10);

            if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Online booking is closed. Booking closes 2 hours prior to scheduled return.'
                ], 422);
            }
        }



        $departureFlightDetails = FlightMaster::where('id', $departureSchedule->flight_id)->first();
        $lastIdData = OrderMaster::orderBy('id', 'desc')->first();

        $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
        $booking_id = 'OT-'. time() .'-'. $departureFlightDetails->vendor_id .'-'. $lastId;

        $adult_fare = 0;
        $child_fare = 0;
        $fareData = FareMaster::where('flight_id',$departureSchedule->flight_id)->where('schedule_id', $departureSchedule->id)->pluck('base_fare','passenger_type')->toArray();

        if(empty($fareData)){
            return response()->json([
                'status'=>0,
                'message'=>'Fare price not matched. Please try after sometime'
            ],422);
        }
        if($request->has('departure_adult_count')){
            $adult_fare = (float) $fareData['ADULT'] * (float)$request->departure_adult_count;
        }

        if($request->has('departure_infant_count')){
            $child_fare = (float) ($fareData['INFANT'] ?? 0) * (float)$request->departure_infant_count;
        }

        if($tripType == 'roundtrip'){
            $fareData = FareMaster::where('flight_id',$returnSchedule->flight_id)->where('schedule_id', $returnSchedule->id)->pluck('base_fare','passenger_type')->toArray();
            if(empty($fareData)){
                return response()->json([
                    'status'=>0,
                    'message'=>'Fare price not matched. Please try after sometime'
                ],422);
            }
            if($request->has('return_adult_count')){
                $adult_fare += (float) $fareData['ADULT'] * (float)$request->return_adult_count;
            }

            if($request->has('return_infant_count')){
                $child_fare += (float) ($fareData['INFANT'] ?? 0) * (float)$request->return_infant_count;
            }
        }

        $total_amount = $adult_fare + $child_fare;

        if((float)$total_amount != (float)$request->total_amount){
            return response()->json([
                'status'=>0,
                'message'=> 'Booking amount tempered. Please try  again after sometime'
            ]);
        }

        $booking = [
            'booking_id'=>$booking_id,
            'booking_type'=>$tripType,
            'onward_schedule_id'=>$departureSchedule->id,
            'return_schedule_id'=>$returnSchedule ? $returnSchedule->id : null,
            'adult_count'=>$request->adult_count,
            'infant_count'=>$request->has('infant_count') ? $request->infant_count : 0,
            'return_adult_count'=>$request->has('return_adult_count') ? $request->return_adult_count : 0,
            'return_infant_count'=>$request->has('return_infant_count') ? $request->return_infant_count : 0,
            'total_passengers'=>$request->total_passengers,
            'base_amount'=>$request->total_amount,
            'total_amount'=>$request->total_amount,
            'departure_adult_count'=>$request->departure_adult_count,
            'departure_infant_count'=>$request->departure_infant_count,
            'adult_fare' => $adult_fare,
            'child_fare' => $child_fare,
            'booked_at'=>date('Y-m-d h:i:s'),
            'onward_flight_date'=>$request->has('departure_date') && $request->departure_date !='' ? date('Y-m-d', strtotime($request->departure_date)):null,
            'return_flight_date'=>$request->has('return_date') && $request->return_date !='' ? date('Y-m-d', strtotime($request->return_date)):null,
        ];

        $book = new FlightBooking($booking);
        if($book->save()){
            return response()->json([
                'status'=>1,
                'message'=> 'Booking request processed',
                'booking_id'=> $booking_id
            ]);
        }
        return response()->json([
            'status'=>0,
            'message'=> 'Something went wrong please try after sometime'
        ]);
    }

    public function flightOrderPayment(Request $request){
        $validator = Validator::make($request->all(),[
            'bookingId'=>'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }
        $response = ['status' => 0,'message' => 'Sorry, Something went wrong please try again after sometime.'];

        $flightBooking = FlightBooking::where('booking_id', $request->bookingId)->first();
        $GSTData = GstDetail::pluck('value', 'name')->toArray();

        try{
            if(!empty($flightBooking)){
                $departureSchedule = FlightSchedule::with('flight')->where('id', $flightBooking->onward_schedule_id)->first();

                $departure_date = $flightBooking->onward_flight_date;
                $tripType = $flightBooking->booking_type;

                $checkInventory = SeatInventory::where('date', $departure_date)->where('schedule_id', $flightBooking->onward_schedule_id)->first();

                if(empty($checkInventory)){
                    return response()->json([
                        'status'=>0,
                        'message'=>'Inventory not available'
                    ], 422);
                }
                $availableBookings = $checkInventory->online_capacity - $checkInventory->online_booked;

                if(empty($checkInventory) || ((int)$flightBooking->adult_count > (int)$availableBookings)){
                    return response()->json([
                        'status'=>0,
                        'message'=>'Tickets are not available'
                    ], 422);
                }
                $flightDetails = $departureSchedule->flight;
                $airport = $this->getSourceDetails($departureSchedule->source_airport_id);
                $airportArr = $this->getSourceDetails($departureSchedule->destination_airport_id);

                $flightBooking->vendor_id = $flightDetails->vendor_id;
                $UserData = User::find($flightDetails->vendor_id);

                $flightBooking->vendor_name = $UserData->company;
                $flightBooking->vendor_email = $UserData->email;
                $flightBooking->vendor_image = $this->site . $UserData->photo;
                $flightBooking->member_since = date("M Y", strtotime($UserData->created_at));
                //$flightBooking->map_location = "https://maps.google.com/maps?q=". $flightBooking->map_lat .",". $flightBooking->map_lng ."&hl=en&z=14&amp;output=embed";

                $vendor_profile = VendorProfile::where('vendor_id', $flightDetails->vendor_id)->first();
                if (!empty($vendor_profile)) {
                    $flightBooking->vendor_profile = $vendor_profile->profile_type;
                    if ($vendor_profile->profile_type == 'own') {
                        $flightBooking->vendor_slug = !empty($vendor_profile->profile_url) ? $vendor_profile->profile_url : 'javascript:void(0)';
                    } else {
                        $flightBooking->vendor_slug = $vendor_profile->slug;
                    }
                } else {
                    $flightBooking->vendor_slug = '';
                }

                $flightBooking->departure_flight = [
                    'flight_code'=>$flightDetails->airline_code,
                    'flight_name'=>$flightDetails->operator_name,
                    'departure_time'=> date('h:i A', strtotime($departureSchedule->departure_time)),
                    'arrival_time'=> date('h:i A', strtotime($departureSchedule->arrival_time)),
                    'departure_from'=> $airport ? $airport->city_name : null,
                    'departure_from_code'=> $airport ? $airport->airport_code : null,
                    'arrival_at'=> $airportArr ? $airportArr->city_name : null,
                    'arrival_at_code'=> $airportArr ? $airportArr->airport_code : null
                ];


                if($tripType == 'roundtrip'){
                    $returnSchedule = FlightSchedule::with('flight')->where('id', $flightBooking->return_schedule_id)->first();

                    $return_date = $flightBooking->return_flight_date;

                    $checkInventory = SeatInventory::where('date', $return_date)->where('schedule_id', $flightBooking->return_schedule_id)->first();
                    $availableBookings = $checkInventory->online_capacity - $checkInventory->online_booked;

                    if(empty($checkInventory) || ((int)$flightBooking->adult_count > (int)$availableBookings)){
                        return response()->json([
                            'status'=>0,
                            'message'=>'Tickets are not available for return'
                        ], 422);
                    }

                    $flightDetails = $returnSchedule->flight;
                    $airport = $this->getSourceDetails($departureSchedule->source_airport_id);
                    $airportArr = $this->getSourceDetails($departureSchedule->destination_airport_id);

                    $flightBooking->return_flight = [
                        'flight_code'=>$flightDetails->airline_code,
                        'flight_name'=>$flightDetails->operator_name,
                        'departure_time'=> date('h:i A', strtotime($departureSchedule->departure_time)),
                        'arrival_time'=> date('h:i A', strtotime($departureSchedule->arrival_time)),
                        'departure_from'=> $airport ? $airport->city_name : null,
                        'departure_from_code'=> $airport ? $airport->airport_code : null,
                        'arrival_at'=> $airportArr ? $airportArr->city_name : null,
                        'arrival_at_code'=> $airportArr ? $airportArr->airport_code : null
                    ];
                }

                unset($flightBooking->return_schedule_id);
                unset($flightBooking->onward_schedule_id);
                unset($flightBooking->pnr_code);
                return response()->json([
                        'status'=>1,
                        'message'=>"",
                        'booking_details'=>$flightBooking
                    ]);
            }else{
                return response()->json([
                    'status'=>0,
                    'message'=>'Invalid booking ID '
                ]);
            }
        }catch(\Exception $e){
            return response()->json([
                'status'=>0,
                'message'=>'Unable to proceed. Please try after sometime '.$e->getMessage()
            ]);
        }

        return response()->json($response);
    }


    public function bookingDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id'      => 'required',
            'order_master_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $bookingId     = $request->booking_id;
        $orderMasterId = (int) $request->order_master_id;

        try {
            $order = OrderMaster::where('id', $orderMasterId)->first();
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order master not found.',
                ], 404);
            }
            if ((string) $order->order_id !== (string) $bookingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking ID does not belong to this order.',
                ], 400);
            }

            $flightBooking = FlightBooking::where('booking_id',$bookingId)->first();

            if (!$flightBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flight booking not found.',
                ], 404);
            }

            if (in_array(strtoupper((string) $flightBooking->booking_status),['CANCELLED', 'CANCELED'],true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking has already been cancelled.',
                ], 400);
            }
            $bookingArr = [];
            $bookingArr[0] = $bookingId;
            array_push($bookingArr, $bookingId);
            if($order->price_type == 'reschedule'){
                $parentBooking = FlightBooking::where('id', $flightBooking->reschedule_id)->first();
                $bookings = FlightBooking::where('reschedule_id', $flightBooking->reschedule_id)->pluck('booking_id')->toArray();
                array_push($bookingArr, $parentBooking->booking_id);
                $bookingArr = array_merge($bookingArr, $bookings);
            }
            //return $bookingArr;
            $passengers = PassengerDetail::whereIn('booking_id',$bookingArr)->get();

            if ($passengers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No passengers found for this booking.',
                ], 404);
            }



            $passengerData = [];

            $totalAmount = 0;
            $totalRefundableAmount = 0;
            $confirmedPassengerCount = 0;

            foreach ($passengers as $passenger) {
                if (!$passenger->schedule_id) {
                    continue;
                }

                $schedule = FlightSchedule::where('id',$passenger->schedule_id)->first();

                if (!$schedule) {
                    continue;
                }

                if (!$schedule->flight_id) {
                    continue;
                }

                $passengerFlightId = (int) $schedule->flight_id;

                $flight = FlightMaster::where('id',$passengerFlightId)->first();

                if (!$flight) {
                    continue;
                }
                $passengerType = strtoupper(trim((string) $passenger->passenger_type));
                if (!in_array($passengerType,['ADULT', 'INFANT'],true)) {
                    continue;
                }

                $sourceAirport = AirportMaster::where('id',$schedule->source_airport_id)->first([
                    'city_name',
                    'airport_code'
                ]);

                $destinationAirport = AirportMaster::where('id',$schedule->destination_airport_id)->first([
                    'city_name',
                    'airport_code'
                ]);

                $fare = FareMaster::where('flight_id',$passengerFlightId)
                    ->where('passenger_type',$passengerType)
                    ->where('schedule_id',$schedule->id)
                    ->where('is_active',1)
                    ->first();
                if (!$fare) {
                    continue;
                }

                $ticketAmount = round((float) $fare->base_fare,2);
                $refundableAmount = round(($ticketAmount -1000),2);

                $passengerName = trim(($passenger->first_name ?? '') .' ' .($passenger->last_name ?? ''));

                $journeyType = strtoupper(trim((string) $passenger->journey_type));
                $passengerDate = [];
                if ($journeyType === 'DEPARTURE') {
                    $passengerDate = [
                        'departure_date' => $flightBooking->onward_flight_date
                    ];

                } elseif ($journeyType === 'RETURN') {
                    $passengerDate = [
                        'return_date' => $flightBooking->return_flight_date
                    ];
                }
                switch ((int) $passenger->order_status) {
                    case 0:
                        $orderStatus = 'PENDING';
                        break;

                    case 1:
                        $orderStatus = 'CONFIRMED';
                        break;

                    default:
                        $orderStatus = 'CANCELLED';
                        break;
                }
                $passengerData[] = array_merge(
                    [
                        'passenger_id' => $passenger->id,
                        'name' => $passengerName,
                        'passenger_type' => $passengerType,
                        'journey_type' => $journeyType,
                        'schedule_id' => $passenger->schedule_id,
                        'flight_id' => $passengerFlightId,
                        'flight_name' => $flight->operator_name,

                        'source_airport' => [
                            'city_name' => $sourceAirport ? $sourceAirport->city_name : null,
                            'airport_code' => $sourceAirport ? $sourceAirport->airport_code : null,
                        ],

                        'destination_airport' => [
                            'city_name' => $destinationAirport ? $destinationAirport->city_name : null,
                            'airport_code' => $destinationAirport ? $destinationAirport->airport_code : null,
                        ],

                        'amount' => number_format($ticketAmount,2,'.',''),
                        'refundable_amount' => number_format($refundableAmount,2,'.',''),
                        'order_status' => $orderStatus,
                    ],
                    $passengerDate
                );
                if ($orderStatus === 'CONFIRMED' && in_array($passengerType,['ADULT', 'INFANT'],true)) {
                    $confirmedPassengerCount++;
                    $totalAmount += $ticketAmount;
                    $totalRefundableAmount += $refundableAmount;
                }
            }
            if (empty($passengerData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid passengers found for this booking.',
                ], 404);
            }
            $bookingDate = $flightBooking->booked_at ? date('Y-m-d H:i:s',strtotime($flightBooking->booked_at)) : null;

            return response()->json([
                'success' => true,
                'message' => 'Booking details fetched successfully.',
                'data' => [
                    'order_master_id' => $order->id,
                    'booking_id' => $bookingId,
                    'transaction_id' => $order->transaction_id,
                    'booking' => [
                        'booking_type' => $flightBooking->booking_type,
                        'booking_date' => $bookingDate,
                        'passenger_count' => $confirmedPassengerCount,
                    ],
                    'passengers' => $passengerData,
                    'cancellation_percentage' => 50,
                    'total_amount' => number_format($totalAmount,2,'.',''),
                    'total_refundable_amount' => number_format($totalRefundableAmount,2,'.',''),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch booking details.',
            ], 500);
        }
    }

    public function cancellationRefund(Request $request){
        $validator = Validator::make($request->all(), [
            'orderId'      => 'required',
            'cancel_passengers_departure.*' => 'required',
            'total_amount_refund' => 'required',
            'cancel_reason' => 'required',
            'cancellation_type'=>'required|in:all,partial'
        ],[
            'cancel_passengers_departure.*'=>'Please set passenger for cancellation'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = $request->user();

        $orderMaster = OrderMaster::where('id', $request->orderId)->first();
        if($orderMaster->status != 'completed'){
            return response()->json([
                'status'=>0,
                'message'=>'Payment for invoice '.$orderMaster->invoice_id.' is not completed'
            ]);
        }
        $passengerIds = gettype($request->cancel_passengers_departure) == 'array' ? $request->cancel_passengers_departure : json_decode($request->cancel_passengers_departure, 1);
        if($request->filled('cancel_passengers_return')){
            $passengeRetArr = gettype($request->cancel_passengers_return) == 'array' ? $request->cancel_passengers_return : json_decode($request->cancel_passengers_return, 1);
            $passengerIds = array_merge($passengerIds, $passengeRetArr);
        }
        $passengerIds = array_unique($passengerIds);
        $totalPassengerIds = implode(",", $passengerIds);
        $amountCancel = $this->calculateCancelRefund($totalPassengerIds);

        if(empty($amountCancel)){
            return response()->json([
                'status'=>0,
                'message'=>'Amount not found'
            ]);
        }

        $originBooking = $this->getOriginBookingSP($orderMaster->order_id);
        if(empty($originBooking)){
            return response()->json([
                'status'=>0,
                'message'=>'Parent Booking id not found. Please try after sometime'
            ]);
        }
        $mainBooking = FlightBooking::where('id', $originBooking->parent_id)->first();
        if($orderMaster->price_type == 'reschedule'){
            //$rescheduled = Reschedule::where('new_booking_id', $orderMaster->order_id)->first();
            $oldOrderMaster = OrderMaster::where('order_id', $mainBooking->booking_id)->first();
            $PaymentHistory = PaymentHistory::find($oldOrderMaster->payment_id);
        }else{
            $PaymentHistory = PaymentHistory::find($orderMaster->payment_id);
        }
        if(empty($PaymentHistory)){
            return response()->json([
                'status'=>0,
                'message'=>'Invalid transaction'
            ],422);
        }



        $no_of_adult_count = 0;
        $return_adult_count = 0;
        $totalCancelledId  = 0;
        $returnAmountTobeCancelled = 0;
        $cancellPassenger = [];
        $passengerName = '<table border="1" cellspacing="0"><thead><tr><th>Passenger Name</th><th>Passenger Type</th><th>Journey Type</th></tr></thead><tbody>';
        $bookingArr = [];

        foreach($amountCancel as $cancelled){
            if($cancelled->passenger_type == 'ADULT' && $cancelled->journey_type == 'DEPARTURE'){
                $no_of_adult_count += 1;
            }
            if($cancelled->passenger_type == 'ADULT' && $cancelled->journey_type == 'RETURN'){
                $return_adult_count += 1;
            }
            $totalCancelledId += 1;
            $passengerInfo = PassengerDetail::where('id', $cancelled->passenger_id)->first();
            $cancellPassenger[] = [
                'passenger_id'=>$cancelled->passenger_id,
                'cancellation_reason'=>$request->cancel_reason,
                'cancelled_by'=>$user->id,
                'gross_fare_paid'=>$cancelled->original_fare,
                'cancellation_fee'=>$cancelled->total_cancellation_fee,
                'net_refund_amount'=>$cancelled->refundable_amount,
                'transaction_id'=>$PaymentHistory->transaction_id,
                'cancelled_at'=>now(),
                'created_by'=>$user->id,
                'updated_by'=>$user->id,
                'created_at'=>now(),
                'updated_at'=>now()
            ];
            $bookingArr[] = $passengerInfo->booking_id;
            $passengerName .= '<tr><td>'.$passengerInfo->title.' '.$cancelled->passenger_name.'</td><td>'.$cancelled->passenger_type.'</td><td>'.$cancelled->journey_type.'</td></tr>';

            $totalPrice = $cancelled->total_net_refund_amount;
        }


        $flightBookingDetails = FlightBooking::where('booking_id', $orderMaster->order_id)->with('scheduleOnward','scheduleReturn')->first();

        if (is_null($flightBookingDetails->return_schedule_id)) {
            $flightBookingDetails->unsetRelation('scheduleReturn');
        }


        if(!empty($passengerIds) && count($passengerIds) > 0){
            $departureSchedule = $flightBookingDetails->scheduleOnward;
            $departure_date = $flightBookingDetails->onward_flight_date;
            $departureReturnDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $departure_date . ' ' . $departureSchedule->departure_time
            );

            $bookingCloseTime = $departureReturnDateTime->copy()->subHours(4)->subMinutes(5);

            if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Online cancellation is closed. Booking closes 4 hours prior to scheduled.'
                ], 422);
            }
        }

        if($flightBookingDetails->booking_type == 'roundtrip'){
            $returnFlight = $flightBookingDetails->scheduleReturn;
            if(count($passengerIds) > 0){

                $return_date = $flightBookingDetails->return_flight_date;
                $departureReturnDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $return_date . ' ' . $returnFlight->departure_time
                );

                $bookingCloseTime = $departureReturnDateTime->copy()->subHours(4)->subMinutes(5);

                if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Online cancellation is closed. Booking closes 4 hours prior to scheduled return.'
                    ], 422);
                }

            }

        }

        $Vendor = User::find($orderMaster->vendor_id);
        $refund_amount = $request->total_amount_refund;
        $price = $refundable_tax_amount = 0;
        $deparureAmountTobeCancelled = 0;

        $passengerName .= '</tbody></table>';

        //return $amountCancel;
        if($totalPrice != $refund_amount){
            return response()->json([
                'status'=>0,
                'message'=>'Cancellation amount tempered. Please try  again after sometime'
            ],422);
        }



        require_once public_path('paytm_lib/config_paytm.php');
        $refund_percent = 50;
        $s_type = 'flight';
        $responce = [];
        if (!empty($PaymentHistory) && $orderMaster->payment_gateway == 'hdfc' && $refund_amount > 0) {
            if (!empty($orderMaster->hdfc_key) && !empty($orderMaster->hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                $HDFC_KEY = $orderMaster->hdfc_key;
                $HDFC_SALT = $orderMaster->hdfc_salt;
            }

            $command = "cancel_refund_transaction";
            $var1 = $PaymentHistory->mihpayid;                      //mihpayid
            $reference_id = $var2 = date('dmY') . time();           //request id
            $var3 = $refund_amount;                                 //amount

            $key = $HDFC_KEY;
            $salt = $HDFC_SALT;

            $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
            $hash = strtolower(hash('sha512', $hash_str));

            $r = array('key' => $key, 'hash' => $hash, 'command' => $command, 'var1' => $var1, 'var2' => $var2, 'var3' => $var3);
            $qs = http_build_query($r);
            $wsUrl = VERIFY_URL; //"https://test.payu.in/merchant/postservice.php?form=2";

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
            if (isset($response['status']) && $response['status'] != 1) {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                return response()->json($responce);
            }
            $result_msg = $response['msg'];
            $refund_txn_id = isset($response['bank_ref_num']) ? $response['bank_ref_num'] : '';
            $rquest_id = isset($response['request_id']) ? $response['request_id'] : '';
            $RefundData = new CustomerRefund([
                'vendor_id' => $orderMaster->vendor_id,
                'order_id' => $request->orderId,
                'invoice_id' => $orderMaster->invoice_id,
                'order_type' => $orderMaster->order_type,
                'service_type' => $s_type,
                'service_id' => $orderMaster->service_name_id,
                'customer_id' => $orderMaster->customer_id,
                'order_date' => $orderMaster->created_at,
                'cancel_date' => date("Y-m-d"),
                'paid_amount' => $orderMaster->total_order_price,
                'refund_amount' => $refund_amount,
                'refund_percent' => $refund_percent,
                'payment_method' => $orderMaster->payment_gateway,
                'client_txn_id' => $PaymentHistory->transaction_id,
                'pg_txn_id' => $PaymentHistory->mihpayid,
                'refund_status' => 'PENDING',
                'result_msg' => $result_msg,
                'reference_id' => $rquest_id,
                'refund_txn_id' => $refund_txn_id,
                'response_json' => json_encode($response),
                'payment_id' => $PaymentHistory->id
            ]);
            $RefundData->save();

            if(count($cancellPassenger) > 0){
                foreach($cancellPassenger as $key=>$pg){
                    $cancellPassenger[$key]['refund_txn_id']=$refund_txn_id;
                    $cancellPassenger[$key]['refund_id']=$RefundData->id;
                    $cancellPassenger[$key]['reference_id']=$rquest_id;
                }
            }
        }

        $amt_msg = ($refund_amount > 0) ? "<p style='color:#000000;'>An amount of &#8377;" . number_format($refund_amount, 2) . " will be refunded soon.</p>" : "The total booking amount is forfeited.";
        $Subject = '';
        $Message = "<p style='color:#000000;'>Dear " . $orderMaster->customer_name . ",</p>";
        $Message .= "<p style='color:#000000;'>Your reservation booking for " . $orderMaster->service_name . " having invoice no " . $orderMaster->invoice_id . " for the below passenger has been cancelled successfully.</p>";

        $Message .= $passengerName;

        $Message .= $amt_msg;
        $Vendor = User::find($orderMaster->vendor_id);
        $service_email = '';
        $To = $orderMaster->customer_email;
        $customerGSTNo = (!empty($orderMaster->gst_regd_no)) ? $orderMaster->gst_regd_no : 'N/A';

        $mobileNumber = $orderMaster->customer_phone;
        $User = User::find($orderMaster->customer_id);

        $sms_txt = '';



        $flightDetails = FlightMaster::where('id', $departureSchedule->flight_id)->first();

        Cancellation::insert($cancellPassenger);

        try{
            $response = $this->updateCancelRefund($totalPassengerIds);
        }catch(Exception $e){
            return response()->json([
                'status'=>0,
                'message'=>$e->getMessage()
            ]);
        }

        if($request->cancellation_type == 'all'){
            array_push($bookingArr, $orderMaster->order_id);
            if($orderMaster->price_type == 'reschedule'){
                array_push($bookingArr, $mainBooking->booking_id);
                $totalFlightBooking = FlightBooking::where('reschedule_id', $mainBooking->id)->pluck('booking_id')->toArray();
                $bookingArr = array_merge($bookingArr, $totalFlightBooking);
                $bookingArr = array_values(array_unique($bookingArr));
            }

            OrderMaster::whereIn('order_id',$bookingArr)->update([
                'status'=>'cancelled',
                'cancel_reason'=>$request->cancel_reason,
                'refund_amount'=>$refund_amount,
                'payment_gateway_error'=>2,
                'payment_error_response'=>json_encode($response)
            ]);

            OrderDetail::where('order_master_id', $orderMaster->id)->update(['status' => 'cancelled']);
        }

        if($request->cancellation_type == 'partial'){
            array_push($bookingArr, $orderMaster->order_id);
            if($orderMaster->price_type == 'reschedule'){
                array_push($bookingArr, $mainBooking->booking_id);
                $totalFlightBooking = FlightBooking::where('reschedule_id', $mainBooking->id)->pluck('booking_id')->toArray();
                $bookingArr = array_merge($bookingArr, $totalFlightBooking);
                $bookingArr = array_values(array_unique($bookingArr));
            }
            $passeneger = PassengerDetail::whereIn('booking_id', $bookingArr);
            $cancelledPassenger = clone $passeneger;
            $cancelledPassenger = $cancelledPassenger->where('order_status', 2)->get()->count();


            if($passeneger->get()->count() == $cancelledPassenger){
                OrderMaster::whereIn('order_id',$bookingArr)->update([
                    'status'=>'cancelled',
                    'cancel_reason'=>$request->cancel_reason,
                    'refund_amount'=>$refund_amount,
                    'payment_gateway_error'=>2,
                    'payment_error_response'=>json_encode($response)
                ]);

                OrderDetail::where('order_master_id', $orderMaster->id)->update(['status' => 'cancelled']);
            }
        }




        $check_date = '';
        $customerGSTNo = '';
        $vendorGSTNo = '';
        $flightInvoice = EmailTemplate::where('ref_code', 'flightCancelInvoice')->first();
        $Subject = $flightInvoice->subject . ' - ' . $orderMaster->service_name . ' - Booking ID - ' . $orderMaster->invoice_id;

        /* $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~orderdetails~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~hotelemail~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~hotelgst~", "~usergst~", "~invoiceserial~"),
                                array($this->site, $orderMaster->customer_name, $orderMaster->customer_phone, $orderMaster->customer_email, $this->site . $Vendor->photo, $orderMaster->invoice_id, date("M d Y h:i a", strtotime($orderMaster->created_at)), $orderMaster->service_name, '', $check_date, number_format($orderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $flightDetails->contact_email, date("M d Y h:i a", strtotime($orderMaster->cancel_date)), $orderMaster->payment_method, $orderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo, $orderMaster->invoice_serial), $flightInvoice->source); */

        $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancel')->first();
        if (!empty($SmsTemplate)) {
            $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($orderMaster->customer_name .',', $orderMaster->service_name, $orderMaster->invoice_id, number_format($refund_amount, 2), "\n", $orderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
            parent::sendSms($mobileNumber, $sms_txt, $SmsTemplate->templete_id);
        }

        $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

        $admin = User::where('role', 1)->first();
        $bcc = [$Vendor->email, $admin->email];
        if (!empty($service_email)) {
            $bcc = array_merge($bcc, explode(',', $service_email));
        }

        try {
            Mail::to($To)
                //->bcc($bcc)
                ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
        }
        catch(\Exception $e) {}

        return response()->json([
            'status'=>1,
            'message'=>'Booking cancelled successfully.'
        ]);

    }

    public function rescheduleFlightBooking(Request $request){
        $validator = Validator::make($request->all(), [
            'orderId' => 'required',
            'schedule_id'=>'required',
            'journey_type' => 'required|in:DEPARTURE,RETURN',
            'passengers.*' => 'required',
            'journey_date'=>'required|date_format:Y-m-d',
            'reschedule_type'=>'required|in:all,partial'
        ],[
            'passengers.*'=>'Please select passenger for reschedule'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = $request->user();
        if(empty($user)){
            return response()->json([
                'status'=>0,
                'message'=>'Unauthorized access'
            ],401);
        }

        $total_amount = 1000;
        $user = $request->user();
        $booking_id = '';
        $booking = [];
        $reschedule = [];
        $orderMaster = OrderMaster::where('id', $request->orderId)->first();
        $scheduleDetails = FlightSchedule::where('id', $request->schedule_id)->first();
        if(empty($orderMaster)){
            return response()->json([
                'status'=>0,
                'message'=>'Invaild Transaction'
            ]);
        }
        if($orderMaster->status != 'completed'){
            return response()->json([
                'status'=>0,
                'message'=>'Current booking is not completed yet. Please wait for complettion'
            ]);
        }

        $lastIdData = OrderMaster::orderBy('id', 'desc')->first();
        $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
        $rescheduleFlightCurrentBooking = $this->getOriginBookingSP($orderMaster->order_id);
        if(empty($rescheduleFlightCurrentBooking)){
            return response()->json([
                'status'=>0,
                'message'=>'Primary booking not found. Please try after sometime'
            ]);
        }
        $flightBookingDetails = FlightBooking::where('booking_id', $orderMaster->order_id)->with('scheduleOnward','scheduleReturn')->first();
        $journey_date = date('Y-m-d',strtotime($request->journey_date));
        if($request->journey_type == 'DEPARTURE'){
            if($flightBookingDetails->onward_flight_date == $journey_date && $request->schedule_id == $flightBookingDetails->onward_schedule_id){
                return response()->json([
                    'status'=>0,
                    'message'=>'Please select the different date'
                ],422);
            }
            if($flightBookingDetails->return_flight_date == $journey_date){
                return response()->json([
                    'status'=>0,
                    'message'=>'Please select the different date. Same day departure & return cannot be booked'
                ],422);
            }
            $departureOldSchedule = $flightBookingDetails->scheduleOnward;
            $departureDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $flightBookingDetails->onward_flight_date . ' ' . $departureOldSchedule->departure_time
            );

            $scheduledDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $journey_date . ' ' . $scheduleDetails->departure_time
            );

            // Minimum allowed new journey time
            $minimumScheduledTime = $departureDateTime->copy()
                ->addHours(4)
                ->addMinutes(5);

            if ($scheduledDateTime->lessThan($minimumScheduledTime)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Journey cannot be rescheduled within 4 hours of the original departure time.'
                ], 422);
            }

        }
        if($request->journey_type == 'RETURN'){
            if($flightBookingDetails->return_flight_date == $journey_date && $request->schedule_id == $flightBookingDetails->return_schedule_id){
                return response()->json([
                    'status'=>0,
                    'message'=>'Please select the different date'
                ],422);
            }
            if($flightBookingDetails->onward_flight_date == $journey_date){
                return response()->json([
                    'status'=>0,
                    'message'=>'Please select the different date. Same day departure & return cannot be booked'
                ],422);
            }
            $returnOldSchedule = $flightBookingDetails->scheduleReturn;
            $returnDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $flightBookingDetails->return_flight_date . ' ' . $returnOldSchedule->departure_time
            );

            $scheduledDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $journey_date . ' ' . $scheduleDetails->departure_time
            );

            // Minimum allowed new journey time
            $minimumScheduledTime = $returnDateTime->copy()
                ->addHours(4)
                ->addMinutes(5);

            if ($scheduledDateTime->lessThan($minimumScheduledTime)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Journey cannot be rescheduled within 4 hours of the original return time.'
                ], 422);
            }
        }

        if (is_null($flightBookingDetails->return_schedule_id)) {
            $flightBookingDetails->unsetRelation('scheduleReturn');
        }

        if($flightBookingDetails->is_reschedule == 1){
            return response()->json([
                'status'=>0,
                'message'=>'Flight already rescheduled once cannot be rescheduled again'
            ]);
        }
        DB::beginTransaction();
        try{
            $scheduleDetails = FlightSchedule::where('id', $request->schedule_id)->first();

            ;

            $departure_date = date('Y-m-d', strtotime($request->journey_date));
            $departureDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $departure_date . ' ' . $scheduleDetails->departure_time
            );

            $bookingCloseTime = $departureDateTime->copy()->subHours(4)->subMinutes(5);

            if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Online booking is closed. Booking closes 4 hours prior to scheduled departure.'
                ], 422);
            }




            if($request->journey_type == 'DEPARTURE'){
                $departureOldSchedule = $flightBookingDetails->scheduleOnward;

                $departure_old_date = date('Y-m-d', strtotime($flightBookingDetails->onward_flight_date));
                $departureDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $departure_old_date . ' ' . $departureOldSchedule->departure_time
                );

                $bookingCloseTime = $departureDateTime->copy()->subHours(4)->subMinutes(5);

                if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Online rescheduled is closed. Booking closes 4 hours prior to scheduled departure.'
                    ], 422);
                }

                $flightDetails = FlightMaster::where('id', $departureOldSchedule->flight_id)->first();
                $booking_id = 'OT-'. time() .'-'. $flightDetails->vendor_id .'-'. $lastId;

                $total_count = 0;
                $infant_count = 0;
                $adult_count = 0;
                $passengers = gettype($request->passengers) == 'array' ? $request->passengers : json_decode($request->passengers, 1);
                foreach($passengers as $passengerId){
                    $passengerDetails = PassengerDetail::where('id', $passengerId)->first();
                    if(!empty($passengerDetails) && $passengerDetails->passenger_type == 'ADULT'){
                        $total_count += 1;
                        $adult_count += 1;
                    }else{
                        $infant_count += 1;
                        $total_count += 1;
                    }

                    $reschedule[] = [
                        'passenger_id'=>$passengerId,
                        'old_booking_id'=>$rescheduleFlightCurrentBooking->latest_booking_id,
                        'new_booking_id'=>$booking_id,
                        'old_fare_amount'=>$flightBookingDetails->total_amount,
                        'new_fare_amount'=>($total_amount * $total_count),
                        'net_additional_charge'=>($total_amount * $total_count),
                        'rescheduled_at'=>now(),
                        'updated_at'=>now(),
                        'created_at'=>now(),
                        'created_by'=>$user->id,
                        'updated_by'=>$user->id,
                    ];
                }
                $booking = [
                    'booking_id'=>$booking_id,
                    'booking_type'=>$flightBookingDetails->booking_type,
                    'onward_schedule_id'=>$scheduleDetails->id,
                    'return_schedule_id'=>$rescheduleFlightCurrentBooking->current_return_schedule_id,
                    'adult_count'=>$adult_count,
                    'infant_count'=>$infant_count,
                    'return_adult_count'=>$flightBookingDetails->return_adult_count,
                    'return_infant_count'=>$flightBookingDetails->return_infant_count,
                    'total_passengers'=>$flightBookingDetails->total_passengers,
                    'base_amount'=>($total_amount * $total_count),
                    'total_amount'=>($total_amount * $total_count),
                    'departure_adult_count'=>$adult_count,
                    'departure_infant_count'=>$infant_count,
                    'adult_fare' => $flightBookingDetails->adult_fare,
                    'child_fare' => $flightBookingDetails->child_fare,
                    'booking_status'=>'RESCHEDULE',
                    'booked_at'=>date('Y-m-d h:i:s'),
                    'onward_flight_date'=>$request->has('journey_date') && $request->journey_date !='' ? date('Y-m-d', strtotime($request->journey_date)):null,
                    'return_flight_date'=>$rescheduleFlightCurrentBooking->current_return_flight_date,
                    'gst_regd_no'=>$flightBookingDetails->gst_regd_no,
                    'gst_company_name'=>$flightBookingDetails->gst_company_name,
                    'gst_company_address'=>$flightBookingDetails->gst_company_address,
                    'is_reschedule'=>1,
                    'reschedule_id'=>$rescheduleFlightCurrentBooking->parent_id
                ];

            }

            if($request->journey_type == 'RETURN'){
                $returnSchedule = $flightBookingDetails->scheduleReturn;

                $return_old_date = date('Y-m-d', strtotime($flightBookingDetails->return_flight_date));
                $returnDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $return_old_date . ' ' . $returnSchedule->departure_time
                );

                $bookingCloseTime = $returnDateTime->copy()->subHours(4)->subMinutes(5);

                if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Online rescheduled is closed. Booking closes 4 hours prior to scheduled return.'
                    ], 422);
                }
                $flightDetails = FlightMaster::where('id', $returnSchedule->flight_id)->first();
                $booking_id = 'OT-'. time() .'-'. $flightDetails->vendor_id .'-'. $lastId;

                $total_count = 0;
                $infant_count = 0;
                $adult_count = 0;
                $passengers = gettype($request->passengers) == 'array' ? $request->passengers : json_decode($request->passengers, 1);
                foreach($passengers as $passengerId){
                    $passengerDetails = PassengerDetail::where('id', $passengerId)->first();
                    if(!empty($passengerDetails) && $passengerDetails->passenger_type == 'ADULT'){
                        $total_count += 1;
                        $adult_count += 1;
                    }else{
                        $infant_count += 1;
                        $total_count += 1;
                    }

                    $reschedule[] = [
                        'passenger_id'=>$passengerId,
                        'old_booking_id'=>$rescheduleFlightCurrentBooking->latest_booking_id,
                        'new_booking_id'=>$booking_id,
                        'old_fare_amount'=>$flightBookingDetails->total_amount,
                        'new_fare_amount'=>($total_amount * $total_count),
                        'net_additional_charge'=>($total_amount * $total_count),
                        'rescheduled_at'=>now(),
                        'updated_at'=>now(),
                        'created_at'=>now(),
                        'created_by'=>$user->id,
                        'updated_by'=>$user->id,
                    ];
                }

                $booking = [
                    'booking_id'=>$booking_id,
                    'booking_type'=>$flightBookingDetails->booking_type,
                    'onward_schedule_id'=>$rescheduleFlightCurrentBooking->current_onward_schedule_id,
                    'return_schedule_id'=>$scheduleDetails ? $scheduleDetails->id : null,
                    'adult_count'=>$adult_count,
                    'infant_count'=>$flightBookingDetails->infant_count,
                    'return_adult_count'=>$adult_count,
                    'return_infant_count'=>$infant_count,
                    'total_passengers'=>$flightBookingDetails->total_passengers,
                    'base_amount'=>($total_amount * $total_count),
                    'total_amount'=>($total_amount * $total_count),
                    'departure_adult_count'=>$flightBookingDetails->departure_adult_count,
                    'departure_infant_count'=>$flightBookingDetails->departure_infant_count,
                    'adult_fare' => $flightBookingDetails->adult_fare,
                    'child_fare' => $flightBookingDetails->child_fare,
                    'booking_status'=>'RESCHEDULE',
                    'booked_at'=>date('Y-m-d h:i:s'),
                    'onward_flight_date'=>$rescheduleFlightCurrentBooking->current_onward_flight_date,
                    'return_flight_date'=>$request->has('journey_date') && $request->journey_date !='' ? date('Y-m-d', strtotime($request->journey_date)):null,
                    'gst_regd_no'=>$flightBookingDetails->gst_regd_no,
                    'gst_company_name'=>$flightBookingDetails->gst_company_name,
                    'gst_company_address'=>$flightBookingDetails->gst_company_address,
                    'is_reschedule'=>1,
                    'reschedule_id'=>$rescheduleFlightCurrentBooking->parent_id
                ];
            }
            $newBookingId = FlightBooking::insertGetId($booking);

            //$flightBookingDetails->is_reschedule = 1;
            //$flightBookingDetails->reschedule_id = $newBookingId;

            $flightBookingDetails->save();

            Reschedule::insert($reschedule);

            DB::commit();
            return response()->json([
                'status'=>1,
                'message'=>'Reschedule Booking successfully',
                'order_id'=>$booking_id,
                'journey_type'=>$request->journey_type,
                'passengers'=>$request->passengers,
                'schedule_id'=>$request->schedule_id,
                'journey_date'=>$request->journey_date,
            ]);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json([
                'status'=>0,
                'message'=>$e->getMessage()
            ], 422);
        }

    }

    public function flightOrderPaymentReschedule(Request $request){
        $user = $request->user();
        $vendor_id = ($user->role == 2) ? $user->id : $user->vendor_id;

        $validate = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'schedule_id'=>'required',
            'total_price'=>'required',
            'journey_date'=>'required',
            'payment_gateway'=>'nullable|string',
            'journey_type'=>'required|in:DEPARTURE,RETURN',
            'request_from'=>'required|in:mobile,web',
            'isForeignCitizen'=>'nullable|in:0,1'
        ]);

        if ($validate->fails()) {
            $responce['status'] = 0;
            $responce['message'] = $validate->errors()->first();
            return response()->json($responce);
        }

        if(empty($user)){
            return response()->json([
                'status'=>0,
                'message'=>'Unauthorized access'
            ], 401);
        }
        $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))->where('service_type', 'flight')->where('status', '!=', 'partially-cancelled')->first();
        $newFligthBooking = FlightBooking::where('booking_id', $request->order_id)->first();
        if(empty($newFligthBooking)){
            return response()->json([
                'status'=>0,
                'message'=>'Booking Details is tempered.Please try again'
            ]);
        }
        $scheduleDetails = FlightSchedule::where('id', $request->schedule_id)->first();
        $flightMaster = FlightMaster::where('id', $scheduleDetails->flight_id)->first();
        $rescheduleBookingId = Reschedule::where('new_booking_id', $request->order_id)->first();
        $oldFlightBooking = FlightBooking::where('id', $newFligthBooking->reschedule_id)->first();
        DB::beginTransaction();
        try{
            if($newFligthBooking->total_amount != $request->total_price){
                $responce['status'] = 0;
                $responce['message'] = "Sorry!, the order data has been tampered, please try again.";
                $responce['recall'] = $this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
                return response()->json($responce);
            }

            if ($LastOrder->totOrder > 0) {
                $LastInvoiceId = (int)$LastOrder->totOrder + 1;
                $invoice_id = date('dmY'). 'FR00'. $LastInvoiceId;
            } else {
                $invoice_id = date('dmY') .'FR001';
            }

            $payment_gateway = $request->has('payment_gateway') ? $request->payment_gateway : 'hdfc';

            $Ip = $_SERVER['REMOTE_ADDR'];

            $BookStatus = $coupon_status = 1;
            $distance = $hour = $service_quantity = $total_room = $total_adult = $total_child = $total_guest = $extra_guests = $extra_guest_price = $total_service_price = $sub_total_price = $total_room_category = $adult_price = $child_price = $halt_hour = $halt_charge = $coupon_percent = $vendor_id = $discount_amount = $taxPercent = $taxPrice = $sub_total_price = $guide_charge = $days_for_guide = $split_status = $senior_citizen = 0;
            $price_details = $drop_location = $travel_trip = $gst_regd_no = $gst_company_name = $gst_company_address = $start_date = $end_date = $vendor_name = $price_breakup = $Paytm_Mid = $gate_no = '';
            $start_time = $end_time = $category = $room_slug = $service_name_id = $service_name = $coupon_name = $coupon_code = $travel_route = $pickup_address = $arrival_time = $need_pickup = $request_for_room = '';
            $checkoutDate = null;
            $room_details = array();
            $BookingData = array();
            $service_details = array();
            $Pricing_data = array();
            $vendorData = array();

            $vendor_id = $flightMaster->vendor_id;
            $vendorData = User::find($vendor_id);
            $vendor_name = $vendorData->company;
            $service_name_id = $newFligthBooking->id;
            $start_date = date("Y-m-d", strtotime($request->journey_date));
            $total_adult = $newFligthBooking->adult_count;
            $total_child = $newFligthBooking->infant_count;
            $adult_price = $newFligthBooking->total_amount;
            $child_price = $newFligthBooking->total_amount;
            $start_time = $scheduleDetails->departure_time;
            $end_time = $scheduleDetails->arrival_time;
            $category = 'flight';

            $departure_date = $request->journey_date;

            $departureDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $departure_date . ' ' . $scheduleDetails->departure_time
            );

            $bookingCloseTime = $departureDateTime->copy()->subHours(4);

            if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                $recall = $this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
                return response()->json([
                    'status' => 0,
                    'message' => 'Online booking is closed. Booking closes 4 hours prior to scheduled departure.',
                    'recall'=>$recall
                ], 422);
            }

            $MasterInventory = SeatInventory::where(['schedule_id' => $scheduleDetails->id,'date' => $departure_date])->first();
            $adult_count = 0;
            $infant_count = 0;
            $old_schedule_id = '';
            $old_journey_date = '';
            if($request->journey_type == 'DEPARTURE'){
                $adult_count = $newFligthBooking->departure_adult_count;
                $infant_count = $newFligthBooking->departure_infant_count;
                $old_schedule_id = $oldFlightBooking->onward_schedule_id;
                $old_journey_date = $oldFlightBooking->onward_flight_date;
            }
            if($request->journey_type == 'RETURN'){
                $adult_count = $newFligthBooking->return_adult_count;
                $infant_count = $newFligthBooking->return_infant_count;
                $old_schedule_id = $oldFlightBooking->return_schedule_id;
                $old_journey_date = $oldFlightBooking->return_flight_date;
            }
            if(!empty($MasterInventory)){
                if(((int)$adult_count + $MasterInventory->online_booked) > $MasterInventory->online_capacity){
                    $responce['status'] = 0;
                    $responce['message'] = 'Sorry! The flight is not available on ' . date('d-m-Y',strtotime($departure_date));
                    $responce['recall'] =$this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
                    return response()->json($responce);
                }
            }

            $reschedule = Reschedule::where('new_booking_id', $newFligthBooking->booking_id)->get();
            if($reschedule->isEmpty()){
                $recall = $this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
                return response()->json([
                    'status'=>0,
                    'message'=>'Sorry, Passenger details are tempered. Please try after sometime',
                    'recall'=>$recall
                ], 422);
            }
            $prop_hdfc_key = $prop_hdfc_salt = '';
            $PropertyAccount = PropertyAccount::where(['service_type' => 'flight', 'service_id' => $flightMaster->id])->first();
            if (!empty($PropertyAccount)) {
                $prop_hdfc_key = $PropertyAccount->hdfc_key;
                $prop_hdfc_salt = $PropertyAccount->hdfc_salt;
                $Paytm_Mid = $PropertyAccount->hdfc_mid;
            }

            if(!empty($vendorData) && $vendorData->is_restricted_main_portal == 1){
                $responce['status'] = 0;
                $responce['message'] = 'This service can not be book.';
                $responce['recall'] = $this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
                return response()->json($responce);
            }

            $service_name = $flightMaster->flight_number.' ('.$flightMaster->operator_name.')';
            $service_city = '';
            $service_charge = '';

            if($request->request_from == 'mobile')
                $request_from = 'mobile';
            else
                $request_from = 'web';

            $OrderMaster = new OrderMaster([
                'order_id' => $request->order_id,
                'invoice_id' => $invoice_id,
                'vendor_id' => $vendor_id,
                'vendor_name' => $vendor_name,
                'order_type' => 'online',
                'customer_id' => $user->id,
                'customer_name' => $user->first_name .' '. $user->last_name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
                'customer_address1' => $user->address1,
                'customer_address2' => $user->address2,
                'gst_regd_no' => $gst_regd_no,
                'gst_company_name' => $gst_company_name,
                'gst_company_address' => $gst_company_address,
                'expected_arrival_time' => $arrival_time,
                'need_pickup' => $need_pickup,
                'service_type' => 'flight',
                'service_category' => $category,
                'service_name' => $service_name,
                'service_name_id' => $service_name_id,
                'travel_distance' => $distance,
                'travel_hour' => $hour,
                'halt_hour' => $halt_hour,
                'halt_charge' => $halt_charge,
                'service_quantity' => $service_quantity,
                'service_city' => $service_city,
                'pickup_address' => $pickup_address,
                'drop_location' => $drop_location,
                'travel_route' => $travel_route,
                'rental_breakdown' => $price_breakup,
                'travel_trip' => $travel_trip,
                'start_date' => $start_date,
                'end_date' => !empty($end_date) ? $end_date : null,
                'room_request' => json_encode($service_details),
                'room_details' => json_encode($room_details),
                'room_slug' => $room_slug,
                'request_for_room' => $request_for_room,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'total_room_category' => $total_room_category,
                'total_rooms' => $total_room,
                'total_guests' => $total_guest,
                'total_adults' => $total_adult,
                'total_child' => $total_child,
                'extra_guests' => $extra_guests,
                'extra_person_price' => $extra_guest_price,
                'total_service_price' => $total_service_price,
                'adult_price' => $adult_price,
                'child_price' => $child_price,
                'sub_total_price' => $sub_total_price,
                'coupon_name' => $coupon_name,
                'coupon_code' => $coupon_code,
                'coupon_amount' => $discount_amount,
                'tax_percentage' => $taxPercent,
                'tax_amount' => $taxPrice,
                'price_type' => 'reschedule',
                'service_charge' => $service_charge,
                'guide_charge' => $guide_charge,
                'days_for_guide' => $days_for_guide,
                'total_order_price' => $request->total_price,
                'status' =>  'pending',
                'payment_gateway' => $request->payment_gateway,
                'paytm_mid' => $Paytm_Mid,
                'hdfc_key' => $prop_hdfc_key,
                'hdfc_salt' => $prop_hdfc_salt,
                'split_initiate_status' => $split_status,
                'request_from' => $request_from,
                'payment_status' => 'pending',
                'book_ip' => $Ip,
                'gate_number' => $gate_no,
                'foreign_visitor' => isset($request->isForeignCitizen) && ($request->isForeignCitizen == 1) ? $request->isForeignCitizen : 0,
            ]);

            if ($OrderMaster->save()) {
                require_once public_path('QrCode/generateQrCode.php');
                require_once public_path('pushNotification.php');
                require_once public_path('paytm_lib/config_paytm.php');

                $OrderMasterId = $OrderMaster->id;
                $payment_gateway_redirect = 1;

                $start_date = $departure_date;
                $end_date = $departure_date;

                $OrderDetailsData[0]  = [
                    'order_id' => $request->order_id,
                    'order_master_id' => $OrderMasterId,
                    'service_type' => $request->service_type,
                    'service_name' => $service_name,
                    'service_name_id' => $newFligthBooking->id,
                    'service_city' => $request->service_city,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'service_item_quantity' => 1,
                    'tax_percentage' => 0,
                    'tax_amount' => 0,
                    'total_room_price' => $request->total_price,
                    'unit_total_price'=>$request->total_price,
                    'status' => 'pending'
                ];
                $booking_id = $request->order_id;
                foreach ($reschedule as $res) {
                    $updated = PassengerDetail::where('id', $res->passenger_id)
                        ->update([
                            'schedule_id' => $scheduleDetails->id,
                            'booking_id'  => $booking_id,
                        ]);
                    if ($updated) {
                        $res->update([
                            'payment_id' => $OrderMasterId,
                        ]);
                    }
                }

                //$MasterInventoryOld = SeatInventory::where(['schedule_id' => $old_schedule_id,'date' => $old_journey_date])->first();
                $statusRelease = $this->releaseSeatBySp($old_schedule_id, $old_journey_date, $adult_count);
                /* if (!empty($MasterInventoryOld)) {
                    $MasterInventoryOld->online_booked -= $adult_count;
                    $MasterInventoryOld->save();
                } */
                $status = $statusRelease['status'];
                $message = $statusRelease['message'];
                if($status != 'SUCCESS'){
                    return response()->json([
                        'status' => 0,
                        'message' => $message,
                        'statusCode'=>$status
                    ], 422);
                }
                if (!empty($MasterInventory)) {
                    $status = null;
                    $message = null;

                    DB::statement(
                        "CALL sp_book_flight_seats(?, ?, ?, @status, @message)",
                        [
                            $scheduleDetails->id,
                            $departure_date,
                            $adult_count
                        ]
                    );
                    $result = DB::selectOne(
                        "SELECT @status AS status, @message AS message"
                    );
                    $status = $result->status;
                    $message = $result->message;
                    if($status != 'SUCCESS'){
                        return response()->json([
                            'status' => 0,
                            'message' => $message,
                            'statusCode'=>$status
                        ], 422);
                    }
                    // $MasterInventory->online_booked += $adult_count;
                    // $MasterInventory->save();
                }

                OrderDetail::insert($OrderDetailsData);

                $payment_info = array();

                // HDFC Payment
                    $payment_info = array();
                    if ($request->payment_gateway == 'hdfc') {

                        $MobSuccessUrl = 'https://www.payumoney.com/mobileapp/payumoney/success.php';
                        $MobFailureUrl = 'https://www.payumoney.com/mobileapp/payumoney/failure.php';

                        if (!empty($prop_hdfc_key) && !empty($prop_hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                            $HDFC_KEY = $prop_hdfc_key;
                            $HDFC_SALT = $prop_hdfc_salt;
                            $MERCHANT_ID = $Paytm_Mid;
                        }
                        $payment_info['txn_id'] = "TXN". time() . rand(10000, 99999999);
                        $payment_info['key'] = $HDFC_KEY;
                        $payment_info['amount'] = $request->total_price;
                        $payment_info['product_info'] = parent::cleanString($service_name);
                        $payment_info['first_name'] = parent::cleanString($user->first_name);
                        $payment_info['last_name'] = parent::cleanString($user->last_name);
                        $payment_info['email'] = $user->email;
                        $payment_info['phone'] = $user->phone;
                        $payment_info['address1'] = parent::cleanString($user->address1);
                        $payment_info['address2'] = parent::cleanString($user->address2);
                        $payment_info['city'] = '';
                        $payment_info['state'] = '';
                        $payment_info['zipcode'] = '';
                        $payment_info['country'] = '';
                        $payment_info['udf1'] = $invoice_id;
                        $payment_info['udf2'] = $vendor_id;
                        $payment_info['udf3'] = $vendor_name;
                        $payment_info['udf4'] = 'flight';
                        $payment_info['surl'] = 'payment_success';
                        $payment_info['furl'] = 'payment_failure';
                        $payment_info['curl'] = 'payment_failure';
                        $payment_info['payUurl'] = PAYU_URL;
                        $payment_info['hash'] = hash('sha512', $HDFC_KEY .'|'. $payment_info['txn_id'] .'|'. $payment_info['amount'] .'|'. $payment_info['product_info'] .'|'. $payment_info['first_name'] .'|'. $payment_info['email'] .'|'. $payment_info['udf1'] .'|'. $payment_info['udf2'] .'|'. $payment_info['udf3'] .'|'. $payment_info['udf4'] .'|||||||'. $HDFC_SALT);
                        $payment_info['ENVIRONMENT'] = PAYTM_ENVIRONMENT;
                        $payment_info['merchantId'] = $MERCHANT_ID;
                        $payment_info['successUrl'] = $MobSuccessUrl;
                        $payment_info['failureUrl'] = $MobFailureUrl;

                        $PaymentHistory = new PaymentHistory([
                            'order_id' => $request->order_id,
                            'vendor_id' => $vendor_id,
                            'user_id' => $user->id,
                            'payment_method' => 'hdfc',
                            'transaction_id' => $payment_info['txn_id'],
                            'amount' => $request->total_price,
                            'product_info' => parent::cleanString($service_name),
                            'first_name' => $payment_info['first_name'],
                            'last_name' => $payment_info['last_name'],
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'address1' => $payment_info['address1'],
                            'address2' => $payment_info['address2'],
                            'city' => '',
                            'state' => '',
                            'country' => '',
                            'zipcode' => '',
                            'udf1' => $invoice_id,
                            'udf2' => $vendor_id,
                            'udf3' => $vendor_name,
                            'udf4' => 'flight',
                            'status' => 'pending'
                        ]);
                        $PaymentHistory->save();
                        $payment_id = $PaymentHistory->id;

                        OrderMaster::find($OrderMasterId)->update(['payment_id' => $payment_id, 'transaction_id' => $payment_info['txn_id'], 'hdfc_key' => $HDFC_KEY, 'hdfc_salt' => $HDFC_SALT, 'paytm_mid' => $MERCHANT_ID]);
                    }
                    // Paytm Payment
                    elseif ($request->payment_gateway == 'paytm') {
                        require_once public_path('paytm_lib/config_paytm.php');
                        require_once public_path('paytm_lib/encdec_paytm.php');

                        $paramList = array();
                        $ORDER_ID = "TXN". time() . rand(10000, 99999999);
                        $CUST_ID = $userId;
                        $INDUSTRY_TYPE_ID = INDUSTRY_TYPE_ID;
                        $CHANNEL_ID = CHANNEL_ID;
                        $TXN_AMOUNT = $request->total_price;
                        // Create an array having all required parameters for creating checksum.
                        if ($request->request_from == 'mobile') {
                            $McallbackUrl = "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=$ORDER_ID";
                            if (PAYTM_ENVIRONMENT == 'PROD')
                                $McallbackUrl = "https://securegw.paytm.in/theia/paytmCallback?ORDER_ID=$ORDER_ID";
                            $paramList["body"] = array(
                                "requestType" => "Payment",
                                "mid" => PAYTM_MERCHANT_MID,
                                "websiteName" => PAYTM_MERCHANT_WEBSITE,
                                "orderId" => $ORDER_ID,
                                "callbackUrl" => $McallbackUrl,
                                "txnAmount" => array(
                                    "value" => $TXN_AMOUNT,
                                    "currency" => "INR",
                                ),
                                "userInfo" => array(
                                    "custId" => $CUST_ID,
                                ),
                            );

                            if (!empty($OrderMaster->paytm_mid)) {
                                $splitSettlementInfo['splitMethod'] = 'AMOUNT';
                                $splitSettlementInfo['splitInfo'][] = array('mid' => $OrderMaster->paytm_mid, 'amount' => array('value' => $OrderMaster->vendor_amount, 'currency' => 'INR'));
                                $paramList["body"]["splitSettlementInfo"] = $splitSettlementInfo;
                            }

                            /*
                                * Generate checksum by parameters we have in body
                                * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys
                                */
                            $checksum = generateSign($paramList["body"], PAYTM_MERCHANT_KEY);

                            $paramList["head"] = array(
                                "signature" => $checksum
                            );
                            $post_data = json_encode($paramList, JSON_UNESCAPED_SLASHES);

                            /* for Staging */
                            $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=" . PAYTM_MERCHANT_MID . "&orderId=$ORDER_ID";
                            if (PAYTM_ENVIRONMENT == 'PROD') {
                                $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=" . PAYTM_MERCHANT_MID . "&orderId=$ORDER_ID";
                            }

                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                            $response = curl_exec($ch);
                            $response = json_decode($response, 1);
                            if (isset($response['body']['txnToken'])) {
                                $paramList["txnToken"] = $response['body']['txnToken'];
                                $paramList["response"] = $response;
                                $paramList["mobile_callbackurl"] = $McallbackUrl;
                            }else {
                                $responce['status'] = 0;
                                $paramList["response"] = $response;
                                $responce['message'] = 'Unable to place order. Please try again after some time.';
                                return response()->json($responce);
                            }
                        } else {
                            $paramList["MID"] = PAYTM_MERCHANT_MID;
                            $paramList["ORDER_ID"] = $ORDER_ID;
                            $paramList["CUST_ID"] = $CUST_ID;
                            $paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
                            $paramList["CHANNEL_ID"] = $CHANNEL_ID;
                            $paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
                            $paramList["WEBSITE"] = PAYTM_MERCHANT_WEBSITE;
                            $paramList["CALLBACK_URL"] = $this->site . "api/auth/paytm_success";
                            $paramList["MSISDN"] = $request->phone;
                            $paramList["EMAIL"] = $request->email;
                            $paramList["VERIFIED_BY"] = "EMAIL";
                            $paramList["IS_USER_VERIFIED"] = "YES";

                            if (!empty($OrderMaster->paytm_mid)) {
                                $splitSettlementInfo['splitMethod'] = 'AMOUNT';
                                $splitSettlementInfo['splitInfo'][] = array('mid' => $OrderMaster->paytm_mid, 'amount' => $OrderMaster->vendor_amount);
                                $paramList["splitSettlementInfo"] = json_encode($splitSettlementInfo);
                            }

                            $checkSum = getChecksumFromArray($paramList, PAYTM_MERCHANT_KEY);
                            $paramList["CHECKSUMHASH"] = $checkSum;
                            $paramList["PAYTM_TXN_URL"] = $PAYTM_TXN_URL;
                        }
                        if (!empty($OrderMaster->paytm_mid)) {
                            $paramList["is_split"] = 1;
                            $paramList["paytm_mid"] = $OrderMaster->paytm_mid;
                        } else {
                            $paramList["is_split"] = 0;
                            $paramList["paytm_mid"] = '';
                        }
                        $paramList['ENVIRONMENT'] = PAYTM_ENVIRONMENT;

                        $payment_info = $paramList;

                        $PaymentHistory = new PaymentHistory([
                            'order_id' => $request->order_id,
                            'vendor_id' => $vendor_id,
                            'user_id' => $userId,
                            'payment_method' => 'paytm',
                            'transaction_id' => $ORDER_ID,
                            'amount' => $request->total_price,
                            'product_info' => $service_name,
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'email' => $request->email,
                            'phone' => $request->phone,
                            'address1' => $request->address1,
                            'address2' => $request->address2,
                            'city' => $request->city,
                            'state' => $request->state,
                            'country' => $request->country,
                            'zipcode' => $request->zipcode,
                            'udf1' => $invoice_id,
                            'udf2' => $vendor_id,
                            'udf3' => $vendor_name,
                            'udf4' => $request->service_type,
                            'status' => 'pending'
                        ]);
                    $PaymentHistory->save();
                    $payment_id = $PaymentHistory->id;

                    OrderMaster::find($OrderMasterId)->update(['payment_id' => $payment_id]);
                }
                DB::commit();
                $responce['status'] = 1;
                $responce['data'] = $payment_info;
                $responce['payment_gateway_redirect'] = $payment_gateway_redirect;
                if ($payment_gateway_redirect == 0) {
                    $param = base64_encode($payment_info['txn_id']);
                    $param = str_replace("=", "%3D", $param);
                    $responce['param'] = $param;
                    $responce['redirect'] = $this->frontendUrl .'success/'. $param;
                    OrderMaster::find($OrderMasterId)->update(['transaction_id' => 'N/A']);
                }
                return response()->json($responce);
            }else {
                DB::rollBack();
                $responce['status'] = 0;
                $responce['message'] = 'Unable to place order';
                $responce['recall'] = $this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
                return response()->json($responce);
            }
        }catch(Exception $e){
            DB::rollBack();
            $responce['status'] = 0;
            $responce['message'] = $e->getMessage();
            $responce['recall'] =$this->revertRescheduleData($newFligthBooking, $oldFlightBooking);
            return response()->json($responce);
        }
    }

    private function revertRescheduleData($newBookingDetails, $oldBookingDetails){
        /* Reschedule::where('old_booking_id', $oldBookingDetails->booking_id)->where('new_booking_id', $newBookingDetails->booking_id)->delete();
        FlightBooking::where('id', $oldBookingDetails->id)->update([
            'is_reschedule'=>null,
            'reschedule_id'=>null
        ]);
        FlightBooking::where('id', $newBookingDetails->id)->delete(); */
        try{
            DB::statement('CALL failed_reschedule(?, ?)', [
                $oldBookingDetails->booking_id,
                $newBookingDetails->booking_id
            ]);
            return 'RECALLED SUCCESSFULLY';
        }catch(\Exception $e){
            return $e->getMessage();
        }


    }


    public function passengerFlightRescheduleList(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $flightBooking = FlightBooking::where('booking_id', $request->booking_id)->with('scheduleOnward','scheduleReturn')->first();

        $passengers = [];
        $orderStatus = [
            '0'=>'PENDING',
            '1'=>'CONFIRMED',
            '2'=>'CANCELLED'
        ];

        if(!empty($flightBooking->onward_schedule_id)){
            $passengerDetails = PassengerDetail::where('schedule_id', $flightBooking->onward_schedule_id)->where('booking_id', $request->booking_id)->get();
            foreach($passengerDetails as $passengerDet){
                $passengers[] = [
                    'passenger_id'=>$passengerDet->id,
                    'passeneger_name'=>$passengerDet->first_name,
                    'passenger_type'=>$passengerDet->passenger_type,
                    'order_status' =>$orderStatus[$passengerDet->order_status],
                    'booking_id'=>$passengerDet->booking_id,
                    'journey_date'=>date('d-m-Y',strtotime($flightBooking->onward_flight_date)).' '.date('h:i A', strtotime($flightBooking->scheduleOnward->departure_time)),
                    'reschedule_status'=>'N'
                ];
            }
        }
        if(!empty($flightBooking->return_schedule_id)){
            $passengerDetails = PassengerDetail::where('schedule_id', $flightBooking->return_schedule_id)->where('booking_id', $request->booking_id)->get();
            foreach($passengerDetails as $passengerDet){
                $passengers[] = [
                    'passenger_id'=>$passengerDet->id,
                    'passeneger_name'=>$passengerDet->first_name,
                    'passenger_type'=>$passengerDet->passenger_type,
                    'order_status' =>$orderStatus[$passengerDet->order_status],
                    'booking_id'=>$passengerDet->booking_id,
                    'journey_date'=>date('d-m-Y',strtotime($flightBooking->return_flight_date)).' '.date('h:i A', strtotime($flightBooking->scheduleReturn->departure_time)),
                    'reschedule_status'=>'N'
                ];
            }
        }
        if($flightBooking->is_reschedule == 1){
            $newflightBooking = FlightBooking::where('id', $flightBooking->reschedule_id)->with('scheduleOnward','scheduleReturn')->first();
            if(!empty($newflightBooking->return_flight_date) && $newflightBooking->return_flight_date != $flightBooking->return_flight_date){
                $passengerDetails = PassengerDetail::where('schedule_id', $newflightBooking->return_schedule_id)->where('booking_id', $newflightBooking->booking_id)->get();

                foreach($passengerDetails as $passengerDet){
                    $passengers[] = [
                        'passenger_id'=>$passengerDet->id,
                        'passeneger_name'=>$passengerDet->first_name,
                        'passenger_type'=>$passengerDet->passenger_type,
                        'order_status' =>$orderStatus[$passengerDet->order_status],
                        'booking_id'=>$passengerDet->booking_id,
                        'journey_date'=>date('d-m-Y',strtotime($newflightBooking->return_flight_date)).' '.date('h:i A', strtotime($newflightBooking->scheduleReturn->departure_time)),
                        'reschedule_status'=>'Y'
                    ];
                }
            }

            if(!empty($newflightBooking->onward_flight_date) && $newflightBooking->onward_flight_date != $flightBooking->onward_flight_date){
                $passengerDetails = PassengerDetail::where('schedule_id', $newflightBooking->onward_schedule_id)->where('booking_id', $newflightBooking->booking_id)->get();

                foreach($passengerDetails as $passengerDet){
                    $passengers[] = [
                        'passenger_id'=>$passengerDet->id,
                        'passeneger_name'=>$passengerDet->first_name,
                        'passenger_type'=>$passengerDet->passenger_type,
                        'order_status' =>$orderStatus[$passengerDet->order_status],
                        'booking_id'=>$passengerDet->booking_id,
                        'journey_date'=>date('d-m-Y',strtotime($newflightBooking->onward_flight_date)).' '.date('h:i A', strtotime($newflightBooking->scheduleOnward->departure_time)),
                        'reschedule_status'=>'Y'
                    ];
                }
            }

            if(!empty($newflightBooking->return_schedule_id) && $newflightBooking->return_schedule_id != $flightBooking->return_schedule_id){
                $passengerDetails = PassengerDetail::where('schedule_id', $newflightBooking->return_schedule_id)->where('booking_id', $newflightBooking->booking_id)->get();

                foreach($passengerDetails as $passengerDet){
                    $passengers[] = [
                        'passenger_id'=>$passengerDet->id,
                        'passeneger_name'=>$passengerDet->first_name,
                        'passenger_type'=>$passengerDet->passenger_type,
                        'order_status' =>$orderStatus[$passengerDet->order_status],
                        'booking_id'=>$passengerDet->booking_id,
                        'journey_date'=>date('d-m-Y',strtotime($newflightBooking->return_flight_date)).' '.date('h:i A', strtotime($newflightBooking->scheduleReturn->departure_time)),
                        'reschedule_status'=>'Y'
                    ];
                }
            }

            if(!empty($newflightBooking->onward_schedule_id) && $newflightBooking->onward_schedule_id != $flightBooking->onward_schedule_id){
                $passengerDetails = PassengerDetail::where('schedule_id', $newflightBooking->onward_schedule_id)->where('booking_id', $newflightBooking->booking_id)->get();

                foreach($passengerDetails as $passengerDet){
                    $passengers[] = [
                        'passenger_id'=>$passengerDet->id,
                        'passeneger_name'=>$passengerDet->first_name,
                        'passenger_type'=>$passengerDet->passenger_type,
                        'order_status' =>$orderStatus[$passengerDet->order_status],
                        'booking_id'=>$passengerDet->booking_id,
                        'journey_date'=>date('d-m-Y',strtotime($newflightBooking->onward_flight_date)).' '.date('h:i A', strtotime($newflightBooking->scheduleOnward->departure_time)),
                        'reschedule_status'=>'Y'
                    ];
                }
            }
        }
        return response()->json([
            'status'=>1,
            'message'=>'successufy fetched',
            'data'=>$passengers
        ]);
    }

    public function getReschedulePassengerList(Request $request){
        $validator = Validator::make($request->all(), [
            'source_airport_id' => 'required',
            'destination_airport_id' => 'required|different:source_airport_id',
            'departure_date' => 'required|date_format:Y-m-d',
            'return_date' => 'nullable|date_format:Y-m-d|after_or_equal:departure_date',
            'trip_type' => 'required|in:oneway,roundtrip',
            'passengers.*'=>'required',
            'journey_type'=>'nullable|in:DEPARTURE,RETURN'
        ],[
            'destination_airport_id.different'=>'Source and destination cannot be same',
            'passengers.*'=>'Please select the passenger to be rescheduled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $tripType = $request->input('trip_type');

        if($tripType == 'roundtrip' && (!$request->has('return_date') || $request->return_date == '')) {
            return response()->json([
                'status' => 0,
                'message' => 'Return date is required for round trip.'
            ], 422);
        }

        if($tripType == 'oneway' && $request->has('return_date') && $request->return_date!='') {
            return response()->json([
                'status' => 0,
                'message' => 'Return date should not be provided for one way trip.'
            ], 422);
        }

        $flights = FlightSchedule::with('flight')->whereHas('flight', function ($query) {
                $query->where('is_active', 1);
            })->where('source_airport_id', $request->input('source_airport_id'))
            ->where('destination_airport_id', $request->input('destination_airport_id'));


        $flights = $flights->where('status', 'OPEN')->get();

        $departure_date = date('Y-m-d', strtotime($request->input('departure_date')));

        $user = $request->user();

        if(empty($user)){
            return response()->json([
                'status'=>0,
                'message'=>'Unauthorised access'
            ],401);
        }

        $passengers = gettype($request->passengers) == 'array' ? $request->passengers : json_decode($request->passengers, 1);

        $passengersStr = (string)implode(",", $passengers);

        if(empty($passengers)){
            return response()->json([
                'status'=>0,
                'message'=>'Please select the passenger to be rescheduled'
            ],422);
        }
        $journey_type = '';
        if(!$request->has('journey_type')){
            $journey_type = 'DEPARTURE';
        }else{
            $journey_type = $request->journey_type;
        }
        $source_airport_id = $request->input('source_airport_id');
        $destination_airport_id = $request->input('destination_airport_id');
        $return_date = null;
        if($tripType == 'roundtrip' && $journey_type == 'RETURN'){
            $return_date = $request->return_date;
        }
        $data = $this->rescheduleSP($passengersStr, $tripType, $source_airport_id, $destination_airport_id, $departure_date, $return_date, $journey_type);
        return response()->json($data);

    }

    public function getAssitance(Request $request){
        $assitances = DB::table('assistance_types')->where('is_active', 1)->get();
        $availableAssistance = [];
        if($assitances->isNotEmpty()){
            foreach($assitances as $assitance){
                $availableAssistance[] = [
                    'id'=>$assitance->id,
                    'assistance_name'=>$assitance->assistance_name,
                    'assistance_code'=>$assitance->assistance_code,
                ];
            }
        }
        return response()->json([
            'status'=>1,
            'message'=>'Fetched sucessfully',
            'data'=>$availableAssistance
        ]);
    }

    public function downloadTicketCopy(Request $request){
        $validator = Validator::make($request->all(), [
            'booking_id'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $flightBookingDetails = FlightBooking::where('booking_id', $request->booking_id)->with('scheduleOnward','scheduleReturn')->first();

        if(empty($flightBookingDetails)){
            return response()->json([
                'status'=>0,
                'message'=>'No booking found'
            ],404);
        }

        $data = [];
        $SubjConfirm = 'Booking Confirmation';


        $OrderMaster = DB::table("order_masters")->where('order_id', $flightBookingDetails->booking_id)->first();
        $file_name = 'tickets/'. $OrderMaster->invoice_id .'_ticket.pdf';
        /*if(file_exists(public_path($file_name))){
            return response()->download(public_path($file_name));
        }*/

        $reschedule = Reschedule::where('new_booking_id', $flightBookingDetails->booking_id)->first();

        if($reschedule){
            $passengersIds = Reschedule::where('old_booking_id', $reschedule->old_booking_id)->pluck('passenger_id')->toArray();
            $passenegersRemains = DB::table('passengers')->whereIn('booking_id', [$reschedule->old_booking_id])->pluck('id')->toArray();
            $passengersIds = array_merge($passengersIds, $passenegersRemains);
            if(count($passengersIds) > 0){
                $passenegers = DB::table('passengers')->whereIn('id', $passengersIds)->get();
            }
        }else{
            $passenegers = DB::table('passengers')->where('booking_id', $flightBookingDetails->booking_id)->get();
        }


        $flightDetails = DB::table('flight_master')->where('id', $flightBookingDetails->scheduleOnward->flight_id)->first();
        $departureSchedule = $flightBookingDetails->scheduleOnward;
        $data['flightBooking'] = $flightBookingDetails;
        $data['orderMaster'] = $OrderMaster;
        $data['passenegers'] = $passenegers;
        $data['departureFlight'] = $flightDetails;

        $data['departureSchedule'] = $flightBookingDetails->scheduleOnward;
        $data['departureSourceFrom'] = DB::table('airport_master')->where('id',$departureSchedule->source_airport_id)->first();
        $data['departureSourceTo'] = DB::table('airport_master')->where('id',$departureSchedule->destination_airport_id)->first();
        if($flightBookingDetails->booking_type == 'roundtrip'){
            $returnFlight = $flightBookingDetails->scheduleReturn;
            $flightReturnDetails = FlightMaster::where('id', $returnFlight->flight_id)->first();
            $returnSourceFrom = DB::table('airport_master')->where('id',$returnFlight->source_airport_id)->first();
            $returnSourceTo = DB::table('airport_master')->where('id',$returnFlight->destination_airport_id)->first();
            $return_flight = $returnSourceFrom->city_name.' --- '.$returnSourceTo->city_name;
            $data['returnFlight'] = $flightReturnDetails;
            $data['returnSchedule'] = $returnFlight;
            $data['returnSourceFrom'] = $returnSourceFrom;
            $data['returnSourceTo'] = $returnSourceTo;
        }
        $To = $OrderMaster->customer_email;

        Mail::to($To)->send(new \App\Mail\FlightBookingConfirmationMail($data, $SubjConfirm));

        return response()->download(public_path($file_name));
    }
}
