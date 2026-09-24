<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'AuthController@login');
Route::get('login', 'AuthController@login');
Route::get('logout', 'AuthController@logout');
Route::post('post-login', 'AuthController@postLogin')->name('post-login');
Route::post('forgot-password', 'AuthController@forgotPassword')->name('forgot-password');
Route::get('change-password/{token}/{email}', 'AuthController@changePassword')->name('change-password');
Route::post('post-password-change', 'AuthController@postPasswordChange')->name('post-password-change');

// cron
Route::get('insert-master-inventory', 'HotelController@insertMasterInventory');
Route::get('insert-custom-inventory/{vendor}/{start}/{end}/{hotelId}', 'HotelController@insertCustomInventory');
Route::get('insert-rental-inventory', 'CarBookingController@insertRentalInventory');
Route::get('insert-mmt-inventory/{vendor}/{start}/{end}', 'IntegrationController@insertMmtInventory');

Route::group(['middleware' => ['auth']], function() {

    // User
    Route::get('dashboard', 'UserController@dashboard');
    Route::get('profile-edit', 'UserController@profileEdit')->name('profile-edit');
    Route::post('post-profile-edit', 'UserController@postProfileEdit')->name('post-profile-edit');
    Route::get('manage-privilege/{id}', 'UserController@managePrivilege')->name('manage-privilege');
    Route::post('save-privilege', 'UserController@savePrivilege')->name('save-privilege');
    Route::post('setting-oprsn', 'UserController@settingOprsn')->name('setting-oprsn');

    // Customer details
    Route::get('user-details', 'UserController@userDetails')->name('user-details');
    Route::post('get-user-details', 'UserController@getUserDetails')->name('get-user-details');
    Route::post('user-oprsn', 'UserController@userOprsn')->name('user-oprsn');

    // ajax
    Route::post('city-state-details', 'UserController@cityStateDetails')->name('city-state-details');

    // staff details
    Route::get('subuser-details', 'UserController@staffDetails')->name('subuser-details');
    Route::post('get-staff-details', 'UserController@getStaffDetails')->name('get-staff-details');
    Route::post('staff-oprsn', 'UserController@staffOprsn')->name('staff-oprsn');
    Route::get('subuser-add', 'UserController@staffAdd')->name('subuser-add');
    Route::post('staff-add-request', 'UserController@staffAddRequest')->name('staff-add-request');
    Route::get('subuser-edit/{id}', 'UserController@staffEdit')->name('subuser-edit');
    Route::post('staff-edit-request', 'UserController@staffEditRequest')->name('staff-edit-request');

    // Agent
    Route::get('agent-details', 'UserController@agentDetails')->name('agent-details');
    Route::post('get-agent-details', 'UserController@getAgentDetails')->name('get-agent-details');
    Route::get('agent-add', 'UserController@agentAdd')->name('agent-add');
    Route::post('agent-add-request', 'UserController@agentAddRequest')->name('agent-add-request');
    Route::get('agent-edit/{id}', 'UserController@agentEdit')->name('agent-edit');
    Route::post('agent-edit-request', 'UserController@agentEditRequest')->name('agent-edit-request');

    // orders
    Route::get('hotel-orders', 'UserController@hotelOrders')->name('hotel-orders');
    Route::post('get-hotel-orders', 'UserController@getHotelOrders')->name('get-hotel-orders');
    Route::post('get-rental-orders', 'UserController@getRentalOrders')->name('get-rental-orders');
    Route::post('get-tour-orders', 'UserController@getTourOrders')->name('get-tour-orders');
    Route::post('get-ticket-orders', 'UserController@getTicketOrders')->name('get-ticket-orders');
    Route::post('get-food-orders', 'UserController@getFoodOrders')->name('get-food-orders');
    Route::post('order-oprsn', 'UserController@orderOprsn')->name('order-oprsn');

    Route::get('booking-voucher/{id}', 'UserController@bookingVoucher')->name('booking-voucher');

    // Email
    Route::get('send-mail', 'UserController@sendMail')->name('send-mail');
    Route::post('send-mail-request', 'UserController@sendMailRequest')->name('send-mail-request');
    Route::post('sendmail-oprsn', 'UserController@sendMailOprsn')->name('sendmail-oprsn');

    Route::post('gst-oprsn', 'UserController@gstOprsn')->name('gst-oprsn');

    // CMS
    Route::get('manage-slider', 'UserController@manageSlider')->name('manage-slider');
    Route::post('get-slider', 'UserController@getSlider')->name('get-slider');
    Route::post('slider-oprsn', 'UserController@sliderOprsn')->name('slider-oprsn');
    Route::get('add-slider', 'UserController@addSlider')->name('add-slider');
    Route::post('add-slider-request', 'UserController@addSliderRequest')->name('add-slider-request');
    Route::get('edit-slider/{id}', 'UserController@editSlider')->name('edit-slider');
    Route::post('edit-slider-request', 'UserController@editSliderRequest')->name('edit-slider-request');
});

// Hotel
Route::group(['middleware' => ['auth', 'hotel']], function() {

    Route::get('all-hotels', 'HotelController@allHotels')->name('all-hotels');
    Route::post('get-hotel-details', 'HotelController@getHotelDetails')->name('get-hotels-details');
    Route::post('hotel-oprsn', 'HotelController@hotelOprsn')->name('hotel-oprsn');
    Route::get('hotel-add', 'HotelController@addHotel')->name('hotel-add');
    Route::post('hotel-add-request', 'HotelController@hotelAddRequest')->name('hotel-add-request');
    Route::get('hotel-edit/{id}', 'HotelController@editHotel')->name('hotel-edit');
    Route::post('hotel-edit-request', 'HotelController@hotelEditRequest')->name('hotel-edit-request');
    Route::get('manage-hotel-rooms/{id}', 'HotelController@manageHotelRoom')->name('manage-hotel-rooms');
    Route::post('room-add-request', 'HotelController@roomAddRequest')->name('room-add-request');
    Route::get('room-edit/{id}', 'HotelController@editRoom')->name('room-edit');
    Route::post('room-edit-request', 'HotelController@roomEditRequest')->name('room-edit-request');
    Route::get('hotel-availability', 'HotelController@hotelAvailability')->name('hotel-availability');

    // Rooom Amenities
    Route::get('room-attribute', 'HotelController@roomAttribute')->name('room-attribute');
    Route::post('room-attribute-add-request', 'HotelController@roomAttributeAddRequest')->name('room-attribute-add-request');
    Route::get('room-attribute-terms/{id}', 'HotelController@roomAttributeTerm')->name('hotel-attribute-terms');
    Route::post('room-attribute-terms-add-request', 'HotelController@roomAttributeTermAddRequest')->name('room-attribute-terms-add-request');

    // MIS Report
    Route::get('hotel-mis-report', 'HotelController@hotelMisReport')->name('hotel-mis-report');
    Route::get('hotelroom-mis-report', 'HotelController@hotelroomMisReport')->name('hotelroom-mis-report');
    Route::post('room-mis-request', 'HotelController@roomMisRequest')->name('room-mis-request');
    Route::get('hotel-booking-report', 'HotelController@hotelBookingReport')->name('hotel-booking-report');
    Route::get('hotel-roomstay-report', 'HotelController@hotelRoomstayReport')->name('hotel-roomstay-report');
    Route::get('hotel-cancel-report', 'HotelController@hotelCancelReport')->name('hotel-cancel-report');
    Route::get('agent-hotel-report', 'HotelController@agentHotelReport')->name('agent-hotel-report');
    Route::get('hotel-booking-count-report', 'HotelController@hotelBookingCountReport')->name('hotel-booking-count-report');
    Route::get('hotel-availablity-report', 'HotelController@hotelAvailablityReport')->name('hotel-availablity-report');
    Route::get('hotel-room-night-report', 'HotelController@hotelRoomNightReport')->name('hotel-room-night-report');
    Route::get('hotel-pax-report', 'HotelController@hotelPaxReport')->name('hotel-pax-report');
    Route::get('hotel-gst-report', 'HotelController@hotelGstReport')->name('hotel-gst-report');

    Route::get('inventory-comparison-report', 'HotelController@bookingComarisonReport')->name('inventory-comparison-report');

    // Hall Orders
    Route::get('hall-orders', 'HallController@hallOrders')->name('hall-orders');
    Route::post('get-hall-orders', 'HallController@getHallOrders')
    ->name('get-hall-orders');
    // Hall Availability
});




// Vendor Access Hotel
Route::group(['middleware' => ['auth', 'vendor', 'hotel', 'XssSanitizer']], function() {
    Route::get('hotel-offline-orders', 'HotelController@createOfflineOrder')->name('hotel-offline-orders');

    Route::get('hotel-room-pricing', 'HotelController@hotelPricing')->name('hotel-room-pricing');
    Route::post('get-hotel-pricing', 'HotelController@getHotelPricing')->name('get-hotel-pricing');
    Route::get('manage-hotel-pricing', 'HotelController@manageHotelPricing')->name('manage-hotel-pricing');
    Route::post('add-hotel-pricing', 'HotelController@addHotelPricing')->name('add-hotel-pricing');
    Route::get('hotel-room-block-data', 'HotelController@hotelRoomBlockData')->name('hotel-room-block-data');
    Route::post('get-block-data', 'HotelController@getBlockData')->name('get-block-data');
    Route::get('block-hotel-room', 'HotelController@blockHotelRoom')->name('block-hotel-room');
    Route::post('block-hotel-room-request', 'HotelController@blockHotelRoomRequest')->name('block-hotel-room-request');
    Route::get('blocked-hotels', 'HotelController@blockedHotels')->name('blocked-hotels');
    Route::post('get-blocked-hotels', 'HotelController@getBlockedHotels')->name('get-blocked-hotels');
    Route::get('offline-order', 'HotelController@offlineHotelOrder')->name('offline-order');
    Route::post('create-offline-order', 'HotelController@createOfflineOrder')->name('create-offline-order');
    Route::post('create-blocked-hotel-order', 'HotelController@createBlockedHotelOrder')->name('create-blocked-hotel-order');
    Route::get('hotel-sales', 'HotelController@hotelSales')->name('hotel-sales');
    Route::post('get-hotel-sales', 'HotelController@getHotelsales')->name('get-hotel-sales');
    Route::get('add-sales-data', 'HotelController@addSalesData')->name('add-sales-data');
    Route::post('add-sales-request', 'HotelController@addSalesRequest')->name('add-sales-request');
    Route::get('hotel-inventory', 'HotelController@hotelInventory')->name('hotel-inventory');
    Route::post('get-hotel-inventory', 'HotelController@getHotelInventory')->name('get-hotel-inventory');
    Route::get('cancel-options/{id}/{orderId}', 'HotelController@cancelOptions')->name('cancel-options');
    Route::post('hotel-cancel-oprsn', 'HotelController@hotelCancelOprsn')->name('hotel-cancel-oprsn');
    Route::get('hotel-inventory-summary', 'HotelController@hotelInventorySummary')->name('hotel-inventory-summary');
    Route::post('get-inventory-summary', 'HotelController@getInventorySummary')->name('get-inventory-summary');
    Route::get('manage-hotel-inventory', 'HotelController@manageHotelInventory')->name('manage-hotel-inventory');
    Route::post('get-master-hotel', 'HotelController@getMasterHotel')->name('get-master-hotel');
    Route::get('modify-hotel-inventory', 'HotelController@modifyHotelInventory')->name('modify-hotel-inventory');
    Route::post('modify-inventory-request', 'HotelController@modifyInventoryRequest')->name('modify-inventory-request');
    Route::get('hotel-estimates', 'HotelController@hotelEstimates')->name('hotel-estimates');
    Route::post('get-hotel-estimates', 'HotelController@getHotelEstimates')->name('get-hotel-estimates');
    Route::get('create-hotel-estimate', 'HotelController@createHotelEstimate')->name('create-hotel-estimate');
    Route::post('create-hotel-estimate-request', 'HotelController@createHotelEstimateRequest')->name('create-hotel-estimate-request');
    Route::get('create-extrabed-bill', 'HotelController@createExtrabedBill')->name('create-extrabed-boll');
    Route::post('create-extrabed-bill-request', 'HotelController@createExtrabedBillRequest')->name('create-extrabed-bill-request');
});

// Car
Route::group(['middleware' => ['auth', 'rental']], function() {

    Route::get('all-cars', 'CarBookingController@allCars')->name('all-cars');
    Route::post('get-car-details', 'CarBookingController@getCarDetails')->name('get-car-details');
    Route::post('car-oprsn', 'CarBookingController@carOprsn')->name('car-oprsn');
    Route::get('car-add', 'CarBookingController@addCar')->name('car-add');
    Route::post('car-add-request', 'CarBookingController@carAddRequest')->name('car-add-request');
    Route::get('car-edit/{id}', 'CarBookingController@editCar')->name('car-edit');
    Route::post('car-edit-request', 'CarBookingController@carEditRequest')->name('car-edit-request');
    Route::get('rental-availability', 'CarBookingController@rentalAvailability')->name('rental-availability');

    Route::get('rental-inventory', 'CarBookingController@rentalInventory')->name('rental-inventory');
    Route::post('get-rental-inventory', 'CarBookingController@getRentalInventory')->name('get-rental-inventory');
    // orders
    Route::get('rental-orders', 'UserController@rentalOrders')->name('rental-orders');

    // MIS Report
    Route::get('rental-mis-report', 'CarBookingController@rentalMisReport')->name('rental-mis-report');



    // Caravan
    Route::get('caravan-add', 'CaravanController@addCaravan')->name('caravan-add');
    Route::get('caravan-attribute', 'CaravanController@caravanAttribute')->name('caravan-attribute');
    Route::post('caravan-attribute-add-request', 'CaravanController@caravanAttributeAddRequest')->name('caravan-attribute-add-request');
    Route::post('caravan-add-request', 'CaravanController@caravanAddRequest')->name('caravan-add-request');
    Route::get('all-caravan', 'CaravanController@allCaravan')->name('all-caravan');
    Route::post('get-caravan-details', 'CaravanController@getCaravanDetails')->name('get-caravan-details');
    Route::get('caravan-attribute-terms/{id}', 'CaravanController@caravanAttributeTerm')->name('caravan-attribute-terms');
    Route::post('caravan-attribute-terms-add-request', 'CaravanController@caravanAttributeTermAddRequest')->name('caravan-attribute-terms-add-request');
    Route::get('caravan-edit/{id}', 'CaravanController@editCaravan')->name('caravan-edit');
    Route::post('caravan-edit-request', 'CaravanController@caravanEditRequest')->name('caravan-edit-request');
    Route::post('get_caravan_inventory', 'CaravanController@getCaravanInventory')->name('get_caravan_inventory');
    Route::post('get-caravan-master-inventory', 'CaravanController@getCaravanMasterInventory')->middleware(['XssSanitizer'])->name('get-caravan-master-inventory');

    /* Caravan Booking */
    Route::get('caravan-inventory', 'CaravanController@caravanInventory')->name('caravan-inventory');
    Route::get('manage-caravan-inventory', 'CaravanController@manageCaravanInventory')->name('manage-caravan-inventory');
    Route::get('caravan-availability', 'CaravanController@caravanAvailability')->name('caravan-availability');
    Route::post('caravanOprsn', 'CaravanController@caravanOprsn')->name('caravanOprsn');


    /* BLOCK CARAVAN MODULE */
    Route::get('caravan-block-data', 'CaravanController@caravanBlockData')->name('caravan-block-data');
    Route::get('block-rental-caravan', 'CaravanController@blockRentalCaravan')->name('block-rental-caravan');
    Route::post('block-rental-caravan-request', 'CaravanController@blockRentalCaravanRequest')->name('block-rental-caravan-request');
    Route::post('caravan-oprsn', 'CaravanController@caravanOprsn')->name('caravan-oprsn');
    Route::post('check-caravan-availability', 'CaravanController@checkCaravanAvailability')->name('check-caravan-availability');

    // MIS  Caravan Report
    Route::get('caravan-mis-report', 'CaravanController@caravanMisReport')->name('caravan-mis-report');
    // MIS Report
    Route::get('caravan-booking-report', 'CaravanController@caravanBookingReport')->name('caravan-booking-report');
    Route::get('caravan-availability-report', 'CaravanController@caravanAvailabilityReport')->name('caravan-availability-report');
});

// Vendor Access Rental
Route::group(['middleware' => ['auth', 'vendor', 'rental', 'XssSanitizer']], function() {

    Route::get('rental-block-data', 'CarBookingController@rentalBlockData')->name('rental-block-data');
    Route::post('get-rental-block-data', 'CarBookingController@getRentalBlockData')->name('get-rental-block-data');
    Route::get('block-rental-vehicle', 'CarBookingController@blockRentalVehicle')->name('block-rental-vehicle');
    Route::post('block-rental-vehicle-request', 'CarBookingController@blockRentalVehicleRequest')->name('block-rental-vehicle-request');
    Route::get('blocked-vehicles', 'CarBookingController@blockedVehicles')->name('blocked-vehicles');
    Route::post('get-blocked-vehicles', 'CarBookingController@getBlockedVehicles')->name('get-blocked-vehicles');
    Route::get('rental-offline-order', 'CarBookingController@rentalOfflineOrder')->name('rental-offline-order');
    Route::post('create-rental-order', 'CarBookingController@createRentalOrder')->name('create-rental-order');
    Route::get('manage-rental-inventory', 'CarBookingController@manageRentalInventory')->name('manage-rental-inventory');
    Route::post('get-rental-master-inventory', 'CarBookingController@getRentalMasterInventory')->name('get-rental-master-inventory');
});

// Tour & Package
Route::group(['middleware' => ['auth', 'tour']], function() {

    Route::get('all-tours', 'TourController@allTours')->name('all-tours');
    Route::post('get-tour-details', 'TourController@getTourDetails')->name('get-tour-details');
    Route::post('tour-oprsn', 'TourController@tourOprsn')->name('tour-oprsn');
    Route::get('tour-add', 'TourController@addTour')->name('tour-add');
    Route::post('tour-add-request', 'TourController@tourAddRequest')->name('tour-add-request');
    Route::get('tour-edit/{id}', 'TourController@editTour')->name('tour-edit');
    Route::post('tour-edit-request', 'TourController@tourEditRequest')->name('tour-edit-request');
    Route::get('manage-tour-routes/{id}', 'TourController@manageTourRoutes')->name('manage-tour-routes');
    Route::post('tour-routes-request', 'TourController@tourRoutesrequest')->name('tour-routes-request');

    Route::get('tour-booking-data', 'TourController@tourBooking')->name('tour-booking-data');
    Route::post('tour-booking-data', 'TourController@tourBooking')->name('tour-booking-data');
    // orders
    Route::get('tour-orders', 'UserController@tourOrders')->name('tour-orders');

    Route::get('tour-booking-report', 'TourController@tourBookingReport')->name('tour-booking-report');

    // Shift tour
    Route::get('tour-cancel-options/{id}/{orderId}', 'TourController@cancelOptions')->name('tour-cancel-options');
    Route::post('tour-cancel-oprsn', 'TourController@tourCancelOprsn')->name('tour-cancel-oprsn');
});

// Vendor Access Tour & Package
Route::group(['middleware' => ['auth', 'vendor', 'tour', 'XssSanitizer']], function() {
    Route::get('sight-seen-pricing', 'TourController@sightSeenPricing')->name('sight-seen-pricing');
    Route::post('get-sight-seen-pricing', 'TourController@getSightSeenPricing')->name('get-sight-seen-pricing');
    Route::get('add-sight-seen-pricing', 'TourController@addSightSeenPricing')->name('add-sight-seen-pricing');
    Route::post('add-sight-seen-pricing-request', 'TourController@addSightSeenPricingRequest')->name('add-sight-seen-pricing-request');

    Route::get('tour-block-data', 'TourController@tourBlockData')->name('tour-block-data');
    Route::post('get-tour-block-data', 'TourController@getTourBlockData')->name('get-tour-block-data');
    Route::get('block-tour', 'TourController@blockTour')->name('block-tour');
    Route::post('block-tour-request', 'TourController@blockTourRequest')->name('block-tour-request');

    Route::get('tour-offline-order', 'TourController@tourOfflineOrder')->name('tour-offline-order');
    Route::post('create-tour-order', 'TourController@createTourOrder')->name('create-tour-order');
});

// Tickets
Route::group(['middleware' => ['auth', 'ticket']], function() {

    Route::get('all-tickets', 'TicketingController@allTickets')->name('all-tickets');
    Route::post('get-all-tickets', 'TicketingController@getAllTickets')->name('get-all-tickets');
    Route::post('ticket-oprsn', 'TicketingController@ticketOprsn')->name('ticket-oprsn');
    Route::get('ticket-add', 'TicketingController@addTicket')->name('ticket-add');
    Route::post('ticket-add-request', 'TicketingController@ticketAddRequest')->name('ticket-add-request');
    Route::get('ticketing-edit/{id}', 'TicketingController@editTicketing')->name('ticketing-edit');
    Route::post('ticket-edit-request', 'TicketingController@ticketEditRequest')->name('ticket-edit-request');

    Route::get('ticket-booking-data', 'TicketingController@ticketBooking')->name('ticket-booking-data');
    Route::post('ticket-booking-data', 'TicketingController@ticketBooking')->name('ticket-booking-data');

    // orders
    Route::get('ticketing-orders', 'UserController@ticketingOrders')->name('ticketing-orders');
    Route::get('ticketing-qr/{id}', 'UserController@ticketingQr')->name('ticketing-qr');

    Route::get('ticket-booking-report', 'TicketingController@ticketBookingReport')->name('ticket-booking-report');
});

// Vendor Access Tickets
Route::group(['middleware' => ['auth', 'vendor', 'ticket', 'XssSanitizer']], function() {

    Route::get('ticket-block-data', 'TicketingController@ticketBlockData')->name('ticket-block-data');
    Route::post('get-ticket-block-data', 'TicketingController@getTicketBlockData')->name('get-ticket-block-data');
    Route::get('block-ticket', 'TicketingController@blockTicket')->name('block-ticket');
    Route::post('block-ticket-request', 'TicketingController@blockTicketRequest')->name('block-ticket-request');

    Route::get('ticket-offline-order', 'TicketingController@ticketOfflineOrder')->name('ticket-offline-order');
    Route::post('create-ticket-order', 'TicketingController@createTicketOrder')->name('create-ticket-order');

    Route::get('ticket-booking-request', 'TicketingController@ticketBookingRequest')->name('ticket-booking-request');
    Route::post('get-ticket-booking-request', 'TicketingController@getTicketBookingRequest')->name('get-ticket-booking-request');

    Route::get('customer-interest', 'TicketingController@customerInterest')->name('customer-interest');
    Route::post('get-customer-interest', 'TicketingController@getCustomerInterest')->name('get-customer-interest');

    Route::get('offline-ticket-order', 'TicketingController@offlineTicketOrder')->name('offline-ticket-order');
    Route::post('create-offline-ticket-order', 'TicketingController@createOfflineTicketOrder')->name('create-offline-ticket-order');
});

// Online Food Ordering
Route::group(['middleware' => ['auth', 'food']], function() {

    Route::get('available-slots', 'RestaurantController@availableSlots')->name('available-slots');
    Route::post('slot-oprsn', 'RestaurantController@slotOprsn')->name('slot-oprsn');
    Route::get('food-category', 'RestaurantController@foodCategory')->name('food-category');
    Route::post('get-food-category', 'RestaurantController@getFoodCategory')->name('get-food-category');
    Route::get('add-food-category', 'RestaurantController@addFoodCategory')->name('add-food-category');
    Route::post('add-category-request', 'RestaurantController@addCategoryRequest')->name('add-category-request');
    Route::get('edit-food-category/{id}', 'RestaurantController@editFoodCategory')->name('edit-food-category');
    Route::post('edit-food-category-request', 'RestaurantController@editFoodCategoryRequest')->name('edit-food-category-request');

    Route::get('food-items', 'RestaurantController@foodItems')->name('food-items');
    Route::post('get-food-items', 'RestaurantController@getFoodItems')->name('get-food-items');
    Route::post('food-oprsn', 'RestaurantController@foodOprsn')->name('food-oprsn');
    Route::get('add-food-item', 'RestaurantController@addFoodItem')->name('add-food-item');
    Route::post('add-food-item-request', 'RestaurantController@addFoodItemRequest')->name('add-food-item-request');
    Route::get('edit-food-item/{id}', 'RestaurantController@editFoodItem')->name('edit-food-item');
    Route::post('edit-food-item-request', 'RestaurantController@editFoodItemRequest')->name('edit-food-item-request');

    // orders
    Route::get('food-orders', 'UserController@foodOrders')->name('food-orders');
});

// Vendor Access
Route::group(['middleware' => ['auth', 'vendor']], function() {

    Route::get('vendor-profile', 'UserController@vendorProfile')->name('vendor-profile');
    Route::post('save-vendor-profile', 'UserController@saveVendorProfile')->name('save-vendor-profile');

    // Review
    Route::get('service-review', 'UserController@serviceReview')->name('service-review');
    Route::post('get-service-review', 'UserController@getServiceReview')->name('get-service-review');
    Route::post('review-oprsn', 'UserController@reviewOprsn')->name('review-oprsn');

    // coupon
    Route::get('coupons', 'UserController@couponList')->name('coupons');
    Route::post('get-coupon-details', 'UserController@getCouponDetails')->name('get-coupon-details');
    Route::get('add-coupon', 'UserController@addCoupon')->name('add-coupon');
    Route::post('coupon-add-request', 'UserController@couponAddRequest')->name('coupon-add-request');
    Route::get('coupon-edit/{id}', 'UserController@couponEdit')->name('coupon-edit');
    Route::post('coupon-edit-request', 'UserController@couponEditRequest')->name('coupon-edit-request');

    // refund policy
    Route::get('refund-policy', 'UserController@refundPolicy')->name('refund-policy');
    Route::post('get-refund-policy', 'UserController@getRefundPolicy')->name('get-refund-policy');
    Route::get('add-refund-policy', 'UserController@addRefundPolicy')->name('add-refund-policy');
    Route::post('policy-add-request', 'UserController@policyAddRequest')->name('policy-add-request');
    Route::get('refund-policy-edit/{id}', 'UserController@refundPolicyEdit')->name('refund-policy-edit');
    Route::post('policy-edit-request', 'UserController@policyEditRequest')->name('policy-edit-request');

    // gst details
    Route::get('gst-rules', 'UserController@gstRules')->name('gst-rules');
    Route::post('get-gst-details', 'UserController@getGstDetails')->name('get-gst-details');
    Route::get('add-gst-rule', 'UserController@addGstRule')->name('add-gst-rule');
    Route::post('gst-add-request', 'UserController@gstAddRequest')->name('gst-add-request');
    Route::get('gst-rules-edit/{id}', 'UserController@gstRulesEdit')->name('gst-rules-edit');
    Route::post('gst-edit-request', 'UserController@gstEditRequest')->name('gst-edit-request');

    // Refund History
    Route::get('refund-history', 'UserController@refundHistory')->name('refund-history');
    Route::post('get-refund-history', 'UserController@getRefundHistory')->name('get-refund-history');
    Route::post('refund-oprsn', 'UserController@refundOprsn')->name('refund-oprsn');

    // Failure Paymentd
    Route::get('failure-payments', 'UserController@failurePayments')->name('failure-payments');
    Route::post('get-failure-payments', 'UserController@getFailurePayments')->name('get-failure-payments');
});

// Admin access
Route::group(['middleware' => ['auth', 'admin']], function() {

    Route::get('gst-details', 'UserController@gstDetails');

    // Vendor details
    Route::get('vendor-details', 'UserController@vendorDetails')->name('vendor-details');
    Route::post('get-vendor-details', 'UserController@getVendorDetails')->name('get-vendor-details');
    Route::post('vendor-oprsn', 'UserController@vendorOprsn')->name('vendor-oprsn');
    Route::get('vendor-add', 'UserController@vendorAdd')->name('vendor-add');
    Route::post('vendor-add-request', 'UserController@vendorAddRequest')->name('vendor-add-request');
    Route::get('vendor-edit/{id}', 'UserController@vendorEdit')->name('vendor-edit');
    Route::post('vendor-edit-request', 'UserController@vendorEditRequest')->name('vendor-edit-request');

    // Vendor Request
    Route::get('vendor-requests', 'UserController@vendorRequests')->name('vendor-requests');
    Route::post('get-vendor-requests', 'UserController@getVendorRequests')->name('get-vendor-requests');

    // Hotel
    Route::get('hotel-attribute', 'HotelController@hotelAttribute')->name('hotel-attribute');
    Route::post('hotel-attribute-add-request', 'HotelController@hotelAttributeAddRequest')->name('hotel-attribute-add-request');
    Route::get('hotel-attribute-terms/{id}', 'HotelController@hotelAttributeTerm')->name('hotel-attribute-terms');
    Route::post('hotel-attribute-terms-add-request', 'HotelController@hotelAttributeTermAddRequest')->name('hotel-attribute-terms-add-request');

    // Car
    Route::get('car-attribute', 'CarBookingController@carAttribute')->name('car-attribute');
    Route::post('car-attribute-add-request', 'CarBookingController@carAttributeAddRequest')->name('car-attribute-add-request');
    Route::get('car-attribute-terms/{id}', 'CarBookingController@carAttributeTerm')->name('car-attribute-terms');
    Route::post('car-attribute-terms-add-request', 'CarBookingController@carAttributeTermAddRequest')->name('car-attribute-terms-add-request');

    // Tour & Package
    Route::get('tour-attribute', 'TourController@tourAttribute')->name('tour-attribute');
    Route::post('tour-attribute-add-request', 'TourController@tourAttributeAddRequest')->name('tour-attribute-add-request');
    Route::get('tour-attribute-terms/{id}', 'TourController@tourAttributeTerm')->name('tour-attribute-terms');
    Route::post('tour-attribute-terms-add-request', 'TourController@tourAttributeTermAddRequest')->name('tour-attribute-terms-add-request');

    // Tickets
    Route::get('ticket-attribute', 'TicketingController@ticketAttribute')->name('ticket-attribute');
    Route::post('ticket-attribute-add-request', 'TicketingController@ticketAttributeAddRequest')->name('ticket-attribute-add-request');
    Route::get('ticket-attribute-terms/{id}', 'TicketingController@ticketAttributeTerm')->name('ticket-attribute-terms');
    Route::post('ticket-attribute-terms-add-request', 'TicketingController@ticketAttributeTermAddRequest')->name('ticket-attribute-terms-add-request');

    // Online Food Ordering
    Route::get('add-available-slots', 'RestaurantController@addAvailableSlots')->name('add-available-slots');
    Route::post('add-slots-request', 'RestaurantController@addSlotsRequest')->name('add-slots-request');
    Route::get('edit-available-slots/{id}', 'RestaurantController@editAvailableSlots')->name('edit-available-slots');
    Route::post('edit-slots-request', 'RestaurantController@editSlotsRequest')->name('edit-slots-request');


    // Manage Review comments
    Route::get('static-review', 'UserController@staticreview')->name('static-review');
    Route::post('static-review-add-request', 'UserController@staticreviewAddRequest')->name('static-review-add-request');

    // CMS
    Route::get('manage-homepage', 'UserController@manageHomepage')->name('manage-homepage');
    Route::post('homepage-edit-request', 'UserController@homepageEditRequest')->name('homepage-edit-request');
    // Manage Pages
    Route::get('manage-pages', 'UserController@managePages')->name('manage-pages');
    Route::post('get-pages', 'UserController@getPages')->name('get-pages');
    Route::get('add-pages', 'UserController@addPages')->name('add-pages');
    Route::post('add-page-request', 'UserController@addPageRequest')->name('add-page-request');
    Route::get('edit-pages/{id}', 'UserController@editPages')->name('edit-pages');
    Route::post('edit-page-request', 'UserController@editPageRequest')->name('edit-page-request');
    // Manage Exclusives
    Route::get('manage-exclusives', 'UserController@manageExclusives')->name('manage-exclusives');
    Route::post('get-exclusives', 'UserController@getExclusives')->name('get-exclusives');
    Route::get('add-exclusives', 'UserController@addExclusives')->name('add-exclusives');
    Route::post('add-exclusives-request', 'UserController@addExclusivesRequest')->name('add-exclusives-request');
    Route::get('edit-exclusives/{id}', 'UserController@editExclusives')->name('edit-exclusives');
    Route::post('edit-exclusives-request', 'UserController@editExclusivesRequest')->name('edit-exclusives-request');

    // Most Popular
    Route::get('manage-most-popular', 'UserController@manageMostPopular')->name('manage-most-popular');
    Route::post('get-most-popular', 'UserController@getMostPopular')->name('get-most-popular');
    Route::get('add-most-popular', 'UserController@addMostPopular')->name('add-most-popular');
    Route::post('add-most-popular-request', 'UserController@addMostPopularRequest')->name('add-most-popular-request');
    Route::get('edit-most-popular/{id}', 'UserController@editMostPopular')->name('edit-most-popular');
    Route::post('edit-most-popular-request', 'UserController@editMostPopularRequest')->name('edit-most-popular-request');

    // HDFC Accounts
    Route::get('link-accounts', 'UserController@linkAccounts')->name('link-accounts');
    Route::post('get-accounts', 'UserController@getAccounts')->name('get-accounts');
    Route::post('account-oprsn', 'UserController@accountOprsn')->name('account-oprsn');
    Route::get('account-add', 'UserController@accountAdd')->name('account-add');
    Route::post('account-add-request', 'UserController@accountAddRequest')->name('account-add-request');
    Route::get('account-edit/{id}', 'UserController@accountEdit')->name('account-edit');
    Route::post('account-edit-request', 'UserController@accountEditRequest')->name('account-edit-request');
});

// MMT Integration
Route::group(['middleware' => ['auth', 'vendor', 'integration', 'XssSanitizer']], function() {

    Route::get('mmt-setting', 'IntegrationController@mmtSetting')->name('mmt-setting');
    Route::post('save-mmt-credentials', 'IntegrationController@saveMmtredentials')->name('save-mmt-credentials');
    Route::get('hotel-mapping', 'IntegrationController@hotelMapping')->name('hotel-mapping');
    Route::get('hotel-mapping/{id}', 'IntegrationController@hotelMapping')->name('hotel-mapping');
    Route::post('hotel-mapping-request', 'IntegrationController@hotelMappingRequest')->name('hotel-mapping-request');
    Route::post('mapping-oprsn', 'IntegrationController@mappingOprsn')->name('mapping-oprsn');
    Route::post('save-mapping-data', 'IntegrationController@saveMappingData')->name('save-mapping-data');
    Route::get('mmt-hotel-list', 'IntegrationController@mmtHotelList')->name('mmt-hotel-list');
    Route::post('get-mmt-hotel-list', 'IntegrationController@getMmtHotelList')->name('get-mmt-hotel-list');
    Route::get('availability-details/{id}', 'IntegrationController@availabilityDetails')->name('availability-details');
    Route::post('get-availability-details', 'IntegrationController@getavailabilityDetails')->name('get-availability-details');
    Route::get('manage-mmt-inventory', 'IntegrationController@manageMmtInventory')->name('manage-mmt-inventory');
    Route::post('get-mmt-inventory', 'IntegrationController@getMmtInventory')->name('get-mmt-inventory');
    Route::post('mmt-oprsn', 'IntegrationController@mmtOprsn')->name('mmt-oprsn');

    Route::get('hotel-mmt-report', 'IntegrationController@hotelMmtReport')->name('hotel-mmt-report');

    Route::get('blocked-mmt-inventory', 'IntegrationController@blockedMmtInventory')->name('blocked-mmt-inventory');
    Route::post('get-blocked-inventory', 'IntegrationController@getBlockedInventory')->name('get-blocked-inventory');
    Route::get('block-mmt-inventory', 'IntegrationController@blockMmtInventory')->name('block-mmt-inventory');
    Route::post('block-mmt-request', 'IntegrationController@blockMmtRequest')->name('block-mmt-request');

    Route::get('hotel-mapping-ctp', 'IntegrationController@hotelMappingCtp')->name('hotel-mapping-ctp');
    Route::get('hotel-mapping-ctp/{id}', 'IntegrationController@hotelMappingCtp')->name('hotel-mapping-ctp');
    Route::post('hotel-ctp-mapping-request', 'IntegrationController@hotelCtpMappingRequest')->name('hotel-ctp-mapping-request');
    Route::post('save-ctp-mapping-data', 'IntegrationController@saveCtpMappingData')->name('save-ctp-mapping-data');
    Route::get('ctp-hotel-list', 'IntegrationController@ctpHotelList')->name('ctp-hotel-list');
    Route::post('get-ctp-hotel-list', 'IntegrationController@getCtpHotelList')->name('get-ctp-hotel-list');

});

// Merchanidse
Route::group(['middleware' => ['auth', 'merchandise']], function() {

    Route::get('categories', 'MerchantController@categories')->name('categories');
    Route::get('add-edit-categories', 'MerchantController@addEditCategories')->name('add-edit-categories');
    Route::get('add-edit-categories/{id}', 'MerchantController@addEditCategories')->name('add-edit-categories');
    Route::get('get-subcategories/{id}', 'MerchantController@getSubcategories')->name('get-subcategories');
    Route::post('add-categories-request', 'MerchantController@addCategoriesRequest')->name('add-categories-request');
    Route::post('merchant-oprsn', 'MerchantController@merchantOprsn')->name('merchant-oprsn');
    Route::get('merchant-products', 'MerchantController@merchantProduct')->name('merchant-products');
    Route::post('get-merchant-products', 'MerchantController@getMerchantProduct')->name('get-merchant-products');
    Route::get('add-merchant-product', 'MerchantController@addMerchantProduct')->name('add-merchant-product');
    Route::post('product-add-request', 'MerchantController@ProductAddRequest')->name('product-add-request');
    Route::get('edit-merchant-product/{id}', 'MerchantController@editMerchantProduct')->name('edit-merchant-product');
    Route::post('product-edit-request', 'MerchantController@productEditRequest')->name('product-edit-request');

    // Orders
    Route::get('merchant-orders', 'UserController@merchantOrders')->name('merchant-orders');
    Route::post('get-merchant-orders', 'UserController@getMerchantOrders')->name('get-merchant-orders');
});

// Conference Module
Route::group(['middleware' => ['auth']], function() {

    // Hall Master
    Route::get('all-hall', 'HallBookingController@allHall')->name('all-hall');
    Route::post('get-hall-details', 'HallBookingController@getHallDetails')->name('get-hall-details');
    Route::post('hall-oprsn', 'HallBookingController@hallOprsn')->name('hall-oprsn');

    Route::get('manage-hall-rooms/{id}', 'HallBookingController@manageHallRooms')
        ->name('manage-hall-rooms');
    Route::post('add-hall-room-request', 'HallBookingController@addHallRooms')
        ->name('add-hall-room-request');

    Route::get('edit-property-hall/{id}', 'HallBookingController@editHallRoom')
        ->name('edit-property-hall');
    Route::post('update-hall-room', 'HallBookingController@updateHallRoom')
        ->name('update-hall-room');

    Route::get('check-availability', 'HallBookingController@propertyAvailability')
        ->name('check-availability');


    Route::get('block-property', 'HallBookingController@hallRoomBlockData')
    ->name('block-property');

    Route::post('get-hall-block-data', 'HallBookingController@getHallBlockData');

    Route::get('blocked-halls', 'HallBookingController@blockedHalls')
        ->name('blocked-halls');

    Route::post('get-blocked-halls', 'HallBookingController@getBlockedHalls')
        ->name('get-blocked-halls');

    Route::get('block-hall-room', 'HallBookingController@blockHallRoom')
        ->name('block-hall-room');

    Route::post('block-hall-room-request', 'HallBookingController@blockHallRoomRequest')
        ->name('block-hall-room-request');

    Route::post('export-hall-block-data', 'HallBookingController@exportHallBlockData')
    ->name('export-hall-block-data');

    // Hall Booking Report
     Route::get('hall-booking-report', 'HallBookingController@hallBookingReport')->name('hall-booking-report');

     // Hall Availablity Report
      Route::get('hall-availablity-report','HallBookingController@hallAvailablityReport')->name('hall-availablity-report');

    // Add New Hall
    Route::get('add-new-hall', 'HallBookingController@addNewHall')->name('add-new-hall');
    Route::get('hall-edit/{id}', 'HallBookingController@editHall')->name('hall-edit');
    Route::post('hall-edit-request', 'HallBookingController@hallEditRequest')->name('hall-edit-request');

    Route::post('add-new-hall', 'HallBookingController@addNewHallRequest')
    ->name('add-new-hall-request');

    // Hall Attribute Master
    Route::get('hall-attribute', 'ConferenceHallMasterController@hallAttributeTermAddRequest')
        ->name('hall-attribute');

    Route::post('hall-attribute-add-request', 'ConferenceHallMasterController@hallAttributeAddRequest')
        ->name('hall-attribute-add-request');

    // Hall Attribute Term Master
    Route::get('hall-attribute-terms/{id?}', 'ConferenceHallMasterController@hallAttributeTerm')
        ->name('hall-attribute-terms');

    Route::post('hall-attribute-term-add-request', 'ConferenceHallMasterController@hallAttributeTermSaveRequest')
        ->name('hall-attribute-term-add-request');

     Route::post('hall-attribute-oprsn', 'ConferenceHallMasterController@hallOprsn')->name('hall-attribute-oprsn');

     Route::get('offline-hall-order', 'HallBookingController@offlineHallOrder')->name('offline-hall-order');

     Route::post('create-offline-hall-order', 'HallBookingController@createOfflineHallOrder')->name('create-offline-hall-order');

});



use Illuminate\Support\Facades\Mail;

Route::get('/smtp-test', function () {

    try {
        Mail::raw('SMTP is working fine!', function ($message) {
            $message->to('your_email@gmail.com')
                    ->subject('SMTP Test Mail');
        });

        return "✅ SMTP is working. Mail sent successfully.";
    } catch (\Exception $e) {
        return "❌ SMTP failed: " . $e->getMessage();
    }

});

Route::get('/session-id', function () {
    return session()->getId();
});
