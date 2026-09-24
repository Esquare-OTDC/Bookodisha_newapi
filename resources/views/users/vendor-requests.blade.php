@extends('layouts.app')

@section('title','Vendor Requests')

@section('content')
<link href="{{ asset('plugins/components/owl.carousel/owl.carousel.min.css') }}" rel="stylesheet">
<link href="{{ asset('plugins/components/owl.carousel/owl.theme.default.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">Vendor Requests</li>
            </ol>
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
                            <th>Enterprise Name</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Designation</th>
                            <th>Address</th>
                            <th>GST Details</th>
                            <th>Services</th>
                            <th>Property Images</th>
                            <th>Status</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="11" class="dataTables_empty">Loading data from server...</td>
                            </tr>
                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="modalHead">Services Providing</h4>
            </div>
            <div class="modal-body" id="formContent">
                
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="panel panel-default">
                    <div class="panel-wrapper p-b-10 collapse in">
                        <div id="owl-demo" class="owl-carousel owl-theme">
                            <!--<div class="item"><img src="../plugins/images/heading-bg/slide2.jpg" alt="Owl Image"></div>-->                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .carousel-item img {
        width: 100%;
    }
</style>

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
                "url": "{{ url('get-vendor-requests') }}",
                "type": "POST",
                "data":{ _token: "{{csrf_token()}}"}
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [10]
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
        
        $(document).on('click', '.banner-content', function () {
            $("#modalHead").html('Services Providing');
            $("#formContent").html('<p>'+ $(this).data('content') +'</p>');
        });
        
        $(document).on('click', '.view-image', function () {
            var vendorId = $(this).data('id');
            if (vendorId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('vendor-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, request_type: "get_property_images"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            console.log(responce.data);
                            let images = responce.data;
                            let html = '';
                            for (let i = 0; i < images.length; i++) {
                                html += '<div class="item"><img src="'+ images[i] +'" style="width: 100%;"></div>';
                            }
                            $("#owl-demo").html(html);
                            $("#imageModal").modal();
                        }
                    }
                });
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
                    data: {vendorId: vendorId, request_type: "delete_vendor_request"},
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
        
        $(document).on('click', '.approveVendor', function () {
            var vendorId = $(this).data('id');
            if (vendorId != '') {
                $("#formContent").html('<form id="vendorForm"><input type="hidden" name="id" id="vendorId" value="'+ vendorId +'"><div class="form-group"><label>Services</label><div class="input-group"><div class="row">@foreach ($Services as $service)<div class="col-md-4"><input type="checkbox" class="check" id="{{ $service['id'] }}" name="services[]" value="{{ $service['slug'] }}" data-checkbox="icheckbox_flat-blue"><label for="{{ $service['id'] }}">{{ $service['name'] }}</label></div>@endforeach</div></div></div><div class="form-group"><label>Admin Commission (in %)</label><input name="admin_commission" id="admin_commission" class="form-control decimalvalidate" autocomplete="off" required></div><div class="form-group"><label>Password</label><input type="password" name="password" id="password" class="form-control" autocomplete="off" required></div><button type="button" class="btn btn-primary" id="submitApprove">Approve</button> <button type="button" data-dismiss="modal" class="btn btn-secondary">Cancel</button></form>');
                $("#modalHead").html ('Approve Vendor Request');
                $("#viewModal").modal();
            }
        });        
                
        $(document).on('click', '#submitApprove', function() {
            let services = [];
            $('input[name="services[]"]:checked').each(function() {
                services.push(this.value);
            });
            if (services.length == 0) {
                alert('Please select atleast one service.')
                return;
            }
            let commission = $("#admin_commission").val();
            if (commission == '') {
                alert('Please enter commission.');
                return;
            }
            let password = $("#password").val();
            if (password == '') {
                alert('Please enter password.');
                return;
            }
            let vendorId = $("#vendorId").val();
            if (services.length > 0 && commission != '' && password != '' && vendorId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('vendor-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {vendorId: vendorId, services: JSON.stringify(services), commission: commission, password: password, request_type: "approve_vendor_request"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        alert(responce.message);
                        oTable.fnFilter('');
                        $('#viewModal').modal('toggle');
                    }
                });
            }
        });        
    });
</script>

@endsection