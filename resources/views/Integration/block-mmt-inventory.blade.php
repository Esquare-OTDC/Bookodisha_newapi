@extends('layouts.app')

@section('title','Block MMT Inventory')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Manage Hotel Room Block</div>
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
                
                        <form action="{{ route('block-mmt-request') }}"  method="POST" id="availabilityForm">
                            @csrf
                            <input type="hidden" name="hotel_name" id="hotel_name">
                            <input type="hidden" name="room_name" id="room_name">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="hotelSection">
                                            <label class="control-label">Hotel</label><span class="required_field">*</span>
                                            <select class="form-control" id="hotel" name="hotel_id" required value="{{ old('hotel_id') }}">
                                                <option value="">Select Hotel</option>
                                                @foreach ($MasterHotel as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('hotel_id'))
                                                <span class="text-danger">{{ $errors->first('hotel_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Platform</label><span class="required_field">*</span>
                                            <select class="form-control check-quantity" id="platform" name="platform" required value="{{ old('platform') }}">
                                                <option value="">Select Platform</option>
                                                <option value="mmt">MakeMyTrip</option>
                                                <option value="cleartrip">Cleartrip</option>
                                                <option value="all">All</option>
                                            </select>
                                            @if ($errors->has('platform'))
                                                <span class="text-danger">{{ $errors->first('platform') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row single-data" id="roomSection">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Room</label><span class="required_field">*</span>
                                            <div id="rooms">
                                                <select class="form-control check-quantity" id="room" name="room_id" required value="{{ old('room_id') }}">
                                                    <option value="">Select Room</option>
                                                </select>
                                            </div>
                                            
                                            @if ($errors->has('room_id'))
                                                <span class="text-danger">{{ $errors->first('room_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Date</label><span class="required_field">*</span>
                                            <div class="input-group" id="blockDateDiv">
                                                <input class="form-control input-daterange-datepicker check-quantity" id="check_date" type="text" name="block_date">
                                            </div>
                                            @if ($errors->has('block_date'))
                                                <span class="text-danger">{{ $errors->first('block_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="futureBlock"></div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Block Reason</label>
                                            <input type="text" class="form-control" name="block_reason" id="block_reason" value="{{ old('block_reason') }}">
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
                                <a href="{{url('blocked-mmt-inventory')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        width: 380px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 380px !important;
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

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment(),
            minDate: moment(),
            // maxDate: moment().add('+120','days'),
            locale: {
                format: 'DD-MM-YYYY'
            }
        });
        
        $(document).on('change', '#hotel', function () {
            $("#futureBlock").html('');
            $("#qunatityBlock").hide();
            let hotelId = $(this).val();
            $.ajax({
                type: "POST",
                url: "{{url('mmt-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {hotelId: hotelId, request_type: "get_hotel_rooms_active"},
                success: function (data) {
                    // $('#room').html('<option value="">Select Room</option>'+ data);
                    // $("#roomSection").html('<div class="col-md-12"><div class="form-group"> <label class="control-label">Room</label><span class="required_field">*</span> <select class="form-control check-initial" id="rooms" name="room_id[]" multiple="multiple" required></select></div></div>');
                    $('#rooms').html('<select class="form-control check-quantity" id="room" name="room_id[]" multiple="multiple" required>'+ data +'</select>');
                    $('#room').multiselect({
                        includeSelectAllOption: true,
                    });
                    $(".multiselect-selected-text").text('Select Rooms');
                }
            });
        });

        $(document).on('change', '.check-quantity', function () {
            $("#qunatityBlock").show();
            let roomId = $("#room").val();
            let date = $("#check_date").val();
            // let blockType = $('input[name="room_id"]:checked').val();
            let roomArray = $('#room option:selected').toArray().map(item => item.text);
            // console.log(text);return;
            if (roomId != '' && date != '') {
                $("#futureBlock").html('');
                $.ajax({
                    type: "POST",
                    url: "{{url('mmt-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {roomId: roomId,date: date, request_type: "get_room_quantity"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert('Unable to get inventory for the date.');
                        } else {
                            let inventory = responce.data;
                            let html = '';
                            for (let i = 0; i < inventory.length; i++) {
                                html += '<div class="col-md-12 table-responsive"><label>'+ roomArray[i] +'</label><table class="display nowrap table table-hover table-bordered">';
                                let row1 = '<tr>';
                                let row2 = '<tr>';
                                for (const key in inventory[i]) {
                                    row1 += '<td>'+ moment(new Date(key)).format('DD-MM-YYYY') +'</td>';
                                    row2 += '<td>'+ inventory[i][key] +'</td>';
                                }
                                html += row1 +'</tr>'+ row2  +'</tr></table></div>';
                            }
                            console.log(html);
                            $("#futureBlock").html(html);
                        }
                    }
                });
            }
        });

        $(document).on('change', '#room', function () {
            let roomNames = $('#room option:selected').toArray().map(item => item.text).join();
            $('#room_name').val(roomNames);
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