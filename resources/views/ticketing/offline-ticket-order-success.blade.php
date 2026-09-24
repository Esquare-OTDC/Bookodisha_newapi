@extends('layouts.app')

@section('title', 'Ticket Offline Order')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Order</li>
                <li class="breadcrumb-item active">Ticket offline order</li>
            </ol>
        </div>
    </div>
    
    @if(Session::has('success'))
        <p class="flashMessage1" style="color: rgb(30, 255, 0); text-align: center;">
            {{ Session::get('success') }}
            @php
                Session::forget('success');
            @endphp
        </p>
    @endif

    @if(Session::has('orderId'))
    @php
    $orderId = Session::get('orderId');
    Session::forget('orderId');
    @endphp
    @endif
    
    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Create Offline Order</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('create-offline-ticket-order') }}" id="offlineOrderForm" method="POST">
                    @csrf
                    <div class="col-md-12">
                        <div class="white-box hotel-section">
                            <h3 class="box-title">Ticket Details</h3><hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>URL</label><br>
                                        {{ $OrderMasterNew['offline_url'] . '( '. $OrderMasterNew['offline_link_expiry'] .' )' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <p>Booking ID - {{ $var1_arr['udf1'] }}</p>
                                        {{-- <p>Start Date - {{ $var1_arr['udf2'] }}</p> --}}
                                        <p>Txn ID - {{ $var1_arr['txnid'] }}</p>
                                        <p>Ticket - {{ $var1_arr['productinfo'] }}</p>
                                        <p>Customer Name - {{ $var1_arr['firstname'] }}</p>
                                        <p>Email - {{ $var1_arr['email'] }}</p>
                                        <p>Ph - {{ $var1_arr['phone'] }}</p>
                                        <p>Amount - {{ $var1_arr['amount'] }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="sightseenDiv">
                            </div>
                            <a href="{{ route('offline-ticket-order') }}"><button type="button" class="btn btn-primary" id="">Back</button></a>
                        </div>
                        {{-- <div class="white-box user-section" style="display:block;">
                            <h3 class="box-title">User Details</h3><hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Booking ID # </label><span class="required_field">*</span>
                                        <input type="text" class="form-control" name="booking_id" placeholder="YYYYMMDDT****" required>
                                        @if ($errors->has('booking_id'))
                                        <span class="text-danger">{{ $errors->first('booking_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Name</label><span class="required_field">*</span>
                                        <input type="text" class="form-control" name="customer_name" required>
                                        @if ($errors->has('customer_name'))
                                        <span class="text-danger">{{ $errors->first('customer_name') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Email</label><span class="required_field">*</span>
                                        <input type="email" class="form-control" name="customer_email" required>
                                        @if ($errors->has('customer_email'))
                                        <span class="text-danger">{{ $errors->first('customer_email') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Phone</label><span class="required_field">*</span>
                                        <input type="text" class="form-control numvalidate" name="customer_phone" required>
                                        @if ($errors->has('customer_phone'))
                                        <span class="text-danger">{{ $errors->first('customer_phone') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label>Price</label><span class="required_field">*</span>
                                        <input type="number" class="form-control" min="0" name="total_order_price" required>
                                        @if ($errors->has('total_order_price'))
                                        <span class="text-danger">{{ $errors->first('total_order_price') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary" id="bookNow">Book Now</button>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                </form>
            </div>                
        </div>
    </div>
</div>

<style>
    .icheck-list li label {
        display: inline;
        color: black;
    }
    .icheck-list {
        padding-right: 0px;
    }
    .icheck-list li {
        padding-bottom: 8px;
    }
    .image-uploader {
        min-height: 20rem;
    }
    .room-price-section {
        text-align: right;
    }
    .price-section {
        line-height: 30px;
        font-size: initial;
    }
    .page-wrapper .container-fluid {
        color: #000;
    }
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        
    });
    
</script>

@endsection