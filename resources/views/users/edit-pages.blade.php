@extends('layouts.app')

@section('title', 'Edit Pages')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">CMS</li>
                <li class="breadcrumb-item active">Edit Page</li>
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
                <h2 id="PageHeading">Edit: {{ $PageContent->title }}</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('edit-page-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{ $PageContent->id }}">
                    <div class="col-md-12">
                        <div class="white-box">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Title</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="title" value="{{ $PageContent->title }}" placeholder="Page name" required>
                                    @if ($errors->has('title'))
                                    <span class="text-danger">{{ $errors->first('title') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="content">Page Content</label>
                                <div class="col-md-12">
                                    <textarea name="content" cols="10" rows="5" required>{{ $PageContent->content }}</textarea>
                                    @if ($errors->has('content'))
                                    <span class="text-danger">{{ $errors->first('content') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="name">Slug</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="slug" value="{{ $PageContent->slug }}" placeholder="Slug" required>
                                    @if ($errors->has('slug'))
                                    <span class="text-danger">{{ $errors->first('slug') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Banner Image</label>
                                <div class="col-md-12">
                                    <input type="file" id="input-file-now" data-default-file="{{ $PageContent->image }}" class="dropify" name="banner_image">
                                    @if ($errors->has('banner_image'))
                                    <span class="text-danger">{{ $errors->first('banner_image') }}</span>
                                    @endif
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
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
    });
    CKEDITOR.replace( 'content' );
</script>

@endsection