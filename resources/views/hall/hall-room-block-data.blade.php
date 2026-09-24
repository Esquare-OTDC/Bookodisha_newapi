@extends('layouts.app')

@section('title','Block Hall')

@section('content')

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Halls</li>
                <li class="breadcrumb-item active">Block Hall Rooms</li>
            </ol>
        </div>

        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button class="btn btn-info" id="exportCSV">Export</button>
            <a href="{{ url('block-hall-room') }}" class="btn btn-info">
                <i class="fa fa-plus"></i> Block Hall
            </a>
        </div>
    </div>

    @include('errors.message')
    <div id="ajaxMessage"></div>

    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">

                <div>
                    <form class="form-inline" onsubmit="event.preventDefault();">
                        <select class="form-control" id="bulkOperation">
                            <option value="">Bulk Action</option>
                            <option value="mark_block_released">Mark Released</option>
                        </select>

                        <button type="button" class="btn btn-primary md-effect mr-sm-2" id="submitBulkOperation">
                            Apply
                        </button>
                    </form>
                </div> 

                <form class="form-inline pull-right" onsubmit="event.preventDefault();">
                    <select id="property" class="form-control">
                        <option value="">Select Property</option>
                        @foreach ($MasterProperty as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>

                    <select id="hall" class="form-control">
                        <option value="">Select Hall</option>
                    </select>

                    <input class="form-control input-daterange-datepicker check-room"
                           id="check_date"
                           type="text"
                           name="check_date">

                    <button type="button" class="btn btn-sm btn-primary" id="search">
                        Search
                    </button>
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
                                <th>Hall Name</th>
                                <th>Slot Type</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <!-- <th>Action</th> -->
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="6" class="dataTables_empty">
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
    var exportQuery = '';
    var hallList = @json($HallList);

    $(document).ready(function () {
        let searchValue1 = '';
        let searchValue2 = '';
        let searchValue3 = '';

        var oTable;
        getHallBlockData(oTable, searchValue1, searchValue2, searchValue3);

        $('.input-daterange-datepicker').daterangepicker({
            autoApply: true,
            opens: 'left',
            startDate: moment(),
            endDate: moment().add(1, 'days'),
            locale: {
                format: 'DD-MM-YYYY'
            }
        });

        $(document).on('change', '#property', function () {
            let propertyId = $(this).val();

            $('#hall').html('<option value="">Select Hall</option>');

            $.each(hallList, function (index, hall) {
                if (propertyId == hall.property_id) {
                    $('#hall').append(
                        '<option value="' + hall.id + '">' + hall.hall_name + '</option>'
                    );
                }
            });
        });

        $(document).on('click', '#search', function () {
            searchValue1 = $("#property").val();
            searchValue2 = $("#hall").val();
            searchValue3 = $("#check_date").val();

            getHallBlockData(oTable, searchValue1, searchValue2, searchValue3);
        });

        $(document).on('click', '#exportCSV', function () {
            $.ajax({
                type: "POST",
                url: "{{ route('export-hall-block-data') }}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}'
                },
                data: {
                    searchValue1: searchValue1,
                    searchValue2: searchValue2,
                    searchValue3: searchValue3
                },
                success: function (data) {
                    var downloadLink = document.createElement("a");
                    var blob = new Blob(["\ufeff", data], {
                        type: "application/vnd.ms-excel;charset=utf-8;"
                    });
                    var url = URL.createObjectURL(blob);

                    downloadLink.href = url;
                    downloadLink.download = "Hall_block_report_" + new Date().getTime() + ".xls";

                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            });
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
        
        function showMessage(type, message) {
            let textColor = (type === 'success') ? '#28a745' : '#dc3545';
            $("#ajaxMessage")
                .stop(true, true)
                .html(
                    '<span style="color:' + textColor + '; font-weight:600;">' +
                    message +
                    '</span>'
                )
                .fadeIn();
            setTimeout(function () {
                $("#ajaxMessage").fadeOut(500, function () {
                    $(this).html('').show();
                });
            }, 5000);
        }

        $(document).on('click', '#submitBulkOperation', function () {
            let idArray = [];
            $("input.itemcheck:checked").each(function () {
                idArray.push($(this).val());
            });
            if (idArray.length == 0) {
                showMessage('error', 'No items selected!');
                return false;
            }
            let action = $("#bulkOperation").val();
            if (action == "") {
                showMessage('error', 'Please select an action!');
                return false;
            }
            $("#submitBulkOperation").prop('disabled', true);
            $.ajax({
                type: "POST",
                url: "{{ url('hall-oprsn') }}",
                dataType: "json",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {
                    IdArray: JSON.stringify(idArray),
                    request_type: action
                },
                success: function (response) {
                    if (response.status == 1) {

                        showMessage('success', response.message ?? "Success");

                        // uncheck all
                        $("#checkAll").prop('checked', false);
                        $(".itemcheck").prop('checked', false);

                    } else {

                        showMessage('error', response.message ?? "Failed");
                    }

                    getHallBlockData(oTable, searchValue1, searchValue2, searchValue3);

                    $("#submitBulkOperation").prop('disabled', false);
                },
                error: function (xhr) {
                    showMessage('error', 'Server error occurred');
                    $("#submitBulkOperation").prop('disabled', false);
                }
            });
        });
    });

    function getHallBlockData(oTable, searchValue1 = '', searchValue2 = '', searchValue3 = '') {
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
                "url": "{{ url('get-hall-block-data') }}",
                "type": "POST",
                "data": {
                    _token: "{{ csrf_token() }}",
                    searchValue1: searchValue1,
                    searchValue2: searchValue2,
                    searchValue3: searchValue3
                }
            },
            "aoColumnDefs": [{
                "bSortable": false,
                "aTargets": [0, 5]
            }],
            "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
            "order": [],
            "iDisplayLength": 10,
            "drawCallback": function (settings) {
                $("span#spandatatable-responsive_info").html(
                    '<i class="icon-ok"></i> ' + $("#datatable-responsive_info").text()
                );

                $("#datatable-responsive_info").hide();

                let response = settings.json;

                if (response && response.exportQuery) {
                    exportQuery = response.exportQuery;
                }
            }
        });
    }
</script>

@endsection
