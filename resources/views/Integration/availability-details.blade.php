@extends('layouts.app')

@section('title','Availability Details')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">MMT Integration</li>
                <li class="breadcrumb-item active">Availability Details</li>
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

                <div class="col-md-12">
                    <form class="row" onsubmit="event.preventDefault();" >
                        <input type="hidden" id="hotel_id" value="{{ !empty($MasterHotel) ? $MasterHotel->id : '' }}">
                        <div class="col-md-3"></div>
                        <div class="col-md-3">
                            <select class="form-control" id="customColumn">
                                <option value="">Select</option>
                                <option value="room_id">Room Name</option>
                                <option value="room_code">Room Code</option>
                                <option value="quantity">Quantity</option>
                                <option value="date">Date</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <span class="mr-sm-2" id="searchInput"><input type="text" id="searchValue" class="form-control"></span>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                            <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                        </div>
                    </form>
                </div><br><br><br>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Hotel Code</th>
                            <th>Hotel Name</th>
                            <th>Room Code</th>
                            <th>Room Name</th>
                            <th>Quantity</th>
                            <th>Date</th>
                            <th>Updated At</th>
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

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        let searchValue1 = {{ $hotel_code }};
        let searchValue2 = '';
        let searchValue3 = '';
        var oTable;
        getHotels(oTable, searchValue1, searchValue2, searchValue3);
        
        $(document).on('click', '#submitsearchText', function () {
            let searchText2 = $("#customColumn").val();
            let searchText3 = $("#searchValue").val();
            if ((searchText2 != '' && searchText3 != '')) {
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                getHotels(oTable, searchValue1, searchValue2, searchValue3);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
            $("#customColumn").val('');
            getHotels(oTable, searchValue1);
        });
        
        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();
            if(column == 'date') {
                $("#searchInput").html('<input class="form-control input-daterange-datepicker" id="searchValue" type="text">');
                $('.input-daterange-datepicker').daterangepicker({
                    autoApply: true,
                    startDate: moment(),
                    endDate: moment().add('+1', 'days'),
                    locale: {
                      format: 'DD MMM YYYY'
                    }
                });
            } else if (column == 'room_id') {
                let hotel_id = $("#hotel_id").val();
                if (hotel_id != '') {
                    $.ajax({
                        type: "POST",
                        url: "{{url('hotel-oprsn')}}",
                        headers: {
                            'X-CSRF-Token': '{{ csrf_token() }}',
                        },
                        data: {hotelId: hotel_id, request_type: 'get_hotel_rooms'},
                        success: function (data) {
                            $("#searchInput").html('<select class="form-control select2" id="searchValue">'+ data +'</select>');
                        }
                    });
                }
                    
            } else {
                $("#searchInput").html('<input type="text" id="searchValue" class="form-control">');
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
                "url": "{{ url('get-availability-details') }}",
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
                'aTargets': [1,3]
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