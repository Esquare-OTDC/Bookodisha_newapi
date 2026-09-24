<?php

namespace App\Http\Controllers\Air;

use App\Http\Controllers\Controller;
use App\Traits\AirTravelTraits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\AirModels\AirportMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\City;
Use App\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\AirModels\FlightMaster;
use App\AirModels\FareMaster;
use App\AirModels\FlightSchedule;

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

}
