@extends('layouts.app')

@section('title', 'Edit Merchant Product')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Merchandise</li>
                <li class="breadcrumb-item active">Edit Product</li>
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
                <h2 id="PageHeading">Edit Product</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('product-edit-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{ $Product->id }}">
                    <div class="col-md-9">
                        <div class="white-box">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Product Name</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="name"  value="{{ $Product->name }}" required>
                                    @if ($errors->has('name'))
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="descriptions">Description</label>
                                <div class="col-md-12">
                                    <textarea name="descriptions" cols="10" rows="5" required>{{ $Product->descriptions }}</textarea>
                                    @if ($errors->has('description'))
                                    <span class="text-danger">{{ $errors->first('description') }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="form-group mt-2">
                                <label class="col-md-12">Gallery</label>
                                <div class="col-md-12">
                                    <div id="gallery-image" style="padding-top: .5rem;"></div>
                                    @if ($errors->has('images.*'))
                                    <span class="text-danger">{{ $errors->first('images.*') }}</span>
                                    @endif
                                </div>
                            </div>

                            
                            <div class="form-group mt-2">
                                <label class="col-md-12">Category</label>
                                <div class="col-md-12">
                                    <select id="category" name="category" class="form-control select2" required>
                                        <option value="">Select Category</option>
                                        @foreach ($CategoryData as $key => $val)
                                        @php
                                        $selected = ($key == $Product->category) ? 'selected' : '';
                                        @endphp
                                        <option value="{{ $key }}" {{ $selected }}>{{ $val }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('category'))
                                    <span class="text-danger">{{ $errors->first('category') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="white-box">
                            <h3 class="box-title">Properties</h3><hr>
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
                                            <tbody id="faq-container">
                                                <?php
                                                if (!empty($Product->properties)) {
                                                    $countr = 0;
                                                    foreach ($Product->properties as $key => $value) {
                                                        ?>
                                                        <tr id="{{ $countr }}">
                                                            <td>
                                                                <input type="text" class="form-control" name="properties[{{ $countr }}][title]" value="{{ $key }}">
                                                            </td>
                                                            <td>
                                                                <textarea rows="2" class="form-control" name="properties[{{ $countr }}][content]" style="overflow: auto;resize: vertical;">{{ $value }}</textarea>
                                                            </td>
                                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i{{ $countr }}"></i>
                                                            </td>
                                                        </tr>
                                                        <?php $countr++;
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                        <span class="btn btn-info btn-sm" id="addNewFaq" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="white-box">
                            <h3 class="box-title">Publish</h3><hr>
                            <div class="form-group">
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio1" value="publish" {{ ($Product->status == 'publish') ? 'checked' : '' }}>
                                    <label for="radio1">Publish</label>
                                </div>
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio2" value="draft" {{ ($Product->status == 'draft') ? 'checked' : '' }}>
                                    <label for="radio2">Draft</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="float: right;margin-top: -20px;">Submit</button>
                        </div>
                        <?php if (Auth::user()->access_type == 'superadmin') { ?>
                            <div class="white-box">
                                <h3 class="box-title">Vendor</h3><hr>
                                <select class="form-control" id="vendorId" name="vendor_id" required>
                                    <option value="">Select Vendor</option>
                                    @foreach ($Vendors as $key => $value)
                                    @php
                                    $selected = ($key == $Product->vendor_id) ? 'selected' : '';
                                    @endphp
                                    <option value="{{ $key }}" {{ $selected }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('vendor_id'))
                                <span class="text-danger">{{ $errors->first('vendor_id') }}</span>
                                @endif
                            </div>
                        <?php } else { ?>
                            <input type="hidden" id="vendor" name="vendor_id" class="form-control" value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                        <?php } ?>
                        <div class="white-box">
                            <h3 class="box-title">Feature Image</h3><hr>
                            <div class="form-group">
                                <input type="file" id="input-file-now" class="dropify" name="feature_image" data-default-file="{{ $Product->feature_image }}">
                                @if ($errors->has('feature_image'))
                                <span class="text-danger">{{ $errors->first('feature_image') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">SKU</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <input type="text" class="form-control" name="sku" value="{{ $Product->sku }}" required>
                                    @if ($errors->has('sku'))
                                    <span class="text-danger">{{ $errors->first('sku') }}</span>
                                    @endif
                                </div>
                            </div>
                            <h3 class="box-title">Price</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <input type="number" class="form-control" name="price" value="{{ $Product->price }}" min="1" required>
                                    @if ($errors->has('price'))
                                    <span class="text-danger">{{ $errors->first('price') }}</span>
                                    @endif
                                </div>
                            </div>
                            <h3 class="box-title">Max Quantity</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <input type="number" class="form-control" name="max_quantity" value="{{ $Product->max_quantity }}" placeholder="Max Qty Per Booking" min="1" required>
                                    @if ($errors->has('max_quantity'))
                                    <span class="text-danger">{{ $errors->first('max_quantity') }}</span>
                                    @endif
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
        width: 500px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 500px !important;
        height: 300px !important;
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
</style>

<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        var length2 = {{ count($Product->properties) }}; 
        
        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });
                
//        $('#category').multiselect({
//            includeSelectAllOption: true,
//            nonSelectedText: 'Select Category'
//        });
        
        $('.clockpicker').clockpicker({
            donetext: 'Done',
        });
        $('.dropify').dropify();
        
        $(document).on('click', '#addNewFaq', function () {
            length2++;
            $("#faq-container").append('<tr id="'+ length2 +'"><td><input type="text" class="form-control" name="properties['+ length2 +'][title]"></td><td><textarea rows="2" class="form-control" name="properties['+ length2 +'][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i'+ length2 +'"></i></td></tr>');
        });
        
        $(document).on('click', '.deleteRow', function () {
            var id = $(this).attr('id').replace('i','');
            $("tr").remove("#"+id);
        });
        
    });
    CKEDITOR.replace('descriptions');
</script>

@endsection