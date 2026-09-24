@extends('layouts.app')

@section('title','Hotel GST Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">MIS Report</li>
                <li class="breadcrumb-item active">Hotel GST Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
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
                <form class="form-inline" id="filterForm" method="get" action="{{ route('hotel-gst-report') }}">
                    @csrf
                    <select id="hotel_id" class="form-control" name="hotel_id" required>
                        <option value="0">All Hotels</option>
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}"  {{ ($hotelId == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    <select id="report_type" class="form-control" name="report_type" required>
                        <option value="start_date" {{ ($date_type == 'OD.start_date') ? 'selected' : '' }}>Checkin Date</option>
                        <option value="created_at" {{ ($date_type == 'OD.created_at') ? 'selected' : '' }}>Booking Date</option>
                    </select>
                    <select id="gst_percent" class="form-control" name="gst_percent" required>
                        <option value="0">All %</option>
                        <option value="12" {{ ($gst_percent == '12') ? 'selected' : '' }}>12%</option>
                        <option value="18" {{ ($gst_percent == '18') ? 'selected' : '' }}>18%</option>
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button> &nbsp; <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Booking Id</th>
                                <th>Invoice Slno</th>
                                <th>Hotel Name</th>
                                <th>Room Type</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Book Date</th>
                                <th>Sub Total Amount</th>
                                <th>GST Percent</th>
                                <th>GST Amount</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($MisData as $val)
                            <tr>
                                <td>{{ $val->invoice_id }}</td>
                                <td>{{ $val->invoice_serial }}</td>
                                <td>{{ $val->service_name }}</td>
                                <td>{{ $val->service_item_name }}</td>
                                <td>{{ date("d-m-Y", strtotime($val->start_date)) }}</td>
                                <td>{{ date("d-m-Y", strtotime($val->end_date)) }}</td>
                                <td>{{ date("d-m-Y h:i a", strtotime($val->created_at)) }}</td>
                                <td>{{ $val->sub_toal }}</td>
                                <td>{{ (int)$val->tax_percentage }}</td>
                                <td>{{ $val->tax_amount }}</td>
                                <td>{{ $val->total_room_price }}</td>
                            </tr>                            
                            @endforeach
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
            let report_type = $("#report_type").val();
            let gst_percent = $("#gst_percent").val();
            let hotel_id = $("#hotel_id").val();
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_hotel_gst_report", hotel_id: hotel_id, report_type : report_type, gst_percent: gst_percent, startDate: "{{ $start_date }}", endDate: "{{ $end_date }}"},
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