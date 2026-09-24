@extends('layouts.app')

@section('title','All Properties')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">All Properties</li>
            </ol>
        </div>
        <div class="col-md-7 align-self-center text-right d-none d-md-block">
            <a href="{{url('add-new-hall')}}" class="btn btn-info"><i class="fa fa-plus"></i> Add Properties</a>
        </div>
    </div>

    @include('errors.message')

    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <div class="col-md-4">
                    <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="publish">Publish</option>
                            <option value="draft">Move to draft</option>
                        </select>
                        <button class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">Apply</button>
                    </form>
                </div>

                <div class="col-md-8">
                    <form class="form-inline" onsubmit="event.preventDefault();" style="float: right;">
                        <select class="form-control" id="customColumn">
                            <option value="">Select</option>
                            <option value="property_name">Name</option>
                            <option value="place">Location</option>
                        </select>

                        <span class="mr-sm-2" id="searchInput">
                            <input type="text" id="searchValue" class="form-control" placeholder="Search by name">
                        </span>

                        <button class="btn btn-primary md-effect mr-sm-2" id="submitsearchText">Search</button>&nbsp;
                        <i id="resetSession" class="fa fa-refresh fa-lg md-effect" aria-hidden="true" style="cursor: pointer;"></i>
                    </form>
                </div>

                <br><br><br>

                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>

                <br><br>

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

                                <th>Property Name</th>
                                <th>Location</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="7" class="dataTables_empty">
                                    Loading data from server...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        var aTarget = 6;

        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';

        var oTable;

        getHalls(searchValue1, searchValue2, searchValue3);

        $(document).on('click', '#submitsearchText', function () {
            let searchText2 = $("#customColumn").val();
            let searchText3 = $("#searchValue").val();

            if (searchText2 != '' && searchText3 != '') {
                searchValue2 = searchText2;
                searchValue3 = searchText3;
            } else {
                searchValue2 = '';
                searchValue3 = '';
            }

            getHalls(searchValue1, searchValue2, searchValue3);
        });

        $(document).on('click', '#resetSession', function () {
            $("#customColumn").val('');

            $("#searchInput").html(
                '<input type="text" id="searchValue" class="form-control" placeholder="Search">'
            );

            searchValue1 = '';
            searchValue2 = '';
            searchValue3 = '';

            getHalls(searchValue1, searchValue2, searchValue3);
        });

        $(document).on('change', '#checkAll', function () {
            $('.itemcheck').prop('checked', $(this).prop("checked"));

            if ($('.itemcheck:checked').length >= 1) {
                $("#changeStatus").show();
            } else {
                $("#changeStatus").hide();
            }
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

            $(".itemcheck:checked").each(function () {
                idArray.push($(this).val());
            });

            if (idArray.length == 0) {
                alert('No items selected!');
                return false;
            }

            let action = $("#bulkOperation").val();

            if (action == "") {
                alert('Please select an action!');
                return false;
            }

            $.ajax({
                type: "POST",
                url: "{{ url('hall-oprsn') }}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {
                    IdArray: JSON.stringify(idArray),
                    request_type: action
                },
                success: function (data) {
                    let response = typeof data === 'string' ? $.parseJSON(data) : data;

                    alert(response.message);

                    if (response.status == 1) {
                        $("#checkAll").prop('checked', false);
                        $("#bulkOperation").val('');
                        getHalls(searchValue1, searchValue2, searchValue3);
                    }
                }
            });
        });

        $(document).on('change', '#customColumn', function () {
            let column = $(this).val();

            if (column == 'place') {
                $.ajax({
                    type: "POST",
                    url: "{{ url('hall-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        request_type: 'get_hall_city'
                    },
                    success: function (data) {
                        let response = typeof data === 'string' ? $.parseJSON(data) : data;

                        if (response.status == 0) {
                            $("#searchInput").html(
                                '<select class="form-control select2" id="searchValue"><option value="">Select</option></select>'
                            );
                        } else {
                            let cityArray = Object.keys(response.data);
                            let html = '<option value="">Select</option>';

                            $.each(cityArray, function (index, city) {
                                html += '<option value="' + city + '">' + city + '</option>';
                            });

                            $("#searchInput").html(
                                '<select class="form-control select2" id="searchValue">' + html + '</select>'
                            );

                            $("#searchValue").select2();
                        }
                    }
                });
            } else {
                $("#searchInput").html(
                    '<input type="text" id="searchValue" class="form-control" placeholder="Search">'
                );
            }
        });

        $(document).on('click', '.deleteHall', function () {
            let hallId = $(this).data('id');

            if (hallId != "" && confirm('Are you sure you want to delete this hall?')) {
                $.ajax({
                    type: "POST",
                    url: "{{ url('hall-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}',
                    },
                    data: {
                        Id: hallId,
                        request_type: "delete_hall"
                    },
                    success: function (data) {
                        let response = typeof data === 'string' ? $.parseJSON(data) : data;

                        alert(response.message);

                        if (response.status == 1) {
                            getHalls(searchValue1, searchValue2, searchValue3);
                        }
                    }
                });
            }
        });

        function getHalls(searchValue1 = '', searchValue2 = '', searchValue3 = '') {
            if ($.fn.DataTable.isDataTable('#datatable-responsive')) {
                $('#datatable-responsive').DataTable().destroy();
            }

            oTable = $('#datatable-responsive').DataTable({
                processing: true,
                serverSide: true,
                paging: true,

                fixedHeader: {
                    header: true
                },

                ajax: {
                    url: "{{ url('get-hall-details') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        searchValue1: searchValue1,
                        searchValue2: searchValue2,
                        searchValue3: searchValue3
                    }
                },

                columnDefs: [{
                    orderable: false,
                    targets: [0, aTarget]
                }],

                lengthMenu: [
                    [10, 20, 50, 100],
                    [10, 20, 50, 100]
                ],

                order: [],
                pageLength: 10,

                drawCallback: function () {
                    let totalrecords = this.api().page.info().recordsTotal;

                    $('.counttotalrecords').html(
                        '<i class="icon-ok"></i> Total Records ' + totalrecords
                    );

                    $("span#spandatatable-responsive_info").html(
                        '<i class="icon-ok"></i> ' +
                        $("#datatable-responsive_info").text()
                    );

                    $("#datatable-responsive_info").hide();
                }
            });
        }
    });
</script>

@endsection