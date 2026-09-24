@extends('layouts.app')

@section('title','Manage Categories')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Merchandise</li>
                <li class="breadcrumb-item active">Manage Categories</li>
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
                <form class="form-horizontal" action="{{ route('add-categories-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{ !empty($Category) ? $Category->id : '' }}" >
                    <div class="form-group">
                        <label>Category Name</label><span class="required_field">*</span>
                        <input type="text" class="form-control" name="category_name" value="{{ (!empty($Category) ? $Category->category_name : '') }}" required>
                        @if ($errors->has('category_name'))
                        <span class="text-danger">{{ $errors->first('category_name') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Parent Category</label><span class="required_field">*</span>
                        <select name="parent_id" class="form-control select2" id="parent_id" required>
                            <option value="0">None</option>
                            @foreach($CategoryData as $key => $val)
                            @php
                                $selected = (!empty($Category) && $Category->parent_id == $key) ? 'selected' : '';
                            @endphp
                            <option value="{{ $key }}" {{ $selected }}>{{ $val }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('parent_id'))
                        <span class="text-danger">{{ $errors->first('parent_id') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="control-label">Image</label>
                        <input type="file" id="image" class="dropify" name="image" {{ (!empty($Category->image) ? 'data-default-file='. $Category->image : '') }} {{ empty($Category) ? 'required' : '' }} >
                        @if ($errors->has('image'))
                        <span class="text-danger">{{ $errors->first('image') }}</span>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="button" class="btn btn-secondary"><a href="{{ url('categories') }}">Cancel</a></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();

        $(document).on('change', '#parent_id', function () {
            let category = $(this).val();
            if (category == '0') {
                $('#image').attr('required', true);
            } else {
                $('#image').removeAttr('required');
            }
        });
    });
</script>

@endsection


