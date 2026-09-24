@extends('layouts.app')

@section('title','Block Vehicle')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Block Vehicle</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        
                        @if(Session::has('success'))
                            <p class="flash" style="color: red; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                
                        <form action="{{ route('block-rental-vehicle-request') }}"  method="POST" id="availabilityForm">
                            @csrf
                            <div class="form-body">
                                <div class="row m-b-20" >
                                    <div class="col-md-12">
                                        <label class="control-label">Choose Block Type</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="block_type" name="block_type" id="block_type_single" value="single" checked> Block Single Vehicle
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="block_type" name="block_type" id="block_type_multiple" value="multiple"> Block Multiple Vehicle
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" id="hotelSection">
                                            <label class="control-label">vehicle</label><span class="required_field">*</span>
                                            <select class="form-control check-quantity" id="car_id" name="car_id" required value="{{ old('car_id') }}">
                                                <option value="">Select Vehicle</option>
                                                @foreach ($MasterCar as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('car_id'))
                                                <span class="text-danger">{{ $errors->first('car_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Date</label><span class="required_field">*</span>
                                            <div class="input-group" id="blockDateDiv">
                                                <input type="text" class="form-control check-quantity" name="block_date" id="datepicker-autoclose" placeholder="mm/dd/yyyy" required value="{{ old('block_date') }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>
                                            </div>
                                            @if ($errors->has('block_date'))
                                                <span class="text-danger">{{ $errors->first('block_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row  single-data" id="qunatityBlock">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Quantity</label><span class="required_field">*</span>
                                            <input type="number" class="form-control" name="quantity" id="quantity" min="0" required value="{{ old('quantity') }}">
                                            <span class="qty-msg text-info"></span>
                                            @if ($errors->has('quantity'))
                                                <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Block Reason</label>
                                            <input type="text" class="form-control" name="block_reason" id="block_reason" value="{{ old('block_reason') }}" required>
                                            @if ($errors->has('block_reason'))
                                                <span class="text-danger">{{ $errors->first('block_reason') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('rental-block-data')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</div>

<style>
    .multiselect-container > li > a > label.checkbox {
        color: #000 !important;
    }
    .multiselect-container > li > a > label {
        padding: 3px 3px 3px 10px;
    }
    .multiselect-clear-filter {
        background-color: #fff;
        margin-right: 5px;
        color: #b0b0b0;
    }
    .multiselect.dropdown-toggle.btn.btn-default {
        width: 500px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 500px !important;
        max-height: 250px;
        overflow-y: scroll;
    }
    .multiselect-container .input-group {
        margin: 4px 8px;
    }
    .input-group {
        width: 100% !important;
    }
    .dropdown-menu>.active>a, .dropdown-menu>.active>a:focus, .dropdown-menu>.active>a:hover {
        background-color: #fff;
    }
    label.checkbox {
        margin-left: 20px !important;
    }
    .checkbox input[type=checkbox] {
        opacity: 1;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {

        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            startDate: '-0m',
            endDate: '+120d'
        });
        
        $('.block_type').on('click',function() {
            let blockType = $('input[name="block_type"]:checked').val();
            if (blockType == 'single') {
                $("#hotelSection").html('<select id="car_id" name="car_id" class="form-control"><option value="">Select Vehicle</option><?= $carHtml ?></select>');
                $("#qunatityBlock").html('<div class="col-md-12"><div class="form-group"> <label class="control-label">Room quantity</label><span class="required_field">*</span> <input type="number" class="form-control" name="quantity" id="quantity" min="0" required> <span class="qty-msg text-info"></span></div></div>');
                $("#blockDateDiv").html('<input type="text" class="form-control check-quantity" name="block_date" id="datepicker-autoclose" placeholder="mm/dd/yyyy" required value="{{ old('block_date') }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>');
                $('#datepicker-autoclose').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    startDate: '-0m',
                    endDate: '+120d'
                });
            } else {
                let data = '<?= $carHtml ?>';
                $(".single-data").html('');
                $("#hotelSection").html('<select id="SelectVehicle" name="vehicles[]" class="form-control" multiple="multiple" required></select>');
                $('#SelectVehicle').html(data);
                $('#SelectVehicle').multiselect({
                    includeSelectAllOption: true,                            
                });
                $(".multiselect-selected-text").text('Select Vehicles');
                $("#blockDateDiv").html('<input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="block_date">');
                $('.input-daterange-datepicker').daterangepicker({
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment(),
                    minDate: moment(),
//                    maxDate: moment().add('+120','days'),
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
                
            }
        });
                        
        $(document).on('change', '#hotel', function () {
            $("#qunatityBlock").hide();
            let hotelId = $(this).val();
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {hotelId: hotelId, request_type: "get_hotel_rooms"},
                success: function (data) {
                    $('#room').html('<option value="">Select Room</option>'+ data);
                    $('#hotel_name').val($("#hotel option:selected" ).text());
                }
            });
        });

        $(document).on('change', '.check-quantity', function () {
            let carId = $("#car_id").val();
            let date = $("#datepicker-autoclose").val();
            if (carId != '' && date != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('car-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {carId: carId,date: date, request_type: "get_vehicle_quantity"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                        } else {
                            $("#quantity").attr("max", responce.quantity);
                            $("#quantity").val(responce.quantity);
                            $(".qty-msg").html("Maximum "+ responce.quantity +" quantity can be blocked");
                        }
                    }
                });
            }
        });
        
        $(document).on('change', '#room', function () {
            $('#room_name').val($("#room option:selected").text());
        });
        
        $(document).on('click', '.addData', function () {
            var x = validator.form();
            return x;
        });
        
        validator = $('#availabilityForm').validate({
            rules: {
                'hotel_id': {
                    required: true
                },
                'room_id': {
                    required: true
                },
                'block_date': {
                    required: true
                },
            },
            messages: {
                'hotel_id': {
                    required: "Please choose hotel"
                },
                'room_id': {
                    required: "Please choose room"
                },
                'block_date': {
                    required: "Please choose date"
                },
            }
        });
    });
</script>

@endsection