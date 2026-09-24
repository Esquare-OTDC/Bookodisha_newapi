@extends('layouts.app')

@section('title','Hotel cancel Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Hotel cancel Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportCustomCSV" class="btn btn-info">Custom Export Excel</button>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-cancel-report') }}">
                    @csrf
                    <select id="hotel_id" class="form-control" name="hotel_id" required>
                        <option value="0">All Hotels</option>
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}"  {{ ($HotelId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
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
                            <th>Cancelled<br>Date</th>
                            <th>Cancelled<br>Time</th>
                            <th>Guest</th>
                            <th>Room Details</th>
                            <th>Check<br>in</th>
                            <th>Check<br>out</th>
                            <th>Nights</th>
                            <th>Paid<br>Amount</th>
                            <th>Refund<br>Amount</th>
                            <th>Booking<br>Source</th>
                        </thead>
                        @foreach($val['data'] as $details)
                        <tr>
                            <td>
                                
                                @if (empty($details['cancel_voucher']))
                                {{ $details['invoice_id'] }}
                                @else
                                <div><a href="javascript:void(0)" class="viewIncoice" title="Click to view invoice" data-id="{{ $details['oderID'] }}" data-platform="{{ $details['platform'] }}">{{ $details['invoice_id'] }}</a></div>
                                @endif
                            </td>
                            <td>{{ $details['book_date'] }}</td>
                            <td>{{ $details['cancel_date'] }}</td>
                            <td>{{ $details['cancel_time'] }}</td>
                            <td><div>{{ $details['guest_name'] }}</div></td>
                            <td>{{ $details['rooms'] }}</td>
                            <td>{{ $details['check_in'] }}</td>
                            <td>{{ $details['check_out'] }}</td>
                            <td>{{ $details['nights'] }}</td>
                            <td>{{ $details['total_amount'] }}</td>
                            <td>{{ $details['refund_amount'] }}</td>
                            <td>{{ $details['order_type'] }}</td>
                        </tr>
                        @endforeach
                    </table>
                    @endforeach
                    
                </div>
            </div>
        </div>
    </div>
</div>
<style type="text/css">
    
</style>

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
            if (filter_date != '' && hotel_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, hotel_id: hotel_id, request_type: "print_cancel_report"},
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
            if (filter_date != '' && hotel_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, hotel_id: hotel_id, request_type: "export_cancel_report"},
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

        $(document).on('click', '#exportCustomCSV', function () {
            let filter_date = $("#check_date").val();
            let hotel_id = $("#hotel_id").val();
            if (filter_date != '' && hotel_id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, hotel_id: hotel_id, request_type: "export_cancel_custom_report"},
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
            let platform = $(this).data('platform');
            if (orderID != '' && platform != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderID: orderID, platform: platform, request_type: "get_cancel_invoice_html"},
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