@extends('layouts.app')

@section('title', 'Edit Car')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Cars</li>
                <li class="breadcrumb-item active">Edit car</li>
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
                <h2 id="PageHeading">Edit Car</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('car-edit-request') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ $CarDetails->id }}">
                <div class="col-md-9">
                    <div class="white-box">
                        <h3 class="box-title">Car Content</h3><hr>                        
                        <div class="form-group">
                            <label class="col-md-12" for="name">Title</label>
                            <div class="col-md-12">
                                <input type="text" class="form-control" name="title" value="{{ $CarDetails->title }}" placeholder="Name of the Car" required>
                                @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-12" for="content">Content</label>
                            <div class="col-md-12">
                                <textarea name="content" cols="10" rows="5" required>{{ $CarDetails->content }}</textarea>
                                @if ($errors->has('content'))
                                <span class="text-danger">{{ $errors->first('content') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="col-md-12">Youtube Video</label>
                            <div class="col-md-12">
                                <input type="text" class="form-control" name="video" value="{{ $CarDetails->video }}" placeholder="Youtube Video Link">
                                @if ($errors->has('video'))
                                <span class="text-danger">{{ $errors->first('video') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label class="col-md-12">Banner Image</label>
                            <div class="col-md-12">
                                <input type="file" id="input-file-now" class="dropify" name="banner_image" data-default-file="{{ $CarDetails->banner_image }}">
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
                                    <textarea name="terms_conditions" cols="10" rows="5" required>{{ $CarDetails->terms_conditions }}</textarea>
                                    @if ($errors->has('terms_conditions'))
                                    <span class="text-danger">{{ $errors->first('terms_conditions') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">FAQS</h3><hr>
                        <div class="row">
                            <div class="form-group mt-2">
                                <div class="col-md-12">
                                    <table class="display nowrap table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Title</th>
                                                <th class="text-center" colspan="2">Content</th>
                                            </tr>
                                        </thead>
                                        <tbody id="policy-container">
                                            <?php
                                            if (!empty($CarDetails->faqs)) {
                                                $countr = 0;
                                                foreach ($CarDetails->faqs as $key => $value) {
                                                    ?>
                                                    <tr id="{{ $countr }}">
                                                        <td>
                                                            <input type="text" class="form-control" name="faqs[{{ $countr }}][title]" value="{{ $key }}">
                                                        </td>
                                                        <td>
                                                            <textarea rows="2" class="form-control" name="faqs[{{ $countr }}][content]" style="overflow: auto;resize: vertical;">{{ $value }}</textarea>
                                                        </td>
                                                        <td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i{{ $countr }}"></i>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $countr++;
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                    <span class="btn btn-info btn-sm" id="addNewRow" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Extra Info</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label class="" for="name">Passenger</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="passenger" value="{{ $CarDetails->passenger }}" required>
                                    @if ($errors->has('passenger'))
                                    <span class="text-danger">{{ $errors->first('passenger') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label class="" for="name">Gear Shift</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="gear" value="{{ $CarDetails->gear }}" required>
                                    @if ($errors->has('gear'))
                                    <span class="text-danger">{{ $errors->first('gear') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label class="" for="name">Baggage</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="baggage" value="{{ $CarDetails->baggage }}" required>
                                    @if ($errors->has('baggage'))
                                    <span class="text-danger">{{ $errors->first('baggage') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <label class="" for="name">Door</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="door" value="{{ $CarDetails->door }}" required>
                                    @if ($errors->has('door'))
                                    <span class="text-danger">{{ $errors->first('door') }}</span>
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
                                        if ($CarDetails->city == $value) {
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
                                <input id="autocomplete" class="form-control" name="address" value="{{ $CarDetails->address }}" placeholder="Enter your address" onFocus="geolocate()" type="text" />
                                @if ($errors->has('address'))
                                <span class="text-danger">{{ $errors->first('address') }}</span>
                                @endif
                                <input type="hidden" id="map_lat" name="map_lat" value="{{ $CarDetails->map_lat }}">
                                <input type="hidden" id="map_lng" name="map_lng" value="{{ $CarDetails->map_lng }}">
                            </div>
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Pricing</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="" for="name">Max Distance (in kilometer)</label>
                                <input type="number" class="form-control" name="max_distance" value="{{ $CarDetails->max_distance }}" placeholder="max distance" min="1" required>
                                @if ($errors->has('max_distance'))
                                    <span class="text-danger">{{ $errors->first('max_distance') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Price (per hour If km less than max dist)</label>
                                <input type="number" class="form-control" name="price_per_hour" value="{{ $CarDetails->price_per_hour }}" placeholder="Price per hour" min="1" required>
                                @if ($errors->has('price_per_hour'))
                                    <span class="text-danger">{{ $errors->first('price_per_hour') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Detention Charge (per hour)</label>
                                <input type="number" class="form-control" name="detention_charge_per_hour" value="{{ $CarDetails->detention_charge_per_hour }}" placeholder="Detention Charge"  min="1" required>
                                @if ($errors->has('detention_charge_per_hour'))
                                    <span class="text-danger">{{ $errors->first('detention_charge_per_hour') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Price (per kilometer)</label>
                                <input type="number" class="form-control" name="price_per_km" value="{{ $CarDetails->price_per_km }}" placeholder="Price per kilometer"  min="1" required>
                                @if ($errors->has('price_per_km'))
                                    <span class="text-danger">{{ $errors->first('price_per_km') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Halting Charge (per night)</label>
                                <input type="number" class="form-control" name="price_for_halt" value="{{ $CarDetails->price_for_halt }}" placeholder="Halting Charge"  min="1" required>
                                @if ($errors->has('price_for_halt'))
                                    <span class="text-danger">{{ $errors->first('price_for_halt') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Halting Hour (per day)</label>
                                <input type="number" class="form-control" name="halt_hour" value="{{ $CarDetails->halt_hour }}" placeholder="Halting Hour"  min="1" required>
                                @if ($errors->has('halt_hour'))
                                    <span class="text-danger">{{ $errors->first('halt_hour') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Quantity</label>
                                <input type="number" class="form-control" name="quantity" value="{{ $CarDetails->quantity }}" placeholder="No. of cars available" min="1" required>
                                @if ($errors->has('quantity'))
                                    <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Distance Covered Per Hour</label>
                                <input type="number" class="form-control" name="dist_cover_per_hour" value="{{ $CarDetails->dist_cover_per_hour }}" placeholder="Distance covered per hour" min="1" required>
                                @if ($errors->has('dist_cover_per_hour'))
                                    <span class="text-danger">{{ $errors->first('dist_cover_per_hour') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Free KM Per Hour</label>
                                <input type="number" class="form-control" name="free_km_per_hour" placeholder="Free KM per hour" min="1" value="{{ $CarDetails->free_km_per_hour }}" required>
                                @if ($errors->has('free_km_per_hour'))
                                    <span class="text-danger">{{ $errors->first('free_km_per_hour') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Minimum Booking Duration (in hour)</label>
                                <input type="number" class="form-control" name="min_duration" placeholder="Minimum Booking Duration" min="1" value="{{ $CarDetails->min_duration }}" required>
                                @if ($errors->has('min_duration'))
                                    <span class="text-danger">{{ $errors->first('min_duration') }}</span>
                                @endif
                            </div>
                            <!--                            <div class="form-group col-md-6">
                                                            <label>Service Charge</label>
                                                            <input type="number" class="form-control" name="service_fee" value="{{ $CarDetails->service_fee }}" placeholder="Service Charge" min="1" value="0">
                                                            @if ($errors->has('service_fee'))
                                                                <span class="text-danger">{{ $errors->first('service_fee') }}</span>
                                                            @endif
                                                        </div>-->
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Contact Information</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Contact Email</label>
                                <input type="email" class="form-control" name="contact_email" value="{{ $CarDetails->contact_email }}" placeholder="Contact email" required/>
                                @if ($errors->has('contact_email'))
                                <span class="text-danger">{{ $errors->first('contact_email') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label class="" for="name">Contact Number</label>
                                <input type="text" class="form-control numvalidate" name="contact_number" value="{{ $CarDetails->contact_number }}" placeholder="Contact Number" required maxlength="10"/>
                                @if ($errors->has('contact_number'))
                                <span class="text-danger">{{ $errors->first('contact_number') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Additional Email</label>
                                <input type="text" class="form-control" name="additional_email" placeholder="Additional Contact Email" autocomplete="off" value="{{ $CarDetails->additional_email }}">
                                @if ($errors->has('additional_email'))
                                <span class="text-danger">{{ $errors->first('additional_email') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Additional Contact Number</label>
                                <input type="text" class="form-control" name="additional_phone" placeholder="Additional Contact Number" autocomplete="off" value="{{ $CarDetails->additional_phone }}">
                                @if ($errors->has('additional_phone'))
                                <span class="text-danger">{{ $errors->first('additional_phone') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="white-box">
                        <h3 class="box-title">Publish</h3><hr>
                        <div class="form-group">
                            <div class="radio radio-info">
                                <input type="radio" name="status" id="radio1" value="publish" {{ ($CarDetails->status == 'publish') ? 'checked' : '' }}>
                                <label for="radio1">Publish</label>
                            </div>
                            <div class="radio radio-info">
                                <input type="radio" name="status" id="radio2" value="draft" {{ ($CarDetails->status == 'draft') ? 'checked' : '' }}>
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
                                    if ($CarDetails->vendor_id == $key) {
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
                                        if (isset($CarDetails->property[$attrs]) && in_array($values, $CarDetails->property[$attrs])) {
                                            $checked = 'checked';
                                        }
                                        if ($attrs == 'Vehicle Type') { 
                                        ?>
                                            <li><input type="radio" class="check" name="property[{{ $attrs }}][]" {{ $checked }} value="{{ $key .'~'. $values }}" data-radio="iradio_square-blue"><label>{{ $values }}</label></li>
                                        <?php } else { ?>
                                            <li>
                                                <input type="checkbox" class="check" name="property[{{ $attrs }}][]" {{ $checked }} value="{{ $key .'~'. $values }}" data-checkbox="icheckbox_flat-blue">
                                                <label>{{ $values }}</label>
                                            </li>
                                        <?php } ?>
<!--                                        <li>
                                            <input type="checkbox" class="check" name="property[{{ $attrs }}][]" value="{{ $key .'~'. $values }}" {{ $checked }} data-checkbox="icheckbox_flat-blue">
                                            <label>{{ $values }}</label>
                                        </li>-->
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="white-box">
                        <h3 class="box-title">Feature Image</h3><hr>
                        <div class="form-group">
                            <input type="file" class="dropify" name="feature_image" data-default-file="{{ $CarDetails->feature_image }}" />
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
                                        <input type="radio" name="gst_applicable" value="1" {{ ($CarDetails->gst_applicable == 1) ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="gst_applicable" value="0" {{ ($CarDetails->gst_applicable == 0) ? 'checked' : '' }}> No </label>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">GST Number</h3><hr>
                            <div class="form-group">
                                <input type="text" name="gst_number" class="form-control" placeholder="Enter GST No" value="{{ $CarDetails->gst_number }}">
                            </div>
                            <h3 class="box-title">Company Name</h3><hr>
                            <div class="form-group">
                                <input type="text" name="gst_legal_name" class="form-control" value="{{ $CarDetails->gst_legal_name }}" placeholder="Enter Company Name">
                            </div>
                        </div>
<!--                        <div class="white-box">
                            <h3 class="box-title">Paytm MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="paytm_mid" class="form-control" value="{{ $CarDetails->paytm_mid }}" placeholder="Enter Paytm MID">
                            </div>
                            <h3 class="box-title">HDFC MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="hdfc_mid" class="form-control" value="{{ $CarDetails->hdfc_mid }}" placeholder="Enter HDFC MID">
                            </div>
                        </div>-->
                        <div class="white-box">
                            <h3 class="box-title">Guide Price</h3><hr>
                            <div class="form-group">
                                <input type="number" name="guide_price_per_day" class="form-control" value="{{ $CarDetails->guide_price_per_day }}" placeholder="Guide Price Per Day" required>
                                @if ($errors->has('guide_price_per_day'))
                                <span class="text-danger">{{ $errors->first('guide_price_per_day') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Show Price</h3><hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="1" {{ ($CarDetails->show_price == 1) ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="0" {{ ($CarDetails->show_price == 0) ? 'checked' : '' }}> No </label>
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
        var length = '<?= !empty($CarDetails->faqs) ? count($CarDetails->faqs) : 0 ?>';
        $('.dropify').dropify();

        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });
        
        $(document).on('click', '#addNewRow', function () {
            length++;
            $("#policy-container").append('<tr id="'+ length +'"><td><input type="text" class="form-control" name="faqs['+ length +'][title]"></td><td><textarea rows="2" class="form-control" name="faqs['+ length +'][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i'+ length +'"></i></td></tr>');
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