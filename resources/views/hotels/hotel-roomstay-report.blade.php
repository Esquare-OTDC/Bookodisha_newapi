@extends('layouts.app')

@section('title','Hotel Room Stay Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Hotel Room Stay Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <!-- <button type="button" id="exportCustomCSV" class="btn btn-info">Custom Export Excel</button> -->
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-roomstay-report') }}">
                    @csrf
                    <select id="hotel_id" class="form-control" name="hotel_id" required>
                        <option value="0">All Hotels</option>
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}"  {{ ($HotelId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    <select id="report_type" class="form-control" name="report_type" required>
                        <option value="stay_date" {{ ($report_type == 'stay_date') ? 'selected' : '' }}>Stay Date</option>
                        <option value="book_date" {{ ($report_type == 'book_date') ? 'selected' : '' }}>Booking Date</option>
                    </select>
                    <input type="text" class="form-control check-quantity" name="check_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" value="{{ date("d-m-Y", strtotime($check_date)) }}">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>

                <div class="table-responsive m-t-10">
                    @if ($report_type == 'stay_date')
                        @foreach($MisHotelData as $key => $val)
                        <table class="display nowrap table table-hover table-bordered">
                            <thead>
                                <th colspan="9">{{ $val['name'] }}</th>
                            </thead>
                            <thead>
                                <th>Booking Id</th>
                                <th>Guest</th>
                                <th>Room Details</th>
                                <th>Check<br>in</th>
                                <th>Check<br>out</th>
                                <th>Nights</th>
                                <th>Adult</th>
                                <th>Child</th>
                                <!-- <th>GST<br>Amount</th>
                                <th>Total<br>Amount</th> -->
                                <th>Booking<br>Source</th>
                            </thead>
                            @foreach($val['data'] as $details)
                            <tr>
                                <td>
                                {{ $details['invoice_id'] }}
                                </td>
                                <td>
                                    @if(Auth::user()->vendor_id == 3 && Auth::user()->role == 3 && Auth::user()->access_type == 'vendor')
                                        <div>{{ $details['guest_name'] }}</div><div>{{ $details['guest_phone'] }}</div>
                                    @else
                                        <div>{{ $details['guest_name'] }}</div><div>{{ $details['guest_phone'] }}</div><div>{{ $details['guest_email'] }}</div>
                                    @endif
                                    <div>
                                        @if ($details['sr_citizen'] == 1)
                                        (Sr. Citizen)&nbsp;
                                        @endif
                                        @if ($details['pickup'] == 'Yes')
                                        (Pickup Required)
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $details['rooms'] }}</td>
                                <td>{{ $details['check_in'] }}</td>
                                <td>{{ $details['check_out'] }}</td>
                                <td>{{ $details['nights'] }}</td>
                                <td>{{ $details['total_adult'] }}</td>
                                <td>{{ $details['total_child'] }}</td>
                                
                                <td>
                                    @if ($details['order_type'] == 'Blocked')
                                    <strong>{{ $details['order_type'] }}</strong>
                                    @else
                                    {{ $details['order_type'] }}
                                    @if ($details['service_type'] == 'tour')
                                    <?= '<br>(Package)' ?>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                            
                            @endforeach
                            <tr>
                                <th colspan="6">Total Bookings:{{ $val['total_book'] }} | {{ implode(' | ', array_map(function ($v, $k) { return $k.':'.$v; },$val['rooms'],array_keys($val['rooms']))) }} | Total Booked Rooms:{{ $val['total_rooms'] }}</th>
                                <th>{{ $val['total_adult'] }}</th>
                                <th>{{ $val['total_child'] }}</th>
                                <th></th>
                            </tr>
                        </table>
                        @endforeach
                    @elseif ($report_type == 'book_date')
                        <table class="display nowrap table table-hover table-bordered">
                            <thead>
                                <th>Booking Id</th>
                                <th>Booking Date</th>
                                <th>Guest</th>
                                <th>Unit</th>
                                <th>Room Details</th>
                                <th>Check<br>in</th>
                                <th>Check<br>out</th>
                                <th>Nights</th>
                                <th>Booking<br>Source</th>
                            </thead>
                            <tbody>
                            @foreach($MisHotelData as $val)
                            <tr>
                                <td>{{ $val['invoice_id'] }}</td>
                                <td>{{ $val['book_date'] }}</td>
                                <td>
                                    @if(Auth::user()->vendor_id == 3 && Auth::user()->role == 3 && Auth::user()->access_type == 'vendor')
                                        <div>{{ $val['guest_name'] }}</div><div>{{ $val['guest_phone'] }}</div>
                                    @else
                                        <div>{{ $val['guest_name'] }}</div><div>{{ $val['guest_phone'] }}</div><div>{{ $val['guest_email'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $val['unit_name'] }}</td>
                                <td>{{ $val['rooms'] }}</td>
                                <td>{{ $val['check_in'] }}</td>
                                <td>{{ $val['check_out'] }}</td>
                                <td>{{ $val['nights'] }}</td>
                                <td>{{ $val['order_type'] }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
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
            let hotel_id = $("#hotel_id").val();
            let report_type = $("#report_type").val();
            if (filter_date != '' && hotel_id != '' && report_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {report_type: report_type, filter_date: filter_date, hotel_id: hotel_id, request_type: "print_roomstay_report"},
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
            let hotel_id = $("#hotel_id").val();
            let report_type = $("#report_type").val();
            if (filter_date != '' && hotel_id != '' && report_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {report_type: report_type, filter_date: filter_date, hotel_id: hotel_id, request_type: "export_roomstay_report"},
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