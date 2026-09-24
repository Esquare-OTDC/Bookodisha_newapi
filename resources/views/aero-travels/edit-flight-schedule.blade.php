@extends('layouts.app')

@section('title', 'Flight Management')

@section('content')

<link rel="stylesheet"  href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>


<div class="container-fluid flight-page">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    Home
                </li>

                <li class="breadcrumb-item">
                    Flight
                </li>

                <li class="breadcrumb-item active">
                    Edit Flight Schedule
                </li>
            </ol>
        </div>
    </div>


    @include('errors.message')

    <div class="row">
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">
                    Flight Management
                </h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="white-box flight-form-box">
                <h3 class="box-title">
                    Edit Flight Details
                </h3>
                <hr>

                <form action="{{ route('update-flight-schedule', $flightSchedule->id) }}" method="POST" id="flightForm">

                    @csrf

                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="from_city"> From <span class="required"> * </span></label>

                            <select class="form-control select2" id="from_city" name="from_city">
                                <option value="">Select Departure Airport</option>

                                @foreach($SelectedCity as $airport)
                                    <option value="{{ $airport->id }}"
                                        {{ old('from_city', $flightSchedule->source_airport_id) == $airport->id ? 'selected' : '' }}>
                                        {{ $airport->city_name }}
                                        ({{ $airport->airport_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('from_city')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="to_city"> To <span class="required"> * </span></label>

                            <select class="form-control select2" id="to_city" name="to_city">
                                <option value=""> Select Destination Airport </option>

                                @foreach($SelectedCity as $airport)

                                    <option value="{{ $airport->id }}"
                                        {{ old('to_city', $flightSchedule->destination_airport_id) == $airport->id ? 'selected' : '' }}>

                                        {{ $airport->city_name }}
                                        ({{ $airport->airport_code }})

                                    </option>

                                @endforeach
                            </select>

                            @error('to_city')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="section-title">
                        <span>Booking Details</span>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="datepicker-autoclose"> Booking Start Date <span class="required"> *</span>
                            </label>
                            @php
                                $bookingStartDate = $flightSchedule->booking_start_date
                                    ? \Carbon\Carbon::parse(
                                        $flightSchedule->booking_start_date
                                      )->format('d-m-Y')
                                    : '';
                            @endphp
                            <div class="input-group">
                                <input type="text" class="form-control" name="book_start_date" id="datepicker-autoclose" value="{{ old('book_start_date', $bookingStartDate) }}"  placeholder="dd-mm-yyyy" autocomplete="off">

                                <span class="input-group-addon">
                                    <i class="icon-calender"></i>
                                </span>
                            </div>


                            @error('book_start_date')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="book_end_time">
                                Booking End Time
                                <span class="required">
                                    *
                                </span>
                            </label>

                            <div class="input-group clockpicker" data-autoclose="true">
                                <input type="text" name="book_end_time" id="book_end_time" class="form-control" value="{{ old('book_end_time', $bookingEndTime) }}" required autocomplete="off">
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-time"></span>
                                </span>
                            </div>
                            @error('book_end_time')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>
                    @php
                        $editSelectedDays = old(
                            'available_days',
                            isset($selectedDays)
                                ? $selectedDays
                                : (
                                    isset($selectedDay) && $selectedDay
                                    ? [$selectedDay]
                                    : []
                                )
                        );

                        if (!is_array($editSelectedDays)) {
                            $editSelectedDays = [$editSelectedDays];
                        }
                    @endphp


                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="available_days">
                                Available Week Days
                                <span class="required">
                                    *
                                </span>
                            </label>
                            <select class="form-control" name="available_days[]" id="available_days" multiple>
                                @foreach($Days as $day)
                                    <option value="{{ $day }}"
                                        {{ in_array($day, $editSelectedDays) ? 'selected' : '' }}>

                                        {{ $day }}

                                    </option>

                                @endforeach
                            </select>


                            @error('available_days')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="days_from_start_date">
                                No. of days from Current Date(To set default Booking Date)
                                <span class="required">*</span>
                            </label>

                            <input type="number" class="form-control" id="days_from_start_date" name="days_from_start_date" value="{{ old('days_from_current', $flightSchedule->days_from_current) }}" placeholder="Days ahead from current date" min="0">

                            @error('days_from_start_date')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>
                   

                    <div class="section-title">
                        <span>Booking Slot</span>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="max_ticket_per_txn">
                                Max Ticket Per Transaction
                                <span class="required">
                                    *
                                </span>
                            </label>

                            <input type="number" class="form-control" id="max_ticket_per_txn" name="max_ticket_per_txn" value="{{ old('max_ticket_per_txn', $flightSchedule->per_transaction_ticket_limit) }}" min="1">
                            @error('max_ticket_per_txn')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="max_ticket_per_user_per_day">
                                Max Ticket Per User / Day
                                <span class="required">
                                    *
                                </span>
                            </label>

                            <input type="number"  class="form-control"  id="max_ticket_per_user_per_day" name="max_ticket_per_user_per_day" value="{{ old('max_ticket_per_user_per_day', $flightSchedule->per_day_ticket_limit) }}" min="1">

                            @error('max_ticket_per_user_per_day')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="slot-section">
                        <div class="table-responsive">
                            <table class="table table-bordered slot-table">
                                <thead>
                                    <tr>
                                        <th>Departure Time</th>
                                        <th>Arrival Time</th>
                                        <th>Online Tickets</th>
                                        <th> Offline Tickets</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="slot-row">
                                        <td>
                                            <div class="input-group clockpicker" data-autoclose="true">
                                                <input type="text" name="departure_time" id="departure_time" class="form-control" value="{{ old('departure_time', $departureTime) }}" required>
                                                <span class="input-group-addon">
                                                    <span class="glyphicon glyphicon-time"></span>
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="input-group clockpicker" data-autoclose="true">
                                                <input type="text" name="arrival_time" id="arrival_time" class="form-control" value="{{ old('arrival_time', $arrivalTime) }}" required>
                                                <span class="input-group-addon">
                                                    <span class="glyphicon glyphicon-time"></span>
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <input type="number" class="form-control" name="online_ticket" value="{{ old('online_ticket', $flightSchedule->online_capacity) }}" min="0" required>
                                        </td>

                                        <td>
                                            <input type="number" class="form-control" name="offline_ticket" value="{{ old('offline_ticket', $flightSchedule->offline_capacity) }}" min="0" required>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @error('departure_time')
                            <span class="error-text">
                                {{ $message }}
                            </span>
                        @enderror


                        @error('arrival_time')
                            <span class="error-text">
                                {{ $message }}
                            </span>
                        @enderror


                        @error('online_ticket')
                            <span class="error-text">
                                {{ $message }}
                            </span>
                        @enderror


                        @error('offline_ticket')
                            <span class="error-text">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="section-title">
                        <span>Pricing</span>
                    </div>
                    <div class="form-group row">
                        {{-- ADULT --}}
                        <div class="col-md-6">
                            <label for="adult_price">
                                Adult Price
                                <span class="required">
                                    *
                                </span>
                            </label>


                            <div class="input-group">
                                <span class="input-group-addon price-symbol">
                                    ₹
                                </span>
                                <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price', $adultFare->base_fare ?? '') }}" min="1">
                            </div>

                            @error('adult_price')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="child_price">
                                Infant Price (0-2 yrs)
                                <span class="required">
                                    *
                                </span>
                            </label>

                            <div class="input-group">
                                <span class="input-group-addon price-symbol">
                                    ₹
                                </span>
                                <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price', $infantFare->base_fare ?? '') }}"  min="0">
                            </div>

                            @error('child_price')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="section-title">
                        <span>Schedule Status</span>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="status">
                                Status
                            </label>

                            <select name="status" id="status" class="form-control">
                                <option value="OPEN"
                                    {{ old('status', $flightSchedule->status) == 'OPEN' ? 'selected' : '' }}>
                                    Publish
                                </option>
                                <option value="CLOSE"
                                    {{ old('status', $flightSchedule->status) == 'CLOSE' ? 'selected' : '' }}>
                                    Draft
                                </option>
                            </select>
                            @error('status')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('manage-flight', $flightSchedule->flight_id ?? $flight->id) }}" class="btn btn-default">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i>
                            Update Flight Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>

    .flight-page {
        padding-bottom: 30px;
    }

    .flight-page .header-section {
        margin-bottom: 20px;
    }

    .flight-page .header-section h2 {
        margin: 0;
        font-size: 22px;
        line-height: 30px;
        font-weight: 600;
        color: #2f3d4a;
    }

    .flight-page .flight-form-box {
        padding: 25px;
        margin-bottom: 20px;
    }

    .flight-page .box-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #2f3d4a;
    }

    .flight-page .section-title {
        margin: 25px 0 18px;
        padding-bottom: 9px;
        border-bottom: 1px solid #e5e5e5;
    }

    .flight-page .section-title span {
        display: inline-block;
        font-size: 15px;
        font-weight: 600;
        color: #2f3d4a;
    }

    .flight-page .form-group {
        margin-bottom: 20px;
    }

    .flight-page label {
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 500;
        color: #4d5965;
    }

    .flight-page .required {
        color: #dc3545;
    }

    .flight-page .form-control {
        width: 100%;
        height: 40px;
        padding: 8px 12px;
        border: 1px solid #e4e7ea;
        border-radius: 2px;
        box-shadow: none;
        color: #565656;
        font-size: 13px;
    }

    .flight-page .form-control:focus {
        border-color: #80bdff;
        box-shadow: none;
        outline: none;
    }

    .flight-page .input-group {
        width: 100%;
    }

    .flight-page .input-group .form-control {
        height: 40px;
    }

    .flight-page .input-group-addon {
        min-width: 40px;
        height: 40px;
        padding: 9px 12px;
        background: #f8f9fa;
        border-color: #e4e7ea;
        color: #777;
    }

    .flight-page .price-symbol {
        font-weight: 600;
        color: #555;
    }

    .flight-page #available_days + .btn-group {
        display: block;
        width: 100%;
    }

    .flight-page #available_days + .btn-group .multiselect {
        width: 100% !important;
        height: 40px;
        padding: 9px 12px;
        text-align: left;
        background: #fff;
        border: 1px solid #e4e7ea;
        border-radius: 2px;
        color: #555;
    }

    .flight-page .multiselect-container.dropdown-menu {
        width: 100% !important;
        max-height: 250px !important;
        overflow-y: auto;
    }

    .flight-page .multiselect-container > li > a > label.checkbox {
        color: #000 !important;
    }

    .flight-page .multiselect-container > li > a > label {
        padding: 3px 3px 3px 10px;
    }

    .flight-page .slot-section {
        margin-bottom: 20px;
    }

    .flight-page .slot-table {
        width: 100%;
        margin-bottom: 0;
        table-layout: fixed;
    }

    .flight-page .slot-table thead th {
        padding: 11px 10px !important;
        background: #f7f8fa;
        border-color: #e5e5e5;
        color: #555;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
    }

    .flight-page .slot-table tbody td {
        padding: 8px !important;
        border-color: #e5e5e5;
        vertical-align: middle;
    }

    .flight-page .slot-table .form-control {
        height: 38px;
        font-size: 12px;
    }

    .flight-page .slot-table .input-group-addon {
        height: 38px;
    }

    .flight-page .error-text {
        display: block;
        margin-top: 5px;
        color: #f44336;
        font-size: 12px;
    }

    .flight-page .form-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eeeeee;
    }

    .flight-page .form-actions .btn {
        min-width: 120px;
        height: 38px;
        padding: 8px 16px;
    }

    @media (max-width: 767px) {
        .flight-page .flight-form-box {
            padding: 18px;
        }

        .flight-page .slot-table {
            min-width: 650px;
        }

        .flight-page .form-actions {
            justify-content: stretch;
        }

        .flight-page .form-actions .btn {
            flex: 1;
        }

    }


    @media (max-width: 575px) {
        .flight-page .header-section h2 {
            font-size: 20px;
        }

        .flight-page .form-actions {
            flex-direction: column;
        }

        .flight-page .form-actions .btn {
            width: 100%;
        }

    }

</style>

<script>
    $(document).ready(function () {

        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'dd-mm-yyyy',
            startDate: '0d'
        });

        $('#available_days').multiselect({
            includeSelectAllOption: true,
            enableFiltering: false,
            nonSelectedText: 'Select Days',
            buttonWidth: '100%',
            numberDisplayed: 3
        });

        $('.clockpicker').clockpicker({
            donetext: 'Done',
            autoclose: true
        });
        /* =====================================================
        FORM VALIDATION
        ====================================================== */

        $('#flightForm').on('submit', function (e) {
            let hasError = false;
            $('.dynamic-error').remove();

            function showError(element, message) {
                $(element).after(
                    '<span class="error-text dynamic-error">' +
                    message +
                    '</span>'
                );

                hasError = true;
            }

            if ($('#from_city').val() === '') {
                showError('#from_city','Departure airport is required.');
            }

            if ($('#to_city').val() === '') {
                showError('#to_city','Destination airport is required.');
            }

            if ($('#from_city').val() !== '' && $('#to_city').val() !== '' && $('#from_city').val() === $('#to_city').val()) {
                showError('#to_city','Departure and destination cannot be the same.');
            }

            if ($('#datepicker-autoclose').val().trim() === '') {
                showError(
                    '#datepicker-autoclose',
                    'Booking start date is required.'
                );
            }

            if ($('#book_end_time').val().trim() === '') {
                showError(
                    '#book_end_time',
                    'Booking end time is required.'
                );
            }

            if (!$('#available_days').val() || $('#available_days').val().length === 0) {
                $('#available_days')
                    .next('.btn-group')
                    .after(
                        '<span class="error-text dynamic-error">' +
                        'Please select at least one day.' +
                        '</span>'
                    );
                hasError = true;
            }

            if ($('#max_ticket_per_txn').val() === '' || parseInt($('#max_ticket_per_txn').val()) <= 0) {
                showError(
                    '#max_ticket_per_txn',
                    'Enter a valid ticket limit.'
                );

            }

            if ($('#max_ticket_per_user_per_day').val() === '' || parseInt($('#max_ticket_per_user_per_day').val()) <= 0) {
                showError(
                    '#max_ticket_per_user_per_day',
                    'Enter a valid daily user limit.'
                );
            }

            if ($('input[name="departure_time"]').val().trim() === '') {
                showError(
                    'input[name="departure_time"]',
                    'Departure time is required.'
                );
            }

            if ($('input[name="arrival_time"]').val().trim() === '') {
                showError(
                    'input[name="arrival_time"]',
                    'Arrival time is required.'
                );
            }

            if ($('input[name="online_ticket"]').val() === '') {
                showError(
                    'input[name="online_ticket"]',
                    'Online capacity is required.'
                );
            }

            if ($('input[name="offline_ticket"]').val() === '') {
                showError(
                    'input[name="offline_ticket"]',
                    'Offline capacity is required.'
                );
            }

            if ($('#adult_price').val().trim() === '') {
                showError(
                    '#adult_price',
                    'Adult price is required.'
                );
            }

            if ($('#child_price').val().trim() === '') {
                showError(
                    '#child_price',
                    'Infant price is required.'
                );
            }

            if (hasError) {
                e.preventDefault();
                let firstError =
                    $('.dynamic-error:first');
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);

                }
                return false;
            }
        });
    });
</script>

@endsection