@extends('layouts.app')

@section('title','Hotel Estimate')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Hotel Estimate</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <button class="btn btn-info" id="export">Export</button>&nbsp;
            <a href="{{url('create-hotel-estimate')}}" class="btn btn-info"><i class="fa fa-plus"></i> Create New Estimate</a>
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
                <div class="col-md-4">
                    <!-- <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Move to draft</option>
                            <option value="show_price">Show Price</option>
                            <option value="hide_price">Hide Price</option>
                        </select>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                    </form> -->
                </div>
                <div class="col-md-8">
                    <!-- <form class="form-inline" onsubmit="event.preventDefault();" style="float: right;">
                        @if(Auth::user()->role == 1)
                        <select class="form-control select2" id="venderId">
                            <option value="">Select Vendor</option>
                            @foreach ($Vendors as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @endif
                        <select class="form-control" id="customColumn">
                            <option value="">Select</option>
                            <option value="name">Name</option>
                            <option value="place">Location</option>
                        </select>
                        <span class="mr-sm-2" id="searchInput"><input type="text" id="searchValue" class="form-control" placeholder="Search by name"></span>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                        <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                    </form> -->
                </div><br><br><br>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <!-- <th>
                                <div class="checkbox-fade">
                                    <label><input type="checkbox" value="checkAll" id="checkAll"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label>
                                </div>
                            </th> -->
                            <th>Estimation Id</th>
                            <th>Hotel Name</th>
                            <th>Customer Details</th>
                            <th>Estimation Date</th>
                            <th>Rooms</th>
                            <th>Check In/Out</th>
                            <th>Total Amount</th>
                            <th>View Estimation</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="8" class="dataTables_empty">Loading data from server...</td>
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
        var aTarget = Number("{{ (Auth::user()->access_type == 'superadmin') ? 7 : 6 }}");
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        var oTable;
        var exportQuery = '';
        getHotels(oTable, searchValue1, searchValue2, searchValue3);
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText1 = $("#venderId").val();
            let searchText2 = $("#customColumn").val();
            let searchText3 = $("#searchValue").val();
            if (searchText1 != '' || (searchText2 != '' && searchText3 != '')) {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                getHotels(oTable, searchValue1, searchValue2, searchValue3);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#venderId").val('');
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#customColumn").val('');
            getHotels(oTable);
        });
        
        $(document).on('change', '#checkAll', function () {
            $('.itemcheck').prop('checked', $(this).prop("checked"));
            if ($('.itemcheck:checked').length >= 1) {
                $("#changeStatus").show();
            }
            else {
                $("#changeStatus").hide();
            }
        });
        
        $(document).on('change', '.itemcheck', function () {
            if ($('.itemcheck:checked').length == $('.itemcheck').length) {
                $("#checkAll").prop('checked', true);
            } else {
                $("#checkAll").prop('checked', false);
            }
        });
        
        $(document).on('click', '#submitBulkOperation', function () {
            let idArray = [];
            $("input:checkbox[class=itemcheck]:checked").each(function(){
                idArray.push($(this).val());
            });
            if(idArray.length == 0) {
                alert('No items selected!');
                return false;
            }
            let action = $("#bulkOperation").val();
            if(action != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {IdArray: JSON.stringify(idArray), request_type: action},
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
            } else {
                alert('please select an action!');
            }
        });
        
        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();
            if(column == 'place') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {request_type: 'get_hotel_city'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            $("#searchInput").html('<select class="form-control select2" id="searchValue"><option value="">Select</option></select>');
                        } else {
                            let cityArray = Object.keys(responce.data);
                            $html = '<option value="">Select</option>';
                            for (let i = 0; i < cityArray.length; i++) {
                                $html += '<option value="'+ cityArray[i] +'">'+ cityArray[i] +'</option>';
                            }
                            $("#searchInput").html('<select class="form-control select2" id="searchValue">'+ $html +'</select>');
                            $("#searchValue").select2();
                        }
                    }
                });
            } else {
                $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            }
        });
        
        $(document).on('click', '#export', function () {
            if (exportQuery != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_hotel_estimates'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "Hotel_order_summary_report_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
            
        });

        $(document).on('click', '.viewEstimate', function () {
            let orderID = $(this).data('id');
            if (orderID != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {orderID: orderID, request_type: "get_estimate_html"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            let url = responce.data;
                            window.open(url, 'window name', 'window settings');
                            return false;
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
                    "url": "{{ url('get-hotel-estimates') }}",
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
                    'aTargets': [7]
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