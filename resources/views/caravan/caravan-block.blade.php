@extends('layouts.app')

@section('title','Block Caravan')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Rental</li>
                <li class="breadcrumb-item active">Block Caravan</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <a href="{{url('block-rental-caravan')}}" class="btn btn-info"><i class="fa fa-plus"></i> Block Caravan</a>
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
                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>
                <br><br>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Caravan Name</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Date</th>
                        </thead>	
                        <tbody>
                            @if($blockedCaravans->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center">No data found</td>
                                </tr>
                            @else
                                @foreach($blockedCaravans as $item)
                                    <tr>
                                        <td>{{ $item->vehicle_name }}</td>
                                        <td>{{ $item->quantity ?? '-' }}</td>
                                        <td>{{ $item->block_reason }}</td>
                                        <td>{{ date('d-m-Y', strtotime($item->block_date)) }}</td>
                                    </tr>
                                @endforeach
                            @endif
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

        $('#datatable-responsive').DataTable({
            processing: true,
            paging: true,
            fixedHeader: true,
            order: [],
            pageLength: 10,
            lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]]
        });

    });
</script>

@endsection