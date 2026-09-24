@extends('layouts.app')

@section('title','Blocked Halls')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Halls</li>
                <li class="breadcrumb-item active">Blocked Halls</li>
            </ol>
        </div>

        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <a href="{{ route('block-hall-room') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Block Hall
            </a>
        </div>
    </div>

    @if(Session::has('success'))
        <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
            {{ Session::get('success') }}
            @php Session::forget('success'); @endphp
        </p>
    @endif

    @include('errors.message')

    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <div>
                    <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="delete_blocked_halls">Delete</option>
                        </select>

                        <button type="button" class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">
                            Apply
                        </button>
                    </form>
                </div>

                <form class="form-inline pull-right" onsubmit="event.preventDefault();">
                    <select id="hall" class="form-control">
                        <option value="">Select Hall</option>
                        @foreach ($HallList as $hall)
                            <option value="{{ $hall->id }}">{{ $hall->hall_name }}</option>
                        @endforeach
                    </select>

                    <input class="form-control input-daterange-datepicker check-room"
                           id="check_date"
                           type="text"
                           name="check_date"
                           readonly>

                    <button type="button" class="btn btn-sm btn-primary" id="search">
                        Search
                    </button>

                    <i id="resetSession"
                       class="fa fa-refresh fa-lg md-effect"
                       aria-hidden="true"
                       style="cursor: pointer;"></i>
                </form>

                <br>

                <span class="label label-success btn-xs" id="spandatatable-responsive_info"></span>

                <br><br>

                <div class="table-responsive">
                    <table id="datatable-responsive" class="display nowrap table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th><input type="checkbox" value="checkAll" id="checkAll"></th>
                                <th>Property Name</th>
                                <th>Hall</th>
                                <th>Slot Type</th>
                                <th>Date</th>
                                <th>Reason</th>
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

<style type="text/css">
    .select2-container {
        min-width: 200px;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';

        var oTable;
        getBlockedHalls(oTable, searchValue1, searchValue2);

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            opens: 'left',
            startDate: moment(),
            endDate: moment().add(1, 'days'),
            locale: {
                format: 'DD-MM-YYYY'
            }
        });

        $(document).on('click', '#search', function () {
            searchValue1 = $("#hall").val();
            searchValue2 = $("#check_date").val();

            getBlockedHalls(oTable, searchValue1, searchValue2);
        });

        $(document).on('click', '#resetSession', function () {
            $("#hall").val('');
            $("#check_date").val('');

            searchValue1 = '';
            searchValue2 = '';

            getBlockedHalls(oTable, searchValue1, searchValue2);
        });

        $(document).on('change', '#checkAll', function () {
            $('.itemcheck').prop('checked', $(this).prop("checked"));
        });

        $(document).on('change', '.itemcheck', function () {
            if ($('.itemcheck:checked').length === $('.itemcheck').length) {
                $("#checkAll").prop('checked', true);
            } else {
                $("#checkAll").prop('checked', false);
            }
        });

        $(document).on('click', '.delete-data', function () {
            let Id = $(this).data('id');

            if (Id !== "" && confirm('Are you sure want to delete?')) {
                $.ajax({
                    type: "POST",
                    url: "{{ url('hall-oprsn') }}",
                    headers: {
                        'X-CSRF-Token': '{{ csrf_token() }}'
                    },
                    data: {
                        Id: Id,
                        request_type: 'delete-blocked-hall'
                    },
                    success: function (data) {
                        var response = $.parseJSON(data);

                        alert(response.message);

                        if (response.status != 0) {
                            getBlockedHalls(oTable, searchValue1, searchValue2);
                        }
                    }
                });
            }
        });

        $(document).on('click', '#submitBulkOperation', function () {
            let idArray = [];

            $("input:checkbox[class=itemcheck]:checked").each(function () {
                idArray.push($(this).val());
            });

            if (idArray.length === 0) {
                alert('No items selected!');
                return false;
            }

            let action = $("#bulkOperation").val();

            if (action === "") {
                alert('Please select an action!');
                return false;
            }

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
                    var response = $.parseJSON(data);

                    alert(response.message);

                    if (response.status != 0) {
                        getBlockedHalls(oTable, searchValue1, searchValue2);
                    }
                }
            });
        });
    });

    function getBlockedHalls(oTable, searchValue1 = '', searchValue2 = '') {
        if ($.fn.DataTable.isDataTable('#datatable-responsive')) {
            $('#datatable-responsive').DataTable().destroy();
        }

        oTable = $('#datatable-responsive').dataTable({
            "bProcessing": true,
            "fixedHeader": {
                header: true
            },
            "bServerSide": true,
            "bPaginate": true,
            "ajax": {
                "url": "{{ route('get-blocked-halls') }}",
                "type": "POST",
                "data": {
                    _token: "{{ csrf_token() }}",
                    searchValue2: searchValue1,
                    searchValue3: searchValue2
                }
            },
            "aoColumnDefs": [{
                "bSortable": false,
                "aTargets": [0, 6]
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "order": [],
            "iDisplayLength": 10,
            "drawCallback": function () {
                $("span#spandatatable-responsive_info").html(
                    '<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text()
                );

                $("#datatable-responsive_info").hide();
            }
        });
    }
</script>

@endsection