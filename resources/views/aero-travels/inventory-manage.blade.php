@extends('layouts.app')

@section('title', 'Check Availability')

@section('content')
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f8;
            color: #333;
        }
        .search-bar-panel {
            background: #ffffff;
            color: #333333;
            padding: 20px 15px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .search-bar-panel label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .search-bar-panel .form-control {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #333333;
            height: 42px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .search-bar-panel .form-control:focus {
            background-color: #ffffff;
            color: #333333;
            border-color: #0078bc;
            box-shadow: 0 0 0 3px rgba(0, 120, 188, 0.15);
        }
        .btn-search {
            background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
            color: #ffffff;
            height: 42px;
            margin-top: 25px;
            font-weight: 600;
            text-transform: uppercase;
            border: none;
            border-radius: 4px;
            width: 100%;
            transition: opacity 0.2s;
        }
        .btn-search:hover, .btn-search:focus {
            opacity: 0.9;
            color: #ffffff;
        }

        /* --- Main Content Layout --- */
        .main-container {
            margin-bottom: 40px;
        }

        /* --- Flight Listing (Using primary brand blue) --- */
        .panel-flights {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .panel-flights h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #0078bc;
            padding-bottom: 10px;
        }
        .flight-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .flight-card:hover {
            border-color: #0078bc;
            background-color: #f0f8ff;
        }
        .flight-card.active {
            border-color: #0078bc;
            background-color: #e6f4fc;
            box-shadow: 0 0 8px rgba(0, 120, 188, 0.2);
        }
        .flight-card.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background-color: #0078bc;
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
        }
        .flight-airline {
            font-weight: 700;
            font-size: 14px;
            color: #0078bc;
        }
        .flight-time {
            font-size: 13px;
            font-weight: 500;
            margin-top: 5px;
        }
        .flight-duration {
            font-size: 11px;
            color: #757575;
        }
        .flight-price {
            font-weight: 700;
            color: #0078bc;
            font-size: 16px;
            text-align: right;
        }

        /* --- Seat Selection Layout --- */
        .panel-seat-layout {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .panel-seat-layout h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #00beda;
            padding-bottom: 10px;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            margin-right: 15px;
        }
        .legend-box {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            margin-right: 6px;
        }
        .legend-available { border: 2px solid #80deea; background-color: #e0f7fa; }
        .legend-selected { background: linear-gradient(to right, #0078bc 1%, #00beda 100%); }
        .legend-booked { background-color: #e0e0e0; cursor: not-allowed; }

        /* The 9-Seater Compact Cabin */
        .plane-cabin {
            background-color: #fafafa;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            padding: 30px 20px;
            max-width: 320px;
            margin: 0 auto;
            position: relative;
        }
        .cabin-nose {
            text-align: center;
            font-size: 11px;
            color: #9e9e9e;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .seat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .row-label {
            font-size: 11px;
            font-weight: bold;
            color: #9e9e9e;
            width: 20px;
            text-align: center;
        }
        .seat {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
       
        .seat.available {
            background: #e0f7fa;
            border: 2px solid #80deea;
            color: #0078bc;
            cursor: pointer;
        }

        /* Online booked */
        .seat.online-booked {
            background: #0099cc;
            border: 2px solid #0099cc;
            color: #ffffff;
            cursor: not-allowed;
        }


        /* Offline reserved */
        .seat.offline-reserved {
            background: #d9d9d9;
            border: 2px solid #bfbfbf;
            color: #666666;
            cursor: not-allowed;
        }


        /* User-selected seat */
        .seat.selected {
            background: #0078bc;
            border: 2px solid #0078bc;
            color: #ffffff;
            cursor: pointer;
        }
        .seat.available:hover {
            background-color: #b2ebf2;
            transform: scale(1.05);
        }
        .seat.selected {
            background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
            border: none;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 120, 188, 0.4);
        }
        .seat.booked {
            background-color: #e0e0e0;
            border: 2px solid #bdbdbd;
            color: #9e9e9e;
            cursor: not-allowed;
        }
        .aisle-space {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Summary Panel styling */
        .booking-summary-box {
            margin-top: 25px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
        }
        .btn-book-now {
            background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: none;
            margin-top: 15px;
            transition: opacity 0.2s;
        }
        .btn-book-now:hover {
            opacity: 0.9;
            color: #ffffff;
        }
        .btn-book-now[disabled] {
            background: #b0bec5 !important;
            cursor: not-allowed;
            opacity: 1;
        }
        .cabin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .cabin-header h3 {
            flex: 1;
            margin-bottom: 0 !important;
        }

        @media (max-width: 767px) {
            .cabin-header {
                display: block;
            }

            .cabin-header h3 {
                margin-bottom: 15px !important;
            }
        }


        @media (max-width: 767px) {
            .search-bar-panel {
                border-radius: 0;
            }
            .btn-search {
                margin-top: 10px;
            }
            .panel-flights {
                margin-bottom: 20px;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-12">
                <div class="search-bar-panel">
                    <form id="searchForm" onsubmit="return false;">
                        @csrf
                        <div class="row">
                            <div class="col-sm-3 col-xs-12 form-group">
                                <label for="fromLoc">
                                    <i class="fa fa-plane-departure" style="color:#0078bc;"></i> From
                                </label>
                                <select id="fromLoc" name="from_airport_id" class="form-control">
                                    <option value=""> Select Departure Airport</option>
                                        @foreach($SelectedCity as $airport)
                                            <option value="{{ $airport->id }}" {{ old('from_city') == $airport->id ? 'selected' : '' }} > {{ $airport->city_name }}
                                            ({{ $airport->airport_code }})
                                            </option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3 col-xs-12 form-group">
                                <label for="toLoc">
                                    <i class="fa fa-plane-arrival" style="color:#00beda;"></i>To
                                </label>
                                <select id="toLoc" name="to_airport_id" class="form-control">
                                    <option value="">Select Destination Airport </option>
                                        @foreach($SelectedCity as $airport)
                                            <option value="{{ $airport->id }}" {{ old('to_city') == $airport->id ? 'selected' : '' }} >
                                                {{ $airport->city_name }}
                                                ({{ $airport->airport_code }})
                                            </option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3 col-xs-12 form-group">
                                <label for="departDate">
                                    <i class="fa fa-calendar" style="color:#0078bc;" ></i> Date
                                </label>
                                <input type="date" id="departDate" name="date" class="form-control" value="{{ date('Y-m-d') }}" >
                            </div>

                            <div class="col-sm-3 col-xs-12">
                                <button type="button" id="searchBtn" class="btn btn-search btn-block" >
                                    <i class="fa fa-search"></i>Search Flights
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row main-container">
            <div class="col-md-4 col-sm-5 col-xs-12">
                <div class="panel-flights">
                    <h3> Available Flights </h3>
                    <div id="flightsContainer">
                    </div>
                </div>
            </div>

            <div class="col-md-8 col-sm-7 col-xs-12">
                <div class="panel-seat-layout">
                    <div class="cabin-header">
                        <h3>
                            Cabin Seating - Flight
                            <span id="currentFlightNo">--</span>
                        </h3>
                    </div>

                    <div class="seat-legend">
                        <span class="legend-item">
                            <span class="legend-box legend-available"></span>
                            Available Online
                        </span>
                        <span class="legend-item">
                            <span class="legend-box legend-selected"></span>
                            Online Booked
                        </span>
                        <span class="legend-item">
                            <span class="legend-box legend-booked"></span>
                            Offline Reserved
                        </span>
                    </div>
                    <div id="inventorySummary" class="inventory-summary" style="display:none;">
                        <div class="row">
                            <div class="col-sm-4 col-xs-6">
                                <div class="summary-item">
                                    <strong> Online Booked: </strong>
                                    <span id="onlineBooked"> 0 </span>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xs-6">
                                <div class="summary-item">
                                    <strong> Online Available:</strong>
                                    <span id="onlineAvailable"> 0 </span>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xs-6">
                                <div class="summary-item">
                                    <strong> Offline Reserved: </strong>
                                    <span id="offlineBooked"> 0 </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="plane-cabin">
                        <div class="cabin-nose">
                            Front / Cockpit
                        </div>
                        <div id="seatMapContainer">
                            <div class="empty-state">
                                <i class="fa fa-chair"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function () {
        let currentScheduleId = null;
        let currentFlightNo = null;
        let inventoryData = null;
        let selectedSeats = [];

        const rowConfig = [
            {
                rowNum: 1,
                seats: ['A', 'C'],
                hasAisle: true
            },
            {
                rowNum: 2,
                seats: ['A', 'C'],
                hasAisle: true
            },
            {
                rowNum: 3,
                seats: ['A', 'C'],
                hasAisle: true
            },
            {
                rowNum: 4,
                seats: ['A', 'B', 'C'],
                hasAisle: false
            }
        ];


        $('#searchBtn').on('click', function () {
            const fromAirportId = $('#fromLoc').val();
            const toAirportId = $('#toLoc').val();
            const date = $('#departDate').val();

            if (!fromAirportId) {
                alert('Please select departure airport.');
                return;
            }

            if (!toAirportId) {
                alert('Please select destination airport.');
                return;
            }

            if (!date) {
                alert('Please select journey date.');
                return;
            }

            if(String(fromAirportId) === String(toAirportId)){
                alert('Origin and destination must be different.');
                return;
            }

            currentScheduleId = null;
            currentFlightNo = null;
            inventoryData = null;
            selectedSeats = [];

            $('#currentFlightNo').text('--');
            $('#inventorySummary').hide();
            $('#inventoryNote').hide();
            $('#seatMapContainer').html(`
                <div class="empty-state">
                    <i class="fa fa-chair"></i>
                    <div>
                        Select a flight to view seat inventory.
                    </div>
                </div>
            `);

            const $button = $('#searchBtn');
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Searching...');
            $.ajax({
                url: "{{ route('inventory-search-flights') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    from_airport_id: fromAirportId,
                    to_airport_id: toAirportId,
                    date: date
                },
                success: function (response) {
                    if ( response.success && response.data && response.data.length > 0) {
                        renderFlights(
                            response.data
                        );
                    }else {
                        showNoFlights(
                            response.message || 'No flights found for the selected route and date.'
                        );
                    }
                },
                error: function (xhr) {
                    let message ='Unable to fetch flights.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values( xhr.responseJSON.errors) .flat() .join('<br>');
                    }else if ( xhr.responseJSON && xhr.responseJSON.message) {
                        message =xhr.responseJSON.message;
                    }
                    $('#flightsContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i>
                            ${escapeHtml(message)}
                        </div>
                    `);
                },
                complete: function () {
                    $button
                        .prop('disabled', false)
                        .html('<i class="fa fa-search"></i> Search Flights');
                }
            });
        });

        function renderFlights(flights){
            const $container = $('#flightsContainer');
            $container.empty();
            flights.forEach(function (flight, index) {
                const departureTime =formatTime(flight.departure_time);
                const arrivalTime = formatTime(flight.arrival_time);
                const totalAvailable = parseInt(flight.total_available || 0,10);
                const onlineAvailable =parseInt(flight.online_available || 0,10);
                const offlineAvailable = parseInt(flight.offline_available || 0,10);

                let availabilityClass ='availability-good';
                if (totalAvailable <= 0) {
                    availabilityClass = 'availability-none';
                }
                else if (totalAvailable <= 2) {
                    availabilityClass ='availability-low';
                }
                const activeClass = index === 0 ? 'active' : '';
                const html = `
                    <div
                        class="flight-card ${activeClass}"
                        data-schedule-id="${escapeHtml(
                            flight.schedule_id
                        )}"
                        data-flight-id="${escapeHtml(
                            flight.flight_id
                        )}"
                        data-flight-no="${escapeHtml(
                            flight.flight_number || ''
                        )}">
                        <div class="row">
                            <div class="col-xs-8">
                                <div class="flight-airline">
                                    <i class="fa fa-plane"></i>
                                    ${escapeHtml(
                                        flight.operator_name || flight.airline_code || 'Airline'
                                    )}
                                </div>
                                <div class="flight-time">
                                    ${escapeHtml(departureTime )}
                                    &rarr;
                                    ${escapeHtml( arrivalTime )}
                                </div>

                                <div class="flight-duration">
                                    ${escapeHtml( flight.aircraft_type || '')}
                                </div>
                                <div class="flight-number" style="margin-top:4px;">
                                    Flight:<strong>
                                        ${escapeHtml(
                                            flight.flight_number || ''
                                        )}
                                    </strong>
                                </div>
                                <div class="flight-availability ${availabilityClass}">
                                    Available:
                                    <strong>
                                        ${totalAvailable}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>`;
                $container.append(html);
            });


            /*
            |--------------------------------------------------------------------------
            | AUTO SELECT FIRST FLIGHT
            |--------------------------------------------------------------------------
            */

            if (flights.length > 0) {

                selectFlight(
                    flights[0]
                );

            }

        }

        $(document).on('click','.flight-card',function (){
            $('.flight-card').removeClass('active');
            $(this).addClass('active');
            currentScheduleId = $(this).attr('data-schedule-id');
            currentFlightNo = $(this).attr('data-flight-no');
            selectedSeats = [];
                $('#currentFlightNo')
                    .text(
                        currentFlightNo || '--'
                    );
                loadSeatInventory(
                    currentScheduleId,
                    $('#departDate').val()
                );

            }
        );

        function selectFlight(flight) {
            if (!flight) {
                return;
            }

            currentScheduleId = flight.schedule_id;
            currentFlightNo = flight.flight_number;

            selectedSeats = [];

            $('#currentFlightNo').text(
                currentFlightNo || '--'
            );

            loadSeatInventory(
                currentScheduleId,
                $('#departDate').val()
            );
        }

        function loadSeatInventory(scheduleId,date){
            if (!scheduleId) {
                return;
            }
            if(!date) {
                return;
            }
            $('#inventorySummary') .hide();
            $('#inventoryNote').hide();
            $.ajax({
                url: "{{ route('inventory-seat') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    schedule_id: scheduleId,
                    date: date
                },
                success: function (response) {
                    if ( response.success && response.data) {
                        inventoryData = response.data;
                        updateInventorySummary(inventoryData);
                        generateSeatMap();
                        $('#inventoryNote').show();
                    }
                    else {
                        inventoryData = null;
                        $('#inventorySummary') .hide();
                        $('#inventoryNote').hide();
                        $('#seatMapContainer').html(`
                            <div class="empty-state">
                                <i class="fa fa-chair"></i>
                                <div>
                                    Inventory not found
                                </div>
                                <small>
                                    No seat inventory exists
                                    for this flight on
                                    ${escapeHtml(date)}.
                                </small>
                            </div>
                        `);
                    }
                },
                error: function (xhr) {
                    let message = 'Unable to load seat inventory.';
                    if ( xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values( xhr.responseJSON.errors)
                            .flat()
                            .join('<br>');
                    }
                    else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    $('#seatMapContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i>
                            ${escapeHtml(message)}
                        </div>
                    `);
                }
            });
        }

        function updateInventorySummary(inventory){
            const onlineCapacity = parseInt(inventory.online_capacity || 0,10);
            const offlineCapacity = parseInt( inventory.offline_capacity || 0, 10);
            const onlineBooked = parseInt(inventory.online_booked || 0,10);

            const offlineReserved = offlineCapacity;
            const onlineAvailable = Math.max(0,onlineCapacity - onlineBooked);
            const offlineAvailable =0;

            $('#onlineCapacity').text(onlineCapacity);
            $('#onlineBooked').text(onlineBooked);
            $('#onlineAvailable').text(onlineAvailable);
            $('#offlineCapacity').text(offlineCapacity);
            $('#offlineBooked').text(offlineReserved);
            $('#offlineAvailable').text(offlineAvailable);

            $('#inventorySummary').show();
        }

        function generateSeatMap(){
            const $container = $('#seatMapContainer');
            $container.empty();
            if (!inventoryData) {
                return;
            }

            const onlineBooked = parseInt(inventoryData.online_booked || 0, 10);
            const offlineCapacity = parseInt(inventoryData.offline_capacity || 0,10);
            const allSeats = [];
            rowConfig.forEach(function (row) {
                row.seats.forEach(function (letter) {
                    allSeats.push(
                        `${row.rowNum}${letter}`
                    );
                });
            });
            const offlineReservedSeats = allSeats.slice(0,offlineCapacity);
            const onlineBookedSeats = allSeats.slice(offlineCapacity,offlineCapacity + onlineBooked);
            rowConfig.forEach(function (row) {
                const $row = $('<div class="seat-row"></div>');
                $row.append(`
                    <span class="row-label">
                        ${row.rowNum}
                    </span>
                `);
                if (row.hasAisle) {
                    appendSeat($row,`${row.rowNum}A`,offlineReservedSeats,onlineBookedSeats);
                    $row.append(`
                        <span class="aisle-space">
                            <i class="fa fa-long-arrow-down" style="font-size:10px;"></i>
                        </span>
                    `);
                    appendSeat(
                        $row,
                        `${row.rowNum}C`,
                        offlineReservedSeats,
                        onlineBookedSeats
                    );
                }
                else {
                    row.seats.forEach(function (letter) {
                        appendSeat(
                            $row,
                            `${row.rowNum}${letter}`,
                            offlineReservedSeats,
                            onlineBookedSeats
                        );
                    });
                }
                $row.append(`
                    <span class="row-label">
                        ${row.rowNum}
                    </span>
                `);
                $container.append($row);
            });
        }
        function appendSeat($row, seatId,offlineReservedSeats, onlineBookedSeats){
            const isOfflineReserved = offlineReservedSeats.includes(seatId);
            const isOnlineBooked = onlineBookedSeats.includes(seatId);
            const isSelected = selectedSeats.includes(seatId);
            let seatClass ='available';
            let seatTitle = `Seat ${seatId} - Available Online`;

            if (isOfflineReserved) {
                seatClass = 'offline-reserved';
                seatTitle =`Seat ${seatId} - Offline Reserved`;
            }
            else if (isOnlineBooked) {
                seatClass ='online-booked';
                seatTitle = `Seat ${seatId} - Online Booked`;
            }
            else if (isSelected) {
                seatClass = 'selected';
                seatTitle = `Seat ${seatId} - Selected`;
            }

            const seatLetter = seatId.substring(seatId.length - 1 );
            $row.append(`
                <div class="seat ${seatClass}" data-seat-id="${escapeHtml( seatId)}"
                    title="${escapeHtml( seatTitle )}">
                    ${escapeHtml(
                        seatLetter
                    )}
                </div>
            `);
        }
        $(document).on('click','.seat.available',
            function(){
                const seatId = $(this) .attr('data-seat-id');
                if(!selectedSeats.includes(seatId)) {
                    selectedSeats.push(
                        seatId
                    );
                }
                $(this)
                    .removeClass('available')
                    .addClass('selected');
                $(this)
                    .attr(
                        'title',
                        `Seat ${seatId} - Selected`
                    );
            }
        );

        $(document).on('click','.seat.selected',
            function(){
                const seatId = $(this).attr('data-seat-id');
                selectedSeats = selectedSeats.filter(
                        function (seat) {
                            return seat !== seatId;
                        }
                    );
                $(this)
                    .removeClass('selected')
                    .addClass('available');
                $(this)
                    .attr(
                        'title',
                        `Seat ${seatId} - Available Online`
                    );
            }
        );

        $(document).on('click','.seat.online-booked, .seat.offline-reserved',
            function(){
                const seatId = $(this).attr('data-seat-id');
            }
        );
        
        function showNoFlights(message) {
            $('#flightsContainer').html(`
                <div class="empty-state">
                    <i class="fa fa-plane"></i>
                    <div>
                        No flights available
                    </div>
                </div>
            `);

            $('#currentFlightNo').text('--');

            $('#inventorySummary').hide();

            $('#inventoryNote').hide();

            $('#seatMapContainer').html(`
                <div class="empty-state">
                    <i class="fa fa-chair"></i>
                    <div>
                        No flight selected
                    </div>
                </div>
            `);

            currentScheduleId = null;
            currentFlightNo = null;
            inventoryData = null;
            selectedSeats = [];
        }

        /*
        |--------------------------------------------------------------------------
        | FORMAT TIME
        |--------------------------------------------------------------------------
        */
        function formatTime(time){
            if (!time) {
                return '';
            }
            const parts =time.split(':');
            let hours = parseInt( parts[0], 10);
            const minutes = parts[1] || '00';
            const ampm = hours >= 12 ? 'PM': 'AM';
            hours = hours % 12;
            if (hours === 0) {
                hours = 12;
            }
            return ( hours + ':' + minutes +' ' + ampm);
        }

        function escapeHtml(value){
            return $('<div>')
                .text(
                    value === null ||
                    value === undefined
                        ? ''
                        : value
                )
                .html();
        }
    });
</script>
@endsection
