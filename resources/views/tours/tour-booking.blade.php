@extends('layouts.app')

@section('title','Tour Booking Summary')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Tour</li>
                <li class="breadcrumb-item active">Booking Summary</li>
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
                <form method="post" class="form-inline" method>
                    @csrf
                    <select id="tour_id" name="tour_id" class="form-control">
                        <option value="">Select Tour</option>
                        @foreach ($Tour as $key => $value)
                            <option value="{{ $key }}" {{ ($tour_id == $key) ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date" style="width:25%;">
                    <button type="submit" class="btn btn-sm btn-primary" id="search">Search</button>
                </form>
                <br><br>
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>                            
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Date</th>
                            <th>Total Booked</th>
                            <th>Total Pending</th>
                            <th>Total Cancelled</th>
                        </thead>	
                        <tbody>
                            @foreach ($OrderData as $value)
                            <tr>
                                <td>{{ date("d M Y", strtotime($value['date'])) }}</td>
                                <td>@if ($value['completeQty'] > 0) <a href="{{ url('tour-orders?tourId='. $tour_id .'&date='. $value['date']) }}">{{ $value['completeQty'] }}</a> @else {{ $value['completeQty'] }} @endif</td>
                                <td>{{ $value['pendingQty'] }}</td>
                                <td>{{ $value['cancelQty'] }}</td>
                            </tr>
                            @endforeach
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
            startDate: moment(new Date('<?= $start_date ?>')),
            endDate: moment(new Date('<?= $end_date ?>')),
            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD MMM YYYY'
            }
        });
        
        $('#datatable-responsive').dataTable({
            "bProcessing": true,
            "searching": true,
            "fixedHeader": {
                header: true
            },
            "bPaginate": true,
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': []
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "order": [],
            "iDisplayLength": 10,
        });
        
        $(document).on('click', '.total-booking', function () {
            let carId = $(this).data('id');
            let bookDate = $(this).data('date');
            let url = '<?= url('rental-orders') ?>?vehicleId=' + carId + '&date=' + bookDate;
            window.open(url, 'window name', 'window settings');
            return false;
        });
    });
</script>

@endsection