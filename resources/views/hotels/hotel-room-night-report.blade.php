@extends('layouts.app')

@section('title','Hotel Room Night Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active"> Hotel Room Night Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportCancel" class="btn btn-info">Export Cancel</button>
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-room-night-report') }}">
                    @csrf
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                @foreach($MisData['hotels'] as $hotels)
                                <th>{{ $hotels }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>	
                        <tbody>
                            @foreach($MisData['data'] as $date => $val)
                            <tr>
                                <td>{{ date("d-M-Y", strtotime($date)) }}</td>
                                @php
                                $total = 0;
                                @endphp
                                @foreach($val as $qtys)
                                @php
                                $total += $qtys;
                                @endphp
                                <td>{{ $qtys }}</td>
                                @endforeach
                                <td>{{ $total }}</td>
                            </tr>                            
                            @endforeach
                            <tr>
                                <th>Total</th>
                                @foreach($MisData['total'] as $qtys)
                                <th>{{ $qtys }}</th>
                                @endforeach
                                <th>{{ $MisData['grandTotal'] }}</th>
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
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_hotel_room_night_report", startDate: "{{ $start_date }}", endDate: "{{ $end_date }}"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        window.location = responce.file_path;
                    }
                    // var downloadLink = document.createElement("a");
                    // var blob = new Blob(["\ufeff", data]);
                    // var url = URL.createObjectURL(blob);
                    // downloadLink.href = url;
                    // downloadLink.download = "Datewise_hotel_book_report_{{ date('Y-m-d h-i-a') }}.csv";  //Name the file here
                    // document.body.appendChild(downloadLink);
                    // downloadLink.click();
                    // document.body.removeChild(downloadLink);
                }
            });
        });

        $(document).on('click', '#exportCancel', function () {
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_cancel_room_night_report", startDate: "{{ $start_date }}", endDate: "{{ $end_date }}"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        window.location = responce.file_path;
                    }
                    // var downloadLink = document.createElement("a");
                    // var blob = new Blob(["\ufeff", data]);
                    // var url = URL.createObjectURL(blob);
                    // downloadLink.href = url;
                    // downloadLink.download = "Datewise_hotel_book_report_{{ date('Y-m-d h-i-a') }}.csv";  //Name the file here
                    // document.body.appendChild(downloadLink);
                    // downloadLink.click();
                    // document.body.removeChild(downloadLink);
                }
            });
        });
    });
    
</script>

@endsection