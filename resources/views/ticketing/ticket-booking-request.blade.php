@extends('layouts.app')

@section('title','Booking Requests')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Orders</li>
                <li class="breadcrumb-item active">Booking Requests</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportData" class="btn btn-info">Export</button>
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
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Application No</th>
                            <th>Service Name</th>
                            <th>Customer Details</th>
                            <th>Guest Data</th>
                            <th>Customer Address</th>
                            <th>Type of<br>Experience</th>
                            <th>Experience Description</th>
                            <th>Publication</th>
                            <th>Social Media Handle</th>
                            <th>Social Media URL</th>
                            <th>Identity Type</th>
                            <th>Identity Files</th>
                            <th>Approval Status</th>
                            <th>Request Date</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="14" class="dataTables_empty">Loading data from server...</td>
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
    $(document).ready(function () {
        var aTarget = Number("{{ (Auth::user()->access_type == 'superadmin') ? 8 : 7 }}");
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        var lexportQuery = '';
        var oTable;
        getHotels(oTable, searchValue1, searchValue2, searchValue3);
        
        $(document).on('click', '#exportData', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('ticket-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_ticket_booking_request'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "Ticketing_booking_request_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });
        
        $(document).on('click', '.approveBooking', function () {
            let bookId = $(this).data('id');
            if(bookId != "" && confirm('Are you sure want to approve')) {
                $.ajax({
                    type: "POST",
                    url: "{{url('ticket-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: bookId, request_type: "approve_ticket_booking"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotels(oTable, searchValue1, searchValue2, searchValue3);
                        }
                    }
                });
            }
        });
        
        function getHotels(oTable, searchValue1 = '', searchValue2 = '', searchValue3 = '') {
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
                    "url": "{{ url('get-ticket-booking-request') }}",
                    "type": "POST",
                    "data": {
                        _token: "{{csrf_token()}}",
                        "searchValue1": searchValue1,
                        "searchValue2": searchValue2,
                        "searchValue3": searchValue3
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
    });        
</script>

@endsection