@extends('layouts.app')

@section('title','All Conference')

@section('content')

<div class="container-fluid">

    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">All Conference</li>
            </ol>
        </div>

        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{ url('add-conference') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Add Conference
            </a>
        </div>
    </div>

    @if(Session::has('success'))
        <p class="flashMessage" style="color:#3bbc2e;text-align:center;">
            {{ Session::get('success') }}
        </p>
    @endif

    <div class="row">
        <div class="col-sm-12">

            <div class="white-box">

                <div class="col-md-4">
                    <form class="form-inline">

                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Move to draft</option>
                            <option value="show_price">Show Price</option>
                            <option value="hide_price">Hide Price</option>
                        </select>

                        <button type="button" class="btn btn-primary md-effect mr-sm-2">
                            Apply
                        </button>

                    </form>
                </div>

                <div class="col-md-8">

                    <form class="form-inline" style="float:right;">

                        @if(Auth::user()->role == 1)
                        <select class="form-control select2" id="vendorId">
                            <option value="">Select Vendor</option>
                        </select>
                        @endif

                        <select class="form-control" id="customColumn">
                            <option value="">Select</option>
                            <option value="name">Name</option>
                            <option value="place">Location</option>
                        </select>

                        <span class="mr-sm-2">
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search by name">
                        </span>

                        <button type="button"
                                class="btn btn-primary md-effect mr-sm-2">
                            Search
                        </button>

                        <i class="fa fa-refresh fa-lg md-effect"
                           aria-hidden="true"
                           style="cursor:pointer;">
                        </i>

                    </form>

                </div>

                <br><br><br>

                <span class="label label-success btn-xs"></span>

                <br><br>

                <div class="table-responsive">

                    <table id="datatable-responsive"
                           class="display nowrap table table-hover table-bordered">

                        <thead>
                            <tr>

                                <th>
                                    <div class="checkbox-fade">
                                        <label>
                                            <input type="checkbox" id="checkAll">
                                            <span class="cr">
                                                <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                                            </span>
                                        </label>
                                    </div>
                                </th>

                                @if(Auth::user()->access_type == 'superadmin')
                                    <th>Vendor</th>
                                @endif

                                <th>Name</th>
                                <th>Location</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>
                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    <input type="checkbox" class="itemcheck">
                                </td>

                                @if(Auth::user()->access_type == 'superadmin')
                                    <td></td>
                                @endif

                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>

                                <td></td>

                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-info btn-xs dropdown-toggle"
                                                type="button"
                                                data-toggle="dropdown">
                                            Action
                                            <span class="caret"></span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-right">

                                            <li>
                                                <a class="dropdown-item" href="#">
                                                    Edit Conference
                                                </a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="{{ url('manage-conference-rooms') }}">
                                                    Manage Conference
                                                </a>
                                            </li>

                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0);">
                                                    Delete
                                                </a>
                                            </li>

                                        </ul>
                                    </div>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>
    </div>

</div>

<style>
.select2-container {
    min-width: 200px;
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

{{-- Only add these if NOT already loaded in layouts.app --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function () {

    $('#datatable-responsive').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        pageLength: 10,
        columnDefs: [
            {
                targets: 0,
                orderable: false
            }
        ]
    });

    $('#checkAll').on('change', function () {
        $('.itemcheck').prop('checked', $(this).prop('checked'));
    });

});
</script>

@endsection