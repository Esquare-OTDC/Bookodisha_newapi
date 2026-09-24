@extends('layouts.app')

@section('title', 'Edit Tour')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Tours</li>
                <li class="breadcrumb-item active">Edit tour</li>
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
                <h2 id="PageHeading">Edit Tour: {{ $TourDetails->name }}</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('tour-edit-request') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ $TourDetails->id }}">
                <div class="col-md-9">
                    <div class="white-box">
                        <h3 class="box-title">Tour Content</h3><hr>                        
                        <div class="form-group">
                            <label class="col-md-12" for="name">Name</label>
                            <div class="col-md-12">
                                <input type="text" class="form-control" name="name" value="{{ $TourDetails->name }}" required>
                                @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-12" for="content">Content</label>
                            <div class="col-md-12">
                                <textarea name="content" cols="10" rows="5" required>{{ $TourDetails->content }}</textarea>
                                @if ($errors->has('content'))
                                <span class="text-danger">{{ $errors->first('content') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="col-md-12">Category</label>
                            <div class="col-md-12">
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option <?= ($TourDetails->category == 'sight seeing') ? 'selected' : '' ?> value="sight seeing">Sight Seeing (1 Day)</option>
                                    <option <?= ($TourDetails->category == 'package') ? 'selected' : '' ?> value="package">Package (Multiple Days)</option>
                                </select>
                                @if ($errors->has('category'))
                                <span class="text-danger">{{ $errors->first('category') }}</span>
                                @endif
                            </div>
                        </div>
                        <?php if ($TourDetails->category == 'sight seeing') { ?>
                            <div class="form-group mt-2 durationDiv">
                                <label class="col-md-12">Duration</label>
                                <div id="durationContent">
                                    <div class="col-md-6">
                                        <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                                            <input type="text" class="form-control" name="duration_start" placeholder="Start Time" value="{{ date("H:i", strtotime($TourDetails->duration_start .' '. $TourDetails->duration_start_text)) }}" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                                            <input type="text" class="form-control" name="duration_end" placeholder="End Time" value="{{ date("H:i", strtotime($TourDetails->duration_end .' '. $TourDetails->duration_end_text)) }}" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="form-group mt-2 durationDiv">
                                <label class="col-md-12">Duration</label>
                                <div id="durationContent">
                                    <div class="col-md-6">
                                        <div class="input-group" data-placement="bottom" data-align="top" data-autoclose="true">
                                            <input type="text" class="form-control numvalidate" name="duration_start" value="{{ $TourDetails->duration_start }}" required> <span class="input-group-addon"> Nights </span>
                                        </div>
                                        <input type="hidden" name="duration_start_text" value="Nights">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group" data-placement="bottom" data-align="top" data-autoclose="true">
                                            <input type="text" class="form-control numvalidate" name="duration_end" value="{{ $TourDetails->duration_end }}" required> <span class="input-group-addon"> Days </span>
                                        </div>
                                        <input type="hidden" name="duration_end_text" value="Days">
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="form-group">
                            <label class="control-label">Tour Start Date</label>
                            <div class="input-group">
                                <input type="text" class="form-control check-quantity" name="start_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" value="{{ $TourDetails->start_date }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>
                            </div>
                            @if ($errors->has('start_date'))
                                <span class="text-danger">{{ $errors->first('start_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group mt-2">
                            <label class="col-md-12">No. of days from Current Date (To set default Booking Date)</label>
                            <div class="col-md-12">
                                <input type="number" class="form-control" name="days_from_start_date" placeholder="Days ahead from current date" value="{{ $TourDetails->days_from_start_date }}" min="0">
<!--                                @if ($errors->has('days_from_start_date'))
                                <span class="text-danger">{{ $errors->first('days_from_start_date') }}</span>
                                @endif                                -->
                            </div>
                        </div>
                        
                        <div class="form-group mt-2">
                            <label class="col-md-12">Tour Max People (Maximum people allowed per day)</label>
                            <div class="col-md-12">
                                <input type="number" class="form-control" name="max_people" placeholder="Maximum people allowed per day" value="{{ $TourDetails->max_people }}" min="0">
                                @if ($errors->has('max_people'))
                                <span class="text-danger">{{ $errors->first('max_people') }}</span>
                                @endif
                                @if ($TourDetails->id == '34' || $TourDetails->id == '35' || $TourDetails->id == '36')
                                <span class="m-r-10"><b>Total Booked - {{ $TourDetails->total_booked }}</b></span>|<span class="m-l-10"><b>Total Available - {{ $TourDetails->total_available }}</b></span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="form-group" id="sightDiv" style="display:{{ ($TourDetails->category == 'sight seeing') ? 'block' : 'none' }};">
                            <label class="col-md-12">Select Week Days (Not Available)</label>
                            <div class="col-md-12">
                                <select class="form-control" name="not_available[]" id="not_available" multiple="multiple">
                                    @foreach ($Days as $val)
                                    <option value="{{ $val }}" {{ (in_array($val, $TourDetails->not_available)) ? 'selected' : '' }}>{{ $val }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('not_available'))
                                <span class="text-danger">{{ $errors->first('not_available') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="col-md-12">Youtube Video</label>
                            <div class="col-md-12">
                                <input type="text" class="form-control" name="video" value="{{ $TourDetails->video }}" placeholder="Youtube Video Link">
                                @if ($errors->has('video'))
                                <span class="text-danger">{{ $errors->first('video') }}</span>
                                @endif
                            </div>
                        </div>                            
                        <div class="form-group mt-2">
                            <label class="col-md-12">Banner Image</label>
                            <div class="col-md-12">
                                <input type="file" id="input-file-now" class="dropify" name="banner_image" data-default-file="{{ $TourDetails->banner_image }}">
                                @if ($errors->has('banner_image'))
                                <span class="text-danger">{{ $errors->first('banner_image') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="col-md-12">Gallery</label>
                            <div class="col-md-12">
                                <div id="gallery-image" style="padding-top: .5rem;"></div>
                                @if ($errors->has('images'))
                                <span class="text-danger">{{ $errors->first('images') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Terms & Conditions</h3><hr>
                        <div class="row">
                            <div class="form-group">
                                <div class="col-md-12">
                                    <textarea name="terms_conditions" cols="10" rows="5" required>{{ $TourDetails->terms_conditions }}</textarea>
                                    @if ($errors->has('terms_conditions'))
                                    <span class="text-danger">{{ $errors->first('terms_conditions') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Location</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label>City</label>
                                <select class="form-control select2" name="city" required>
                                    <option value="">Select City</option>
                                    <?php
                                    foreach ($CityDetail as $value) {
                                        $checked = '';
                                        if ($TourDetails->city == $value) {
                                            $checked = 'selected';
                                        }
                                        echo '<option value="' . $value . '" ' . $checked . '>' . $value . '</option>';
                                    }
                                    ?>
                                </select>
                                @if ($errors->has('city'))
                                <span class="text-danger">{{ $errors->first('city') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Real address</label>
                                <input id="autocomplete" class="form-control" name="address" value="{{ $TourDetails->address }}" onFocus="geolocate()" type="text" />
                                @if ($errors->has('address'))
                                <span class="text-danger">{{ $errors->first('address') }}</span>
                                @endif
                                <input type="hidden" id="map_lat" name="map_lat" value="{{ $TourDetails->map_lat }}">
                                <input type="hidden" id="map_lng" name="map_lng" value="{{ $TourDetails->map_lng }}">
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Contact Information</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Contact Email</label>
                                <input type="email" class="form-control" name="contact_email" value="{{ $TourDetails->contact_email }}" placeholder="Contact email" required/>
                                @if ($errors->has('contact_email'))
                                <span class="text-danger">{{ $errors->first('contact_email') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label class="" for="name">Contact Number</label>
                                <input type="text" class="form-control numvalidate" name="contact_number" value="{{ $TourDetails->contact_number }}" placeholder="Contact Number" maxlength="10" required/>
                                @if ($errors->has('contact_number'))
                                <span class="text-danger">{{ $errors->first('contact_number') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Additional Email</label>
                                <input type="text" class="form-control" name="additional_email" placeholder="Additional Contact Email" autocomplete="off" value="{{ $TourDetails->additional_email }}">
                                @if ($errors->has('additional_email'))
                                <span class="text-danger">{{ $errors->first('additional_email') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Additional Contact Number</label>
                                <input type="text" class="form-control" name="additional_phone" placeholder="Additional Contact Number" autocomplete="off" value="{{ $TourDetails->additional_phone }}">
                                @if ($errors->has('additional_phone'))
                                <span class="text-danger">{{ $errors->first('additional_phone') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if (Auth::user()->role == 2)
                    <div class="white-box">
                        <h3 class="box-title">Assign Sub user</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <select id="subUser" name="sub_user[]" class="form-control" multiple="multiple">
                                    @foreach ($SubUser as $val)
                                    <option value="{{ $val['id'] }}" {{ ($val['checked'] == 1) ? 'selected' : '' }}>{{ $val['name'] }}</option>
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
                                <input type="radio" name="status" id="radio1" value="publish" {{ ($TourDetails->status == 'publish') ? 'checked' : '' }}>
                                <label for="radio1">Publish</label>
                            </div>
                            <div class="radio radio-info">
                                <input type="radio" name="status" id="radio2" value="draft" {{ ($TourDetails->status == 'draft') ? 'checked' : '' }}>
                                <label for="radio2">Draft</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="float: right;margin-top: -20px;">Submit</button>
                    </div>
                    <?php if (Auth::user()->access_type == 'superadmin') { ?>
                        <div class="white-box">
                            <h3 class="box-title">Vendor</h3><hr>
                            <select class="form-control select2" id="venderId" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                <?php
                                foreach ($Vendors as $key => $value) {
                                    $checked = '';
                                    if ($TourDetails->vendor_id == $key) {
                                        $checked = 'selected';
                                    }
                                    echo '<option value="' . $key . '" ' . $checked . '>' . $value . '</option>';
                                }
                                ?>
                            </select>
                            @if ($errors->has('vendor_id'))
                            <span class="text-danger">{{ $errors->first('vendor_id') }}</span>
                            @endif
                        </div>
                    <?php } else { ?>
                        <input type="hidden" id="vendor" name="vendor_id" class="form-control" value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                    <?php } ?>
                    <?php foreach ($CarAttributes as $attrs => $terms) { ?>
                        <div class="white-box">
                            <div style="font-size: 15px;"><strong>Attribute: {{$attrs}}</strong></div><hr>
                            <div class="input-group">
                                <ul class="icheck-list">
                                    <?php
                                    foreach ($terms as $key => $values) {
                                        $checked = '';
                                        if (isset($TourDetails->property[$attrs]) && in_array($values, $TourDetails->property[$attrs])) {
                                            $checked = 'checked';
                                        }
                                        ?>
                                        <li>
                                            <input type="checkbox" class="check" name="property[{{ $attrs }}][]" value="{{ $key .'~'. $values }}" {{ $checked }} data-checkbox="icheckbox_flat-blue">
                                            <label>{{ $values }}</label>
                                        </li>
    <?php } ?>
                                </ul>
                            </div>
                        </div>
<?php } ?>
                    <div class="white-box">
                        <h3 class="box-title">Feature Image</h3><hr>
                        <div class="form-group">
                            <input type="file" class="dropify" name="feature_image" data-default-file="{{ $TourDetails->feature_image }}">
                            @if ($errors->has('feature_image'))
                            <span class="text-danger">{{ $errors->first('feature_image') }}</span>
                            @endif
                        </div>
                    </div>
                        <div class="white-box sight-price" style="display:{{ ($TourDetails->category == 'sight seeing') ? 'block' : 'none' }}">
                        <h3 class="box-title">Pricing</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label>Per Head Price</label>
                                <span id="seightPrice">
                                    @if ($TourDetails->category == 'sight seeing')
                                    <input type="number" class="form-control" name="single_share_price" min="1" value="{{ $TourDetails->single_share_price }}" required>
                                    @endif
                                </span>
                                @if ($errors->has('single_share_price'))
                                <span class="text-danger">{{ $errors->first('single_share_price') }}</span>
                                @endif
                            </div>                            
                            <!--                            <div class="form-group col-md-12">
                                                            <label>Service Charge</label>
                                                            <input type="number" class="form-control" name="service_fee" min="0" value="{{ $TourDetails->service_fee }}" value="0">
                                                            @if ($errors->has('service_fee'))
                                                                <span class="text-danger">{{ $errors->first('service_fee') }}</span>
                                                            @endif
                                                        </div>-->
                        </div>
                    </div>
                    <div class="white-box min-max-adultperbooking" style="display:{{ ($TourDetails->category == 'sight seeing') ? 'block' : 'none' }}">
                        <h3 class="box-title">Min/Max People/Booking</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label>Minimum People/Booking</label>
                                <span id="minPeopleBooking">
                                    @if ($TourDetails->category == 'sight seeing')
                                    <input type="number" class="form-control" name="minimum_people_for_single_booking" min="0" value="{{ $TourDetails->minimum_people_for_single_booking }}" required>
                                    @endif
                                </span>
<!--                                    @if ($errors->has('minimum_people_for_single_booking'))
                                <span class="text-danger">{{ $errors->first('minimum_people_for_single_booking') }}</span>
                                @endif-->
                            </div>
                            <div class="form-group col-md-12">
                                <label>Maximum People/Booking</label>
                                <span id="maxPeopleBooking">
                                    @if ($TourDetails->category == 'sight seeing')
                                    <input type="number" class="form-control" name="maximum_people_for_single_booking" min="0" value="{{ $TourDetails->maximum_people_for_single_booking }}" required>
                                    @endif
                                </span>
<!--                                    @if ($errors->has('maximum_people_for_single_booking'))
                                <span class="text-danger">{{ $errors->first('maximum_people_for_single_booking') }}</span>
                                @endif-->
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Special Tour</h3><hr>
                        <div class="form-group">
                            <div class="radio-list m-l-20">
                                <label class="radio-inline">
                                    <input type="radio" name="is_special_tour" value="1" {{ ($TourDetails->is_special_tour == 1) ? 'checked' : '' }}> Yes </label>
                                <label class="radio-inline">
                                    <input type="radio" name="is_special_tour" value="0" {{ ($TourDetails->is_special_tour == 0) ? 'checked' : '' }}> No </label>
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">GST Applicable</h3><hr>
                        <div class="form-group">
                            <div class="radio-list m-l-20">
                                <label class="radio-inline">
                                    <input type="radio" name="gst_applicable" value="1" {{ ($TourDetails->gst_applicable == 1) ? 'checked' : '' }}> Yes </label>
                                <label class="radio-inline">
                                    <input type="radio" name="gst_applicable" value="0"  {{ ($TourDetails->gst_applicable == 0) ? 'checked' : '' }}> No </label>
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">GST Number</h3><hr>
                        <div class="form-group">
                            <input type="text" name="gst_number" class="form-control" value="{{ $TourDetails->gst_number }}" placeholder="Enter GST No">
                        </div>
                        <h3 class="box-title">Company Name</h3><hr>
                        <div class="form-group">
                            <input type="text" name="gst_legal_name" class="form-control" value="{{ $TourDetails->gst_legal_name }}" placeholder="Enter Company Name">
                        </div>
                    </div>
<!--                    <div class="white-box">
                        <h3 class="box-title">Paytm MID</h3><hr>
                        <div class="form-group">
                            <input type="text" name="paytm_mid" class="form-control" value="{{ $TourDetails->paytm_mid }}" placeholder="Enter Paytm MID">
                        </div>
                        <h3 class="box-title">HDFC MID</h3><hr>
                        <div class="form-group">
                            <input type="text" name="hdfc_mid" class="form-control" value="{{ $TourDetails->hdfc_mid }}" placeholder="Enter HDFC MID">
                        </div>
                    </div>-->
                    <div class="white-box">
                            <h3 class="box-title">Show Price</h3><hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="1" {{ ($TourDetails->show_price == 1) ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="0" {{ ($TourDetails->show_price == 0) ? 'checked' : '' }}> No </label>
                                </div>
                            </div>
                        </div>
                </div>
                <div class="col-md-12 package-price" style="display:{{ ($TourDetails->category == 'package') ? 'block' : 'none' }}">
                    <div class="white-box">
                        <h3 class="box-title">Pricing</h3>
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="box-title">Single occupancy Price</h5>
                                <table class="display nowrap table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Service</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Refund(Before 7 Days)</th>
                                            <th class="text-center">Refund(Within 7 Days)</th>                                                
                                            <th class="text-center" colspan="2">Refund(Within 24 hrs)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sopContainer">
                                        @php
                                        $count = 1;
                                        @endphp
                                        @foreach ($TourDetails->single_share_policy as $val)
                                        <tr id="sop{{ $count }}">
                                            <td>
                                                <input type="text" required class="form-control" name="sop[{{ $count }}][service]" value="{{ $val['service'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control sopprice" name="sop[{{ $count }}][price]" value="{{ $val['price'] }}">
                                            </td>        
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="sop[{{ $count }}][refund_before_7d]" value="{{ $val['refund_before_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="sop[{{ $count }}][refund_within_7d]" value="{{ $val['refund_within_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="sop[{{ $count }}][refund_within_24hr]"value="{{ $val['refund_within_24hr'] }}">
                                            </td>
                                            </td>
                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteSOP fa fa-trash" id="dsop{{ $count }}"></i>
                                            </td>
                                        </tr>                                        
                                        @php
                                        $count++;
                                        @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                <div style="width:50%;" id="sopTotal">
                                    @if ($TourDetails->category == 'package')
                                    <input type="number" class="form-control" step="any" name="single_share_price" id="single_share_price" placeholder="Single occupancy Price" value="{{ $TourDetails->single_share_price }}" readonly required>
                                    @endif
                                </div>
                                <span class="btn btn-info btn-sm" id="addSOP" style="float: right;"><i class="icon-plus"></i> Add</span>
                            </div>
                            <div class="col-md-12">
                                <h5 class="box-title">Double occupancy Price</h5>
                                <table class="display nowrap table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Service</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Refund(Before 7 Days)</th>
                                            <th class="text-center">Refund(Within 7 Days)</th>                                                
                                            <th class="text-center" colspan="2">Refund(Within 24 hrs)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dopContainer">
                                        @php
                                        $count = 1;
                                        @endphp
                                        @foreach ($TourDetails->double_share_policy as $val)
                                        <tr id="dop{{ $count }}">
                                            <td>
                                                <input type="text" required class="form-control" name="dop[{{ $count }}][service]" value="{{ $val['service'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control dopprice" name="dop[{{ $count }}][price]" value="{{ $val['price'] }}">
                                            </td>        
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="dop[{{ $count }}][refund_before_7d]" value="{{ $val['refund_before_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="dop[{{ $count }}][refund_within_7d]" value="{{ $val['refund_within_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="dop[{{ $count }}][refund_within_24hr]"value="{{ $val['refund_within_24hr'] }}">
                                            </td>
                                            </td>
                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteDOP fa fa-trash" id="ddop{{ $count }}"></i>
                                            </td>
                                        </tr>                                        
                                        @php
                                        $count++;
                                        @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                <div style="width:50%;" id="dopTotal">
                                    @if ($TourDetails->category == 'package')
                                    <input type="number" class="form-control" step="any" name="double_share_price" id="double_share_price" placeholder="Double occupancy Price" value="{{ $TourDetails->double_share_price }}" readonly required>
                                    @endif
                                </div>
                                <span class="btn btn-info btn-sm" id="addDOP" style="float: right;"><i class="icon-plus"></i> Add</span>
                            </div>
                            <div class="col-md-12">
                                <h5 class="box-title">Triple occupancy Price</h5>
                                <table class="display nowrap table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Service</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Refund(Before 7 Days)</th>
                                            <th class="text-center">Refund(Within 7 Days)</th>                                                
                                            <th class="text-center" colspan="2">Refund(Within 24 hrs)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="topContainer">
                                        @php
                                        $count = 1;
                                        @endphp
                                        @foreach ($TourDetails->triple_share_policy as $val)
                                        <tr id="top{{ $count }}">
                                            <td>
                                                <input type="text" required class="form-control" name="top[{{ $count }}][service]" value="{{ $val['service'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control topprice" name="top[{{ $count }}][price]" value="{{ $val['price'] }}">
                                            </td>        
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="top[{{ $count }}][refund_before_7d]" value="{{ $val['refund_before_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="top[{{ $count }}][refund_within_7d]" value="{{ $val['refund_within_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="top[{{ $count }}][refund_within_24hr]"value="{{ $val['refund_within_24hr'] }}">
                                            </td>
                                            </td>
                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteTOP fa fa-trash" id="dtop{{ $count }}"></i>
                                            </td>
                                        </tr>                                        
                                        @php
                                        $count++;
                                        @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                <div style="width:50%;" id="topTotal">
                                    @if ($TourDetails->category == 'package')
                                    <input type="number" class="form-control" step="any" name="triple_share_price" id="triple_share_price" placeholder="Tripple occupancy Price" value="{{ $TourDetails->triple_share_price }}" readonly required>
                                    @endif
                                </div>
                                <span class="btn btn-info btn-sm" id="addTOP" style="float: right;"><i class="icon-plus"></i> Add</span>
                            </div>
                            <div class="col-md-12">
                                <h5 class="box-title">Child Price</h5>
                                <table class="display nowrap table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Service</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Refund(Before 7 Days)</th>
                                            <th class="text-center">Refund(Within 7 Days)</th>                                                
                                            <th class="text-center" colspan="2">Refund(Within 24 hrs)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpContainer">
                                        @php
                                        $count = 1;
                                        @endphp
                                        @foreach ($TourDetails->child_price_policy as $val)
                                        <tr id="cp{{ $count }}">
                                            <td>
                                                <input type="text" required class="form-control" name="cp[{{ $count }}][service]" value="{{ $val['service'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control cpprice" name="cp[{{ $count }}][price]" value="{{ $val['price'] }}">
                                            </td>        
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="cp[{{ $count }}][refund_before_7d]" value="{{ $val['refund_before_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="cp[{{ $count }}][refund_within_7d]" value="{{ $val['refund_within_7d'] }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="cp[{{ $count }}][refund_within_24hr]"value="{{ $val['refund_within_24hr'] }}">
                                            </td>
                                            </td>
                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteCP fa fa-trash" id="dcp{{ $count }}"></i>
                                            </td>
                                        </tr>                                        
                                        @php
                                        $count++;
                                        @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                <div style="width:50%;" id="cpTotal">
                                    @if ($TourDetails->category == 'package')
                                    <input type="number" class="form-control" step="any" name="child_price" id="child_price" placeholder="Child Price" value="{{ $TourDetails->child_price }}" readonly required>
                                    @endif
                                </div>
                                <span class="btn btn-info btn-sm" id="addCP" style="float: right;"><i class="icon-plus"></i> Add</span>
                            </div>
                        </div>                                
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="white-box">                        
                        <h3 class="box-title itinary">Itinerary</h3>
                        <div class="row itinary">
                            <div class="form-group mt-2">
                                <div class="col-md-12">
                                    <table class="display nowrap table table-bordered">

                                        <tbody id="itinerary-container">
                                            <?php
                                            if (!empty($TourDetails->itinerary)) {
                                                $countr = 1;
                                                foreach ($TourDetails->itinerary as $key => $value) {
                                                    ?>
                                                    <tr id="itr{{ $countr }}">
                                                        <td colspan="2">
                                                            <div class="row">
                                                                <div class="col-md-2">Title</div>
                                                                <div class="col-md-10">
                                                                    <input type="text" class="form-control" name="itinerary[{{ $countr }}][title]" value="{{ $value['title'] }}" required>
                                                                </div>
                                                            </div>                                                                
                                                            <div class="row">
                                                                <div class="col-md-2 mt2">Hotel</div>
                                                                <div class="col-md-10 mt2">
                                                                    <select class="form-control itr-hotel" id="itrHotel{{ $countr }}" name="itinerary[{{ $countr }}][hotel]">
                                                                        <option value="">No Accommodation</option>
                                                                        <?php
                                                                        foreach ($hotel_array as $hotels) {
                                                                            $temp = explode('~', $hotels);
                                                                            $selected = ($value['hotel'] == $temp[1]) ? 'selected' : '';
                                                                            echo '<option value="' . $hotels . '" ' . $selected . '>' . $temp[0] . '</option>';
                                                                        }
                                                                        $oth_select = ($value['hotel'] == 'other') ? 'selected' : '';
                                                                        echo '<option value="other" ' . $oth_select . '>Other</option>';
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row" id="itrRoom{{ $countr }}">
                                                                <?php if ($value['hotel'] == 'No Accommodation') { ?>
                                                                    
                                                                <?php } elseif ($value['hotel'] == 'other') { ?>
                                                                    <div class="col-md-2 mt2">Hotel Name</div>
                                                                    <div class="col-md-10 mt2">
                                                                        <input type="text" class="form-control" name="itinerary[{{ $countr }}][hotelName]" value="{{ $value['hotelName'] }}" required>
                                                                    </div>
                                                                    <div class="col-md-2 mt2">Room Name</div>
                                                                    <div class="col-md-10 mt2">
                                                                        <input type="text" class="form-control" name="itinerary[{{ $countr }}][room]" value="{{ $value['roomName'] }}" required>
                                                                    </div>
                                                                <?php } else { ?>
                                                                    <div class="col-md-2 mt2">Hotel Rooms</div>
                                                                    <div class="col-md-10 mt2">
                                                                        <select class="form-control" name="itinerary[{{ $countr }}][room]" required>
                                                                            <option value="">Select Room</option>
                                                                            @foreach ($value['hotelRoom'] as $rooms)
                                                                            <?php
                                                                            $temp1 = explode('~', $rooms['title']);
                                                                            $selected = ($rooms['selected'] == 1) ? 'selected' : '';
                                                                            ?>
                                                                            <option value="{{ $rooms['title'] }}" {{ $selected }}>{{ $temp1[0] }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-2 mt2">Meals</div>
                                                                <div class="col-md-10 mt2">
                                                                    <input type="text" class="form-control" name="itinerary[{{ $countr }}][meals]" value="{{ $value['meals'] }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-2 mt2">Sight seeing</div>
                                                                <div class="col-md-10 mt2">
                                                                    <input type="text" class="form-control" data-role="tagsinput" name="itinerary[{{ $countr }}][places]" value="{{ $value['places'] }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-2 mt2">Details</div>
                                                                <div class="col-md-10 mt2">
                                                                    <textarea rows="2" class="form-control" name="itinerary[{{ $countr }}][content]" required style="overflow: auto;resize: vertical;">{{ $value['content'] }}</textarea>
                                                                </div>
                                                            </div>
                                                        </td>    
                                                        <td style="width:7%"><i class="btn btn-danger btn-sm deleteItinerary fa fa-trash" id="j{{ $countr }}"></i></td>

                                                    </tr>
                                                    <?php $countr++;
                                                }
                                            } ?>
                                        </tbody>
                                    </table>
                                    <span class="btn btn-info btn-sm" id="addNewItinerary" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                </div>
                            </div>
                        </div>
                        <h3 class="box-title">Include</h3>
                        <div class="row">
                            <div class="form-group mt-2">
                                <div class="col-md-12">
                                    <table class="display nowrap table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width:35%;">Title</th>
                                                <th class="text-center" colspan="2">Content</th>
                                            </tr>
                                        </thead>
                                        <tbody id="include-container">
                                            <?php
                                            if (!empty($TourDetails->include)) {
                                                $countr = 0;
                                                foreach ($TourDetails->include as $value) {
                                                ?>
                                                    <tr id="inc{{ $countr }}">
                                                        <td>
                                                            <input type="text" class="form-control" name="include[{{ $countr }}][title]" value="{{ $value['title'] }}">
                                                        </td>
                                                        <td>
                                                            <textarea rows="2" class="form-control" name="include[{{ $countr }}][content]" style="overflow: auto;resize: vertical;">{{ $value['content'] }}</textarea>
                                                        </td>
                                                        <td style="width:7%"><i class="btn btn-danger btn-sm deleteInclude fa fa-trash" id="k{{ $countr }}"></i></td>
                                                    </tr>
                                                    <?php $countr++;
                                                }
                                            } ?>
                                        </tbody>
                                    </table>
                                    <span class="btn btn-info btn-sm" id="addNewInclude" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                </div>
                            </div>
                        </div>
                        <h3 class="box-title">Exclude</h3>
                        <div class="row">
                            <div class="form-group mt-2">
                                <div class="col-md-12">
                                    <table class="display nowrap table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width:35%;">Title</th>
                                                <th class="text-center" colspan="2">Content</th>
                                            </tr>
                                        </thead>
                                        <tbody id="exclude-container">
                                            <?php
                                            if (!empty($TourDetails->exclude)) {
                                                $countr = 0;
                                                foreach ($TourDetails->exclude as $value) {
                                                    ?>
                                                    <tr id="exc{{ $countr }}">
                                                        <td>
                                                            <input type="text" class="form-control" name="exclude[{{ $countr }}][title]" value="{{ $value['title'] }}">
                                                        </td>
                                                        <td>
                                                            <textarea rows="2" class="form-control" name="exclude[{{ $countr }}][content]" style="overflow: auto;resize: vertical;">{{ $value['content'] }}</textarea>
                                                        </td>
                                                        <td style="width:7%"><i class="btn btn-danger btn-sm deleteExclude fa fa-trash" id="l{{ $countr }}"></i></td>
                                                    </tr>
                                                    <?php $countr++;
                                                }
                                            } ?>
                                        </tbody>
                                    </table>
                                    <span class="btn btn-info btn-sm" id="addNewExclude" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                </div>
                            </div>
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
        max-height: 250px !important;
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
    .bootstrap-tagsinput {
        width: 100%;
        text-align: left;
    }
    .mt2 {
        margin-top: 5px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        $('.clockpicker').clockpicker({
            donetext: 'Done',
        });        
        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'dd-mm-yyyy',
            startDate: '-0m',
        });
        
        $('#subUser').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Sub user'
        });
        
        let selectedcategory = $('#category').val();
        if (selectedcategory == 'sight seeing') {
            $("#itinerary-container").html('');
            $(".itinary").hide();
        }
        
        $('#not_available').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Days'
        });
        
        var length2 = '<?= !empty($TourDetails->itinerary) ? count($TourDetails->itinerary) : 0 ?>';
        var length3 = '<?= !empty($TourDetails->include) ? count($TourDetails->include) : 0 ?>';
        var length4 = '<?= !empty($TourDetails->exclude) ? count($TourDetails->exclude) : 0 ?>';
        var lengthSOP = Number('{{ count($TourDetails->single_share_policy) }}');
        var lengthDOP = Number('{{ count($TourDetails->double_share_policy) }}');
        var lengthTOP = Number('{{ count($TourDetails->triple_share_policy) }}');
        var lengthCP = Number('{{ count($TourDetails->child_price_policy) }}');
        
        $('.dropify').dropify();
        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });
        
        $(document).on('click', '#addNewItinerary', function () {
            length2++;
            $("#itinerary-container").append('<tr id="itr'+ length2 +'"><td colspan="2"><div class="row"><div class="col-md-2">Title</div><div class="col-md-10"> <input type="text" class="form-control" name="itinerary['+ length2 +'][title]" required></div></div><div class="row"><div class="col-md-2 mt2">Hotel</div><div class="col-md-10 mt2"> <select class="form-control itr-hotel" id="itrHotel'+ length2 +'" name="itinerary['+ length2 +'][hotel]"><option value="">No Accommodation</option> <?= $hotel_list ?><option value="other">Other</option> </select></div></div><div class="row" id="itrRoom'+ length2 +'"></div><div class="row"><div class="col-md-2 mt2">Meals</div><div class="col-md-10 mt2"> <input type="text" class="form-control" name="itinerary['+ length2 +'][meals]" required></div></div><div class="row"><div class="col-md-2 mt2">Sight seeing</div><div class="col-md-10 mt2"> <input type="text" class="form-control" data-role="tagsinput" name="itinerary['+ length2 +'][places]" required></div></div><div class="row"><div class="col-md-2 mt2">Details</div><div class="col-md-10 mt2"><textarea rows="2" class="form-control" name="itinerary['+ length2 +'][content]" required style="overflow: auto;resize: vertical;"></textarea></div></div></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteItinerary fa fa-trash" id="j'+ length2 +'"></i></td></tr>');
            $('input[name ="itinerary['+ length2 +'][places]"]').tagsinput('refresh');
        });
        
        $(document).on('click', '.deleteItinerary', function () {
            var id = $(this).attr('id').replace('j','');
            $("tr").remove("#itr"+id);
        });
        
        $(document).on('click', '#addNewInclude', function () {
            length3++;
            $("#include-container").append('<tr id="inc'+ length3 +'"><td><input type="text" class="form-control" name="include['+ length3 +'][title]"></td><td><textarea rows="2" class="form-control" name="include['+ length3 +'][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteInclude fa fa-trash" id="k'+ length3 +'"></i></td></tr>');
        });
        
        $(document).on('click', '.deleteInclude', function () {
            var id = $(this).attr('id').replace('k','');
            $("tr").remove("#inc"+id);
        });
        
        $(document).on('click', '#addNewExclude', function () {
            length4++;
            $("#exclude-container").append('<tr id="exc'+ length4 +'"><td><input type="text" class="form-control" name="exclude['+ length4 +'][title]"></td><td><textarea rows="2" class="form-control" name="exclude['+ length4 +'][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteExclude fa fa-trash" id="l'+ length4 +'"></i></td></tr>');
        });
        
        $(document).on('click', '.deleteExclude', function () {
            var id = $(this).attr('id').replace('l','');
            $("tr").remove("#exc"+id);
        });
        
        $(document).on('change', '#category', function () {
            let category = $(this).val();
            if (category == 'sight seeing') {
                $("#sightDiv").show();
                $(".sight-price").show();
                $("#seightPrice").html('<input type="number" class="form-control" name="single_share_price" min="1" required>');
                $(".min-max-adultperbooking").show();
                $("#minPeopleBooking").html('<input type="number" class="form-control" name="minimum_people_for_single_booking" min="0" value="0" required>');
                $("#maxPeopleBooking").html('<input type="number" class="form-control" name="maximum_people_for_single_booking" min="0" value="0" required>');
                $(".package-price").hide();
                $("#itinerary-container").html('');
                $(".itinary").hide();
                $(".durationDiv").show();
                $("#durationContent").html('<div class="col-md-6"><div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true"> <input type="text" class="form-control" name="duration_start" placeholder="Start Time" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span></div></div><div class="col-md-6"><div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true"> <input type="text" class="form-control" name="duration_end" placeholder="End Time" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span></div></div>');
                $('.clockpicker').clockpicker({
                    donetext: 'Done',
                });
                $("#sopTotal").html('');
                $("#dopTotal").html('');
                $("#topTotal").html('');
                $("#cpTotal").html('');
                
                $("#sopContainer").html('');
                $("#dopContainer").html('');
                $("#topContainer").html('');
                $("#cpContainer").html('');
                
            } else if (category == 'package') {
                $("#sightDiv").hide();
                $(".sight-price").hide();
                $("#seightPrice").html('');
                $(".min-max-adultperbooking").hide();
                $("#minPeopleBooking").html('');
                $("#maxPeopleBooking").html('');
                $(".package-price").show();
                $(".itinary").show();
                $(".durationDiv").show();
                $("#durationContent").html('<div class="col-md-6"><div class="input-group" data-placement="bottom" data-align="top" data-autoclose="true"> <input type="text" class="form-control numvalidate" name="duration_start" required> <span class="input-group-addon"> Nights </span></div> <input type="hidden" name="duration_start_text" value="Nights"></div><div class="col-md-6"><div class="input-group" data-placement="bottom" data-align="top" data-autoclose="true"> <input type="text" class="form-control numvalidate" name="duration_end" required> <span class="input-group-addon"> Days </span></div> <input type="hidden" name="duration_end_text" value="Days"></div>');
                $("#sopTotal").html('<input type="number" class="form-control" step="any" name="single_share_price" id="single_share_price" placeholder="Single occupancy Price" readonly required>');
                $("#dopTotal").html('<input type="number" class="form-control" step="any" name="double_share_price" id="double_share_price" placeholder="Double occupancy Price" readonly required>');
                $("#topTotal").html('<input type="number" class="form-control" step="any" name="triple_share_price" id="triple_share_price" placeholder="Triple occupancy Price" readonly required>');
                $("#cpTotal").html('<input type="number" class="form-control" step="any" name="child_price" id="child_price" placeholder="Child Price" readonly required>');
            } else {
                $("#sightDiv").hide();
                $(".itinary").hide();
                $(".durationDiv").hide();
                $("#sopContainer").html('');
                $("#dopContainer").html('');
                $("#topContainer").html('');
                $("#cpContainer").html('');
            }            
        });
        
        $(document).on('click', '#addSOP', function () {
            lengthSOP++;
            $("#sopContainer").append('<tr id="sop'+ lengthSOP +'"><td><input type="text" required class="form-control" name="sop['+ lengthSOP +'][service]"></td><td><input type="number" min="0" step="any" required class="form-control sopprice" name="sop['+ lengthSOP +'][price]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="sop['+ lengthSOP +'][refund_before_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="sop['+ lengthSOP +'][refund_within_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="sop['+ lengthSOP +'][refund_within_24hr]"></td></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteSOP fa fa-trash" id="dsop'+ lengthSOP +'"></i></td></tr>');
        });        
        $(document).on('click', '.deleteSOP', function () {
            var id = $(this).attr('id').replace('dsop','');
            $("tr").remove("#sop"+id);
            sopCalculate();
        });        
        $(document).on('keyup', '.sopprice', function () {
            sopCalculate();
        });
        
        $(document).on('click', '#addDOP', function () {
            lengthDOP++;
            $("#dopContainer").append('<tr id="dop'+ lengthDOP +'"><td><input type="text" required class="form-control" name="dop['+ lengthDOP +'][service]"></td><td><input type="number" min="0" step="any" required class="form-control dopprice" name="dop['+ lengthDOP +'][price]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="dop['+ lengthDOP +'][refund_before_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="dop['+ lengthDOP +'][refund_within_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="dop['+ lengthDOP +'][refund_within_24hr]"></td></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteDOP fa fa-trash" id="ddop'+ lengthDOP +'"></i></td></tr>');
        });        
        $(document).on('click', '.deleteDOP', function () {
            var id = $(this).attr('id').replace('ddop','');
            $("tr").remove("#dop"+id);
            dopCalculate();
        });        
        $(document).on('keyup', '.dopprice', function () {
            dopCalculate();
        });
        
        $(document).on('click', '#addTOP', function () {
            lengthTOP++;
            $("#topContainer").append('<tr id="top'+ lengthTOP +'"><td><input type="text" required class="form-control" name="top['+ lengthTOP +'][service]"></td><td><input type="number" min="0" step="any" required class="form-control topprice" name="top['+ lengthTOP +'][price]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="top['+ lengthTOP +'][refund_before_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="top['+ lengthTOP +'][refund_within_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="top['+ lengthTOP +'][refund_within_24hr]"></td></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteTOP fa fa-trash" id="dtop'+ lengthTOP +'"></i></td></tr>');
        });        
        $(document).on('click', '.deleteTOP', function () {
            var id = $(this).attr('id').replace('dtop','');
            $("tr").remove("#top"+id);
            topCalculate();
        });        
        $(document).on('keyup', '.topprice', function () {
            topCalculate();
        });
        
        $(document).on('click', '#addCP', function () {
            lengthCP++;
            $("#cpContainer").append('<tr id="cp'+ lengthCP +'"><td><input type="text" required class="form-control" name="cp['+ lengthCP +'][service]"></td><td><input type="number" min="0" step="any" required class="form-control cpprice" name="cp['+ lengthCP +'][price]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="cp['+ lengthCP +'][refund_before_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="cp['+ lengthCP +'][refund_within_7d]"></td><td><input type="number" min="0" step="any" required class="form-control" placeholder="in %" name="cp['+ lengthCP +'][refund_within_24hr]"></td></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteCP fa fa-trash" id="dcp'+ lengthCP +'"></i></td></tr>');
        });        
        $(document).on('click', '.deleteCP', function () {
            var id = $(this).attr('id').replace('dcp','');
            $("tr").remove("#cp"+id);
            cpCalculate();
        });        
        $(document).on('keyup', '.cpprice', function () {
            cpCalculate();
        });
        
        function sopCalculate() {
            let price = 0;
            $(".sopprice").each(function() {
                let val = Number($(this).val());
                price += val; 
            });            
            $("#single_share_price").val(price);
        }
        function dopCalculate() {
            let price = 0;
            $(".dopprice").each(function() {
                let val = Number($(this).val());
                price += val; 
            });            
            $("#double_share_price").val(price);
        }
        function topCalculate() {
            let price = 0;
            $(".topprice").each(function() {
                let val = Number($(this).val());
                price += val; 
            });            
            $("#triple_share_price").val(price);
        }
        function cpCalculate() {
            let price = 0;
            $(".cpprice").each(function() {
                let val = Number($(this).val());
                price += val; 
            });            
            $("#child_price").val(price);
        }
        
        $(document).on('change', '.itr-hotel', function () {
            let hotel = $(this).val().split('~');
            let hotelId = hotel[1];
            let id = $(this).attr('id').replace('itrHotel','');
            if (hotel != '' && hotel != 'other') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {hotelName: hotelId, request_type: "get_hotel_rooms"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        let Data = responce.data;
                        console.log(Data);
                        $("#itrRoom"+ id).html('<div class="col-md-2 mt2">Hotel Rooms</div><div class="col-md-10 mt2"><select class="form-control" name="itinerary['+ id +'][room]" required>'+ Data +'</select></div>');
                    }
                });
            } else if(hotel == 'other') {
                $("#itrRoom"+ id).html('<div class="col-md-2 mt2">Hotel Name</div><div class="col-md-10 mt2"> <input type="text" class="form-control" name="itinerary['+ id +'][hotelName]" required></div><div class="col-md-2 mt2">Room Name</div><div class="col-md-10 mt2"> <input type="text" class="form-control" name="itinerary['+ id +'][room]" required></div>');
            } else if(hotel == '') {
                $("#itrRoom"+ id).html('');
            }
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