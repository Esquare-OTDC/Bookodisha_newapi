@extends('layouts.app')

@section('title','Tour Booking Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Tour Booking Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportCSV" class="btn btn-info">Export Excel</button>
            <button type="button" id="exportData" class="btn btn-info">Export</button>
        </div>
    </div>
    
    @if(Session::has('success'))
        <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
            {{ Session::get('success') }}
            @php
                Session::forget('success');
            @endphp
        </p>
    @endif
    
    
    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <form class="form-inline" id="filterForm" method="get" action="{{ route('tour-booking-report') }}">
                    @csrf
                    <select id="ticket_id" class="form-control" name="ticket_id" required>
                        <option value="0">All Tours</option>
                        @foreach ($Tours as $key => $value)
                            <option value="{{ $key }}"  {{ ($TicketId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    <select id="report_type" class="form-control" name="report_type" required>
                        <option value="book_date" {{ ($report_type == 'book_date') ? 'selected' : '' }}>Booking Date</option>
                        <option value="stay_date" {{ ($report_type == 'stay_date') ? 'selected' : '' }}>Stay Date</option>
                    </select>
                    <input type="text" class="form-control check-quantity" name="check_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" value="{{ date("d-m-Y", strtotime($check_date)) }}">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>

                <div class="table-responsive m-t-10">
                    @if ($report_type == 'book_date')
                        
                        <table class="display nowrap table table-hover table-bordered">
                            <thead>
                                <th>Booking Id</th>
                                <th>Booking Date</th>
                                <th>Guest</th>
                                <th>Tour Name</th>
                                <th>Tour<br>Date</th>
                                <th>Tour<br>Type</th>
                                <th>Occupancy</th>
                                <th>Seat No</th>
                                <th>Total<br>Amount</th>
                                <th>Booking<br>Source</th>
                                <th>Booking<br>Status</th>
                            </thead>
                            <tbody>
                            @foreach($MisTicketData as $val)
                            <tr>
                                <td>
                                    <div><a href="javascript:void(0)" class="viewIncoice" title="Click to view invoice" data-id="{{ $val['oderID'] }}">{{ $val['invoice_id'] }}</a></div>
                                    <div><a class="viewConfirmation" data-id="{{ $val['oderID'] }}" title="Click to view Confirmation Voucher"><i class="fa fa-eye"></i></a></div>
                                </td>
                                <td>{{ $val['book_date'] }}</td>
                                <td><div>{{ $val['guest_name'] }}</div><div>{{ $val['guest_phone'] }}</div><div>{{ $val['guest_email'] }}</div></td>
                                <td>{{ $val['unit_name'] }}</td>
                                <td>{{ $val['check_in'] }}</td>
                                <td>{{ strtoupper($val['service_category']) }}</td>
                                <td>{{ $val['occupancy'] }}</td>
                                <td>{{ !empty($val['seat_no']) ? $val['seat_no'] : 'N/A' }}</td>
                                <td>{{ $val['total_amount'] }}</td>
                                <td>{{ $val['order_type'] }}</td>
                                <td><div>{{ $val['status'] }}</div><div>{{ ($val['status'] == 'Confirmed') ? '' : $val['cancel_date'] }}</div></td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @elseif ($report_type == 'stay_date')
                        @foreach($MisTicketData as $key => $val)
                        <table class="display nowrap table table-hover table-bordered">
                            <thead>
                                <th colspan="11">{{ $val['name'] }}</th>
                            </thead>
                            <thead>
                                <th>Booking Id</th>
                                <th>Guest</th>
                                <th>Book<br>Date</th>
                                <th>Tour<br>Date</th>
                                <th>Tour<br>Type</th>
                                <th>Occupancy</th>
                                <th>Seat No</th>
                                <th>Total<br>Amount</th>
                                <th>Booking<br>Source</th>
                            </thead>
                            @foreach($val['data'] as $details)
                            <tr>
                                <td>
                                    <div><a href="javascript:void(0)" class="viewIncoice" title="Click to view invoice" data-id="{{ $details['oderID'] }}">{{ $details['invoice_id'] }}</a><div>
                                    <div><a class="viewConfirmation" data-id="{{ $details['oderID'] }}" title="Click to view Confirmation Voucher"><i class="fa fa-eye"></i></a></div>
                                </td>
                                <td><div>{{ $details['guest_name'] }}</div><div>{{ $details['guest_phone'] }}</div><div>{{ $details['guest_email'] }}</div></td>
                                <td>{{ $details['book_date'] }}</td>
                                <td>{{ $details['check_in'] }}</td>
                                <td>{{ strtoupper($details['service_category']) }}</td>
                                <td>{{ $details['occupancy'] }}</td>
                                <td>{{ !empty($details['seat_no']) ? $details['seat_no'] : 'N/A' }}</td>
                                <td>{{ $details['total_amount'] }}</td>
                                <td>{{ $details['order_type'] }}</td>
                            </tr>
                            
                            @endforeach
                            <tr>
                                <th colspan="5">Total Bookings:{{ $val['total_book'] }}</th>
                                <th>{{ $val['total_occupancy'] }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </table>
                        @endforeach
                    @endif
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            format: "dd-mm-yyyy",
            todayHighlight: true,
            // startDate: moment(new Date("{{ $check_date }}")).format('DD-MM-YYYY'),
            // endDate: '+120d'
        });

        $(document).on('click', '#resetSession', function () {
            window.location = window.location.pathname;
        });
        
        $(document).on('click', '#exportData', function () {
            let filter_date = $("#datepicker-autoclose").val();
            let report_type = $("#report_type").val();
            let ticket_id = $("#ticket_id").val();
            if (filter_date != '' && report_type != '' && ticket_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {report_type: report_type, filter_date: filter_date, ticket_id: ticket_id, request_type: "print_booking_report"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let url = responce.file_path;
                            window.open(url, 'window name', 'window settings');
                            return false;
                        }
                    }
                });
            }
        });

        $(document).on('click', '#exportCSV', function () {
            let filter_date = $("#datepicker-autoclose").val();
            let report_type = $("#report_type").val();
            let ticket_id = $("#ticket_id").val();
            if (filter_date != '' && report_type != '' && ticket_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {report_type: report_type, filter_date: filter_date, ticket_id: ticket_id, request_type: "export_booking_report"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            window.location.href = responce.file_path;
                            // let url = responce.file_path;
                            // window.open(url, 'window name', 'window settings');
                            // return false;
                        }
                    }
                });
            }
        });

        $(document).on('click', '.viewIncoice', function () {
            let orderID = $(this).data('id');
            if (orderID != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderID: orderID, request_type: "get_invoice_html"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let url = responce.data;
                            window.open(url, 'window name', 'window settings');
                            return false;
                        }
                    }
                });
            }
        });

        $(document).on('click', '.viewConfirmation', function () {
            let orderID = $(this).data('id');
            if (orderID != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderID: orderID, request_type: "get_confirm_html"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let url = responce.data;
                            window.open(url, 'window name', 'window settings');
                            return false;
                        }
                    }
                });
            }
        });
    });
    
</script>

@endsection