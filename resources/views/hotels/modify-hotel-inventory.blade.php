@extends('layouts.app')

@section('title','Modify Hotel Inventory')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Modify Hotel Inventory</div>
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
                
                        <form action="{{ route('modify-inventory-request') }}"  method="POST" id="availabilityForm">
                            @csrf
                            
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
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
                                </div>                                
                                <div class="row single-data" id="roomSection">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Room</label><span class="required_field">*</span>
                                            <select class="form-control check-quantity" id="room" name="room_id" required value="{{ old('room_id') }}">
                                                <option value="">Select Room</option>
                                            </select>
                                            @if ($errors->has('room_id'))
                                                <span class="text-danger">{{ $errors->first('room_id') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Choose to Increase / Decrease</label><span class="required_field">*</span>
                                            <select class="form-control check-quantity" id="room" name="operation" required value="{{ old('operation') }}">
                                                <option value="">Select</option>
                                                <option value="increase">Increase</option>
                                                <option value="decrease">Decrease</option>
                                            </select>
                                            @if ($errors->has('room_id'))
                                                <span class="text-danger">{{ $errors->first('room_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
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
                                
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Room quantity</label><span class="required_field">*</span>
                                            <input type="number" class="form-control" name="quantity" id="quantity" min="0" required value="{{ old('quantity') }}">
                                            <span class="qty-msg text-info"></span>
                                            @if ($errors->has('quantity'))
                                                <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="futureBlock">
                                    
                                </div>
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('manage-hotel-inventory')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment(),
            // minDate: moment(),
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
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {hotelId: hotelId, request_type: "get_hotel_rooms_active_quantity"},
                success: function (data) {
                    $('#room').html('<option value="">Select Room</option>'+ data);
                    $('#hotel_name').val($("#hotel option:selected" ).text());
                }
            });
        });

        $(document).on('change', '.check-quantity', function () {
            $("#qunatityBlock").show();
            let roomId = $("#room").val();
            let date = $("#check_date").val();
            let blockType = $('input[name="block_type"]:checked').val();
            if (roomId != '' && date != '') {
                $("#futureBlock").html('');
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
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
                            let html = '<div class="col-md-12 table-responsive"><label>Available Rooms</label><table class="display nowrap table table-hover table-bordered">';
                            let row1 = '<tr>';
                            let row2 = '<tr>';
                            for (const key in inventory) {
                                row1 += '<td>'+ moment(new Date(key)).format('DD-MM-YYYY') +'</td>';
                                row2 += '<td>'+ inventory[key] +'</td>';
                            }
                            html += row1 +'</tr>'+ row2  +'</tr><table></div>';
                            $("#futureBlock").html(html);
                        }
                    }
                });
            }
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
                'quantity': {
                    required: true
                }
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
                'quantity': {
                    required: "Quantity is required"
                }
            }
        });
    });
</script>

@endsection