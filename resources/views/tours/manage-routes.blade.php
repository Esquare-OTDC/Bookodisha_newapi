@extends('layouts.app')

@section('title','Manage Tour Route')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Tours</li>
                <li class="breadcrumb-item active">Manage tour route</li>
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
                <div class="row">
                    <div class="col-sm-12">
                        <form class="form-horizontal" method="POST" action="{{ route('tour-routes-request') }}">
                            @csrf
                            <input type="hidden" name="id" id="tourId" value="{{ $TourId }}">
                            <input type="hidden" id="itinerary">
                            <label>Itinerary</label>
                            <select class="form-control" name="route_map" id="route_map">
                                @foreach ($itinerary as $key => $value)
                                <option value="{{ $value['title'] }}">{{ $value['title'] }}</option>
                                @endforeach
                            </select><br>
                            <table class="display nowrap table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Address</th>
                                        <th style="width:15%;">Latitude</th>
                                        <th style="width:15%;">Longitude</th>
                                        <th style="width:7%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="routeContent">
                                    <?php if(!empty($default_map)) {
                                        $count = 0;
                                        foreach ($default_map as $keys => $values) { ?>
                                            <tr id="route{{ $count }}">
                                                <td>
                                                    <input id="autocomplete{{ $count }}" type="text" class="form-control autocomplete" name="route[{{ $count }}][address]" value="{{ $values['address'] }}" onFocus="geolocate(this.id)">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" id="latautocomplete{{ $count }}" name="route[{{ $count }}][map_lat]" value="{{ $values['map_lat'] }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" id="lngautocomplete{{ $count }}" name="route[{{ $count }}][map_lng]" value="{{ $values['map_lng'] }}" readonly>
                                                </td>
                                                <td><i class="btn btn-danger btn-sm deleteRoute fa fa-trash" id="j{{ $count }}"></i>
                                                </td>
                                            </tr>
                                        <?php $count++; ?>
                                        <!--<script>initAutocomplete('autocomplete<?= $count ?>');</script>-->
                                    <?php }
                                    } ?>
                                </tbody>
                            </table> 
                            <span class="btn btn-info btn-sm" id="addNewRoute" data-id="'+ length2 +'" style="float: right;"><i class="icon-plus"></i> Add Route</span>
                            <button type="submit" class="btn btn-primary" id="saveData">Save</button>
                        </form>
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
        let defaultData = <?= json_encode($default_map) ?>;
        if(defaultData.length > 0){
            for (let i = 0; i < defaultData.length; i++) {
                initAutocomplete('autocomplete'+ i);
            }
        }
        let itinerary = $("#route_map").val();
        $("#itinerary").val(itinerary);        
        
        var length = (defaultData.length > 0) ? defaultData.length : 0;
        $(document).on('click', '#addNewRoute', function () {
            length++;
            $("#routeContent").append('<tr id="route'+ length +'"><td><input id="autocomplete'+ length +'" type="text" class="form-control autocomplete" name="route['+ length +'][address]" onFocus="geolocate(this.id)"></td><td><input type="text" class="form-control" id="latautocomplete'+ length +'" name="route['+ length +'][map_lat]" readonly></td><td><input type="text" class="form-control" id="lngautocomplete'+ length +'" name="route['+ length +'][map_lng]" readonly></td><td><i class="btn btn-danger btn-sm deleteRoute fa fa-trash" id="j'+ length +'"></i></td></tr>');
            initAutocomplete('autocomplete'+ length);
        });
        
        $(document).on('click', '.deleteRoute', function () {
            var id = $(this).attr('id').replace('j','');
            $("tr").remove("#route"+id);
        });
                
        $('#route_map').on('change',function() {
            $("#itinerary").val($(this).val());
            let route = $(this).val();
            let tourId = $("#tourId").val();
            if (route != '' && tourId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {route: route, tourId: tourId, request_type: "get_tour_route"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        let Data = responce.data;
                        length = Data.length;
                        $("#routeContent").html('');
                        for (let j = 0; j < Data.length; j++) {
                            $("#routeContent").append('<tr id="route'+ j +'"><td><input id="autocomplete'+ j +'" type="text" class="form-control autocomplete" value="'+ Data[j]['address'] +'" name="route['+ j +'][address]" onFocus="geolocate(this.id)"></td><td><input type="text" class="form-control" id="latautocomplete'+ j +'" name="route['+ j +'][map_lat]" value="'+ Data[j]['map_lat'] +'" readonly></td><td><input type="text" class="form-control" id="lngautocomplete'+ j +'" name="route['+ j +'][map_lng]" value="'+ Data[j]['map_lng'] +'" readonly></td><td><i class="btn btn-danger btn-sm deleteRoute fa fa-trash" id="j'+ j +'"></i></td></tr>');
                            initAutocomplete('autocomplete'+ j);
                        }
                    }
                });
            }
        });
        
    });
    
    var autoId = '';
    let placeSearch;
    let autocomplete = [];
    const componentForm = {};            
    function geolocate(id) {
        autoId = id;
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const geolocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                const circle = new google.maps.Circle({
                    center: geolocation,
                    radius: position.coords.accuracy,
                });
                autocomplete.setBounds(circle.getBounds());
            });
        }
    }

    function initAutocomplete(id) {
        autoId = id;
        autocomplete = new google.maps.places.Autocomplete(document.getElementById(id), {
                types: ['address'],
                componentRestrictions: {
                        country: ['IN']
                }
        });
        autocomplete.setFields(["address_component", "geometry"]);
        autocomplete.addListener("place_changed", fillInAddress);
    }

    function fillInAddress() {
        const place = autocomplete.getPlace();
        document.getElementById("lat"+autoId).value = place.geometry.location.lat();
        document.getElementById("lng"+autoId).value = place.geometry.location.lng();
    }
</script>

@endsection