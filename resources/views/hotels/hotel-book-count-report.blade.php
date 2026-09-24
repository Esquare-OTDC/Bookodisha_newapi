@extends('layouts.app')

@section('title','Hotel Book Count Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Hotel Book Count Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportWithBlockData" class="btn btn-info">Export with Blocked</button>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-booking-count-report') }}">
                    @csrf
                    <select id="hotel_id" class="form-control" name="hotel_id" required>
                    @foreach ($MasterHotel as $key => $value)
                        <option value="{{ $key }}"  {{ ($HotelId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                    </select>
                    <input type="text" class="form-control check-quantity" name="check_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" value="{{ date("d-m-Y", strtotime($start_date)) }}">
                    <select id="book_from" class="form-control" name="book_from" required>
                        <option value="all" {{ ($bookFrom == 'all') ? 'selected' : '' }}>All</option>
                        <option value="online" {{ ($bookFrom == 'online') ? 'selected' : '' }}>Online</option>
                        <option value="mmt" {{ ($bookFrom == 'mmt') ? 'selected' : '' }}>OTA</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th colspan="{{ count($MisData['rooms']) + 2 }}">Booking status of {{ $MisData['name'] }} as on ({{ date("d-m-Y h:i a") }})</th>
                            </tr>
                            <tr>
                                <th rowspan="2">Tent Capacity</th>
                                @foreach($MisData['rooms'] as $rname => $qty)
                                <th>{{ $rname }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                            <tr>
                                @php
                                $total = 0;
                                @endphp
                                @foreach($MisData['rooms'] as $rname => $qty)
                                <th>{{ $qty }}</th>
                                @php
                                $total += $qty;
                                @endphp
                                @endforeach
                                <th>{{ $total }}</th>
                            </tr>
                            <tr>
                                <th>Up to {{ date("d-m-Y", strtotime($start_date .' -1 days')) }}</th>
                                @php
                                $total = 0;
                                @endphp
                                @foreach($MisData['before'] as $qty)
                                <th>{{ $qty }}</th>
                                @php
                                $total += $qty;
                                @endphp
                                @endforeach
                                <th>{{ $total }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($MisData['data'] as $date => $details)
                            <tr>
                                <td>{{ date("d-m-Y", strtotime($date)) }}</td>
                                @php
                                echo '<td>'. implode('</td><td>', $details) .'</td>';
                                @endphp
                                <td>{{ array_sum(array_values($details)) }}</td>
                                
                            </tr>
                            @endforeach
                            <tr>
                                <th>Tent Night Sold</th>
                                @php
                                $totalSold = 0;
                                @endphp
                                @foreach($MisData['allBooked'] as $qty)
                                <th>{{ $qty }}</th>
                                @php
                                $totalSold += $qty;
                                @endphp
                                @endforeach
                                <th>{{ $totalSold }}</th>
                            </tr>
                            <tr>
                                <th>Tent Night Available</th>
                                @php
                                $totalAvailable = 0;
                                @endphp
                                @foreach($MisData['allInitial'] as $qty)
                                <th>{{ $qty }}</th>
                                @php
                                $totalAvailable += $qty;
                                @endphp
                                @endforeach
                                <th>{{ $totalAvailable }}</th>
                            </tr>
                            <tr>
                                @php
                                $percent = ($totalAvailable != 0 && $totalSold != 0) ? round(($totalSold / $totalAvailable) * 100, 2) : 0;
                                @endphp
                                <th colspan="{{ count($MisData['rooms']) + 2 }}">% of Occupancy {{ $percent }}</th>
                            </tr>
                        </tbody>	
                        
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<style type="text/css">
    
</style>

<script type="text/javascript">
    $(document).ready(function () {

        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            format: "dd-mm-yyyy",
            todayHighlight: true,
        });

        $(document).on('click', '#resetSession', function () {
            window.location = window.location.pathname;
        });
        
        $(document).on('click', '#exportData', function () {
            let hotelId = $("#hotel_id").val();
            let check_date = $("#datepicker-autoclose").val();
            let book_from = $("#book_from").val();
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_hotel_book_count_report", hotelId: hotelId, check_date: check_date, book_from: book_from},
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
            let check_date = $("#datepicker-autoclose").val();
            let book_from = $("#book_from").val();
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_hotel_book_count_with_block", hotelId: hotelId, check_date: check_date, book_from: book_from},
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