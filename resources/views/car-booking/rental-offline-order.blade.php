@extends('layouts.app')

@section('title', 'Rental Offline Order')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Rental</li>
                <li class="breadcrumb-item active">Rental offline order</li>
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
    @endif
    
    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Create Offline Order</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('create-rental-order') }}" id="offlineOrderForm" method="POST">
                    @csrf                    
                    <input type="hidden" name="room_request" id="room_request">
                    <input type="hidden" name="service_quantity" id="service_quantity" value="1">
                    <input type="hidden" name="rental_breakdown" id="rental_breakdown">
                    <input type="hidden" name="total_service_price" id="total_service_price">
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
                            <h3 class="box-title">Vehicle Details</h3><hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Vehicle</label>
                                        <select class="form-control select2 check-room" name="service_name_id" id="service_name_id" required>
                                            <option value="">Select Vehicle</option>
                                            @foreach ($MasterCar as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('service_name_id'))
                                        <span class="text-danger">{{ $errors->first('service_name_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="preferenceDiv" style="display:none;">
                                <div class="col-md-12">
                                    <table class="display nowrap table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Date</th>
                                                <th class="text-center">Pickup City</th>
                                                <th class="text-center">Pickup Address</th>
                                                <th class="text-center">Distance</th>
                                                <th class="text-center">Drop City</th>
                                                <th class="text-center">Drop Address</th>
                                            </tr>
                                        </thead>
                                        <tbody id="breakupDiv">
                                            <tr class="break" id="brk0">
                                                <td><input class="form-control input-daterange-datepicker input0" id="date0" type="text" name="day_breakup_details[0][date]"></td>
                                                <td>
                                                    <select class="form-control input0" name="day_breakup_details[0][pickup_city]" id="pickup_city0">
                                                        <option value="">Select City</option>
                                                        @foreach ($CityDetail as $val)
                                                        <option value="{{ $val }}">{{ $val }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input class="form-control input0" id="pickup_point0" type="text" name="day_breakup_details[0][pickup_point]"></td>
                                                <td><input class="form-control input0" id="day_km0" type="number" min="1" value="50" name="day_breakup_details[0][day_km]"></td>
                                                <td>
                                                    <select class="form-control input0" name="day_breakup_details[0][drop_city]" id="drop_city0">
                                                        <option value="">Select City</option>
                                                        @foreach ($CityDetail as $val)
                                                        <option value="{{ $val }}">{{ $val }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input class="form-control input0" id="drop_point0" type="text" name="day_breakup_details[0][drop_point]"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button class="btn btn-primary" type="button" id="confirmSelection" data-id="0">Confirm</button>
                                    <span style="float: right;">
                                        <span class="btn btn-info btn-sm" id="addNewRow" ><i class="icon-plus"></i> Add</span>
                                        <span><i class="btn btn-danger btn-sm fa fa-trash" id="deleteRow"></i></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="white-box user-section" style="display:none;">
                            <h3 class="box-title">User Details</h3><hr>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group ">
                                        <label>Name</label><span class="required_field">*</span>
                                        <input type="text" class="form-control" name="customer_name" required>
                                        @if ($errors->has('customer_name'))
                                        <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                                        @endif
                                    </div>                               
                                    <div class="form-group ">
                                        <label>Email</label><span class="required_field">*</span>
                                        <input type="email" class="form-control" name="customer_email" required>
                                        @if ($errors->has('customer_email'))
                                        <span class="text-danger">{{ $errors->first('customer_email') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Phone</label><span class="required_field">*</span>
                                        <input type="text" class="form-control numvalidate" name="customer_phone" required>
                                        @if ($errors->has('customer_phone'))
                                        <span class="text-danger">{{ $errors->first('customer_phone') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label>Address</label>
                                        <input type="text" class="form-control reqfields" name="customer_address1" >
                                        @if ($errors->has('customer_address1'))
                                        <span class="text-danger">{{ $errors->first('customer_address1') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Country</label>
                                        <select class="form-control reqfields" name="customer_country" id="customer_country" >
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
                                        <select class="form-control reqfields" name="customer_state" id="customer_state" >
                                            <option value="">Select State</option>
                                        </select>
                                        @if ($errors->has('customer_state'))
                                        <span class="text-danger">{{ $errors->first('customer_state') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>City</label>
                                        <select class="form-control reqfields" name="customer_city" id="customer_city" >
                                            <option value="">Select City</option>
                                        </select>
                                        @if ($errors->has('customer_city'))
                                        <span class="text-danger">{{ $errors->first('customer_city') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group ">
                                        <label>Zip Code</label>
                                        <input type="text" class="form-control reqfields" name="customer_zipcode" >
                                        @if ($errors->has('customer_zipcode'))
                                        <span class="text-danger">{{ $errors->first('customer_zipcode') }}</span>
                                        @endif
                                    </div>                                                                        
                                    <div class="form-group">
                                        <label>Booking Naration</label><span class="required_field">*</span>
                                        <textarea name="book_naration" rows="2" class="form-control" maxlength="100" placeholder="Please put MR No. and date / Payment details" required></textarea>
                                        @if ($errors->has('book_naration'))
                                        <span class="text-danger">{{ $errors->first('book_naration') }}</span>
                                        @endif
                                    </div>
                                    @if (Auth::user()->user_role == 'agent_staff')
                                        <input type="hidden" name="payment_gateway" value="cash">
                                    @else
                                    <div class="form-group">
                                        <label>Payment Method</label><span class="required_field">*</span>
                                        <select class="form-control" name="payment_gateway" id="payment_gateway" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="cash">Cash</option>
                                            <option value="hdfc">HDFC</option>
                                        </select>
                                        @if ($errors->has('payment_gateway'))
                                        <span class="text-danger">{{ $errors->first('payment_gateway') }}</span>
                                        @endif
                                    </div>    
                                    @endif
<!--                                    <div class="form-group ">
                                        <label>Payment Status</label>
                                        <select class="form-control" name="status" required>
                                            <option value="">Select Payment Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                        @if ($errors->has('status'))
                                        <span class="text-danger">{{ $errors->first('status') }}</span>
                                        @endif
                                    </div>-->
                                </div>
                                <div class="col-md-4">
                                    <h5 class="text-center" style="font-weight:bold;"><u>PAYMENT BREAKUP</u></h5>
                                    <div class="breakup-section"></div>
                                    <div class="price-section"></div>
                                    <div class="form-group m-t-10 form-inline">
                                        <label style="font-size: large;font-weight: 600;">APPLY COUPON</label><br>
                                        <input type="text" class="form-control" id="coupon_code">
                                        <button type="button" class="btn btn-info" id="verify_coupon">Apply</button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary" id="bookNow">Book Now</button>
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
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        
        var City = JSON.parse('<?= json_encode($CityDetail) ?>');
        var day_breakup_details = [];

        var pricingData = {};
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
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment().add('+1', 'days'),
            endDate: moment().add('+2', 'days'),
            minDate: moment().add('+1', 'days'),
            maxDate: moment().add('+120','days'),
            timePicker: true,
            timePickerIncrement: 5,
            timePicker12Hour: true,
            timePickerSeconds: false,
            locale: {
              format: 'DD MMM YYYY'
            }
        });

        $(document).on("change", "#payment_gateway", function() {
            let paymentMethod = $(this).val();
            if (paymentMethod == 'cash') {
                $(".reqfields").removeAttr('required');
            } else {
                $(".reqfields").attr("required", "true");
            }
        });
        
        var length = 0;
        
        $(document).on("click", "#addNewRow", function() {
            let valid = validateForm(length);
            if (valid) {
                let dateVal = $("#date"+ length).val();
                let dateArr = dateVal.split(' - ');
                let nextDate = new Date(dateArr[1]);
                $(".input"+ length).attr('disabled', true);

                let strtDate = $("#date"+ length).data('daterangepicker').startDate;
                let enDate = $("#date"+ length).data('daterangepicker').endDate;
                if (day_breakup_details.length == length) {
                    day_breakup_details.push({pickup_city: $("#pickup_city"+ length).val(), pickup_point: $("#pickup_point"+ length).val(), drop_city: $("#drop_city"+ length).val(), drop_point: $("#drop_point"+ length).val(), day_km: $("#day_km"+ length).val(), date : {startDate: strtDate.format('DD-MM-YYYY H:m:s'), endDate: enDate.format('DD-MM-YYYY H:m:s')}});
                }
                console.log(day_breakup_details);
                length++;
                let options = '<option value="">Select City</option>';
                for(let i = 0; i < City.length; i++) {
                    options += '<option value="'+ City[i] +'">'+ City[i] +'</option>';
                }
                $('#breakupDiv').append('<tr class="break" id="brk'+ length +'"><td> <input class="form-control input-daterange-datepicker input'+ length +'" id="date'+ length +'" type="text" name="day_breakup_details['+ length +'][date]"></td><td> <select class="form-control input'+ length +'" name="day_breakup_details['+ length +'][pickup_city]" id="pickup_city'+ length +'">'+ options +'</select></td><td> <input class="form-control input'+ length +'" id="pickup_point'+ length +'" type="text" name="day_breakup_details['+ length +'][pickup_point]"></td><td> <input class="form-control input'+ length +'" id="day_km'+ length +'" type="number" min="1" value="50" name="day_breakup_details['+ length +'][day_km]"></td><td> <select class="form-control input'+ length +'" name="day_breakup_details['+ length +'][drop_city]" id="drop_city'+ length +'">'+ options +'</select></td><td> <input class="form-control input'+ length +'" id="drop_point'+ length +'" type="text" name="day_breakup_details['+ length +'][drop_point]"></td></tr>');
                $("#deleteRow").data('id', length);
                $('#confirmSelection').data('id', length);

                $("#date"+ length).daterangepicker({
                    autoApply: true,
                    startDate: moment(nextDate).add('+30', 'hour'),
                    endDate: moment(nextDate).add('+54', 'hour'),
                    minDate: moment(nextDate).add('+30', 'hour'),
                    maxDate: moment().add('+120','days'),
                    timePicker: true,
                    timePickerIncrement: 5,
                    timePicker12Hour: true,
                    timePickerSeconds: false,
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
            }
        });
        
        $(document).on("click", "#deleteRow", function() {
            let id = $(this).data('id');
            if (id != 0) {
                if (day_breakup_details.length == $('.break').length) {
                    day_breakup_details.pop();
                }
                $("tr").remove("#brk"+id);
                length--;
                $("#deleteRow").data('id', length);
                $('#confirmSelection').data('id', length);
                console.log(day_breakup_details);
            }
        });
        
        $('#service_name_id').on('change',function() {
            let carId = $(this).val();
            resetForm(carId);
        });
        
        $('#confirmSelection').on('click',function() {
            let id = $(this).data('id');
            let valid = validateForm(id);
            if (valid) {
                let dateVal = $("#date"+ id).val();
                let dateArr = dateVal.split(' - ');
                let nextDate = new Date(dateArr[1]);
                $(".input"+ id).attr('disabled', true);

                let strtDate = $("#date"+ id).data('daterangepicker').startDate;
                let enDate = $("#date"+ id).data('daterangepicker').endDate;
                if (day_breakup_details.length == id) {
                    day_breakup_details.push({pickup_city: $("#pickup_city"+ id).val(), pickup_point: $("#pickup_point"+ id).val(), drop_city: $("#drop_city"+ id).val(), drop_point: $("#drop_point"+ id).val(), day_km: $("#day_km"+ id).val(), date : {startDate: strtDate.format('DD-MM-YYYY H:m:s'), endDate: enDate.format('DD-MM-YYYY H:m:s')}});
                }
                console.log(day_breakup_details);
                
                let carId = $('#service_name_id').val();
                if (carId != '') {
                    $.ajax({
                        type: "POST",
                        url: "{{url('car-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {carId: carId, day_breakup_details: JSON.stringify(day_breakup_details), request_type: "calculate_price"},
                        success: function (data) {
                            var responce = $.parseJSON(data);
                            if (responce.status == 0) {
                                alert(responce.message);
                            } else {
                                responseData = responce;
                                $("#room_request"). val(JSON.stringify(day_breakup_details));
                                $("#rental_breakdown"). val(JSON.stringify(responce.price_breakup));
                                cancelCoupon();
                                $(".hotel-section").hide(2000);
                                $(".user-section").show(2000);
                            }
                        }
                    });
                } else {
                    alert("Please select a vehicle");                    
                }
            }
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
        
        $(document).on('keyup','#coupon_code', function(){
           coupon_code = $(this).val();
        });
        
        $(document).on('click', '#verify_coupon', function () {
            let order_value = $("#sub_total_price").val();
            let carId = $("#service_name_id").val();
            let checkIn = day_breakup_details[0].date.startDate;
            let pricingData = $("#rental_breakdown").val();
            if (coupon_code != '' && couponStatus == 0) {
                $.ajax({
                    type: "POST",
                    url: "{{url('car-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {coupon_code: coupon_code, order_value: order_value, carId: carId, pricingData: pricingData, checkIn: checkIn, request_type: "verify_coupon"},
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
                                    gstHtml += '<div class="col-md-8"><strong>'+ gstRes[i].gst_name +' @ '+ gstRes[i].gst_percentage +'% :</strong></div><div class="col-md-4">&#8377;'+ gstRes[i].gst_value +'</div>';
                                }                            
                                let hotelSellPrice = (responce.grossPrice - responce.extra_price).toFixed(2);
                                let priceHtml = '<div class="row"><div class="col-md-8"><strong>Gross Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.grossPrice).toFixed(2) +'</div>'
                                +'<div class="col-md-12"><button type="button" class="btn m-t-10 m-b-10" style="border-radius: 0;background: #ffd400;border: 2px dashed #333;width: 100%;color: #333;">'+ responce.coupon_data.coupon_name +' '+ parseFloat(responce.coupon_data.coupon_value) +'% off</button><i class="fa fa-times cancelcoupon" style="position:absolute;color:red;font-size:27px;margin-left:-15px;"></i></div>'
                                +'<div class="col-md-8"><strong>Discount:</strong></div><div class="col-md-4">- &#8377;'+ (responce.discount_amount).toFixed(2) +'</div><div class="col-md-8"><strong>Sub Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.subTotalPrice).toFixed(2) +'</div>'
                                + gstHtml +'<div class="col-md-8"><strong>Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.totalOrderPrice).toFixed(2) +'</div></div>';
                                $(".price-section").html(priceHtml);
                                $("#total_order_price").val((responce.totalOrderPrice).toFixed(2));
                                $("#sub_total_price").val((responce.subTotalPrice).toFixed(2));
                                $("#total_service_price").val((responce.grossPrice).toFixed(2));
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
        
        function validateForm (index) {
            let selectDate = $("#date"+ index).val();
            let pickup_city = $("#pickup_city"+ length).val();
            let pickup_point = $("#pickup_point"+ length).val();
            let drop_city = $("#drop_city"+ length).val();
            let drop_point = $("#drop_point"+ length).val();
            let day_km = $("#day_km"+ length).val();
            if (selectDate == '') {
                alert("please select Date");
                $("#date"+ index).focus();
                return false;
            } else if (pickup_city == '') {
                alert("please select pickup city");
                $("#pickup_city"+ index).focus();
                return false;
            } else if (pickup_point == '') {
                alert("please enter pickup address");
                $("#pickup_point"+ index).focus();
                return false;
            } else if (drop_city == '') {
                alert("please select drop city");
                $("#drop_city"+ index).focus();
                return false;
            } else if (drop_point == '') {
                alert("please enter drop address");
                $("#drop_point"+ index).focus();
                return false;
            } else if (day_km == '') {
                alert("please enter distance");
                $("#day_km"+ index).focus();
                return false;
            } else {
                return true;
            }
        }
        
        function resetForm(carId) {
            day_breakup_details = [];
            $(".break").each(function() {
                let id = $(this).attr('id').replace('brk','');
                if (id != '0') {
                    $("tr").remove("#brk"+id);
                }
            });
            length = 0;
            $("#date0").removeAttr('disabled');
            $("#pickup_city0").removeAttr('disabled');
            $("#pickup_point0").val('');
            $("#pickup_point0").removeAttr('disabled');
            $("#day_km0").val('50');
            $("#day_km0").removeAttr('disabled');
            $("#drop_city0").val('');
            $("#drop_city0").removeAttr('disabled');
            $("#drop_point0").val('');
            $("#drop_point0").removeAttr('disabled');
            
            if (carId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('car-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {carId: carId, request_type: "get_car_details"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let city = responce.data.city;
                            $("#pickup_city0").val(city);
                            $("#pickup_city0").attr('disabled', 'true');
                            $("#preferenceDiv").show();
                        }
                    }
                });
            } else {
                $("#preferenceDiv").hide();
            }
        }
        
        function cancelCoupon() {
            couponStatus = 0;
            let responce = responseData;
            let payBrkHtml = '<div class="row">';
            let payBreak = responce.price_breakup;
            for(let i = 0; i < payBreak.length; i++) {
                let kmPriceHtml = ''; let hourPriceHtml = '';
                if (payBreak[i].totalPriceHr == 0) {
                    kmPriceHtml = '<tr><td><strong>Total KM Price</strong></td><td>('+ payBreak[i].kmValue +' x &#8377;'+ payBreak[i].price_per_km +')</td><td>&#8377;'+ (payBreak[i].totalPriceKm).toFixed(2) +'</td></tr>';
                } else {
                    hourPriceHtml = '<tr><td><strong>Chargeable Hour Price</strong></td><td>('+ payBreak[i].calculateHr +' x &#8377;'+ payBreak[i].price_per_hr +')</td><td>&#8377;'+ (payBreak[i].totalPriceHr).toFixed(2) +'</td></tr>';
                }
                let haltPriceHtml = '';
                if (payBreak[i].totalHaltPrice != 0) {
                    haltPriceHtml = '<tr><td><strong>Night Halting Price</strong></td><td>('+ payBreak[i].totalhalt +' x &#8377;'+ payBreak[i].price_for_halt +')</td><td>&#8377;'+ (payBreak[i].totalHaltPrice).toFixed(2) +'</td></tr>';
                }
                let detentaionHtml = '';
                if (payBreak[i].detainationCharge != 0) {
                    detentaionHtml = '<tr><td><strong>Detentaion Charges</strong></td><td>('+ payBreak[i].detainationHour +' x &#8377;'+ payBreak[i].detention_charge_per_hour +')</td><td>&#8377;'+ (payBreak[i].detainationCharge).toFixed(2) +'</td></tr>';
                }
                let extraKMHtml = '';
                if (payBreak[i].extrakm_price != 0) {
                    extraKMHtml = '<tr><td><strong>Extra KM Price</strong></td><td>('+ payBreak[i].extrakm +' x &#8377;'+ payBreak[i].price_per_km +')</td><td>&#8377;'+ (payBreak[i].extrakm_price).toFixed(2) +'</td></tr>';
                }
                
                payBrkHtml += '<div class="col-md-12"><table></table><table class="display nowrap table table-bordered"><tr><td><strong>Booking Date</strong></td><td colspan="2">'+ payBreak[i].booking_date.startDate +' - '+ payBreak[i].booking_date.endDate +'</td></tr>'
                + kmPriceHtml + hourPriceHtml + haltPriceHtml + detentaionHtml + extraKMHtml +'<tr><td><strong>Day Total</strong></td><td colspan="2">'+ (payBreak[i].totalPrice).toFixed(2) +'</td></tr></table></div>';
            }
            payBrkHtml += '</div>';
            
            let gstHtml = '';
            let gstRes = responce.gst_data;
            let taxAmount = 0;let taxPercent = 0;
            for(let i = 0; i < gstRes.length; i++) {
                taxAmount += Number(gstRes[i].gst_value);
                taxPercent += Number(gstRes[i].gst_percentage);
                gstHtml += '<div class="col-md-8"><strong>'+ gstRes[i].gst_name +' @ '+ gstRes[i].gst_percentage +'% :</strong></div><div class="col-md-4">&#8377;'+ gstRes[i].gst_value +'</div>';
            }                            
            let priceHtml = '<div class="row"><div class="col-md-8"><strong>Gross Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.grossPrice).toFixed(2) +'</div><div class="col-md-8"><strong>Sub Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.subTotalPrice).toFixed(2) +'</div>'
            + gstHtml +'<div class="col-md-8"><strong>Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.totalOrderPrice).toFixed(2) +'</div></div>';
            $(".breakup-section").html(payBrkHtml);
            $(".price-section").html(priceHtml);
            $("#total_order_price").val((responce.totalOrderPrice).toFixed(2));
            $("#sub_total_price").val((responce.subTotalPrice).toFixed(2));
            $("#total_service_price").val((responce.grossPrice).toFixed(2));
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