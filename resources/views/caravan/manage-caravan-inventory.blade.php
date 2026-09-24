@extends('layouts.app')

@section('title','Manage Caravan Inventory')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Caravan</li>
                <li class="breadcrumb-item active">Manage Caravan Inventory</li>
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
                <form class="form-inline">
                    <select id="hotel" class="form-control">
                        <option value="">Select Vehicle</option>
                        @foreach ($MasterCaravan as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="button" class="btn btn-sm btn-primary" id="search">Search</button>
                </form>
                <br><br>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Date</th>
                            <th>Vehicle Name</th>
                            <th>Initial Qty</th>
                            <th>Total Available</th>
                            <th>Total Booked</th>
                            <th>Total Blocked</th>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="dataTables_empty">No data available in table</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changeQtyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Manage Inventory</h4>
            </div>
            <div class="modal-body">
                <form onsubmit="event.preventDefault();">
                    <input type="hidden" id="mid">
                    <input type="hidden" id="availQty">
                    <div class="form-group">
                        <label for="recipient-name" class="display-modal">Current Quantity :</label> &nbsp;<span id="currentQty" class="display-modal"></span>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="display-modal"> Choose to Increase / Decrease</label>
                        <select class="form-control" id="changeType">
                            <option value="">Select</option>
                            <option value="increase">Increase</option>
                            <option value="decrease">Decrease</option>
                        </select>
                    </div>
                    <div class="form-group display-modal">
                        <input type="number" class="form-control" id="changeQty" min="0">
                        <span id="qtyMessage" class="text-danger"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" id="submitData" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changeBlockModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Manage Blocked Inventory</h4>
            </div>
            <div class="modal-body">
                <form onsubmit="event.preventDefault();">
                    <input type="hidden" id="mbid">
                    <div class="form-group">
                        <label class="display-modal">Current Blocked Quantity :</label> &nbsp;<span id="curBlockQty" class="display-modal"></span>
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="display-modal"> Enter no. of rooms you want to release</label>
                        <input type="number" class="form-control" id="releaseQty" min="1">
                        <span id="qtyBlockMessage" class="text-danger"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" id="submitBlockData" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<style type="text/css">
    .display-modal {
        color: #000;
        font-weight: bold;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment().add('+1', 'days'),
            minDate: moment(),
//            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD-MM-YYYY'
            }
        });

//        $(document).on('change', '#hotel', function () {
//            let hotelId = $(this).val();
//            if (hotelId != '') {
//                $.ajax({
//                    type: "POST",
//                    url: "{{url('hotel-oprsn')}}",
//                    headers: {
//                        'X-CSRF-Token': '{{ csrf_token() }}',
//                    },
//                    data: {hotelId: hotelId, request_type: "get_hotel_rooms"},
//                    success: function (data) {
//                        $('#room').html('<option value="">Select Room</option>'+ data);
//                    }
//                });
//            }
//        });

        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';
        var oTable;
//        getHotels(oTable, searchValue1, searchValue2, searchValue3);

        $(document).on('click', '#search', function () {
            let HotelId = $("#hotel").val();
            let date = $("#check_date").val();
            if (HotelId != '' && date != '') {
                searchValue1 = HotelId;
                searchValue2 = date;
                getHotels(oTable, searchValue1, searchValue2);
            }
        });

        $(document).on('click', '.change-qty', function () {
            let Id = $(this).data('id');
            let availableQty = $(this).data('available');
            let quantity = $(this).data('qty');
            $("#mid").val(Id);
            $("#availQty").val(availableQty);
            $("#currentQty").html(quantity);
            $("#changeQty").val('');
            $("#qtyMessage").html('');
        });

        $(document).on('click', '#submitData', function () {
            let inventoryId = $("#mid").val();
            let changeType = $("#changeType").val();
            if (changeType == '') {
                alert('Please choose you want to increase or decrease.');
                return;
            }
            let changeQty = $("#changeQty").val();
            if (changeQty != '' && changeType != '') {
                $.ajax({
                    type: "POST",
                    url: "{{route('caravanOprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {inventoryId: inventoryId,changeType: changeType, changeQty: changeQty, request_type: "change_master_inventory"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            $('#changeQtyModal').modal('toggle');
                            getHotels(oTable, searchValue1, searchValue2);
                        }
                    }
                });
            } else {
                alert("Please enter quantity");
            }

        });

        $(document).on('change', '#changeType', function () {
            let changeType = $(this).val();
            if (changeType == 'decrease') {
                let maxVal = $("#availQty").val();
                $("#changeQty").attr('max', maxVal);
                $("#qtyMessage").html('Maximum '+  maxVal +' number of vehicles can be decreased.');
            } else {
                $("#changeQty").removeAttr('max');
                $("#qtyMessage").html('');
            }
        });

        $(document).on('click', '.release-qty', function () {
            let Id = $(this).data('id');
            let blockQty = $(this).data('blocked');
            $("#mbid").val(Id);
            $("#curBlockQty").html(blockQty);
            $("#releaseQty").attr('max', blockQty);
            $("#releaseQty").val(blockQty);
            $("#qtyBlockMessage").html('');
        });

        $(document).on('click', '#submitBlockData', function () {
            let inventoryId = $("#mbid").val();
            let releaseQty = $("#releaseQty").val();
            let maxQty = parseInt($("#releaseQty").attr('max'));
            if (releaseQty > maxQty) {
                $("#qtyBlockMessage").html('Maximum '+ maxQty +' number of vehicles can be released.');
                return false;
            }
            else if (releaseQty < 1) {
                $("#qtyBlockMessage").html('Minimum 1 vehicle can be released.');
                return false;
            }
            if (releaseQty != '' && releaseQty > 0 && releaseQty <= maxQty) {
                $("#qtyBlockMessage").html('');
                $.ajax({
                    type: "POST",
                    url: "{{route('caravanOprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {inventoryId: inventoryId, releaseQty: releaseQty, request_type: "change_block_inventory"},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            $('#changeBlockModal').modal('toggle');
                            getHotels(oTable, searchValue1, searchValue2, searchValue3);
                        }
                    }
                });
            } else {
                alert("Please enter release quantity");
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
                "url": "{{ route('get-caravan-master-inventory') }}",
                "type": "POST",
                "data": {
                    _token: "{{csrf_token()}}",
                    "searchValue1": searchValue1,
                    "searchValue2": searchValue2,
                },
            },
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [1]
            }],
            "aLengthMenu": [[50, 100, 150, 200], [50, 100, 150, 200]],
            "order": [],
            "iDisplayLength": 50,
            "drawCallback": function (settings) {
                var totalrecords = oTable.fnSettings().fnRecordsTotal();
                $('.counttotalrecords').html('<i class="icon-ok"></i>Total Records ' + totalrecords);
                $("span#spandatatable-responsive_info").html('<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text());
                $("#datatable-responsive_info").hide();
            }
        });
    }
</script>

@endsection
