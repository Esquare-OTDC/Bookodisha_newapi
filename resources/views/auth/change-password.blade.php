<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="keywords" content="">
        <meta name="description" content="">
        <meta name="author" content="">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('plugins/images/favicon.png') }}">
        <title>Change Password :: OTDC</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link href="{{ asset('bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/animate.css') }}" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('css/colors/default.css') }}" id="theme" rel="stylesheet">
        <style>
            .error{
                color: red;
            }
            .login-register {
                background: url("{{ asset('plugins/images/bookodishapg-banner-bg.jpg') }}") center center/cover no-repeat!important;
            }
            .login-box {              
                margin-top: 10%;
            }
        </style>
    </head>

    <body class="mini-sidebar">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>
        <section id="wrapper" class="login-register">
            <div class="login-box">
                <div class="white-box">
                    <form method="POST" action="{{ route('post-password-change') }}" id="changePasswrdForm" class="form-horizontal form-material" autocomplete="off">
                        @csrf
                        <h3 class="box-title m-b-20" style="text-align: center;">Change Password</h3>
                        @if(Session::has('success'))
                            <p class="flashMessage" style="color: #ff0000; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                        @if ($isValid)
                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="password_reset_token" value="{{ $token }}">
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <input type="password" name="password" class="form-control" required placeholder="Password">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <input type="password" name="password_confirmation" class="form-control" required placeholder="Confirm password">
                            </div>
                        </div>

                        <div class="form-group text-center m-t-20">
                            <div class="col-xs-12">
                                <button type="submit" class="btn btn-primary btn-md btn-block waves-effect text-center m-b-20" id="passwordChange">Submit</button>
                            </div>
                        </div>
                        @else
                            <p style="padding: 50px 10px; color: red; text-align: center; font-size: 20px;"><?php echo $message; ?></p>
                        @endif
                        <p class="f-w-600 text-right"><a href="{{url('login')}}">Back to Login.</a></p>
                    </form>
                </div>
            </div>
        </section>

        <script src="{{ asset('plugins/components/jquery/dist/jquery.min.js') }}"></script>
        <script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('js/sidebarmenu.js') }}"></script>
        <script src="{{ asset('js/jquery.slimscroll.js') }}"></script>
        <script src="{{ asset('js/waves.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>
        <script src="{{ asset('plugins/components/styleswitcher/jQuery.style.switcher.js') }}"></script>
        <script src="{{ asset('js/validate.js') }}"></script>
        <script>
            $(document).ready(function () {
                $(document).on('click', '#passwordChange', function () {
                    var x = validator.form();
                    return x;
                });
                $.validator.addMethod('validPassword', function (value) {
                    return /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/.test(value);
                }, 'Password contain at least one lowercase letter, one uppercase letter, one numeber, and one special character.');

                validator = $('#changePasswrdForm').validate({
                    rules: {
                        'password': {
                            minlength: 8,
                            maxlength: 15,
                            validPassword: true
                        },
                        'password_confirmation': {
                            minlength: 8,
                            maxlength: 15,
                            validPassword: true,
                            equalTo: '[name="password"]'
                        },
                    },
                    messages: {
                        'password': {
                            minlength: "Password can not less than 8 character",
                            maxlength: "Password can not greater than 15 character"
                        },
                        'password_confirmation': {
                            minlength: "Confirm password can not less than 8 character",
                            maxlength: "Confirm password can not greater than 15 character",
                            equalTo: "Password and confirm password must be same"
                        }
                    }
                });
            });
                
        </script>
        
    </body>
</html>