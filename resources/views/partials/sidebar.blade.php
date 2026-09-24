@php
$services = (!is_null(Auth::user()->services)) ? json_decode(Auth::user()->services) : [];
$vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;

@endphp
<aside class="sidebar">
    <div class="scroll-sidebar">
        <nav class="sidebar-nav">
            <ul id="side-menu">
                <li style="margin-top: 5px;">
                    <a class="active waves-effect" href="{{ url('dashboard') }}" aria-expanded="false"><i class="icon-screen-desktop fa-fw"></i> <span class="hide-menu"> Dashboard </a>
                </li>
                @if (Auth::user()->access_type != 'admin' && Auth::user()->access_type != 'flight')
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="icon-settings fa-fw"></i> <span class="hide-menu"> Setting <i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        @if (Auth::user()->access_type == 'superadmin' || Auth::user()->id == 1)
                        <li> <a href="{{ url('user-details') }}">Customer Details</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('vendor-details') }}">Vendor Details</a> </li>
                        <li> <a href="{{ url('vendor-requests') }}">Vendor Requests</a> </li>
                        <li> <a href="{{ url('link-accounts') }}">Link Acounts</a> </li>
                        @endif
                        @if (Auth::user()->role != 3)
                        <li> <a href="{{ url('subuser-details') }}">Sub-user Details</a> </li>
                        @endif
                        <li> <a href="{{ url('agent-details') }}">Agent Details</a> </li>
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('gst-details') }}">GST Details</a> </li>
                        <li> <a href="{{ url('static-review') }}">Manage Review</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'vendor')
                        <li> <a href="{{ url('gst-rules') }}">GST Details</a> </li>
                        <li> <a href="{{ url('coupons') }}">Manage Coupons</a> </li>
                        @endif
                    </ul>
                </li>
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="icon-clock fa-fw"></i> <span class="hide-menu">Orders<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        @if (Auth::user()->access_type == 'superadmin' || in_array('hotel', $services))
                            <li>
                                <a href="{{ url('hotel-orders') }}">Hotel Orders</a>
                            </li>
                            @if (Auth::user()->access_type == 'vendor' && in_array('hotel', $services))
                            <li>
                                <a href="{{ url('offline-order') }}">Hotel Offline Order</a>
                            </li>
                            <li>
                                <a href="{{ url('hotel-estimates') }}">Hotel Estimate</a>
                            </li>
                        @endif
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('hall', $services))
                            <li>
                                <a href="{{ url('hall-orders') }}">Hall Orders</a>
                            </li>
                            <li>
                                <a href="{{ url('offline-hall-order') }}">Hall Offline Order</a>
                            </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('rental', $services))
                        <li> <a href="{{ url('rental-orders') }}">Rental Orders</a> </li>
                        @if (Auth::user()->access_type == 'vendor' && in_array('rental', $services))
                        <li> <a href="{{ url('rental-offline-order') }}">Rental Offline Order</a> </li>
                        @endif
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('tour', $services))
                        <li> <a href="{{ url('tour-orders') }}">Tour Orders</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'vendor' && in_array('tour', $services))
                        <li> <a href="{{ url('tour-offline-order') }}">Tour offline Order</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('ticketing', $services))
                        <li> <a href="{{ url('ticketing-orders') }}">Ticketing Orders</a> </li>
                        @if ($vendor_id == '270' || $vendor_id == '6045')
                        <li> <a href="{{ url('ticket-booking-request') }}">Booking Requests</a> </li>
                        <li> <a href="{{ url('customer-interest') }}">Birds Walk Registrations</a> </li>
                        @endif
                        @if ($vendor_id == '268' || $vendor_id == '6048')
                        <li> <a href="{{ url('ticket-offline-order') }}">Ticket Offline Order</a> </li>
                        @endif
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('food-ordering', $services))
                        <li> <a href="{{ url('food-orders') }}">Food Orders</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('merchandise', $services))
                        <li> <a href="{{ url('merchant-orders') }}">Merchant Orders</a> </li>
                        @endif
                    </ul>
                </li>
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-bar-chart fa-fw"></i> <span class="hide-menu">MIS Report<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        @if (Auth::user()->access_type == 'superadmin' || in_array('hotel', $services))
                        <li> <a href="{{ url('hotel-booking-report') }}">Hotel Booking Report</a> </li>
                        <li> <a href="{{ url('hotel-roomstay-report') }}">Hotel Stay Report</a> </li>
                        <li> <a href="{{ url('hotelroom-mis-report') }}">Hotel Room Report</a> </li>
                        <li> <a href="{{ url('hotel-booking-count-report') }}">Hotel Book Status Report</a> </li>
                        <li> <a href="{{ url('hotel-mis-report') }}">Daywise Hotel Book Report</a> </li>
                        <li> <a href="{{ url('hotel-room-night-report') }}">Hotel Room Night Report</a> </li>
                        <li> <a href="{{ url('hotel-pax-report') }}">Hotel PAX Report</a> </li>
                        <li> <a href="{{ url('hotel-availablity-report') }}">Hotel Availability Report</a> </li>
                        <li> <a href="{{ url('hotel-cancel-report') }}">Hotel Cancel Report</a> </li>
                        <li> <a href="{{ url('agent-hotel-report') }}">Agent Hotel Report</a> </li>
                        <li> <a href="{{ url('hotel-mmt-report') }}">MMT Boooking Report</a> </li>
                        <li> <a href="{{ url('hotel-gst-report') }}">Hotel GST Report</a> </li>
                        <li> <a href="{{ url('inventory-comparison-report') }}">Inventory Comparison Report</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('rental', $services))
                        <li> <a href="{{ url('rental-mis-report') }}">Rental Report</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('tour', $services))
                        <li> <a href="{{ url('tour-booking-report') }}">Tour Booking Report</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('ticketing', $services))
                        <li> <a href="{{ url('ticket-booking-report') }}">Ticket Booking Report</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('conference', $services))
                        <li> <a href="{{ url('hall-booking-report') }}">Hall Booking Report</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'superadmin' || in_array('conference', $services))
                        <li> <a href="{{ url('hall-availablity-report') }}">Hall Availability Report</a> </li>
                        @endif
                        @if (in_array(Auth::user()->access_type, ['admin', 'superadmin']) || in_array('caravn', $services))
                        <li> <a href="{{ url('caravan-booking-report') }}">Caravan Booking Report</a> </li>
                        @endif
                        @if (in_array(Auth::user()->access_type, ['admin', 'superadmin']) || in_array('caravn', $services))
                        <li> <a href="{{ url('caravan-availability-report') }}">Caravan Availability Report</a> </li>
                        @endif
                    </ul>
                </li>
                @if (Auth::user()->access_type == 'vendor')
                <li>
                    <a class="waves-effect" href="{{ url('refund-history') }}" aria-expanded="false"><i class="fa fa-exchange fa-fw"></i> <span class="hide-menu">Refund History</span></a>
                </li>
                <li>
                    <a class="waves-effect" href="{{ url('failure-payments') }}" aria-expanded="false"><i class="fa fa-warning fa-fw"></i> <span class="hide-menu">Failure Payments</span></a>
                </li>
                @endif
                @if (Auth::user()->access_type == 'superadmin' || in_array('hotel', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-building-o fa-fw"></i> <span class="hide-menu">Hotel<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('all-hotels') }}">All Hotels</a> </li>
                        <li> <a href="{{ url('hotel-add') }}">Add New Hotel</a> </li>
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('hotel-attribute') }}">Attribute</a> </li>
                        @endif
                        <li> <a href="{{ url('room-attribute') }}">Room Attribute</a> </li>
                        <li> <a href="{{ url('hotel-availability') }}">Availability</a> </li>
                        @if (Auth::user()->access_type == 'vendor')
                        <li> <a href="{{ url('hotel-room-pricing') }}">Hotel Pricing</a> </li>
                        <li> <a href="{{ url('hotel-room-block-data') }}">Block Hotel / Room</a> </li>
                        <li> <a href="{{ url('hotel-sales') }}">Sales Start / Stop</a></li>
                        <li> <a href="{{ url('hotel-inventory') }}">View Inventory</a></li>
                        <li> <a href="{{ url('hotel-inventory-summary') }}">Inventory Summary</a></li>
                        <li> <a href="{{ url('manage-hotel-inventory') }}">Manage Inventory</a></li>
                        @endif
                    </ul>
                </li>
                @endif
                @if (Auth::user()->access_type == 'superadmin' || in_array('rental', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-car fa-fw"></i> <span class="hide-menu">Car<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('all-cars') }}">All Cars</a> </li>
                        <li> <a href="{{ url('car-add') }}">Add New Car</a> </li>
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('car-attribute') }}">Attribute</a> </li>
                        @endif
                        <li> <a href="{{ url('rental-availability') }}">Availability</a> </li>
                        @if (Auth::user()->access_type == 'vendor')
                        <li> <a href="{{ url('rental-block-data') }}">Block vehicle</a> </li>
                        <li> <a href="{{ url('rental-inventory') }}">View Inventory</a></li>
                        <li> <a href="{{ url('manage-rental-inventory') }}">Manage Inventory</a></li>
                        @endif
                    </ul>
                </li>
                @endif
                @if (Auth::user()->access_type == 'superadmin' || in_array('tour', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-umbrella fa-fw"></i> <span class="hide-menu">Tour<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('all-tours') }}">All Tours</a> </li>
                        <li> <a href="{{ url('tour-add') }}">Add New Tour</a> </li>
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('tour-attribute') }}">Attribute</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'vendor')
                        <li> <a href="{{ url('sight-seen-pricing') }}">Sight Seen Pricing</a> </li>
                        <li> <a href="{{ url('tour-block-data') }}">Block Tour</a> </li>
                        @endif
                        <li> <a href="{{ url('tour-booking-data') }}">Booking Summary</a> </li>
                    </ul>
                </li>
                @endif
                @if (Auth::user()->access_type == 'superadmin' || in_array('ticketing', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-ticket fa-fw"></i> <span class="hide-menu">Tickets / Events<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('all-tickets') }}">All Tickets / Events</a> </li>
                        <li> <a href="{{ url('ticket-add') }}">Add New Ticket / Event</a> </li>
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('ticket-attribute') }}">Attribute</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'vendor')
                        <li> <a href="{{ url('ticket-block-data') }}">Block Ticket / Event</a> </li>
                        @endif
                        <li> <a href="{{ url('ticket-booking-data') }}">Booking Summary</a> </li>
                    </ul>
                </li>
                @endif
                @if (Auth::user()->access_type == 'superadmin' || in_array('food-ordering', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-cutlery fa-fw"></i> <span class="hide-menu">Food Ordering<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('available-slots') }}">Available Slots</a> </li>
                        <li> <a href="{{ url('food-category') }}">Food Category</a> </li>
                        <li> <a href="{{ url('food-items') }}">Food Items</a> </li>
                    </ul>
                </li>
                @endif

                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-edit fa-fw"></i> <span class="hide-menu">CMS<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('manage-homepage') }}">Home Page</a> </li>
                        <li> <a href="{{ url('manage-most-popular') }}">Most Popular</a> </li>
                        <li> <a href="{{ url('manage-pages') }}">Pages</a> </li>
                        <li> <a href="{{ url('manage-exclusives') }}">Manage Exclusives</a> </li>
                        @endif
                        <li> <a href="{{ url('manage-slider') }}">Manage Ads</a> </li>
                    </ul>
                </li>
                @if (Auth::user()->role != 3 && Auth::user()->access_type != 'flight')
                <li>
                    <a class="waves-effect" href="{{ url('send-mail') }}" aria-expanded="false"><i class="fa fa-envelope fa-fw"></i> <span class="hide-menu">Send E-mail</span></a>
                </li>
                @endif
                @if (Auth::user()->access_type == 'vendor' && Auth::user()->access_type != 'flight')
                <li>
                    <a class="waves-effect" href="{{ url('service-review') }}" aria-expanded="false"><i class="fa fa-comments fa-fw"></i> <span class="hide-menu">Customer Reviews</span></a>
                </li>
                <li>
                    <a class="waves-effect" href="{{ url('refund-policy') }}" aria-expanded="false"><i class="fa fa-lock fa-fw"></i> <span class="hide-menu">Refund Policy</span></a>
                </li>
                @endif
                @if (Auth::user()->access_type == 'vendor' && Auth::user()->access_type != 'flight' && in_array('mmt-integration', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-building fa-fw"></i> <span class="hide-menu">OTA Integration<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <!-- <li> <a href="{{ url('mmt-setting') }}">MMT Settings</a></li> -->
                        <li> <a href="{{ url('mmt-hotel-list') }}">Hotel List MMT</a></li>
                        <li> <a href="{{ url('hotel-mapping') }}">Map Hotels MMT</a></li>
                        <!-- <li> <a href="{{ url('ctp-hotel-list') }}">Hotel List Cleartrip</a></li>
                        <li> <a href="{{ url('hotel-mapping-ctp') }}">Map Hotels Cleartrip</a></li> -->
                        <li> <a href="{{ url('blocked-mmt-inventory') }}">Block Inventory</a></li>
                    </ul>
                </li>
                <!-- <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-building fa-fw"></i> <span class="hide-menu">Cleartrip Integration<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('ctp-hotel-list') }}">Hotel List</a></li>
                        <li> <a href="{{ url('hotel-mapping-ctp') }}">Map Hotels</a></li>
                        <li> <a href="{{ url('blocked-ctp-inventory') }}">Block Cleartrip Inventory</a></li>
                    </ul>
                </li> -->
                @endif
                @if (Auth::user()->access_type == 'superadmin' || in_array('merchandise', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-industry fa-fw"></i> <span class="hide-menu">Merchandise<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('categories') }}">Categories</a></li>
                        <li> <a href="{{ url('merchant-products') }}">Products</a></li>
                    </ul>
                </li>
                @endif
                {{-- @if (Auth::user()->access_type == 'superadmin' || in_array('caravn', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-bus fa-fw"></i> <span class="hide-menu">Caravan<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ route('all-caravan') }}">All Caravan</a> </li>
                        <li> <a href="{{ route('caravan-add') }}">Add New Caravan</a> </li>
                        @if (Auth::user()->access_type == 'superadmin')
                        <li> <a href="{{ url('caravan-attribute') }}">Attribute</a> </li>
                        @endif
                        <li> <a href="{{ url('caravan-availability') }}">Availability</a> </li>
                        @if (Auth::user()->access_type == 'vendor')
                        <li> <a href="{{ url('caravan-block-data') }}">Block Caravan</a> </li>
                        <li> <a href="{{ route('caravan-inventory') }}">View Inventory</a></li>
                        <li> <a href="{{ route('manage-caravan-inventory') }}">Manage Inventory</a></li>
                        @endif
                    </ul>
                </li>
                @endif --}}
                @endif
                @if (in_array(Auth::user()->access_type, ['admin', 'superadmin']) || in_array('caravn', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-bus fa-fw"></i> <span class="hide-menu">Caravan<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ route('all-caravan') }}">All Caravan</a> </li>
                        <li> <a href="{{ route('caravan-add') }}">Add New Caravan</a> </li>

                        <li> <a href="{{ url('caravan-attribute') }}">Attribute</a> </li>

                        <li> <a href="{{ url('caravan-availability') }}">Availability</a> </li>

                        <li> <a href="{{ url('caravan-block-data') }}">Block Caravan</a> </li>
                        <li> <a href="{{ route('caravan-inventory') }}">View Inventory</a></li>
                        <li> <a href="{{ route('manage-caravan-inventory') }}">Manage Inventory</a></li>

                    </ul>
                </li>
                @endif

                @if (Auth::user()->access_type == 'superadmin' || in_array('conference', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-users fa-fw"></i>     <span class="hide-menu">Conference<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li> <a href="{{ url('all-hall') }}">All Properties</a> </li>
                        <li> <a href="{{ url('add-new-hall') }}">Add New Property</a> </li>
                        <li> <a href="{{ url('hall-attribute') }}">Hall Attribute</a> </li>
                        <li> <a href="{{ url('block-property') }}">Block Hall</a> </li>
                        <li> <a href="{{ url('check-availability') }}">Availability</a> </li>


                    </ul>
                </li>
                @endif

                @if (Auth::user()->access_type == 'superadmin' || in_array('flight', $services))
                <li>
                    <a class="waves-effect" href="javascript:void(0);" aria-expanded="false"><i class="fa fa-plane fa-fw"></i>     <span class="hide-menu">Air Travels<i class="pull-right fa fa-chevron-down"></i></span></a>
                    <ul aria-expanded="false" class="collapse">
                        @if (Auth::user()->access_type != 'flight' && in_array('flight', $services))
                        <li> <a href="{{ route('all-flight') }}">All Flights</a> </li>
                        <li> <a href="{{ route('add-new-flight') }}">Add New Flight</a> </li>
                        <li> <a href="{{ route('inventory-manage') }}">Availability</a> </li>
                        @endif
                        @if (Auth::user()->access_type == 'flight' && in_array('flight', $services))
                        <li> <a href="{{ route('inventory-manage') }}">Availability</a> </li>
                        <li> <a href="{{ route('inventory-manage-list') }}">Confirm Passenger List</a> </li>
                        <li> <a href="{{ route('seat-update') }}">Seat Update</a> </li>
                        @endif


                    </ul>
                </li>
                @endif
        </nav>
    </div>
</aside>
