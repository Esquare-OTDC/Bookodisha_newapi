@extends('layouts.app')

@section('title', 'Create Hotel Estimate')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Orders</li>
                <li class="breadcrumb-item active">Create Hotel Estimate</li>
            </ol>
        </div>
    </div>
    
    @if(Session::has('success'))
        <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
            {{ Session::get('success') }}
            @php
                Session::forget('success');
            @endphp
        </p>
    @elseif(Session::has('failure'))
        <p class="flashMessag" style="color: #ff0000; text-align: center;">
            {{ Session::get('failure') }}
            @php
                Session::forget('failure');
            @endphp
        </p>
    @endif
    
    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Create Hotel Estimate</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('create-hotel-estimate-request') }}" id="offlineOrderForm" method="POST">
                    @csrf                    
                    <input type="hidden" name="request_type" id="request_type" value="room">
                    <input type="hidden" name="room_details" id="room_details">
                    <input type="hidden" name="cart_details" id="cart_details">
                    <input type="hidden" name="pricing_details" id="pricing_details">                    
                    <input type="hidden" name="total_rooms" id="total_rooms">
                    <input type="hidden" name="total_adult" id="total_adult">
                    <input type="hidden" name="total_child" id="total_child">
                    <input type="hidden" name="total_room_price" id="total_room_price">
                    <input type="hidden" name="sub_total_price" id="sub_total_price">                    
                    <input type="hidden" class="coupon" name="coupon_name" id="coupon_name">
                    <input type="hidden" class="coupon" name="coupon_code" id="coupon_code">
                    <input type="hidden" class="coupon" name="coupon_percent" id="coupon_percent" value="0">
                    <input type="hidden" class="coupon" name="coupon_amount" id="coupon_amount" value="0">
                    <input type="hidden" name="cgst" id="cgst">
                    <input type="hidden" name="sgst" id="sgst">
                    <input type="hidden" name="tax_amount" id="tax_amount">
                    <input type="hidden" name="tax_percentage" id="tax_percentage">
                    <input type="hidden" name="service_charge" id="service_charge">
                    <input type="hidden" name="total_order_price" id="total_order_price">
                    <div class="col-md-12">
                        <div class="white-box hotel-section">
                            <h3 class="box-title" style="display:inline-block;">Hotel Details</h3>
                            <a class="btn btn-primary pull-right" href="{{url('create-extrabed-bill')}}">Extra Matress</a>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Hotel</label>
                                        <select class="form-control check-room" id="hotelId" name="service_name_id" required value="{{ old('hotel_id') }}">
                                            <option value="">Select Hotel</option>
                                            @foreach ($MasterHotel as $key => $value)
                                            <option value="{{ $value .'~'. $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('service_name_id'))
                                        <span class="text-danger">{{ $errors->first('service_name_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Check-In / Check-Out</label>
                                        <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date">
                                        @if ($errors->has('check_date'))
                                        <span class="text-danger">{{ $errors->first('check_date') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="col-md-8"><h3 class="box-title">Room Details</h3></div><div class="col-md-4"><button type="button" class="btn btn-info pull-right" id="check-availability">Check Availability</button></div><hr>                                    
                                    <div class="col-md-9">
                                        <table class="table table-bordered room-section hideRoom w-100" style="display: none;">
                                            <thead>
                                                <tr>
                                                    <th>Room Type</th>
                                                    <th>Price</th>
                                                    <th>No. of Rooms</th>
                                                </tr>
                                            </thead>
                                            <tbody id="roomData">
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-3 selection-area-div">
                                        <div class="row selection-area hideRoom"></div>
                                    </div>
                                    <div class="col-md-8 hideRoom"></div>
                                    <div class="col-md-4 room-price-section hideRoom"></div>
                                    <div class="col-md-12 hideRoom" style="display:none">
                                        <button type="button" class="btn btn-primary" id="confirmRoom">Confirm Room</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="white-box user-section" style="display:none;">
                            <h3 class="box-title">User Details</h3><hr>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Name</label><span class="required_field">*</span>
                                        <input type="text" class="form-control" name="customer_name" required>
                                        @if ($errors->has('customer_name'))
                                        <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                                        @endif
                                    </div>                               
                                    <div class="form-group ">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="customer_email">
                                        @if ($errors->has('customer_email'))
                                        <span class="text-danger">{{ $errors->first('customer_email') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Phone</label>
                                        <input type="text" class="form-control numvalidate" name="customer_phone">
                                        @if ($errors->has('customer_phone'))
                                        <span class="text-danger">{{ $errors->first('customer_phone') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label>Address</label>
                                        <input type="text" class="form-control reqfields" name="customer_address1">
                                        @if ($errors->has('customer_address1'))
                                        <span class="text-danger">{{ $errors->first('customer_address1') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Country</label>
                                        <select class="form-control" name="customer_country" id="customer_country">
                                            <option value="">Select Country</option>
                                            @foreach ($CountryData as $key => $value)
                                            <option value="{{ $value .'~'. $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('customer_country'))
                                        <span class="text-danger">{{ $errors->first('customer_country') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>State</label>
                                        <select class="form-control" name="customer_state" id="customer_state">
                                            <option value="">Select State</option>
                                        </select>
                                        @if ($errors->has('customer_state'))
                                        <span class="text-danger">{{ $errors->first('customer_state') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>City</label>
                                        <select class="form-control" name="customer_city" id="customer_city">
                                            <option value="">Select City</option>
                                        </select>
                                        @if ($errors->has('customer_city'))
                                        <span class="text-danger">{{ $errors->first('customer_city') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Zip Code</label>
                                        <input type="text" class="form-control" name="customer_zipcode">
                                        @if ($errors->has('customer_zipcode'))
                                        <span class="text-danger">{{ $errors->first('customer_zipcode') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>GST Registration Number</label>
                                        <input type="text" class="form-control" name="gst_regd_no">
                                        @if ($errors->has('gst_regd_no'))
                                        <span class="text-danger">{{ $errors->first('gst_regd_no') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Registered Company Name</label>
                                        <input type="text" class="form-control" name="gst_company_name">
                                        @if ($errors->has('gst_company_name'))
                                        <span class="text-danger">{{ $errors->first('gst_company_name') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Registered Company Address</label>
                                        <input type="text" class="form-control" name="gst_company_address">
                                        @if ($errors->has('gst_company_address'))
                                        <span class="text-danger">{{ $errors->first('gst_company_address') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Account Name</label>
                                        <input type="text" class="form-control" name="account_name" value="{{ $account_name }}">
                                        @if ($errors->has('account_name'))
                                        <span class="text-danger">{{ $errors->first('account_name') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Account Number</label>
                                        <input type="text" class="form-control" name="account_no" value="{{ $account_no }}">
                                        @if ($errors->has('account_no'))
                                        <span class="text-danger">{{ $errors->first('account_no') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>IFSC Code</label>
                                        <input type="text" class="form-control" name="ifsc_code" value="{{ $account_ifsc }}">
                                        @if ($errors->has('ifsc_code'))
                                        <span class="text-danger">{{ $errors->first('ifsc_code') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label>Type</label><span class="required_field">*</span>
                                        <select class="form-control" name="est_type" id="est_type" required>
                                            <option value="estimate">Estimate</option>
                                            <option value="bill">Bill</option>
                                        </select>
                                        @if ($errors->has('est_type'))
                                        <span class="text-danger">{{ $errors->first('est_type') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <div class="radio radio-info">
                                            <input type="radio" name="account_details" id="radio1" value="1">
                                            <label for="radio1">Add Account Details</label>
                                        </div>
                                        <div class="radio radio-info">
                                            <input type="radio" name="account_details" id="radio2" value="0" checked>
                                            <label for="radio2">Don't Add Account Details</label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="radio radio-info">
                                            <input type="radio" name="send_mail" id="radio3" value="1" checked>
                                            <label for="radio1">Send Email</label>
                                        </div>
                                        <div class="radio radio-info">
                                            <input type="radio" name="send_mail" id="radio4" value="0">
                                            <label for="radio2">Don't Send Email</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-center" style="font-weight:bold;"><u>Booking Details</u></h5>
                                    <div class="detail-section"></div>
                                    <h5 class="text-center" style="font-weight:bold;"><u>PAYMENT BREAKUP</u></h5>
                                    <div class="price-section"></div>
                                    <div class="form-group m-t-10 form-inline">
                                        <label style="font-size: large;font-weight: 600;">Apply Discount (in %)</label><br>
                                        <input type="text" class="form-control numvalidate" id="coupon_code" placeholder="e.g. 10%">
                                        <button type="button" class="btn btn-info" id="verify_coupon">Apply</button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary" id="bookNow">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>                
        </div>
    </div>
</div>

<style>
    .icheck-list li label {
        display: inline;
        color: black;
    }
    .icheck-list {
        padding-right: 0px;
    }
    .icheck-list li {
        padding-bottom: 8px;
    }
    .image-uploader {
        min-height: 20rem;
    }
    .room-price-section {
        text-align: right;
    }
    .price-section {
        line-height: 30px;
        font-size: initial;
    }
    .
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {

        var hotelroomarray = [];
        var guestDetails = {};
        var addtoCartRoom = {};
        var addtoCartTotal = 0;
        var customeTotalPrice = 0;
        var pricingData = {};
        var gstApplicable = '';
        var cGst = 0;
        var sGst = 0;
        var totalPrice = 0;
        var couponStatus = 0;
        var totalNight = 0;
        var serviceFee = 0;
        var coupon_amount = 0;
        var subTotalPrice = 0;
        var coupon_code = '';
        var responseData = {};
        var adminUser = "{{ $adminUser }}";
        if (adminUser == 1) {
            $('.input-daterange-datepicker').daterangepicker({
                autoApply: true,
                startDate: moment(),
                endDate: moment().add('+1', 'days'),
                /* maxDate: moment().add('+120','days'), */
                locale: {
                format: 'DD MMM YYYY'
                }
            });
        } else {
            $('.input-daterange-datepicker').daterangepicker({
                autoApply: true,
                startDate: moment(),
                endDate: moment().add('+1', 'days'),
                minDate: moment(),
                /* maxDate: moment().add('+120','days'), */
                locale: {
                format: 'DD MMM YYYY'
                }
            });
        }
        
        
        $('#service_city').on('change',function() {
            let city = $(this).val();
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {City: city, request_type: "get_hotels_by_city"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            $("#hotelId").html(responce.data);
                        }
                }
            });
        });
        
        $('#customer_country').on('change',function() {
            let countryData = $(this).val();
            let countryArray = countryData.split("~");
            countryId = countryArray[1];
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {countryId: countryId, request_type: "get_states_country"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            $("#customer_state").html(responce.data);
                        }
                }
            });
        });
        
        $('#customer_state').on('change',function() {
            let stateData = $(this).val();
            let stateArray = stateData.split("~");
            stateId = stateArray[1];
            $.ajax({
                type: "POST",
                url: "{{url('hotel-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {stateId: stateId, request_type: "get_city_state"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            $("#customer_city").html(responce.data);
                        }
                }
            });
        });
        
        $('.check-room').on('change',function() {
            $(".hideRoom").hide();
        });
        
        $('#confirmRoom').on('click',function() {
            if (customeTotalPrice > 0) {
                let hotel = $("#hotelId").val();
                let strArray = hotel.split("~");
                let hotelId = strArray[1];
                let room_details = $("#room_details").val();
                let pricingData = $("#pricing_details").val();
                let checkDate = $("#check_date").val();
                let dateArray = checkDate.split(" - ");
                checkIn = dateArray[0];
                checkOut = dateArray[1];
                let bookFrom = $('#book_from option:selected').text();
                /* if (confirm("Ae you sure want to book from "+ bookFrom)) { */
                    $.ajax({
                        type: "POST",
                        url: "{{url('hotel-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {hotelId: hotelId, room_details: room_details, checkIn: checkIn, checkOut: checkOut,pricingData: pricingData, request_type: "calculate_all_gst"},
                        success: function (data) {
                            var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                            } else {
                                responseData = responce;
                                let hotelname = $("#hotelId option:selected").text();
                                let selectedRoomHtml = '<div class="col-md-12"><ol>';
                                let roomData = JSON.parse(room_details);
                                let j = 0;
                                for(let i = 0; i < roomData.length; i++) {
                                    /* console.log(roomData[i]); */
                                    let chld = (roomData[i].child > 0) ? roomData[i].child +' Child/s' : '';
                                    selectedRoomHtml += '<li><strong>'+ roomData[i].quantity +' '+ roomData[i].name +' ('+ roomData[i].adult +' Adult/s &nbsp;&nbsp;'+ chld +')</strong></li>';
                                }
                                selectedRoomHtml += '</ol></div>';
                                let detailHtml = '<div class="row"><div class="col-md-12"><strong><u>Hotel Name</u></strong></div><div class="col-md-12"><strong>'+ hotelname +'</strong></div><div class="col-md-12"><strong><u>Check in - Check out</u></strong></div><div class="col-md-12"><strong>'+ checkDate +'</strong></div><div class="col-md-12"><strong><u>Selected Rooms</u></strong></div><div class="col-md-12">'+ selectedRoomHtml +'</div></div>';
                                $(".detail-section").html(detailHtml);
                                cancelCoupon();
                                /* let gstHtml = '';
                                let gstRes = responce.gst_data;
                                let taxAmount = 0;let taxPercent = 0;
                                for(let i = 0; i < gstRes.length; i++) {
                                    taxAmount += Number(gstRes[i].gst_value);
                                    taxPercent += Number(gstRes[i].gst_percentage);
                                    gstHtml += '<div class="col-md-8"><strong>'+ gstRes[i].gst_name +' @ '+ gstRes[i].gst_percentage +'% :</strong></div><div class="col-md-4">&#8377;'+ gstRes[i].gst_value +'</div>';
                                }                            
                                let hotelSellPrice = (responce.grossPrice - responce.extra_price).toFixed(2);
                                let priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room :</strong></div><div class="col-md-4">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Hotel Sell Price :</strong></div><div class="col-md-4">&#8377;'+ hotelSellPrice +'</div>'
                                +'<div class="col-md-8"><strong>Extra Adult / Child Charges :</strong></div><div class="col-md-4">&#8377;'+ (responce.extra_price).toFixed(2) +'</div>'
                                +'<div class="col-md-8"><strong>Gross Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.grossPrice).toFixed(2) +'</div><div class="col-md-8"><strong>Sub Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.subTotalPrice).toFixed(2) +'</div>'
                                + gstHtml +'<div class="col-md-8"><strong>Service Charge :</strong></div><div class="col-md-4">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.totalOrderPrice).toFixed(2) +'</div></div>';
                                $(".price-section").html(priceHtml);
                                $("#total_order_price").val((responce.totalOrderPrice).toFixed(2));
                                $("#sub_total_price").val((responce.grossPrice).toFixed(2));
                                $("#total_room_price").val((responce.grossPrice).toFixed(2));
                                $("#tax_amount").val(taxAmount.toFixed(2));
                                $("#tax_percentage").val(taxPercent.toFixed(2)); */
                            }
                        }
                    });
                    $(".hotel-section").hide(2000);
                    $(".user-section").show(2000);
                /* } */
            } else {
                alert("Please select atleast one room!");
            }
        });
        
        $('#book_from').on('change',function() {
            let bookFrom = $('#book_from').val();
            if (bookFrom == 'live') {
                let url = "<?= route('create-offline-order') ?>";
                $("#offlineOrderForm").attr('action', url);
            } else {
                let url = "<?= route('create-blocked-hotel-order') ?>";
                $("#offlineOrderForm").attr('action', url);
            }
        });
        
        $('#check-availability').on('click',function() {
            
            guestDetails = {};
            addtoCartRoom = {};
            addtoCartTotal = 0;
            customeTotalPrice = 0;
            totalPrice = 0;
            subTotalPrice = 0;
            addCartData();
            
            let hotelId = checkIn = checkOut = '';
            let hotel = $("#hotelId").val();
            if (hotel != '') {
                let strArray = hotel.split("~");
                hotelId = strArray[1];
            }
            let checkDate = $("#check_date").val();
            if (checkDate != '') {
                let dateArray = checkDate.split(" - ");
                checkIn = dateArray[0];
                checkOut = dateArray[1];
            }
            let bookFrom = $("#book_from").val();
            if (hotelId != '' && checkIn != '' && checkOut != '') {                
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {hotelId: hotelId, checkinDate: checkIn, checkoutDate: checkOut, request_type: "check_all_room_availability"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            $("#roomData").html('');
                            alert(responce.message);                                
                        } else {
                            $(".hideRoom").show();
                            /* console.log(responce.data); */
                            let html = '';
                            let rooms = responce.data;
                            hotelroomarray = rooms;
                            gstApplicable = responce.gstApplicable;
                            cGst = responce.gst_data['CGST'];
                            sGst = responce.gst_data['SGST'];
                            totalNight = rooms[0]['days'];
                            serviceFee = responce.service_chagre;
                            $("#tax_percentage").val(Number(cGst) + Number(sGst));
                            $("#service_charge").val(Number(serviceFee));
                            
                            for(let i = 0; i < rooms.length; i++) {
                                pricingData[rooms[i]['id']] = rooms[i]['pricing_details'];
                                html += '<tr><td>'+ rooms[i].title +'</td><td>&#8377;'+ rooms[i].price +'/'+ rooms[i].days +' night/s<br>';
                                let options = '<option value="'+ i +'">Select</option>';
                                let guestData = rooms[i].guestdata;
                                for (let j = 0; j < guestData.length; j++) {
                                    options += '<option value="'+ i +'_'+ j +'">'+ (guestData[j].adult) +' Adults '+ ((guestData[j].child > 0) ? (guestData[j].child +' Child ') : '') +'&#8377;'+ (guestData[j].price) +'</option>';
                                }
                                html += '<select class="form-control roomOption">'+ options +'</select><br><button type="button" class="btn btn-sm btn-block btn-primary addToCart" id="addToCart'+ i +'"  data-id="'+ i +'">Add to Room List</button></td><td><input type="number" min="1" class="form-control rQty" value="1" id="rQty'+ i +'"></td></tr>';
                            }
                            
                            $("#roomData").html(html);
                            let roomHeight = $("#roomData").height();
                            $(".selection-area-div").css('max-height', roomHeight + 40);
                            $(".selection-area-div").css('overflow-y', 'auto');
                            $("#pricing_details").val(JSON.stringify(pricingData));
                        }
                    }
                });
            }
        });
        
        $(document).on("change click", ".roomOption", function() {
            let index = $(this).val();
            let roomno = index.split('_')[0];
            let guestno = index.split('_')[1]; 
            if (guestno == undefined && guestDetails[roomno]) {
                delete guestDetails[roomno];
            } else {
                guestDetails[roomno] = hotelroomarray[roomno]['guestdata'][guestno];
            }
            
        });
        
        $(document).on('click', '.addToCart', function () {
            let index = $(this).data('id');
            let rQty = Number($("#rQty"+ index).val());
            if (rQty == "" || rQty == 0) {
                alert('Number of rooms should re greater than 0.');
                return;
            }
            if(Object.keys(guestDetails).length > 0 && guestDetails[index] != undefined){
                /* hotelroomarray[index].quantity = hotelroomarray[index].quantity-1; */
                var guestData = [];
                if(addtoCartRoom[index] != undefined){
                  guestData = addtoCartRoom[index].guestData;
                }
                guestDetails[index].qty = rQty;
                guestData.push(guestDetails[index]);
                
                customeTotalPrice += (guestDetails[index].price * guestDetails[index].qty);
                addtoCartRoom[index] = {roomName:hotelroomarray[index].title,roomId:hotelroomarray[index].id,guestData:guestData,days:hotelroomarray[index].days};
                addtoCartTotal += guestDetails[index].qty;
                if (hotelroomarray[index].quantity < 1) {
                    $(this).attr('disabled', true);
                }
                /* console.log(addtoCartRoom, addtoCartTotal, 'check'); */
            }
            addCartData();
            /* cancelCoupon('cart');
            console.log(addtoCartRoom, customeTotalPrice); */
            
        });
                
        $(document).on('click', '.deleteCart', function () {
            let index = $(this).data('id');
            let roomno = index.split('_')[0];
            let guestno = index.split('_')[1];
            /* console.log(addtoCartRoom, roomno, guestno, 'delete');return; */
            /* hotelroomarray[roomno].quantity = hotelroomarray[roomno].quantity+1; */
            customeTotalPrice -= (addtoCartRoom[roomno].guestData[guestno].price * addtoCartRoom[roomno].guestData[guestno].qty);
            addtoCartTotal -= addtoCartRoom[roomno].guestData[guestno].qty;
            if(addtoCartRoom[roomno].guestData.length > 1){
                addtoCartRoom[roomno].guestData.splice(guestno, 1)
                addtoCartRoom[roomno].guestData = addtoCartRoom[roomno].guestData;
            } else {
              delete addtoCartRoom[roomno];
            }
            /* if (hotelroomarray[roomno].quantity > 0) {
                $("#addToCart"+ roomno).removeAttr('disabled');
            } */
            addCartData();
            /* cancelCoupon('cart'); */
        });
                
        $(document).on('click', '#bookNow', function () {
            let totalPrice = $("#total_room_price").val();
            if(totalPrice < 1) {
                alert('Please select a room and book again!');
                return false;
            }
        });
        
        $(document).on('keyup','#coupon_code', function(){
           coupon_code = $(this).val();
        });
        
        $(document).on('click', '#verify_coupon', function () {
            let order_value = $("#sub_total_price").val();
            let hotel = $("#hotelId").val();
            let strArray = hotel.split("~");
            let hotelId = strArray[1];
            let room_details = $("#room_details").val();
            let pricingData = $("#pricing_details").val();
            let checkDate = $("#check_date").val();
            let dateArray = checkDate.split(" - ");
            checkIn = dateArray[0];
            checkOut = dateArray[1];
            if (coupon_code != '' && couponStatus == 0) {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {coupon_code: coupon_code, order_value: order_value, hotelId: hotelId, room_details: room_details, pricingData: pricingData, checkIn: checkIn, checkOut: checkOut, request_type: "verify_coupon_all"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                                couponStatus = 0;
                                $(".coupon").val('');
                                alert(responce.message);
                            } else {
                                couponStatus = 1;
                                $("#coupon_name").val(responce.coupon_data.coupon_name);
                                $("#coupon_code").val(responce.coupon_data.coupon_code);
                                $("#coupon_percent").val(responce.coupon_data.coupon_value);
                                $("#coupon_amount").val(responce.discount_amount);
                                
                                let gstHtml = '';
                                let gstRes = responce.gst_data;
                                let taxAmount = 0;let taxPercent = 0;
                                for(let i = 0; i < gstRes.length; i++) {
                                    taxAmount += Number(gstRes[i].gst_value);
                                    taxPercent += Number(gstRes[i].gst_percentage);
                                    gstHtml += '<div class="col-md-8"><strong>'+ gstRes[i].gst_name +' @ '+ gstRes[i].gst_percentage +'% :</strong></div><div class="col-md-4 text-right">&#8377;'+ gstRes[i].gst_value +'</div>';
                                }                            
                                let hotelSellPrice = (responce.grossPrice - responce.extra_price).toFixed(2);
                                let priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room :</strong></div><div class="col-md-4 text-right">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Hotel Sell Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ hotelSellPrice +'</div>'
                                +'<div class="col-md-8"><strong>Extra Adult / Child Charges :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.extra_price).toFixed(2) +'</div><div class="col-md-8"><strong>Gross Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.grossPrice).toFixed(2) +'</div>'
                                +'<div class="col-md-12"><button type="button" class="btn m-t-10 m-b-10" style="border-radius: 0;background: #ffd400;border: 2px dashed #333;width: 100%;color: #333;">'+ responce.coupon_data.coupon_name +' '+ parseInt(responce.coupon_data.coupon_value) +'% off</button><i class="fa fa-times cancelcoupon" style="position:absolute;color:red;font-size:27px;margin-left:-15px;"></i></div>'
                                +'<div class="col-md-8"><strong>Discount:</strong></div><div class="col-md-4 text-right">- &#8377;'+ (responce.discount_amount).toFixed(2) +'</div><div class="col-md-8"><strong>Sub Total Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.subTotalPrice).toFixed(2) +'</div>'
                                + gstHtml +'<div class="col-md-8"><strong>Service Charge :</strong></div><div class="col-md-4 text-right">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.totalOrderPrice).toFixed(2) +'</div></div>';
                                $(".price-section").html(priceHtml);
                                $("#total_order_price").val((responce.totalOrderPrice).toFixed(2));
                                $("#sub_total_price").val((responce.subTotalPrice).toFixed(2));
                                $("#total_room_price").val((responce.grossPrice).toFixed(2));
                                $("#tax_amount").val(taxAmount.toFixed(2));
                                $("#tax_percentage").val(taxPercent.toFixed(2));
                            }
                    }
                });
            }
        });
        
        $(document).on('click', '.cancelcoupon', function () {
            cancelCoupon();
        });
        
        function addCartData() {
            $(".selection-area").html('');
            let cartHtml = '';
            
            for (const rooms in addtoCartRoom) {
                for (let i = 0; i < addtoCartRoom[rooms].guestData.length; i++) {
                    let gdata = addtoCartRoom[rooms].guestData;
                    let roomAdult = 0;
                    let roomChild = 0;
                    roomAdult = gdata[i].adult;
                    roomChild = gdata[i].child;
                    let roomQty = gdata[i].qty;
                    /* if (gdata[i].sts == 0) {
                        roomAdult = gdata[i].adult + gdata[i].extra;
                        roomChild = gdata[i].child;
                    } else {
                        roomAdult = gdata[i].adult;
                        roomChild = gdata[i].child + gdata[i].extra;
                    }
                    console.log(gdata[i], roomAdult, roomChild); */
                    cartHtml += '<div class="col-md-12"><div class="card" style="border: 1px solid;padding: 10px; margin-bottom: 5px;"><div><button type="button" class="close deleteCart" data-id="'+ rooms +'_'+ i +'">&times;</button></div><div class="card-body"><h4 class="card-title">'+ addtoCartRoom[rooms].roomName +'</h4><p class="card-text">'+ (roomAdult) + ' Adults '+ ((roomChild != 0) ? (roomChild + ' Child') : '') +'</p><p class="card-text">'+ roomQty +' room/s</p><p class="card-text"><strong>'+ gdata[i].price * roomQty +'</strong></p><p class="card-text">Per '+ addtoCartRoom[rooms].days +' Nights</p></div></div></div>';
                }
            }
            $(".selection-area").html(cartHtml);
            let roomPriceHtml = '<div class="row"><div class="col-md-6"><strong>Total Room:</strong></div><div class="col-md-6"><strong>'+ addtoCartTotal +'</strong></div><div class="col-md-6"><strong>Gross Price:</strong></div><div class="col-md-6"><strong>&#8377;'+ customeTotalPrice +'</strong></div></div>';
            if (customeTotalPrice > 0) {
                $(".room-price-section").html(roomPriceHtml);
            } else {
                $(".room-price-section").html('');
            }
            let adults = 0;
            let children = 0;
            let bookingRoomDetails = [];
            for (const key in addtoCartRoom) {
                var roomid = hotelroomarray[key].id;
                let pricing_details = {};
                pricing_details[roomid] = hotelroomarray[key].pricing_details;
                for (let gt = 0; gt < addtoCartRoom[key].guestData.length; gt++) {
                    let roomAdult = 0;
                    let roomChild = 0;
                    roomAdult = addtoCartRoom[key].guestData[gt].adult;
                    roomChild = addtoCartRoom[key].guestData[gt].child;
                    /* if (addtoCartRoom[key].guestData[gt].sts == 0) {
                        roomAdult = addtoCartRoom[key].guestData[gt].adult + addtoCartRoom[key].guestData[gt].extra;
                        roomChild = addtoCartRoom[key].guestData[gt].child ;
                    } else {
                        roomAdult = addtoCartRoom[key].guestData[gt].adult;
                        roomChild = addtoCartRoom[key].guestData[gt].child + addtoCartRoom[key].guestData[gt].extra;
                    } */
                    bookingRoomDetails.push({ id: hotelroomarray[key].id, quantity: addtoCartRoom[key].guestData[gt].qty, name : hotelroomarray[key].title, price : addtoCartRoom[key].guestData[gt].price, extra_bed:addtoCartRoom[key].guestData[gt].extra, extra_bed_price:hotelroomarray[key].extra_bed_price, adult:roomAdult,child:roomChild});
                    adults += (roomAdult * addtoCartRoom[key].guestData[gt].qty);
                    children += (roomChild * addtoCartRoom[key].guestData[gt].qty);
                }
            }
            
            $("#cart_details").val(JSON.stringify(addtoCartRoom));
            $("#room_details").val(JSON.stringify(bookingRoomDetails));
            
            $("#total_rooms").val(addtoCartTotal);
            $("#total_adult").val(adults);
            $("#total_child").val(children);
            /* calculateGST(bookingRoomDetails); */
        }
        
        function calculateGST(bookingRoomDetails) {
            let cGstPrice = 0;
            let sGstPrice = 0;
            let priceHtml = '';
            if(gstApplicable == 1) {
                for (let i = 0; i < bookingRoomDetails.length; i++) {
                    if ((bookingRoomDetails[i].price - (bookingRoomDetails[i].extra_bed * Number(bookingRoomDetails[i].extra_bed_price))) > 1000) {
                      cGstPrice += ((bookingRoomDetails[i].price - (bookingRoomDetails[i].extra_bed * Number(bookingRoomDetails[i].extra_bed_price) * Number(totalNight) )) * cGst) /100;
                      sGstPrice += ((bookingRoomDetails[i].price - (bookingRoomDetails[i].extra_bed * Number(bookingRoomDetails[i].extra_bed_price) * Number(totalNight) )) * sGst) /100;
                      cGstPrice = Number(cGstPrice.toFixed(2));
                      sGstPrice = Number(sGstPrice.toFixed(2));
                      totalPrice = Number(customeTotalPrice) + (cGstPrice + sGstPrice) + Number(serviceFee);
                    }
                }
                if(cGstPrice > 0 || sGstPrice > 0) {
                    
                    priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room</strong></div><div class="col-md-4 text-right">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Gross Price</strong></div><div class="col-md-4 text-right">&#8377;'+ customeTotalPrice +'</div>'
                        +'<div class="col-md-8"><strong>CGST @ '+ cGst +'%</strong></div><div class="col-md-4 text-right">&#8377;'+ cGstPrice +'</div><div class="col-md-8"><strong>SGST @ '+ sGst +'%</strong></div><div class="col-md-4 text-right">&#8377;'+ sGstPrice +'</div>'
                        +'<div class="col-md-8"><strong>Service Charge</strong></div><div class="col-md-4 text-right">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price</strong></div><div class="col-md-4 text-right">&#8377;'+ totalPrice +'</div></div>';
                } else {
                    totalPrice = Number(customeTotalPrice) + Number(serviceFee);
                    priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room</strong></div><div class="col-md-4 text-right">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Gross Price</strong></div><div class="col-md-4 text-right">&#8377;'+ customeTotalPrice +'</div>'
                    +'<div class="col-md-8"><strong>Service Charge</strong></div><div class="col-md-4 text-right">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price</strong></div><div class="col-md-4 text-right">&#8377;'+ totalPrice +'</div></div>';
                }
            }
            else {
                totalPrice = customeTotalPrice + Number(serviceFee);
                priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room</strong></div><div class="col-md-4 text-right">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Gross Price</strong></div><div class="col-md-4 text-right">&#8377;'+ customeTotalPrice +'</div>'
                    +'<div class="col-md-8"><strong>Service Charge</strong></div><div class="col-md-4 text-right">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price</strong></div><div class="col-md-4 text-right">&#8377;'+ totalPrice +'</div></div>';        
            }
            /* if (customeTotalPrice > 0) {
                $(".price-section").html(priceHtml);
            } else {
                $(".price-section").html('');
            } */
            $("#total_room_price").val(customeTotalPrice);
            $("#sub_total_price").val(customeTotalPrice);
            $("#tax_amount").val(Number(cGstPrice + sGstPrice));
            $("#total_order_price").val(totalPrice);
            $("#cgst").val(cGstPrice);
            $("#sgst").val(sGstPrice);
        }
        
        function calculateCoupon() {
            let couponPercent = $("#coupon_percent").val();
            if(couponPercent != 0) {
                let couponName = $("#coupon_name").val();
                let cGstPrice = $("#cgst").val();
                let sGstPrice = $("#sgst").val();
                coupon_amount = Number(customeTotalPrice) * (Number(couponPercent) / 100);
                coupon_amount = Number(coupon_amount.toFixed(2));
                subTotalPrice = Number(customeTotalPrice) - coupon_amount;
                totalPrice -= coupon_amount;
                totalPrice = Number(totalPrice.toFixed(2));
                $("#sub_total_price").val(subTotalPrice);
                $("#total_order_price").val(totalPrice);
                $("#coupon_amount").val(coupon_amount);
                if (cGstPrice > 0 || sGstPrice > 0) {
                    priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room:</strong></div><div class="col-md-4">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Gross Price:</strong></div><div class="col-md-4">&#8377;'+ customeTotalPrice +'</div>'
                        +'<div class="col-md-12"><button type="button" class="btn m-t-10 m-b-10" style="border-radius: 0;background: #ffd400;border: 2px dashed #333;width: 100%;color: #333;">'+ couponName +'</button><i class="fa fa-times cancelcoupon" style="position:absolute;color:red;font-size:27px;margin-left:-15px;"></i></div>'
                        +'<div class="col-md-8"><strong>Discount:</strong></div><div class="col-md-4">- &#8377;'+ coupon_amount +'</div><div class="col-md-8"><strong>Sub Total Price:</strong></div><div class="col-md-4">&#8377;'+ subTotalPrice +'</div>'
                        +'<div class="col-md-8"><strong>CGST @ '+ cGst +'%:</strong></div><div class="col-md-4 text-right">&#8377;'+ cGstPrice +'</div><div class="col-md-8"><strong>SGST @ '+ sGst +'%:</strong></div><div class="col-md-4 text-right">&#8377;'+ sGstPrice +'</div>'
                        +'<div class="col-md-8"><strong>Service Charge:</strong></div><div class="col-md-4 text-right">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price:</strong></div><div class="col-md-4 text-right">&#8377;'+ totalPrice +'</div></div>';
                    $(".price-section").html(priceHtml);
                } else {
                    priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room:</strong></div><div class="col-md-4">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Gross Price:</strong></div><div class="col-md-4">&#8377;'+ customeTotalPrice +'</div>'
                        +'<div class="col-md-12"><button type="button" class="btn m-t-10 m-b-10" style="border-radius: 0;background: #ffd400;border: 2px dashed #333;width: 100%;color: #333;">'+ couponName +'</button><i class="fa fa-times cancelcoupon" style="position:absolute;color:red;font-size:27px;margin-left:-15px;"></i></div>'
                        +'<div class="col-md-8"><strong>Discount:</strong></div><div class="col-md-4">- &#8377;'+ coupon_amount +'</div><div class="col-md-8"><strong>Sub Total Price:</strong></div><div class="col-md-4">&#8377;'+ subTotalPrice +'</div>'
                        +'<div class="col-md-8"><strong>Service Charge:</strong></div><div class="col-md-4">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price:</strong></div><div class="col-md-4">&#8377;'+ totalPrice +'</div></div>';
                    $(".price-section").html(priceHtml);
                }
            }
        }
        
        function cancelCoupon() {
            couponStatus = 0;
            let responce = responseData;
            let gstHtml = '';
            let gstRes = responce.gst_data;
            let taxAmount = 0;let taxPercent = 0;
            for(let i = 0; i < gstRes.length; i++) {
                taxAmount += Number(gstRes[i].gst_value);
                taxPercent += Number(gstRes[i].gst_percentage);
                gstHtml += '<div class="col-md-8"><strong>'+ gstRes[i].gst_name +' @ '+ gstRes[i].gst_percentage +'% :</strong></div><div class="col-md-4 text-right">&#8377;'+ (gstRes[i].gst_value).toFixed(2) +'</div>';
            }                            
            let hotelSellPrice = (responce.grossPrice - responce.extra_price).toFixed(2);
            let priceHtml = '<div class="row"><div class="col-md-8"><strong>Total Room :</strong></div><div class="col-md-4 text-right">'+ addtoCartTotal +'</div><div class="col-md-8"><strong>Hotel Sell Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ hotelSellPrice +'</div>'
            +'<div class="col-md-8"><strong>Extra Adult / Child Charges :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.extra_price).toFixed(2) +'</div>'
            +'<div class="col-md-8"><strong>Gross Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.grossPrice).toFixed(2) +'</div><div class="col-md-8"><strong>Sub Total Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.subTotalPrice).toFixed(2) +'</div>'
            + gstHtml +'<div class="col-md-8"><strong>Service Charge :</strong></div><div class="col-md-4 text-right">&#8377;'+ Number(serviceFee) +'</div><div class="col-md-8"><strong>Total Price :</strong></div><div class="col-md-4 text-right">&#8377;'+ (responce.totalOrderPrice).toFixed(2) +'</div></div>';
            $(".price-section").html(priceHtml);
            $("#total_order_price").val((responce.totalOrderPrice).toFixed(2));
            $("#sub_total_price").val((responce.subTotalPrice).toFixed(2));
            $("#total_room_price").val((responce.grossPrice).toFixed(2));
            $("#tax_amount").val(taxAmount.toFixed(2));
            $("#tax_percentage").val(taxPercent.toFixed(2));
            $("#coupon_amount").val('0');
            $("#coupon_name").val('');
            $("#coupon_code").val('');
            $("#coupon_percent").val('0');
        }
    });
    
</script>

@endsection