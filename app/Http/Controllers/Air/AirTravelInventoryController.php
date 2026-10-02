<?php

namespace App\Http\Controllers\Air;

use App\Http\Controllers\Controller;
use App\AirModels\AirportMaster;
use App\AirModels\FlightBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\AirModels\FlightMaster;
use App\AirModels\PassengerDetail;
use App\AirModels\Reschedule;
use App\AirModels\SeatInventory;
Use App\OrderMaster;
use App\Traits\AirTravelTraits;
use Carbon\Carbon;

class AirTravelInventoryController extends Controller
{

    use AirTravelTraits;

    public $site = '';
    public $frontendUrl = '';
    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    // API to fetch Flight Booking Details
    public function flightOrderDetails(Request $request){
        $response = [
            'status' => 0,
            'message' => 'Network error, please try again.'
        ];
        if ($request->request_type == 'get_user_orders') {
            $user = $request->user();
            if (!$user) {
                $response['message'] = 'Unauthorized user.';
                return response()->json($response, 401);
            }

            $response['message'] = '';

            $take = !empty($request->pageSize) ? (int) $request->pageSize : 10;
            $pageIndex = isset($request->pageIndex) ? (int) $request->pageIndex : 0;

            $skip = $pageIndex * $take;

            $previousPageIndex = ($pageIndex > 0) ? $pageIndex - 1 : 0;
            $order_column = 'id';
            $order_dir = 'DESC';

            $OrderMasterQuery = OrderMaster::select(
                'service_type',
                'vendor_name',
                'service_name',
                'created_at',
                'start_date',
                'order_id',
                'invoice_id',
                'order_type',
                'status',
                'total_order_price',
                'payment_gateway',
                'payment_status',
                'transaction_id',
                'id',
                'end_date',
                'service_name_id',
                'room_request',
                'payment_method',
                'price_type'
            )
            ->where('customer_id', $user->id)
            ->where('service_type', 'flight');

            if ($request->status == 'all' || empty($request->status)) {
                $OrderMasterQuery->where('status','!=','partially-cancelled');

            } elseif ($request->status == 'cancelled') {
                $OrderMasterQuery->where('status','cancelled');

            } elseif ($request->status == 'upcoming') {
                $OrderMasterQuery
                    ->where('start_date', '>', date('Y-m-d'))
                    ->where('status', 'completed');

                $order_column = 'start_date';
                $order_dir = 'ASC';
            } else {
                $OrderMasterQuery->where('status',$request->status);
            }

            if (!empty($request->active) && !empty($request->direction)) {
                $order_column = $request->active;
                $order_dir = strtoupper($request->direction) == 'ASC' ? 'ASC' : 'DESC';
            }


            $totalData = $OrderMasterQuery->count();

            $OrderMaster = $OrderMasterQuery
                ->orderBy($order_column, $order_dir)
                ->skip($skip)
                ->take($take)
                ->get();
            $testLastes = [];
            if ($OrderMaster->count() > 0) {
                foreach ($OrderMaster as $key => $value) {
                    $OrderMaster[$key]->paynow = 0;
                    $OrderMaster[$key]->cancel = 0;

                    $Flight = null;
                    $originBooking = $this->getOriginBookingSP($OrderMaster[$key]->order_id);
                    if (!empty($value->service_name_id)) {
                        $Flight = FlightMaster::find($value->service_name_id);
                    }

                    if ($Flight) {
                        $OrderMaster[$key]->slug = !empty($Flight->slug) ? $Flight->slug . '-' . $Flight->id : '';

                        $OrderMaster[$key]->icon_name =!empty($Flight->feature_image) ? $this->site . $Flight->feature_image : '';
                    } else {
                        $OrderMaster[$key]->slug = '';
                        $OrderMaster[$key]->icon_name = '';
                    }
                    $OrderMaster[$key]->transaction_id = !empty($value->transaction_id) ? $value->transaction_id : 'N/A';

                    $OrderMaster[$key]->end_date = !empty($value->end_date) ? date('M d Y',strtotime($value->end_date)) : '';


                    $OrderMaster[$key]->service_type = strtoupper($value->service_type);

                    $OrderMaster[$key]->payment_method = !empty($value->payment_method) ? $value->payment_method : 'N/A';

                    $OrderMaster[$key]->is_cancel_btn = true;
                    if($originBooking->latest_booking_id == $OrderMaster[$key]->order_id){
                        $OrderMaster[$key]->is_cancel_btn = false;
                    }

                    $PCOrders = OrderMaster::where('id','!=',$value->id)
                    ->where('invoice_id',$value->invoice_id)
                    ->count();

                    $FlightBooking = FlightBooking::where('booking_id', $OrderMaster[$key]->order_id)->with('scheduleOnward','scheduleReturn')->first();

                    $bookingArr = [];
                    if($OrderMaster[$key]->price_type == 'reschedule'){
                        $mainBooking = FlightBooking::where('id', $originBooking->parent_id)->first();
                        array_push($bookingArr, $mainBooking->booking_id);
                        $totalFlightBooking = FlightBooking::where('reschedule_id', $mainBooking->id)->pluck('booking_id')->toArray();
                        $bookingArr = array_merge($bookingArr, $totalFlightBooking);
                    }

                    if ($value->status != 'cancelled' && $value->status != 'partially-cancelled' && $value->status != 'pending' && !empty($value->start_date) && date('Y-m-d', strtotime($value->start_date)) >= date('Y-m-d') &&$PCOrders == 0) {
                        $OrderMaster[$key]->cancel = 1;
                    }



                    $OrderMaster[$key]->adult_count = !empty($FlightBooking->adult_count) ? $FlightBooking->adult_count : 0;
                    $OrderMaster[$key]->infant_count = !empty($FlightBooking->infant_count) ? $FlightBooking->infant_count : 0;
                    $OrderMaster[$key]->start_date = !empty($FlightBooking->booked_at) ? date('M d Y h:i A', strtotime($FlightBooking->booked_at)) : '';
                    $OrderMaster[$key]->trip_type = !empty($FlightBooking->booking_type) ? $FlightBooking->booking_type : '';
                    $rescheduledData = Reschedule::where('new_booking_id', $FlightBooking->booking_id)->where('status','!=','CANCEL')->first();
                    $OrderMaster[$key]->is_rescheduled = false;
                    if(!empty($rescheduledData))
                    {
                        if($OrderMaster[$key]->status == 'completed'){
                            $OrderMaster[$key]->is_rescheduled = true;
                        }
                    }else{
                        $oldRescheduledData = Reschedule::where('old_booking_id', $FlightBooking->booking_id)->where('status','!=','CANCEL')->first();
                        if(!empty($oldRescheduledData) && $FlightBooking->booking_type == 'oneway'){
                            if($OrderMaster[$key]->status == 'completed'){
                                $OrderMaster[$key]->is_rescheduled = true;
                            }
                        }

                        if(!empty($oldRescheduledData) && $FlightBooking->booking_type == 'roundtrip'){

                            $result = DB::table('reschedules as res')
                            ->join('passengers as p', 'p.id', '=', 'res.passenger_id')
                            ->selectRaw("
                                MAX(CASE WHEN p.journey_type = 'DEPARTURE' THEN 1 ELSE 0 END) AS departure,
                                MAX(CASE WHEN p.journey_type = 'RETURN' THEN 1 ELSE 0 END) AS return_journey
                            ")
                            ->where('res.status', '!=', 'CANCEL')
                            ->where('res.old_booking_id', $FlightBooking->booking_id)
                            ->first();
                            $count = $result ? $result->departure + $result->return_journey : 0;
                            if($OrderMaster[$key]->status == 'completed' && $count == 2){
                                $OrderMaster[$key]->is_rescheduled = true;
                            }
                        }
                    }

                    $OrderMaster[$key]->status = strtoupper($value->status);

                    if(!empty($bookingArr)){
                        $passengerExists = PassengerDetail::whereIn('booking_id', $bookingArr)->where('order_status', '!=', 2)->exists();
                        if(!$passengerExists){
                            $OrderMaster[$key]->is_cancel_btn = true;
                        }
                    }

                    if(!empty($FlightBooking->scheduleOnward)){
                        $souce = $this->getSourceDetails($FlightBooking->scheduleOnward->source_airport_id);
                        $destination = $this->getSourceDetails($FlightBooking->scheduleOnward->destination_airport_id);
                        $departure_passenger = PassengerDetail::where('booking_id', $FlightBooking->booking_id)->where('order_status','!=', 2)->where('journey_type','DEPARTURE')->pluck('id')->toArray();
                        $OrderMaster[$key]->departure_details = [
                            'source_airport_id'=> $souce->id,
                            'source_city_code'=> $souce->airport_code,
                            'destination_airport_id'=> $destination->id,
                            'destination_city_code'=> $destination->airport_code,
                            'source_city_name'=> $souce->city_name,
                            'destination_city_name'=> $destination->city_name,
                            'departure_adult_count'=> $FlightBooking->departure_adult_count,
                            'departure_infant_count'=> $FlightBooking->departure_infant_count,
                            'depature_time'=>date('d-m-Y',strtotime($FlightBooking->onward_flight_date)).' '.date('h:i A', strtotime($FlightBooking->scheduleOnward->departure_time)),
                            'arrival_time'=>date('d-m-Y',strtotime($FlightBooking->onward_flight_date)).' '.date('h:i A', strtotime($FlightBooking->scheduleOnward->arrival_time)),
                            'passengers'=>$departure_passenger
                        ];
                    }


                    if($FlightBooking->booking_type == 'roundtrip' && !empty($FlightBooking->scheduleReturn)){
                        $souce = $this->getSourceDetails($FlightBooking->scheduleReturn->source_airport_id);
                        $destination = $this->getSourceDetails($FlightBooking->scheduleReturn->destination_airport_id);
                        $return_passenger = PassengerDetail::where('booking_id', $FlightBooking->booking_id)->where('order_status','!=', 2)->where('journey_type','RETURN')->pluck('id')->toArray();
                        $OrderMaster[$key]->return_details = [
                            'source_airport_id'=> $souce->id,
                            'source_city_code'=> $souce->airport_code,
                            'source_city_name'=> $souce->city_name,
                            'destination_city_code'=> $destination->airport_code,
                            'destination_airport_id'=> $destination->id,
                            'destination_city_name'=> $destination->city_name,
                            'return_adult_count'=> $FlightBooking->return_adult_count,
                            'return_infant_count'=> $FlightBooking->return_infant_count,
                            'depature_time'=>date('d-m-Y',strtotime($FlightBooking->return_flight_date)).' '.date('h:i A', strtotime($FlightBooking->scheduleReturn->departure_time)),
                            'arrival_time'=>date('d-m-Y',strtotime($FlightBooking->return_flight_date)).' '.date('h:i A', strtotime($FlightBooking->scheduleReturn->arrival_time)),
                            'passengers'=>$return_passenger
                        ];
                    }
                }

                $response['pageIndex'] = $skip;
                $response['pageSize'] = $take;
                $response['length'] = $totalData;
                $response['totalPage'] = $take > 0 ? ceil($totalData / $take) : 0;
                $response['previousPageIndex'] = $previousPageIndex;
                $response['status'] = 1;
                $response['data'] = $OrderMaster;

            } else {
                $response['status'] = 0;
                $response['message'] = 'No flight orders found.';
            }
        }

        return response()->json($response);
    }

    /**
     * Inventory Management Page
     */
    public function inventoryManage(){
        $SelectedCity = AirportMaster::select('id','city_name','airport_code')
            ->where('is_active', 1)
            ->get();
        return view('aero-travels.inventory-manage',compact('SelectedCity'));
    }

    /**
     * Search Flights
     */
    public function searchFlights(Request $request){
        $validated = $request->validate([
            'from_airport_id' => 'required|integer',
            'to_airport_id'   => 'required|integer|different:from_airport_id',
            'date'            => 'required|date',
        ]);
        $date = Carbon::parse($validated['date']);
        $departureDay = $date->dayOfWeekIso;

        $flights = DB::table('flight_schedule as fs')
            ->join('flight_master as fm','fm.id','=','fs.flight_id')
            ->leftJoin('seat_inventory as si', function ($join) use ($validated) {
                $join->on('si.schedule_id','=','fs.id');
                $join->where('si.date','=',$validated['date']);
            })

            ->where('fs.source_airport_id',$validated['from_airport_id'])
            ->where('is_active','1')
            ->where('fs.destination_airport_id',$validated['to_airport_id'])
            ->where('fs.departure_day',$departureDay)
            ->where('fs.status','OPEN')
            ->select([
                'fs.id as schedule_id',
                'fs.flight_id',
                'fs.source_airport_id',
                'fs.destination_airport_id',
                'fs.departure_day',
                'fs.departure_time',
                'fs.arrival_time',
                'fs.total_capacity',
                'fs.online_capacity',
                'fs.offline_capacity',

                /*
                | Flight Master
                */
                'fm.flight_number',
                'fm.airline_code',
                'fm.operator_name',
                'fm.aircraft_type',

                /*
                | Seat Inventory
                */
                'si.id as inventory_id',
                'si.date as inventory_date',
                'si.online_capacity as inventory_online_capacity',
                'si.offline_capacity as inventory_offline_capacity',
                'si.online_booked',
                'si.offline_booked',
                'si.status as inventory_status',
            ])
            ->orderBy('fs.departure_time','asc')
            ->get();

        $flights = $flights->map(function ($flight) {
            $onlineCapacity = (int) ($flight->inventory_online_capacity ?? $flight->online_capacity ?? 0);

            $offlineCapacity = (int) ($flight->inventory_offline_capacity ?? $flight->offline_capacity ?? 0);

            $onlineBooked = (int) ($flight->online_booked ?? 0);

            $offlineBooked = $offlineCapacity;
            $onlineAvailable = max(0, $onlineCapacity - $onlineBooked);
            $offlineAvailable = 0;
            $totalAvailable = $onlineAvailable;
            $totalCapacity = $onlineCapacity + $offlineCapacity;
            $totalBooked = $onlineBooked + $offlineBooked;

            return [
                'schedule_id' =>$flight->schedule_id,
                'flight_id' => $flight->flight_id,
                'flight_number' => $flight->flight_number,
                'airline_code' => $flight->airline_code,
                'operator_name' => $flight->operator_name,
                'aircraft_type' => $flight->aircraft_type,
                'departure_time' => $flight->departure_time,
                'arrival_time' => $flight->arrival_time,
                'total_capacity' => $totalCapacity,
                'online_capacity' => $onlineCapacity,
                'offline_capacity' => $offlineCapacity,
                'online_booked' => $onlineBooked,
                'offline_booked' =>$offlineBooked,
                'online_available' => $onlineAvailable,
                'offline_available' => $offlineAvailable,
                'total_available' => $totalAvailable,
                'inventory_id' => $flight->inventory_id,
                'inventory_date' =>$flight->inventory_date,
                'inventory_status' => $flight->inventory_status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' =>'Flights fetched successfully.',
            'data' =>$flights,
        ]);
    }

    /**
     * Get Seat Inventory
     */
    public function getSeatInventory(Request $request){
        $request->validate([
            'schedule_id' => 'required|integer',
            'date'        => 'required|date',
        ]);

        $inventory = DB::table('seat_inventory')
            ->where('schedule_id', $request->schedule_id)
            ->whereDate('date', $request->date)
            ->first();

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Seat inventory not found for the selected flight and date.',
                'data' => null,
            ], 404);
        }

        $onlineCapacity  = (int) ($inventory->online_capacity ?? 0);
        $offlineCapacity = (int) ($inventory->offline_capacity ?? 0);
        $onlineBooked  = (int) ($inventory->online_booked ?? 0);
        $offlineBooked = (int) ($inventory->offline_booked ?? 0);
        $onlineAvailable = max(0,$onlineCapacity - $onlineBooked);

        $offlineAvailable = max(0,$offlineCapacity - $offlineBooked);

        $totalCapacity = $onlineCapacity + $offlineCapacity;
        $totalBooked = $onlineBooked + $offlineBooked;
        $totalAvailable = $onlineAvailable + $offlineAvailable;

        return response()->json([
            'success' => true,
            'message' => 'Seat inventory fetched successfully.',

            'data' => [
                'id' => $inventory->id,
                'schedule_id' => $inventory->schedule_id,
                'date' => $inventory->date,

                'online_capacity' => $onlineCapacity,
                'offline_capacity' => $offlineCapacity,

                'online_booked' => $onlineBooked,
                'offline_booked' => $offlineBooked,

                'online_available' => $onlineAvailable,
                'offline_available' => $offlineAvailable,

                'total_capacity' => $totalCapacity,
                'total_booked' => $totalBooked,
                'total_available' => $totalAvailable,

                'status' => $inventory->status,
            ],
        ]);
    }
    /**
     * Download Passenger List
     */
    public function downloadPassengers(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|integer',
            'date'        => 'required|date',
        ]);

        $scheduleId = (int) $validated['schedule_id'];
        $date       = $validated['date'];

        $flight = DB::table('flight_schedule as fs')
            ->join('flight_master as fm', 'fm.id', '=', 'fs.flight_id')
            ->leftJoin('airport_master as from_airport','from_airport.id','=','fs.source_airport_id')
            ->leftJoin('airport_master as to_airport','to_airport.id','=','fs.destination_airport_id')
            ->where('fs.id', $scheduleId)
            ->select([
                'fs.id as schedule_id',
                'fs.flight_id',
                'fm.flight_number',
                'from_airport.city_name as from_city',
                'from_airport.airport_code as from_code',
                'to_airport.city_name as to_city',
                'to_airport.airport_code as to_code',
            ])
            ->first();

        if (!$flight) {
            return response()->json([
                'success' => false,
                'message' => 'Flight schedule not found.',
            ], 404);
        }

        $flightBookings = DB::table('flight_bookings as fb')
            ->where('fb.booking_status', 'CONFIRM')
            ->where(function ($query) use ($scheduleId, $date) {
                $query->where(function ($q) use ($scheduleId, $date) {
                    $q->where('fb.onward_schedule_id', $scheduleId)
                        ->whereDate('fb.onward_flight_date', $date);
                })
                ->orWhere(function ($q) use ($scheduleId, $date) {
                    $q->where('fb.return_schedule_id', $scheduleId)
                        ->whereDate('fb.return_flight_date', $date);
                });
            })
            ->select([
                'fb.id',
                'fb.booking_id',
                'fb.booking_status',
                'fb.booking_type',
                'fb.onward_schedule_id',
                'fb.onward_flight_date',
                'fb.return_schedule_id',
                'fb.return_flight_date',
                'fb.adult_count',
                'fb.infant_count',
                'fb.total_passengers',
                'fb.departure_adult_count',
                'fb.departure_infant_count',
                'fb.return_adult_count',
                'fb.return_infant_count',
            ])
            ->distinct()
            ->orderBy('fb.id', 'asc')
            ->get();

        if ($flightBookings->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No confirmed booking found for this flight and journey date.',
            ], 404);
        }

        $isDeparture = $flightBookings->contains(function ($booking) use ($scheduleId,$date) {
            return
                (int) $booking->onward_schedule_id === $scheduleId && !empty($booking->onward_flight_date) && date('Y-m-d', strtotime($booking->onward_flight_date)) === $date;
        });

        $isReturn = $flightBookings->contains(function ($booking) use ($scheduleId,$date) {
            return
                (int) $booking->return_schedule_id === $scheduleId && !empty($booking->return_flight_date) &&  date('Y-m-d', strtotime($booking->return_flight_date)) === $date;
        });

        if ($isDeparture && !$isReturn) {
            $journeyType = 'departure';
        } elseif ($isReturn && !$isDeparture) {
            $journeyType = 'return';
        } elseif ($isDeparture && $isReturn) {
            return response()->json([
                'success' => false,
                'message' => 'The selected schedule and date matched both departure and return journeys.',
            ], 422);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unable to determine passenger journey type.',
            ], 422);
        }

        $bookingIds = $flightBookings
            ->pluck('booking_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $orders = DB::table('order_masters')
            ->whereIn('order_id', $bookingIds)
            ->whereRaw('LOWER(service_type) = ?', ['flight'])
            ->whereRaw('LOWER(payment_status) = ?', ['success'])
            ->select(['order_id','transaction_id'])
            ->get()
            ->unique('order_id')
            ->keyBy('order_id');

        $validBookingIds = collect($bookingIds)
            ->filter(function ($bookingId) use ($orders) {
                return $orders->has($bookingId);
            })
            ->values()
            ->toArray();

        if (empty($validBookingIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No successfully paid flight orders found.',
            ], 404);
        }

        $passengers = DB::table('passengers')
            ->whereIn('booking_id', $validBookingIds)
            ->where('schedule_id', $scheduleId)
            ->where('order_status',1)
            ->whereRaw('LOWER(TRIM(journey_type)) = ?', [strtolower($journeyType)])
            ->select([
                'id',
                'booking_id',
                'schedule_id',
                'first_name',
                'last_name',
                'passenger_type',
                'journey_type',
                'order_status',
                'is_active',
            ])
            ->orderBy('booking_id', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->unique('id')
            ->values();

        $safeFlightNumber = preg_replace('/[^A-Za-z0-9_-]/','_',$flight->flight_number ?? 'flight');
        $filename ='passenger-list-' .$safeFlightNumber .'-' .$date .'.csv';
        $callback = function () use ($passengers,$orders,$flight,$date) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Sl.No',
                'Flight Number',
                'Journey Date',
                'From',
                'To',
                'Booking ID',
                'Booking Status',
                'Payment Status',
                'Passenger Type',
                'Passenger Name',
                'Transaction ID',
            ]);

            $from = trim(
                ($flight->from_city ?? '') .' (' .($flight->from_code ?? '') .')'
            );

            $to = trim(
                ($flight->to_city ?? '') .' (' .($flight->to_code ?? '') .')'
            );

            $serial = 1;

            foreach ($passengers as $passenger) {
                $passengerName = trim(($passenger->first_name ?? '') .' ' .($passenger->last_name ?? ''));
                $order = $orders->get($passenger->booking_id);
                fputcsv($file, [
                    $serial++,
                    $flight->flight_number ?? '',
                    $date,
                    $from,
                    $to,
                    $passenger->booking_id ?? '',
                    'CONFIRM',
                    'SUCCESS',
                    $passenger->passenger_type ?? '',
                    $passengerName,
                    $order->transaction_id ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback,200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' =>'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }


    public function inventoryManageList(){
        $SelectedCity = AirportMaster::select('id','city_name','airport_code')
            ->where('is_active', 1)
            ->get();
        return view('aero-travels.inventory-manage-list',compact('SelectedCity'));
    }

     /**
     * Get Confirm Passenger Listi
     */

    public function getSeatPassengerList(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|integer',
            'date'        => 'required|date',
        ]);

        $scheduleId = (int) $validated['schedule_id'];
        $date       = $validated['date'];

        $passengerDetails = DB::select(
            'CALL sp_get_flight_passenger(?, ?)',
            [
                $scheduleId,
                $date
            ]
        );

        return response()->json([
            'status' => true,
            'data'   => $passengerDetails,
        ]);
    }

    /**
     * Download Confirm Passenger List
     */
    public function downloadPassengersList(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|integer',
            'date'        => 'required|date',
        ]);

        $scheduleId = (int) $validated['schedule_id'];
        $date       = $validated['date'];

        $passengers = collect(
            DB::select('CALL sp_get_flight_passenger(?, ?)',[$scheduleId,$date])
        );

        if ($passengers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No passenger records found for this flight schedule.',
            ], 404);
        }

        $flightNumber = $passengers->first()->{'Flight Number'} ?? 'flight';
        $safeFlightNumber = preg_replace('/[^A-Za-z0-9_-]/','_', $flightNumber);
        $filename = 'passenger-list-' . $safeFlightNumber . '-' .$date .'.csv';

        $callback = function () use ($passengers) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Sl.No',
                'Flight Number',
                'Journey Date',
                'From',
                'To',
                'Booking Status',
                'Payment Status',
                'Passenger Type',
                'Passenger Name',
                'Gender',
                'Assistance',
                'Transaction ID',
            ]);

            $serial = 1;

            foreach ($passengers as $passenger) {

                fputcsv($file, [
                    $serial++,
                    $passenger->{'Flight Number'} ?? '',
                    $passenger->{'Journey Date'} ?? '',
                    $passenger->{'From'} ?? '',
                    $passenger->{'To'} ?? '',
                    $passenger->{'Booking Status'} ?? '',
                    $passenger->{'Payment Status'} ?? '',
                    $passenger->{'Passenger Type'} ?? '',
                    $passenger->{'Passenger Name'} ?? '',
                    $passenger->{'Gender'} ?? '',
                    $passenger->{'Assistance'} ?? '',
                    $passenger->{'Transaction ID'} ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback,200,
            [
                'Content-Type' =>'text/csv; charset=UTF-8',
                'Content-Disposition' =>'attachment; filename="' . $filename . '"',
                'Cache-Control' =>'no-cache, no-store, must-revalidate',
                'Pragma' =>'no-cache',
                'Expires' =>'0',
            ]
        );
    }



    /**
     * Seat Update view page
     */

    public function seatUpdate(Request $request){
        $SelectedCity = AirportMaster::select('id','city_name','airport_code')
            ->where('is_active', 1)
            ->get();
        return view('aero-travels.seat-update',compact('SelectedCity'));
    }

    /**
     * Update the Seat Capacity
     */

    public function updateSeatCapacity(Request $request){
        $request->validate([
            'schedule_id' => 'required|integer',
            'date' => 'required|date',
            'online_capacity' => 'required|integer|min:0',
            'offline_capacity' => 'required|integer|min:0',
        ]);

        // Find the seat inventory record
        $seatInventory = SeatInventory::where('schedule_id', $request->schedule_id)
            ->whereDate('date', $request->date)
            ->first();

        // Inventory not found
        if (!$seatInventory) {
            return response()->json([
                'success' => false,
                'message' => 'Seat inventory not found.',
                'errors' => [
                    'seat_inventory' => [
                        'Seat inventory not found for the selected flight and date.'
                    ]
                ]
            ], 404);
        }


        $onlineBooked = (int) ($seatInventory->online_booked ?? 0);
        $offlineBooked = (int) ($seatInventory->offline_booked ?? 0);

        $onlineCapacity = (int) $request->online_capacity;
        $offlineCapacity = (int) $request->offline_capacity;

        if ($onlineCapacity < $onlineBooked) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'online_capacity' => [
                        'Online capacity cannot be less than ' .
                        $onlineBooked .
                        ' already booked online seats.'
                    ]
                ]
            ], 422);
        }

        if ($offlineCapacity < $offlineBooked) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'offline_capacity' => [
                        'Offline capacity cannot be less than ' .
                        $offlineBooked .
                        ' already booked offline seats.'
                    ]
                ]
            ], 422);
        }

        if (($onlineCapacity + $offlineCapacity) > 9) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'online_capacity' => [
                        'Online + Offline capacity cannot exceed 9.'
                    ]
                ]
            ], 422);
        }

        $seatInventory->update([
            'online_capacity' => $onlineCapacity,
            'offline_capacity' => $offlineCapacity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Seat inventory updated successfully.',
            'data' => [
                'online_capacity' => $seatInventory->online_capacity,
                'offline_capacity' => $seatInventory->offline_capacity,
                'online_booked' => $onlineBooked,
                'offline_booked' => $offlineBooked,
            ]
        ]);
    }

}
