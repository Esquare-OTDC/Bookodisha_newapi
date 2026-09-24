@extends('layouts.app')

@section('title','Hall Booking Report')

@section('content')

<div class="container-fluid">

    <div class="row page-titles">

        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active">Hall Booking Report</li>
            </ol>
        </div>

        <div class="col-md-6 align-self-center text-right d-none d-md-block">

            <button type="button"
                    id="exportCSV"
                    class="btn btn-info">
                Export Excel
            </button>

            <button type="button"
                    id="exportData"
                    class="btn btn-info">
                Export
            </button>

        </div>

    </div>


    @if(Session::has('success'))

        <p class="flashMessage"
           style="color: #3bbc2e; text-align: center;">

            {{ Session::get('success') }}

            @php
                Session::forget('success');
            @endphp

        </p>

    @endif


    <div class="row">

        <div class="col-sm-12">

            <div class="white-box">

                <form class="form-inline"
                      id="filterForm"
                      method="get"
                      action="{{ route('hall-booking-report') }}">

                    @csrf

                    <select id="property_id"
                            class="form-control"
                            name="property_id"
                            required>

                        <option value="0">All Properties</option>

                        @foreach($MasterProperty as $key => $value)

                            <option value="{{ $key }}"
                                {{ $property_id == $key ? 'selected' : '' }}>

                                {{ $value }}

                            </option>

                        @endforeach

                    </select>


                    <select id="report_type"
                            class="form-control"
                            name="report_type"
                            required>

                        <option value="book_date"
                            {{ $report_type == 'book_date' ? 'selected' : '' }}>
                            Booking Date
                        </option>

                        <option value="stay_date"
                            {{ $report_type == 'stay_date' ? 'selected' : '' }}>
                            Stay Date
                        </option>

                    </select>


                    <input type="text"
                           class="form-control check-quantity"
                           name="check_date"
                           id="datepicker-autoclose"
                           placeholder="dd-mm-yyyy"
                           value="{{ date('d-m-Y', strtotime($check_date)) }}">


                    <button type="submit"
                            class="btn btn-sm btn-primary"
                            id="search">
                        Search
                    </button>

                    &nbsp;

                    <i id="resetSession"
                       class="fa fa-refresh fa-lg md-effect"
                       aria-hidden="true"
                       title="Reset"
                       style="cursor: pointer;">
                    </i>

                </form>


                <div class="table-responsive m-t-10">

                    <table class="display nowrap table table-hover table-bordered">

                        <thead>

                            <th>Sl No</th>

                            <th>Booking Id</th>

                            <th>Booking Date</th>

                            <th>Customer Name</th>

                            <th>Hall Name</th>

                            <th>Hall Type</th>

                            <th>Slot</th>

                            <th>
                                Total<br>
                                Amount
                            </th>

                            <th>
                                Booking<br>
                                Status
                            </th>

                        </thead>


                        <tbody>

                            @foreach($MisHallData as $key => $val)

                                <tr>

                                    <td>{{ ++$key }}</td>


                                    <td>
                                        @if ($val['link'] == 0)

                                            {{ $val['invoice_id'] }}

                                        @else

                                            <div>
                                                <a href="javascript:void(0)"
                                                class="viewIncoice"
                                                title="Click to view invoice"
                                                data-id="{{ $val['oderID'] }}"
                                                data-platform="website">

                                                    {{ $val['invoice_id'] }}

                                                </a>
                                            </div>

                                            <div>
                                                <a href="javascript:void(0)"
                                                class="viewIncoice"
                                                title="Click to view invoice"
                                                data-id="{{ $val['oderID'] }}"
                                                data-platform="website"
                                                style="cursor: pointer;">

                                                    <i class="fa fa-eye"></i>

                                                </a>
                                            </div>

                                        @endif
                                    </td>

                                    <td>{{ $val['book_date'] }}</td>


                                    <td>{{ $val['customer_name'] }}</td>


                                    <td>{{ $val['service_name'] }}</td>


                                    <td>{{ $val['hall_type'] }}</td>


                                    <td>{{ $val['slot_type'] }}</td>


                                    <td>{{ $val['total_amount'] }}</td>


                                    <td>

                                        <div>
                                            {{ $val['status'] }}
                                        </div>

                                        <div>

                                            {{ $val['status'] == 'Confirmed'
                                                ? ''
                                                : $val['cancel_date'] }}

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<script type="text/javascript">

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Datepicker
    |--------------------------------------------------------------------------
    */
    $('#datepicker-autoclose').datepicker({
        autoclose: true,
        format: "dd-mm-yyyy",
        todayHighlight: true
    });


    /*
    |--------------------------------------------------------------------------
    | Reset Filter
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '#resetSession', function () {
        window.location.href = window.location.pathname;
    });


    /*
    |--------------------------------------------------------------------------
    | Export Print Report
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '#exportData', function () {

        let filter_date = $("#datepicker-autoclose").val();
        let report_type = $("#report_type").val();
        let property_id = $("#property_id").val();

        if (
            filter_date === '' ||
            report_type === '' ||
            property_id === ''
        ) {
            alert('Please select all required filters.');
            return;
        }

        $.ajax({
            type: "POST",
            url: "{{ url('hall-oprsn') }}",

            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            },

            data: {
                report_type: report_type,
                filter_date: filter_date,
                property_id: property_id,
                request_type: "print_booking_report"
            },

            success: function (data) {

                let response = typeof data === 'string'
                    ? $.parseJSON(data)
                    : data;

                if (response.status == 0) {
                    alert(response.message);
                    return;
                }

                if (response.file_path) {
                    window.open(response.file_path, '_blank');
                } else {
                    alert('Report file URL not found.');
                }
            },

            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Unable to export report.');
            }
        });

    });


    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '#exportCSV', function () {

        let filter_date = $("#datepicker-autoclose").val();
        let report_type = $("#report_type").val();
        let property_id = $("#property_id").val();

        if (
            filter_date === '' ||
            report_type === '' ||
            property_id === ''
        ) {
            alert('Please select all required filters.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Booking IDs in exact order shown in table
        |--------------------------------------------------------------------------
        */
        let booking_order = [];

        $('.table-responsive table tbody tr').each(function () {

            let bookingId = $.trim(
                $(this).find('td:eq(1)').text()
            );

            if (bookingId !== '') {
                booking_order.push(bookingId);
            }

        });

        console.log(
            'Booking order:',
            booking_order
        );

        $.ajax({

            type: "POST",

            url: "{{ url('hall-oprsn') }}",

            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            },

            data: {
                report_type: report_type,
                filter_date: filter_date,
                property_id: property_id,

                /*
                * Send exact page order
                */
                booking_order: booking_order,

                request_type: "export_booking_report"
            },

            success: function (data) {

                let response = typeof data === 'string'
                    ? $.parseJSON(data)
                    : data;

                if (response.status == 0) {

                    alert(response.message);
                    return;
                }

                if (response.file_path) {

                    window.location.href =
                        response.file_path;

                } else {

                    alert(
                        'Excel file URL not found.'
                    );
                }
            },

            error: function (xhr) {

                console.log(
                    xhr.responseText
                );

                alert(
                    'Unable to export Excel report.'
                );
            }

        });

    });
        
    /*
    |--------------------------------------------------------------------------
    | Custom Export Excel
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '#exportCustomCSV', function () {

        let filter_date = $("#datepicker-autoclose").val();
        let report_type = $("#report_type").val();
        let property_id = $("#property_id").val();

        if (
            filter_date === '' ||
            report_type === '' ||
            property_id === ''
        ) {
            alert('Please select all required filters.');
            return;
        }

        $.ajax({
            type: "POST",
            url: "{{ url('hall-oprsn') }}",

            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            },

            data: {
                report_type: report_type,
                filter_date: filter_date,
                property_id: property_id,
                request_type: "export_booking_custom_report"
            },

            success: function (data) {

                let response = typeof data === 'string'
                    ? $.parseJSON(data)
                    : data;

                if (response.status == 0) {
                    alert(response.message);
                    return;
                }

                if (response.file_path) {
                    window.location.href = response.file_path;
                } else {
                    alert('Custom Excel file URL not found.');
                }
            },

            error: function (xhr) {
                console.log(xhr.responseText);
                alert('Unable to export custom Excel report.');
            }
        });

    });


    /*
    |--------------------------------------------------------------------------
    | View Invoice
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '.viewIncoice', function (event) {

        event.preventDefault();

        let orderID = $(this).data('id');
        let platform = $(this).data('platform');

        if (!orderID) {
            alert('Order ID is missing.');
            return;
        }

        $.ajax({
            type: "POST",
            url: "{{ url('hall-oprsn') }}",

            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            },

            data: {
                orderID: orderID,
                platform: platform,
                request_type: "get_invoice_html"
            },

            success: function (data) {

                let response;

                try {

                    response = typeof data === 'string'
                        ? $.parseJSON(data)
                        : data;

                } catch (error) {

                    console.log(error);

                    alert(
                        'Invalid response received from server.'
                    );

                    return;
                }

                if (response.status == 0) {
                    alert(response.message);
                    return;
                }

                if (response.data) {
                    window.open(
                        response.data,
                        '_blank'
                    );
                    return;
                }

                if (response.file_path) {
                    window.open(
                        response.file_path,
                        '_blank'
                    );
                    return;
                }

                alert('Invoice URL not found.');
            },

            error: function (xhr) {

                console.log(
                    xhr.responseText
                );

                alert(
                    'Unable to open invoice.'
                );
            }
        });

    });

});

</script>

@endsection