@extends('layouts.app')

@section('title', 'Flight Management')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>


<div class="container-fluid flight-page">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Flight</li>
                <li class="breadcrumb-item active">
                    Flight Management
                </li>
            </ol>
        </div>
    </div>

    @include('errors.message')

    <div class="row">
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">Flight Management</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="white-box flight-form-box">
                <h3 class="box-title">
                    Add Flight Schedule Details
                </h3>
                <hr>

                <form action="{{ route('add-flight-schedule', $id) }}" method="POST" id="flightForm">
                    @csrf

                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="from_city">
                                From
                                <span class="required">*</span>
                            </label>
                            <select class="form-control select2"  id="from_city"  name="from_city">
                                <option value="">
                                    Select Departure Airport
                                </option>

                                @foreach($SelectedCity as $airport)

                                    <option value="{{ $airport->id }}"
                                        {{ old('from_city') == $airport->id ? 'selected' : '' }}>

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
                            <label for="to_city">
                                To
                                <span class="required">*</span>
                            </label>

                            <select class="form-control select2" id="to_city" name="to_city">
                                <option value="">
                                    Select Destination Airport
                                </option>
                                @foreach($SelectedCity as $airport)
                                    <option value="{{ $airport->id }}"
                                        {{ old('to_city') == $airport->id ? 'selected' : '' }}>
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
                            <label for="datepicker-autoclose">
                                Booking Start Date
                                <span class="required">*</span>
                            </label>

                            <div class="input-group">
                                <input type="text" class="form-control" name="book_start_date" id="datepicker-autoclose" value="{{ old('book_start_date') }}" placeholder="dd-mm-yyyy"  autocomplete="off">

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
                                <span class="required">*</span>
                            </label>

                            <div class="input-group clockpicker" data-autoclose="true">
                                <input type="text"  class="form-control" name="book_end_time" id="book_end_time" value="{{ old('book_end_time') }}" placeholder="HH:MM" autocomplete="off">
                                
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

                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="available_days">
                                Available Week Days
                                <span class="required">*</span>
                            </label>

                            <select class="form-control" name="available_days[]" id="available_days" multiple>
                                @foreach($Days as $val)
                                    <option value="{{ $val }}" {{ in_array($val, old('available_days', [])) ? 'selected' : '' }}>
                                        {{ $val }}
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

                            <input type="number" class="form-control" id="days_from_start_date" name="days_from_start_date" value="1" placeholder="Days ahead from current date" min="0">

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
                                <span class="required">*</span>
                            </label>

                            <input type="number" class="form-control" id="max_ticket_per_txn" name="max_ticket_per_txn" value="{{ old('max_ticket_per_txn') }}" min="1">

                            @error('max_ticket_per_txn')
                                <span class="error-text">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="max_ticket_per_user_per_day">
                                Max Ticket Per User / Day
                                <span class="required">*</span>
                            </label>

                            <input type="number" class="form-control" id="max_ticket_per_user_per_day" name="max_ticket_per_user_per_day" value="{{ old('max_ticket_per_user_per_day') }}" min="1">

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
                                        <th>Offline Tickets</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="slot-row">
                                        <td>
                                            <div class="input-group clockpicker" data-autoclose="true">
                                                <input type="text" class="form-control"  name="departure_time" value="{{ old('departure_time') }}" placeholder="HH:MM" autocomplete="off" required>

                                                <span class="input-group-addon">
                                                    <span class="glyphicon glyphicon-time"></span>
                                                </span>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="input-group clockpicker" data-autoclose="true">
                                                <input type="text" class="form-control"  name="arrival_time" value="{{ old('arrival_time') }}" placeholder="HH:MM" autocomplete="off" required>

                                                <span class="input-group-addon">
                                                    <span class="glyphicon glyphicon-time"></span>
                                                </span>

                                            </div>
                                        </td>

                                        <td>
                                            <input type="number" class="form-control" name="online_ticket" value="{{ old('online_ticket') }}"  min="0"  required>
                                        </td>

                                        <td>
                                            <input type="number" class="form-control" name="offline_ticket" value="{{ old('offline_ticket') }}" min="0" required>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @error('departure_time')
                            <span class="error-text d-block">
                                {{ $message }}
                            </span>
                        @enderror
                        @error('arrival_time')
                            <span class="error-text d-block">
                                {{ $message }}
                            </span>
                        @enderror
                        @error('online_ticket')
                            <span class="error-text d-block">
                                {{ $message }}
                            </span>
                        @enderror
                        @error('offline_ticket')
                            <span class="error-text d-block">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="section-title">
                        <span>Pricing</span>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="adult_price">
                                Adult Price
                                <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-addon price-symbol">
                                    ₹
                                </span>

                                <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price') }}" min="1">
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
                                <span class="required">*</span>
                            </label>

                            <div class="input-group">
                                <span class="input-group-addon price-symbol">
                                    ₹
                                </span>

                                <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price') }}"  min="0">
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
                                    {{ old('status', 'OPEN') == 'OPEN' ? 'selected' : '' }}>
                                    Publish
                                </option>

                                <option value="CLOSE"
                                    {{ old('status') == 'CLOSE' ? 'selected' : '' }}>
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
                        <button type="reset" class="btn btn-default">
                            Reset
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i>
                            Add Flight Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="white-box flight-list-box">
                <div class="list-header">
                    <div>
                        <h3 class="box-title">
                            Flight Schedule List
                        </h3>
                    </div>


                    <div class="bulk-actions">
                        <select class="form-control" id="bulkOperation">
                            <option value="">
                                Bulk Action
                            </option>

                            <option value="publish-flight">
                                Publish
                            </option>

                            <option value="draft-flight">
                                Move to Draft
                            </option>
                        </select>

                        <button type="button" class="btn btn-primary" id="submitBulkOperation">
                            Apply
                        </button>
                    </div>
                </div>
                <hr>

                <div class="table-responsive">
                    <table id="datatable-responsive" class="display table table-hover table-bordered flight-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th class="check-col">
                                    <div class="checkbox-fade">
                                        <label>
                                            <input type="checkbox" value="checkAll" id="checkAll">
                                            <span class="cr">
                                                <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                            </span>
                                        </label>
                                    </div>
                                </th>

                                <th> Route </th>
                                <th> Price</th>
                                <th>Departure Day</th>
                                <th> Status</th>
                                <th class="action-col"> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($flightSchedules as $schedule)
                            @php
                                $adultFare = $schedule->fares
                                    ->where('passenger_type', 'ADULT')
                                    ->first();

                                $infantFare = $schedule->fares
                                    ->where('passenger_type', 'INFANT')
                                    ->first();

                                $dayMap = [
                                    1 => 'Monday',
                                    2 => 'Tuesday',
                                    3 => 'Wednesday',
                                    4 => 'Thursday',
                                    5 => 'Friday',
                                    6 => 'Saturday',
                                    7 => 'Sunday',
                                ];
                                $departureDay = $dayMap[(int) $schedule->departure_day] ?? '-';

                            @endphp
                            <tr>
                                <td class="check-col">
                                    <div class="checkbox-fade">
                                        <label>
                                            <input type="checkbox" class="itemcheck" value="{{ $schedule->id }}">
                                            <span class="cr">
                                                <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                            </span>
                                        </label>
                                    </div>
                                </td>

                                <td>
                                    <div class="route-code">
                                        {{ $schedule->sourceAirport->airport_code ?? '-' }}
                                        <span class="route-arrow">
                                            →
                                        </span>
                                        {{ $schedule->destinationAirport->airport_code ?? '-' }}
                                    </div>

                                    <div class="route-city">
                                        {{ $schedule->sourceAirport->city_name ?? '-' }}
                                        <span>→</span>

                                        {{ $schedule->destinationAirport->city_name ?? '-' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="price-line">
                                        <span class="price-label">
                                            Adult
                                        </span>

                                        <strong>
                                            ₹{{ number_format($adultFare->base_fare ?? 0, 2) }}
                                        </strong>

                                    </div>

                                    <div class="price-line">
                                        <span class="price-label">
                                            Infant
                                        </span>

                                        <strong>
                                            ₹{{ number_format($infantFare->base_fare ?? 0, 2) }}
                                        </strong>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="label label-info status-label">
                                        {{ $departureDay }}
                                    </span>
                                </td>

                                <td>
                                    @if($schedule->status === 'OPEN')
                                        <span class="label label-success status-label">
                                            Publish
                                        </span>
                                    @elseif($schedule->status === 'CLOSE')
                                        <span class="label draft-label">
                                            Draft
                                        </span>
                                    @else
                                        <span class="label label-warning status-label">
                                            {{ $schedule->status }}
                                        </span>
                                    @endif
                                </td>

                                <td class="action-col">
                                    <a href="{{ route('edit-flight-schedule', $schedule->id) }}" class="btn btn-xs btn-primary edit-btn" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center no-data">
                                    <i class="fa fa-plane"></i>
                                    <p>
                                        No flight schedules found.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
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

.flight-page .flight-form-box,
.flight-page .flight-list-box {
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
    height: 40px;
    border: 1px solid #e4e7ea;
    border-radius: 2px;
    box-shadow: none;
    color: #565656;
    font-size: 13px;
}

.flight-page .form-control:focus {
    border-color: #80bdff;
    box-shadow: none;
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

.flight-page .multiselect-container .input-group {
    margin: 4px 8px;
}

.flight-page .slot-section {
    margin-bottom: 20px;
}

.flight-page .slot-table {
    margin-bottom: 0;
    table-layout: fixed;
}

.flight-page .slot-table thead th {
    background: #f7f8fa;
    border-color: #e5e5e5;
    color: #555;
    font-size: 12px;
    font-weight: 600;
    padding: 11px 10px;
    text-align: center;
}

.flight-page .slot-table tbody td {
    padding: 8px;
    vertical-align: middle;
    border-color: #e5e5e5;
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
    gap: 8px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eeeeee;
}

.flight-page .form-actions .btn {
    min-width: 110px;
    height: 38px;
    padding: 8px 16px;
}

.flight-page .list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.flight-page .list-description {
    margin: 5px 0 0;
    color: #999;
    font-size: 12px;
}

.flight-page .bulk-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flight-page .bulk-actions .form-control {
    width: 180px;
    height: 38px;
}

.flight-page .bulk-actions .btn {
    height: 38px;
    padding: 8px 18px;
}

.flight-page .flight-table {
    margin-bottom: 0 !important;
}

.flight-page .flight-table thead th {
    padding: 12px 10px !important;
    background: #f7f8fa;
    border-color: #e5e5e5;
    color: #555;
    font-size: 12px;
    font-weight: 600;
    vertical-align: middle;
    white-space: nowrap;
}

.flight-page .flight-table tbody td {
    padding: 13px 10px !important;
    border-color: #eeeeee;
    color: #555;
    font-size: 13px;
    vertical-align: middle;
}

.flight-page .flight-table tbody tr:hover {
    background: #fafafa;
}
.flight-page .check-col {
    width: 55px;
    text-align: center;
}

.flight-page .checkbox-fade {
    display: inline-block;
}

.flight-page .checkbox-fade label {
    margin: 0;
}
.flight-page .route-code {
    color: #2f3d4a;
    font-size: 14px;
    font-weight: 600;
}

.flight-page .route-arrow {
    padding: 0 5px;
    color: #999;
}

.flight-page .route-city {
    margin-top: 5px;
    color: #999;
    font-size: 11px;
}

.flight-page .route-city span {
    padding: 0 4px;
}

.flight-page .price-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.flight-page .price-line:last-child {
    margin-bottom: 0;
}

.flight-page .price-label {
    display: inline-block;
    min-width: 48px;
    color: #999;
    font-size: 11px;
}

.flight-page .price-line strong {
    color: #2f3d4a;
    font-size: 12px;
}
.flight-page .status-label {
    padding: 5px 10px;
    border-radius: 2px;
    font-size: 11px;
    font-weight: 500;
}

.flight-page .draft-label {
    display: inline-block;
    padding: 5px 10px;
    background: #ffb136;
    color: #fff;
    border-radius: 2px;
    font-size: 11px;
    font-weight: 500;
}
.flight-page .action-col {
    width: 80px;
    text-align: center;
}

.flight-page .edit-btn {
    width: 32px;
    height: 30px;
    padding: 6px 8px;
}


.flight-page .flight-table thead th:first-child::before,
.flight-page .flight-table thead th:first-child::after {
    display: none !important;
}

.flight-page .flight-table thead th:first-child {
    background-image: none !important;
    cursor: default !important;
}


.flight-page .no-data {
    padding: 35px !important;
    color: #999;
}

.flight-page .no-data i {
    margin-bottom: 10px;
    font-size: 24px;
    color: #ccc;
}

.flight-page .no-data p {
    margin: 0;
    font-size: 13px;
}

@media (max-width: 767px) {

    .flight-page .flight-form-box,
    .flight-page .flight-list-box {
        padding: 18px;
    }

    .flight-page .list-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .flight-page .bulk-actions {
        width: 100%;
    }

    .flight-page .bulk-actions .form-control {
        flex: 1;
        width: auto;
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

    .flight-page .bulk-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .flight-page .bulk-actions .form-control,
    .flight-page .bulk-actions .btn {
        width: 100%;
    }

    .flight-page .form-actions {
        flex-direction: column;
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

    $('.clockpicker').clockpicker({
        donetext: 'Done',
        autoclose: true
    });

    $('#available_days').multiselect({
        includeSelectAllOption: true,
        enableFiltering: false,
        nonSelectedText: 'Select Days',
        buttonWidth: '100%',
        numberDisplayed: 3
    });


    if ($.fn.DataTable) {
        $('#datatable-responsive').DataTable({
            paging: false,
            searching: false,
            ordering: true,
            info: false,
            lengthChange: false,
            scrollX: false,
            autoWidth: false,

            columnDefs: [
                {
                    targets: 0,
                    orderable: false
                }
            ]

        });

    }


    $(document).on('change', '#checkAll', function () {
        $('.itemcheck').prop(
            'checked',
            $(this).prop('checked')
        );

    });


    $(document).on('change', '.itemcheck', function () {
        let total = $('.itemcheck').length;
        let checked = $('.itemcheck:checked').length;

        $('#checkAll').prop('checked', total > 0 && total === checked);
    });


    /* =========================================================
       FORM VALIDATION
    ========================================================== */

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
            showError('#datepicker-autoclose','Booking start date is required.');
        }


        if ($('#book_end_time').val().trim() === '') {
            showError('#book_end_time','Booking end time is required.');
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


        if ( $('#max_ticket_per_txn').val() === '' || parseInt($('#max_ticket_per_txn').val()) <= 0) {
            showError('#max_ticket_per_txn','Enter a valid ticket limit.');
        }


        if ($('#max_ticket_per_user_per_day').val() === '' || parseInt($('#max_ticket_per_user_per_day').val()) <= 0) {
            showError('#max_ticket_per_user_per_day', 'Enter a valid daily user limit.');
        }


        if ($('#adult_price').val().trim() === '') {
            showError('#adult_price','Adult price is required.');
        }


        if ($('#child_price').val().trim() === '') {
            showError('#child_price','Infant price is required.');
        }

        if ($('#days_from_start_date').val().trim() === '') {
            showError('#days_from_start_date','No. of days from Current Date');
        }


        if (hasError) {
            e.preventDefault();
            let firstError = $('.dynamic-error:first');
            if (firstError.length) {
                $('html, body').animate(
                    {
                        scrollTop: firstError.offset().top - 100
                    },
                    300
                );

            }
            return false;
        }

    });


    /* =========================================================
       BULK OPERATION
    ========================================================== */

    $('#submitBulkOperation').on('click', function (e) {
        e.preventDefault();

        let idArray = [];

        $('.itemcheck:checked').each(function () {
            idArray.push($(this).val());
        });

        if (idArray.length === 0) {
            alert('No items selected!');
            return false;
        }

        let action = $('#bulkOperation').val();

        if (action === '') {
            alert('Please select an action!');
            return false;
        }

        $.ajax({
            type: 'POST',
            url: "{{ route('flight-operation') }}",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },

            data: {
                IdArray: JSON.stringify(idArray),
                request_type: action
            },

            success: function (data) {
                let response = typeof data === 'string' ? $.parseJSON(data) : data;
                alert(response.message);
                if (response.status != 0) {
                    location.reload();
                }

            },

            error: function () {
                alert('Something went wrong. Please try again.');
            }

        });
    });
});

</script>
@endsection