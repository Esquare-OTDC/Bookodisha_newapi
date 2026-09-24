@extends('layouts.app')

@section('title','Hall Orders')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Hall Orders</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button class="btn btn-info" id="printTable">Print</button>
            <button class="btn btn-info" id="expotrtSummary">Summary Report</button>
            <button class="btn btn-info"  id="expotrtDetail">Detailed Report</button>
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
                    <li role="presentation" class="active fetch_data" data-status="all"><a href="#home1" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true"><span class="visible-xs"><i class="ti-home"></i></span><span class="hidden-xs"> ALL BOOKING</span></a></li>
                    <li role="presentation" class="fetch_data" data-status="completed"><a href="#profile1" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-user"></i></span> <span class="hidden-xs">COMPLETED</span></a></li>
                    <li role="presentation" class="fetch_data" data-status="pending"><a href="#messages1" aria-controls="messages" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-email"></i></span> <span class="hidden-xs">PENDING</span></a></li>
                    <li role="presentation" class="fetch_data" data-status="cancelled"><a href="#settings1" aria-controls="settings" role="tab" data-toggle="tab" aria-expanded="false"><span class="visible-xs"><i class="ti-settings"></i></span> <span class="hidden-xs">CANCELLED</span></a></li>
                </ul>
                <br>
                <div class="col-md-12">
                    <form onsubmit="event.preventDefault();">
                        <div class="row">
                            @if (Auth::user()->role == '2')
                                <div class="col-md-4"></div>

                                <div class="col-md-3">
                                    <select class="form-control ml-5 filter" id="hall">
                                        <option value="">Select Hall</option>
                                        @foreach ($MasterHall as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div class="col-md-7"></div>
                            @endif

                            <div class="col-md-2">
                                <select class="form-control ml-5 filter" id="orderType">
                                    <option value="">Order Type</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>

                                    @foreach ($OfflineAgents as $key => $value)
                                        <option value="{{ $value }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select class="form-control ml-5 filter" id="payMethod">
                                    <option value="">Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="hdfc">Online</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                        </div>

                        <div class="row m-t-5">
                            <div class="col-md-4"></div>

                            <div class="col-md-3">
                                <select class="form-control ml-5" id="customColumn">
                                    <option value="">Select</option>
                                    <option value="invoice_id">Booking Id</option>
                                    <option value="transaction_id">Txn Id</option>
                                    <option value="created_at">Booking Date</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <span class="mr-sm-2" id="searchInput">
                                    <input type="text" id="searchValue" class="form-control">
                                </span>
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                                <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Booking Id</th>
                            <th>Hall Name</th>
                            <th>Customer Name</th>
                            <th>Customer Phone</th>
                            <th>Booking Date</th>
                            <th>Slot</th>
                            <th>Total Amount</th>
                            <th>Order Type</th>
                            <th>Status</th>
                            <th>Payment<br>Method</th>
                            <th>Payment<br>Status</th>
                            <th>Txn Id</th>
                            <!-- <th>Arrival Time</th> -->
                            <th>Action</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="13" class="dataTables_empty">Loading data from server...</td>
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

<div class="modal fade" id="updateOrderModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document" style="width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Update Booking Details</h4> </div>
            <div class="modal-body" id="updateDetails">
                <form id="updateOrderForm" onsubmit="event.preventDefault();">
                    <input type="hidden" name="orderId" id="order_id">
                    <input type="hidden" name="request_type" value="save_hall_customer_details">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="display-modal">Customer Name</label>
                                <input type="text" name="customer_name" class="form-control update-field" id="custName" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="display-modal">Customer Phone</label>
                                <input type="text" name="customer_phone" class="form-control update-field numvalidate" maxlength="10" id="custPhone" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="display-modal">Customer Email</label>
                                <input type="email" name="customer_email" class="form-control update-field" id="custEmail" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="display-modal">GST Regd No</label>
                                <input type="text" name="gst_regd_no" class="form-control update-field" id="custGSTNo">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="display-modal">GST Company Name</label>
                                <input type="text" name="gst_company_name" class="form-control update-field" id="custGSTCompany">
                            </div>
                        </div>

                        <div class="col-md-6" id="paymentMode"></div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="saveOrderData" class="btn btn-default">Save Changes</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
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
    #cancelPolicyModal, #updateOrderModal {
        color: black;
    }
    .border-top {
        border-top: 1px solid #c9c9c99e;
        margin-top: 8px;
        padding-top: 5px;
    }
    .table-responsive {
        min-height: 300px;
        padding-bottom: 140px;
        margin-bottom: -140px;
    }
    #datatable-responsive .btn-group {
        position: relative;
    }
    #datatable-responsive .dropdown-menu {
        z-index: 9999;
        min-width: 190px;
    }
    #datatable-responsive .btn-group.open .dropdown-menu {
        display: block;
        top: auto;
        right: 0;
        bottom: 100%;
        left: auto;
        margin-bottom: 2px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        var aTarget = 12;
        let searchValue1 = 'all';
        let searchValue2 = '';
        let searchValue3 = '';
        let searchValue4 = '';
        let searchValue5 = '';
        let searchValue9 = '';
        let searchValue10 = '';
        var exportQuery = '';
        var printQuery = '';
        var oTable;

        let url_string = window.location.href;
        var url = new URL(url_string);
        var searchValue6 = url.searchParams.get("roomId");
        var searchValue7 = url.searchParams.get("date");
        var searchValue8 = url.searchParams.get("hotelId");

        getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);

        $(document).on('click', '.fetch_data', function () {
            let searchText1 = $(this).data('status');

            if (searchText1 != '') {
                searchValue1 = searchText1;
                getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);
            }
        });

        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();

            if (column == 'created_at' || column == 'start_date' || column == 'end_date') {
                $("#searchInput").html('<input class="form-control input-daterange-datepicker" id="searchValue" type="text">');

                $('.input-daterange-datepicker').daterangepicker({
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment().add('+1', 'days'),
                    locale: {
                        format: 'DD MMM YYYY'
                    }
                });
            } else {
                $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            }
        });

        $(document).on('click', '#submitsearchText', function () {
            let searchText2 = '';
            let searchText3 = $("#customColumn").val();
            let searchText4 = $("#searchValue").val();
            let searchText5 = $("#hall").length ? $("#hall").val() : '';
            let searchText9 = $("#orderType").val();
            let searchText10 = $("#payMethod").val();

            if (searchText5 != '' || searchText9 != '' || searchText10 != '' || (searchText3 != '' && searchText4 != '')) {
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                searchValue4 = searchText4;
                searchValue5 = searchText5;
                searchValue9 = searchText9;
                searchValue10 = searchText10;

                getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);
            }
        });

        $(document).on('click', '#resetSession', function () {
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#customColumn").val('');
            $("#hall").val('');
            $(".filter").val('');

            searchValue2 = '';
            searchValue3 = '';
            searchValue4 = '';
            searchValue5 = '';
            searchValue9 = '';
            searchValue10 = '';

            getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);
        });

        $(document).on('click', '.user_details', function () {
            let orderId = $(this).data('id');

            if (orderId != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        orderId: orderId,
                        request_type: 'get_customer_details'
                    },
                    success: function (data) {
                        var responce = $.parseJSON(data);

                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let resData = responce.data;

                            resData['customer_notes'] = (resData['customer_notes'] != null) ? resData['customer_notes'] : 'N/A';
                            resData['customer_address2'] = (resData['customer_address2'] != null) ? resData['customer_address2'] : 'N/A';

                            let UserData = '<tr><th>Name</th><td>' + resData['customer_name'] + '</td></tr>';
                            UserData += '<tr><th>Email</th><td>' + resData['customer_email'] + '</td></tr>';
                            UserData += '<tr><th>Phone</th><td>' + resData['customer_phone'] + '</td></tr>';
                            UserData += '<tr><th>Address line 1</th><td>' + resData['customer_address1'] + '</td></tr>';
                            UserData += '<tr><th>Address line 2</th><td>' + resData['customer_address2'] + '</td></tr>';
                            UserData += '<tr><th>City</th><td>' + resData['customer_city'] + '</td></tr>';
                            UserData += '<tr><th>State</th><td>' + resData['customer_state'] + '</td></tr>';
                            UserData += '<tr><th>Country</th><td>' + resData['customer_country'] + '</td></tr>';
                            UserData += '<tr><th>ZIP code</th><td>' + resData['customer_zipcode'] + '</td></tr>';
                            UserData += '<tr><th>Special Requirements</th><td>' + resData['customer_notes'] + '</td></tr>';

                            $("#userDetails").html(UserData);
                        }
                    }
                });
            }
        });

        $(document).on('click', '.order_details', function () {
            let orderId = $(this).data('id');

            $("#orderDetails").html('<p class="text-center">Loading order details...</p>');

            if (orderId != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        orderId: orderId,
                        request_type: 'get_hall_order_details'
                    },
                    success: function (data) {
                        var responce = $.parseJSON(data);

                        if (responce.status == 0) {
                            $("#orderDetails").html('');
                            alert(responce.message);
                        } else {
                            $("#orderDetails").html(responce.data);
                        }
                    },
                    error: function (xhr) {
                        $("#orderDetails").html('<p class="text-danger text-center">Unable to load order details.</p>');
                        console.log(xhr.responseText);
                    }
                });
            }
        });
        $(document).on('click', '.update_order', function () {
            let orderId = $(this).data('id');

            if (orderId != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        orderId: orderId,
                        request_type: 'get_hall_customer_details'
                    },
                    success: function (data) {
                        var responce = $.parseJSON(data);

                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            $("#order_id").val(responce.data.id);
                            $("#custchkIn").val(responce.data.customer_checkin);
                            $("#custChkOut").val(responce.data.customer_checkout);
                            $("#custName").val(responce.data.customer_name);
                            $("#custEmail").val(responce.data.customer_email);
                            $("#custPhone").val(responce.data.customer_phone);
                            $("#custGSTNo").val(responce.data.gst_regd_no);
                            $("#custGSTCompany").val(responce.data.gst_company_name);

                            if (responce.data.payment_gateway == 'credit') {
                                let html = '<div class="form-group">'
                                    + '<label class="display-modal">Payment Method</label>'
                                    + '<select class="form-control" name="payment_gateway"><option value="credit" selected>Credit</option><option value="cash">Cash</option><option value="upi">UPI</option><option value="card">Card</option></select>'
                                    + '</div>';

                                $("#paymentMode").html(html);
                            } else {
                                $("#paymentMode").html('');
                            }
                        }
                    }
                });
            }
        });

        $(document).on('click', '#saveOrderData', function () {
            let cust_name = $("#custName").val();
            let cust_email = $("#custEmail").val();
            let cust_phone = $("#custPhone").val();

            if (cust_name == '' || cust_email == '' || cust_phone == '') {
                alert('Customer Name or email or phone can not be empty.');
                return;
            }

            $.ajax({
                type: "POST",
                url: "{{ url('order-oprsn') }}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: $("#updateOrderForm").serialize(),
                success: function (data) {
                    var responce = $.parseJSON(data);
                    alert(responce.message);

                    if (responce.status != 0) {
                        $("#updateOrderModal").modal('toggle');
                        getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);
                    }
                }
            });
        });

        $(document).on('click', '#expotrtSummary', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        exportQuery: exportQuery,
                        request_type: 'export_hall_summary_report'
                    },
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);

                        downloadLink.href = url;
                        downloadLink.download = "Hall_order_summary_report_" + new Date().getTime() + ".csv";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });

        $(document).on('click', '#expotrtDetail', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        exportQuery: exportQuery,
                        request_type: 'export_hall_detailed_report'
                    },
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);

                        downloadLink.href = url;
                        downloadLink.download = "Hall_order_detailed_report_" + new Date().getTime() + ".csv";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });

        $(document).on('click', '#printTable', function () {
            if (printQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        printQuery: printQuery,
                        request_type: 'print_hall_order'
                    },
                    success: function (data) {
                        let url = data;
                        window.open(url, 'window name', 'window settings');
                        return false;
                    }
                });
            }
        });

        $(document).on('click', '.cancel_booking', function () {
            let orderId = $(this).data('id');
            let refAmt = $(this).data('amount');

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

            if (orderId != "" && cancelReason != "" && confirm('Are you sure want to cancel this order ?')) {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        orderId: orderId,
                        cancelReason: cancelReason.trim(),
                        request_type: 'cancel_order'
                    },
                    success: function (data) {
                        var responce = $.parseJSON(data);

                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            $("#cancelFullModal").modal('toggle');
                            getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);
                        }
                    }
                });
            }
        });

        $(document).on('click', '.cancel_booking_policy', function () {
            let orderId = $(this).data('id');
            let cur_date = moment().format('DD-MM-YYYY');

            $("#canDatePicker").html('<input type="text" class="form-control" id="cancelDate" placeholder="dd-mm-yyyy">');

            if (orderId != "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        ID: orderId,
                        request_type: 'view_refund_amount',
                        date: cur_date
                    },
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
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        ID: orderId,
                        request_type: 'view_refund_amount',
                        date: canceldate
                    },
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

            if (orderId != "" && canceldate != "" && confirm('Are you sure want to cancel this order ?')) {
                $.ajax({
                    type: "POST",
                    url: "{{ url('order-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        orderId: orderId,
                        request_type: 'cancel_order_policy',
                        date: canceldate,
                        cancelReason: cancelReason
                    },
                    success: function (data) {
                        var responce = $.parseJSON(data);

                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5, searchValue6, searchValue7, searchValue8, searchValue9, searchValue10);
                            $("#cancelPolicyModal").modal('toggle');
                        }
                    }
                });
            }
        });

        function getHotels(oTable, searchValue1 = '', searchValue2 = '', searchValue3 = '', searchValue4 = '', searchValue5 = '', searchValue6 = '', searchValue7 = '', searchValue8 = '', searchValue9 = '', searchValue10 = '') {
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
                    "url": "{{ route('get-hall-orders') }}",
                    "type": "POST",
                    "data": function (data) {
                        data._token = "{{ csrf_token() }}";
                        data.searchValue1 = searchValue1;
                        data.searchValue2 = searchValue2;
                        data.searchValue3 = searchValue3;
                        data.searchValue4 = searchValue4;
                        data.searchValue5 = searchValue5;
                        data.searchValue6 = searchValue6;
                        data.searchValue7 = searchValue7;
                        data.searchValue8 = searchValue8;
                        data.searchValue9 = searchValue9;
                        data.searchValue10 = searchValue10;
                    },
                    "error": function (xhr) {
                        console.error('Hall Orders request failed:', xhr.status, xhr.responseText);
                        alert('Unable to load Hall Orders. HTTP status: ' + xhr.status);
                    }
                },
                "aoColumnDefs": [{
                    'bSortable': false,
                    'aTargets': [aTarget]
                }],
                "aLengthMenu": [
                    [5, 10, 20, 50, 100],
                    [5, 10, 20, 50, 100]
                ],
                "order": [],
                "iDisplayLength": 10,
                "drawCallback": function (settings) {
                    $('.table-responsive').scrollLeft(0);

                    var totalrecords = oTable.fnSettings().fnRecordsTotal();
                    $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
                    $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
                    $("#datatable-responsive_info").hide();

                    let Response = settings.json;
                    exportQuery = Response.exportQuery;
                    printQuery = Response.printQuery;
                }
            });
        }

        function setcancelFields(responce) {
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
