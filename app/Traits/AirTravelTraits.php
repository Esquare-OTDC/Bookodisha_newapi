<?php
namespace App\Traits;

use App\AirModels\AirportMaster;
use App\AirModels\FareMaster;
use App\AirModels\FlightSchedule;
use App\AirModels\SeatInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Traits\HdfcTraits;

trait AirTravelTraits {

    use HdfcTraits;

    private $weeks = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        7 => 'sunday'
    ];


    public function getWeeks() {
        return $this->weeks;
    }

    public function getDayFromNumber($dayNumber=null) {
        try {
            $dayNumber = $dayNumber ?? date('N');

            $days = $this->getWeeks();

            return $days[$dayNumber] ?? null;
        } catch (Exception $e) {
            Log::error('Error in getDayFromNumber: ' . $e->getMessage());
            return null;
        }
    }

    public function getNumberFromDay($dayName = null) {
        try {
            $dayName = $dayName ?? strtolower(date('l'));
            $dayName = strtolower($dayName);
            $weeks = [
                'monday' => 1,
                'tuesday' => 2,
                'wednesday' => 3,
                'thursday' => 4,
                'friday' => 5,
                'saturday' => 6,
                'sunday' => 7,
            ];

            return $weeks[$dayName] ?? null;
        } catch (Exception $e) {
            Log::error('Error in getNumberFromDay: ' . $e->getMessage());
            return null;
        }
    }
    public function getDayFromDate($date=null) {
        try {

            $date = $date ?? date('Y-m-d');

            $weekName = date('l', strtotime($date));
            return strtolower($weekName);
        } catch (Exception $e) {
            Log::error('Error in getDayFromDate: ' . $e->getMessage());
            return null;
        }
    }

    /******************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-02
    * Description: This function debugs a raw SQL query and returns the final executed query with bindings.
    * @param \Illuminate\Database\Query\Builder $query The query builder instance.
    * @return string The final executed query.
    * *****************************************************************************************/
    public function rawQueryDebug($query){

        $sql = vsprintf(
            str_replace('?', "'%s'", $query->toSql()),
            $query->getBindings()
        );
        return $sql;
    }

    public function generateInventory90days($flightId = null, $slot_key = null){
        $count = 0;
        $inventory = [];
        $today = date("Y-m-d");

        $flightSchedules = $flightId ? FlightSchedule::where('flight_id', $flightId)->where('status', 'OPEN') : FlightSchedule::where('status', 'OPEN');
        if($slot_key){
            $flightSchedules = $flightSchedules->where('slot_key', $slot_key);
        }

        $flightSchedules = $flightSchedules->get();

        if($flightSchedules->isEmpty()) {
            return $count; // No schedules found, return 0
        }

        foreach($flightSchedules as $schedule){
            $seatInventory = SeatInventory::where('schedule_id', $schedule->id)->pluck('date')
                                ->map(function ($date) {
                                    return $date->format('Y-m-d');
                                })->unique()->values()->toArray();

            $departure_day = $this->getDayFromNumber($schedule->departure_day);
            for($i = 0; $i < 90; $i++){
                $date = date("Y-m-d", strtotime($today . " +$i days"));
                $dayOfWeek = $this->getDayFromDate($date);
                if(!in_array($date, $seatInventory) && strtolower($dayOfWeek) == strtolower($departure_day)){
                    $inventory[] = [
                        'schedule_id' => $schedule->id,
                        'date' => $date,
                        'online_capacity' => $schedule->online_capacity, // Default online capacity
                        'offline_capacity' => $schedule->offline_capacity, // Default offline capacity
                        'online_booked' => 0,
                        'offline_booked' => 0,
                        'status' => 'ACTIVE',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $count++;
                }
            }
        }

        if(!empty($inventory)){
            SeatInventory::insert($inventory);
        }
        return $count;
    }

    public function getSourceDetails($airportId=null){
        if($airportId){
            return AirportMaster::where('id', $airportId)->first();
        }
        return null;
    }

    public function getFarePrice($flightId, $schedule_id, $type='ADULT'){
        if(in_array($type, ['ADULT', 'INFANT'])){
            return FareMaster::where('flight_id', $flightId)->where('schedule_id', $schedule_id)->where('passenger_type', $type)->where('is_active',1)->first()->base_fare;
        }
    }

    public function updateSeatBySp($schedule_id, $date, $adult_count){
        $status = null;
        $message = null;

        DB::statement(
            "CALL sp_book_flight_seats(?, ?, ?, @status, @message)",
            [
                $schedule_id,
                $date,
                $adult_count
            ]
        );
        $result = DB::selectOne(
            "SELECT @status AS status, @message AS message"
        );
        $status = $result->status;
        $message = $result->message;
        return ['status' => $status, 'message' => $message];
    }

    public function releaseSeatBySp($schedule_id, $date, $adult_count){
        $status = null;
        $message = null;

        DB::statement(
            "CALL sp_release_flight_seats(?, ?, ?, @status, @message)",
            [
                $schedule_id,
                $date,
                $adult_count
            ]
        );
        $result = DB::selectOne(
            "SELECT @status AS status, @message AS message"
        );
        $status = $result->status;
        $message = $result->message;
        return ['status' => $status, 'message' => $message];
    }

    public function rescheduleSP($passenger_id,$trip_type,$source_airport_id,$destination_airport_id,$departure_date,$return_date,$reschedule_journey_type){

        $data = DB::select(
            "CALL sp_get_reschedule_flight_options(?, ?, ?, ?, ?, ?, ?)",
            [
                $passenger_id,
                strtoupper($trip_type),
                $source_airport_id,
                $destination_airport_id,
                $departure_date,
                $return_date,
                strtoupper($reschedule_journey_type)
            ]
        );
        $response = json_decode($data[0]->reschedule_response_json, true);
        return $response;
    }

    public function getOriginBookingSP($parent_booking_id){
        $data = DB::select(
            "CALL sp_get_latest_flight(?)",
            [
                $parent_booking_id,
            ]
        );
        return $data[0] ?? null;
    }

    public function getOriginBookingRefundSP($passenger_ids, $journey_type, $current_booking_id){
        $data = DB::select(
            "CALL sp_get_parent_booking_id(?, ?, ?)",
            [
                $passenger_ids,
                strtoupper($journey_type),
                $current_booking_id
            ]
        );
        return $data;
    }

    public function calculateCancelRefund($passenger_ids){
        $data = DB::select(
            "CALL sp_calculate_cancellation_refund(?)",
            [
                $passenger_ids,
            ]
        );
        return $data;
    }

    public function updateCancelRefund($passenger_ids){
        $data = DB::select(
            "CALL sp_cancel_passengers_and_process(?)",
            [
                $passenger_ids,
            ]
        );
        return $data;
    }
}
