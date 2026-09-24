<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title')</title>
        <meta name="keywords" content="">
        <meta name="description" content="">
        <meta name="author" content="">
        <!--<meta http-equiv="refresh" content="900;url=logout" />-->
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('plugins/images/favicon.png') }}">
        <link href="{{ asset('bootstrap/dist/css/bootstrap.min.css') }}" rel="stylesheet">
        <link href="{{ asset('plugins/components/datatables/jquery.dataTables.min.css') }}" rel="stylesheet" type="text/css" />
        <!--<link href="//cdn.datatables.net/buttons/1.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />-->
        <link href="{{ asset('plugins/components/datatables/buttons.dataTables.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('plugins/components/custom-select/custom-select.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('plugins/components/bootstrap-select/bootstrap-select.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('plugins/components/chartist-js/dist/chartist.min.css') }}" rel="stylesheet">
        <link href="{{ asset('plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css') }}" rel="stylesheet">
        <link href="{{ asset('css/animate.css') }}" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('css/colors/default.css') }}" id="theme" rel="stylesheet">
        <link href="{{ asset('plugins/components/icheck/skins/all.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('plugins/components/dropify/dist/css/dropify.min.css') }}">
        <link rel="stylesheet" href="{{ asset('plugins/components/dropzone/src/image-uploader.min.css') }}">
        <link href="{{ asset('plugins/components/clockpicker/dist/jquery-clockpicker.min.css') }}" rel="stylesheet">
        <link href="{{ asset('plugins/components/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('plugins/components/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">
        <link href="{{ asset('plugins/components/doom-edit/doomEdit.css') }}" rel="stylesheet">
        <!--<link href="{{ asset('plugins/components/full-calender/css/fullcalendar.css') }}" rel="stylesheet" />-->
        <link href="{{ asset('plugins/components/bootstrap-tagsinput/dist/bootstrap-tagsinput.css') }}" rel="stylesheet" />
        <script src="{{ asset('plugins/components/jquery/dist/jquery.min.js') }}"></script>      
        <style>
            .panel-heading {
                font-size: 20px;
                padding: 15px 25px;
            }
            .required_field {
                color: red;
            }
            .error {
                color: red;
            }
            .dataTables_filter input {
                border: 1px solid rgba(207, 219, 193, 0.44);
                padding: 5px;
            }
            #loading-image {
                left: 635px;
                opacity: 1;
                position: fixed;
                top: 230px;
                z-index: 1000;
            }
            th {
                text-align: center;
            }
            .table-responsive ul.dropdown-menu {
                right: 0 !important;
                left: inherit;
            }
        </style>
    </head>

    <body class="mini-sidebar">
        <div id="wrapper">
            <div class="preloader">
                <div class="cssload-speeding-wheel"></div>
            </div>
            
            <img src="{{ asset('plugins/images/ajax-loader.gif') }}" id="loading-image" style="display: none;width: 80px;left: 50%;z-index: 9999;">

            @include('partials.header')

            @include('partials.sidebar')

            <div class="page-wrapper">
                @yield('content')

                @include('partials.footer')
            </div>
        </div>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC6S9jmKEyQqEVJS6MQI6zx8g9NYAyAHOw&callback=initAutocomplete&libraries=places" defer></script>
        <script src="{{ asset('js/validate.js') }}"></script>
        <script src="{{ asset('bootstrap/dist/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('js/jquery.slimscroll.js') }}"></script>
        <script src="{{ asset('js/waves.js') }}"></script>
        <script src="{{ asset('plugins/components/owl.carousel/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('plugins/components/owl.carousel/owl.custom.js') }}"></script>
        <script src="{{ asset('js/sidebarmenu.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>
        
        <!--- Data tables --->
        <script src="{{ asset('plugins/components/datatables/jquery.dataTables.min.js') }}"></script>
        
        <script src="{{ asset('plugins/components/datatables/dataTables.buttons.min.js') }}"></script>
        <script src="{{ asset('plugins/components/datatables/buttons.flash.min.js') }}"></script>
        <script src="{{ asset('plugins/components/datatables/jszip.min.js') }}"></script>
        <script src="{{ asset('plugins/components/datatables/pdfmake.min.js') }}"></script>
        <script src="{{ asset('plugins/components/datatables/vfs_fonts.js') }}"></script>
        <script src="{{ asset('plugins/components/datatables/buttons.html5.min.js') }}"></script>
        <script src="{{ asset('plugins/components/datatables/buttons.print.min.js') }}"></script>
        <!--<script src="//cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>-->        
        <!--<script src="//cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>-->
        <!--<script src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>-->
        <!--<script src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>-->
        <!--<script src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>-->
        <!--<script src="//cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>-->
        <!--<script src="//cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>-->
    
        <script src="{{ asset('plugins/components/custom-select/custom-select.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('plugins/components/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('plugins/components/chartist-js/dist/chartist.min.js') }}"></script>
        <script src="{{ asset('plugins/components/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js') }}"></script>
        <script src="{{ asset('plugins/components/sparkline/jquery.sparkline.min.js') }}"></script>
        <script src="{{ asset('plugins/components/sparkline/jquery.charts-sparkline.js') }}"></script>
        <script src="{{ asset('plugins/components/knob/jquery.knob.js') }}"></script>
        <script src="{{ asset('plugins/components/easypiechart/dist/jquery.easypiechart.min.js') }}"></script>
        <script src="{{ asset('plugins/components/icheck/icheck.min.js') }}"></script>
        <script src="{{ asset('plugins/components/icheck/icheck.init.js') }}"></script>
        <script src="{{ asset('js/db1.js') }}"></script>
        <script src="{{ asset('plugins/components/dropify/dist/js/dropify.min.js') }}"></script>
        <script src="{{ asset('plugins/components/dropzone/src/image-uploader.min.js') }}"></script>
        <script src="{{ asset('plugins/components/clockpicker/dist/jquery-clockpicker.min.js') }}"></script>
        <script src="{{ asset('plugins/components/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
        <!--<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>-->
        <script type="text/javascript" src="{{ asset('plugins/components/moment/moment.min.js') }}"></script>
        <script src="{{ asset('plugins/components/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
        <script src="{{ asset('plugins/components/doom-edit/jquery.doomEdit.js') }}"></script>
        <!--<script src="{{ asset('plugins/components/full-calender/js/fullcalendar.js') }}"></script>-->
        <script src="{{ asset('plugins/components/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js') }}"></script>
        <script src="{{ asset('plugins/components/styleswitcher/jQuery.style.switcher.js') }}"></script>
        <script>
            jQuery(document).ready(function() {
                
                
                // For select 2
                $(".select2").select2();
                $('.selectpicker').selectpicker();
                
                if ($('.flashMessage').text()) {
                    $('.flashMessage').hide(10000);
                }
                if ($('.flashMessag').text()) {
                    $('.flashMessag').hide(15000);
                }
                $(document).ajaxStart(function () {
                    $('#loading-image').css("display", "block");
                });
                $(document).ajaxComplete(function () {
                    $('#loading-image').css("display", "none");
                });
                
                $(document).ajaxStart(function () {
                    $('#loading-image').css("display", "block");
                });
                $(document).ajaxComplete(function () {
                    $('#loading-image').css("display", "none");
                });
                
                $(document).on('keypress', ".numvalidate", function (e) {
                    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                        return false;
                    }
                });
                
                $(document).on('keypress', ".decimalvalidate", function (event) {
                    var decimal = /^\s*-?[1-9]\d*(\.\d{1,2})?\s*$/;
                    return isNumber(event, this)
                });
                
                function isNumber(evt, element) {
                    var charCode = (evt.which) ? evt.which : event.keyCode
                    if ((charCode != 45 || $(element).val().indexOf('-') == -1) && // “-” CHECK MINUS, AND ONLY ONE.
                        (charCode != 46 || $(element).val().indexOf('.') != -1) &&
                        (charCode != 8) && // “.” CHECK DOT, AND ONLY ONE.
                        (charCode < 48 || charCode > 57)) {
                        return false;
                    } else {
                        return true;
                    }
                }
            });
        </script>
    </body>
</html>
