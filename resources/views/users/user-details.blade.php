@extends('layouts.app')

@section('title','User Details')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">User Details</li>
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
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span><br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Photo</th>
                            <th>Birth Date</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Country</th>
                            <th>Zip Code</th>
                            <th>Join Date</th>
                            <th>Status</th>
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

<script type="text/javascript">
    $(document).ready(function () {
        var exportQuery = '';
        var oTable = $('#datatable-responsive').dataTable({
            "bProcessing": true,
            "fixedHeader": {
                header: true
            },
            "bServerSide": true,
            "bPaginate": true,
           "ajax": {
                "url": "{{ url('get-user-details') }}",
                "type": "POST",
                "data":{ _token: "{{csrf_token()}}"}
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [12]
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
        
        $(document).on('click', '.deleteUser', function () {
            if (confirm('Are you sure want to delete')) {
                var userId = $(this).data('id');
                $.ajax({
                    type: "POST",
                    url: "{{url('user-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {userId: userId, request_type: "delete_user"},
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
            var userId = $(this).data('id');
            var status = $(this).data('status');
            var message = (status == 1) ? 'Are you sure want to deactivate this user' : 'Are you sure want to activate this user';    
            
            if (confirm(message)) {
                $.ajax({
                    type: "POST",
                    url: "{{url('user-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {userId: userId, status: status, request_type: "userStatusModify"},
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

        $(document).on('click', '#expotrtCSV', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('user-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_user_details'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "User_report_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
        });
        
    });
</script>

@endsection