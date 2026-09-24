@extends('layouts.plane')

@section('title','Booking QR')

@section('content')

<div class="container-fluid">
    <!-- <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Booking Voucher</li>
            </ol>
        </div>
        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button class="btn btn-primary" id="printInvoice">Print</button>
        </div>
    </div> -->
    
    
    
    <!-- <div class="row">
        <div class="col-sm-12">
            <div class="white-box"> -->
            

            @if (!empty($OrderMaster))
            <div style="margin: 100px 0  0 450px;">
                <img src="{{ $OrderMaster->qr_base64 }}">
                <div>
                {{ $OrderMaster->service_name }} <br>
                Booking Id: {{ $OrderMaster->invoice_id }} <br>
                No of tickets: {{ $OrderMaster->total_guests }} <br>
                {{ $OrderMaster->gate_number }}
                </div>
            </div>
            
            @endif
            <!-- </div>
        </div>
    </div> -->
</div>

@endsection
