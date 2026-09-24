@extends('layouts.app')

@section('title','Tour Orders')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Tour Orders</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button class="btn btn-info" id="printSight">Print Sight Seeing</button>
            <button class="btn btn-info" id="printPackage">Print Package</button>
            <button class="btn btn-info" id="expotrtSummary">Summary Report</button>
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
            <div class="white-box">
                <ul class="nav customtab nav-tabs" role="tablist">
                    <li role="presentation" class="fetch_data" data-status="all"><a href="#home1" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-home"></i></span><span class="hidden-xs"> ALL BOOKING</span></a></li>
                    <li role="presentation" class="active fetch_data" data-status="completed"><a href="#profile1" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-user"></i></span> <span class="hidden-xs">COMPLETED</span></a></li>
                    <li role="presentation" class="fetch_data" data-status="pending"><a href="#messages1" aria-controls="messages" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-email"></i></span> <span class="hidden-xs">PENDING</span></a></li>
                    <li role="presentation" class="fetch_data" data-status="cancelled"><a href="#settings1" aria-controls="settings" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-settings"></i></span> <span class="hidden-xs">CANCELLED</span></a></li>
                </ul>
                <br>
                <div class="col-md-12">
                    <form onsubmit="event.preventDefault();" style="//float: right;">
                        <div class="row">
                            <div class="col-md-3">
                            @if(Auth::user()->role == 1)
                            <select class="form-control select2" id="venderId">
                                <option value="">Select Vendor</option>
                                @foreach ($Vendors as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @endif
                            </div>
                            <div class="col-md-4">
                                <select class="form-control ml-5 filter" id="tour">
                                    <option value="">Select Tour</option>
                                    @foreach ($Tour as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control ml-5 filter" id="orderType">
                                    <option value="">Order Type</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control ml-5 filter" id="payMethod">
                                    <option value="">Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="hdfc">Online</option>
                                </select>
                            </div>
                        </div>
                        <div class="row m-t-5">
                            <div class="col-md-2">
                                <select class="form-control ml-5 filter" id="serviceCity">
                                    <option value="">Start From</option>
                                    @foreach ($TourCity as $value)
                                    <option value="{{ $value }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control ml-5 filter" id="category">
                                    <option value="">Category</option>
                                    <option value="sight seeing">Sight seeing</option>
                                    <option value="package">Package</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control ml-5" id="customColumn">
                                    <option value="">Select</option>
                                    <!-- <option value="service_name">Tour Name</option> -->
                                    <!-- <option value="service_category">Tour Category</option> -->
                                    <!-- <option value="order_id">Order Id</option> -->
                                    <option value="invoice_id">Invoice Id</option>
                                    <option value="transaction_id">Txn Id</option>
                                    <option value="created_at">Booking Date</option>
                                    <option value="start_date">Check-in Date</option>
                                    <option value="end_date">Check-out Date</option>
                                    <option value="customer_id">Agent</option>
                                    <option value="request_from">Book Through</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                            <span class="mr-sm-2" id="searchInput"><input type="text" id="searchValue" class="form-control"></span>
                            </div>
                            <div class="col-md-2">
                            <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                            <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                            </div>
                        </div>                        
                    </form>
                    <br>
                </div>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            @if(Auth::user()->access_type == 'superadmin')
                                <th>Vendor</th>
                            @endif
                            <th>Invoice Id</th>
                            <th>Tour Name</th>
                            <th>Customer Name</th>
                            <th>Customer Phone</th>
                            <th>Start From</th>
                            <th>No of<br>Guest</th>
                            <th>Tour Type</th>
                            <th>Booking Date</th>
                            <th>Check In/Out</th>
                            <th>Order Type</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Payment<br>Method</th>
                            <th>Payment<br>Status</th>
                            <th>Txn Id</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="17" class="dataTables_empty">Loading data from server...</td>
                            </tr>
                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">User Details</h4> </div>
            <div class="modal-body">
                <table class="table">
                    <tbody id="userDetails">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document" style="width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Order Details</h4> </div>
            <div class="modal-body"  id="orderDetails">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelPolicyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Cancel Booking (Refund as policy)</h4>
            </div>
            <div class="modal-body">
                <form onsubmit="event.preventDefault();">
                    <input type="hidden" id="orderId">
                    <div class="form-group">
                        <label class="display-modal">Cancel Date:</label>
                        <div id="canDatePicker"></div>
                    </div>
                    <div class="form-group">
                        <label class="display-modal">Calculated refund amount</label>
                        <div class="row">
                            <div class="col-md-9 text-right">Sub total</div>
                            <div class="col-md-3 text-right update-field" id="subtotal"></div>
                            <div class="col-md-9 text-right">GST</div>
                            <div class="col-md-3 text-right update-field" id="gst"></div>
                            <div class="col-md-9 text-right border-top">Total refund amount</div>
                            <div class="col-md-3 text-right update-field border-top" id="totalRefund"></div>
                        </div>
                    </div>
                    <div class="form-group display-modal">
                    <label class="display-modal">Cancel Reason</label>
                        <input type="text" class="form-control update-field" id="cancelReason">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" id="submitData" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelFullModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Cancel Booking (Refund Full)</h4>
            </div>
            <div class="modal-body">
                <form onsubmit="event.preventDefault();">
                    <input type="hidden" id="orderIdFull">
                    <div class="form-group">
                        <label class="display-modal">Total refund amount</label>
                        <input type="text" disabled class="form-control" id="totalRefundAmt">
                    </div>
                    <div class="form-group display-modal">
                    <label class="display-modal">Cancel Reason</label>
                        <input type="text" class="form-control update-field" id="cancelReasonFull">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" id="submitDataFull" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
    #orderDetails th {
        text-align: center;
    }
    #userDetails td {
        text-align: right;
    }
    #userDetails th {
        text-align: left;
    }
    #cancelPolicyModal {
        color: black;
    }
    .border-top {
        border-top: 1px solid #c9c9c99e;
        margin-top: 8px;
        padding-top: 5px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        var agents = JSON.parse('<?= $Agents ?>');
        var aTarget = Number("{{ (Auth::user()->access_type == 'superadmin') ? 16 : 15 }}");
        let searchValue1 = 'completed';
        let searchValue2 = '';
        let searchValue3 = '';
        let searchValue4 = '';
        let searchValue5 = '';
        let searchValue8 = '';
        let searchValue9 = '';
        let searchValue10 = '';
        let searchValue11 = '';
        var exportQuery = '';
        var printQuery = '';
        var printSightQuery = '';
        var printPackageQuery = '';
        var oTable;
        
        let url_string = window.location.href;
        var url = new URL(url_string);
        var searchValue6 = url.searchParams.get("tourId");
        var searchValue7 = url.searchParams.get("date");
        
        getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7);
       
        $(document).on('click', '.fetch_data', function () {
            let searchText1 = $(this).data('status');
            if (searchText1 != '') {
                searchValue1 = searchText1;
                if (searchValue1 == 'all') { searchValue1 = ''; }
                getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10, searchValue11);
            }
        });
        
        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();
            if(column == 'created_at' || column == 'start_date' || column == 'end_date') {
                $("#searchInput").html('<input class="form-control input-daterange-datepicker" id="searchValue" type="text">');
                $('.input-daterange-datepicker').daterangepicker({
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment().add('+1', 'days'),
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
            } else if(column == 'customer_id') {
                let html = '<option value="">Select Agent</option>';
                for (const key in agents) {
                    html += '<option value="'+ key +'">'+ agents[key] +'</option>';
                }
                $("#searchInput").html('<select class="form-control" id="searchValue">'+ html +'</select>');
            } else if(column == 'request_from') {
                let html = '<option value="web">Website</option><option value="mobile">Mobile</option>';
                $("#searchInput").html('<select class="form-control" id="searchValue">'+ html +'</select>');
            } else {
                $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            }
        });
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText2 = $("#venderId").val();
            let searchText3 = $("#customColumn").val();
            let searchText4 = $("#searchValue").val();
            let searchText5 = $("#tour").val();
            let searchText8 = $("#orderType").val();
            let searchText9 = $("#payMethod").val();
            let searchText10 = $("#serviceCity").val();
            let searchText11 = $("#category").val();
            if (searchText2 != '' || (searchText3 != '' && searchText4 != '') || searchText5 != '' || searchText8 != '' || searchText9 != '' || searchText10 != '' || searchText11 != '') {
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                searchValue4 = searchText4;
                searchValue5 = searchText5;
                searchValue8 = searchText8;
                searchValue9 = searchText9;
                searchValue10 = searchText10;
                searchValue11 = searchText11;
                getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10, searchValue11);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#venderId").val('');
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#customColumn").val('');
            $(".filter").val('');
            getHotels(oTable, searchValue1, searchValue2 = '', searchValue3 = '', searchValue4 = '', searchValue5 = '', searchValue6, searchValue7, searchValue8 = '', searchValue9 = '', searchValue10 = '', searchValue11 = '');
        });
        
        $(document).on('click', '.user_details', function () {            
            let orderId = $(this).data('id');
            if(orderId != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderId: orderId, request_type: 'get_customer_details'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let resData = responce.data;
                            resData['customer_notes'] = (resData['customer_notes'] != null) ? resData['customer_notes'] : 'N/A';
                            resData['customer_address2'] = (resData['customer_address2'] != null) ? resData['customer_address2'] : 'N/A';
                            let UserData = '<tr><th>Name</th><td>'+  resData['customer_name'] +'</td></tr>';
                            UserData += '<tr><th>Email</th><td>'+  resData['customer_email'] +'</td></tr>';
                            UserData += '<tr><th>Phone</th><td>'+  resData['customer_phone'] +'</td></tr>';                            
                            UserData += '<tr><th>Address line 1</th><td>'+  resData['customer_address1'] +'</td></tr>';
                            UserData += '<tr><th>Address line 2</th><td>'+  resData['customer_address2'] +'</td></tr>';
                            UserData += '<tr><th>City</th><td>'+  resData['customer_city'] +'</td></tr>';
                            UserData += '<tr><th>State</th><td>'+  resData['customer_state'] +'</td></tr>';
                            UserData += '<tr><th>Country</th><td>'+  resData['customer_country'] +'</td></tr>';
                            UserData += '<tr><th>ZIP code</th><td>'+  resData['customer_zipcode'] +'</td></tr>';
                            UserData += '<tr><th>Special Requirements</th><td>'+  resData['customer_notes'] +'</td></tr>';
                            $("#userDetails").html(UserData);
                            
                        }
                    }
                });
            }
        });
        
        $(document).on('click', '.order_details', function () {            
            let orderId = $(this).data('id');
            if(orderId != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderId: orderId, request_type: 'get_tour_order_details'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            $("#orderDetails").html(responce.data);
                        }
                    }
                });
            }
        });
        
        // $(document).on('click', '.cancel_booking', function () {            
        //     let orderId = $(this).data('id');
        //     if(orderId != "" && confirm('Are you sure want to cancel this order ?')){
        //         $.ajax({
        //             type: "POST",
        //             url: "{{url('order-oprsn')}}",
        //             headers: {
        //                 'X-CSRF-Token': '{{ csrf_token() }}',
        //             },
        //             data: {orderId: orderId, request_type: 'cancel_order'},
        //             success: function (data) {
        //                 var responce = $.parseJSON(data);
        //                 if (responce.status == 0) {
        //                     alert(responce.message);
        //                 } else {
        //                     alert(responce.message);
        //                     getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10, searchValue11);
        //                 }
        //             }
        //         });
        //     }
        // });
        
        $(document).on('click', '#expotrtSummary', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_tour_summary_report'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "Tour_order_summary_report_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });
        
        $(document).on('click', '#printSight', function () {
            if (printQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {printQuery: printQuery, request_type: 'print_sightseeing_order'},
                    success: function (data) {
                        let url = data;
                        window.open(url, 'window name', 'window settings');
                        return false;

                    }
                });
            }
        });
        
        $(document).on('click', '#printPackage', function () {
            if (printQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {printQuery: printQuery, request_type: 'print_pacakge_order'},
                    success: function (data) {
                        let url = data;
                        window.open(url, 'window name', 'window settings');
                        return false;

                    }
                });
            }
        });

        $(document).on('click', '.cancel_booking_policy', function () {            
            let orderId = $(this).data('id');
            let cur_date = moment().format('DD-MM-YYYY');
            $("#canDatePicker").html('<input type="text" class="form-control" id="cancelDate" placeholder="dd-mm-yyyy">');
            if(orderId != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {ID: orderId, request_type: 'view_refund_amount', 'date': cur_date},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            $("#orderId").val(orderId);
                            $('#cancelDate').val(cur_date);
                            setcancelFields(responce);                            
                        }
                    }
                });
            }
        });

        $(document).on('change', '#cancelDate', function () {            
            let orderId = $("#orderId").val();
            let canceldate = $(this).val();
            if (orderId != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {ID: orderId, request_type: 'view_refund_amount', 'date': canceldate},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            setcancelFields(responce);
                            
                        }
                    }
                });
            }
        });

        $(document).on('click', '#submitData', function () {            
            let orderId = $("#orderId").val();
            let canceldate = $("#cancelDate").val();
            let cancelReason = $("#cancelReason").val();
            if (cancelReason == '') {
                alert("Please enter cancel reason.");
                return;
            }
            if(orderId != "" && canceldate != "" && confirm('Are you sure want to cancel this order ?')) {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderId: orderId, request_type: 'cancel_order_policy', 'date': canceldate},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10, searchValue11);
                            $("#cancelPolicyModal").modal('toggle');
                        }
                    }
                });
            }
        });

        $(document).on('click', '.cancel_booking', function () {            
            let orderId = $(this).data('id');
            let refAmt = $(this).data('amount');
            // orderIdFull totalRefundAmt cancelReasonFull

            $("#orderIdFull").val(orderId);
            $('#totalRefundAmt').val(refAmt);
            $("#cancelFullModal").modal('toggle');
        });
        
        $(document).on('click', '#submitDataFull', function () {            
            let orderId = $("#orderIdFull").val();
            let cancelReason = $("#cancelReasonFull").val();
            if (cancelReason.trim() == '') {
                alert("Please provide cancel reason.");
                return;
            }
            // let orderStatus = $(this).data('status');
            if(orderId != "" && cancelReason != "" && confirm('Are you sure want to cancel this order ?')){
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderId: orderId, cancelReason: cancelReason.trim(), request_type: 'cancel_order'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            $("#cancelFullModal").modal('toggle');
                            getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10, searchValue11);
                        }
                    }
                });
            }
        });
       
        function getHotels(oTable, searchValue1 = '',searchValue2 = '',searchValue3 = '',searchValue4 = '',searchValue5 = '', searchValue6 = '', searchValue7 = '', searchValue8 = '', searchValue9 = '', searchValue10 = '', searchValue11 = '') {
            if ($.fn.DataTable.isDataTable('#datatable-responsive')) {
                 $('#datatable-responsive').DataTable().destroy();
            }
            oTable = $('#datatable-responsive').dataTable({
                "bProcessing": true,
                "fixedHeader": {
                    header: true
                },
                "bServerSide": true,
                "bPaginate": true,
               "ajax": {
                    "url": "{{ url('get-tour-orders') }}",
                    "type": "POST",
                    "data": {
                        _token: "{{csrf_token()}}",
                        "searchValue1": searchValue1,
                        "searchValue2": searchValue2,
                        "searchValue3": searchValue3,
                        "searchValue4": searchValue4,
                        "searchValue5": searchValue5,
                        "searchValue6": searchValue6,
                        "searchValue7": searchValue7,
                        "searchValue8": searchValue8,
                        "searchValue9": searchValue9,
                        "searchValue10": searchValue10,
                        "searchValue11": searchValue11
                    },
                },  
                "aoColumnDefs": [{
                    'bSortable': false,
                    'aTargets': [aTarget]
                }],
                "aLengthMenu": [[5, 10, 20, 50, 100], [5, 10, 20, 50, 100]],
                "order": [],
                "iDisplayLength": 10,
                "drawCallback": function (settings) {
                    var totalrecords = oTable.fnSettings().fnRecordsTotal();
                    $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
                    $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
                    $("#datatable-responsive_info").hide();
                    let Response = settings.json;
                    exportQuery = Response.exportQuery;
                    printQuery = Response.printQuery;
                    // printSightQuery = Response.printSightQuery;
                    // printPackageQuery = Response.printPackageQuery;
                }
            });
        }

        function setcancelFields (responce) {
            $(".update-field").val('');
            
            $("#subtotal").html(responce.sub_total);
            $("#gst").html(responce.gst);
            $("#totalRefund").html(responce.total_refund);
            $('#cancelDate').datepicker({
                autoclose: true,
                format: "dd-mm-yyyy",
                todayHighlight: true,
                startDate: new Date(responce.min_date),
                endDate: new Date(responce.max_date),
            });
        }
    });            
</script>

@endsection
