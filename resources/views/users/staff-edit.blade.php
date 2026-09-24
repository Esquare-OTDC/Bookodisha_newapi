@extends('layouts.app')

@section('title','Staff Edit')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Edit Sub-user</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        @if(Session::has('success'))
                            <p style="color: #3bbc2e; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                
                        <form action="{{ route('staff-edit-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <input type="hidden" name="id" value="{{ $StaffDetails->id }}" />
                            
                            <div class="form-body">
                                <div class="row">
                                    @if(Auth::user()->access_type == 'superadmin')
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Choose Vendor</label><span class="required_field">*</span>
                                            <select class="form-control" id="vendor" name="vendor">
                                                <option value="">Select Vendor</option>
                                                @if(Auth::user()->role == 1)
                                                    <option {{ ($StaffDetails->vendor_id == Auth::user()->id) ? 'selected' : '' }} value="{{ Auth::user()->id }}">Self</option>
                                                @endif
                                                @foreach ($VendorDetails as $vendorId => $companyName)
                                                    <option {{ ($StaffDetails->vendor_id == $vendorId) ? 'selected' : '' }} value="{{ $vendorId }}">{{ $companyName }}</option>
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
                                            <input type="email" name="email" class="form-control" required value="{{ $StaffDetails->email }}" autocomplete="off" maxlength="50">
                                            
                                            @if ($errors->has('email'))
                                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">User Type</label><span class="required_field">*</span>
                                            <select class="form-control" id="userType" name="user_role" required>
                                                @if(Auth::user()->access_type == 'superadmin')
                                                <option {{ ($StaffDetails->access_type == 'superadmin') ? 'selected' : '' }} value="superadmin">Sub-user</option>
                                                @else
                                                <option {{ ($StaffDetails->access_type == 'vendor') ? 'selected' : '' }} value="vendor">Sub-user</option>
                                                @endif
                                                <option {{ ($StaffDetails->user_role == 'agent_staff') ? 'selected' : '' }}  value="agent_staff">Offline Agent</option>
                                            </select>
                                            @if ($errors->has('user_role'))
                                            <span class="text-danger">{{ $errors->first('user_role') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Book From</label><span class="required_field">*</span>
                                            <select class="form-control" name="user_book_from" required>
                                                <option value="live" {{ ($StaffDetails->user_book_from == 'live') ? 'selected' : '' }}>Live Inventory</option>
                                                <option value="blocked" {{ ($StaffDetails->user_book_from == 'blocked') ? 'selected' : '' }}>Blocked Inventory</option>
                                                <option value="both" {{ ($StaffDetails->user_book_from == 'both') ? 'selected' : '' }}>Both (Live & Blocked)</option>
                                            </select>
                                            @if ($errors->has("user_book_from")) 
                                            <span class="text-danger">{{ $errors->first("user_book_from") }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="paymentDiv">
                                        <div class="form-group">
                                            <label class="control-label">Payment Method</label><span class="required_field">*</span>
                                            <select class="form-control" name="user_payment" required>
                                                <option value="cash" {{ ($StaffDetails->user_payment == 'cash') ? 'selected' : '' }}>Cash</option>
                                                <option value="hdfc" {{ ($StaffDetails->user_payment == 'hdfc') ? 'selected' : '' }}>Link</option>
                                                <option value="both" {{ ($StaffDetails->user_payment == 'both') ? 'selected' : '' }}>Both (Cash & Link)</option>
                                                <option value="credit" {{ ($StaffDetails->user_payment == 'credit') ? 'selected' : '' }}>Credit</option>
                                                <option value="all" {{ ($StaffDetails->user_payment == 'all') ? 'selected' : '' }}>All (Cash, Link & Credit)</option>
                                            </select>
                                            @if ($errors->has("user_payment"))
                                            <span class="text-danger">{{ $errors->first("user_payment") }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                          
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">First Name</label><span class="required_field">*</span>
                                            <input type="text" name="first_name" class="form-control" required value="{{ $StaffDetails->first_name }}" autocomplete="off"> 
                                            
                                            @if ($errors->has('first_name'))
                                                <span class="text-danger">{{ $errors->first('first_name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Last Name</label><span class="required_field">*</span>
                                            <input type="text" name="last_name" class="form-control" required value="{{ $StaffDetails->last_name }}" autocomplete="off"> 
                                            
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
                                            <input type="text" name="phone" class="form-control numvalidate" required value="{{ $StaffDetails->phone }}" autocomplete="off">
                                            
                                            @if ($errors->has('phone'))
                                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Country</label><span class="required_field">*</span>
                                            <select class="form-control" id="country" name="country" required>
                                                <option value="">Select Country</option>
                                                @foreach ($CountryDetail as $countryId => $countryName)
                                                    <option {{ ($StaffDetails->country == $countryId) ? 'selected' : '' }} value="{{ $countryId }}">{{ $countryName }}</option>
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
                                            <select class="form-control" name="state" id="state" required>
                                                @foreach ($StateDetail as $stateId => $stateName)
                                                    <option {{ ($StaffDetails->state == $stateId) ? 'selected' : '' }} value="{{ $stateId }}">{{ $stateName }}</option>
                                                @endforeach
                                            </select>
                                            
                                            @if ($errors->has('state'))
                                                <span class="text-danger">{{ $errors->first('state') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">City</label><span class="required_field">*</span>
                                            <select class="form-control" name="city" id="city" required>
                                                @foreach ($CityDetail as $cityId => $cityName)
                                                    <option {{ ($StaffDetails->city == $cityId) ? 'selected' : '' }} value="{{ $cityId }}">{{ $cityName }}</option>
                                                @endforeach
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
                                            <input type="text" name="pincode" class="form-control numvalidate" required value="{{ $StaffDetails->pincode }}" autocomplete="off">
                                            
                                            @if ($errors->has('pincode'))
                                                <span class="text-danger">{{ $errors->first('pincode') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Address</label><span class="required_field">*</span>
                                            <input type="text" name="address" class="form-control" required value="{{ $StaffDetails->address }}" autocomplete="off">
                                            
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
                                            <select class="form-control" name="password_rem_quetion" >
                                                <option value="">Select</option>
                                                @foreach ($PasswordRemQstn as $question)
                                                    <option {{ ($StaffDetails->password_rem_quetion == $question['id']) ? 'selected' : '' }} value="{{ $question['id'] }}">{{ $question['variable_name'] }}</option>
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
                                            <input type="text" name="password_rem_ans" class="form-control" value="{{ $StaffDetails->password_rem_ans }}" autocomplete="off">
                                            
                                            @if ($errors->has('password_rem_ans'))
                                                <span class="text-danger">{{ $errors->first('password_rem_ans') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Password</label>
                                            <input type="password" name="password" class="form-control"> 
                                            @if ($errors->has('password'))
                                                <span class="text-danger">{{ ($errors->has('password')) ? 'Password must contain at least one lowercase letter, one uppercase letter, one numeber, and one special character.' : '' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Confirm Password</label>
                                            <input type="password" name="password_confirmation" class="form-control"> 
                                            @if ($errors->has('password'))
                                                <span class="text-danger">{{ $errors->first('password_confirmation') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success staffEdit"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('subuser-details')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        $(document).on('click', '.staffEdit', function() {
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
                'first_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 25
                },
                'last_name': {
                    required: true,
                    minlength: 3,
                    maxlength: 25
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
                    minlength : 5,
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
                    minlength: 8,
                    maxlength: 15,
//                    validPassword: true
                },
                'password_confirmation': {
                    minlength: 8,
                    maxlength: 15,
                    equalTo : '[name="password"]'
                }
            },
            messages: {
                'vendor': {
                    required: "Please choose vendor"
                },
                'first_name': {
                    required: "First name is required",
                    minlength: "First name cannot be less than 3 character",
                    maxlength: "First name cannot be greater than 25 character"
                },
                'last_name': {
                    required: "Last name is required",
                    minlength: "Last name cannot be less than 3 character",
                    maxlength: "Last name cannot be greater than 25 character"
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
                    minlength : "Pincode must be a 6 digit number",
                    maxlength : "Pincode must be a 6 digit number"
                },
                'address': {
                    required: "Address is required",
                    minlength : "Address field cannot be less than 5 character",
                    maxlength : "Address field cannot be greater than 500 character"
                },
                'password_rem_quetion': {
                    required: "Password reminder question is required"
                },
                'password_rem_ans': {
                    required: "Password reminder answer is required",
                    minlength : "Password reminder answer cannot be less than 3 character",
                    maxlength : "Password reminder answer cannot be greater than 15 character"
                },
                'password': {
                    minlength: "Password cannot be less than 8 character",
                    maxlength : "Password cannot be greater than 15 character"
                },
                'password_confirmation': {
                    minlength: "Confirm password cannot be less than 8 character",
                    maxlength : "Confirm password cannot be greater than 15 character",
                    equalTo: "Password and confirm password must be same"
                }
            }
        });
        
        // Get state
        $('#country').on('change',function() {
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
        $('#state').on('change',function() {
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

        // $('#userType').on('change', function () {
        //     var userType = $(this).val();
        //     if (userType == 'agent_staff') {
        //         $('#paymentDiv').html('<div class="form-group"><label class="control-label">Payment Method</label><span class="required_field">*</span><select class="form-control" name="user_payment" required><option value="cash">Cash</option><option value="hdfc">Link</option><option value="credit">Credit</option></select>@if ($errors->has("user_payment")) <span class="text-danger">{{ $errors->first("user_payment") }}</span> @endif</div>');
        //     } else {
        //         $('#paymentDiv').html('');
        //     }
        // });
    });
</script>

@endsection