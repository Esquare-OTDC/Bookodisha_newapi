@extends('layouts.app')

@section('title','Vendor Profile')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Vendor Profile</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        
                         @if(Session::has('success'))
                            <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                
                        <form action="{{ route('save-vendor-profile') }}" enctype="multipart/form-data"  method="POST" id='profileForm'>
                            @csrf
                            <div class="form-body">
                                <div class="row m-b-20" >
                                    <div class="col-md-12">
                                        <label class="control-label">Profile Type</label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="profile_type" name="profile_type" value="own" {{ ($profile_type == 'own') ? 'checked' : '' }}> Own
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="profile_type" name="profile_type" value="custom" {{ ($profile_type == 'custom') ? 'checked' : '' }}> Custom
                                    </div>
                                </div>
                                <div class="row own" style="display:{{ ($profile_type == 'own') ?  'block' : 'none' }};">
                                    <div class="form-group">
                                        <label class="col-md-12" for="name">Profile Url</label>
                                        <div class="col-md-12">
                                            <input type="text" class="form-control" id="profile_url" name="profile_url" value="{{ !empty($VendorProfile) ? $VendorProfile->profile_url : '' }}">
                                            @if ($errors->has('profile_url'))
                                            <span class="text-danger">{{ $errors->first('profile_url') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row custom" style="display:{{ ($profile_type == 'custom') ?  'block' : 'none' }};">
                                    <div class="form-group mt-2">
                                        <label class="col-md-12">Gallery</label>
                                        <div class="col-md-12">
                                            <div id="gallery-image" style="padding-top: .5rem;"></div>
                                            @if ($errors->has('images.*'))
                                            <span class="text-danger">{{ $errors->first('images.*') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-12" for="content">Details</label>
                                        <div class="col-md-12">
                                            <textarea name="details" id="details" cols="10" rows="5" required>{{ !empty($VendorProfile) ? $VendorProfile->details : '' }}</textarea>
                                            @if ($errors->has('details'))
                                            <span class="text-danger">{{ $errors->first('details') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                          
                                                    
                                <hr> 
                            </div>                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success profileEdit"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('dashboard')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</div>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    CKEDITOR.replace( 'details' );
    $(document).ready(function () {
        let data = '<?= $gallery; ?>';
        if (data) {
            $('#gallery-image').imageUploader({
                preloaded: JSON.parse(data),
                imagesInputName: 'images',
                preloadedInputName: 'oldimage',
                maxSize: 2 * 1024 * 1024,
                maxFiles: 10
            });
        } else {
            $('#gallery-image').imageUploader();
        }
        
        $('.profile_type').on('change',function() {
            var gateway = $('input[name="profile_type"]:checked').val();
            if (gateway != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('setting-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {request_type: "get_vendor_profile"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        let vendorData = responce.data;
                        if (gateway == 'own') {
                            $('.custom').hide();
                            $('.own').show();
                            if(vendorData) {
                                $('#profile_url').val(vendorData.profile_url);
                            }
                        } else {
                            $('.custom').show();
                            $('.own').hide();
                            if(vendorData) {
                                $('#details').val(vendorData.details);
                            }

                        } 
                    }
                });
            }
        });
        
    });
</script>

@endsection