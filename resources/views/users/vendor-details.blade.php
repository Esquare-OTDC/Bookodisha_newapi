@extends('layouts.app')

@section('title','Vendor Details')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">Vendor Details</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{url('vendor-add')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add Vendor</a>
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
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span><br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Business Name</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Payment Merchand Id</th>
                            <th>Status</th>
                            <th>Action</th>
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

<script type="text/javascript">
    $(document).ready(function () {
        var oTable = $('#datatable-responsive').dataTable({
            "bProcessing": true,
            "fixedHeader": {
                header: true
            },
            "bServerSide": true,
            "bPaginate": true,
           "ajax": {
                "url": "{{ url('get-vendor-details') }}",
                "type": "POST",
                "data":{ _token: "{{csrf_token()}}"}
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [7]
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "order": [],
            "iDisplayLength": 10,
            "drawCallback": function () {
                var totalrecords = oTable.fnSettings().fnRecordsTotal();
                $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
                $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
                $("#datatable-responsive_info").hide();
            }
        });
        
        $(document).on('click', '.deleteVendor', function () {
            if (confirm('Are you sure want to delete')) {
                var vendorId = $(this).data('id');
                $.ajax({
                    type: "POST",
                    url: "{{url('vendor-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, request_type: "delete_vendor"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                            oTable.fnFilter('');
                        } else {
                            alert(responce.message);
                            oTable.fnFilter('');
                        }
                    }
                });
            }
        });
        
        $(document).on('click', '.statusModify', function () {
            var vendorId = $(this).data('id');
            var status = $(this).data('status');
            var message = (status == 1) ? 'Are you sure want to deactivate this vendor' : 'Are you sure want to activate this vendor';    
            
            if (confirm(message)) {
                $.ajax({
                    type: "POST",
                    url: "{{url('vendor-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, status: status, request_type: "vendorStatusModify"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            oTable.fnFilter('');
                        }
                    }
                });
            }
        });
        
    });
</script>

@endsection