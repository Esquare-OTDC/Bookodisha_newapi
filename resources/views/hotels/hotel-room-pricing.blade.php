@extends('layouts.app')

@section('title','Manage Hotel Pricing')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Hotel</a></li>
                <li class="breadcrumb-item active">Hotel Room Pricing</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{url('manage-hotel-pricing')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add Room Pricing</a>
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
                    <table id="datatable-responsive" class="display nowrap table table-hover table-striped table-bordered">
                        <thead>
                            <th>Hotel Name</th>
                            <th>Room Name</th>
                            <th>Price Plan</th>
                            <th>Discount</th>
                            <th>Date</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="6" class="dataTables_empty">Loading data from server...</td>
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
        var oTable;
        getHotelpricings(oTable, searchValue1, searchValue2);     
        
        $(document).on('click', '.delete-data', function () {
            let plan = $(this).data('plan');
            if(plan != "" && confirm('Are you sure want to delete')){
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {plan: plan, request_type: 'delete-hotel-pricing'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            getHotelpricings(oTable, searchValue1, searchValue2);
                        }
                    }
                });
            } 
        });        
        
    });
    function getHotelpricings(oTable, searchValue1 = '', searchValue2 = '') {
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
                "url": "{{ url('get-hotel-pricing') }}",
                "type": "POST",
                "data": {
                    _token: "{{csrf_token()}}",
                    "searchValue1": searchValue1,
                    "searchValue2": searchValue2
                },
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [5]
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
    }
</script>

@endsection