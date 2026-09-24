@extends('layouts.app')

@section('title','Tour Order Shift')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Orders</li>
                <li class="breadcrumb-item active">Tour Order Shift</li>
            </ol>
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
                <table class="display nowrap table table-hover table-bordered">
                        <tr>
                            <th>Booking Id</th>
                            <th>Hotel</th>
                            <th>Tour Date</th>
                            <th>Customer Name</th>
                            <th>Customer Email</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <td>{{ $OrderMasterData->invoice_id }}</td>
                            <td>{{ $OrderMasterData->service_name }}</td>
                            <td>{{ date("M d Y", strtotime($OrderMasterData->start_date)) }}</td>
                            <td>{{ $OrderMasterData->customer_name }}</td>
                            <td>{{ $OrderMasterData->customer_email }}</td>
                            <td>{{ $OrderMasterData->status }}</td>
                        </tr>
                </table>
                <label>Select Cancellation Type</label>
                <select id="cancelType" class="form-control">
                    <option value="">Select Cancellation Type</option>
                    <option value="shift-date">Change Booking Date</option>
                </select>
                <div class="row m-t-40">
                    <div class="col-md-12 cancelElements" id="shiftElement" style="display: none;">
                        <label>Select Check-in Date</label>
                        <input type="text" class="form-control" name="start_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy">
                        <button type="button" class="btn btn-primary m-t-10" id="checkAvailability">Check Availability and Book</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    
</style>

<script type="text/javascript">
    $(document).ready(function () {
        var book_date = "<?= date("Y-m-d", strtotime($OrderMasterData->created_at)) ?>";
        var start_date = "<?= $OrderMasterData->start_date ?>";
        var end_date = "<?= $OrderMasterData->end_date ?>";
        var orderId = "<?= $OrderMasterData->order_id ?>";
        var orderStatus = "<?= $OrderMasterData->status ?>";
        var orderMasterId = "<?= $OrderMasterData->id ?>";
        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            format: "dd-mm-yyyy",
            todayHighlight: true,
            startDate: new Date(book_date),
            endDate: '+120d'
        });
        
        $(document).on('change', '#cancelType', function () {
            let cancelType = $(this).val();
            if (cancelType == 'shift-date') {
                $(".cancelElements").hide();
                $("#shiftElement").show();
                $('#datepicker-autoclose').focus();
            }
            else if (cancelType == 'cancel-date') {
                $(".cancelElements").hide();
                $('.input-daterange-datepicker').daterangepicker({
                    autoApply: true,
                    startDate: new Date(start_date),
                    endDate: new Date(end_date),
                    minDate: new Date(start_date),
                    maxDate: new Date(end_date),
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
                $("#cancelDateElement").show();                
            }
            else if (cancelType == 'cancel-room') {
                $(".cancelElements").hide();
                $("#cancelRoomElement").show();
            }
            else if (cancelType == 'change-room-quantity') {
                $(".cancelElements").hide();
                $("#changeQuantityElement").show();
            } else {
                $(".cancelElements").hide();
            }
        });
        
        $(document).on('click', '#checkAvailability', function () {
            let startDate = $('#datepicker-autoclose').val();
            if (startDate != '') {
                if (confirm('Are you sure want to change booking date ?')) {
                    $.ajax({
                        type: "POST",
                        url: "{{url('tour-cancel-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {orderId: orderMasterId, startDate: startDate, orderStatus: orderStatus, request_type: "shift_order_date"},
                        success: function (data) {
                            var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                                alert(responce.message);
                            } else {
                                alert(responce.message);
                                location.href = "{{ url('tour-orders') }}";
                            }
                        }
                    });
                }
            } else {
                alert("Please enter a valid date!");
            }
        });
        
        $(document).on('click', '#cancelDate', function () {
            let checkDate = $('#check_date').val().split(' - ');
            let start = moment(checkDate[0]);
            let end = moment(checkDate[1]);
            let sDate = moment(start_date);
            let eDate = moment(end_date);
            
            if (((start.diff(sDate, 'days') == 0) || (eDate.diff(end, 'days') == 0)) && ((start.diff(sDate, 'days') != 0) || (eDate.diff(end, 'days') != 0))) {
                if (confirm('Are you sure want to cancel booking date ?')) {
                    $.ajax({
                        type: "POST",
                        url: "{{url('hotel-cancel-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {orderId: orderMasterId, startDate: checkDate[0], endDate: checkDate[1], orderStatus: orderStatus, request_type: "cancel-date"},
                        success: function (data) {
                            var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                                alert(responce.message);
                            } else {
                                alert(responce.message);
                                location.href = "{{ url('hotel-orders') }}";
                            }
                        }
                    });
                }
            } else if ((start.diff(sDate, 'days') == 0) && (eDate.diff(end, 'days') == 0)) {
                alert('check-in date and check-out date are same as per the existing order. Please change date and try again.');
            } else {
                alert('Either check-in date or check-out date should be same as check-in or check-out date of the existing order.');
            }            
        });
        
        $(document).on('click', '#cancelRoom', function () {
            let roomData = [];
            if ($('.itemcheck:checked').length == $('.itemcheck').length) {
                alert("You can't select all room categories!");
            } else if ($('.itemcheck:checked').length == 0) {
                alert("Please select at-least one room category!");
            } else {
                if (confirm('Are you sure want to cancel selected room category ?')) {
                    $("input:checkbox[class=itemcheck]:checked").each(function() {
                        roomData.push($(this).val());
                    });
                    $.ajax({
                        type: "POST",
                        url: "{{url('hotel-cancel-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {orderId: orderMasterId, roomData: roomData, orderStatus: orderStatus, request_type: "cancel-room"},
                        success: function (data) {
                            var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                                alert(responce.message);
                            } else {
                                alert(responce.message);
                                location.href = "{{ url('hotel-orders') }}";
                            }
                        }
                    });
                }                
            }
        });
        
        $(document).on('click', '#changeRoomQuantity', function () {
            let roomData = [];
            if ($('.quantitycheck:checked').length == $('.quantitycheck').length) {
                alert("You can't select all rooms!");
            } else if ($('.quantitycheck:checked').length == 0) {
                alert("Please select at-least one room.");
            } else {
                if (confirm('Are you sure want to cancel selected rooms ?')) {
                    $("input:checkbox[class=quantitycheck]:checked").each(function() {
                        roomData.push($(this).val());
                        // roomData.push({roomId: $(this).val(), adult: $(this).data('adult'), child: $(this).data('child')});
                    });
                    $.ajax({
                        type: "POST",
                        url: "{{url('hotel-cancel-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {orderId: orderMasterId, roomData: roomData, orderStatus: orderStatus, request_type: "change-room-quantity"},
                        success: function (data) {
                            var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                                alert(responce.message);
                            } else {
                                alert(responce.message);
                                location.href = "{{ url('hotel-orders') }}";
                            }
                        }
                    });
                }
            }
        });
    });
</script>

@endsection