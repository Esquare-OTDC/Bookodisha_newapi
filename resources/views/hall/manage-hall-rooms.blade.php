@extends('layouts.app')

@section('title', 'Hall Room Management')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Hall</li>
                <li class="breadcrumb-item active">Hall Management</li>
            </ol>
        </div>
    </div>

    @include('errors.message')

    <div class="row">
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">Hall Management</h2>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="white-box add-room-box">
                        <h3 class="box-title">Add Hall</h3>
                        <hr>

                        <form class="form-horizontal" action="{{ route('add-hall-room-request') }}"
                            method="POST" id="hallForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="property_id" value="{{ $propertyId }}">
                            <div class="form-group">
                                <label class="col-md-12">Hall Name <span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="title" placeholder="Hall name" maxlength="50">
                                    @if ($errors->has('title'))
                                        <span class="text-danger">{{ $errors->first('title') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">Hall Category <span style="color:red;">*</span> </label>
                                <div class="col-md-12">
                                    @foreach($hallCategories as $category)
                                        <label>
                                            <input type="radio" name="hall_type" value="{{ $category->id }}" >
                                            {{ $category->hcategory_name }}
                                        </label><br>
                                    @endforeach

                                    @if ($errors->has('hall_type'))
                                        <span class="text-danger">{{ $errors->first('hall_type') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Feature Image <span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <input type="file" id="input-file-now" class="dropify" name="image" accept=".jpg,.jpeg,.png">
                                    @if ($errors->has('image'))
                                        <span class="text-danger">{{ $errors->first('image') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Gallery</label>
                                <div class="col-md-12">
                                    <div id="gallery-image" style="padding-top: .5rem;" accept=".jpg,.jpeg,.png"></div>

                                    @if ($errors->has('images'))
                                        <span class="text-danger">{{ $errors->first('images') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Hall Capacity<span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <input type="number" class="form-control" name="hall_capacity" min="1" max="99999" placeholder="Hall capacity">
                                    @if ($errors->has('hall_capacity'))
                                        <span class="text-danger">{{ $errors->first('hall_capacity') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Price <span style="color:red;">*</span></label>
                                <div class="col-md-12 price-row">
                                    <label>Full day</label>
                                    <input type="number" class="form-control price-input" name="full_day_price" id="full_day_price" min="0" max="999999" step="1" placeholder="Amount">
                                </div>

                                <div class="col-md-12 price-row">
                                    <label>Half day</label>
                                    <input type="number" class="form-control price-input" name="half_day_price" id="half_day_price" min="0" max="999999" step="1" placeholder="Amount">
                                </div>
                            </div>

                            @foreach ($HallAttributes as $attribute)
                                <div class="white-box">
                                    <h3 class="box-title">Attribute: {{ $attribute->attribute_name }}</h3>
                                    <hr>

                                    <div class="form-group">
                                        <div class="icheck-list">
                                            @foreach ($attribute->facilities as $facility)
                                                <label>
                                                    <input type="checkbox"
                                                           name="facilities[]"
                                                           value="{{ $facility->id }}"
                                                           {{ is_array(old('facilities')) && in_array($facility->id, old('facilities')) ? 'checked' : '' }}>
                                                    {{ $facility->facility_name }}
                                                </label><br>
                                            @endforeach
                                        </div>

                                        @if ($errors->has('facilities'))
                                            <span class="text-danger">{{ $errors->first('facilities') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <div class="form-group">
                                <label class="col-md-12">Status</label>
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

                            <div class="form-submit-row">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-save"></i> Add Hall
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="white-box">
                        <form class="form-inline" onsubmit="event.preventDefault();">
                            <select class="form-control" id="bulkOperation">
                                <option value="">Bulk Action</option>
                                <option value="publish-room">Publish</option>
                                <option value="draft-room">Move to draft</option>
                            </select>

                            <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">
                                Apply
                            </button>
                        </form>

                        <br>

                        <div class="table-responsive no-scroll-table">
                            <table id="datatable-responsive" class="display table table-hover table-bordered" style="width: 100%;">
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
                                        <th>Hall name</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @if(!empty($HotelRooms))
                                        @foreach ($HotelRooms as $value)
                                            <tr>
                                                <td>
                                                    <div class="checkbox-fade fade-in-primary">
                                                        <label>
                                                            <input type="checkbox" value="{{ $value->id }}" class="itemcheck">
                                                            <span class="cr">
                                                                <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>

                                                <td>{{ $value->hall_name }}</td>

                                                <td>
                                                    <span class="badge badge-success">
                                                        FULL DAY - {{ $value->full_day_price !== null ? number_format($value->full_day_price, 2) : '-' }}
                                                    </span>
                                                    <span class="badge badge-success">
                                                        HALF DAY - {{ $value->half_day_price !== null ? number_format($value->half_day_price, 2) : '-' }}
                                                    </span>
                                                </td>

                                                <td>
                                                    <span style="text-transform: capitalize;font-size: 12px;color: #fff;{{ ($value->status == 1) ? 'background-color: #28a745;' : 'background-color: #ffb136;' }}font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">
                                                        {{ ($value->status == 1) ? 'Publish' : 'Draft' }}
                                                    </span>
                                                </td>

                                                <td>
                                                    <div class="btn-group">
                                                        <button aria-expanded="false"
                                                                data-toggle="dropdown"
                                                                class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light"
                                                                type="button">
                                                            Action <span class="caret"></span>
                                                        </button>

                                                        <ul role="menu" class="dropdown-menu">
                                                            <li>
                                                                <a href="{{ url('edit-property-hall', Crypt::encryptString($value->id)) }}">Edit</a>
                                                            </li>
                                                            <li>
                                                                <a class="deleteRoom" data-id="{{ Crypt::encryptString($value->id) }}">Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
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
    .add-room-box {
        padding-bottom: 25px;
    }

    .hall-category-box label {
        margin-right: 15px;
        font-weight: normal;
    }

    .price-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: nowrap;
    }

    .price-row label {
        min-width: 75px;
        margin-bottom: 0;
        font-weight: normal;
    }

    .price-input {
        width: 130px;
        max-width: 130px;
    }

    .form-submit-row {
        clear: both;
        display: flex;
        justify-content: flex-end;
        padding: 15px 15px 0;
    }

    .image-uploader {
        min-height: 20rem;
    }

    .no-scroll-table,
    .no-scroll-table .dataTables_wrapper {
        overflow-x: hidden !important;
    }

    .no-scroll-table table.dataTable {
        width: 100% !important;
        margin-bottom: 0 !important;
    }

    .no-scroll-table table.dataTable th,
    .no-scroll-table table.dataTable td {
        white-space: normal !important;
    }

    table.dataTable thead th:first-child::before,
    table.dataTable thead th:first-child::after {
        display: none !important;
    }

    table.dataTable thead th:first-child {
        background-image: none !important;
        cursor: default !important;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();
        $('#gallery-image').imageUploader({
            maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });

        if ($.fn.DataTable) {
            $('#datatable-responsive').DataTable({
                paging: false,
                searching: false,
                ordering: true,
                info: false,
                lengthChange: false,
                scrollX: false,
                autoWidth: false,
                columnDefs: [
                    {
                        targets: 0,
                        orderable: false
                    }
                ]
            });
        }

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
                    url: "{{ url('hall-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}'
                    },
                    data: {
                        IdArray: JSON.stringify(idArray),
                        request_type: action
                    },
                    success: function (data) {
                        var responce = typeof data === 'string' ? $.parseJSON(data) : data;

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

            if (roomId != "" && confirm('Are you sure want to delete?')) {
                $.ajax({
                    type: "POST",
                    url: "{{ url('hall-oprsn') }}",
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: {
                        Id: roomId,
                        request_type: "delete_hall_room"
                    },
                    success: function (response) {
                        if (response.status == 1) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                        alert('Something went wrong. Check console for details.');
                    }
                });
            }
        });
    });

    $('#hallForm').on('submit', function(e){
        let hasError = false;
        $('.dynamic-error').remove();
        function showError(element, message){
            $(element).after(
                '<span class="text-danger dynamic-error d-block">' +
                message +
                '</span>'
            );
            hasError = true;
        }
        // Hall Name
        if($('input[name="title"]').val().trim() === ''){
            showError(
                'input[name="title"]',
                'Hall Name is required'
            );
        }
        // Hall Category
        if($('input[name="hall_type"]:checked').length === 0){
            $('input[name="hall_type"]:last')
                .closest('.col-md-12')
                .append(
                    '<span class="text-danger dynamic-error d-block">Hall Category is required</span>'
                );
            hasError = true;
        }
        // Feature Image
        const maxSize = 2 * 1024 * 1024; // 2 MB
        if($('input[name="image"]').get(0).files.length === 0){
            $('input[name="image"]')
                .closest('.col-md-12')
                .append(
                    '<span class="text-danger dynamic-error d-block">Feature Image is required</span>'
                );
            hasError = true;
        }else if ($('input[name="image"]').get(0).files[0].size > maxSize){
            $('input[name="image"]')
                .closest('.col-md-12')
                .append(
                    '<span class="text-danger dynamic-error d-block">Feature Image size should not exceed 2 MB.</span>'
                );
            hasError = true;
        }
        // Hall Capacity
        let capacity = $('input[name="hall_capacity"]').val();

        if(capacity === '' || parseInt(capacity) <= 0){
            showError(
                'input[name="hall_capacity"]',
                'Enter valid hall capacity'
            );
        }
        // Full Day Price
        if($('#full_day_price').val().trim() === ''){
            showError(
                '#full_day_price',
                'Full Day Price is required'
            );
        }

        // Half Day Price
        if($('#half_day_price').val().trim() === ''){
            showError(
                '#half_day_price',
                'Half Day Price is required'
            );
        }

        if(hasError){
            e.preventDefault();

            $('html, body').animate({
                scrollTop: $('.dynamic-error:first').offset().top - 100
            }, 500);

            return false;
        }
    });
</script>

@endsection