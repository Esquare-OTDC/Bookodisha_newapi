@extends('layouts.app')

@section('title', 'Conference Attribute')

@section('content')

@php
    $Attributes = [
        ['id' => 1, 'name' => 'Facilities'],
    ];
@endphp

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Conference</li>
                <li class="breadcrumb-item active">Conference Attribute</li>
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
                <h2 id="PageHeading">Conference Attribute</h2>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="white-box">
                        <h3 class="box-title">Add Attribute</h3><hr>                        

                        <form class="form-horizontal" action="{{ route('conference-attribute') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Name</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="name" required>
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <button class="btn btn-primary" type="submit">Add new</button>
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
                                    <option value="delete-room-attribute">Delete</option>
                                </select>
                                <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                            </form>
                        </div><br>

                        <div class="table-responsive">
                            <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="checkbox-fade">
                                                <label>
                                                    <input type="checkbox" value="checkAll" id="checkAll">
                                                    <span class="cr">
                                                        <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                                    </span>
                                                </label>
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($Attributes as $value)
                                        <tr>
                                            <td style="width: 6%;">
                                                <div class="checkbox-fade fade-in-primary">
                                                    <label>
                                                        <input type="checkbox" value="{{ $value['id'] }}" class="itemcheck">
                                                        <span class="cr">
                                                            <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>

                                            <td>{{ $value['name'] }}</td>

                                            <td>
                                                <button data-toggle="modal" data-target="#editAttributeModal" class="btn btn-primary btn-sm edit-attribute" data-id="{{ $value['id'] }}" data-name="{{ $value['name'] }}"><i class="fa fa-edit"></i>Edit</button>
                                                <a href="{{ url('conference-attribute-terms', $value['id']) }}" class="btn btn-success btn-sm"><i class="fa fa-edit"></i>Manage Terms</a>
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

<div class="modal fade" id="editAttributeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="exampleModalLabel1">Edit Attribute</h4>
            </div>

            <div class="modal-body">
                <form onsubmit="event.preventDefault();">
                    <input type="hidden" name="id" id="attr-id">

                    <div class="form-group">
                        <label class="control-label">Name</label>
                        <input type="text" class="form-control" id="attr-name" name="name" required> 
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" id="submitAttributeData" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(document).on('change', '#checkAll', function () {
            $('.itemcheck').prop('checked', $(this).prop("checked"));
        });
        
        $(document).on('change', '.itemcheck', function () {
            if ($('.itemcheck:checked').length == $('.itemcheck').length) {
                $("#checkAll").prop('checked', true);
            } else {
                $("#checkAll").prop('checked', false);
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

            alert('Selected IDs: ' + idArray.join(', '));
        });
        
        $(document).on('click', '.edit-attribute', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');

            $("#attr-name").val(name);
            $("#attr-id").val(id);
        });
        
        $(document).on('click', '#submitAttributeData', function () {
            alert('This is static data. Database update is not available.');
        });
    });
</script>

@endsection