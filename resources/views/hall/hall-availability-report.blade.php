@extends('layouts.app')

@section('title', 'Hall Availability Report')

@section('content')

<div class="container-fluid">

    <div class="row page-titles">

        <div class="col-md-6 align-self-center">

            <ol class="breadcrumb">

                <li class="breadcrumb-item">
                    <a href="{{ url('/') }}">
                        Home
                    </a>
                </li>

                <li class="breadcrumb-item">
                    MIS Report
                </li>

                <li class="breadcrumb-item active">
                    Hall Availability Report
                </li>

            </ol>

        </div>

        <div class="col-md-6 align-self-center text-right d-none d-md-block">
            <button type="button" id="exportWithBlockData" class="btn btn-info">Export with booked</button>
            <button type="button" id="exportAll" class="btn btn-info">Export All</button>
        </div>
    </div>

    </div>

    @if(session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif

    @if($errors->any())

        <div class="alert alert-danger">

            @foreach($errors->all() as $error)

                <div>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif

    <div class="row">

        <div class="col-sm-12">

            <div class="white-box">

                <form
                    method="GET"
                    action="{{ route('hall-availablity-report') }}"
                    id="filterForm"
                    class="form-inline"
                >

                    <select
                        name="property_id"
                        id="property_id"
                        class="form-control"
                        required
                    >

                        @forelse($MasterProperty as $id => $name)

                            <option
                                value="{{ $id }}"
                                {{ (string) $PropertyId === (string) $id ? 'selected' : '' }}
                            >
                                {{ $name }}
                            </option>

                        @empty

                            <option value="">
                                No property found
                            </option>

                        @endforelse

                    </select>

                    <input
                        type="text"
                        name="check_date"
                        id="check_date"
                        class="form-control input-daterange-datepicker"
                        autocomplete="off"
                        required
                    >

                    <button
                        type="submit"
                        id="search"
                        class="btn btn-primary"
                    >
                        Search
                    </button>

                    <a
                        href="{{ route('hall-availablity-report') }}"
                        id="resetReport"
                        title="Reset"
                    >
                        <i
                            class="fa fa-refresh fa-lg"
                            aria-hidden="true"
                        ></i>
                    </a>

                </form>

                <div class="table-responsive">

                    <table
                        class="table table-hover table-bordered hall-report-table"
                    >

                        <thead>

                            <tr>

                                <th
                                    colspan="3"
                                    class="report-title"
                                >
                                    Availability status of
                                    {{ $PropertyName ?: 'Property' }}
                                    as on
                                    ({{ now()->format('d-m-Y h:i a') }})
                                </th>

                            </tr>

                            <tr>

                                <th class="date-heading">
                                    Date
                                </th>

                                <th>
                                    Convention Hall
                                    <br>
                                    <small>
                                        Booked / Total
                                    </small>
                                </th>

                                <th>
                                    Conference Hall
                                    <br>
                                    <small>
                                        Booked / Total
                                    </small>
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($HallReportData as $row)

                                <tr>

                                    <td>
                                        {{ $row['display_date'] }}
                                    </td>

                                    <td>
                                        {{ number_format(
                                            $row['convention_booked'],
                                            $row['convention_booked'] == floor($row['convention_booked']) ? 0 : 1
                                        ) }}
                                        /
                                        {{ $row['convention_total'] }}
                                    </td>

                                    <td>
                                        {{ number_format(
                                            $row['conference_booked'],
                                            $row['conference_booked'] == floor($row['conference_booked']) ? 0 : 1
                                        ) }}
                                        /
                                        {{ $row['conference_total'] }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="3"
                                        class="text-center"
                                    >
                                        No report data found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<style>

    #filterForm {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin: 0;
        padding: 0;
    }

    #property_id {
        width: 280px;
        height: 38px;
        margin: 0 4px 0 0;
        border-radius: 0;
        font-size: 14px;
    }

    #check_date {
        width: 245px;
        height: 38px;
        margin: 0 4px 0 0;
        border-radius: 0;
        font-size: 14px;
    }

    #search {
        height: 38px;
        margin: 0;
        border-radius: 2px;
        font-size: 14px;
    }

    #resetReport {
        display: inline-block;
        margin-left: 14px;
        color: #8d9aa5;
        line-height: 38px;
        text-decoration: none;
    }

    #resetReport:hover {
        color: #03a9f3;
    }

    .hall-report-table {
        width: 100%;
        margin-top: 0;
        margin-bottom: 0;
        border-collapse: collapse;
        table-layout: fixed;
        color: #7f8b95;
        font-size: 14px;
    }

    .hall-report-table th,
    .hall-report-table td {
        padding: 8px 10px;
        border: 1px solid #dfe5e8 !important;
        text-align: center;
        vertical-align: middle !important;
        line-height: 20px;
    }

    .hall-report-table thead th {
        color: #111111;
        background-color: #ffffff;
        font-weight: 500;
    }

    .hall-report-table .report-title {
        padding: 8px 10px;
        color: #111111;
        background-color: #f6f8fa;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
    }

    .hall-report-table thead small {
        color: #7f8b95;
        font-size: 11px;
        font-weight: 400;
    }

    .hall-report-table tbody td {
        color: #89949e;
        background-color: #ffffff;
        font-weight: 400;
    }

    .hall-report-table tbody tr:hover td {
        background-color: #f7f9fa;
    }

    .hall-report-table .date-heading {
        width: 34%;
    }

    .white-box .table-responsive {
        margin-top: 0;
    }

    @media screen and (max-width: 767px) {

        #property_id,
        #check_date {
            width: 100%;
            margin: 0 0 8px 0;
        }

        #search {
            margin-bottom: 8px;
        }

        #resetReport {
            margin-bottom: 8px;
        }

        .hall-report-table {
            min-width: 650px;
        }
    }

    @media print {

        .page-titles,
        #filterForm,
        #printReport,
        .left-sidebar,
        .topbar {
            display: none !important;
        }

        .page-wrapper {
            margin-left: 0 !important;
        }

        .white-box {
            box-shadow: none !important;
            padding: 0 !important;
        }

        .hall-report-table {
            color: #000000;
        }

        .hall-report-table th,
        .hall-report-table td {
            color: #000000 !important;
        }
    }

</style>

<script>

    $(document).ready(function () {

        $('.input-daterange-datepicker').daterangepicker({

            autoApply: true,

            startDate: moment(
                '{{ $start_date }}',
                'YYYY-MM-DD'
            ),

            endDate: moment(
                '{{ $end_date }}',
                'YYYY-MM-DD'
            ),

            locale: {
                format: 'DD-MM-YYYY'
            }
        });

        $('#property_id').on(
            'change',
            function () {
                // Search button must be clicked.
            }
        );

        $('#printReport').on(
            'click',
            function () {
                window.print();
            }
        );

    });

    /*
    |--------------------------------------------------------------------------
    | Export with booked
    |--------------------------------------------------------------------------
    */
    $(document).on('click', '#exportWithBlockData', function () {

        let propertyId = $("#property_id").val();
        let filterDate = $("#check_date").val();

        if (!propertyId) {
            alert('Please select property.');
            return;
        }

        if (!filterDate) {
            alert('Please select date range.');
            return;
        }

        $.ajax({

            type: "POST",

            url: "{{ url('hall-oprsn') }}",

            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}'
            },

            data: {
                property_id: propertyId,
                filter_date: filterDate,
                request_type: "export_hall_availability_with_booked"
            },

            success: function (data) {

                let response =
                    typeof data === 'string'
                        ? $.parseJSON(data)
                        : data;

                if (response.status == 0) {
                    alert(response.message);
                    return;
                }

                if (response.file_path) {
                    window.location.href =
                        response.file_path;
                } else {
                    alert('Excel file URL not found.');
                }
            },

            error: function (xhr) {

                console.log(
                    xhr.responseText
                );

                alert(
                    'Unable to export hall availability report.'
                );
            }
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Export All
    |--------------------------------------------------------------------------
    */
   $(document).on('click', '#exportAll', function () {

    let filterDate =
        $("#check_date").val();

    if (!filterDate) {

        alert(
            'Please select date range.'
        );

        return;
    }


    $.ajax({

        type: "POST",

        url: "{{ url('hall-oprsn') }}",

        headers: {

            'X-CSRF-Token':
                '{{ csrf_token() }}'
        },

        data: {

            filter_date:
                filterDate,

            request_type:
                "export_all_hall_availability"
        },


        success: function (data) {

            let response;

            try {

                response =
                    typeof data === 'string'
                        ? $.parseJSON(data)
                        : data;

            } catch (error) {

                console.log(
                    'Raw Response:',
                    data
                );

                alert(
                    'Invalid server response.'
                );

                return;
            }


            console.log(
                'Hall Export All:',
                response
            );


            if (
                response.status == 0
            ) {

                alert(
                    response.message
                );

                return;
            }


            if (
                response.file_path
            ) {

                window.location.href =
                    response.file_path;

            } else {

                alert(
                    'Excel file URL not found.'
                );
            }
        },


        error: function (xhr) {

            console.log(
                'Status:',
                xhr.status
            );

            console.log(
                'Response:',
                xhr.responseText
            );


            let message =
                'Unable to export all hall availability report.';


            try {

                let errorResponse =
                    JSON.parse(
                        xhr.responseText
                    );


                if (
                    errorResponse.message
                ) {

                    message =
                        errorResponse.message;
                }

            } catch (e) {

                // Keep default message
            }


            alert(
                message
            );
        }

    });

});

</script>

@endsection