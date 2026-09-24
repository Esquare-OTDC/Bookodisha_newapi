@extends('layouts.app')

@section('title', 'Seat Update')

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

    .btn-search:hover,
    .btn-search:focus {
        opacity: 0.9;
        color: #ffffff;
    }

    .main-container {
        margin-bottom: 40px;
    }

    .panel-flights {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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

    .flight-number {
        font-size: 12px;
        color: #555;
    }

    .flight-availability {
        font-size: 12px;
        margin-top: 5px;
    }

    .availability-good {
        color: #28a745;
    }

    .availability-low {
        color: #e67e22;
    }

    .availability-none {
        color: #dc3545;
    }

    .panel-seat-layout {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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

    .inventory-form {
        background: #f9fafb;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 25px;
    }

    .inventory-form label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 7px;
    }

    .inventory-form .form-control {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #333333;
        height: 42px;
        border-radius: 4px;
        transition: all 0.3s;
    }

    .inventory-form .form-control:focus {
        border-color: #0078bc;
        box-shadow: 0 0 0 3px rgba(0, 120, 188, 0.15);
    }

    .inventory-form .form-group {
        margin-bottom: 20px;
    }

    .btn-update-inventory {
        background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
        border: none;
        color: #ffffff;
        height: 42px;
        font-weight: 600;
        padding: 0 25px;
        border-radius: 4px;
        transition: opacity 0.2s;
    }

    .btn-update-inventory:hover,
    .btn-update-inventory:focus {
        opacity: 0.9;
        color: #ffffff;
    }

    .btn-update-inventory:disabled {
        background: #b0bec5 !important;
        cursor: not-allowed;
        opacity: 1;
    }
    .inventory-info {
        margin-top: 20px;
        padding: 12px 15px;
        background: #eaf7fc;
        border-left: 4px solid #0078bc;
        border-radius: 4px;
        color: #40505c;
    }

    .inventory-info strong {
        color: #2c3e50;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    .empty-state i {
        font-size: 30px;
        margin-bottom: 10px;
    }

    .inventory-message {
        margin-top: 15px;
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

        .cabin-header {
            display: block;
        }

        .cabin-header h3 {
            margin-bottom: 15px !important;
        }

        .inventory-form {
            padding: 15px;
        }

        .btn-update-inventory {
            width: 100%;
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
                                <i class="fa fa-plane-departure" style="color:#0078bc;"></i>
                                From
                            </label>

                            <select id="fromLoc" name="from_airport_id" class="form-control">
                                <option value="">
                                    Select Departure Airport
                                </option>
                                @foreach ($SelectedCity as $airport)
                                    <option
                                        value="{{ $airport->id }}"
                                        {{ old('from_city') == $airport->id ? 'selected' : '' }}>

                                        {{ $airport->city_name }}
                                        ({{ $airport->airport_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-3 col-xs-12 form-group">
                            <label for="toLoc">
                                <i class="fa fa-plane-arrival" style="color:#00beda;"></i>
                                To
                            </label>

                            <select id="toLoc" name="to_airport_id" class="form-control">
                                <option value="">
                                    Select Destination Airport
                                </option>
                                @foreach ($SelectedCity as $airport)
                                    <option
                                        value="{{ $airport->id }}"
                                        {{ old('to_city') == $airport->id ? 'selected' : '' }}>

                                        {{ $airport->city_name }}
                                        ({{ $airport->airport_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-3 col-xs-12 form-group">
                            <label for="departDate">
                                <i class="fa fa-calendar" style="color:#0078bc;"></i>
                                Date
                            </label>

                            <input type="date" id="departDate" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-sm-3 col-xs-12">
                            <button type="button" id="searchBtn" class="btn btn-search btn-block">
                                <i class="fa fa-search"></i>
                                Search Flights
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
                <h3>
                    Available Flights
                </h3>
                <div id="flightsContainer">
                    <div class="empty-state">
                        <i class="fa fa-plane"></i>
                        <div>
                            Search for a flight to continue.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-sm-7 col-xs-12">
            <div class="panel-seat-layout">
                <div class="cabin-header">
                    <h3>
                        Seat Inventory
                        <span id="currentFlightNo">
                            --
                        </span>
                    </h3>
                </div>
                <div id="inventoryFormContainer">
                    <div class="empty-state">
                        <i class="fa fa-chair"></i>
                        <div style="margin-top:10px;">
                            Select a flight to view
                            seat inventory.
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
    $(document).ready(function() {
        let currentScheduleId = null;
        let currentFlightNo = null;
        let inventoryData = null;


        /* ==========================================================
           SEARCH FLIGHTS
        ========================================================== */

        $('#searchBtn').on('click', function() {
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


            if (String(fromAirportId) === String(toAirportId)) {
                alert('Origin and destination must be different.');
                return;
            }

            currentScheduleId = null;
            currentFlightNo = null;
            inventoryData = null;


            $('#currentFlightNo').text('--');
            $('#inventoryFormContainer').html(`
                <div class="empty-state">
                    <i class="fa fa-chair"></i>
                    <div>
                        Select a flight to view seat inventory.
                    </div>
                </div>
            `);

            const $button = $('#searchBtn');


            $button
                .prop('disabled', true)
                .html(`
                    <i class="fa fa-spinner fa-spin"></i>
                    Searching...
                `);

            $.ajax({
                url: "{{ route('inventory-search-flights') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    from_airport_id: fromAirportId,
                    to_airport_id:toAirportId,
                    date: date
                },
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        renderFlights(
                            response.data
                        );
                    } else {

                        showNoFlights(response.message || 'No flights found for the selected route and date.');
                    }

                },


                error: function(xhr) {
                    let message = 'Unable to fetch flights.';
                    if ( xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = getFirstBackendError(xhr.responseJSON.errors);
                    }

                    else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }


                    $('#flightsContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i>
                            ${escapeHtml(message)}
                        </div>
                    `);
                },
                complete: function() {
                    $button
                        .prop('disabled', false)
                        .html(`
                            <i class="fa fa-search"></i>
                            Search Flights
                        `);

                }

            });

        });
        /* ==========================================================
           RENDER FLIGHTS
        ========================================================== */
        function renderFlights(flights) {
            const $container = $('#flightsContainer');
            $container.empty();
            flights.forEach(function(flight, index) {
                const departureTime = formatTime(flight.departure_time);
                const arrivalTime = formatTime(flight.arrival_time);
                const totalAvailable = parseInt(flight.total_available || 0,10);
                let availabilityClass = 'availability-good';

                if (totalAvailable <= 0) {
                    availabilityClass = 'availability-none';
                }

                else if (totalAvailable <= 2) {
                    availabilityClass = 'availability-low';
                }
                const activeClass = index === 0 ? 'active' : '';
                const html = `
                    <div class="flight-card ${activeClass}" data-schedule-id="${escapeHtml(flight.schedule_id)}" data-flight-id="${escapeHtml(flight.flight_id)}" data-flight-no="${escapeHtml(flight.flight_number || '')}">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="flight-airline">
                                    <i class="fa fa-plane"></i>
                                    ${escapeHtml(
                                        flight.operator_name ||
                                        flight.airline_code ||
                                        'Airline'
                                    )}
                                </div>


                                <div class="flight-time">
                                    ${escapeHtml(departureTime)}
                                    &rarr;
                                    ${escapeHtml(arrivalTime)}
                                </div>


                                <div class="flight-duration">
                                    ${escapeHtml(flight.aircraft_type || '')}
                                </div>


                                <div class="flight-number" style="margin-top:4px;">
                                    Flight:
                                    <strong>
                                        ${escapeHtml(flight.flight_number || '')}
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
                    </div>
                `;
                $container.append(html);
            });


            /* ------------------------------------------------------
               AUTO SELECT FIRST FLIGHT
            ------------------------------------------------------ */

            if (flights.length > 0) {
                $('.flight-card')
                    .first()
                    .trigger('click');
            }

        }


        /* ==========================================================
           FLIGHT CARD CLICK
        ========================================================== */

        $(document).on('click','.flight-card',
            function() {

                $('.flight-card') .removeClass('active');
                $(this) .addClass('active');

                currentScheduleId = $(this).attr('data-schedule-id');
                currentFlightNo =$(this).attr('data-flight-no');
                $('#currentFlightNo')
                    .text(
                        currentFlightNo || '--'
                    );

                loadSeatInventory(currentScheduleId,$('#departDate').val());
            }
        );


        /* ==========================================================
           LOAD SEAT INVENTORY
        ========================================================== */

        function loadSeatInventory(scheduleId,date) {
            if (!scheduleId || !date) {
                return;
            }

            $('#inventoryFormContainer').html(`
                <div class="empty-state" style="padding:50px 20px;">
                    <i class="fa fa-spinner fa-spin" style=" font-size:30px; color:#0078bc;"></i>
                    <div style="margin-top:10px;">
                        Loading seat inventory...
                    </div>
                </div>
            `);


            $.ajax({
                url: "{{ route('inventory-seat') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    schedule_id: scheduleId,
                    date: date
                },


                success: function(response) {
                    if (response.success && response.data) {
                        inventoryData = response.data;
                        renderInventoryForm(
                            response.data
                        );

                    } else {
                        inventoryData = null;
                        $('#inventoryFormContainer')
                            .html(`
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    Seat inventory not found
                                    for this flight.
                                </div>
                            `);
                    }
                },

                error: function(xhr) {
                    let message = 'Unable to load seat inventory.';
                    if ( xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = getFirstBackendError(xhr.responseJSON.errors);
                    }

                    else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }


                    $('#inventoryFormContainer')
                        .html(`
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-triangle"></i>
                                ${escapeHtml(message)}
                            </div>
                        `);
                }
            });
        }


        /* ==========================================================
           RENDER INVENTORY FORM
        ========================================================== */

        function renderInventoryForm(inventory) {

            const onlineCapacity = parseInt(inventory.online_capacity || 0,10);
            const offlineCapacity = parseInt(inventory.offline_capacity || 0,10);

            const onlineBooked = parseInt(inventory.online_booked || 0,10);
            const offlineBooked = parseInt(inventory.offline_booked || 0,10);
            $('#inventoryFormContainer')
                .html(`
                    <div class="inventory-form">
                        <form id="inventoryUpdateForm">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6 col-xs-12 form-group">
                                    <label for="onlineCapacityInput">
                                        <i class="fa fa-globe" style="color:#0078bc;"></i>
                                        Total Online Capacity
                                    </label>
                                    <input type="number" min="0" class="form-control" id="onlineCapacityInput" name="online_capacity" value="${onlineCapacity}" required>
                                </div>
            
                                <div class="col-sm-6 col-xs-12 form-group">
                                    <label for="offlineCapacityInput">
                                        <i class="fa fa-building" style="color:#00beda;"></i>
                                        Offline Capacity
                                    </label>


                                    <input type="number" min="0" class="form-control" id="offlineCapacityInput" name="offline_capacity" value="${offlineCapacity}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12">
                                    <button type="submit" id="updateInventoryBtn" class="btn btn-update-inventory">
                                        <i class="fa fa-save"></i>Update Inventory
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="inventory-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>
                                Flight:
                            </strong>

                            ${escapeHtml(currentFlightNo || '--')}

                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            <strong>
                                Journey Date:
                            </strong>

                            ${escapeHtml(
                                $('#departDate').val()
                            )}
                        </div>
                        <div id="inventoryMessage" class="inventory-message"></div>
                    </div>

                `);

        }


        /* ==========================================================
           UPDATE INVENTORY
        ========================================================== */

        $(document).on('submit','#inventoryUpdateForm',function(e) {
                e.preventDefault();
                if (!currentScheduleId) {
                    alert('Please select a flight first.');
                    return;

                }

                const onlineCapacity =parseInt($('#onlineCapacityInput').val(),10);
                const offlineCapacity =parseInt($('#offlineCapacityInput').val(),10);

                const date = $('#departDate').val();
                if (isNaN(onlineCapacity) || onlineCapacity < 0) {
                    alert('Please enter a valid online capacity.');
                    return;
                }


                if (isNaN(offlineCapacity) || offlineCapacity < 0) {
                    alert('Please enter a valid offline capacity.');
                    return;
                }


                if (!date) {
                    alert('Please select journey date.');
                    return;
                }


                const $button =$('#updateInventoryBtn');
                $button
                    .prop('disabled', true)
                    .html(`
                        <i class="fa fa-spinner fa-spin"></i>
                        Updating...
                    `);


                $('#inventoryMessage').html('');
                $.ajax({
                    url:"{{ route('update-seat-capacity') }}",
                    type: "POST",
                    data: {
                        _token:"{{ csrf_token() }}",
                        schedule_id: currentScheduleId,
                        date: date,
                        online_capacity: onlineCapacity,
                        offline_capacity: offlineCapacity

                    },
                    success: function(response) {
                        if (response.success) {
                            if (inventoryData && response.data) {
                                inventoryData.online_capacity = response.data.online_capacity;
                                inventoryData.offline_capacity = response.data.offline_capacity;
                            }
                            $('#inventoryMessage')
                                .html(`
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i>
                                        ${escapeHtml(
                                            response.message || 'Seat inventory updated successfully.'
                                        )}

                                    </div>

                                `);
                            setTimeout(function() {
                                renderInventoryForm(
                                    inventoryData
                                );
                            }, 800);

                        } else {
                            showInventoryBackendError(response.errors || null, response.message || '');
                        }
                    },

                    error: function(xhr) {
                        let errors = null;
                        let message = 'Unable to update seat inventory.';
                        if (xhr.responseJSON) {
                            errors = xhr.responseJSON.errors || null;
                            if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message
                            }

                        }

                        showInventoryBackendError(errors,message);
                    },
                    complete: function() {
                        $button
                            .prop('disabled',false)
                            .html(`
                                <i class="fa fa-save"></i>
                                Update Inventory

                            `);
                    }

                });

            }
        );


        /* ==========================================================
           SHOW INVENTORY BACKEND ERROR
        ========================================================== */

        function showInventoryBackendError(errors,fallbackMessage) {

            let message = 'Validation failed.';

            if (errors && errors.online_capacity && errors.online_capacity.length > 0) {
                message = errors.online_capacity[0];
            }

            else if (errors && errors.offline_capacity && errors.offline_capacity.length > 0) {
                message = errors.offline_capacity[0];
            }

            else if (errors && typeof errors === 'object') {
                message = getFirstBackendError(errors);
            }
            else if (fallbackMessage) {
                message = fallbackMessage;
            }


            $('#inventoryMessage')
                .html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        ${escapeHtml(message)}
                    </div>

                `);


            $('#onlineCapacityInput').removeClass('has-error');
            $('#offlineCapacityInput').removeClass('has-error');

            if (errors && errors.online_capacity) {
                $('#onlineCapacityInput').addClass('has-error');
            }


            if (errors && errors.offline_capacity) {
                $('#offlineCapacityInput').addClass('has-error');
            }

        }


        /* ==========================================================
           GET FIRST BACKEND ERROR
        ========================================================== */

        function getFirstBackendError(errors) {
            if (!errors || typeof errors !== 'object') {
                return 'Validation failed.';
            }

            const fields = Object.keys(errors);
            if (fields.length === 0) {
                return 'Validation failed.';
            }

            const firstField = fields[0];
            const fieldErrors = errors[firstField];
            if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
                return fieldErrors[0];
            }


            if (typeof fieldErrors === 'string'){
                return fieldErrors;
            }
            return 'Validation failed.';
        }


        /* ==========================================================
           NO FLIGHTS
        ========================================================== */

        function showNoFlights(message) {

            $('#flightsContainer')
                .html(`
                    <div class="empty-state">
                        <i class="fa fa-plane"></i>
                        <div>
                            No flights available
                        </div>
                        <small>
                            ${escapeHtml(message)}
                        </small>
                    </div>
                `);

            $('#currentFlightNo').text('--');
            $('#inventoryFormContainer')
                .html(`
                    <div class="empty-state">
                        <i class="fa fa-chair"></i>
                        <div>
                            No flight selected.
                        </div>
                    </div>
                `);


            currentScheduleId =null;
            currentFlightNo = null;
            inventoryData = null;
        }

        /* ==========================================================
           FORMAT TIME
        ========================================================== */

        function formatTime(time) {

            if (!time) {
                return '';
            }

            const parts = time.split(':');
            let hours = parseInt(parts[0],10);
            const minutes = parts[1] || '00';
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;

            if (hours === 0) {
                hours = 12;
            }

            return (hours + ':' + minutes + ' ' + ampm);
        }

        function escapeHtml(value) {
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