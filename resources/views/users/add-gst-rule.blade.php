@extends('layouts.app')

@section('title','Add GST Rule')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Add GST Rule</div>
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

                        <form action="{{ route('gst-add-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <div class="form-body">
                                <div class="row">
                                    <input type="hidden" id="vendor" name="vendor_id" class="form-control" required value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Service</label><span class="required_field">*</span>
                                            <select class="form-control" name="service_type" required value="{{ old('vendor_id') }}">
                                                <option value="">Select Service</option>
                                                @foreach ($Services as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('service_type'))
                                            <span class="text-danger">{{ $errors->first('service_type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Minimum Amount</label><span class="required_field">*</span>
                                            <input type="number" min="1" required class="form-control" name="min_amount">
                                            @if ($errors->has('min_amount'))
                                            <span class="text-danger">{{ $errors->first('min_amount') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-2">
                                        <label class="col-md-12" for="name">GST<span class="required_field">*</span></label>
                                        <div class="col-md-12">
                                            <table class="display nowrap table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">Name</th>
                                                        <th class="text-center" colspan="2">Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="policy-container">
                                                    <tr id="0">
                                                        <td><input type="text" required class="form-control" name="gst[0][title]"></td>
                                                        <td><input type="number" min="0" step="any" required class="form-control" name="gst[0][value]"></td>
                                                        <td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i0"></i></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <span class="btn btn-info btn-sm" id="addNewRow" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                        </div>
                                        @if ($errors->has('gst'))
                                            <span class="text-danger">{{ $errors->first('gst') }}</span>
                                        @endif
                                    </div>
                                </div>                                
                                <hr> 
                            </div>

                            <div class="form-actions m-t-20 text-center">
                                <button type="submit" name="submit" class="btn btn-success staffAdd"> <i class="fa fa-check"></i> Save</button>
                                <a href="{{url('gst-rules')}}"><button type="button" class="btn btn-default">Cancel</button></a>
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
        var length = 0;
        $(document).on('click', '#addNewRow', function () {
            length++;
            $("#policy-container").append('<tr id="'+ length +'"><td><input type="text" required class="form-control" name="gst['+ length +'][title]"></td><td><input type="number" min="0" step="any" required class="form-control" name="gst['+ length +'][value]"></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i'+ length +'"></i></td></tr>');
        });
        
        $(document).on('click', '.deleteRow', function () {
            var id = $(this).attr('id').replace('i','');
            $("tr").remove("#"+id);
        });
    });
</script>

@endsection