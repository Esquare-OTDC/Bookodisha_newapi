<?php

use Illuminate\Support\Facades\Route;

/* *************************************************************************************
* Helicopter Web Routes with prefix 'helicopter'
* These routes are loaded by the RouteServiceProvider within a group which
* is assigned the "web" middleware group and the "App\Http\Controllers\Helicopter" namespace.
* All routes in this file will be prefixed with 'aero'.
* ***************************************************************************************/

Route::get('all-flight', 'Air\AirTravelController@allFlight')->name('all-flight');
Route::get('add-new-flight', 'Air\AirTravelController@addNewFlight')->name('add-new-flight');
Route::post('add-new-flight-request', 'Air\AirTravelController@addNewFlightRequest')->name('add-new-flight-request');
Route::get('edit-new-flight/{id}', 'Air\AirTravelController@editNewFlight')->name('edit-new-flight');
Route::post('edit-new-flight-request/{id}', 'Air\AirTravelController@editNewFlightRequest')->name('edit-new-flight-request');

// Manage Flight Routes
Route::get('manage-flight/{id}', 'Air\AirTravelController@flightSchedule')->name('manage-flight');
Route::post('add-flight-schedule/{id}', 'Air\AirTravelController@addFlightSchedule')->name('add-flight-schedule');
Route::get('edit-flight-schedule/{id}','Air\AirTravelController@editFlightSchedule')->name('edit-flight-schedule');
Route::post('update-flight-schedule/{id}','Air\AirTravelController@updateFlightSchedule')->name('update-flight-schedule');
Route::post('flight-operation', 'Air\AirTravelController@flightOperation')->name('flight-operation');

Route::get('inventory-manage', 'Air\AirTravelInventoryController@inventoryManage')->name('inventory-manage');
Route::post('inventory-seat','Air\AirTravelInventoryController@getSeatInventory')->name('inventory-seat');
Route::post('inventory-search-flights', 'Air\AirTravelInventoryController@searchFlights')->name('inventory-search-flights');
Route::post('inventory-download-passengers', 'Air\AirTravelInventoryController@downloadPassengers')->name('inventory-download-passengers');
Route::post('inventory-download-passengers-list', 'Air\AirTravelInventoryController@downloadPassengersList')->name('inventory-download-passengers-list');
Route::post('inventory-seat-passenger-list','Air\AirTravelInventoryController@getSeatPassengerList')->name('inventory-seat-passenger-list');
Route::get('inventory-manage-list', 'Air\AirTravelInventoryController@inventoryManageList')->name('inventory-manage-list');
Route::get('seat-update', 'Air\AirTravelInventoryController@seatUpdate')->name('seat-update');
Route::post('update-seat-capacity','Air\AirTravelInventoryController@updateSeatCapacity')->name('update-seat-capacity');


// Flight Orders 
Route::get('flight-orders', 'Air\AirTravelController@flightOrders')->name('flight-orders');
Route::post('get-flight-orders', 'Air\AirTravelController@getFlightOrders')->name('get-flight-orders');
Route::post('flight-oprsn', 'Air\AirTravelController@flightOprsn')->name('flight-oprsn');