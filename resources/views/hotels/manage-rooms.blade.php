@extends('layouts.app')

@section('title', 'Room Management')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Hotels</li>
                <li class="breadcrumb-item">{{ $MasterHotel->name }}</li>
                <li class="breadcrumb-item active">Room Management</li>
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
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">Room Management</h2>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="white-box">
                        <h3 class="box-title">Add Room</h3><hr>                        
                        <form class="form-horizontal" action="{{ route('room-add-request') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="hotel_id" value="{{ $MasterHotel->id }}">
                            <div class="row">
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Title</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="title" placeholder="Room name" required maxlength="50">
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Feature Image</label>
                                    <div class="col-md-12">
                                        <input type="file" id="input-file-now" class="dropify" name="image" required>
                                        @if ($errors->has('image'))
                                            <span class="text-danger">{{ $errors->first('image') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12">Gallery</label>
                                    <div class="col-md-12">
                                        <div id="gallery-image" style="padding-top: .5rem;"></div>
                                        @if ($errors->has('images'))
                                        <span class="text-danger">{{ $errors->first('images') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Price</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="0" max="99999" maxlength="5" name="price" placeholder="Price" required>
                                            @if ($errors->has('price'))
                                                <span class="text-danger">{{ $errors->first('price') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Number of room</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="1" max="99" name="quantity" placeholder="Quantity" required>
                                            @if ($errors->has('quantity'))
                                                <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="" for="name">Number of rooms for MMT</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="0" max="99" name="mmt_quantity" placeholder="Quantity for MMT" required>
                                            @if ($errors->has('mmt_quantity'))
                                                <span class="text-danger">{{ $errors->first('mmt_quantity') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Number of beds</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="1" max="99" name="beds" required>
                                            @if ($errors->has('beds'))
                                                <span class="text-danger">{{ $errors->first('beds') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Room Size</label>
                                        <div class="input-group">
                                            <input type="number" id="example-input2-group1" min="0" max="99999" name="size" class="form-control" required> <span class="input-group-addon">sqft</span>
                                            @if ($errors->has('size'))
                                                <span class="text-danger">{{ $errors->first('size') }}</span>
                                            @endif
                                        </div>                                        
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Max Adults</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="1" max="99" name="adults" required>
                                            @if ($errors->has('adults'))
                                                <span class="text-danger">{{ $errors->first('adults') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Max Children</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="0" max="99" name="children">
                                            @if ($errors->has('children'))
                                                <span class="text-danger">{{ $errors->first('children') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="" for="name">Extra Mattress Allowed</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="0" max="9" name="extra_bed_allowed" required>
                                            @if ($errors->has('extra_bed_allowed'))
                                                <span class="text-danger">{{ $errors->first('extra_bed_allowed') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group m-t-20">
                                        <label class="" for="name">Extra Mattress Price</label>
                                        <div class="">
                                            <input type="number" class="form-control" min="0" max="99999" name="extra_bed_price" required>
                                            @if ($errors->has('extra_bed_price'))
                                                <span class="text-danger">{{ $errors->first('extra_bed_price') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Bed Type</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="bed_type" placeholder="e.g. : King Bed" required maxlength="50">
                                        @if ($errors->has('bed_type'))
                                            <span class="text-danger">{{ $errors->first('bed_type') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                                
                            @foreach ($RoomAttributes as $attrs => $terms)
                            <div class="white-box">
                                <div style="font-size: 15px;"><strong>Attribute: {{$attrs}}</strong></div>
                                <div class="input-group">
                                    <ul class="icheck-list">
                                        @foreach ($terms as $key => $values)
                                        <li>
                                            <input type="checkbox" class="check" name="property[{{ $attrs }}][]" value="{{ $key .'~'. $values }}" data-checkbox="icheckbox_flat-blue">
                                            <label>{{ $values }}</label>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            @endforeach
                            <div class="row">
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Status</label>
                                    <div class="col-md-12">
                                        <select name="status" class="form-control">
                                            <option value="publish">Publish</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                        @if ($errors->has('status'))
                                            <span class="text-danger">{{ $errors->first('status') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success pull-right"><i class="fa fa-save"></i> Add Room</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="white-box">
                        <div class="">
                            <form class="form-inline" onsubmit="event.preventDefault();">
                                <select class="form-control" id="bulkOperation">
                                    <option value="">Bulk Action</option>
                                    <option value="publish-room">Publish</option>
                                    <option value="draft-room">Move to draft</option>
                                </select>
                                <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                            </form>
                        </div><br>
                        <div class="table-responsive">
                            <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                                <thead>
                                    <th>
                                        <div class="checkbox-fade">
                                            <label><input type="checkbox" value="checkAll" id="checkAll"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label>
                                        </div>
                                    </th>
                                    <th>Room name</th>
                                    <th>Number of room</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </thead>	
                                <tbody>
                                    @foreach ($HotelRooms as $value)
                                        <tr>
                                            <td><div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="{{ $value->id }}" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div></td>
                                            <td>{{ $value->title }}</td>
                                            <td>{{ $value->quantity }}</td>
                                            <td>{{ $value->price }}</td>
                                            <td><span style="text-transform: capitalize;font-size: 12px;color: #fff;{{ ($value->status == 'publish') ? 'background-color: #28a745;' : 'background-color: #ffb136;' }}font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">{{ $value->status }}</span></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                                                    <ul role="menu" class="dropdown-menu">
                                                        <li><a href="{{ url('room-edit', $value->id) }}">Edit</a></li>
                                                        <li><a href="javascript:void(0)" class="deleteRoom" data-id="{{ $value->id }}">Delete</a></li>
                                                    </ul>
                                                </div>
                                                
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody> 
                            </table>
                        </div>
                    </div>
                </div>
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
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();
        $('#gallery-image').imageUploader();
        
        $(document).on('change', '#checkAll', function () {
            $('.itemcheck').prop('checked', $(this).prop("checked"));
            if ($('.itemcheck:checked').length >= 1) {
                $("#changeStatus").show();
            }
            else {
                $("#changeStatus").hide();
            }
        });
        
        $(document).on('change', '.itemcheck', function () {
            if ($('.itemcheck:checked').length == $('.itemcheck').length) {
                $("#checkAll").prop('checked', true);
            } else {
                $("#checkAll").prop('checked', false);
            }
            
            if ($('.itemcheck:checked').length >= 1) {
                $("#changeStatus").show();
            }
            else {
                $("#changeStatus").hide();
            }
        });
        
        $(document).on('click', '#submitBulkOperation', function () {
            let idArray = [];
            $("input:checkbox[class=itemcheck]:checked").each(function(){
                idArray.push($(this).val());
            });
            if(idArray.length == 0) {
                alert('No items selected!');
                return false;
            }
            let action = $("#bulkOperation").val();
            if(action != ""){
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {IdArray: JSON.stringify(idArray), request_type: action},
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
            } else {
                alert('please select an action!');
            }
        });
        
        $(document).on('click', '.deleteRoom', function () {
            let roomId = $(this).data('id');
            if(roomId != "" && confirm('Are you sure want to delete')) {
                $.ajax({
                    type: "POST",
                    url: "{{url('hotel-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {Id: roomId, request_type: "delete_hotel_room"},
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