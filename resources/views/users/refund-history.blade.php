@extends('layouts.app')

@section('title','Refund History')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Refund History</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button class="btn btn-info" id="expotrtCSV">Export</button>
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
                <form class="form-inline pull-right">
                    <select id="searchColumn" class="form-control">
                        <option value="">Select</option>
                        <option value="invoice_id">Booking Id</option>
                        <option value="service_type">Service Type</option>
                        <option value="service_name">Service Name</option>
                        <option value="customer_name">Customer Name</option>
                        <option value="customer_phone">Customer Phone</option>
                        <option value="refund_amount">Refund Amount</option>
                        <option value="cancel_date">Cancel Date</option>
                        <option value="pg_txn_id">PayU Id</option>
                        <option value="reference_id">Reference Id</option>
                        <option value="bank_reference_num">Bank Ref. No.</option>
                        <option value="refund_status">Refund Status</option>
                    </select>
                    <span id="searchInput">
                        <input type="text" class="form-control" id="searchValue">
                    </span>
                    <button type="button" class="btn btn-sm btn-primary" id="search">Search</button>
                    <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Cancel Date</th>
                            <th>Booking ID</th>
                            <th>Service Type</th>
                            <th>Service Name</th>
                            <th>Customer Name</th>
                            <th>Paid Amount</th>
                            <th>Refund Amount</th>
                            <th>Payment Method</th>
                            <th>PayU Id</th>
                            <th>Reference Id</th>
                            <th>Bank Ref. No.</th>
                            <th>Status</th>
                            <th>Initiate Date</th>
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

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    var exportQuery = '';
    $(document).ready(function () {
        var status = JSON.parse('<?= $Status ?>');
        let searchValue1 = '';
        let searchValue2 = '';
        
        var oTable;
        getHotels(oTable, searchValue1, searchValue2);
        
        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'dd-mm-yyyy'
//            startDate: '-0m',
//            endDate: '+120d'
        });
        
        $(document).on('change', '#searchColumn', function () {
            let column = $(this).val();
            if(column == 'cancel_date') {
                $("#searchInput").html('<input class="form-control input-daterange-datepicker" id="searchValue" type="text">');
                $('.input-daterange-datepicker').daterangepicker({
                    opens: 'left',
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment().add('+1', 'days'),
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
            } else if(column == 'service_type') {
                let html = '<option value="hotel">Hotel</option><option value="car">Rental</option><option value="tour">Tour</option><option value="ticketing">Ticketing</option>';
                $("#searchInput").html('<select class="form-control" id="searchValue">'+ html +'</select>');
            } else if(column == 'refund_status') {
                let html = '';
                for (const key in status) {
                    html += '<option value="'+ status[key] +'">'+ status[key] +'</option>';
                }
                $("#searchInput").html('<select class="form-control" id="searchValue">'+ html +'</select>');
            } else {
                $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            }
        });
        
        $(document).on('click', '#search', function () {
            let searchText1 = $("#searchColumn").val();
            let searchText2 = $("#searchValue").val();
            if (searchText1 != '' && searchText2 != '') {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                getHotels(oTable, searchValue1, searchValue2);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#searchColumn").val('');
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#searchValue").val('');
            getHotels(oTable);
        });
        
        $(document).on('click', '#expotrtCSV', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('refund-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_refund_history'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "Refund_report_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });
        
        
        $(document).on('click', '.initiateRefund', function () {
            let Id = $(this).data('id');
            if(Id != "" && confirm('Are you sure want to re-initiate refund ?')) {
                $.ajax({
                    type: "POST",
                    url: "{{url('refund-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, request_type: 'reinitiate_refund_process'},
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
            "searching": false,
            "bServerSide": true,
            "bPaginate": true,
           "ajax": {
                "url": "{{ url('get-refund-history') }}",
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
                'aTargets': []
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
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
</script>

@endsection