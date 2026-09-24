@extends('layouts.app')

@section('title', 'Caravan Availability Report')

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
                    Caravan Availability Report
                </li>

            </ol>

        </div>


        <div class="col-md-6 align-self-center text-right d-none d-md-block">

            <button
                type="button"
                id="exportWithBooked"
                class="btn btn-info"
            >
                Export with booked
            </button>

            <button
                type="button"
                id="exportAll"
                class="btn btn-info"
            >
                Export All
            </button>

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
                    action="{{ route('caravan-availability-report') }}"
                    id="filterForm"
                    class="form-inline"
                >


                    {{-- Caravan Dropdown --}}
                    <select
                        name="caravan_id"
                        id="caravan_id"
                        class="form-control"
                        required
                    >

                        @forelse(
                            $MasterCaravan
                            as $id =>
                            $name
                        )

                            <option
                                value="{{ $id }}"
                                {{
                                    (string) $CaravanId
                                    ===
                                    (string) $id
                                    ?
                                    'selected'
                                    :
                                    ''
                                }}
                            >

                                {{ $name }}

                            </option>

                        @empty

                            <option value="">

                                No caravan found

                            </option>

                        @endforelse

                    </select>


                    {{-- Date Range --}}
                    <input
                        type="text"
                        name="check_date"
                        id="check_date"
                        class="form-control input-daterange-datepicker"
                        autocomplete="off"
                        required
                    >


                    {{-- Search --}}
                    <button
                        type="submit"
                        id="search"
                        class="btn btn-primary"
                    >

                        Search

                    </button>


                    {{-- Reset --}}
                    <a
                        href="{{ route('caravan-availability-report') }}"
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
                        class="table table-hover table-bordered caravan-report-table"
                    >

                        <thead>

                            <tr>

                                <th
                                    colspan="4"
                                    class="report-title"
                                >

                                    Availability status of

                                    {{ $CaravanName ?: 'Caravan' }}

                                    as on

                                    ({{ now()->format('d-m-Y h:i a') }})

                                </th>

                            </tr>


                            <tr>

                                <th class="date-heading">

                                    Date

                                </th>


                                <th>

                                    Available Caravan

                                </th>


                                <th>

                                    Booked Caravan

                                </th>


                                <th>

                                    Total Caravan

                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse(
                                $CaravanReportData
                                as $row
                            )

                                <tr>

                                    <td>

                                        {{ $row['display_date'] }}

                                    </td>


                                    <td>

                                        {{ $row['available'] }}

                                    </td>


                                    <td>

                                        {{ $row['booked'] }}

                                    </td>


                                    <td>

                                        {{ $row['total'] }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="4"
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


    #caravan_id {

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


    .caravan-report-table {

        width: 100%;

        margin-top: 0;

        margin-bottom: 0;

        border-collapse: collapse;

        table-layout: fixed;

        color: #7f8b95;

        font-size: 14px;

    }


    .caravan-report-table th,
    .caravan-report-table td {

        padding: 8px 10px;

        border: 1px solid #dfe5e8 !important;

        text-align: center;

        vertical-align: middle !important;

        line-height: 20px;

    }


    .caravan-report-table thead th {

        color: #111111;

        background-color: #ffffff;

        font-weight: 500;

    }


    .caravan-report-table .report-title {

        padding: 8px 10px;

        color: #111111;

        background-color: #f6f8fa;

        font-size: 14px;

        font-weight: 600;

        text-align: center;

    }


    .caravan-report-table tbody td {

        color: #89949e;

        background-color: #ffffff;

        font-weight: 400;

    }


    .caravan-report-table tbody tr:hover td {

        background-color: #f7f9fa;

    }


    .caravan-report-table .date-heading {

        width: 25%;

    }


    .white-box .table-responsive {

        margin-top: 0;

    }


    @media screen and (max-width: 767px) {

        #caravan_id,
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


        .caravan-report-table {

            min-width: 650px;

        }

    }

</style>


<script>

$(document).ready(function () {


    /*
    |--------------------------------------------------------------------------
    | Date Range Picker
    |--------------------------------------------------------------------------
    */
    $('.input-daterange-datepicker')
        .daterangepicker({

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

                format:
                    'DD-MM-YYYY'

            }

        });


    /*
    |--------------------------------------------------------------------------
    | Export with booked
    |--------------------------------------------------------------------------
    |
    | Backend can be connected later.
    |
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '#exportWithBooked',
        function () {

            let caravanId =
                $("#caravan_id").val();

            let filterDate =
                $("#check_date").val();


            if (!caravanId) {

                alert(
                    'Please select caravan.'
                );

                return;
            }


            if (!filterDate) {

                alert(
                    'Please select date range.'
                );

                return;
            }


            alert(
                'Caravan Export with booked functionality will be implemented.'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Export All
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '#exportAll',
        function () {

            let filterDate =
                $("#check_date").val();


            if (!filterDate) {

                alert(
                    'Please select date range.'
                );

                return;
            }


            alert(
                'Caravan Export All functionality will be implemented.'
            );

        }
    );


});

</script>

@endsection