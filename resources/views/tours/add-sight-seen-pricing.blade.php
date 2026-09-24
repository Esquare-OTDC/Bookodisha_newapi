@extends('layouts.app')

@section('title','Add Sight Seen Pricing')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Add Sight Seen Price</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">

                        @if(Session::has('success'))
                        <p class="flash" style="color: red; text-align: center;">
                            {{ Session::get('success') }}
                            @php
                            Session::forget('success');
                            @endphp
                        </p>
                        @endif

                        <form action="{{ route('add-sight-seen-pricing-request') }}"  method="POST" id="availabilityForm">
                            @csrf
                            <input type="hidden" name="tour_name" id="tour_name">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Hotel</label><span class="required_field">*</span>
                                            <select class="form-control" id="tour_id" name="tour_id" required value="{{ old('tour_id') }}">
                                                <option value="">Select Tour</option>
                                                @foreach ($Tour as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('tour_id'))
                                            <span class="text-danger">{{ $errors->first('tour_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Price plan</label><span class="required_field">*</span>
                                            <input class="form-control" id="price_plan" name="price_plan" required value="{{ old('price_plan') }}">                                            
                                            @if ($errors->has('price_plan'))
                                            <span class="text-danger">{{ $errors->first('price_plan') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                               

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Discount (in amount)</label><span class="required_field">*</span>
                                            <input type="number" min="1" class="form-control" id="offer_percentage" name="offer_percentage" required value="{{ old('offer_percentage') }}">                                            
                                            @if ($errors->has('offer_percentage'))
                                            <span class="text-danger">{{ $errors->first('offer_percentage') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Discount Type</label>
                                            <div class="radio-list">
                                                <label class="radio-inline p-0">
                                                    <div class="radio radio-info">
                                                        <input type="radio" name="offer_type" id="radio1" value="plus">
                                                        <label for="radio1">Plus(+)</label>
                                                    </div>
                                                </label>
                                                <label class="radio-inline">
                                                    <div class="radio radio-info">
                                                        <input type="radio" name="offer_type" id="radio2" value="minus" checked>
                                                        <label for="radio2">Minus(-) </label>
                                                    </div>
                                                </label>
                                            </div>
                                            @if ($errors->has('offer_type'))
                                            <span class="text-danger">{{ $errors->first('offer_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Date</label><span class="required_field">*</span>
                                            <input class="form-control input-daterange-datepicker check-room" id="check_date" type="text" name="check_date">
                                            @if ($errors->has('check_date'))
                                            <span class="text-danger">{{ $errors->first('check_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>

                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('sight-seen-pricing')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</div>


<script type="text/javascript">
    $(document).ready(function () {
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment().add('+1', 'days'),
            minDate: moment(),
//            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD MMM YYYY'
            }
        });
//        $('#datepicker-autoclose1').datepicker({
//            autoclose: true,
//            todayHighlight: true,
//            startDate: '-0m'
//        });
        $('#datepicker-autoclose2').datepicker({
            autoclose: true,
            todayHighlight: true,
            startDate: '-0m'
        });
        $('#tour_id').on('change', function () {
            $('#tour_name').val($("#tour_id option:selected").text());
        });
        $(document).on('click', '.addData', function () {
            var x = validator.form();
            return x;
        });
        validator = $('#availabilityForm').validate({
            rules: {
                'tour_id': {
                    required: true
                },
                
                'price_plan': {
                    required: true
                },
                'offer_percentage': {
                    required: true
                },
                'offer_type': {
                    required: true
                },
                'check_date': {
                    required: true
                }
            },
            messages: {
                'hotel_id': {
                    required: "Please choose a tour"
                },
                'price_plan': {
                    required: "Please give price plan name"
                },
                'offer_percentage': {
                    required: "Please give Discount"
                },
                'offer_type': {
                    required: "Please choose discount type"
                },
                'check_date': {
                    required: "Please choose date"
                }
            }
        });
    });
</script>

@endsection