@extends('layouts.app')

@section('title','Coupons')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">Coupons</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{url('add-coupon')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add Coupon</a>
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
                            <th>Service</th>
                            <th>Type</th>
                            <th>Coupon Name</th>
                            <th>Coupon Code</th>
                            <th>Amount(%)</th>
                            <th>Multi Item<br>Purchase</th>
                            <th>Min Order Amount</th>
                            <th>Use Type</th>
                            <th>Frequency/User</th>
                            <th>Frequency</th>
                            <th>Already Used</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </thead>	
                        <tbody>
                            <tr>
                                <td colspan="16" class="dataTables_empty">Loading data from server...</td>
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
        
        $(document).on('click', '.deleteCoupon', function () {
            if (confirm('Are you sure want to delete')) {
                var couponId = $(this).data('id');
                $.ajax({
                    type: "POST",
                    url: "{{url('review-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {couponId: couponId, request_type: "delete_coupon"},
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
                "url": "{{ url('get-coupon-details') }}",
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
                'aTargets': [15]
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