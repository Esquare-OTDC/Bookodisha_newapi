@extends('layouts.app')

@section('title','Edit Food Category')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Edit Food Category</div>
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

                        <form action="{{ route('edit-food-category-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <input type='hidden' name='id' value='{{ $CategoryDetails->id }}'>
                            <div class="form-body">
                                <div class="row">
                                    @if(Auth::user()->access_type == 'superadmin')
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Choose Vendor</label><span class="required_field">*</span>
                                            <select class="form-control" id="vendor" name="vendor_id" required>
                                                <option value="">Select Vendor</option>
                                                @foreach ($Vendors as $vendorId => $companyName)
                                                @php
                                                $slelected = ($vendorId == $CategoryDetails->vendor_id) ? 'selected="selected"' : '';
                                                @endphp
                                                <option value="{{ $vendorId }}" {{ $slelected }}>{{ $companyName }}</option>
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
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Name</label><span class="required_field">*</span>
                                            <input type="text" name="name" class="form-control" required value="{{ $CategoryDetails->name }}">
                                            @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Availability</label><span class="required_field">*</span>
                                            <select id="SelectDay" name="slot[]" class="form-control" multiple="multiple">
                                                @foreach ($AvailableSlots as $key => $val)
                                                @php
                                                $slelected = (in_array($key, $CategoryDetails->slot)) ? 'selected="selected"' : '';
                                                @endphp
                                                <option value="{{ $key }}"  {{ $slelected }}>{{ $val }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Status</label><span class="required_field">*</span>
                                            <select name="status" class="form-control">
                                                <option value="publish" {{ ($CategoryDetails->status == 'publish') ? 'selected' : '' }}>Publish</option>
                                                <option value="draft" {{ ($CategoryDetails->status == 'draft') ? 'selected' : '' }}>Draft</option>
                                            </select>
                                            @if ($errors->has('status'))
                                            <span class="text-danger">{{ $errors->first('status') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <hr> 
                                <div class="form-actions m-t-20 text-center">
                                    <button type="submit" name="submit" class="btn btn-success staffAdd"> <i class="fa fa-check"></i> Save</button>
                                    <a href="{{url('food-category')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        width: 400px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 400px !important;
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
        $('#SelectDay').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Slots'
        });
    });
</script>

@endsection