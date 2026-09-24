@extends('layouts.app')

@section('title','Available Slots')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Food Ordering</li>
                <li class="breadcrumb-item active">Available Slots</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            @if (Auth::user()->access_type == 'superadmin')
            <a href="{{url('add-available-slots')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add new Slot</a>
            @endif
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
                            <th>Name</th>
                            <th>Timing</th>
                            <th>Availability</th>
                            <th>City</th>
                            @if (Auth::user()->access_type == 'superadmin')
                            <th>Action</th>
                            @endif
                        </thead>	
                        <tbody>
                            @foreach ($AvailableSlots as $slots)
                            <tr>
                                <td>{{ $slots->name }}</td>
                                <td>{{ $slots->start_time .' - '. $slots->end_time }}</td>
                                <td>{{ $slots->availability }}</td>
                                <td>{{ $slots->city }}</td>
                                @if (Auth::user()->access_type == 'superadmin')
                                <td>
                                    <div class="btn-group">
                                        <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                                        <ul role="menu" class="dropdown-menu">
                                            <li><a href='{{ url('edit-available-slots/'. $slots->id) }}' class="editSlot" data-id="{{ $slots->id }}">Edit</a></li>
                                            <li><a href="javascript:void(0)" class="deleteSlot" data-id="{{ $slots->id }}">Delete</a></li>
                                        </ul>
                                    </div>
                                </td>
                                @endif
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
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        
        $(document).on('click', '.deleteSlot', function () {
            let id = $(this).data('id');
            if(id != '') {
                $.ajax({
                    type: "POST",
                    url: "{{url('slot-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: id,request_type: 'delete_slot'},
                    success: function (data) {
                        var responce = $.parseJSON(data);
                        if (responce.status == 0) {
                            alert(responce.message);
                        } else {
                            alert(responce.message);
                            location.reload();
                        }
                    }
                });
            }
        });
    });
</script>

@endsection