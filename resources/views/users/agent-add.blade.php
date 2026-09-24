@extends('layouts.app')

@section('title','Add Agent')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Add agent</div>
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

                        <form action="{{ route('agent-add-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    @if(Auth::user()->access_type == 'superadmin')
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Choose Vendor</label><span class="required_field">*</span>
                                            <select class="form-control" id="vendor" name="vendor">
                                                <option value="">Select Vendor</option>                                                
                                                @foreach ($VendorDetails as $vendorId => $companyName)
                                                <option value="{{ $vendorId }}">{{ $companyName }}</option>
                                                @endforeach
                                            </select>

                                            @if ($errors->has('vendor'))
                                            <span class="text-danger">{{ $errors->first('vendor') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @else
                                    <input type="hidden" id="vendor" name="vendor" class="form-control" required value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                                    @endif                            

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Email</label><span class="required_field">*</span>
                                            <input type="email" name="email" class="form-control" required value="{{ old('email') }}" autocomplete="off" maxlength="50">
                                            @if ($errors->has('email'))
                                            <span class="text-danger">{{ $errors->first('email') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Block Booking Date</label>
                                            <input type="text" class="form-control check-quantity" name="agent_block_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" value="{{ old('agent_block_date') }}">
                                            @if ($errors->has('agent_block_date'))
                                            <span class="text-danger">{{ $errors->first('agent_block_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Agent Comission</label>(%)<span class="required_field">*</span>
                                            <input type="number" name="agent_comission" class="form-control numvalidate" min="0" max="99" required value="{{ old('agent_comission') }}"> 
                                            @if ($errors->has('agent_comission'))
                                            <span class="text-danger">{{ $errors->first('agent_comission') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Commission taken from property</label><span class="required_field"></span>
                                            <select class="form-control commission-taken" name="commission_taken" value="{{ old('commission_taken') }}">
                                                <option value=0>No</option>
                                                <option value=1>Yes</option>
                                            </select>

                                            @if ($errors->has('commission_taken'))
                                            <span class="text-danger">{{ $errors->first('commission_taken') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row commission-apply" style="display: none">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Commission apply (More than 100 nights in a month)</label><span class="required_field"></span>
                                            <select class="form-control" name="night_limit_commission" value="{{ old('night_limit_commission') }}">
                                                <option value=0>No</option>
                                                <option value=1>Yes</option>
                                            </select>

                                            @if ($errors->has('night_limit_commission'))
                                            <span class="text-danger">{{ $errors->first('night_limit_commission') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Commission apply (For foreign visitors)</label><span class="required_field"></span>
                                            <select class="form-control" name="foreign_visitors_commission" value="{{ old('foreign_visitors_commission') }}">
                                                <option value=0>No</option>
                                                <option value=1>Yes</option>
                                            </select>

                                            @if ($errors->has('foreign_visitors_commission'))
                                            <span class="text-danger">{{ $errors->first('foreign_visitors_commission') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">First Name</label><span class="required_field">*</span>
                                            <input type="text" name="first_name" class="form-control" required value="{{ old('first_name') }}" autocomplete="off"> 

                                            @if ($errors->has('first_name'))
                                            <span class="text-danger">{{ $errors->first('first_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Last Name</label><span class="required_field">*</span>
                                            <input type="text" name="last_name" class="form-control" required value="{{ old('last_name') }}" autocomplete="off"> 

                                            @if ($errors->has('last_name'))
                                            <span class="text-danger">{{ $errors->first('last_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Phone</label><span class="required_field">*</span>
                                            <input type="text" name="phone" class="form-control numvalidate" required value="{{ old('phone') }}">

                                            @if ($errors->has('phone'))
                                            <span class="text-danger">{{ $errors->first('phone') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Country</label><span class="required_field">*</span>
                                            <select class="form-control" id="country" name="country" required value="{{ old('country') }}">
                                                <option value="">Select Country</option>
                                                @foreach ($CountryDetail as $countryId => $countryName)
                                                <option value="{{ $countryId }}">{{ $countryName }}</option>
                                                @endforeach
                                            </select>

                                            @if ($errors->has('country'))
                                            <span class="text-danger">{{ $errors->first('country') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">State</label><span class="required_field">*</span>
                                            <select class="form-control" name="state" id="state" required value="{{ old('state') }}">
                                                <option value="">Select State</option>
                                            </select>

                                            @if ($errors->has('state'))
                                            <span class="text-danger">{{ $errors->first('state') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">City</label><span class="required_field">*</span>
                                            <select class="form-control" name="city" id="city" required value="{{ old('city') }}">
                                                <option value="">Select City</option>
                                            </select>

                                            @if ($errors->has('city'))
                                            <span class="text-danger">{{ $errors->first('city') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Pin Code</label><span class="required_field">*</span>
                                            <input type="text" name="pincode" class="form-control numvalidate" required value="{{ old('pincode') }}" autocomplete="off">

                                            @if ($errors->has('pincode'))
                                            <span class="text-danger">{{ $errors->first('pincode') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Address</label><span class="required_field">*</span>
                                            <input type="text" name="address" class="form-control" required value="{{ old('address') }}" autocomplete="off">

                                            @if ($errors->has('address'))
                                            <span class="text-danger">{{ $errors->first('address') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Password Reminder Question</label>
                                            <select class="form-control" name="password_rem_quetion" value="{{ old('password_rem_quetion') }}">
                                                <option value="">Select</option>
                                                @foreach ($PasswordRemQstn as $question)
                                                <option value="{{ $question['id'] }}">{{ $question['variable_name'] }}</option>
                                                @endforeach
                                            </select>

                                            @if ($errors->has('password_rem_quetion'))
                                            <span class="text-danger">{{ $errors->first('password_rem_quetion') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Password Reminder Answer</label>
                                            <input type="text" name="password_rem_ans" class="form-control" value="{{ old('password_rem_ans') }}" autocomplete="off">

                                            @if ($errors->has('password_rem_ans'))
                                            <span class="text-danger">{{ $errors->first('password_rem_ans') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Password</label><span class="required_field">*</span>
                                            <input type="password" name="password" class="form-control" required> 
                                            @if ($errors->has('password'))
                                            <span class="text-danger">{{ $errors->first('password') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Confirm Password</label><span class="required_field">*</span>
                                            <input type="password" name="password_confirmation" class="form-control" required> 
                                            @if ($errors->has('password'))
                                            <span class="text-danger">{{ $errors->first('password') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>

                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success staffAdd"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('agent-details')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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

        $('#datepicker-autoclose').datepicker({
            multidate: true,
            format: 'dd-mm-yyyy',
            endDate: '+120d'
        });

        // profile details update
        $(document).on('click', '.staffAdd', function () {
            var x = validator.form();
            return x;
        });
        $.validator.addMethod('validPassword', function (value) {
            return /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/.test(value);
        }, 'Password contain at least one lowercase letter, one uppercase letter, one numeber, and one special character.');

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
                    maxlength: 50
                },
                'last_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 50
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
                    maxlength: 500
                },
                // 'password_rem_quetion': {
                //     required: true
                // },
                // 'password_rem_ans': {
                //     required: true,
                //     minlength: 3,
                //     maxlength: 15
                // },
                'password': {
                    required: true,
                    minlength: 8,
                    maxlength: 15,
                    validPassword: true
                },
                'password_confirmation': {
                    required: true,
                    minlength: 8,
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
                    minlength: "First name cannot be less than 3 character",
                    maxlength: "First name cannot cannot be greater than 50 character"
                },
                'last_name': {
                    required: "Last name is required",
                    minlength: "Last name cannot be less than 3 character",
                    maxlength: "Last name cannot be greater than 50 character"
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
                    minlength: "Address field cannot be less than 5 character",
                    maxlength: "Address field cannot be greater than 500 character"
                },
                // 'password_rem_quetion': {
                //     required: "Password reminder question is required"
                // },
                // 'password_rem_ans': {
                //     required: "Password reminder answer is required",
                //     minlength: "Password reminder answer cannot be less than 3 character",
                //     maxlength: "Password reminder answer cannot be greater than 15 character"
                // },
                'password': {
                    required: "Password is required",
                    minlength: "Password cannot be less than 8 character",
                    maxlength: "Password cannot be greater than 15 character"
                },
                'password_confirmation': {
                    required: "Confirm password is required",
                    minlength: "Confirm password cannot be less than 8 character",
                    maxlength: "Confirm password cannot be greater than 15 character",
                    equalTo: "Password and confirm password must be same"
                }
            }
        });

        // Get state
        $('#country').on('change', function () {
            var countryId = $(this).val();
            $.ajax({
                type: "POST",
                url: "{{url('city-state-details')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {countryId: countryId, request_type: "get_state_details"},
                success: function (data) {
                    $('#state').html(data);
                    $('#city').html('<option value="">Select City</option>');
                }
            });
        });

        // Get city
        $('#state').on('change', function () {
            var stateId = $(this).val();
            $.ajax({
                type: "POST",
                url: "{{url('city-state-details')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {stateId: stateId, request_type: "get_city_details"},
                success: function (data) {
                    $('#city').html(data);
                }
            });
        });

        // agent commission
        $('.commission-taken').on('change',function() {
            var stateId = $(this).val();
            if (stateId == 1) {
                $('.commission-apply').show();
            } else {
                $('.commission-apply').hide();
                $('.commission-apply select').val(0);
            }
        });
    });
</script>

@endsection