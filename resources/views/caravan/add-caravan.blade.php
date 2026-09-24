@extends('layouts.app')

@section('title', 'Add New Car')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Caravan</li>
                <li class="breadcrumb-item active">Add new caravan</li>
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
                <h2 id="PageHeading">Add New Caravan</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('caravan-add-request') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="col-md-9">
                    <div class="white-box">
                        <h3 class="box-title">Caravan Content</h3><hr>
                        <div class="form-group">
                            <label class="col-md-12" for="name">Title</label>
                            <div class="col-md-12">
                                <input type="text" class="form-control" name="title" placeholder="Name of the Caravan" required>
                                @if ($errors->has('title'))
                                <span class="text-danger">{{ $errors->first('title') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-12" for="contents">Content</label>
                            <div class="col-md-12">
                                <textarea name="contents" cols="10" rows="5" required></textarea>
                                @if ($errors->has('contents'))
                                <span class="text-danger">{{ $errors->first('contents') }}</span>
                                @endif
                            </div>
                        </div>
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
                                    <textarea name="terms_conditions" cols="10" rows="5" required></textarea>
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
                                        </tbody>
                                    </table>
                                    <span class="btn btn-info btn-sm" id="addNewRow" style="float: right;"><i class="icon-plus"></i> Add item</span>
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
                                    @foreach ($CityDetail as $value)
                                        <option value="{{ $value }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('city'))
                                    <span class="text-danger">{{ $errors->first('city') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Real address</label>
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
                        <h3 class="box-title">Pricing</h3><hr>
                        <div class="row">
                            @foreach($CarAttributes as $key => $values)
                            <div class="form-group col-md-4 mt-2">
                                <input type="radio" class="check" name="car_book_type" onchange="removeRequired('{{ $key }}')" value="{{ $key }}" data-radio="iradio_square-blue"> <label><b>{{ $values }}</b></label>
                            </div>
                            <div class="form-group col-md-5">
                                <input type="number" class="form-control per_day_p" id="price_per_day{{ $key }}" name="price_per_day{{ $key }}" placeholder="Price per day" min="1">
                                @if ($errors->has('price_per_day'))
                                    <span class="text-danger">{{ $errors->first('price_per_day') }}</span>
                                @endif
                            </div>
                             <div class="form-group col-md-3">
                                <input type="number" class="form-control per_day_p" id="quantity{{ $key }}" name="quantity{{ $key }}" placeholder="Quantity" min="1">
                                @if ($errors->has('quantity'))
                                    <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="white-box">
                        <h3 class="box-title">Contact Information</h3><hr>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>Contact Email</label>
                                <input type="email" class="form-control" name="contact_email" placeholder="Contact email"  required/>
                                @if ($errors->has('contact_email'))
                                <span class="text-danger">{{ $errors->first('contact_email') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label class="" for="name">Contact Number</label>
                                <input type="text" class="form-control numvalidate" name="contact_number" placeholder="Contact Number" required maxlength="10"/>
                                @if ($errors->has('contact_number'))
                                <span class="text-danger">{{ $errors->first('contact_number') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Additional Email</label>
                                <input type="text" class="form-control" name="additional_email" placeholder="Additional Contact Email" autocomplete="off">
                                @if ($errors->has('additional_email'))
                                <span class="text-danger">{{ $errors->first('additional_email') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-12">
                                <label class="" for="name">Additional Contact Number</label>
                                <input type="text" class="form-control" name="additional_phone" placeholder="Additional Contact Number" autocomplete="off">
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
                    <?php if(in_array(Auth::user()->access_type, ['superadmin','admin'])) { ?>
                    <div class="white-box">
                        <h3 class="box-title">Vendor</h3><hr>
                        <select class="form-control select2" id="venderId" name="vendor_id" required>
                            <option value="">Select Vendor</option>
                            @foreach ($Vendors as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('vendor_id'))
                            <span class="text-danger">{{ $errors->first('vendor_id') }}</span>
                        @endif
                    </div>
                    <?php } else { ?>
                        <input type="hidden" id="vendor" name="vendor_id" class="form-control" value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                    <?php } ?>
                    {{-- <?php foreach ($CarAttributes as $attrs => $terms) { ?>
                        <div class="white-box">
                            <div style="font-size: 15px;"><strong>Attribute: {{$attrs}}</strong></div><hr>
                            <div class="input-group">
                                <ul class="icheck-list">
                                    <?php foreach ($terms as $key => $values) {
                                        if ($attrs == 'Caravan') {
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
                    <?php } ?> --}}
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
        var length = 0;
        $('.dropify').dropify();
        $('#gallery-image').imageUploader();

        $(document).on('click', '#addNewRow', function () {
            length++;
            $("#policy-container").append('<tr id="'+ length +'"><td><input type="text" class="form-control" name="faqs['+ length +'][title]"></td><td><textarea rows="2" class="form-control" name="faqs['+ length +'][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i'+ length +'"></i></td></tr>');
        });

        $(document).on('click', '.deleteRow', function () {
            var id = $(this).attr('id').replace('i','');
            $("tr").remove("#"+id);
        });



    });
    $('input[name="car_book_type"]').on('ifChecked', function(event){
        removeRequired($(this).val());
    });
    let placeSearch;
    let autocomplete;
    const componentForm = {
    };

    CKEDITOR.replace('contents');
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
    function removeRequired(id) {
        $('.per_day_p').removeAttr('required');
        $('#price_per_day'+id).attr('required', true);
        $('#quantity'+id).attr('required', true);
    }
</script>

@endsection



  