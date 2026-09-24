@extends('layouts.app')

@section('title','Hotel Availability Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Hotel Availability Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportWithBlockData" class="btn btn-info">Export with blocked</button>
            <button type="button" id="exportAll" class="btn btn-info">Export All</button>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-availablity-report') }}">
                    @csrf
                    <input type="hidden" name="filter" value="filter"> 
                    <select id="hotel_id" class="form-control" name="hotel_id" required>
                        <!-- <option value="0">All Hotels</option> -->
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}"  {{ ($HotelId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>

                <div class="table-responsive">
                    
                    @foreach($MisHotelData as $hotel => $hotels)
                    <table class="display nowrap table table-hover table-bordered">
                        <tr>
                            <th colspan="{{ count($hotels['rooms']) + 2 }}">Availability status of {{ $hotel }} as on ({{ date("d-m-Y h:i a") }})</th>
                        </tr>
                        <tr>
                            <th rowspan="2">Tent Capacity</th>
                            @foreach($hotels['rooms'] as $title => $qty)
                            <th>{{ $title }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                        <tr>
                            @php
                            $total = 0;
                            @endphp
                            @foreach($hotels['rooms'] as $title => $qty)
                            <th>{{ $qty }}</th>
                            @php
                            $total += $qty;
                            @endphp
                            @endforeach
                            <th>{{ $total }}</th>
                        </tr>
                        @foreach($hotels['data'] as $key => $val)
                        <tr>
                            <td>{{ date("d-M-Y", strtotime($key)) }}</td>
                            @php
                            $tot = $book = $avail = 0;
                            @endphp
                            @foreach($hotels['data'][$key] as $qtys)
                            <td>{{ $qtys['available'] }}</td>
                            @php
                            $tot += $qtys['initial'];
                            $book += $qtys['booked'];
                            $avail += $qtys['available'];
                            @endphp
                            @endforeach
                            <td>{{ $avail }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <th>Total Available</th>
                            @php
                            $totalAvailable = 0;
                            @endphp
                            @foreach($hotels['total'] as $qty)
                            <th>{{ $qty }}</th>
                            @php
                            $totalAvailable += $qty;
                            @endphp
                            @endforeach
                            <th>{{ $totalAvailable }}</th>
                        </tr>
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
        // $("body").addClass('mini-sidebar');
        
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(new Date("{{ $start_date }}")),
            endDate: moment(new Date("{{ $end_date }}")),
            // minDate: moment(),
//            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD-MM-YYYY'
            }
        });

        $(document).on('click', '#resetSession', function () {
            window.location = window.location.pathname;
        });
        
        $(document).on('click', '#exportData', function () {
            let hotelId = $("#hotel_id").val();
            let check_date = $("#check_date").val();
            
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {hotelId: hotelId, filter_date: check_date, request_type: "export_hotel_availability_report"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        window.location = responce.file_path;
                    }
                    // let url = data;
                    // window.open(url, 'window name', 'window settings');
                    // return false;

                    // var downloadLink = document.createElement("a");
                    // var blob = new Blob(["\ufeff", data]);
                    // var url = URL.createObjectURL(blob);
                    // downloadLink.href = url;
                    // downloadLink.download = "Hotel_room_mis_report_"+ new Date().getTime() +".csv";  //Name the file here
                    // document.body.appendChild(downloadLink);
                    // downloadLink.click();
                    // document.body.removeChild(downloadLink);
                }
            });
        });

        $(document).on('click', '#exportAll', function () {
            let check_date = $("#check_date").val();
            
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {filter_date: check_date, request_type: "export_all_hotel_availability"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        window.location = responce.file_path;
                    }
                }
            });
        });

        $(document).on('click', '#exportWithBlockData', function () {
            let hotelId = $("#hotel_id").val();
            let check_date = $("#check_date").val();
            
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {hotelId: hotelId, filter_date: check_date, request_type: "export_hotel_availability_with_block_report"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        window.location = responce.file_path;
                    }
                }
            });
        });
    });
    
</script>

@endsection