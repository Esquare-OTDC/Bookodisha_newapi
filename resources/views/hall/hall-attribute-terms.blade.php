@extends('layouts.app')

@section('title', 'Hall Attributes Terms')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Hall</li>
                <li class="breadcrumb-item">Hall Attributes</li>
                <li class="breadcrumb-item active">Attribute: {{ $ServiceAttribute->attribute_name }}</li>
            </ol>
        </div>
    </div>

    @include('errors.message')

    <div class="row">
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">Attribute: {{ $ServiceAttribute->attribute_name }}</h2>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="white-box">
                        <h3 class="box-title">Add Terms</h3><hr>

                        <form class="form-horizontal" action="{{ route('hall-attribute-term-add-request') }}" method="POST">
                            @csrf
                            <input type="hidden" name="attr_id" value="{{ $ServiceAttribute->id }}">

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
                        <form class="form-inline" onsubmit="event.preventDefault();">
                            <select class="form-control" id="bulkOperation">
                                <option value="">Bulk Action</option>
                                <option value="delete-hall-attribute-term">Delete</option>
                            </select>
                            <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                        </form>

                        <br>

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
                                    @foreach ($AttributeTerms as $value)
                                        <tr>
                                            <td style="width: 6%;">
                                                <div class="checkbox-fade fade-in-primary">
                                                    <label>
                                                        <input type="checkbox" value="{{ $value->id }}" class="itemcheck">
                                                        <span class="cr">
                                                            <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>

                                            <td>{{ $value->facility_name }}</td>

                                            <td>
                                                <button data-toggle="modal" data-target="#editAttributeModal" class="btn btn-primary btn-sm edit-attribute" data-id="{{ $value->id }}" data-name="{{ $value->facility_name }}">
                                                    <i class="fa fa-edit"></i>Edit
                                                </button>
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

<div class="modal fade" id="editAttributeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">Edit Term</h4>
            </div>

            <div class="modal-body">
                <form onsubmit="event.preventDefault();">
                    <input type="hidden" id="attr-id">

                    <div class="form-group">
                        <label class="control-label">Name</label>
                        <input type="text" class="form-control" id="attr-name" required>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <a href="{{ route('hall-attribute') }}" class="btn btn-default">Back</a>
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
        $("#checkAll").prop('checked', $('.itemcheck:checked').length == $('.itemcheck').length);
    });

    $(document).on('click', '#submitBulkOperation', function () {
        let idArray = [];

        $(".itemcheck:checked").each(function(){
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
                url: "{{ route('hall-attribute-oprsn') }}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {
                    IdArray: JSON.stringify(idArray),
                    request_type: action
                },
                success: function (data) {
                    var responce = typeof data === 'string' ? $.parseJSON(data) : data;
                    alert(responce.message);
                    if (responce.status == 1) {
                        location.reload();
                    }
                }
            });
        } else {
            alert('please select an action!');
        }
    });

    $(document).on('click', '.edit-attribute', function () {
        $("#attr-id").val($(this).data('id'));
        $("#attr-name").val($(this).data('name'));
    });

    $(document).on('click', '#submitAttributeData', function () {
        let id = $("#attr-id").val();
        let attrName = $("#attr-name").val();

        if(id != "" && attrName != ''){
            $.ajax({
                type: "POST",
                url: "{{ route('hall-attribute-oprsn') }}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {
                    id: id,
                    name: attrName,
                    request_type: 'save-hall-terms-changes'
                },
                success: function (data) {
                    var responce = typeof data === 'string' ? $.parseJSON(data) : data;
                    alert(responce.message);
                    if (responce.status == 1) {
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