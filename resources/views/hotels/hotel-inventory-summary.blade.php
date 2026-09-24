@extends('layouts.app')

@section('title','Hotel Inventory')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Hotels</li>
                <li class="breadcrumb-item active">Hotel Inventory</li>
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
                    <select id="hotel_id" class="form-control">
                        <option value="">Select Hotel</option>
                        @foreach ($MasterHotel as $key => $value)
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
                            <th>Initial Qty</th>
                            <th>Total Available</th>
                            <th>Total Booked</th>
                            <th>Total Blocked</th>
                            <th>Total Cancelled</th>
                            
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

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment().add('+1', 'days'),
//            minDate: moment(),
            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD MMM YYYY'
            }
        });
        
        
        let searchValue1 = '';
        let searchValue2 = '';
        var oTable;
//        getHotels(oTable, searchValue1, searchValue2);
        
        $(document).on('click', '#search', function () {
            let HotelId = $("#hotel_id").val();
            let date = $("#check_date").val();
            if (HotelId != '' && date != '') {
                searchValue1 = HotelId;
                searchValue2 = date;
                getHotels(oTable, searchValue1, searchValue2);
            }
        });
        
        $(document).on('click', '.total-booking', function () {
            let roomId = $(this).data('id');
            let url = '<?= url('hotel-orders') ?>?roomId=' + roomId + '&date=' + searchValue2;
            window.open(url, 'window name', 'window settings');
            return false;
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
                "url": "{{ url('get-inventory-summary') }}",
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