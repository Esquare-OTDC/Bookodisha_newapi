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
        <title>Login :: OTDC</title>
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
            
            #loading-image {
                left: 635px;
                opacity: 1;
                position: fixed;
                top: 230px;
                z-index: 1000;
            }
            #wrapper {
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .login-box {
                margin: 0;
                background: unset;
                margin-top: 10%;
            }
            .white-box{
                margin-bottom: 0;
            }
        </style>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    </head>

    <body class="mini-sidebar">
        <div class="preloader">
            <div class="cssload-speeding-wheel"></div>
        </div>
        <img src="{{ asset('plugins/images/ajax-loader.gif') }}" id="loading-image" style="display: none;width: 80px;left: 50%;z-index: 9999;">
        <section id="wrapper" class="login-register">
            <div class="login-box">
                <div class="white-box">
                    <form action="{{ route('post-login') }}" method="POST" class="form-horizontal form-material" id="loginform" autocomplete="off">
                        @csrf
                        <h3 class="box-title m-b-20" style="text-align: center;">Sign In</h3>
                        @if(Session::has('success'))
                            <p class="flashMessage" style="color: #ff0000; text-align: center;">
                                {{ Session::get('success') }}
                                @php
                                    Session::forget('success');
                                @endphp
                            </p>
                        @endif
                        @if ($errors->has('email'))
                            <p style="text-align: center;" class="error">{{ $errors->first('email') }}<p>
                        @endif
                        
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <input type="email" id="email" name="email" class="form-control" required autocomplete="off" maxlength="50" placeholder="Email" autofocus>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <input type="password" name="password" class="form-control" required placeholder="Password" autocomplete="off" maxlength="15">
                            </div>
                        </div>
                        <div class="g-recaptcha" style="margin-left:6%;" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                        <div class="form-group m-t-10">
                            <div class="col-md-12">
                                <div class="checkbox checkbox-primary pull-left p-t-0">
                                    <input id="checkbox-signup" type="checkbox">
                                    <label for="checkbox-signup"> &nbsp; Remember me </label>
                                </div>
                                <a href="javascript:void(0);" id="to-recover" class="text-dark pull-right"><i class="fa fa-lock m-r-5"></i> Forgot password?</a> 
                            </div>
                        </div>

                        <div class="form-group text-center m-t-20">
                            <div class="col-xs-12">
                                <button type="submit" class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Log In</button>
                            </div>
                        </div>
                    </form>
                    <form class="form-horizontal" id="recoverform">
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <h3>Recover Password</h3>
                                <p class="text-muted">Enter your Email and instructions will be sent to you! </p>
                            </div>
                        </div>
                        <div class="form-group ">
                            <div class="col-xs-12">
                                <input class="form-control" type="email" required id="email1" placeholder="Email" autocomplete="off" maxlength="50" autofocus>
                            </div>
                        </div>
                        <!-- <div class="g-recaptcha" style="margin-left:6%;" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div> -->
                        <div class="form-group text-center m-t-20">
                            <div class="col-xs-12">
                                <button class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light" id="retrieve" type="button">Reset</button>
                                <button class="btn btn-secondary btn-lg btn-block text-uppercase waves-effect waves-light" id="backLogin" type="button">Back to Login</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        <script src="{{ asset('plugins/components/jquery/dist/jquery.min.js') }}"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('js/sidebarmenu.js') }}"></script>
        <script src="{{ asset('js/jquery.slimscroll.js') }}"></script>
        <script src="{{ asset('js/waves.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>
        <script src="{{ asset('plugins/components/styleswitcher/jQuery.style.switcher.js') }}"></script>
        <script>
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function () {
                window.history.go(1);
            };
            $(document).ready(function () {
                
                
                $(document).ajaxStart(function () {
                    $('#loading-image').css("display", "block");
                });
                $(document).ajaxComplete(function () {
                    $('#loading-image').css("display", "none");
                });
                $('#retrieve').on('click', function () {
                    var captchaResponse = grecaptcha.getResponse();
                    if (captchaResponse == '') {
                        alert('Plesse verify you are not a robot.');
                        return;
                    }
                    var emailId = $("#email1").val();
                    if (emailId != '' && captchaResponse != '') {
                        $.ajax({
                            type: "POST",
                            url: "{{url('forgot-password')}}",
                            headers: {
                                'X-CSRF-Token': '{{ csrf_token() }}',
                            },
                            data: {emailId: emailId, captchaResponse: captchaResponse, request_type: "retrieve_password"},
                            success: function (data) {
                                var responce = $.parseJSON(data);
                                alert(responce.message);
                                location.reload();
                            }
                        });
                    } else {
                        alert("Please, Enter your email !");
                    }
                });
                
                $(document).on('click', '#to-recover', function () {
                    $("#email1").focus();
                });
                
                $(document).on('click', '#backLogin', function () {
                    $("#email").val('');
                    $("#email").focus();
                    $(".error").html('');
                    $('#loginform').trigger("reset");
                    $("#recoverform").hide('30');
                    $("#loginform").show('30');
                });
            });
                
        </script>
        
    </body>
</html>