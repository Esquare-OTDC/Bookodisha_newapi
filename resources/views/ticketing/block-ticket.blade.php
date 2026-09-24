@extends('layouts.app')

@section('title','Block Ticketing')

@section('content')


<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Block Ticketing</div>
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
                
                        <form action="{{ route('block-ticket-request') }}"  method="POST" id="availabilityForm">
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" id="hotelSection">
                                            <label class="control-label">Tour</label><span class="required_field">*</span>
                                            <select class="form-control select2 check-quantity" id="ticket_id" name="ticket_id" required value="{{ old('tour_id') }}">
                                                <option value="">Select Ticket / Event</option>
                                                @foreach ($Ticket as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('ticket_id'))
                                                <span class="text-danger">{{ $errors->first('ticket_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Date</label><span class="required_field">*</span>
                                            <input class="form-control input-daterange-datepicker check-room" id="block_date" type="text" name="block_date" style="width: 100%;">
                                                <!--<input type="text" class="form-control check-quantity" name="block_date" id="datepicker-autoclose" placeholder="mm/dd/yyyy" required value="{{ old('block_date') }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>-->
                                            @if ($errors->has('block_date'))
                                                <span class="text-danger">{{ $errors->first('block_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Block Reason</label>
                                            <input type="text" class="form-control" name="block_reason" id="block_reason" value="{{ old('block_reason') }}">
                                            @if ($errors->has('block_reason'))
                                                <span class="text-danger">{{ $errors->first('block_reason') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <hr> 
                            </div>
                            
                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success addData"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('ticket-block-data')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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

        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            startDate: '-0m',
            endDate: '+120d'
        });
        
        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            startDate: moment(),
            endDate: moment().add('+1', 'days'),
            minDate: moment(),
            maxDate: moment().add('+120','days'),
            locale: {
              format: 'DD MMM YYYY'
            }
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
                'block_date': {
                    required: true
                },
            },
            messages: {
                'tour_id': {
                    required: "Please choose Tour"
                },
                'block_date': {
                    required: "Please choose date"
                },
            }
        });
    });
</script>

@endsection