@extends('layouts.app')

@section('title', 'Add New Slider')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">CMS</li>
                <li class="breadcrumb-item active">Add New Slider</li>
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
                <h2 id="PageHeading">Add New Slider</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('add-slider-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="start_date" id="start_date">
                    <input type="hidden" name="end_date" id="end_date">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Section</label>
                                        <select name="section" class="form-control" required>
                                            <option value="">Select Section</option>
                                            @foreach($Services as $key => $value)
                                            @if($key != 'mmt-integration' && $key != 'food-ordering' && $key != 'merchandise')
                                            <option value="{{$key}}">{{$value}}</option>
                                            @endif
                                            @endforeach
                                        </select>
                                        @if ($errors->has('section'))
                                        <span class="text-danger">{{ $errors->first('section') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Link</label>
                                        <input type="text" class="form-control" name="url" placeholder="Hyperlink (if any)">
                                        @if ($errors->has('url'))
                                        <span class="text-danger">{{ $errors->first('url') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Display Type</label>
                                        <select name="display_type" class="form-control" id="display_type" required>
                                            <option value="complete">All Time</option>
                                            <option value="partial">For a Time period</option>
                                        </select>
                                        @if ($errors->has('display_type'))
                                        <span class="text-danger">{{ $errors->first('display_type') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6" id="durationDiv">
<!--                                    <div class="form-group">
                                        <label class="" for="name">Duration</label>
                                        <input class="form-control input-daterange-datepicker" id="duration" type="text" name="duration" required>
                                        @if ($errors->has('duration'))
                                        <span class="text-danger">{{ $errors->first('duration') }}</span>
                                        @endif
                                    </div>-->
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="" for="name">Content</label>
                                        <textarea class="form-control" name="content" cols="10" rows="5"></textarea>
                                        @if ($errors->has('content'))
                                        <span class="text-danger">{{ $errors->first('content') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="" for="name">Image</label>
                                        <input type="file" id="input-file-now" class="dropify" name="slider_image" required>
                                        @if ($errors->has('slider_image'))
                                        <span class="text-danger">{{ $errors->first('slider_image') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <button type="submit" id="saveData" class="btn btn-primary">Submit</button>
                            <a href="{{url('manage-slider')}}"><button type="button" class="btn btn-secondary">Cancel</button></a>
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
        $('.dropify').dropify();
        $(document).on("change", "#display_type", function() {
            var display_type = $(this).val();
            if (display_type == 'partial') {
                $('#durationDiv').html('<div class="form-group"><label for="name">Duration</label><input class="form-control input-daterange-datepicker" id="duration" name="duration" required> @if ($errors->has('duration')) <span class="text-danger">{{ $errors->first('duration') }}</span> @endif</div>');
                $('.input-daterange-datepicker').daterangepicker({
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment().add('+1', 'days'),
                    minDate: moment(),
//                    maxDate: moment().add('+120','days'),
                    timePicker: true,
                    timePickerIncrement: 5,
                    timePicker12Hour: true,
                    timePickerSeconds: false,
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
            } else {
                $('#durationDiv').html('');
            }
        });
        
        $(document).on("click", "#saveData", function() {
            let startDate = $("#duration").data('daterangepicker').startDate.format('DD-MM-YYYY H:m:s');
            let endDate = $("#duration").data('daterangepicker').endDate.format('DD-MM-YYYY H:m:s');
            $('#start_date').val(startDate);
            $('#end_date').val(endDate);
        });
    });
//    CKEDITOR.replace( 'content' );
</script>

@endsection