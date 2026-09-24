@extends('layouts.app')

@section('title','Rental MIS Report')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Rental</li>
                <li class="breadcrumb-item active">Rental MIS Report</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportData" class="btn btn-info">Export</button>
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

                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Date</th>
                            @foreach($MasterCar as $vehicles)
                            <th>{{ $vehicles }}</th>
                            @endforeach
                        </thead>	
                        <tbody>
                            @foreach($MisData as $key => $val)
                            <tr>
                                <td>{{ date("d-M-Y", strtotime($key)) }}</td>
                                @foreach($MisData[$key] as $qtys)
                                <td>{{ $qtys }}</td>
                                @endforeach
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
    
</style>

<script type="text/javascript">
    $(document).ready(function () {
        
        $(document).on('click', '#exportData', function () {
            $.ajax({
                type: "POST",
                url: "{{url('car-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {request_type: "export_rental_mis_report"},
                success: function (data) {
                    var downloadLink = document.createElement("a");
                    var blob = new Blob(["\ufeff", data]);
                    var url = URL.createObjectURL(blob);
                    downloadLink.href = url;
                    downloadLink.download = "Rental_mis_report_"+ new Date().getTime() +".csv";  //Name the file here
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            });
        });
    });
    
</script>

@endsection