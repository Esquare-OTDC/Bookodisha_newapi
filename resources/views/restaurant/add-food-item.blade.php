@extends('layouts.app')

@section('title','Add Food Item')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Add Food Item</div>
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

                        <form action="{{ route('add-food-item-request') }}" enctype="multipart/form-data" method="POST" id='staffForm'>
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    @if(Auth::user()->access_type == 'superadmin')
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Choose Vendor</label><span class="required_field">*</span>
                                            <select class="form-control" id="vendor" name="vendor_id" required>
                                                <option value="">Select Vendor</option>
                                                @foreach ($Vendors as $vendorId => $companyName)
                                                <option value="{{ $vendorId }}">{{ $companyName }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('vendor'))
                                            <span class="text-danger">{{ $errors->first('vendor') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @else
                                    <input type="hidden" id="vendor" name="vendor_id" class="form-control" required value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Item Name</label><span class="required_field">*</span>
                                            <input type="text" name="item_name" class="form-control" required value="{{ old('item_name') }}">
                                            @if ($errors->has('item_name'))
                                            <span class="text-danger">{{ $errors->first('item_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
<!--                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Availability</label><span class="required_field">*</span>
                                            <select id="SelectDay" name="slot[]" class="form-control" multiple="multiple">
                                                @foreach ($AvailableSlots as $key => $val)
                                                <option value="{{ $key }}">{{ $val }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>-->
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Price</label><span class="required_field">*</span>
                                            <input type="number" name="price" min="1" class="form-control" required value="{{ old('price') }}">
                                            @if ($errors->has('price'))
                                            <span class="text-danger">{{ $errors->first('price') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">category</label><span class="required_field">*</span>
                                            <select name="category_id" class="form-control">
                                                <option value="">Select Category</option>
                                                @foreach ($FoodCategory as $key => $val)
                                                <option value="{{ $key }}">{{ $val }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('category_id'))
                                            <span class="text-danger">{{ $errors->first('category_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Veg / Non-veg</label><span class="required_field">*</span>
                                            <select name="is_veg" class="form-control">
                                                <option value="1">Veg</option>
                                                <option value="2">Non-veg</option>
                                            </select>
                                            @if ($errors->has('is_veg'))
                                            <span class="text-danger">{{ $errors->first('is_veg') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Status</label><span class="required_field">*</span>
                                            <select name="status" class="form-control">
                                                <option value="publish">Publish</option>
                                                <option value="draft">Draft</option>
                                            </select>
                                            @if ($errors->has('status'))
                                            <span class="text-danger">{{ $errors->first('status') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Maximum Cart Quantity</label><span class="required_field">*</span>
                                            <input type="number" name="max_cart_qty" min="1" class="form-control" required value="{{ old('max_cart_qty') }}">
                                            @if ($errors->has('max_cart_qty'))
                                            <span class="text-danger">{{ $errors->first('max_cart_qty') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Maximum Order Per Day</label><span class="required_field">*</span>
                                            <input type="number" name="max_qty" min="1" class="form-control" required value="{{ old('max_qty') }}">
                                            @if ($errors->has('max_qty'))
                                            <span class="text-danger">{{ $errors->first('max_qty') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Image</label><span class="required_field">*</span>
                                            <input type="file" id="input-file-now" class="dropify" name="image" required>
                                            @if ($errors->has('image'))
                                            <span class="text-danger">{{ $errors->first('image') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Description</label>
                                            <textarea name="short_description" class="form-control" rows="9"></textarea>
                                            @if ($errors->has('image'))
                                            <span class="text-danger">{{ $errors->first('image') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                <hr> 
                            </div>

                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success staffAdd"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('food-items')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
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
        width: 380px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 380px !important;
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

<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();
        
        $('#SelectDay').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Slots'
        });
    });
</script>

@endsection