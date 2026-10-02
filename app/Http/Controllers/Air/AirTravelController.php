<?php

namespace App\Http\Controllers\Air;

use App\Http\Controllers\Controller;
use App\Traits\AirTravelTraits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\SmsTemplate;
use App\PaymentHistory;
use App\PropertyAccount;
use App\CustomerRefund;
use App\AirModels\AirportMaster;
use App\AirModels\PassengerDetail;
use App\AirModels\FlightMaster;
use App\AirModels\FlightBooking;
use App\AirModels\FareMaster;
use App\AirModels\FlightSchedule;
use App\AirModels\Reschedule;
use App\AirModels\Cancellation;
use Illuminate\Support\Facades\Mail;
use App\OrderMaster;
use App\OrderDetail;
use App\City;
Use App\User;
use PDF;



class AirTravelController extends Controller
{
    use AirTravelTraits;

    // Fetch and display all active flights.
    public function allFlight(){
        $FlightList = FlightMaster::where('is_active', 1)
            ->orderBy('id', 'DESC')
            ->get();

        return view(
            'aero-travels.all-flight',
            compact('FlightList')
        );
    }

    // Move a flight to draft status.
    public function flightDraft(Request $request){
        $flight = FlightMaster::find($request->id);

        if (empty($flight)) {
            return response()->json([
                'status' => 0,
                'message' => 'Flight not found.'
            ]);
        }

        $flight->is_active = 0;
        $flight->updated_by = Auth::id();
        $flight->save();

        return response()->json([
            'status' => 1,
            'message' => 'Flight moved to draft successfully.'
        ]);
    }


    // Show the add new flight form with active vendors.
    public function addNewFlight(){
        $Vendors = User::where('role', '2')->where('status', 1)->pluck('company', 'id');
        return view( 'aero-travels.add-new-flight', compact('Vendors') );
    }

    // Validate and create a new flight record within a database transaction.
    public function addNewFlightRequest(Request $request)
    {
        try {
            $validated = $request->validate([
                'flight_number' => 'required|string|max:100|unique:flight_master,flight_number',
                'name' => 'required|string|max:255',
                'content_data' => 'required',
                'vendor_id' => 'required',
                'terms_conditions' => 'required',
                'contact_email' => 'required|email',
                'contact_number' => 'required',
            ],
            [
                'flight_number.unique' => 'This flight number already exists. Please enter a different flight number.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        try {
            $flightId = DB::transaction(function () use ($request) {
                $userId = Auth::id();

                $faq = $request->input('faqs', []);

                $flight = new FlightMaster();

                $flight->flight_number = $request->flight_number;
                $flight->airline_code = $request->airline_code;
                $flight->operator_name = $request->name;
                $flight->vendor_id = $request->vendor_id;
                $flight->aircraft_type = null;
                $flight->content = $request->content_data;
                $flight->terms_conditions = $request->terms_conditions;
                $flight->faq = !empty($faq) ? json_encode($faq) : null;
                $flight->contact_email = $request->contact_email;
                $flight->contact_number = $request->contact_number;
                $flight->additional_email = $request->additional_email ?: null;
                $flight->additional_contact = $request->additional_phone ?: null;
                $flight->is_active = $request->status === 'publish' ? 1 : 0;
                $flight->created_by = $userId;
                $flight->updated_by = $userId;
                $flight->save();

                return $flight->id;
            });

            return redirect()
                ->route('all-flight')
                ->with('success', 'Flight added successfully.');

        } catch (\Throwable $e) {
            if (app()->environment('local', 'development')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error','Flight creation failed: '.$e->getMessage().' | File: '
                        .$e->getFile().' | Line: '
                        .$e->getLine()
                    );
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error','Unable to add flight. Please check the application logs.');
        }
    }

    // Load the flight details, vendors, and FAQ data for editing.
    public function editNewFlight($id){
        $Flight = FlightMaster::findOrFail($id);

        $Vendors = User::where('role', 2)
            ->orderBy('company', 'ASC')
            ->pluck('company', 'id');

        $SelectedVendorId = $Flight->vendor_id;

        $FaqList = $Flight->faq;

        if (is_string($FaqList)) {
            $decodedFaq = json_decode($FaqList, true);
            $FaqList = is_array($decodedFaq) ? $decodedFaq : [];
        }

        if (!is_array($FaqList)) {
            $FaqList = [];
        }

        $FaqList = array_values($FaqList);

        return view(
            'aero-travels.edit-new-flight',
            compact(
                'Flight',
                'Vendors',
                'SelectedVendorId',
                'FaqList'
            )
        );
    }

    // Validate and update the flight details within a database transaction.
    public function editNewFlightRequest(Request $request, $id)
    {
        $flight = FlightMaster::findOrFail($id);
        try {
            $validated = $request->validate([
                'flight_number' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('flight_master', 'flight_number')
                        ->ignore($flight->id),
                ],
                'name' => 'required|string|max:255',
                'content_data' => 'required',
                'terms_conditions' => 'required',
                'contact_email' => 'required|email',
                'contact_number' => 'required',
                'vendor_id' => 'required|exists:users,id',
                'status' => 'required|in:publish,draft',
            ], [
                'flight_number.unique' => 'This flight number already exists.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        try {
            DB::transaction(function () use ($request, $flight) {

                $userId = Auth::id();

                $faq = $request->input('faqs', []);

                $flight->flight_number = $request->flight_number;
                $flight->airline_code = $request->airline_code;
                $flight->operator_name = $request->name;
                $flight->content = $request->content_data;
                $flight->terms_conditions = $request->terms_conditions;
                $flight->faq = $faq;
                $flight->contact_email = $request->contact_email;
                $flight->contact_number = $request->contact_number;
                $flight->additional_email = $request->additional_email;
                $flight->additional_contact = $request->additional_phone;
                $flight->vendor_id = $request->vendor_id;
                $flight->is_active = $request->status === 'publish' ? 1 : 0;
                $flight->updated_by = $userId;

                $flight->save();
            });

            return redirect()
                ->route('all-flight')
                ->with('success', 'Flight updated successfully.');

        } catch (\Throwable $e) {

            if (app()->environment('local', 'development')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        'Flight update failed: '
                        . $e->getMessage()
                        . ' | File: '
                        . $e->getFile()
                        . ' | Line: '
                        . $e->getLine()
                    );
            }

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Unable to update flight. Please check the application logs.'
                );
        }
    }

    // Load flight details, active airports, days, and schedules for managing flight timings.
    public function flightSchedule($id){
        $flight = FlightMaster::findOrFail($id);

        $SelectedCity = AirportMaster::select('id','city_name','airport_code')
        ->where('is_active', 1)
        ->get();

        $Days = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ];

        $flightSchedules = FlightSchedule::with([
            'sourceAirport:id,city_name,airport_code',
            'destinationAirport:id,city_name,airport_code',
            'fares'
        ])
        ->where('flight_id', $id)
        ->orderBy('departure_day')
        ->orderBy('departure_time')
        ->get();

        return view(
            'aero-travels.add-flight-schedule',
            compact('SelectedCity','Days','id','flightSchedules','flight')
        );
    }

    // Validate and create flight schedules with passenger fares in a database transaction.
    public function addFlightSchedule(Request $request, $id){
        $flight = FlightMaster::findOrFail($id);
        $dayMap = [
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6,
            'Sunday'    => 7,
        ];

        $validated = $request->validate([
            'from_city'                     => 'required|integer|exists:airport_master,id',
            'to_city'                       => 'required|integer|different:from_city|exists:airport_master,id',

            'book_start_date'               => 'required|date_format:d-m-Y',
            'book_end_time'                 => 'required|date_format:H:i',

            'available_days'                => 'required|array|min:1',
            'available_days.*'              => 'required|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',

            'max_ticket_per_txn'            => 'required|integer|min:1',
            'max_ticket_per_user_per_day'   => 'required|integer|min:1',
            'days_from_start_date'          => 'required|integer|min:1',

            'departure_time'                => 'required|date_format:H:i',
            'arrival_time'                  => 'required|date_format:H:i',

            'online_ticket'                 => 'required|integer|min:0',
            'offline_ticket'                => 'required|integer|min:0',

            'adult_price'                   => 'required|numeric|min:1',
            'child_price'                   => 'required|numeric|min:0',

            'status'                        => 'required|in:OPEN,CLOSE',

        ], [
            'to_city.different' => 'Departure and destination cannot be the same.',
        ]);

        DB::beginTransaction();
        $slot_key = Str::random(10) . '-' . time();
        try {
            foreach ($validated['available_days'] as $day) {
                $departureDay = $dayMap[$day];

                $flightSchedule = FlightSchedule::create([
                    'flight_id' => $id,
                    'source_airport_id' => $validated['from_city'],
                    'destination_airport_id' => $validated['to_city'],
                    'departure_day' => $departureDay,
                    'departure_time' => $validated['departure_time'],
                    'arrival_time' => $validated['arrival_time'],
                    'booking_start_date' =>
                        Carbon::createFromFormat(
                            'd-m-Y',
                            $validated['book_start_date']
                        )->format('Y-m-d'),

                    'booking_end_time' => $validated['book_end_time'],
                    'days_from_current' => $validated['days_from_start_date'],
                    'per_transaction_ticket_limit' => $validated['max_ticket_per_txn'],
                    'per_day_ticket_limit' => $validated['max_ticket_per_user_per_day'],
                    'total_capacity' => $validated['online_ticket'] + $validated['offline_ticket'],
                    'online_capacity' => $validated['online_ticket'],
                    'offline_capacity' => $validated['offline_ticket'],
                    'slot_key' => $slot_key,
                    'status' => $validated['status'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $fares = [
                    'ADULT'  => $validated['adult_price'],
                    'INFANT' => $validated['child_price'],
                ];

                foreach ($fares as $passengerType => $baseFare) {
                    FareMaster::updateOrCreate(
                        [
                            'flight_id'      => $id,
                            'schedule_id'    => $flightSchedule->id,
                            'passenger_type' => $passengerType,
                        ],
                        [
                            'base_fare'  => $baseFare,
                            'is_active'  => 1,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]
                    );
                }
            }
            $this->generateInventory90days($id, $slot_key);


            DB::commit();

            return redirect()
                ->back()
                ->with('success','Flight schedule added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error','Something went wrong while saving the flight schedule.');
        }
    }

    // Load flight schedule details, airport options, timings, and fares for editing.
    public function editFlightSchedule($id)
    {
        $flightSchedule = FlightSchedule::with([
            'sourceAirport:id,city_name,airport_code',
            'destinationAirport:id,city_name,airport_code',
            'fares'
        ])->findOrFail($id);

        $flight = FlightMaster::findOrFail($flightSchedule->flight_id);

        $SelectedCity = AirportMaster::select('id','city_name','airport_code')
        ->where('is_active', 1)
        ->get();

        $Days = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ];
        $dayMap = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        $selectedDay = $dayMap[ (int) $flightSchedule->departure_day] ?? '';

        $bookingStartDate = '';
        if (!empty($flightSchedule->booking_start_date)) {
            $bookingStartDate = Carbon::parse(
                $flightSchedule->booking_start_date
            )->format('d-m-Y');
        }

        $bookingEndTime = '';
        if (!empty($flightSchedule->booking_end_time)) {
            $bookingEndTime = Carbon::parse(
                $flightSchedule->booking_end_time
            )->format('H:i');
        }

        $departureTime = '';
        if (!empty($flightSchedule->departure_time)) {
            $departureTime = Carbon::parse(
                $flightSchedule->departure_time
            )->format('H:i');
        }

        $arrivalTime = '';
        if (!empty($flightSchedule->arrival_time)) {
            $arrivalTime = Carbon::parse(
                $flightSchedule->arrival_time
            )->format('H:i');
        }

        $adultFare = $flightSchedule->fares
            ->where('passenger_type', 'ADULT')
            ->first();

        $infantFare = $flightSchedule->fares
            ->where('passenger_type', 'INFANT')
            ->first();

        return view('aero-travels.edit-flight-schedule', compact('flight','flightSchedule','SelectedCity','Days','selectedDay','adultFare','infantFare','bookingStartDate','bookingEndTime','departureTime','arrivalTime')
        );
    }

    // Validate and update the flight schedule and passenger fares in a database transaction.
    public function updateFlightSchedule(Request $request, $id)
    {
        $flightSchedule = FlightSchedule::findOrFail($id);
        $flightId = $flightSchedule->flight_id;

        $validated = $request->validate([
            'from_city' => ['required','integer','exists:airport_master,id',],
            'to_city' => ['required','integer','different:from_city','exists:airport_master,id',],
            'book_start_date' => ['required','date_format:d-m-Y',],
            'book_end_time' => ['required','date_format:H:i',],
            'available_days' => ['required','array','size:1',],
            'days_from_start_date' => ['required','integer','min:1',],
            'available_days.0' => ['required','string','in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',],
            'max_ticket_per_txn' => ['required','integer','min:1',],
            'max_ticket_per_user_per_day' => ['required','integer','min:1',],
            'departure_time' => ['required','date_format:H:i',],
            'arrival_time' => ['required','date_format:H:i',],
            'online_ticket' => ['required','integer','min:0',],
            'offline_ticket' => ['required','integer','min:0',],
            'adult_price' => ['required','numeric','min:1',],
            'child_price' => ['required','numeric','min:0',],
            'status' => ['required','in:OPEN,CLOSE',],
        ], [
            'to_city.different' =>'Departure and destination cannot be the same.',
        ]);

        $dayMap = [
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6,
            'Sunday'    => 7,
        ];
        $selectedDay = $validated['available_days'][0];
        $departureDay = $dayMap[$selectedDay];

        DB::beginTransaction();
        try {
            $flightSchedule->update([

                'source_airport_id' => $validated['from_city'],
                'destination_airport_id' => $validated['to_city'],
                'departure_day' => $departureDay,
                'days_from_current' => $validated['days_from_start_date'],
                'departure_time' => $validated['departure_time'],
                'arrival_time' => $validated['arrival_time'],
                'booking_start_date' => Carbon::createFromFormat('d-m-Y', $validated['book_start_date'])->format('Y-m-d'),
                'booking_end_time' => $validated['book_end_time'],
                'per_transaction_ticket_limit' => $validated['max_ticket_per_txn'],
                'per_day_ticket_limit' => $validated['max_ticket_per_user_per_day'],
                'total_capacity' => (int) $validated['online_ticket'] + (int) $validated['offline_ticket'],
                'online_capacity' => $validated['online_ticket'],
                'offline_capacity' => $validated['offline_ticket'],
                'status' => $validated['status'],
                'updated_by' => auth()->id(),
            ]);

            FareMaster::updateOrCreate(
                [
                    'flight_id'      => $flightId,
                    'schedule_id'    => $flightSchedule->id,
                    'passenger_type' => 'ADULT',
                ],
                [
                    'base_fare' => $validated['adult_price'],
                    'is_active' => 1,
                    'updated_by' => auth()->id(),
                ]
            );
            FareMaster::updateOrCreate(
                [
                    'flight_id'      => $flightId,
                    'schedule_id'    => $flightSchedule->id,
                    'passenger_type' => 'INFANT',
                ],
                [
                    'base_fare' => $validated['child_price'],
                    'is_active' => 1,
                    'updated_by' => auth()->id(),
                ]
            );

            if($validated['status'] === 'OPEN') {
                $this->generateInventory90days($flightId, $flightSchedule->slot_key);
            }

            DB::commit();

            return redirect()
                ->route('manage-flight', $flightId)
                ->with('success', 'Flight schedule updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withInput()
                ->with('error','Something went wrong while updating the flight schedule.');
        }
    }

    // Publish or move selected flight schedules to draft status.
    public function flightOperation(Request $request)
    {
        try {
            $requestType = $request->request_type;

            if (in_array($requestType, ['publish-flight', 'draft-flight'])) {

                $ids = json_decode($request->IdArray, true);

                if (!is_array($ids) || empty($ids)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Please select at least one flight.'
                    ]);
                }

                $status = $requestType === 'publish-flight' ? 'OPEN' : 'CLOSE';

                FlightSchedule::whereIn('id', $ids)
                    ->update(['status' => $status]);

                return response()->json([
                    'status'  => 1,
                    'message' => $requestType === 'publish-flight' ? 'Flight(s) published successfully.' : 'Flight(s) moved to draft successfully.'
                ]);
            }

            return response()->json([
                'status'  => 0,
                'message' => 'Invalid request type.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // Flight Orders
    public function flightOrders(Request $request){
        if (!(parent::checkViewPrivilege(28))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
       
        $flightList = FlightMaster::where('is_active', 1)->pluck('flight_number', 'id');
        return view('aero-travels.flight-orders', compact('flightList'));
    }

    // Get flight orders with filters, pagination, sorting, and action options.
    public function getFlightOrders(Request $request)
    {
        $aColumns = [
            'invoice_id',        
            null,                 
            null,                 
            'booked_at',          
            'customer_name',      
            'customer_phone',     
            'total_order_price',  
            'order_type',         
            'status',             
            'payment_method',     
            'payment_status',     
            'transaction_id',    
            'id',                 
        ];

        $sIndexColumn = 'om.id';

        $sFrom = '
            order_masters om
            INNER JOIN flight_bookings fb ON fb.booking_id = om.order_id
            LEFT JOIN flight_schedule fs1 ON fs1.id = fb.onward_schedule_id
            LEFT JOIN flight_master fm1 ON fm1.id = fs1.flight_id
            LEFT JOIN airport_master ap1s ON ap1s.id = fs1.source_airport_id
            LEFT JOIN airport_master ap1d ON ap1d.id = fs1.destination_airport_id
            LEFT JOIN flight_schedule fs2 ON fs2.id = fb.return_schedule_id
            LEFT JOIN flight_master fm2 ON fm2.id = fs2.flight_id
            LEFT JOIN airport_master ap2s ON ap2s.id = fs2.source_airport_id
            LEFT JOIN airport_master ap2d ON ap2d.id = fs2.destination_airport_id
        ';

        $sSelect = '
            om.id,
            om.invoice_id,
            om.customer_name,
            om.customer_phone,
            om.customer_email,
            om.total_order_price,
            om.order_type,
            om.status,
            om.payment_method,
            om.payment_status,
            om.payment_gateway,
            om.transaction_id,
            om.service_name_id,
            fb.booking_type,
            fb.adult_count,
            fb.infant_count,
            fb.total_passengers,
            fb.booked_at,
            fb.onward_flight_date,
            fs1.departure_time AS onward_departure_time,
            fb.return_flight_date,
            fm1.flight_number   AS onward_flight_number,
            ap1s.airport_code   AS onward_source_code,
            ap1d.airport_code   AS onward_destination_code,
            fm2.flight_number   AS return_flight_number,
            ap2s.airport_code   AS return_source_code,
            ap2d.airport_code   AS return_destination_code
        ';

        $sLimit = '';

        if ($request->filled('start') && $request->input('length') != '-1') {
            $sLimit = ' LIMIT '
                . intval($request->input('start'))
                . ', '
                . intval($request->input('length'));
        }

        $sOrder = ' ORDER BY fb.booked_at DESC ';

        if ($request->has('order')) {
            $orders = $request->input('order', []);
            $columns = $request->input('columns', []);

            $orderParts = [];

            foreach ($orders as $order) {
                $columnIndex = intval($order['column']);

                if (
                    isset($columns[$columnIndex]) &&
                    $columns[$columnIndex]['orderable'] === 'true' &&
                    isset($aColumns[$columnIndex]) &&
                    $aColumns[$columnIndex] !== null
                ) {
                    $direction = $order['dir'] === 'asc' ? 'ASC' : 'DESC';
                    $sortColumn = $aColumns[$columnIndex] === 'booked_at'
                        ? 'fb.booked_at'
                        : 'om.`' . $aColumns[$columnIndex] . '`';

                    $orderParts[] = $sortColumn . ' ' . $direction;
                }
            }

            if (!empty($orderParts)) {
                $sOrder = ' ORDER BY ' . implode(', ', $orderParts);
            }
        }

        $vendorCondition = '';

        if (Auth::user()->access_type == 'vendor') {
            $vendorId = Auth::user()->role == 2
                ? Auth::user()->id
                : Auth::user()->vendor_id;

            $vendorCondition = ' AND om.vendor_id = ' . intval($vendorId);
        }

        $sWhere = ' WHERE 1'
            . $vendorCondition
            . ' AND om.service_type = "flight"'
            . ' AND om.status != "partially-cancelled"';

        $bookingStatus = parent::cleanString($request->input('searchValue1', 'all'));

        if ($bookingStatus === 'cancelled') {
            $sWhere .= ' AND (
                (
                    om.status = "cancelled"
                    OR om.status = "partially-cancelled"
                )
                AND om.payment_status = "success"
            )';
        } elseif ($bookingStatus !== '' && $bookingStatus !== 'all') {
            $sWhere .= ' AND om.status = "' . $bookingStatus . '"';
        }

        // Matches the options in the "customColumn" dropdown in the view.
        $searchColumns = [
            'invoice_id',
            'transaction_id',
            'created_at',
            'flight_number',
        ];

        $customColumn = $request->input('searchValue3');
        $customValue = $request->input('searchValue4');

        if (
            !empty($customColumn) &&
            !empty($customValue) &&
            in_array($customColumn, $searchColumns)
        ) {
            if ($customColumn === 'created_at') {
                $dates = explode(' - ', $customValue);

                if (count($dates) === 2) {
                    $startDate = date('Y-m-d', strtotime($dates[0]));
                    $endDate = date('Y-m-d', strtotime($dates[1]));

                    $sWhere .= ' AND fb.booked_at BETWEEN "'
                        . $startDate . ' 00:00:00" AND "'
                        . $endDate . ' 23:59:59"';
                }
            } elseif ($customColumn === 'flight_number') {
                $cleanValue = parent::cleanString($customValue);
                $sWhere .= ' AND (fm1.flight_number LIKE "%' . $cleanValue . '%" OR fm2.flight_number LIKE "%' . $cleanValue . '%")';
            } else {
                $cleanValue = parent::cleanString($customValue);
                $sWhere .= ' AND om.`' . $customColumn . '` LIKE "%' . $cleanValue . '%"';
            }
        }

        if ($request->filled('searchValue5')) {

            $flightId = intval($request->input('searchValue5'));
            $sWhere .= ' AND (fm1.id = ' . $flightId . ' OR fm2.id = ' . $flightId . ')';
        }

        if ($request->filled('searchValue9')) {
            $orderType = parent::cleanString($request->input('searchValue9'));
            $sWhere .= ' AND om.order_type = "' . $orderType . '"';
        }

        if ($request->filled('searchValue10')) {
            $paymentMethod = parent::cleanString($request->input('searchValue10'));
            $sWhere .= ' AND om.payment_gateway = "' . $paymentMethod . '"';
        }

        $globalSearch = $request->input('search.value');

        if (!empty($globalSearch)) {
            $globalSearch = parent::cleanString($globalSearch);

            $searchParts = [
                'om.invoice_id LIKE "%' . $globalSearch . '%"',
                'om.customer_name LIKE "%' . $globalSearch . '%"',
                'om.customer_phone LIKE "%' . $globalSearch . '%"',
                'om.transaction_id LIKE "%' . $globalSearch . '%"',
                'fm1.flight_number LIKE "%' . $globalSearch . '%"',
                'fm2.flight_number LIKE "%' . $globalSearch . '%"',
            ];

            $sWhere .= ' AND (' . implode(' OR ', $searchParts) . ')';
        }

        $exportQuery = "SELECT {$sSelect} FROM {$sFrom} {$sWhere} {$sOrder}";

        $printQuery = $dataQuery =
            "SELECT SQL_CALC_FOUND_ROWS {$sSelect}
            FROM {$sFrom}
            {$sWhere}
            {$sOrder}
            {$sLimit}";

        $results = DB::select($dataQuery);

        $filteredResult = DB::select('SELECT FOUND_ROWS() AS totalrow');
        $filteredTotal = $filteredResult[0]->totalrow ?? 0;

        $totalResult = DB::select(
            "SELECT COUNT({$sIndexColumn}) AS countindex
            FROM {$sFrom}
            {$sWhere}"
        );

        $total = $totalResult[0]->countindex ?? 0;

        $output = [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredTotal,
            'data' => []
        ];

        foreach ($results as $order) {
           
            $isRoundTrip = $order->booking_type === 'roundtrip';
            $cancelOption = '';
            if ($order->status === 'completed' && $order->payment_status === 'success' && !empty($order->onward_flight_date) && !empty($order->onward_departure_time)) {
                try {
                    $departureDateTime = \Carbon\Carbon::parse(
                        $order->onward_flight_date . ' ' . $order->onward_departure_time
                    );

                    $now = \Carbon\Carbon::now();

                    $cancellationCutoff = $departureDateTime->copy()->subHours(2);

                    if ($now->lt($cancellationCutoff)) {
                        $cancelOption = '
                            <li>
                                <a href="javascript:void(0);"
                                class="cancel_booking"
                                data-status="' . $order->status . '"
                                data-id="' . $order->id . '"
                                data-amount="' . $order->total_order_price . '">
                                    Cancel Booking (Refund full)
                                </a>
                            </li>';
                    }

                } catch (\Exception $e) {
                    $cancelOption = '';
                }
            }

            $orderType = $order->order_type;

            $flightNumbers = [];

            if (!empty($order->onward_flight_number)) {
                $flightNumbers[] = $order->onward_flight_number;
            }

            if ($isRoundTrip && !empty($order->return_flight_number)) {
                $flightNumbers[] = $order->return_flight_number;
            }

            $flightNumberCell = !empty($flightNumbers) ? implode('<hr style="margin: 3px 0;">', $flightNumbers) : 'N/A';


            $routes = [];

            if (!empty($order->onward_source_code) && !empty($order->onward_destination_code)) {
                $routes[] = $order->onward_source_code . ' - ' . $order->onward_destination_code;
            }

            if ($isRoundTrip && !empty($order->return_source_code) && !empty($order->return_destination_code)) {
                $routes[] = $order->return_source_code . ' - ' . $order->return_destination_code;
            }

            $routeCell = !empty($routes) ? implode('<hr style="margin: 3px 0;">', $routes)  : 'N/A';

            $transactionId = !empty($order->transaction_id) ? $order->transaction_id : 'Null';

            // Order Details / User Details - shown for every status/tab.
            $orderDetailsOption = '
                <li>
                    <a href="javascript:void(0);"
                    class="order_details"
                    data-id="' . $order->id . '"
                    data-toggle="modal"
                    data-target="#orderDetailsModal">
                        Order Details
                    </a>
                </li>';

            $userDetailsOption = '
                <li>
                    <a href="javascript:void(0);"
                    class="user_details"
                    data-id="' . $order->id . '"
                    data-toggle="modal"
                    data-target="#userDetailsModal">
                        User Details
                    </a>
                </li>';

            $action = '
                <div class="btn-group">
                    <button type="button"
                            class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light"
                            data-toggle="dropdown"
                            aria-expanded="false">
                        Action <span class="caret"></span>
                    </button>
                    <ul role="menu" class="dropdown-menu dropdown-menu-right">
                        ' . $orderDetailsOption . '
                        ' . $userDetailsOption . '
                        ' . $cancelOption . '
                    </ul>
                </div>';

            $row = [];
            $row[] = $order->invoice_id;
            $row[] = $flightNumberCell;
            $row[] = $routeCell;
            $row[] = date('M d Y H:i:s', strtotime($order->booked_at));
            $row[] = $order->customer_name;
            $row[] = $order->customer_phone;
            $row[] = $order->total_order_price;
            $row[] = $orderType;
            $row[] = $order->status;
            $row[] = $order->payment_method;
            $row[] = $order->payment_status;
            $row[] = $transactionId;
            $row[] = $action;

            $output['data'][] = $row;
        }

        $output['exportQuery'] = $exportQuery;
        $output['printQuery'] = $printQuery;

        return response()->json($output);
    }

    // Handle flight order details, customer details, cancellation, export, and print operations.
    public function flightOprsn(Request $request)
    {
        if ($request->request_type == 'get_customer_details') {
            $order = DB::table('order_masters')->where('id', $request->orderId)->first();

            if (empty($order)) {
                return json_encode(['status' => 0, 'message' => 'Order not found.']);
            }

            $data = [
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'customer_address1' => $order->customer_address1,
                'customer_address2' => $order->customer_address2 ?? null,
                'customer_city' => $order->customer_city,
                'customer_state' => $order->customer_state,
                'customer_country' => $order->customer_country,
                'customer_zipcode' => $order->customer_zipcode,
                'customer_notes' => $order->customer_notes ?? null,
            ];

            return json_encode(['status' => 1, 'data' => $data]);
        } elseif ($request->request_type == 'get_flight_order_details') {
            $order = DB::selectOne('
                SELECT
                    om.id,
                    om.invoice_id,
                    om.customer_name,
                    om.customer_phone,
                    om.total_order_price,
                    om.order_type,
                    om.status,
                    om.payment_method,
                    om.payment_status,
                    om.transaction_id,
                    fb.booking_type,
                    fb.pnr_code,
                    fb.adult_count,
                    fb.infant_count,
                    fb.total_passengers,
                    fb.total_amount,
                    fb.booked_at,
                    fb.onward_flight_date,
                    fb.return_flight_date,
                    fm1.flight_number  AS onward_flight_number,
                    ap1s.airport_code  AS onward_source_code,
                    ap1d.airport_code  AS onward_destination_code,
                    fm2.flight_number  AS return_flight_number,
                    ap2s.airport_code  AS return_source_code,
                    ap2d.airport_code  AS return_destination_code
                FROM order_masters om
                INNER JOIN flight_bookings fb ON fb.booking_id = om.order_id
                LEFT JOIN flight_schedule fs1 ON fs1.id = fb.onward_schedule_id
                LEFT JOIN flight_master fm1 ON fm1.id = fs1.flight_id
                LEFT JOIN airport_master ap1s ON ap1s.id = fs1.source_airport_id
                LEFT JOIN airport_master ap1d ON ap1d.id = fs1.destination_airport_id
                LEFT JOIN flight_schedule fs2 ON fs2.id = fb.return_schedule_id
                LEFT JOIN flight_master fm2 ON fm2.id = fs2.flight_id
                LEFT JOIN airport_master ap2s ON ap2s.id = fs2.source_airport_id
                LEFT JOIN airport_master ap2d ON ap2d.id = fs2.destination_airport_id
                WHERE om.id = ?
            ', [$request->orderId]);

            if (empty($order)) {
                return json_encode(['status' => 0, 'message' => 'Order not found.']);
            }

            $routeOnward = trim(($order->onward_source_code ?? '') . ' - ' . ($order->onward_destination_code ?? ''), ' -');
            $routeReturn = trim(($order->return_source_code ?? '') . ' - ' . ($order->return_destination_code ?? ''), ' -');

            $html = '<table class="table table-bordered">';
            $html .= '<tr><th>Booking Id</th><td>' . e($order->invoice_id) . '</td></tr>';
            $html .= '<tr><th>PNR</th><td>' . e($order->pnr_code) . '</td></tr>';
            $html .= '<tr><th>Booking Type</th><td>' . e(ucfirst($order->booking_type)) . '</td></tr>';
            $html .= '<tr><th>Onward Flight</th><td>' . e($order->onward_flight_number) . ' | ' . e($routeOnward) . ' | ' . e($order->onward_flight_date) . '</td></tr>';

            if ($order->booking_type === 'roundtrip') {
                $html .= '<tr><th>Return Flight</th><td>' . e($order->return_flight_number) . ' | ' . e($routeReturn) . ' | ' . e($order->return_flight_date) . '</td></tr>';
            }

            $html .= '<tr><th>Passengers</th><td>Adults: ' . e($order->adult_count) . ', Infants: ' . e($order->infant_count) . ', Total: ' . e($order->total_passengers) . '</td></tr>';
            $html .= '<tr><th>Customer</th><td>' . e($order->customer_name) . ' (' . e($order->customer_phone) . ')</td></tr>';
            $html .= '<tr><th>Order Type</th><td>' . e($order->order_type) . '</td></tr>';
            $html .= '<tr><th>Status</th><td>' . e($order->status) . '</td></tr>';
            $html .= '<tr><th>Payment Method</th><td>' . e($order->payment_method) . '</td></tr>';
            $html .= '<tr><th>Payment Status</th><td>' . e($order->payment_status) . '</td></tr>';
            $html .= '<tr><th>Transaction Id</th><td>' . e($order->transaction_id ?: 'Nill') . '</td></tr>';
            $html .= '<tr><th>Total Amount</th><td>' . e($order->total_order_price) . '</td></tr>';
            $html .= '<tr><th>Booked At</th><td>' . e($order->booked_at) . '</td></tr>';
            $html .= '</table>';

            return json_encode(['status' => 1, 'data' => $html]);
        } elseif ($request->request_type == 'cancel_order') {
            $orderId = intval($request->orderId);
            $cancelReason = trim($request->cancelReason);

            if (empty($orderId) || $cancelReason === '') {
                return json_encode(['status' => 0, 'message' => 'Invalid order or missing cancel reason.']);
            }

            // processFullFlightCancellation() reads $request->cancel_reason (snake_case),
            // while the view/this dispatcher use cancelReason (camelCase) - bridge it.
            $request->merge(['cancel_reason' => $cancelReason]);

            // processFullFlightCancellation() type-hints an Eloquent OrderMaster model,
            // not a stdClass - must fetch it this way, not via DB::table(...)->first().
            $orderMaster = OrderMaster::find($orderId);

            if (empty($orderMaster)) {
                return json_encode(['status' => 0, 'message' => 'Order not found.']);
            }

            // processFullFlightCancellation() already validates status, payment_status,
            // duplicate cancellation, and the 2-hour cutoff internally - no need to
            // duplicate those checks here.
            $response = $this->processFullFlightCancellation($request, $orderMaster, Auth::user());

            // It returns response()->json(...), which sets Content-Type: application/json
            // and would make jQuery auto-parse the body before $.parseJSON(data) runs in
            // the view, throwing "[object Object] is not valid JSON". Pull out the raw
            // JSON string instead, same fix applied to every other branch here.
            return $response->getContent();
        } elseif ($request->request_type == 'export_flight_detailed_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/flight_order_detailed_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Booking Id', 'Transaction Id', 'Order Type', 'Customer Name', 'Customer Address1', 'Booking Type', 'Adults', 'Infants', 'Total Passengers', 'Booking Date', 'Booking Time', 'Total Price', 'Status', 'Payment Method', 'Payment Status', 'Leg', 'Flight Number', 'Route', 'Flight Date');

            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }

            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);

            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $isRoundTrip = ($value->booking_type ?? '') === 'roundtrip';

                    // One leg row for onward, plus one more for return if round trip.
                    $legs = [
                        [
                            'label' => 'Onward',
                            'flight_number' => $value->onward_flight_number ?? 'N/A',
                            'route' => trim(($value->onward_source_code ?? '') . ' - ' . ($value->onward_destination_code ?? ''), ' -') ?: 'N/A',
                            'flight_date' => $value->onward_flight_date ?? '',
                            'adults' => $value->departure_adult_count ?? $value->adult_count ?? '',
                            'infants' => $value->departure_infant_count ?? $value->infant_count ?? '',
                        ],
                    ];

                    if ($isRoundTrip) {
                        $legs[] = [
                            'label' => 'Return',
                            'flight_number' => $value->return_flight_number ?? 'N/A',
                            'route' => trim(($value->return_source_code ?? '') . ' - ' . ($value->return_destination_code ?? ''), ' -') ?: 'N/A',
                            'flight_date' => $value->return_flight_date ?? '',
                            'adults' => $value->return_adult_count ?? '',
                            'infants' => $value->return_infant_count ?? '',
                        ];
                    }

                    foreach ($legs as $leg) {
                        $data = [];

                        if (Auth::user()->access_type == 'superadmin') {
                            $data['vendor'] = $value->vendor_name ?? '';
                        }

                        $data['invoice_id'] = $value->invoice_id;
                        $data['transaction_id'] = $value->transaction_id;
                        $data['order_type'] = $value->order_type;

                        $data['customer_name'] = $value->customer_name;
                        $data['customer_address1'] = $value->customer_address1 ?? '';

                        $data['booking_type'] = ucfirst($value->booking_type ?? '');
                        $data['adult_count'] = $value->adult_count ?? '';
                        $data['infant_count'] = $value->infant_count ?? '';
                        $data['total_passengers'] = $value->total_passengers ?? '';

                        $data['created_date'] = date("Y-m-d", strtotime($value->booked_at));
                        $data['created_time'] = date("h:i a", strtotime($value->booked_at));

                        $data['total_order_price'] = $value->total_order_price;
                        $data['status'] = $value->status;
                        $data['payment_gateway'] = $value->payment_method;
                        $data['payment_status'] = $value->payment_status;

                        $data['leg_label'] = $leg['label'];
                        $data['leg_flight_number'] = $leg['flight_number'];
                        $data['leg_route'] = $leg['route'];
                        $data['leg_flight_date'] = $leg['flight_date'];

                        fputcsv($fp, $data);
                    }

                    fputcsv($fp, array());
                }
            }

            fclose($fp);

            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'print_flight_order') {
            $OrderMaster = DB::select($request->printQuery);

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:30%;"><strong>User & Transaction Information</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Date of Booking</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Flight</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Route</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Passengers</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:20%;"><strong>Payment Details</strong></th></tr>';

            foreach ($OrderMaster as $value) {
                $isRoundTrip = ($value->booking_type ?? '') === 'roundtrip';

                $flightNumbers = trim(($value->onward_flight_number ?? '') . ($isRoundTrip ? ' / ' . ($value->return_flight_number ?? '') : ''), ' /');

                $routeOnward = trim(($value->onward_source_code ?? '') . ' - ' . ($value->onward_destination_code ?? ''), ' -');
                $routeReturn = $isRoundTrip ? trim(($value->return_source_code ?? '') . ' - ' . ($value->return_destination_code ?? ''), ' -') : '';
                $routes = $routeOnward . ($isRoundTrip ? '<br>' . $routeReturn : '');

                $html .= '<tr style="font-size:14px;">'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:30%;">'
                    . 'User Name: ' . $value->customer_name . '<br>Mobile No: ' . $value->customer_phone . '<br>Booking Id: ' . $value->invoice_id . '<br>Mode Of payment: ' . $value->order_type . '<br>PG: ' . $value->payment_method . '<br>TXN Id: ' . $value->transaction_id . '<br>Grand total: ' . $value->total_order_price . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y h:i a", strtotime($value->booked_at)) . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $flightNumbers . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $routes . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">Adults: ' . ($value->adult_count ?? '') . '<br>Infants: ' . ($value->infant_count ?? '') . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:20%;">'
                    . 'Sub Total: ' . ($value->sub_total_price ?? '') . '<br>GST: ' . ($value->gst_amount ?? '') . '<br>Service Charge: ' . ($value->service_charge_amount ?? '') . '<br>Grand Total: ' . $value->total_order_price . '</td>'
                    . '</tr>';
            }

            $html .= '</table></div></body></html>';

            $file = 'documents/Flight_Order_' . time() . '.pdf';
            $pdfname = public_path($file);
            PDF::loadHTML(html_entity_decode($html))->save($pdfname);
            echo asset($file);
            exit;
        }
    }

    // Handle flight order details, customer details, cancellation, export, and print operations.
    private function processFullFlightCancellation(Request $request,OrderMaster $orderMaster,$user) {
        try {

            if (empty($orderMaster)) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Invalid booking.'
                ], 422);
            }

            if ($orderMaster->status !== 'completed') {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Payment for invoice ' . $orderMaster->invoice_id . ' is not completed.'
                ], 422);
            }

            if ($orderMaster->payment_status !== 'success') {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Payment for invoice ' . $orderMaster->invoice_id . ' is not completed.'
                ], 422);
            }

            if ($orderMaster->status === 'cancelled') {
                return response()->json([
                    'status'  => 0,
                    'message' => 'This booking has already been cancelled.'
                ], 422);
            }

            $flightBookingDetails = FlightBooking::where('booking_id',$orderMaster->order_id)
            ->with('scheduleOnward', 'scheduleReturn')
            ->first();

            if (empty($flightBookingDetails)) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Flight booking details not found.'
                ], 422);
            }


            if ($orderMaster->price_type === 'reschedule') {

                $rescheduled = Reschedule::where('new_booking_id',$orderMaster->order_id)->first();

                if (empty($rescheduled)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Reschedule booking details not found.'
                    ], 422);
                }

                $oldOrderMaster = OrderMaster::where('order_id',$rescheduled->old_booking_id)->first();

                if (empty($oldOrderMaster)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Original booking details not found.'
                    ], 422);
                }

                $PaymentHistory = PaymentHistory::find($oldOrderMaster->payment_id);

            } else {

                $PaymentHistory = PaymentHistory::find($orderMaster->payment_id);
            }

            if (empty($PaymentHistory)) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Invalid transaction.'
                ], 422);
            }

            $departureSchedule = $flightBookingDetails->scheduleOnward;

            if (empty($departureSchedule) || empty($flightBookingDetails->onward_flight_date) || empty($departureSchedule->departure_time)) {

                return response()->json([
                    'status'  => 0,
                    'message' => 'Flight departure details are missing.'
                ], 422);
            }

            $departureDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $flightBookingDetails->onward_flight_date .
                ' ' .
                $departureSchedule->departure_time
            );

            $bookingCloseTime = $departureDateTime
                ->copy()
                ->subHours(2);

            if (Carbon::now()->greaterThanOrEqualTo($bookingCloseTime)) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Online cancellation is closed. Booking closes 2 hours prior to scheduled departure.'
                ], 422);
            }


            if ($flightBookingDetails->booking_type === 'roundtrip' && !empty($flightBookingDetails->return_schedule_id) && !empty($flightBookingDetails->scheduleReturn)) {

                $returnFlight = $flightBookingDetails->scheduleReturn;

                if (!empty($flightBookingDetails->return_flight_date) && !empty($returnFlight->departure_time)) {

                    $returnDateTime = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $flightBookingDetails->return_flight_date .
                        ' ' .
                        $returnFlight->departure_time
                    );

                    $returnBookingCloseTime = $returnDateTime
                        ->copy()
                        ->subHours(2);

                    if (Carbon::now()->greaterThanOrEqualTo($returnBookingCloseTime)) {
                        return response()->json([
                            'status'  => 0,
                            'message' => 'Online cancellation is closed. Booking closes 2 hours prior to scheduled return departure.'
                        ], 422);
                    }
                }
            }

            $passengers = PassengerDetail::where('booking_id',$orderMaster->order_id)->get();

            if ($passengers->isEmpty()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'No passengers found for this booking.'
                ], 422);
            }

            $refundAmount = (float) $orderMaster->total_order_price;

            if ($refundAmount <= 0) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Invalid refund amount.'
                ], 422);
            }

            $cancellPassenger = [];

            $passengerName = '
                <table border="1" cellspacing="0" cellpadding="5">
                    <thead>
                        <tr>
                            <th>Passenger Name</th>
                            <th>Passenger Type</th>
                            <th>Journey Type</th>
                        </tr>
                    </thead>
                    <tbody>
            ';

            foreach ($passengers as $passenger) {
                $passengerName .= '
                    <tr>
                        <td>'
                        . $passenger->title . ' '
                        . $passenger->first_name . ' '
                        . $passenger->last_name .
                        '</td>
                        <td>' . $passenger->passenger_type . '</td>
                        <td>' . $passenger->journey_type . '</td>
                    </tr>
                ';

                $cancellPassenger[] = [
                    'passenger_id'        => $passenger->id,
                    'cancellation_reason' => $request->cancel_reason,
                    'cancelled_by'        => $user->id,
                    'gross_fare_paid'     => 0,
                    'cancellation_fee'    => 0,
                    'net_refund_amount'   => $refundAmount,
                    'transaction_id'      => $PaymentHistory->transaction_id,
                    'cancelled_at'        => now(),
                    'created_by'          => $user->id,
                    'updated_by'          => $user->id,
                    'created_at'          => now(),
                    'updated_at'          => now()
                ];
            }

            $passengerName .= '</tbody></table>';

            $refundResponse = [];
            $refundTxnId = '';
            $requestId = '';
            $refundDataId = null;

            if ($refundAmount > 0 && $orderMaster->payment_gateway === 'hdfc') {

                $HDFC_KEY = null;
                $HDFC_SALT = null;

                require_once public_path('paytm_lib/config_paytm.php');

                if (!empty($orderMaster->hdfc_key) && !empty($orderMaster->hdfc_salt) && defined('PAYTM_ENVIRONMENT') && PAYTM_ENVIRONMENT === 'PROD') {

                    $HDFC_KEY = $orderMaster->hdfc_key;
                    $HDFC_SALT = $orderMaster->hdfc_salt;
                }

                if (empty($HDFC_KEY) || empty($HDFC_SALT)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Payment gateway credentials are missing.'
                    ], 422);
                }


                $command = 'cancel_refund_transaction';

                $var1 = $PaymentHistory->mihpayid;
                $var2 = date('dmY') . time();
                $var3 = number_format($refundAmount,2,'.','');

                $hashString = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;

                $hash = strtolower(hash('sha512', $hashString));

                $gatewayData = [
                    'key'     => $HDFC_KEY,
                    'hash'    => $hash,
                    'command' => $command,
                    'var1'    => $var1,
                    'var2'    => $var2,
                    'var3'    => $var3
                ];

                $qs = http_build_query($gatewayData);

                $wsUrl = VERIFY_URL;

                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL, $wsUrl);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $qs);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

                $gatewayResponse = curl_exec($curl);

                $curlError = curl_error($curl);

                curl_close($curl);


                if (!empty($curlError)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Unable to connect to payment gateway. Please try again.'
                    ], 422);
                }


                $refundResponse = json_decode($gatewayResponse,true);

                if (!is_array($refundResponse)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Invalid response received from payment gateway.'
                    ], 422);
                }


                if (isset($refundResponse['status']) && $refundResponse['status'] != 1) {

                    return response()->json([
                        'status'  => 0,
                        'message' => $refundResponse['msg']
                            ?? 'Unable to initiate refund. Please try after some time.'
                    ], 422);
                }


                $resultMessage = $refundResponse['msg'] ?? 'Refund initiated successfully.';

                $refundTxnId = $refundResponse['bank_ref_num'] ?? '';

                $requestId = $refundResponse['request_id']  ?? '';

                $RefundData = new CustomerRefund([
                    'vendor_id'       => $orderMaster->vendor_id,
                    'order_id'        => $orderMaster->id,
                    'invoice_id'      => $orderMaster->invoice_id,
                    'order_type'      => $orderMaster->order_type,
                    'service_type'    => 'flight',
                    'service_id'      => $orderMaster->service_name_id,
                    'customer_id'     => $orderMaster->customer_id,
                    'order_date'      => $orderMaster->created_at,
                    'cancel_date'     => date('Y-m-d'),
                    'paid_amount'     => $orderMaster->total_order_price,
                    'refund_amount'   => $refundAmount,
                    'refund_percent'  => 100,
                    'payment_method'  => $orderMaster->payment_gateway,
                    'client_txn_id'   => $PaymentHistory->transaction_id,
                    'pg_txn_id'       => $PaymentHistory->mihpayid,
                    'refund_status'   => 'PENDING',
                    'result_msg'      => $resultMessage,
                    'reference_id'    => $requestId,
                    'refund_txn_id'   => $refundTxnId,
                    'response_json'   => json_encode($refundResponse),
                    'payment_id'      => $PaymentHistory->id
                ]);

                $RefundData->save();

                $refundDataId = $RefundData->id;


                foreach ($cancellPassenger as $key => $cancelData) {

                    $cancellPassenger[$key]['refund_txn_id'] = $refundTxnId;
                    $cancellPassenger[$key]['refund_id'] = $refundDataId;
                    $cancellPassenger[$key]['reference_id'] =  $requestId;
                }
            }
            DB::beginTransaction();

            $adultDepartureCount = $passengers
                ->where('journey_type', 'DEPARTURE')
                ->where('passenger_type', 'ADULT')
                ->count();

            if ($adultDepartureCount > 0) {

                $onwardInventory = DB::table('seat_inventory')
                    ->where('schedule_id',$departureSchedule->id)
                    ->where('date',$flightBookingDetails->onward_flight_date)
                    ->first();

                if (empty($onwardInventory)) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Seat inventory record not found for the onward flight.'
                    ], 422);
                }

                $bookedColumn = $orderMaster->order_type === 'online' ? 'online_booked' : 'offline_booked';

                DB::table('seat_inventory')
                    ->where('id', $onwardInventory->id)
                    ->update([
                        $bookedColumn => DB::raw(
                            'GREATEST(' .
                            $bookedColumn .
                            ' - ' .
                            intval($adultDepartureCount) .
                            ', 0)'
                        ),
                        'updated_at' => now(),
                    ]);
            }


            if ($flightBookingDetails->booking_type === 'roundtrip' && !empty($flightBookingDetails->return_schedule_id) && !empty($flightBookingDetails->scheduleReturn)) {

                $returnFlight = $flightBookingDetails->scheduleReturn;

                $adultReturnCount = $passengers
                    ->where('journey_type', 'RETURN')
                    ->where('passenger_type', 'ADULT')
                    ->count();

                if ($adultReturnCount > 0) {
                    $returnInventory = DB::table('seat_inventory')
                        ->where('schedule_id', $returnFlight->id)
                        ->where('date',$flightBookingDetails->return_flight_date)
                        ->first();

                    if (empty($returnInventory)) {
                        DB::rollBack();
                        return response()->json([
                            'status'  => 0,
                            'message' => 'Seat inventory record not found for the return flight.'
                        ], 422);
                    }

                    $bookedColumn = $orderMaster->order_type === 'online' ? 'online_booked' : 'offline_booked';

                    DB::table('seat_inventory')
                        ->where('id',$returnInventory->id)
                        ->update([
                            $bookedColumn => DB::raw('GREATEST(' . $bookedColumn .' - ' . intval($adultReturnCount) . ', 0)'),
                            'updated_at' => now(),
                        ]);
                }
            }


            PassengerDetail::where('booking_id',$orderMaster->order_id)
            ->update([
                'order_status' => 2
            ]);


            if ($orderMaster->price_type === 'reschedule') {
                Reschedule::where('new_booking_id',$orderMaster->order_id)
                ->update([
                    'status' => 'CANCEL'
                ]);
            }


            $orderMaster->update([
                'status'                 => 'cancelled',
                'cancel_reason'          => $request->cancel_reason,
                'refund_amount'          => $refundAmount,
                'payment_gateway_error'  => 2,
                'payment_error_response' => json_encode($refundResponse)
            ]);

            OrderDetail::where('order_master_id',$orderMaster->id)
            ->update([
                'status' => 'cancelled'
            ]);


            if (!empty($cancellPassenger)) {
                Cancellation::insert($cancellPassenger);
            }

            DB::commit();

            try {

                $mobileNumber = $orderMaster->customer_phone;
                $SmsTemplate = SmsTemplate::where('ref_code','BookingCancel')->first();

                if (!empty($SmsTemplate)) {

                    $sms_txt = str_replace(
                        [
                            '~var1~',
                            '~var2~',
                            '~var3~',
                            '~var4~',
                            '~var5~',
                            '~var6~',
                            '~var7~'
                        ],
                        [
                            $orderMaster->customer_name . ',',
                            $orderMaster->service_name,
                            $orderMaster->invoice_id,
                            number_format($refundAmount, 2),
                            "\n",
                            $orderMaster->vendor_name,
                            "\n\n"
                        ],
                        $SmsTemplate->source
                    );

                    parent::sendSms(
                        $mobileNumber,
                        $sms_txt,
                        $SmsTemplate->templete_id
                    );
                }

            } catch (\Throwable $smsError) {

            }

            try {

                $amt_msg = '
                    <p style="color:#000000;">
                        The complete booking amount of
                        &#8377;' . number_format($refundAmount, 2) . '
                        will be refunded to you.
                    </p>
                ';

                $Subject = '';

                $Message = '
                    <p style="color:#000000;">
                        Dear ' . $orderMaster->customer_name . ',
                    </p>
                ';

                $Message .= '
                    <p style="color:#000000;">
                        Your flight reservation booking for '
                        . $orderMaster->service_name .
                        ' having invoice no '
                        . $orderMaster->invoice_id .
                        ' has been cancelled successfully.
                    </p>
                ';

                $Message .= $passengerName;
                $Message .= $amt_msg;

                $Message .= '
                    <div style="margin-top:30px;text-align:center;">

                        <p style="font-family: Segoe UI;color:#333;">
                            Feel free to
                            <a href="https://www.bookodisha.com/tourism/contact">
                                contact us
                            </a>
                            for any further questions or clarifications.
                        </p>

                        <p style="font-family: Segoe UI;color:#333;">
                            <b>bookodisha.com support team</b>
                        </p>

                        <p style="font-family: Segoe UI;font-size:11px;color:#999;">
                            Please do not reply to this message.
                            This email address is automated for delivering outbound messages.
                            <br>
                            Please check the web site for more information
                            <a href="https://www.bookodisha.com/" target="_blank">
                                www.bookodisha.com
                            </a>
                            <br>
                            Copyright &copy; 2022 Odisha Tourism.
                            All rights reserved.
                        </p>

                    </div>
                ';

                $To = $orderMaster->customer_email;

                if (!empty($To)) {

                    Mail::to($To)->send(
                        new \App\Mail\RegistrationMailUser(
                            $Message,
                            $Subject
                        )
                    );
                }

            } catch (\Throwable $emailError) {

            }
            return response()->json([
                'status'        => 1,
                'message'       => 'Booking cancelled successfully. Full refund of ₹' . number_format($refundAmount, 2). ' has been initiated.',
                'refund_amount' => $refundAmount
            ]);


        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'status'  => 0,
                'message' => 'Unable to cancel booking. Please try again after some time.'
            ], 500);
        }
    }
       
}
