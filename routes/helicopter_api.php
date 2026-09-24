<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Helicopter\Api\HelicopterApiController;


/* *************************************************************************************
* Helicopter API Routes with prefix 'api/helicopter'
* These routes are loaded by the RouteServiceProvider within a group which
* is assigned the "api" middleware group and the "App\Http\Controllers\Helicopter\Api" namespace.
* All routes in this file will be prefixed with 'api/aero'.
* ***************************************************************************************/

const HELICOPTER_API_PREFIX = 'Air\AirTravelApiController@';


Route::get('/get-destinations', HELICOPTER_API_PREFIX.'getDestination');
Route::get('/get-flights', HELICOPTER_API_PREFIX.'flightSearch');
Route::post('/flights-booking', HELICOPTER_API_PREFIX.'flightBooking');
Route::post('/flight-check-out-details', HELICOPTER_API_PREFIX.'flightOrderPayment');
Route::get('/get-booking-details',HELICOPTER_API_PREFIX.'bookingDetails');
Route::post('/cancel-refund',HELICOPTER_API_PREFIX.'cancellationRefund')->middleware('webservice:api');
Route::post('/reschedule-flight',HELICOPTER_API_PREFIX.'rescheduleFlightBooking')->middleware('webservice:api');
Route::post('/reschedule-flight-payment',HELICOPTER_API_PREFIX.'flightOrderPaymentReschedule')->middleware('webservice:api');
Route::get('/get-reschedule-list',HELICOPTER_API_PREFIX.'passengerFlightRescheduleList');
Route::get('/get-assitance-list',HELICOPTER_API_PREFIX.'getAssitance');
Route::get('/download-ticket-copy',HELICOPTER_API_PREFIX.'downloadTicketCopy');



Route::middleware('webservice:api')->group(function () {
    Route::post('get-flight-order-details', 'Air\AirTravelInventoryController@flightOrderDetails');
    Route::get('get-flight-details', HELICOPTER_API_PREFIX.'getReschedulePassengerList');
});


// CRON
const CRON_API = 'Air\FlightCronController@';
Route::prefix('cron')->group(function(){
    Route::get('/add-inventory', CRON_API.'generateInventory90daysCron');
    Route::get('/flight-online-status', CRON_API.'checkOnlinePaymentStatus');
    Route::get('/flight-online-status-cancel', CRON_API.'orderCancellation');
    Route::get('/late-pending-status-cancel', CRON_API.'latePendingOrderCancle');
    Route::get('/refund-pending-status', CRON_API.'refundOrCancellation');
});





