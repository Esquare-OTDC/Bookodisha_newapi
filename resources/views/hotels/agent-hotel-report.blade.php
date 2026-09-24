@extends('layouts.app')

@section('title','Agent Hotel Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active">Agent Hotel Report</li>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('agent-hotel-report') }}">
                    @csrf
                    <select id="report_type" class="form-control" name="report_type" required>
                        <option value="book_date" {{ ($report_type == 'book_date') ? 'selected' : '' }}>Booking Date</option>
                        <option value="stay_date" {{ ($report_type == 'stay_date') ? 'selected' : '' }}>Checkin Date</option>
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>

                <div class="table-responsive m-t-10">
                    <table class="display nowrap table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Unit Name</th>
                                <th>Agent</th>
                                <th>Room Nights</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                        $grandTotal = 0;
                        @endphp
                        @foreach($MisHotelData as $key => $val)
                            @php
                            $unitTotal = 0;
                            @endphp
                            @foreach($val as $details)
                            <tr>
                                <td>{{ $details['unit_name'] }}</td>
                                <td>{{ $details['agent_name'] }}</td>
                                <td>{{ $details['room_night'] }}</td>
                            </tr>
                            @php
                            $unitTotal += $details['room_night'];
                            @endphp
                            @endforeach
                            <tr>
                                <th colspan="2">UNIT TOTAL</th>
                                <th colspan="4">{{ $unitTotal }}</th>
                            </tr>
                            @php
                            $grandTotal += $unitTotal;
                            @endphp
                        @endforeach
                        <tr>
                            <th colspan="2">GRAND TOTAL</th>
                            <th colspan="4">{{ $grandTotal }}</th>
                        </tr>
                        </tbody>
                    </table>
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
            let report_type = $("#report_type").val();
            if (filter_date != '' && report_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, report_type: report_type, request_type: "print_agent_hotel_report"},
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
            let report_type = $("#report_type").val();
            if (filter_date != '' && report_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {filter_date: filter_date, report_type: report_type, request_type: "export_agent_hotel_report"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            window.location = responce.file_path;
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