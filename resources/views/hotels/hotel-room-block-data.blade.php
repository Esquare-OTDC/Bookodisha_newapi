@extends('layouts.app')

@section('title','Block Hotel / Room')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Hotels</li>
                <li class="breadcrumb-item active">Block Hotel Rooms</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button class="btn btn-info" id="expotrtCSV">Export</button>
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
                            <option value="mark_block_booked">Mark Booked</option>
                            <option value="mark_block_released">Mark Released</option>
                        </select>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                    </form>
                    <span class="pull-right"><a href="{{ url('blocked-hotels') }}" style="text-decoration: underline;">View Blocked Hotels</a></span>
                </div>
                
                <form class="form-inline pull-right">
                    <select id="hotel" class="form-control">
                        <option value="">Select Hotel</option>
                        @foreach ($MasterHotel as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    <select id="room" class="form-control">
                        <option value="">Select Room</option>
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" >
                    <!-- <input type="text" class="form-control check-quantity" name="block_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" required> -->
                    <button type="button" class="btn btn-sm btn-primary" id="search">Search</button>
                </form><br>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>
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
                            <th>Room Name</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Date</th>
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

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    var exportQuery = '';
    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        
        var oTable;
        getHotels(oTable, searchValue1, searchValue2, searchValue3);
        
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            opens: 'left',
            startDate: moment(),
            endDate: moment().add('+1', 'days'),
            locale: {
              format: 'DD-MM-YYYY'
            }
        });
        
        $(document).on('change', '#hotel', function () {
            let hotelId = $(this).val();
            if (hotelId != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {hotelId: hotelId, request_type: "get_hotel_rooms_active"},
                    success: function (data) {
                        $('#room').html('<option value="">Select Room</option>'+ data);
                    }
                });
            }
        });
        
        $(document).on('click', '#search', function () {
            let searchText1 = $("#hotel").val();
            let searchText2 = $("#room").val();
            let searchText3 = $("#check_date").val();
            if (searchText1 != '' || searchText2 != '' || searchText3 != '') {
                searchValue1 = searchText1;
                searchValue2 = searchText2;
                searchValue3 = searchText3;
                getHotels(oTable, searchValue1, searchValue2, searchValue3);
            }
        });
        
        $(document).on('click', '#resetSession', function () {
            $("#select2-chosen-1").html('Select Vendor');
            $("#venderId").val('');
            $("#searchValue").val('');
            getHotels(oTable);
        });
        
        $(document).on('click', '#expotrtCSV', function () {
            if (exportQuery != "") {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {exportQuery: exportQuery, request_type: 'export_block_data_csv'},
                    success: function (data) {
                        var downloadLink = document.createElement("a");
                        var blob = new Blob(["\ufeff", data]);
                        var url = URL.createObjectURL(blob);
                        downloadLink.href = url;
                        downloadLink.download = "Hotel_block_report_"+ new Date().getTime() +".csv";  //Name the file here
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    }
                });
            }
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
        
        
        $(document).on('click', '.modify-status', function () {
            let Id = $(this).data('id');
            let status =  $(this).data('status');
            let statText = (status == '2') ? 'mark as booked' : 'mark as released';
            if(Id != "" && confirm('Are you sure want to '+ statText)) {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, status: status, request_type: 'modify-block-room-status'},
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

        $(document).on('click', '.delete-data', function () {
            let Id = $(this).data('id');
            if(Id != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: Id, request_type: 'delete-blocked-room'},
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
                "url": "{{ url('get-block-data') }}",
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
                'aTargets': [0,6]
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
</script>

@endsection