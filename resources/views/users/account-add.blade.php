@extends('layouts.app')

@section('title','Add Account')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Add Account</div>
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
                
                        <form action="{{ route('account-add-request') }}"  method="POST" id='vendorForm'>
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Vendor</label><span class="required_field">*</span>
                                            <select class="form-control" id="vendor_id" name="vendor_id" required value="{{ old('vendor_id') }}">
                                                <option value="">Select Vendor</option>
                                                @foreach ($Vendors as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            
                                            @if ($errors->has('vendor_id'))
                                                <span class="text-danger">{{ $errors->first('vendor_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Property Type</label><span class="required_field">*</span>
                                            <select class="form-control" id="service_type" name="service_type" required value="{{ old('service_type') }}">
                                                <option value="">Select Property Type</option>
                                            </select>
                                            
                                            @if ($errors->has('service_type'))
                                                <span class="text-danger">{{ $errors->first('service_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Property Name</label><span class="required_field">*</span>
                                            <select class="form-control" id="service_id" name="service_id" required value="{{ old('service_id') }}">
                                                <option value="">Select Property</option>
                                            </select>
                                            
                                            @if ($errors->has('service_id'))
                                                <span class="text-danger">{{ $errors->first('service_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">HDFC MID</label><span class="required_field">*</span>
                                            <input type="text" name="hdfc_mid" class="form-control" required value="{{ old('hdfc_mid') }}" maxlength="16" autocomplete="off">
                                            
                                            @if ($errors->has('hdfc_mid'))
                                                <span class="text-danger">{{ $errors->first('hdfc_mid') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">HDFC Key</label><span class="required_field">*</span>
                                            <input type="text" name="hdfc_key" class="form-control" required value="{{ old('hdfc_key') }}" maxlength="32" autocomplete="off">
                                            
                                            @if ($errors->has('hdfc_key'))
                                                <span class="text-danger">{{ $errors->first('hdfc_key') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">HDFC Salt</label><span class="required_field">*</span>
                                            <input type="text" name="hdfc_salt" class="form-control" required value="{{ old('hdfc_salt') }}" maxlength="64" autocomplete="off">
                                            
                                            @if ($errors->has('hdfc_salt'))
                                                <span class="text-danger">{{ $errors->first('hdfc_salt') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success vendorAdd"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('link-accounts')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        
        // profile details update
        $(document).on('click', '.vendorAdd', function() {
            var x = validator.form();
            return x;
        });
        
        validator = $('#vendorForm').validate({
            rules: {
                'vendor_id' : {
                    required: true,
                },
                'service_type': {
                    required: true,
                },
                'service_id': {
                    required: true,
                },
                'hdfc_mid': {
                    required: true,
                    maxlength: 16
                },
                'hdfc_key': {
                    required: true,
                    maxlength: 32
                },
                'hdfc_salt': {
                    required: true,
                    maxlength: 64
                }
            }
        });
        
        $(document).on('change', '#vendor_id', function() {
            $('#service_type').html('<option value="">Select Property Type</option>');
            $('#service_id').html('<option value="">Select Property</option>');
            let vendorId = $(this).val();
            if (vendorId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('account-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, request_type: "get_vendor_services"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                           $('#service_type').html(responce.data);
                        }
                    }
                });
            }
        });
        
        $(document).on('change', '#service_type', function() {
            $('#service_id').html('<option value="">Select Property</option>');
            let vendorId = $('#vendor_id').val();
            let prop_type = $(this).val();
            if (vendorId != '' && prop_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('account-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, service_type: prop_type, request_type: "get_vendor_property"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                           $('#service_id').html(responce.data);
                        }
                    }
                });
            }
        });
    });
</script>

@endsection