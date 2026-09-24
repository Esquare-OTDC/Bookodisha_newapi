@extends('layouts.app')

@section('title', 'Conference Room Management')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Conference</li>
                <li class="breadcrumb-item">Name</li>
                <li class="breadcrumb-item active">Conference Room Management</li>
            </ol>
        </div>
    </div>

    @if(Session::has('success'))
        <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
            {{ Session::get('success') }}
            @php Session::forget('success'); @endphp
        </p>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="header-section">
                <h2 id="PageHeading">Conference Room Management</h2>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="white-box add-room-box">
                        <h3 class="box-title">Add Room</h3>
                        <hr>

                        <form class="form-horizontal"
                              action="{{ route('manage-conference-rooms') }}"
                              method="POST"
                              enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="hotel_id" value="">

                            <div class="form-group">
                                <label class="col-md-12">Title</label>
                                <div class="col-md-12">
                                    <input type="text"
                                           class="form-control"
                                           name="title"
                                           placeholder="Room name"
                                           required
                                           maxlength="50">

                                    @if ($errors->has('title'))
                                        <span class="text-danger">{{ $errors->first('title') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Hall Category</label>
                                <div class="col-md-12 hall-category-box">
                                    <label>
                                        <input type="checkbox"
                                               class="hall-type-check"
                                               name="hall_type"
                                               value="conference_hall">
                                        Conference hall
                                    </label>

                                    <label>
                                        <input type="checkbox"
                                               class="hall-type-check"
                                               name="hall_type"
                                               value="convention_hall">
                                        Convention hall
                                    </label>

                                    @if ($errors->has('hall_type'))
                                        <span class="text-danger">{{ $errors->first('hall_type') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Feature Image</label>
                                <div class="col-md-12">
                                    <input type="file"
                                           id="input-file-now"
                                           class="dropify"
                                           name="image"
                                           required>

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

                            <div class="form-group">
                                <label class="col-md-12">Hall Capacity</label>
                                <div class="col-md-12">
                                    <input type="number"
                                           class="form-control"
                                           name="hall_capacity"
                                           min="1"
                                           max="99999"
                                           placeholder="Hall capacity"
                                           required>

                                    @if ($errors->has('hall_capacity'))
                                        <span class="text-danger">{{ $errors->first('hall_capacity') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Quantity</label>
                                <div class="col-md-12">
                                    <input type="number"
                                           class="form-control"
                                           name="hall_capacity"
                                           min="1"
                                           max="99999"
                                           placeholder="Quantity"
                                           required>

                                    @if ($errors->has('quantity'))
                                        <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-md-12">Price</label>

                                <div class="col-md-12 price-row">
                                    <label>Full day</label>

                                    <input type="number"
                                        class="form-control price-input"
                                        name="full_day_price"
                                        min="0"
                                        max="999999"
                                        placeholder="Price">
                                </div>

                                <div class="col-md-12 price-row">
                                    <label>Half day</label>

                                    <input type="number"
                                        class="form-control price-input"
                                        name="half_day_price"
                                        min="0"
                                        max="999999"
                                        placeholder="Price">
                                </div>
                            </div>
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
                                    <i class="fa fa-save"></i> Add Room
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
                                        <th>Room name</th>
                                        <th>Number of room</th>
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
                                                <td>{{ $value->title }}</td>
                                                <td>{{ $value->quantity }}</td>
                                                <td>{{ $value->price }}</td>
                                                <td>
                                                    <span style="text-transform: capitalize;font-size: 12px;color: #fff;{{ ($value->status == 'publish') ? 'background-color: #28a745;' : 'background-color: #ffb136;' }}font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">
                                                        {{ $value->status }}
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
                                                                <a href="{{ url('room-edit', $value->id) }}">Edit</a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0)" class="deleteRoom" data-id="{{ $value->id }}">Delete</a>
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

    .price-check {
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

<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>

<script type="text/javascript">
    $(document).ready(function () {
        $('.dropify').dropify();
        $('#gallery-image').imageUploader();

        $(document).on('change', '.hall-type-check', function () {
            $('.hall-type-check').not(this).prop('checked', false);
        });

        $(document).on('change', '.price-type-check', function () {
            $('.price-type-check').not(this).prop('checked', false);
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
                    url: "{{ url('hotel-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}'
                    },
                    data: {
                        IdArray: JSON.stringify(idArray),
                        request_type: action
                    },
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
                    url: "{{ url('hotel-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}'
                    },
                    data: {
                        Id: roomId,
                        request_type: "delete_hotel_room"
                    },
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