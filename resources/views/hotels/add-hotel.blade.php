@extends('layouts.app')

@section('title', 'Add New Hotel')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Hotels</li>
                <li class="breadcrumb-item active">Add Hotel</li>
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
            <div class="header-section">
                <h2 id="PageHeading">Add New Hotel</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('hotel-add-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-9">
                        <div class="white-box">
                            <h3 class="box-title">Hotel Content</h3><hr>                        
                            <div class="form-group">
                                <label class="col-md-12" for="name">Title</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="name" placeholder="Name of the hotel" maxlength="100" required>
                                    @if ($errors->has('name'))
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="content">Content</label>
                                <div class="col-md-12">
                                    <textarea name="content" cols="10" rows="5" required></textarea>
                                    @if ($errors->has('content'))
                                    <span class="text-danger">{{ $errors->first('content') }}</span>
                                    @endif
                                </div>
                            </div>

<!--                            <div class="form-group">
                                <label class="col-md-12" for="name">Service Charge</label>
                                <div class="col-md-12">
                                    <input type="number" min="0" class="form-control" name="service_fee" placeholder="">
                                    @if ($errors->has('service_fee'))
                                    <span class="text-danger">{{ $errors->first('service_fee') }}</span>
                                    @endif
                                </div>
                            </div>-->
                            <div class="form-group mt-2">
                                <label class="col-md-12">Youtube Video</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="video" placeholder="Youtube Video Link">
                                    @if ($errors->has('video'))
                                    <span class="text-danger">{{ $errors->first('video') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Banner Image</label>
                                <div class="col-md-12">
                                    <input type="file" id="input-file-now" class="dropify" name="banner_image" required>
                                    @if ($errors->has('banner_image'))
                                    <span class="text-danger">{{ $errors->first('banner_image') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Gallery</label>
                                <div class="col-md-12">
                                    <div id="gallery-image" style="padding-top: .5rem;"></div>
                                    @if ($errors->has('images.*'))
                                    <span class="text-danger">{{ $errors->first('images.*') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Hotel Policy</h3><hr>
                            <div class="row">
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Hotel rating standard</label>
                                    <div class="col-md-6">
                                        <input type="number" class="form-control" min="0" max="5" name="star_rate" placeholder="Eg: 5">
                                        @if ($errors->has('star_rate'))
                                        <span class="text-danger">{{ $errors->first('star_rate') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group mt-2">
                                    <label class="col-md-12" for="name">Policy</label>
                                    <div class="col-md-12">
                                        <table class="display nowrap table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Title</th>
                                                    <th class="text-center" colspan="2">Content</th>
                                                </tr>
                                            </thead>
                                            <tbody id="policy-container">
                                            </tbody>
                                        </table>
                                        <span class="btn btn-info btn-sm" id="addNewRow" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Terms & Conditions</h3><hr>
                            <div class="row">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <textarea name="terms_conditions" cols="10" rows="5" required></textarea>
                                        @if ($errors->has('terms_conditions'))
                                        <span class="text-danger">{{ $errors->first('terms_conditions') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Check in/out time</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Time for check in</label>
                                    <div class="input-group clockpicker " data-placement="bottom" data-align="top" data-autoclose="true">
                                        <input type="text" class="form-control" name="check_in_time" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                        @if ($errors->has('check_in_time'))
                                        <span class="text-danger">{{ $errors->first('check_in_time') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Time for check out</label>
                                    <div class="input-group clockpicker " data-placement="bottom" data-align="top" data-autoclose="true">
                                        <input type="text" class="form-control" name="check_out_time" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                        @if ($errors->has('check_out_time'))
                                        <span class="text-danger">{{ $errors->first('check_out_time') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>                            
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Location</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label>District</label>
                                    <select class="form-control select2" name="city" required>
                                        <option value="">Select City</option>
                                        @foreach ($CityDetail as $value)
                                        <option value="{{ $value }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('city'))
                                    <span class="text-danger">{{ $errors->first('city') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label>Place (Use for location filter)</label>
                                    <input class="form-control" name="place" type="text" required>
                                    @if ($errors->has('place'))
                                    <span class="text-danger">{{ $errors->first('place') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Address</label>
                                    <input id="autocomplete" class="form-control" name="address" placeholder="Enter address" onFocus="geolocate()" type="text" />
                                    @if ($errors->has('address'))
                                    <span class="text-danger">{{ $errors->first('address') }}</span>
                                    @endif
                                    <input type="hidden" id="map_lat" name="map_lat" >
                                    <input type="hidden" id="map_lng" name="map_lng" >
                                </div>
                            </div>                            
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Contact Information</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Contact Email</label>
                                    <input type="email" class="form-control" name="contact_email" placeholder="Contact email" autocomplete="off" />
                                    @if ($errors->has('contact_email'))
                                    <span class="text-danger">{{ $errors->first('contact_email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Manager Name</label>
                                    <input type="text" class="form-control" name="manager_name" placeholder="Manager Name" autocomplete="off" />
                                    @if ($errors->has('manager_name'))
                                    <span class="text-danger">{{ $errors->first('manager_name') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Manager Contact Number</label>
                                    <input type="text" class="form-control numvalidate" name="contact_number" placeholder="Manager Contact Number" autocomplete="off" maxlength="10" required>
                                    @if ($errors->has('contact_number'))
                                    <span class="text-danger">{{ $errors->first('contact_number') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Reception Contact Number</label>
                                    <input type="text" class="form-control numvalidate" name="reception_contact" placeholder="Reception Contact Number" autocomplete="off" maxlength="10" required>
                                    @if ($errors->has('reception_contact'))
                                    <span class="text-danger">{{ $errors->first('reception_contact') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Additional Email</label>
                                    <input type="text" class="form-control" name="additional_email" placeholder="Additional Contact Email" autocomplete="off">
                                    @if ($errors->has('additional_email'))
                                    <span class="text-danger">{{ $errors->first('additional_email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Additional Contact Number</label>
                                    <input type="text" class="form-control" name="additional_phone" placeholder="Additional Contact Number" autocomplete="off">
                                    @if ($errors->has('additional_phone'))
                                    <span class="text-danger">{{ $errors->first('additional_phone') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Hotel address</label>
                                    <input class="form-control" name="real_address" placeholder="Enter real address" type="text" autocomplete="off" maxlength="100"/>
                                    @if ($errors->has('real_address'))
                                    <span class="text-danger">{{ $errors->first('real_address') }}</span>
                                    @endif
                                </div>
                            </div>                            
                        </div>
                        
                        @if (Auth::user()->role == 2)
                        <div class="white-box">
                            <h3 class="box-title">Assign Sub user</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <!--<label>Choose Sub user</label>-->
                                    <select id="subUser" name="sub_user[]" class="form-control" multiple="multiple">
                                        @foreach ($SubUser as $key => $val)
                                        <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('sub_user'))
                                    <span class="text-danger">{{ $errors->first('sub_user') }}</span>
                                    @endif
                                </div>
                            </div>                            
                        </div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <div class="white-box">
                            <h3 class="box-title">Publish</h3><hr>
                            <div class="form-group">
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio1" value="publish" checked>
                                    <label for="radio1">Publish</label>
                                </div>
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio2" value="draft">
                                    <label for="radio2">Draft</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="float: right;margin-top: -20px;">Submit</button>
                        </div>
                        <?php if (Auth::user()->access_type == 'superadmin') { ?>
                            <div class="white-box">
                                <h3 class="box-title">Vendor</h3><hr>
                                <select class="form-control" id="venderId" name="vender_id" required>
                                    <option value="">Select Vendor</option>
                                    @foreach ($Vendors as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('vender_id'))
                                <span class="text-danger">{{ $errors->first('vender_id') }}</span>
                                @endif
                            </div>
                        <?php } else { ?>
                            <input type="hidden" id="vendor" name="vender_id" class="form-control" value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                        <?php } ?>
                        <?php foreach ($HotelAttributes as $attrs => $terms) { ?>
                            <div class="white-box">
                                <div style="font-size: 15px;"><strong>Attribute: {{$attrs}}</strong></div><hr>
                                <div class="input-group">
                                    <ul class="icheck-list">
                                        <?php foreach ($terms as $key => $values) {
                                            if ($attrs == 'Property type') {
                                                ?>
                                                <li><input type="radio" class="check" name="property[{{ $attrs }}][]" value="{{ $key .'~'. $values }}" data-radio="iradio_square-blue"><label>{{ $values }}</label></li>
                                            <?php } else { ?>
                                                <li>
                                                    <input type="checkbox" class="check" name="property[{{ $attrs }}][]" value="{{ $key .'~'. $values }}" data-checkbox="icheckbox_flat-blue">
                                                    <label>{{ $values }}</label>
                                                </li>
                                            <?php } ?>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="white-box">
                            <h3 class="box-title">Feature Image</h3><hr>
                            <div class="form-group">
                                <input type="file" class="dropify" name="feature_image" required />
                                @if ($errors->has('feature_image'))
                                <span class="text-danger">{{ $errors->first('feature_image') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">GST Applicable</h3><hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="gst_applicable" value="1"> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="gst_applicable" value="0" checked> No </label>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">GST Number</h3><hr>
                            <div class="form-group">
                                <input type="text" name="gst_number" class="form-control" placeholder="Enter GST No">
                            </div>
                            <h3 class="box-title">Company Name</h3><hr>
                            <div class="form-group">
                                <input type="text" name="gst_legal_name" class="form-control" placeholder="Enter Company Name">
                            </div>
                        </div>
<!--                        <div class="white-box">
                            <h3 class="box-title">Paytm MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="paytm_mid" class="form-control" placeholder="Enter Paytm MID">
                            </div>
                            <h3 class="box-title">HDFC MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="hdfc_mid" class="form-control" placeholder="Enter HDFC MID">
                            </div>
                        </div>-->
                        <div class="white-box">
                            <h3 class="box-title">Show Price</h3><hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="1" checked> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="0"> No </label>
                                </div>
                            </div>
                        </div>    
                        <div class="white-box">
                            <h3 class="box-title">More than 100 nights in a month (%)</h3><hr>
                            <div class="form-group">
                                <input type="number" name="more_nights" class="form-control numvalidate" min="0" max="99" value="{{ old('more_nights') }}">
                                @if ($errors->has('more_nights'))
                                    <span class="text-danger">{{ $errors->first('more_nights') }}</span>
                                @endif
                            </div>
                            <h3 class="box-title">Foreign visitors (%)</h3><hr>
                            <div class="form-group">
                                <input type="number" name="foreign_visitors" class="form-control numvalidate" min="0" max="99" value="{{ old('foreign_visitors') }}">
                                @if ($errors->has('foreign_visitors'))
                                    <span class="text-danger">{{ $errors->first('foreign_visitors') }}</span>
                                @endif
                            </div>
                            <h3 class="box-title">Weekend days (%)</h3><hr>
                            <div class="form-group">
                                <input type="number" name="weekend_days" class="form-control numvalidate" min="0" max="99" value="{{ old('weekend_days') }}">
                                @if ($errors->has('weekend_days'))
                                    <span class="text-danger">{{ $errors->first('weekend_days') }}</span>
                                @endif
                            </div>
                            <h3 class="box-title">Week days (%)</h3><hr>
                            <div class="form-group">
                                <input type="number" name="week_days" class="form-control numvalidate" min="0" max="99" value="{{ old('week_days') }}">
                                @if ($errors->has('week_days'))
                                    <span class="text-danger">{{ $errors->first('week_days') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>                
        </div>
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
        width: 400px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 400px !important;
        max-height: 200px;
        overflow-y: auto;
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
    .icheck-list li label {
        display: inline;
        color: black;
    }
    .icheck-list {
        padding-right: 0px;
    }
    .icheck-list li {
        padding-bottom: 8px;
    }
    .image-uploader {
        min-height: 20rem;
    }
</style>

<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        var length = 0;
        $('.dropify').dropify();
        $('#gallery-image').imageUploader();
        
        $('.clockpicker').clockpicker({
            donetext: 'Done',
        });
        
        $('#subUser').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Sub user'
        });
        
        $(document).on('click', '#addNewRow', function () {
            length++;
            $("#policy-container").append('<tr id="'+ length +'"><td><input type="text" class="form-control" name="policy['+ length +'][title]"></td><td><textarea rows="2" class="form-control" name="policy['+ length +'][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i'+ length +'"></i></td></tr>');
        });
        
        $(document).on('click', '.deleteRow', function () {
            var id = $(this).attr('id').replace('i','');
            $("tr").remove("#"+id);
        });
    });
    let placeSearch;
    let autocomplete;
    const componentForm = {
    };
    
    CKEDITOR.replace('content');
    CKEDITOR.replace('terms_conditions');
    
    function geolocate() {
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

    function initAutocomplete() {
        autocomplete = new google.maps.places.Autocomplete(document.getElementById("autocomplete"), {
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
        document.getElementById("map_lat").value = place.geometry.location.lat();
        document.getElementById("map_lng").value = place.geometry.location.lng();
    }
</script>

@endsection