@extends('layouts.app')

@section('title','Edit Slot')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Edit Slot</div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
                        @if(Session::has('success'))
                        <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
                            {{ Session::get('success') }}
                            @php
                            Session::forget('success');
                            @endphp
                        </p>
                        @endif

                        <form action="{{ route('edit-slots-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <input type='hidden' name='id' value='{{ $SlotDetails->id }}'>
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="control-label">Name</label><span class="required_field">*</span>
                                            <input type="text" name="name" class="form-control" required value='{{ $SlotDetails->name }}'> 
                                            @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Start Time</label><span class="required_field">*</span>
                                            <div class="input-group clockpicker " data-placement="bottom" data-align="top" data-autoclose="true">
                                                <input type="text" class="form-control" name="start_time" value='{{ $SlotDetails->start_time }}' required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                                @if ($errors->has('start_time'))
                                                <span class="text-danger">{{ $errors->first('start_time') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">End Time</label><span class="required_field">*</span>
                                            <div class="input-group clockpicker " data-placement="bottom" data-align="top" data-autoclose="true">
                                                <input type="text" class="form-control" name="end_time" value='{{ $SlotDetails->end_time }}' required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                                @if ($errors->has('end_time'))
                                                <span class="text-danger">{{ $errors->first('end_time') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Availability</label><span class="required_field">*</span><br>
                                            <select id="SelectDay" name="availability[]" class="form-control" multiple="multiple">
                                                @foreach ($Days as $val)
                                                @php
                                                $slelected = (in_array($val, $SlotDetails->availability)) ? 'selected="selected"' : '';
                                                @endphp
                                                <option value="{{ $val }}" {{ $slelected }}>{{ $val }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">City</label><span class="required_field">*</span><br>
                                            <select id="SelectCity" name="city[]" class="form-control" multiple="multiple">
                                                @foreach ($CityDetail as $val)
                                                @php
                                                $slelected = (in_array($val, $SlotDetails->city)) ? 'selected="selected"' : '';
                                                @endphp
                                                <option value="{{ $val }}" {{ $slelected }}>{{ $val }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <hr> 
                                </div>

                                <div class="form-actions m-t-20 text-center">
                                    <button type="submit" name="submit" class="btn btn-success staffAdd"> <i class="fa fa-check"></i> Save</button>
                                    <a href="{{url('available-slots')}}"><button type="button" class="btn btn-default">Cancel</button></a>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1"></div>
    </div>
</div>

<style>
    .multiselect-container > li > a > label.checkbox {
        color: #000 !important;
    }
    .multiselect-container > li > a > label {
        padding: 3px 3px 3px 10px;
    }
    .multiselect-clear-filter {
        background-color: #fff;
        margin-right: 5px;
        color: #b0b0b0;
    }
    .multiselect.dropdown-toggle.btn.btn-default {
        width: 400px !important;
    }
    .multiselect-container.dropdown-menu {
        width: 400px !important;
        height: 300px !important;
        overflow-y: auto;
    }
    .multiselect-container .input-group {
        margin: 4px 8px;
    }
    .input-group {
        width: 100% !important;
    }
    .dropdown-menu>.active>a, .dropdown-menu>.active>a:focus, .dropdown-menu>.active>a:hover {
        background-color: #fff;
    }
    label.checkbox {
        margin-left: 20px !important;
    }
    .checkbox input[type=checkbox] {
        opacity: 1;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        $('.clockpicker').clockpicker({
            donetext: 'Done',
        });
        
        $('#SelectDay').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Days'
        });
        
        $('#SelectCity').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select City',
            enableFiltering: true
        });
    });
</script>

@endsection