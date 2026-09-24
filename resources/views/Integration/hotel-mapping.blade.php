@extends('layouts.app')

@section('title','Hotel Mapping')

@section('content')

@if(Session::has('hotel_code'))
@php
    $Hotel_code = Session::get('hotel_code')
@endphp
@endif
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">MMT Integration</li>
                <li class="breadcrumb-item active">Map Hotel</li>
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
    @if(Session::has('error'))
        <p style="color: #ff0000; text-align: center;">
            {{ Session::get('error') }}
            @php
                Session::forget('error');
            @endphp
        </p>
    @endif
    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <form class="form-horizontal" id="mappingRequestForm" action="{{ route('hotel-mapping-request') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Enter Hotel Code</label>
                        <input type="text" class="form-control" name="hotel_code" value="{{ (Session::has('hotel_code')) ? Session::get('hotel_code') : (!empty($hotel_code) ? $hotel_code : '') }}" {{ !empty($hotel_code) ? 'readonly' : '' }}>
                        @if ($errors->has('hotel_code'))
                        <span class="text-danger">{{ $errors->first('hotel_code') }}</span>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
            @if(Session::has('RoomList'))
                @php
                    $RoomList = Session::get('RoomList');
                @endphp
                <div class="white-box">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Hotel Code</th>
                            <th>Room Type Name</th>
                            <th>Room Type Code</th>
                            <th>Active</th>
                            <th>Base Adult Occupancy</th>
                            <th>Max Adult Occupancy</th>
                            <th>Base Child Occupancy</th>
                            <th>Max Child Occupancy</th>
                        </thead>	
                        <tbody>
                            @php
                                $room_array = array();
                            @endphp
                            @foreach($RoomList as $room)
                            @php
                                $room_array[$room->room_type_code] = $room->room_type_name;
                            @endphp
                            <tr>
                                <td>{{ $room->hotel_code }}</td>
                                <td>{{ $room->room_type_name }}</td>
                                <td>{{ $room->room_type_code }}</td>
                                <td>{{ $room->is_active }}</td>
                                <td>{{ $room->base_adult_occupancy }}</td>
                                <td>{{ $room->max_adult_occupancy }}</td>
                                <td>{{ $room->base_child_occupancy }}</td>
                                <td>{{ $room->max_child_occupancy }}</td>
                            </tr>
                            @endforeach
                    </table>
                    <!--<button class="btn btn-primary" id="map-hotel">Map To Hotel</button><br>-->
                    <div id="map-detail" class="m-t-20" style="display:none;">
                        <form id="mappingForm">
                            <input type="hidden" name="hotelCode" id="hotelCode" value="{{ $Hotel_code }}" >
                            <div class="row">
                                <div class="col-md-12 text-center"><h3>Select Hotel</h3></div>
                                <div class="col-md-6"><b>Hotel Code: {{ $Hotel_code }}</b></div>
                                <div class="col-md-6">
                                    <select id="hotel_id" name="hotel_id" class="form-control">
                                        <option value="">Select Hotel</option>
                                        @foreach($MasterHotel as $hotel)
                                        @php
                                        $selected = ($Hotel_code == $hotel->mmt_hotel_id) ? 'selected' : '';
                                        @endphp
                                        <option value="{{ $hotel->id }}" {{ $selected }}>{{ $hotel->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row m-t-5">
                                <div class="col-md-12 text-center"><h3>Select Rooms</h3></div>
                                <input type="hidden" name="room_length"  value="{{ count($room_array) }}">
                                @php $count = 1; @endphp
                                @foreach($room_array as $key => $val)
                                <input type="hidden" name="room_code{{ $count }}" id="room_code{{ $count }}" value="{{ $key }}">
                                <div class="col-md-6"><b>{{ $val .' ('. $key .')' }}</b></div>
                                <div class="col-md-6">
                                    <select id="room_id{{ $count }}" name="room_id{{ $count }}" class="form-control rooms"></select>
                                    </select>
                                </div>
                                <div class="col-md-12">&nbsp;</div>
                                @php $count++; @endphp
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-primary" id="saveMapData">Submit</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('hotelId')) {
            $('form#mappingRequestForm').submit();
        }
        
        let hotel_code = $("#hotelCode").val();
        if(typeof hotel_code != "undefined") {
            getData(hotel_code);
        }
            
        $(document).on('click', '#map-hotel', function () {
            let hotel_code = $("#hotelCode").val();
            $(this).hide();
            getData(hotel_code);
            
        });
        
        $(document).on('change', '#hotel_id', function () {
            let hotel_code = $("#hotelCode").val();
            let hotel_id = $(this).val();
            if(hotel_id == '') {
                $(".rooms").each(function() {
                    let html = '';
                    html += '<option value="">Select Room</option>';
                    $(this).html(html);
                });
            } else {
                getData(hotel_code, hotel_id);
            }
        });
        
        $(document).on('click', '#saveMapData', function () {
            let rooms = [];
            $(".rooms").each(function() {
                if ($(this).val() != '') {
                    rooms.push($(this).val());
                }
            });
            var chk = (new Set(rooms)).size !== rooms.length;
            
            if (chk) {
                alert('A room code can not be assigned to multiple rooms. Please change room & try again.');
            } else {
                let formData = $("#mappingForm").serialize();
                $.ajax({
                    type: "POST",
                    url: "{{url('save-mapping-data')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: $("#mappingForm").serialize(),
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        alert(responce.message);
                    }
                });
            }
        });
        
        function getData(hotel_code, hotel_id = '') {
            let room_length = {{ isset($room_array) ? count($room_array) : 0 }};
            let postData = {hotel_code: hotel_code, request_type: 'get_data_for_hotelcode'};
            if(hotel_id != '') {
                postData.hotel_id = hotel_id;
            }
            $.ajax({
                type: "POST",
                url: "{{url('mapping-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: postData,
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        var HotelRoom = responce.data;
                        if (HotelRoom.length > 0) {
                            $(".rooms").each(function() {
                                let html = '';
                                html += '<option value="">Select Room</option>';
                                var id = $(this).attr('id').replace('room_id','');
                                let room_code = $("#room_code"+ id).val();
                                for(let i = 0; i < HotelRoom.length; i++) {
                                    let checked = (HotelRoom[i].mmt_room_id == room_code) ? 'selected' : '';
                                    html += '<option value="'+ HotelRoom[i].id +'" '+ checked +'>'+ HotelRoom[i].title +'</option>';
                                }
                                $("#room_id"+ id).html(html);
                            });
                        }
                        $("#map-detail").show();
                    }
                }
            });
        }
    });    
</script>

@endsection