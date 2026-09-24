@extends('layouts.app')

@section('title','Block Sales')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Block Sales</div>
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
                
                        <form action="{{ route('add-sales-request') }}"  method="POST" id="availabilityForm">
                            @csrf
                            <input type="hidden" name="hotel_name" id="hotel_name">
                            <input type="hidden" name="rooms" id="rooms">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Hotel</label><span class="required_field">*</span>
                                            <select class="form-control" id="hotel" name="hotel_id[]" multiple="multiple" required value="{{ old('hotel_id') }}">
                                                <!-- <option value="">Select Hotel</option> -->
                                                @foreach ($MasterHotel as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('hotel_id'))
                                                <span class="text-danger">{{ $errors->first('hotel_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row" id="roomBlock" style="display:none;">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Room</label><span class="required_field">*</span>
                                            <div id="roomSelection">
                                                <select id="SelectRoom" class="form-control" multiple="multiple"></select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Date</label><span class="required_field">*</span>
                                                <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" required>
                                            @if ($errors->has('check_date'))
                                                <span class="text-danger">{{ $errors->first('check_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('hotel-sales')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        width: 95% !important;
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
        var all_rooms = [];

        $('#hotel').multiselect({
            includeSelectAllOption: true,
        });
        $(".multiselect-selected-text").text('Select Hotel');

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment().add('+121','days'),
            endDate: moment().add('+122', 'days'),
            minDate: moment().add('+121','days'),
//            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD MMM YYYY'
            }
        });
        
        $('#hotels').on('change',function() {
            $("#qunatityBlock").hide();
            let hotelId = $(this).val();
            if (hotelId != '') {
                $('#SelectRoom').html('');
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {hotelId: hotelId, request_type: "get_hotel_rooms_active"},
                    success: function (data) {
                        $('#hotel_name').val($("#hotel option:selected" ).text());
                        $('#roomSelection').html('<select id="SelectRoom" class="form-control" multiple="multiple"></select>');
                        $('#SelectRoom').html(data);
                        $('#SelectRoom').multiselect({
                            includeSelectAllOption: true,
                            
//                            enableFiltering: true
                        });
                        $(".multiselect-selected-text").text('Select Room');
                        $('#customSelectedRoom').hide();
                        $('#roomBlock').show();
                    }
                });
            } else {
                $('#roomBlock').hide();
            }
        });
        
        $(document).on('click', '.addData', function () {
            $("input:checkbox:checked").each(function () {
                var checkedValue = $(this).val();
                if (checkedValue != 'multiselect-all') {
                    all_rooms.push(checkedValue);
                }
            });
            $("#rooms").val(JSON.stringify(all_rooms));

            var x = validator.form();
            
            if (all_rooms.length == 0 && x) {
                alert("Please select atleast one room!");
                return false;                
            }
            return x;
        });
        
        validator = $('#availabilityForm').validate({
            rules: {
                'hotel_id': {
                    required: true
                },
                'check_date': {
                    required: true
                },
            },
            messages: {
                'hotel_id': {
                    required: "Please choose hotel"
                },
                
                'check_date': {
                    required: "Please choose date"
                },
            }
        });
    });
</script>

@endsection