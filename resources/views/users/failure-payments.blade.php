@extends('layouts.app')

@section('title','Failure Payments')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Failure Payments</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            @if(Auth::user()->role == 2 || Auth::user()->role == 1)
            <button class="btn btn-info" id="checkPayment">Check Failure Payments</button>
            @endif
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
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>
                <!-- <form class="form-inline pull-right">
                    <select id="searchColumn" class="form-control">
                        <option value="">Select</option>
                        <option value="invoice_id">Invoice Id</option>
                        <option value="service_type">Service Type</option>
                        <option value="service_name">Service Name</option>
                        <option value="customer_name">Customer Name</option>
                        <option value="customer_phone">Customer Phone</option>
                        <option value="refund_amount">Refund Amount</option>
                        <option value="cancel_date">Refund Date</option>
                        <option value="payment_method">Payment Method</option>
                        <option value="reference_id">Reference Id</option>
                        <option value="bank_reference_num">Bank Ref. No.</option>
                    </select>
                    <span id="searchInput">
                        <input type="text" class="form-control" id="searchValue">
                    </span>
                    <button type="button" class="btn btn-sm btn-primary" id="search">Search</button>
                    <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form> -->
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Booking ID</th>
                            <th>Service Type</th>
                            <th>Service Name</th>
                            <th>Customer Details</th>
                            <th>Amount Paid</th>
                            <th>Booking Date</th>
                            <th>Start/End Date</th>
                            <th>Payment Method</th>
                            <th>Transaction ID</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="10" class="dataTables_empty">Loading data from server...</td>
                            </tr>
                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    var exportQuery = '';
    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';
        
        var oTable;
        getHotels(oTable, searchValue1, searchValue2);
        
        // $('#datepicker-autoclose').datepicker({
        //     autoclose: true,
        //     todayHighlight: true,
        //     format: 'dd-mm-yyyy'
        //     startDate: '-0m',
        //     endDate: '+120d'
        // });
        
        // $(document).on('change', '#searchColumn', function () {
        //     let column = $(this).val();
        //     if(column == 'cancel_date') {
        //         $("#searchInput").html('<input class="form-control input-daterange-datepicker" id="searchValue" type="text">');
        //         $('.input-daterange-datepicker').daterangepicker({
        //             opens: 'left',
        //             autoApply: true,
        //             startDate: moment(),
        //             endDate: moment().add('+1', 'days'),
        //             locale: {
        //               format: 'DD MMM YYYY'
        //             }
        //         });
        //     } else {
        //         $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
        //     }
        // });
        
        // $(document).on('click', '#search', function () {
        //     let searchText1 = $("#searchColumn").val();
        //     let searchText2 = $("#searchValue").val();
        //     if (searchText1 != '' && searchText2 != '') {
        //         searchValue1 = searchText1;
        //         searchValue2 = searchText2;
        //         getHotels(oTable, searchValue1, searchValue2);
        //     }
        // });
        
        // $(document).on('click', '#resetSession', function () {
        //     $("#select2-chosen-1").html('Select Vendor');
        //     $("#searchColumn").val('');
        //     $("#searchValue").val('');
        //     getHotels(oTable);
        // });
        
        // $(document).on('click', '#expotrtCSV', function () {
        //     if (exportQuery != "") {
        //         $.ajax({
        //             type: "POST",
        //             url: "{{url('refund-oprsn')}}",
        //             headers: {
        //                 'X-CSRF-Token': '{{ csrf_token() }}',
        //             },
        //             data: {exportQuery: exportQuery, request_type: 'export_refund_history'},
        //             success: function (data) {
        //                 var downloadLink = document.createElement("a");
        //                 var blob = new Blob(["\ufeff", data]);
        //                 var url = URL.createObjectURL(blob);
        //                 downloadLink.href = url;
        //                 downloadLink.download = "Refund_report_"+ new Date().getTime() +".csv";  //Name the file here
        //                 document.body.appendChild(downloadLink);
        //                 downloadLink.click();
        //                 document.body.removeChild(downloadLink);
        //             }
        //         });
        //     }
        // });
        
        $(document).on('click', '#checkPayment', function () {
            $.ajax({
                type: "POST",
                url: "{{url('refund-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: 'check_falied_transaction'},
                success: function (data) {
                    alert("Success.");
                    getHotels(oTable, searchValue1, searchValue2);
                }
            });
        });

        $(document).on('click', '.refundOrder', function () {
            let Id = $(this).data('id');
            if(Id != "" && confirm('Are you sure want to initiate refund ?')){
                $.ajax({
                    type: "POST",
                    url: "{{url('refund-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, request_type: 'refund_falied_transaction'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2);
                        }
                    }
                });
            } 
        });

        $(document).on('click', '.successOrder', function () {
            let Id = $(this).data('id');
            if(Id != "" && confirm('Are you sure want to success this booking ?')){
                $.ajax({
                    type: "POST",
                    url: "{{url('refund-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, request_type: 'success_falied_transaction'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2);
                        }
                    }
                });
            } 
        });
    });
    function getHotels(oTable, searchValue1 = '', searchValue2 = '') {
        if ($.fn.DataTable.isDataTable('#datatable-responsive')) {
             $('#datatable-responsive').DataTable().destroy();
        }
        oTable = $('#datatable-responsive').dataTable({
            "bProcessing": true,
            "fixedHeader": {
                header: true
            },
            // "searching": false,
            "bServerSide": true,
            "bPaginate": true,
           "ajax": {
                "url": "{{ url('get-failure-payments') }}",
                "type": "POST",
                "data": {
                    _token: "{{csrf_token()}}",
                    "searchValue1": searchValue1,
                    "searchValue2": searchValue2,
                    "searchValue3": '{{ $condition }}'
                },
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [9]
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "order": [5,'asc'],
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
</script>

@endsection