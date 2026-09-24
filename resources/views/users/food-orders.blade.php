@extends('layouts.app')

@section('title','Food Orders')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Food Orders</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
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
                    <form class="row" onsubmit="event.preventDefault();" style="//float: right;">
                        <div class="col-md-1"></div>
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
                        <div class="col-md-2">
                        <select class="form-control ml-5" id="customColumn">
                            <option value="">Select</option>
                            <option value="service_name">Food Supplier</option>
                            <option value="order_id">Order Id</option>
                            <option value="invoice_id">Invoice Id</option>
                            <option value="transaction_id">Txn Id</option>
                            <option value="created_at">Booking Date</option>                            
                        </select>
                        </div>
                        <div class="col-md-4">
                        <span class="mr-sm-2" id="searchInput"><input type="text" id="searchValue" class="form-control"></span>
                        </div>
                        <div class="col-md-2">
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                        <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                        </div>
                    </form>
                </div>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            @if(Auth::user()->access_type == 'superadmin')
                                <th>Vendor</th>
                            @endif
                            <th>Food Supplier</th>
                            <th>Customer Name</th>
                            <th>Customer Phone</th>
                            <th>Booking Date</th>
                            <th>Order Id</th>
                            <th>Invoice Id</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Payment<br>Method</th>
                            <th>Payment<br>Status</th>
                            <th>Txn Id</th>
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
</style>

<script type="text/javascript">
    $(document).ready(function () {
        var aTarget = Number("{{ (Auth::user()->access_type == 'superadmin') ? 12 : 11 }}");
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        let searchValue4 = '';
        let searchValue5 = '';
        var exportQuery = '';
        var oTable;
        getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5);
       
        $(document).on('click', '.fetch_data', function () {
            let searchText1 = $(this).data('status');
            if (searchText1 != '') {
                searchValue1 = searchText1;
                if (searchValue1 == 'all') { searchValue1 = ''; }
                getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5);
            }
        });
        
        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();
            if(column == 'created_at') {
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
            let searchText2 = $("#venderId").val();
            let searchText3 = $("#customColumn").val();
            let searchText4 = $("#searchValue").val();
            if (searchText2 != '' || (searchText3 != '' && searchText4 != '')) {
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                searchValue4 = searchText4;
                getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#venderId").val('');
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#customColumn").val('');
            getHotels(oTable, searchValue1, searchValue2 = '', searchValue3 = '', searchValue4 = '', searchValue5 = '');
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
                    data: {orderId: orderId, request_type: 'get_food_order_details'},
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
        
        $(document).on('click', '.cancel_booking', function () {            
            let orderId = $(this).data('id');
            if(orderId != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderId: orderId, request_type: 'cancel_order'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2, searchValue3, searchValue4, searchValue5);
                        }
                    }
                });
            }
        });
        
        $(document).on('click', '#expotrtSummary', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_food_summary_report'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "Food_order_summary_report_"+ new Date().getTime() +".csv";  //Name the file here
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
                    url: "{{url('order-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_food_detailed_report'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "food_order_detailed_report_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });
        
        function getHotels(oTable, searchValue1 = '',searchValue2 = '',searchValue3 = '',searchValue4 = '',searchValue5 = '') {
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
                    "url": "{{ url('get-food-orders') }}",
                    "type": "POST",
                    "data": {
                        _token: "{{csrf_token()}}",
                        "searchValue1": searchValue1,
                        "searchValue2": searchValue2,
                        "searchValue3": searchValue3,
                        "searchValue4": searchValue4,
                        "searchValue5": searchValue5
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
                }
            });
        }
    });           
</script>

@endsection