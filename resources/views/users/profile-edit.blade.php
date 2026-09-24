@extends('layouts.app')

@section('title','Profile Edit')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Profile Detail Update</div>
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
                
                        <form action="{{ route('post-profile-edit') }}" enctype="multipart/form-data"  method="POST" id='profileForm'>
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="control-label">First Name</label><span class="required_field">*</span>
                                                    <input type="text" name="first_name" class="form-control" required value="{{ Auth::user()->first_name }}"> 
                                                    @if ($errors->has('first_name'))
                                                        <span class="text-danger">{{ $errors->first('first_name') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="control-label">Last Name</label><span class="required_field">*</span>
                                                    <input type="text" name="last_name" class="form-control" required value="{{ Auth::user()->last_name }}"> 
                                                    @if ($errors->has('last_name'))
                                                        <span class="text-danger">{{ $errors->first('last_name') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="control-label">Photo</label>
                                                    <input type="file" id="photo" class="dropify" name="photo" data-default-file="{{ !empty(Auth::user()->photo) ? Auth::user()->photo : 'images/profile/no-image.png' }}">
                                                    @if ($errors->has('photo'))
                                                    <span class="text-danger">{{ $errors->first('photo') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                          
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Email</label><span class="required_field">*</span>
                                            <input type="email" name="email" class="form-control" required value="{{ Auth::user()->email }}" autocomplete="off" maxlength="50">
                                            
                                            @if ($errors->has('email'))
                                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Phone</label><span class="required_field">*</span>
                                            <input type="text" name="phone" class="form-control numvalidate" required value="{{ Auth::user()->phone }}">
                                            
                                            @if ($errors->has('phone'))
                                                <span class="text-danger">{{ $errors->first('phone') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Payment Merchand Id</label>
                                            <input type="text" name="payment_merchand_id" class="form-control" value="{{ Auth::user()->payment_merchand_id }}"  autocomplete="off" maxlength="50">
                                            
                                            @if ($errors->has('payment_merchand_id'))
                                                <span class="text-danger">{{ $errors->first('payment_merchand_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Country</label><span class="required_field">*</span>
                                            <select class="form-control" id="country" name="country" required>
                                                <option value="">Select</option>
                                                @foreach ($CountryDetail as $countryId => $countryName)
                                                    <option {{ (Auth::user()->country == $countryId) ? 'selected' : '' }} value="{{ $countryId }}">{{ $countryName }}</option>
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
                                                    <option {{ (Auth::user()->state == $stateId) ? 'selected' : '' }} value="{{ $stateId }}">{{ $stateName }}</option>
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
                                                    <option {{ (Auth::user()->city == $cityId) ? 'selected' : '' }} value="{{ $cityId }}">{{ $cityName }}</option>
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
                                            <input type="text" name="pincode" class="form-control numvalidate" required value="{{ Auth::user()->pincode }}">
                                            
                                            @if ($errors->has('pincode'))
                                                <span class="text-danger">{{ $errors->first('pincode') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Address</label><span class="required_field">*</span>
                                            <input type="text" name="address" class="form-control" required value="{{ Auth::user()->address }}">
                                            
                                            @if ($errors->has('address'))
                                                <span class="text-danger">{{ $errors->first('address') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Password Reminder Question</label><span class="required_field">*</span>
                                            <select class="form-control" name="password_rem_quetion" required>
                                                <option value="">Select</option>
                                                @foreach ($PasswordRemQstn as $question)
                                                    <option {{ (Auth::user()->password_rem_quetion == $question['id']) ? 'selected' : '' }} value="{{ $question['id'] }}">{{ $question['variable_name'] }}</option>
                                                @endforeach
                                            </select>
                                      
                                            @if ($errors->has('password_rem_quetion'))
                                                <span class="text-danger">{{ $errors->first('password_rem_quetion') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Password Reminder Answer</label><span class="required_field">*</span>
                                            <input type="text" name="password_rem_ans" class="form-control" required value="{{ Auth::user()->password_rem_ans }}">
                                            
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
                                <button type="submit" name="submit" class="btn btn-success profileEdit"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('dashboard')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        $('.dropify').dropify();
        // profile details update
        $(document).on('click', '.profileEdit', function() {
            var x = validator.form();
            return x;
        });
        
        $.validator.addMethod('validPassword', function (value) {
            return /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/.test(value);
        }, 'Password contain at least one lowercase letter, one uppercase letter, one numeber, and one special character.');
        
        validator = $('#profileForm').validate({
            rules: {
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
                    minlength : 5,
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
                    minlength: 8,
                    maxlength: 15,
//                    validPassword: true
                },
                'password_confirmation': {
                    minlength: 6,
                    maxlength: 15,
                    equalTo : '[name="password"]'
                },
            },
            messages: {
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
                    minlength : "Pincode must be a 6 digit number",
                    maxlength : "Pincode must be a 6 digit number"
                },
                'address': {
                    required: "Address is required",
                    minlength : "Address field cannto less than 5 character",
                    maxlength : "Address field cannto greater than 500 character"
                },
                'password_rem_quetion': {
                    required: "Password reminder question is required"
                },
                'password_rem_ans': {
                    required: "Password reminder answer is required",
                    minlength : "Password reminder answer can not less than 3 character",
                    maxlength : "Password reminder answer can not greater than 15 character"
                },
                'password': {
                    minlength: "Password can not be less than 8 character",
                    maxlength : "Password can not be greater than 15 character"
                },
                'password_confirmation': {
                    minlength: "Confirm password can not be less than 8 character",
                    maxlength : "Confirm password can not greater than 15 character",
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
                    $('#city').html('<option value="">Select</option>');
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
    });
</script>

@endsection