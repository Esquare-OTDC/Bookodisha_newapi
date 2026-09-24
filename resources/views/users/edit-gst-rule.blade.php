@extends('layouts.app')

@section('title','Edit GST Rule')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-1"></div>
        <div class="col-md-10">
            <div class="panel panel-info">
                <div class="panel-heading text-center">Edit GST Rule</div>
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

                        <form action="{{ route('gst-edit-request') }}"  method="POST" id='staffForm'>
                            @csrf
                            <input type="hidden" name="id" class="form-control" value="{{ $GstTable->id }}">
                            <div class="form-body">
                                <div class="row">
                                    <input type="hidden" id="vendor" name="vendor_id" class="form-control" required value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Service</label><span class="required_field">*</span>
                                            <select class="form-control" name="service_type" required>
                                                <option value="">Select Service</option>
                                                @foreach ($Services as $key => $value)
                                                @php
                                                $selected = ($GstTable->service_type == $key) ? 'selected' : '';
                                                @endphp
                                                <option value="{{ $key }}" {{ $selected }}>{{ $value }}</option>
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
                                            <input type="number" min="1" required class="form-control" name="min_amount"  value="{{ $GstTable->min_amount }}">
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
                                                    <?php
                                                    if (!empty($GstTable->gst)) {
                                                        $countr = 0;
                                                        foreach ($GstTable->gst as $key => $value) {
                                                            ?>
                                                            <tr id="{{ $countr }}">
                                                                <td>
                                                                    <input type="text" class="form-control" required name="gst[{{ $countr }}][title]" value="{{ $key }}">
                                                                </td>
                                                                <td>
                                                                    <input type="number" min="0" required step="any" class="form-control" name="gst[{{ $countr }}][value]" value="{{ $value }}">
                                                                </td>
                                                                <td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i{{ $countr }}"></i>
                                                                </td>
                                                            </tr>
                                                            <?php $countr++;
                                                        }
                                                    } ?>
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
        var length = "{{ count($GstTable->gst) }}";
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