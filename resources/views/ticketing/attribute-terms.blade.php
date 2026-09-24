@extends('layouts.app')

@section('title', 'Ticket Attributes Terms')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">        
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Ticketing</li>
                <li class="breadcrumb-item">Ticketing Attributes</li>
                <li class="breadcrumb-item active">Attribute: {{ $ServiceAttribute->name }}</li>
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
                <h2 id="PageHeading">Attribute: {{ $ServiceAttribute->name }}</h2>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="white-box">
                        <h3 class="box-title">Add Terms</h3><hr>                        
                        <form class="form-horizontal" action="{{ route('ticket-attribute-terms-add-request') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                            <input type="hidden" name="attr_id" value="{{ $ServiceAttribute->id }}">
                            <div class="row">
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Name</label>
                                    <div class="col-md-12">
                                        <input type="text" class="form-control" name="name" placeholder="Term name" required>
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-12" for="name">Icon</label>
                                    <div class="col-md-12">
                                        <input type="file" class="form-control" name="icon" accept=".png,.jpg,.jpeg">
                                        @if ($errors->has('icon'))
                                            <span class="text-danger">{{ $errors->first('icon') }}</span>
                                        @endif
                                    </div>
                                </div>
<!--                                <div class="form-group">
                                    <label class="col-md-12" for="name">Status</label>
                                    <div class="col-md-12">
                                        <label class="radio-inline">
                                            <input type="radio" name="status" value="1" checked required>Show
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="status" value="0" required>Hide
                                        </label>
                                    </div>
                                </div>-->
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
                                    <option value="delete-attribute-term">Delete</option>
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
                                    <th>Name</th>
                                    <th>Icon</th>
                                    <th>Action</th>
                                </thead>	
                                <tbody>
                                    @foreach ($AttributeTerms as $value)
                                        <tr>
                                            <td style="width: 6%;"><div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="{{ $value->id }}" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div></td>
                                            <td>{{ $value->name }}</td>
                                            <td>
                                                @if(!empty($value->icon))
                                                <img src="{{ $site_url . $value->icon }}" height="50" width="50">
                                                @else
                                                N/A
                                                @endif
                                            </td>
                                            <td>
                                                <button data-toggle="modal" data-target="#editAttributeModal" class="btn btn-primary btn-sm edit-attribute" data-id="{{ $value->id }}" data-name="{{ $value->name }}" data-status="{{ $value->status }}" data-icon="{{ $value->icon }}"><i class="fa fa-edit"></i>Edit</button>
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
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="exampleModalLabel1">Edit Attribute</h4>
            </div>
            <div class="modal-body">
                <form id="attrForm" onsubmit="event.preventDefault();">
                    <input type="hidden" name="id" id="attr-id">
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">Name</label>
                        <input type="text" class="form-control" id="attr-name" name="name" required> 
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">Icon</label>
                        <input type="file" class="form-control" id="attr-icon" name="icon" accept=".png,.jpg,.jpeg"> 
                    </div>
<!--                    <div class="form-group">
                        <label for="recipient-name" class="control-label">Status</label><br>
                        <label class="radio-inline">
                            <input type="radio" name="attrStatus" value="1" required>Show
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="attrStatus" value="0" required>Hide
                        </label>
                    </div>-->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" id="submitAttributeData" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
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
                    url: "{{url('ticket-oprsn')}}",
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
        
        $(document).on('click', '.edit-attribute', function () {
            let site = '<?= $site_url ?>';
            let id = $(this).data('id');
            let name = $(this).data('name');
            let status = $(this).data('status');
            $("#attr-name").val(name);
            $("#attr-id").val(id);
            $("input[name=attrStatus][value=" + status + "]").prop('checked', true);
        });
        
        $(document).on('click', '#submitAttributeData', function () {
            let id = $("#attr-id").val();
            let attrName = $("#attr-name").val();
            let attrStatus = $("input[name=edit-status]:checked").val();
            if(id != "" && attrName != '' && attrStatus != ''){
                let formData = new FormData($("#attrForm")[0]);
                formData.append('request_type', 'save-terms-changes');
                $.ajax({
                    type: "POST",
                    url: "{{url('ticket-oprsn')}}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: formData, // {Id: id,attrName: attrName, attrStatus: attrStatus, request_type: 'save-terms-changes'},
                    mimeType: "multipart/form-data",
                    contentType: false,
                    cache: false,
                    processData: false,
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
                alert('please fill all data');
            }
        });
    });
</script>

@endsection