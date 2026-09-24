@extends('layouts.app')

@section('title','MMT Booking Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> MMT Booking Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportSales" class="btn btn-info">Export Sales Report</button>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-mmt-report') }}">
                    @csrf
                    <select id="hotel_id" class="form-control" name="hotel_id" required>
                        <option value="0">All Hotels</option>
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}"  {{ ($HotelId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    <select id="filter_type" class="form-control" name="filter_type" required>
                        <option value="booking_date" {{ ($filterType == 'booking_date') ? 'selected' : '' }}>Booking Date</option>
                        <option value="start_date" {{ ($filterType == 'start_date') ? 'selected' : '' }}>Checkin Date</option>
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>

                <div class="table-responsive m-t-10">
                    @foreach($MisHotelData as $key => $val)
                    <table class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th colspan="12"><h5>{{ $val['name'] }}</h5></th>
                        </thead>
                        <thead>
                            <th>Booking Id</th>
                            <th>Booking<br>Date</th>
                            <th>Guest</th>
                            <th>Room Details</th>
                            <th>Check<br>in</th>
                            <th>Check<br>out</th>
                            <th>Nights</th>
                            <th>Total<br>Amount</th>
                            <th>Booking<br>Source</th>
                            <th>Booking<br>Status</th>
                        </thead>
                        @foreach($val['data'] as $details)
                        <tr>
                            <td>{{ $details['invoice_id'] }}</td>
                            <td>{{ $details['book_date'] }}</td>
                            <td><div>{{ $details['guest_name'] }}</div></td>
                            <td>{{ $details['rooms'] }}</td>
                            <td>{{ $details['check_in'] }}</td>
                            <td>{{ $details['check_out'] }}</td>
                            <td>{{ $details['nights'] }}</td>
                            <td>{{ $details['total_amount'] }}</td>
                            <td>{{ $details['order_type'] }}</td>
                            <td><div>{{ $details['status'] }}</div><div>{{ ($details['status'] == 'Confirmed') ? '' : $details['cancel_date'] }}</div></td>
                        </tr>
                        @endforeach
                    </table>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(new Date("{{ $start_date }}")),
            endDate: moment(new Date("{{ $end_date }}")),
            // minDate: moment(),
            // maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD-MM-YYYY'
            }
        });

        $(document).on('click', '#resetSession', function () {
            window.location = window.location.pathname;
        });
        
        $(document).on('click', '#exportData', function () {
            let filter_date = $("#check_date").val();
            let hotel_id = $("#hotel_id").val();
            let filterType = $("#filter_type").val();
            if (filter_date != '' && hotel_id != '' && filterType != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('mmt-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, hotel_id: hotel_id, filter_type: filterType, request_type: "print_mmt_report"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            // window.location.href = responce.file_path;
                            let url = responce.file_path;
                            window.open(url, 'window name', 'window settings');
                            return false;
                        }
                    }
                });
            }
        });

        $(document).on('click', '#exportCSV', function () {
            let filter_date = $("#check_date").val();
            let hotel_id = $("#hotel_id").val();
            let filterType = $("#filter_type").val();
            if (filter_date != '' && hotel_id != '' && filterType != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('mmt-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, hotel_id: hotel_id, filter_type: filterType, request_type: "export_mmt_report"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            window.location.href = responce.file_path;
                        }
                    }
                });
            }
        });

        $(document).on('click', '#exportSales', function () {
            let filter_date = $("#check_date").val();
            let hotel_id = $("#hotel_id").val();
            let filter_type = $("#filter_type").val();
            if (filter_date != '' && hotel_id != '' && filter_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('mmt-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, hotel_id: hotel_id, filter_type: filter_type, request_type: "export_mmt_sales_report"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            window.location.href = responce.file_path;
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
                    url: "{{url('hotel-oprsn')}}",
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
                    url: "{{url('hotel-oprsn')}}",
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