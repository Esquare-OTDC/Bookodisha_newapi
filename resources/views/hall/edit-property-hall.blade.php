@extends('layouts.app')
@section('title', 'Edit New Hall')
@section('content')

<div class="container-fluid" style="padding:20px;">
    <div class="row">
        <div class="col-md-12">
            <div class="white-box">
                <h3 class="box-title">Edit Hall Room</h3>
                @include('errors.message')
                <form class="form-horizontal" action="{{ route('update-hall-room') }}" method="POST" id="editHallForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="room_id" value="{{ Crypt::encryptString($RoomDetails->id) }}">
                    <input type="hidden" name="property_id" value="{{ $RoomDetails->property_id }}">
                    <div class="form-group">
                        <label class="col-md-12">Hall  Name <span style="color:red;">*</span></label>
                        <div class="col-md-12">
                            <input type="text"  class="form-control" name="title" value="{{ old('title', $RoomDetails->hall_name) }}" >
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-12">Hall Category <span style="color:red;">*</span></label>
                        <div class="col-md-12">
                            @foreach($hallCategories as $category)
                                <label style="display:block;margin-bottom:10px;">
                                    <input type="radio"
                                           name="hall_type"
                                           value="{{ $category->id }}"
                                           {{ $RoomDetails->hcategory_id == $category->id ? 'checked' : '' }}>

                                    {{ $category->hcategory_name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group" id="featureImageGroup">
                        <input type="hidden" name="feature_removed" id="feature_removed" value="0">
                        <label class="col-md-12">
                            Feature Image <span style="color:red;">*</span>
                        </label>
                        <div class="col-md-12">
                            <input type="file" name="image" class="dropify" accept=".jpg,.jpeg,.png" data-default-file="{{ !empty($RoomDetails->feature_image) ? asset($RoomDetails->feature_image) : '' }}">
                        </div>
                    </div>

                    @php
                        $gallery = json_decode($RoomDetails->gallery, true);
                    @endphp
                    <div class="form-group">
                        <label class="col-md-12">
                            Gallery Images
                        </label>
                        <div class="col-md-12">
                            <div id="gallery-image"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-12">Hall Capacity<span style="color:red;">*</span></label>
                        <div class="col-md-12">
                            <input type="number" class="form-control" name="hall_capacity" min="1" max="99999" value="{{ old('hall_capacity', $RoomDetails->room_capacity) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-12">Pricing <span style="color:red;">*</span></label>
                        <div class="col-md-6">
                            <label>Full Day Price</label>
                            <input type="number" class="form-control" name="full_day_price" id="full_day_price" min="0" max="999999" step="1" value="{{ old('full_day_price', $fullDayPrice) }}">
                        </div>

                        <div class="col-md-6">
                            <label>Half Day Price</label>
                            <input type="number" class="form-control" name="half_day_price" id="half_day_price" min="0" max="999999" step="1" value="{{ old('half_day_price', $halfDayPrice) }}">
                        </div>
                    </div>

                    @foreach ($HallAttributes as $attribute)
                        <div class="white-box" style="margin-top:20px;">
                            <h4> Attribute :
                                {{ $attribute->attribute_name }}
                            </h4>
                            <hr>
                            @foreach ($attribute->facilities as $facility)
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox"
                                           name="facilities[]"
                                           value="{{ $facility->id }}"
                                           {{ in_array($facility->id, $selectedFacilities) ? 'checked' : '' }}>

                                    {{ $facility->facility_name }}
                                </label>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="form-group" style="margin-top:20px;">
                        <label class="col-md-12">Status</label>
                        <div class="col-md-12">
                            <select name="status" class="form-control">
                                <option value="publish"
                                    {{ $RoomDetails->publish_status == 'PUBLISH' ? 'selected' : '' }}>
                                    Publish
                                </option>
                                <option value="draft"
                                    {{ $RoomDetails->publish_status == 'DRAFT' ? 'selected' : '' }}>
                                    Draft
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:30px;">
                        <div class="col-md-12">
                            <button type="submit"
                                    class="btn btn-primary">
                                <i class="fa fa-save"></i>
                                Update Hall
                            </button>

                            <a href="{{ url()->previous() }}"
                               class="btn btn-default">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    var drEvent = $('.dropify').dropify();
    drEvent = drEvent.data('dropify');
    $('.dropify').on('dropify.afterClear', function () {
        $('#feature_removed').val('1');
    });
    $('.dropify').on('change', function () {
        $('#feature_removed').val('0');
    });

    // Preload gallery images
    let preloaded = [];
    @php
        $gallery = json_decode($RoomDetails->gallery, true);
    @endphp
    @if(!empty($gallery))
        @foreach($gallery as $img)
            preloaded.push({
                id: '{{ basename($img) }}',
                src: '{{ asset($img) }}'
            });
        @endforeach
    @endif

    // Initialize ImageUploader
    $('#gallery-image').imageUploader({
        preloaded: preloaded,
        imagesInputName: 'images',
        preloadedInputName: 'old',
        maxSize: 2 * 1024 * 1024,
        maxFiles: 10
    });

    // Form Validation
    $('#editHallForm').on('submit', function(e){
        let hasError = false;
        $('.dynamic-error').remove();
        function showError(element, message){
            $(element).after(
                '<span class="text-danger dynamic-error d-block">' +
                message +
                '</span>'
            );
            hasError = true;
        }

        // Hall Name
        if($('input[name="title"]').val().trim() === ''){
            showError(
                'input[name="title"]',
                'Hall Name is required'
            );
        }

        // Hall Category
        if($('input[name="hall_type"]:checked').length === 0){
            $('input[name="hall_type"]:last')
                .closest('.col-md-12')
                .append(
                    '<span class="text-danger dynamic-error d-block">Hall Category is required</span>'
                );
            hasError = true;
        }

        // Hall Capacity
        let capacity = $('input[name="hall_capacity"]').val();
        if(capacity === '' || parseInt(capacity) <= 0){
            showError(
                'input[name="hall_capacity"]',
                'Enter valid hall capacity'
            );
        }

        // Full Day Price
        if($('#full_day_price').val().trim() === ''){
            showError(
                '#full_day_price',
                'Full Day Price is required'
            );
        }

        // Half Day Price
        if($('#half_day_price').val().trim() === ''){
            showError(
                '#half_day_price',
                'Half Day Price is required'
            );
        }

        const maxSize = 2 * 1024 * 1024;
        const featureRemoved = $('#feature_removed').val();
        if(featureRemoved == '1' && $('input[name="image"]')[0].files.length === 0){
            hasError = true;
            $('#featureImageGroup .dynamic-error').remove();
            $('#featureImageGroup .dropify-wrapper').after(
            '<span class="text-danger dynamic-error d-block mt-2">Feature Image is required.</span>');
        }else if ($('input[name="image"]')[0].files.length > 0) {
            if ($('input[name="image"]')[0].files[0].size > maxSize) {
                hasError = true;
                $('#featureImageGroup .dynamic-error').remove();
                $('#featureImageGroup .dropify-wrapper').after(
                '<span class="text-danger dynamic-error d-block mt-2">Feature Image size should not exceed 2 MB.</span>');
            }
        }

        if(hasError){
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.dynamic-error:first').offset().top - 100
            }, 500);
            return false;
        }

    });

});
</script>
@endsection
