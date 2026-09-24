<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    // user
    Route::post('user_login', 'ApiController@userLogin');
    Route::post('user_signup', 'ApiController@userSignup');
    Route::post('forgot_password', 'ApiController@forgotPassword');
    Route::post('reset_Password', 'ApiController@resetPassword');
    Route::post('verify_email', 'ApiController@verifyEmail');
    Route::post('vendor_profile', 'ApiController@vendorProfile');
    Route::post('vendor_register', 'ApiController@vendorSignup');
    Route::post('register_booking', 'ApiController@registerBooking');

    // mobile app
    Route::post('user_login_signup', 'ApiController@userLoginSignUp');
    Route::post('verify_qrcode', 'ApiController@veriyQrcode');
    Route::post('vendor_login', 'ApiController@vendorLogin');
    Route::get('search_property', 'ApiController@searchProperty');
    Route::get('get_version', 'ApiController@getVersion');

    // hotel
    Route::post('hotel_details', 'ApiController@hotelDetails');
    Route::post('get_assets', 'ApiController@getAssets');
    Route::post('hotel_booking_guest', 'ApiController@hotelBooking');

    // review
    Route::post('service_review', 'ApiController@serviceReview');

    // car
    Route::post('car_details', 'ApiController@carDetails');
    Route::post('car_booking_guest', 'ApiController@carBooking');

    // Caravan
    Route::post('caravan_details', 'CaravanBookingApiController@caravanDetails');

    // Caravan Booking & payments
    Route::post('caravan_booking_guest', 'CaravanBookingApiController@caravanBooking');

    Route::group(['prefix' => 'caravan'], function() {

        // Route::post('payment_guest', 'ApiController@caravanPayment');
    });

    // tour
    Route::post('tour_details', 'ApiController@tourDetails');
    Route::post('tour_booking_guest', 'ApiController@tourBooking');

    // Tickets
    Route::post('ticketing_details', 'ApiController@ticketingDetails');
    Route::post('ticket_booking_guest', 'ApiController@ticketBooking');

    //  Food Ordering
    Route::post('food_details', 'ApiController@foodDetails');
    Route::post('food_booking_guest', 'ApiController@foodBooking');

    //  Merchant
    Route::post('merchant_details', 'ApiController@merchantDetails');
    Route::post('merchant_booking_guest', 'ApiController@merchantBooking');

    // order
    Route::post('order_payment_guest', 'ApiController@orderPayment');

    // cms
    Route::post('homepage_assets', 'ApiController@homepageAssets');
    Route::post('site_pages', 'ApiController@sitePages');

    // Enqiry
    Route::post('post_enquiry', 'ApiController@postEnquiry');

    // Contact Us
    Route::post('contact_us', 'ApiController@contactUs');

    // payment
    Route::post('payment_success', 'ApiController@paymentSuccess');
    Route::post('payment_failure', 'ApiController@paymentFailure');
    Route::post('payment_cancel', 'ApiController@paymentCancel');
    Route::post('payment_details', 'ApiController@paymentDetails');
    Route::post('retry_payment', 'ApiController@retryPayment');
    Route::post('paytm_success', 'ApiController@paytmSuccess');

    Route::group(['middleware' => 'webservice:api'], function() {
        // user
        Route::get('user_logout', 'ApiController@userLogout');
        Route::get('user_details', 'ApiController@userDetails');
        Route::post('update_profile', 'ApiController@updateProfile');

        // hotel
        Route::post('hotel_booking', 'ApiController@hotelBooking');

        // car
        Route::post('car_booking', 'ApiController@carBooking');

        // tour
        Route::post('tour_booking', 'ApiController@tourBooking');

        // ticketing
        Route::post('ticket_booking', 'ApiController@ticketBooking');

        // food
        Route::post('food_booking', 'ApiController@foodBooking');

        // merchant
        Route::post('merchant_booking', 'ApiController@merchantBooking');

        // order
        Route::post('order_payment', 'ApiController@orderPayment');
        Route::post('order_details', 'ApiController@orderDetails');
        Route::post('cancel_booking_request', 'ApiController@cancelBookingRequest');

        // review
        Route::post('post_service_review', 'ApiController@postServiceReview');

        // Coupons
        Route::post('coupon_details', 'ApiController@couponDetails');

        // Caravan
        Route::post('caravan_booking', 'CaravanBookingApiController@caravanBooking');

        // Mobile App
        Route::get('get_notifications', 'ApiController@getNotifications');
        Route::get('vedor_services', 'ApiController@vendorServices');
        Route::post('verify_qrcode', 'ApiController@veriyQrcode');
        Route::post('hotel_inventory', 'ApiController@hotelInventory');
        Route::post('rental_inventory', 'ApiController@rentalInventory');
        Route::post('caravan_inventory', 'CaravanBookingApiController@caravanInventory');
        Route::post('vendor_orders', 'ApiController@vendorOrders');
        Route::post('generate_instant_ticket', 'ApiController@generateInstantTicket');
        Route::post('verify_payment', 'ApiController@verifyPayment');


    });
});


Route::get('caravan-payment-api', 'PaymentStatus\PaymentUpdate@updatePaymentCaravanStatus');
Route::get('test-sms-api', 'PaymentStatus\PaymentUpdate@testsmsApi');

/*  Hall Module Routes */
Route::group(['prefix' => 'auth'], function() {

    // Property & Hall related routes
    Route::post('property_hall_details', 'Api\HallBookingApiController@getPropertyHallDetails');

    // Hall Booking & payments
    Route::post('booking_hall_guest', 'Api\HallBookingApiController@bookingHall');
    // booking realted routes
    Route::group(['middleware' => 'webservice:api'], function() {
        Route::post('booking_hall', 'Api\HallBookingApiController@bookingHall');
    });
});

Route::get('hall-inventory-create', 'Api\HallBookingApiController@createInventoryApi');

/* External Routes */
Route::get('ticketing-booking', 'ExternalApiController@ticketsBookingApi');
Route::get('tour-booking', 'ExternalApiController@tourBookingApi');
Route::get('hotel-booking', 'ExternalApiController@hotelBookingApi');
