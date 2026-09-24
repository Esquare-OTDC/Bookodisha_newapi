@extends('layouts.app')

@section('title','Manage home page')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">CMS</li>
                <li class="breadcrumb-item active">Manage home page</li>
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
                <form class="form-horizontal" action="{{ route('homepage-edit-request') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ $HomepageData[0]->id }}">
                    <div class="form-group">
                        <label class="col-md-12">Banner Image</label>
                        <div class="col-md-12">
                            <div id="gallery-image" style="padding-top: .5rem;"></div>
                            @if ($errors->has('images'))
                            <span class="text-danger">{{ $errors->first('images') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Contact Email<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="email" class="form-control" name="email" value="{{ $content['contact_email'] }}" required>
                                    @if ($errors->has('email'))
                                    <span class="text-danger">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label class="col-md-12" for="name">Contact Phone<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="phone" value="{{ $content['contact_phone'] }}" required>
                                    @if ($errors->has('phone'))
                                    <span class="text-danger">{{ $errors->first('phone') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="name">Facebook Link<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="fb_link" value="{{ $content['fb_link'] }}" required>
                                    @if ($errors->has('fb_link'))
                                    <span class="text-danger">{{ $errors->first('fb_link') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Logo</label>
                                <div class="col-md-12">
                                    <input type="file" id="input-file-now" class="dropify" name="logo" data-default-file="{{ $content['logo'] }}">
                                    @if ($errors->has('logo'))
                                    <span class="text-danger">{{ $errors->first('logo') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Twitter Link<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="twitter_link" value="{{ $content['twitter_link'] }}" required>
                                    @if ($errors->has('twitter_link'))
                                    <span class="text-danger">{{ $errors->first('twitter_link') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="name">Youtube Link<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="youtube_link" value="{{ $content['youtube_link'] }}" required>
                                    @if ($errors->has('youtube_link'))
                                    <span class="text-danger">{{ $errors->first('youtube_link') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Instagram Link<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="instagram_link" value="{{ $content['instagram_link'] }}" required>
                                    @if ($errors->has('instagram_link'))
                                    <span class="text-danger">{{ $errors->first('instagram_link') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="name">Pinterest Link<span class="text-danger">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="pinterest_link" value="{{ $content['pinterest_link'] }}" required>
                                    @if ($errors->has('pinterest_link'))
                                    <span class="text-danger">{{ $errors->first('pinterest_link') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success pull-right"><i class="fa fa-save"></i> Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">

</style>

<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();
        
        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });
    });    
</script>

@endsection