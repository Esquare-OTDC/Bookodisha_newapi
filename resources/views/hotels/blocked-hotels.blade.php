@extends('layouts.app')

@section('title','Blocked Hotels')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Hotels</li>
                <li class="breadcrumb-item active">Blocked Hotels</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <a href="{{url('block-hotel-room')}}" class="btn btn-info"><i class="fa fa-plus"></i> Block Hotel / Room</a>
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
                <div>
                    <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="delete_blocked_hotels">Delete</option>
                        </select>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                    </form>
                    <span class="pull-right"><a href="{{ url('hotel-room-block-data') }}" style="text-decoration: underline;">View Blocked Hotel Rooms</a></span>
                </div>
                <form class="form-inline pull-right">
                    <select id="hotel" class="form-control">
                        <option value="">Select Hotel</option>
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" readonly>
                    <button type="button" class="btn btn-sm btn-primary" id="search">Search</button>
                    <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                </form>
                <br><span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>
                                <div class="checkbox-fade">
                                    <label><input type="checkbox" value="checkAll" id="checkAll"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label>
                                </div>
                            </th>
                            <th>Hotel Name</th>
                            <th>Rooms</th>
                            <th>Date</th>
                            <th>Reason</th>
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

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';
        var oTable;
        getHotels(oTable, searchValue1, searchValue2);

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            opens: 'left',
            startDate: moment(),
            endDate: moment().add('+1', 'days'),
            locale: {
              format: 'DD-MM-YYYY'
            }
        });
        
        $(document).on('click', '#search', function () {
            let searchText1 = $("#hotel").val();
            let searchText2 = $("#check_date").val();
            if (searchText1 != '' || searchText2 != '') {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                getHotels(oTable, searchValue1, searchValue2);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#hotel").val('');
            $("#check_date").val('');
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
            
            if ($('.itemcheck:checked').length >= 1) {
                $("#changeStatus").show();
            }
            else {
                $("#changeStatus").hide();
            }
        });
        
        
        $(document).on('click', '.delete-data', function () {
            let Id = $(this).data('id');
            if(Id != "" && confirm('Are you sure want to delete')){
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, request_type: 'delete-blocked-hotel'},
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
            "bServerSide": true,
            "bPaginate": true,
           "ajax": {
                "url": "{{ url('get-blocked-hotels') }}",
                "type": "POST",
                "data": {
                    _token: "{{csrf_token()}}",
                    "searchValue1": searchValue1,
                    "searchValue2": searchValue2
                },
            },  
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [0,5]
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