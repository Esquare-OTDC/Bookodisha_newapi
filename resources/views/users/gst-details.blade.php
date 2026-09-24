@extends('layouts.app')

@section('title','GST Details')

@section('content')

<link href="{{ asset('plugins/components/bootstrap-editable/bootstrap-editable.css') }}" rel="stylesheet" />
<script src="{{ asset('plugins/components/bootstrap-editable/bootstrap-editable.js') }}"></script>      

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Setting</a></li>
                <li class="breadcrumb-item active">GST Details</li>
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
                <h3>GST Management</h3>
                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-striped table-bordered">
                        <thead>
                            <th>GST Name</th>
                            <th>GST Percentage</th>
                            <th>Date</th>
                        </thead>	
                        
                        <tbody>
                            @foreach ($GstDetails as $value)
                            <tr>
                                <td>{{ $value->name }}</td>
                                <td> <a href="javascript:void(0)" class="myedit" id="save_gst_value" data-type="text" data-placement="right" data-id="{{ $value->id }}">{{ $value->value }}</a> </td>
                                <td>{{ date("M d Y, H:i", strtotime($value->updated_at)) }}</td>
                            </tr>
                            @endforeach
                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        $.fn.editable.defaults.mode = 'inline';
        $('.myedit').each(function() {
            var id = $(this).data('id');
            $(this).editable({
                type: 'text',
                pk: id,
                url: "{{url('gst-oprsn')}}",
                success: function(data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        alert(responce.message);
                        location.reload();
                    }
                }
            });
        });
    });
</script>

@endsection