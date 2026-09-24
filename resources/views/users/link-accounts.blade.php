@extends('layouts.app')

@section('title','Link Accounts')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">Link Accounts</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{url('account-add')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add Account</a>
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
                <div class="col-md-12">
                    <form class="row" onsubmit="event.preventDefault();" style="//float: right;">
                        
                        <div class="col-md-3">
                        <select class="form-control" id="venderId">
                            <option value="">Select Vendor</option>
                            @foreach ($Vendors as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control ml-5" id="service_type">
                                <option value="">Select Property Type</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control ml-5 select2" id="service_id">
                                <option value="">Select Property</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                        <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                        </div>
                    </form>
                </div>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span><br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Vendor</th>
                            <th>Property Type</th>
                            <th>Property Name</th>
                            <th>HDFC MID</th>
                            <th>HDFC Key</th>
                            <th>HDFC Salt</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="7" class="dataTables_empty">Loading data from server...</td>
                            </tr>
                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        var oTable;
        
        getAccounts(oTable, searchValue1, searchValue2, searchValue3);
//        var oTable = $('#datatable-responsive').dataTable({
//            "bProcessing": true,
//            "fixedHeader": {
//                header: true
//            },
//            "bServerSide": true,
//            "bPaginate": true,
//           "ajax": {
//                "url": "{{ url('get-accounts') }}",
//                "type": "POST",
//                "data":{ _token: "{{csrf_token()}}"}
//            },  
//            "aoColumnDefs": [{
//                'bSortable': false,
//                'aTargets': [6]
//            }],
//            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
//            "order": [],
//            "iDisplayLength": 10,
//            "drawCallback": function () {
//                var totalrecords = oTable.fnSettings().fnRecordsTotal();
//                $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
//                $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
//                $("#datatable-responsive_info").hide();
//            }
//        });
        
        $(document).on('click', '.deleteAccount', function () {
            if (confirm('Are you sure want to delete')) {
                var accountId = $(this).data('id');
                $.ajax({
                    type: "POST",
                    url: "{{url('account-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {accountId: accountId, request_type: "delete_account"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        alert(responce.message);
                        getAccounts(oTable, searchValue1, searchValue2, searchValue3);
                    }
                });
            }
        });
        
        $(document).on('change', '#venderId', function() {
            $("#select2-chosen-1").html('Select Property');
            $('#service_type').html('<option value="">Select Property Type</option>');
            $('#service_id').html('<option value="">Select Property</option>');
            let vendorId = $(this).val();
            if (vendorId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('account-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, request_type: "get_vendor_services"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                           $('#service_type').html(responce.data);
                        }
                    }
                });
            }
        });
        
        $(document).on('change', '#service_type', function() {
            $("#select2-chosen-1").html('Select Property');
            $('#service_id').html('<option value="">Select Property</option>');
            let vendorId = $('#venderId').val();
            let prop_type = $(this).val();
            if (vendorId != '' && prop_type != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('account-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, service_type: prop_type, request_type: "get_vendor_property"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                           $('#service_id').html(responce.data);
                        }
                    }
                });
            }
        });
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText1 = $("#venderId").val();
            let searchText2 = $("#service_type").val();
            let searchText3 = $("#service_id").val();
            if (searchText1 != '' || (searchText2 != '' && searchText3 != '')) {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                getAccounts(oTable, searchValue1, searchValue2, searchValue3);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Property');
            $("#venderId").val('');
            $('#service_type').html('<option value="">Select Property Type</option>');
            $('#service_id').html('<option value="">Select Property</option>');
            
            getHotels(oTable, searchValue1, searchValue2 = '',searchValue3 = '',searchValue4 = '', searchValue5, searchValue6, searchValue7, searchValue8);
        });
        
        function getAccounts(oTable, searchValue1 = '', searchValue2 = '', searchValue3 = '') {
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
                            "url": "{{ url('get-accounts') }}",
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
                            'aTargets': [6]
                    }],
                    "aLengthMenu": [
                            [10, 20, 50, 100],
                            [10, 20, 50, 100]
                    ],
                    "order": [],
                    "iDisplayLength": 10,
                    "drawCallback": function (settings) {
                            var totalrecords = oTable.fnSettings().fnRecordsTotal();
                            $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
                            $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
                            $("#datatable-responsive_info").hide();
                    }
            });
        }        
    });
</script>

@endsection