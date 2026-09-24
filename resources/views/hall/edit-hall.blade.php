@extends('layouts.app')

@section('title', 'Edit Property')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Property</li>
                <li class="breadcrumb-item active">Edit Property</li>
            </ol>
        </div>
    </div>

    @include('errors.message')

    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Edit Property</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('hall-edit-request')}}" method="POST" enctype="multipart/form-data" id="propertyForm">
                    @csrf
                    <input type="hidden" name="id" value="{{ Crypt::encryptString($HallDetails->id) }}">
                    <div class="col-md-9">
                        <div class="white-box">
                            <h3 class="box-title">Property Content</h3><hr>
                            <div class="form-group">
                                <label class="col-md-12" for="name">Name of the property <span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="name" maxlength="100" placeholder="Name of the property" value="{{ $HallDetails->property_name  }}" >
                                    @if ($errors->has('name'))
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="content">Content <span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <textarea name="content" cols="10" rows="5" >{{ $HallDetails->content }}</textarea>
                                    @if ($errors->has('content'))
                                    <span class="text-danger">{{ $errors->first('content') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Youtube Video</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="video" value="{{ $HallDetails->youtube_video  }}" placeholder="Youtube Video Link">
                                    @if ($errors->has('video'))
                                    <span class="text-danger">{{ $errors->first('video') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2" id="bannerImageGroup">
                                <label class="col-md-12">Banner Image <span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <input type="hidden" name="banner_removed" id="banner_removed" value="0">
                                    <input type="file" id="banner_image" class="dropify"  accept=".jpg,.jpeg,.png" name="banner_image" data-default-file="{{ $HallDetails->banner_image }}">
                                    @if ($errors->has('banner_image'))
                                    <span class="text-danger">{{ $errors->first('banner_image') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Gallery </label>
                                <div class="col-md-12">
                                    <div id="gallery-image" style="padding-top: .5rem;" accept=".jpg,.jpeg,.png"></div>
                                    @if ($errors->has('images.*'))
                                    <span class="text-danger">{{ $errors->first('images.*') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Terms & Conditions <span style="color:red;">*</span></h3><hr>
                            <div class="row">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <textarea name="terms_conditions" id = "terms_conditions" cols="10" rows="5" >{{ $HallDetails->terms_condition }}</textarea>
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
                                    <label>City <span style="color:red;">*</span> </label>
                                     <select class="form-control" name="district_id">
                                        <option value="">Select City</option>
                                        @foreach ($Districts as $district)
                                            <option value="{{ $district->id }}"
                                                {{ $HallDetails->district_id == $district->id ? 'selected' : '' }}>
                                                {{ $district->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('city'))
                                    <span class="text-danger">{{ $errors->first('city') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label>Place (Use for location filter) <span style="color:red;">*</span> </label>
                                    <input class="form-control" name="place" type="text" value="{{ $HallDetails->place }}" >
                                    @if ($errors->has('place'))
                                    <span class="text-danger">{{ $errors->first('place') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Address</label>
                                    <input id="autocomplete" class="form-control" name="address" value="{{ $HallDetails->address }}" placeholder="Enter Address" onFocus="geolocate()" type="text" />
                                    @if ($errors->has('address'))
                                    <span class="text-danger">{{ $errors->first('address') }}</span>
                                    @endif
                                    <input type="hidden" id="map_lat" name="map_lat" value="{{ $HallDetails->map_lat }}">
                                    <input type="hidden" id="map_lng" name="map_lng" value="{{ $HallDetails->map_lng }}">
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Contact Information</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Contact Email</label>
                                    <input type="email" class="form-control" name="contact_email" placeholder="Contact email" value="{{ $HallDetails->contact_email }}" autocomplete="off" />
                                    @if ($errors->has('contact_email'))
                                    <span class="text-danger">{{ $errors->first('contact_email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Manager Name</label>
                                    <input type="text" class="form-control" name="manager_name" placeholder="Manager Name" value="{{ $HallDetails->manager_name }}" autocomplete="off" />
                                    @if ($errors->has('manager_name'))
                                    <span class="text-danger">{{ $errors->first('manager_name') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Manager Contact Number <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" name="contact_number" placeholder="Manager Contact Number" value="{{ $HallDetails->manager_contact  }}" autocomplete="off" maxlength="10"  />
                                    @if ($errors->has('contact_number'))
                                    <span class="text-danger">{{ $errors->first('contact_number') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Reception Contact Number <span style="color:red;">*</span></label>
                                    <input type="text" class="form-control" name="reception_contact" value="{{ $HallDetails->reception_contact }}" placeholder="Reception Contact Number" autocomplete="off" maxlength="15" >
                                    @if ($errors->has('reception_contact'))
                                    <span class="text-danger">{{ $errors->first('reception_contact') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Additional Email</label>
                                    <input type="text" class="form-control" name="additional_email" placeholder="Additional Contact Email" autocomplete="off" value="{{ $HallDetails->additional_email }}">
                                    @if ($errors->has('additional_email'))
                                    <span class="text-danger">{{ $errors->first('additional_email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Additional Contact Number</label>
                                    <input type="text" class="form-control" name="additional_phone" placeholder="Additional Contact Number" autocomplete="off" value="{{ $HallDetails->additional_contact }}">
                                    @if ($errors->has('additional_phone'))
                                    <span class="text-danger">{{ $errors->first('additional_phone') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Address</label>
                                    <input class="form-control" name="real_address" placeholder="Enter Address" type="text" value="{{ $HallDetails->hotel_address  }}" autocomplete="off" maxlength="100" />
                                    @if ($errors->has('real_address'))
                                    <span class="text-danger">{{ $errors->first('real_address') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!--  -->
                    </div>
                    <div class="col-md-3">
                        <div class="white-box">
                            <h3 class="box-title">Publish</h3><hr>
                            <div class="form-group">
                                <div class="radio radio-info">
                                    <input type="radio" name="publish_status" id="radio1" value="PUBLISH" {{ ($HallDetails->publish_status == 'PUBLISH') ? 'checked' : '' }}>
                                    <label for="radio1">Publish</label>
                                </div>
                                <div class="radio radio-info">
                                    <input type="radio" name="publish_status" id="radio2" value="DRAFT" {{ ($HallDetails->publish_status == 'DRAFT') ? 'checked' : '' }}>
                                    <label for="radio2">Draft</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="float: right;margin-top: -20px;">Submit</button>
                        </div>
                        <?php if (Auth::user()->access_type == 'superadmin') { ?>
                            <!-- <div class="white-box">
                                <h3 class="box-title">Vendor</h3><hr>
                                <select class="form-control" id="venderId" name="vender_id" >
                                    <option value="">Select Vendor</option>
                                    <?php
                                    foreach ($Vendors as $key => $value) {
                                        $checked = '';
                                        if ($HallDetails->vender_id == $key) {
                                            $checked = 'selected';
                                        }
                                        echo '<option value="' . $key . '"' . $checked . '>' . $value . '</option>';
                                    }
                                    ?>
                                </select>
                                @if ($errors->has('vender_id'))
                                <span class="text-danger">{{ $errors->first('vender_id') }}</span>
                                @endif
                            </div> -->
                        <?php } else { ?>
                            <input type="hidden" id="vendor" name="vender_id" class="form-control" value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                        <?php } ?>
                        <input type="hidden" name="feature_removed" id="feature_removed" value="0">
                        <div class="white-box">
                            <h3 class="box-title">Feature Image <span style="color:red;">*</span></h3><hr>
                            <div class="form-group" id="featureImageGroup">
                                <input type="file" class="dropify" name="feature_image" accept=".jpg,.jpeg,.png" data-default-file="{{ $HallDetails->feature_image }}"  />
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
                                        <input type="radio" name="gst_applicable" value="1" {{ old('gst_applicable', $HallDetails->gst_applicable) == 1 ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="gst_applicable" value="0" {{ old('gst_applicable', $HallDetails->gst_applicable) == 0 ? 'checked' : '' }}> No </label>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">GST Number <span id="gst-" style="color:red; display:none;">*</span> </h3><hr>
                            <div class="form-group">
                                <input type="text" id="gst_number" name="gst_number" class="form-control" value="{{ $HallDetails->gst_number }}">
                            </div>
                            <h3 class="box-title">Company Name</h3><hr>
                            <div class="form-group">
                                <input type="text" name="gst_legal_name" class="form-control" value="{{ $HallDetails->company_name  }}" placeholder="Enter Company Name">
                            </div>
                        </div>
<!--                        <div class="white-box">
                            <h3 class="box-title">Paytm MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="paytm_mid" class="form-control" value="{{ $HallDetails->paytm_mid }}" placeholder="Enter Paytm MID">
                            </div>
                            <h3 class="box-title">HDFC MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="hdfc_mid" class="form-control" value="{{ $HallDetails->hdfc_mid }}" placeholder="Enter HDFC MID">
                            </div>
                        </div>-->
                        <div class="white-box">
                            <h3 class="box-title">Show Price</h3><hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="1" {{ old('show_price', $HallDetails->show_price) == 1 ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="0" {{ old('show_price', $HallDetails->show_price) == 0 ? 'checked' : '' }}> No </label>
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
        var length = {{ count($HallDetails->policy ?? []) }};
        var drEvent = $('.dropify').dropify();
        drEvent.on('dropify.afterClear', function (event, element) {
            if ($(element.input).attr('name') == 'feature_image') {
                $('#feature_removed').val('1');
            }

            if ($(element.input).attr('name') == 'banner_image') {
                $('#banner_removed').val('1');
            }
        });


        $('#subUser').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Sub user'
        });

        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });

        $('.clockpicker').clockpicker({
            donetext: 'Done',
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

    CKEDITOR.replace( 'content' );
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

    // Toggle for GST button
    function toggleGST() {
        let gstApplicable = $('input[name="gst_applicable"]:checked').val();

        if (gstApplicable == '1') {
            $('#gst-').show();
        } else {
            $('#gst-').hide();
            $('.gst-error').remove();
        }
    }
    $('input[name="gst_applicable"]').on('change', toggleGST);
    toggleGST();

    // Form Validation
    $('#propertyForm').on('submit', function(e) {

        // Update CKEditor values
        for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }

        let hasError = false;

        // Remove old errors
        $('.dynamic-error').remove();

        function showError(element, message) {
            $(element).after(
                '<span class="text-danger dynamic-error" style="display:block;margin-top:5px;">' +
                message +
                '</span>'
            );
            hasError = true;
        }

        // Property Name
        if ($('input[name="name"]').val().trim() === '') {
            showError('input[name="name"]', 'Property Name is required');
        }

        if ($('textarea[name="content"]').val().trim() === '') {
            $('#cke_content').after(
                '<span class="text-danger dynamic-error d-block">Content is required</span>'
            );
            hasError = true;
        }

        if ($('textarea[name="terms_conditions"]').val().trim() === '') {
            $('#cke_terms_conditions').after(
                '<span class="text-danger dynamic-error d-block">Terms & Conditions is required</span>'
            );
            hasError = true;
        }
        // District
        if ($('select[name="district_id"]').val() === '') {
            showError('select[name="district_id"]', 'District is required');
        }

        // Place
        if ($('input[name="place"]').val().trim() === '') {
            showError('input[name="place"]', 'Place is required');
        }

        // Manager Contact Number
        let contact = $('input[name="contact_number"]').val();

        if (contact.trim() === '') {
            showError(
                'input[name="contact_number"]',
                'Manager Contact Number is required'
            );
        } else if (!/^[6-9]\d{9}$/.test(contact)) {
            showError(
                'input[name="contact_number"]',
                'Enter a valid 10-digit mobile number'
            );
        }

        // Reception Contact Number
        let reception = $('input[name="reception_contact"]').val();
        if (reception.trim() === '') {
            showError(
                'input[name="reception_contact"]',
                'Reception Contact Number is required'
            );
        } else if (!/^[6-9]\d{9}$/.test(reception)) {
            showError(
                'input[name="reception_contact"]',
                'Enter a valid 10-digit mobile number'
            );
        }
        // Vendor
        @if(Auth::user()->access_type == 'superadmin')
            if ($('#venderId').val() === '') {
                showError('#venderId', 'Vendor is required');
            }
        @endif

        if ($('input[name="gst_applicable"]:checked').val() == '1') {
            if ($('#gst_number').val().trim() === '') {
                $('#gst_number').closest('.form-group').append(
                    '<span class="text-danger dynamic-error gst-error d-block">GST Number is required</span>'
                );
                hasError = true;
            }
        }
        // Banner Image Size Validation (2MB)
        let bannerImage = $('input[name="banner_image"]')[0];
        if (bannerImage.files.length > 0) {
            let file = bannerImage.files[0];

            if (file.size > (2 * 1024 * 1024)) {
                $('input[name="banner_image"]').closest('.col-md-12').append(
                    '<span class="text-danger dynamic-error d-block mt-2">' +
                    'Banner image must be less than or equal to 2 MB.' +
                    '</span>'
                );
                hasError = true;
            }
        }

        // Feature Image Size Validation (2MB)
        let featureImage = $('input[name="feature_image"]')[0];
        if (featureImage.files.length > 0) {
            let file = featureImage.files[0];

            if (file.size > (2 * 1024 * 1024)) {
                $('input[name="feature_image"]').closest('.form-group').append(
                    '<span class="text-danger dynamic-error d-block mt-2">' +
                    'Feature image must be less than or equal to 2 MB.' +
                    '</span>'
                );
                hasError = true;
            }
        }
        // Feature Image Validation
        if ($('#feature_removed').val() == '1' && $('input[name="feature_image"]')[0].files.length === 0){
            hasError = true;
            $('#featureImageGroup .dynamic-error').remove();
            $('#featureImageGroup').append(
                '<span class="text-danger dynamic-error d-block mt-2">Feature Image is required.</span>'
            );
        }
        // Banner Image Validation
        if ($('#banner_removed').val() == '1' && $('#banner_image')[0].files.length === 0) {
            hasError = true;
            $('#bannerImageGroup .dynamic-error').remove();

            $('#bannerImageGroup .dropify-wrapper').after(
                '<span class="text-danger dynamic-error d-block mt-2">Banner Image is required.</span>'
            );
        }
        // Youtube Video
        let video = $('input[name="video"]').val().trim();
        if (video !== '') {
            try {
                let url = new URL(video);
                if (
                    url.hostname !== 'www.youtube.com' &&
                    url.hostname !== 'youtube.com' &&
                    url.hostname !== 'youtu.be'
                ) {
                    throw new Error();
                }

            } catch (e) {
                $('input[name="video"]')
                    .closest('.col-md-12')
                    .append(
                        '<span class="text-danger dynamic-error d-block mt-1">Please enter a valid YouTube URL</span>'
                    );

                hasError = true;
            }
        }
        if (hasError) {
            console.log($('.dynamic-error').length);
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.dynamic-error:first').offset().top - 120
            }, 500);
            return false;
        }
    });
</script>

@endsection
