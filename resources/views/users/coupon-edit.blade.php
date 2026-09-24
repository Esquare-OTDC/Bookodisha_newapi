@extends('layouts.app')

@section('title','Edit Coupon')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Edit Coupon</div>
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

                        <form action="{{ route('coupon-edit-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <input type="hidden" name="id" value="{{ $CouponDetails->id }}">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Service</label><span class="required_field">*</span>
                                            <select class="form-control" name="service_type" required value="{{ old('vendor_id') }}">
                                                <option value="">Select Service</option>
                                                @foreach ($Services as $key => $value)
                                                @php
                                                $selected = ($CouponDetails->service_type == $value->slug) ? 'selected' : '';
                                                @endphp
                                                <option value="{{ $value->slug }}" {{ $selected }}>{{ $value->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('service_type'))
                                            <span class="text-danger">{{ $errors->first('service_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Coupon Name</label><span class="required_field">*</span>
                                            <input type="text" name="coupon_name" class="form-control" required value="{{ $CouponDetails->coupon_name }}">
                                            @if ($errors->has('coupon_name'))
                                            <span class="text-danger">{{ $errors->first('coupon_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Coupon Code</label><span class="required_field">*</span>
                                            <input type="text" name="coupon_code" class="form-control" required value="{{ $CouponDetails->coupon_code }}">
                                            @if ($errors->has('coupon_code'))
                                            <span class="text-danger">{{ $errors->first('coupon_code') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Coupon Amount (%)</label><span class="required_field">*</span>
                                            <input type="number" name="coupon_amount" min="0" class="form-control" required value="{{ $CouponDetails->coupon_amount }}"> 
                                            @if ($errors->has('coupon_amount'))
                                            <span class="text-danger">{{ $errors->first('coupon_amount') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Min Order Amount</label><span class="required_field">*</span>
                                            <input type="number" name="min_order_amount" min="0" class="form-control numvalidate" required value="{{ $CouponDetails->min_order_amount }}">
                                            @if ($errors->has('min_order_amount'))
                                            <span class="text-danger">{{ $errors->first('min_order_amount') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Coupon Use Type</label><span class="required_field">*</span>
                                            <select class="form-control" id="coupon_use_type" name="coupon_use_type" value="{{ old('coupon_use_type') }}">
                                                <option value="single" {{ ($CouponDetails->coupon_use_type == 'single') ? 'selected' : '' }} >Single</option>
                                                <option value="multiple" {{ ($CouponDetails->coupon_use_type == 'multiple') ? 'selected' : '' }} >Multiple</option>
                                            </select>
                                            @if ($errors->has('coupon_use_type'))
                                            <span class="text-danger">{{ $errors->first('coupon_use_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6" id="perUserFrequency" style="{{ ($CouponDetails->coupon_use_type == 'single') ? 'display:none;' : '' }}">
                                        <div class="form-group">
                                            <label class="control-label">Frequency Per User</label><span class="required_field">*</span>
                                            <input type="number" id="frequency_per_user" name="frequency_per_user" min="0"  class="form-control" required value="{{ $CouponDetails->frequency_per_user }}">
                                            @if ($errors->has('frequency_per_user'))
                                            <span class="text-danger">{{ $errors->first('frequency_per_user') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="totFrequency">
                                        <div class="form-group">
                                            <label class="control-label">Frequency</label><span class="required_field">*</span>
                                            <input type="number" name="frequency" min="0" class="form-control" required value="{{ $CouponDetails->frequency }}">
                                            @if ($errors->has('frequency'))
                                            <span class="text-danger">{{ $errors->first('frequency') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Effectivity</label><span class="required_field">*</span>
                                            <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date">
                                            @if ($errors->has('check_date'))
                                            <span class="text-danger">{{ $errors->first('check_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Access Type</label><span class="required_field">*</span>
                                            <select class="form-control" name="access_type" required value="{{ old('access_type') }}">
                                                <option value="private" {{ ($CouponDetails->access_type == 'private') ? 'selected' : '' }}>Private</option>
                                                <option value="public" {{ ($CouponDetails->access_type == 'public') ? 'selected' : '' }}>Public</option>
                                            </select>
                                            @if ($errors->has('access_type'))
                                            <span class="text-danger">{{ $errors->first('access_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Description</label><span class="required_field">*</span>
                                            <textarea name="description" rows="5" class="form-control">{{ $CouponDetails->description }}</textarea>
                                            @if ($errors->has('description'))
                                            <span class="text-danger">{{ $errors->first('description') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="status">Status</label><span class="required_field">*</span>
                                            <select name="status" class="form-control">
                                                <option {{ ($CouponDetails->status == 'publish') ? 'selected' : '' }} value="publish">Publish</option>
                                                <option {{ ($CouponDetails->status == 'draft') ? 'selected' : '' }} value="draft">Draft</option>
                                            </select>
                                            @if ($errors->has('status'))
                                            <span class="text-danger">{{ $errors->first('status') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="radio-list">
                                                <label class="text-dark"><input type="radio" name="multi_usage" value="1" {{ ($CouponDetails->multi_usage == '1') ? 'checked' : '' }}> <b>Coupon can be used in multiple (room/ticket) purchase</b></label><br />
                                                <label class="text-dark"><input type="radio" name="multi_usage" value="0" {{ ($CouponDetails->multi_usage == '0') ? 'checked' : '' }}> <b>Coupon can be used in only single (room/ticket) purchase</b></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>

                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success staffAdd"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('coupons')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        var start_date = "<?= $CouponDetails->start_date ?>";
        var end_date = "<?= $CouponDetails->end_date ?>";
        
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: new Date(start_date),
            endDate: new Date(end_date),
            minDate: moment(),
            locale: {
              format: 'DD MMM YYYY'
            }
        });
        
        $(document).on('change', '#coupon_use_type', function () {
            var useType = $(this).val();
            if (useType == 'single') {
                $("#perUserFrequency").hide();
                $("#frequency_per_user").val("0");
            } else {
                $("#perUserFrequency").show();
            }
        });


        // profile details update
        $(document).on('click', '.staffAdd', function () {
            var x = validator.form();
            return x;
        });

        validator = $('#staffForm').validate({
            rules: {
                'vendor': {
                    required: true
                },
                'agent_comission': {
                    required: true
                },
                'first_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 15
                },
                'last_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 15
                },
                'email': {
                    required: true,
                    email: true
                },
                'phone': {
                    required: true,
                    minlength: 10,
                    maxlength: 10
                },
                'country': {
                    required: true
                },
                'state': {
                    required: true
                },
                'city': {
                    required: true
                },
                'pincode': {
                    required: true,
                    minlength: 6,
                    maxlength: 6
                },
                'address': {
                    required: true,
                    minlength: 5,
                    maxlength: 500
                },
                'password_rem_quetion': {
                    required: true
                },
                'password_rem_ans': {
                    required: true,
                    minlength: 3,
                    maxlength: 15
                },
                'password': {
                    required: true,
                    minlength: 6,
                    maxlength: 15
                },
                'password_confirmation': {
                    required: true,
                    minlength: 6,
                    maxlength: 15,
                    equalTo: '[name="password"]'
                }
            },
            messages: {
                'vendor': {
                    required: "Please choose vendor"
                },
                'agent_comission': {
                    required: "Please enter agent comission"
                },
                'first_name': {
                    required: "First name is required",
                    minlength: "First name cannot less than 3 character",
                    maxlength: "First name cannot cannot greater than 15 character"
                },
                'last_name': {
                    required: "Last name is required",
                    minlength: "Last name cannot less than 3 character",
                    maxlength: "Last name cannot cannot greater than 15 character"
                },
                'email': {
                    required: "Email is required",
                    email: "Please enter a valid mail id"
                },
                'phone': {
                    required: "Phone number is required",
                    minlength: "Phone number must be a 10 digit number",
                    maxlength: "Phone number must be a 10 digit number"
                },
                'country': {
                    required: "Country is required"
                },
                'state': {
                    required: "State is required"
                },
                'city': {
                    required: "City is required"
                },
                'pincode': {
                    required: "Pincode is required",
                    minlength: "Pincode must be a 6 digit number",
                    maxlength: "Pincode must be a 6 digit number"
                },
                'address': {
                    required: "Address is required",
                    minlength: "Address field cannto less than 5 character",
                    maxlength: "Address field cannto greater than 500 character"
                },
                'password_rem_quetion': {
                    required: "Password reminder question is required"
                },
                'password_rem_ans': {
                    required: "Password reminder answer is required",
                    minlength: "Password reminder answer can not less than 3 character",
                    maxlength: "Password reminder answer can not greater than 15 character"
                },
                'password': {
                    required: "Password is required",
                    minlength: "Password can not less than 6 character",
                    maxlength: "Password can not greater than 15 character"
                },
                'password_confirmation': {
                    required: "Confirm password is required",
                    minlength: "Confirm password can not less than 6 character",
                    maxlength: "Confirm password can not greater than 15 character",
                    equalTo: "Password and confirm password must be same"
                }
            }
        });

    });
</script>

@endsection