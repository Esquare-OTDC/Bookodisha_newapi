@extends('layouts.app')

@section('title', 'Ticket Offline Order')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Order</li>
                <li class="breadcrumb-item active">Ticket offline order</li>
            </ol>
        </div>
    </div>
    
    @if(Session::has('success'))
        <p class="flashMessage1" style="color: red; text-align: center;">
            {{ Session::get('success') }}
            @php
                Session::forget('success');
            @endphp
        </p>
    @endif

    @if(Session::has('orderId'))
    @php
    $orderId = Session::get('orderId');
    Session::forget('orderId');
    @endphp
    @endif
    
    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Create Offline Order</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('create-ticket-order') }}" id="offlineOrderForm" method="POST">
                    @csrf                    
                    <input type="hidden" name="adult_price" id="adult_price">
                    <input type="hidden" name="total_service_price" id="total_service_price">
                    <input type="hidden" name="sub_total_price" id="sub_total_price">
                    <input type="hidden" name="total_order_price" id="total_order_price">
                    <div class="col-md-12">
                        <div class="white-box hotel-section">
                            <h3 class="box-title">Ticket Details</h3><hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tour</label>
                                        <select class="form-control checkQty" name="service_name_id" id="service_name_id" required>
                                            <option value="">Select Ticket</option>
                                            @foreach ($Ticket as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('service_name_id'))
                                        <span class="text-danger">{{ $errors->first('service_name_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="text" class="form-control checkQty" name="start_date" id="start_date" placeholder="dd/mm/yyyy">

                                        @if ($errors->has('start_date'))
                                        <span class="text-danger">{{ $errors->first('start_date') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="sightseenDiv">
                            </div>
                        </div>
                        <div class="white-box user-section" style="display:block;">
                            <h3 class="box-title">User Details</h3><hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Name</label><span class="required_field">*</span>
                                        <input type="text" class="form-control" name="customer_name" required>
                                        @if ($errors->has('customer_name'))
                                        <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Phone</label><span class="required_field">*</span>
                                        <input type="text" class="form-control numvalidate" name="customer_phone" required>
                                        @if ($errors->has('customer_phone'))
                                        <span class="text-danger">{{ $errors->first('customer_phone') }}</span>
                                        @endif
                                    </div>
                                    <input type="hidden" name="payment_gateway" value="cash">
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
    .page-wrapper .container-fluid {
        color: #000;
    }
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        let orderId = '<?= $orderId ?>';
        if (orderId != '') {
            let url = "<?= $site ?>" + "booking-voucher/" + orderId;
            window.open(url, 'window name', 'window settings');
            return false;
        }
        
        var day_breakup_details = [];
        
        var pricingData = {};
        var gstApplicable = '';
        var cGst = 0;
        var sGst = 0;
        
        var couponStatus = 0;
        
        var serviceFee = 0;
        var coupon_amount = 0;
        var subTotalPrice = 0;
        var coupon_code = '';
        var responseData = {};
        var singlePrice = 0;
        var doublePrice = 0;
        var triplePrice = 0;
        var childPrice = 0;
        var totalPrice = 0;
        var maxTicket = 0;
        
        // $('#start_date').datepicker({
        //     autoclose: true,
        //     format: "dd-mm-yyyy",
        //     todayHighlight: true,
        //     startDate: '-0m',
        // });
        
        var length = 0;
        
        $(document).on("change", "#payment_gateway", function() {
            let paymentMethod = $(this).val();
            if (paymentMethod == 'cash') {
                $(".reqfields").removeAttr('required');
            } else {
                $(".reqfields").attr("required", "true");
            }
        });

        $(document).on('change','#service_name_id', function() {
            let serviceId = $(this).val();
            if (serviceId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('ticket-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {serviceId: serviceId, request_type: "get_ticket_date"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 1) {
                            let start_date = responce.start_date;
                            let end_date = responce.end_date;
                            $('#start_date').datepicker({
                                autoclose: true,
                                format: "dd-mm-yyyy",
                                todayHighlight: true,
                                startDate: new Date(start_date),
                                endDate: new Date(end_date),
                            });
                        }
                    }
                });
            }
            
        });
        
        $(document).on('change','.checkQty', function() {
            let tourId = $("#service_name_id").val();
            let checkIn = $("#start_date").val();
            if (tourId != '' && checkIn != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('ticket-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {tourId: tourId, checkinDate: checkIn, request_type: "get_ticket_details"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                            $("#packageDiv").html('');
                            $("#sightseenDiv").html('');
                        } else {
                            maxTicket = (responce.maxTicket < responce.max_ticket_per_txn) ? responce.maxTicket : responce.max_ticket_per_txn;
                            let ticketPrice = parseInt(responce.adult_price);
                            let html = '<div class="col-md-6"></div><div class="col-md-3 text-right"><strong>Tickets (&#8377;'+ parseInt(responce.adult_price) +' per Person)</strong></div><div class="col-md-3"><input class="form-control" type="number" name="total_adults" id="total_tickets" min="1" max="'+ maxTicket +'" value="1"></div><div class="col-md-6 m-t-5"></div><div class="col-md-3 m-t-5 text-right"><strong>Total Price</strong></div><div class="col-md-3 m-t-5 text-right">&#8377;<span id="sightTotal">'+ parseInt(ticketPrice) +'</span></div><div class="col-md-12 m-t-5"></div>';
                            $("#sightseenDiv").html(html);
                            $("#adult_price").val(parseInt(ticketPrice));
                            $("#total_service_price").val(parseInt(ticketPrice));
                        }
                    }
                });
            } else {
                $("#sightseenDiv").html('');
            }
        });
        
        $(document).on('change','#total_tickets', function() {
            let adult_price = $("#adult_price").val();
            let sightPrice = Number($(this).val()) * Number(adult_price);            
            $("#sightTotal").html(sightPrice.toFixed(2));
            $("#total_service_price").val(sightPrice.toFixed(2));
            $("#adult_price").val(adult_price);
            $("#sub_total_price").val(sightPrice);
            $("#total_order_price").val(sightPrice);
        });
        
        $(document).on('change','.packageTicket', function() {
            let adults = $("#total_adults").val();
            let child = $("#total_child").val();
            let price_type = 'singleoccupancy';
            let adult_price = singlePrice;
            if (adults % 2 == 0) {
                price_type = 'tripleoccupancy';
                adult_price = doublePrice;
            } else if (adults % 3 == 0) {
                price_type = 'tripleoccupancy';
                adult_price = triplePrice;
            }
            let child_price = childPrice;
            let total_price = (adult_price * adults) + (child_price * child);
            $("#adultPriceDiv").html(parseInt(adult_price));
            $("#packageTotal").html(total_price.toFixed(2));
            $("#total_service_price").val(total_price.toFixed(2));
            $("#adult_price").val(adult_price);
            $("#child_price").val(child_price);
            $("#price_type").val(price_type);
        });
        
        $(document).on('click','#confirmSelection', function(){
            let tourId = $("#service_name_id").val();
            let orderTotal = $("#total_service_price").val();
            if (tourId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {tourId: tourId, orderTotal: orderTotal, request_type: "confirm_tour"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            responseData = responce;
                            cancelCoupon();
                            $(".hotel-section").hide(2000);
                            $(".user-section").show(2000);
                        }
                    }
                });
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
            let tourId = $("#service_name_id").val();
            let checkIn = $("#start_date").val();
            if (coupon_code != '' && couponStatus == 0) {
                $.ajax({
                    type: "POST",
                    url: "{{url('tour-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {coupon_code: coupon_code, order_value: order_value, tourId: tourId, checkIn: checkIn, request_type: "verify_coupon"},
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
                                +'<div class="col-md-12"><button type="button" class="btn m-t-10 m-b-10" style="border-radius: 0;background: #ffd400;border: 2px dashed #333;width: 100%;color: #333;">'+ responce.coupon_data.coupon_name +' '+ parseInt(responce.coupon_data.coupon_value) +'% off</button><i class="fa fa-times cancelcoupon" style="position:absolute;color:red;font-size:27px;margin-left:-15px;"></i></div>'
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
            let responce = responseData;
            let gstHtml = '';
            let gstRes = responce.gst_data;
            let taxAmount = 0;let taxPercent = 0;
            let priceHtml = '<div class="row"><div class="col-md-8"><strong>Gross Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.grossPrice).toFixed(2) +'</div><div class="col-md-8"><strong>Sub Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.subTotalPrice).toFixed(2) +'</div>'
            + gstHtml +'<div class="col-md-8"><strong>Total Price :</strong></div><div class="col-md-4">&#8377;'+ (responce.totalOrderPrice).toFixed(2) +'</div></div>';
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