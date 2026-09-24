@extends('layouts.app')

@section('title','Hotel Room MIS Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Hotel Room MIS Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportInitial" class="btn btn-info">Export Initials</button>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotelroom-mis-report') }}">
                    @csrf
                    <input type="hidden" name="filter" value="filter"> 
                    <select id="hotel" class="form-control" name="hotel_id" required>
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
                            <th colspan="{{ count($hotels['rooms']) + 2 }}">{{ $hotel }}</th>
                        </tr>
                        <tr>
                            <th>Date</th>
                            @foreach($hotels['rooms'] as $rooms)
                            <th>{{ $rooms }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                        @foreach($hotels['data'] as $key => $val)
                        <tr>
                            <td>{{ date("d-M-Y, (l)", strtotime($key)) }}</td>
                            @php
                            $tot = $book = $avail = 0;
                            @endphp
                            @foreach($hotels['data'][$key] as $qtys)
                            @php
                            $tot += $qtys['initial'];
                            $book += $qtys['booked'];
                            $avail += $qtys['available'];
                            $color = "dark";
                            if ($qtys['initial'] == 0 || (($qtys['available'] / $qtys['initial']) * 100 <= 10)) {
                                $color = "danger";
                            }
                            @endphp                            
                            <td>{{ $qtys['booked'] .'/'. $qtys['initial'] }} Avail: <span class="text-{{ $color }}">{{ $qtys['available'] }}</span></td>
                            @endforeach
                            @php
                            $totColor = "dark";
                            if ($tot == 0 || (($avail / $tot) * 100 <= 10)) {
                                $totColor = "danger";
                            }
                            @endphp
                            <td>{{ $book .'/'. $tot }} Avail: <span class="text-{{ $totColor }}">{{ $avail }}</span></td>
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
            $(document.body).addClass('mini-sidebar');
            let hotelId = "<?= $HotelId ?>";
            let filter_date = $("#check_date").val();
            $(".sidebar").hide();
            $(".breadcrumb").hide();
            $("#filterForm").hide();
            $(".footer").hide();
            $(this).hide();
            window.print();
            $(this).show();
            $(".footer").show();
            $(".sidebar").show();
            $(".breadcrumb").show();
            $("#filterForm").show();
            // return;
            // $.ajax({
            //     type: "POST",
            //     url: "{{url('hotel-oprsn')}}",
            //     headers: {
            //         'X-CSRF-Token': '{{ csrf_token() }}',
            //     },
            //     data: {hotelId: hotelId, filter_date: filter_date, request_type: "export_hotel_room_mis_report"},
            //     success: function (data) {
            //         let url = data;
            //         window.open(url, 'window name', 'window settings');
            //         return false;

                    // var downloadLink = document.createElement("a");
                    // var blob = new Blob(["\ufeff", data]);
                    // var url = URL.createObjectURL(blob);
                    // downloadLink.href = url;
                    // downloadLink.download = "Hotel_room_mis_report_"+ new Date().getTime() +".csv";  //Name the file here
                    // document.body.appendChild(downloadLink);
                    // downloadLink.click();
                    // document.body.removeChild(downloadLink);
            //     }
            // });
        });
        
        $(document).on('click', '#exportInitial', function () {
            let hotel_id = $("#hotel").val();
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_room_initial_report", startDate: "{{ $start_date }}", endDate: "{{ $end_date }}", hotel_id: hotel_id},
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