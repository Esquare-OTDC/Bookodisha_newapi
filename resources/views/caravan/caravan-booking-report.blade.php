@extends('layouts.app')

@section('title','Caravan Booking Report')

@section('content')

<div class="container-fluid">

    <div class="row page-titles">

        <div class="col-md-6 align-self-center">

            <ol class="breadcrumb">

                <li class="breadcrumb-item">
                    Home
                </li>

                <li class="breadcrumb-item">
                    MIS Report
                </li>

                <li class="breadcrumb-item active">
                    Caravan Booking Report
                </li>

            </ol>

        </div>


        <div class="col-md-6 align-self-center text-right d-none d-md-block">

            <button
                type="button"
                id="exportData"
                class="btn btn-info"
            >
                Export
            </button>

        </div>

    </div>


    @if(Session::has('success'))

        <p
            class="flashMessage"
            style="
                color:#3bbc2e;
                text-align:center;
            "
        >

            {{ Session::get('success') }}

            @php
                Session::forget('success');
            @endphp

        </p>

    @endif


    <div class="row">

        <div class="col-sm-12">

            <div class="white-box">


                {{-- Filters --}}

                <form
                    class="form-inline"
                    id="filterForm"
                    method="GET"
                    action="{{ route('caravan-booking-report') }}"
                >


                    {{-- Caravan --}}

                    <select
                        id="caravan_id"
                        class="form-control"
                        name="caravan_id"
                    >

                        <option value="0">
                            All Caravans
                        </option>


                        @foreach(
                            $MasterCaravan
                            as $key =>
                            $value
                        )

                            <option
                                value="{{ $key }}"

                                {{
                                    $caravan_id == $key
                                        ? 'selected'
                                        : ''
                                }}
                            >

                                {{ $value }}

                            </option>

                        @endforeach

                    </select>


                    {{-- Report Type --}}

                    <select
                        id="report_type"
                        class="form-control"
                        name="report_type"
                    >

                        <option
                            value="book_date"

                            {{
                                $report_type == 'book_date'
                                    ? 'selected'
                                    : ''
                            }}
                        >
                            Booking Date
                        </option>


                        <option
                            value="travel_date"

                            {{
                                $report_type == 'travel_date'
                                    ? 'selected'
                                    : ''
                            }}
                        >
                            Travel Period
                        </option>

                    </select>


                    {{-- Date --}}

                    <input
                        type="text"
                        class="form-control"
                        name="check_date"
                        id="datepicker-autoclose"
                        value="{{ $check_date }}"
                        placeholder="dd-mm-yyyy"
                        autocomplete="off"
                    >


                    {{-- Search --}}

                    <button
                        type="submit"
                        class="btn btn-sm btn-primary"
                        id="search"
                    >
                        Search
                    </button>


                    &nbsp;


                    {{-- Reset --}}

                    <i
                        id="resetSession"
                        class="fa fa-refresh fa-lg md-effect"
                        aria-hidden="true"
                        title="Reset"
                        style="cursor:pointer;"
                    ></i>

                </form>


                {{-- Table --}}

                <div class="table-responsive m-t-10">

                    <table
                        class="
                            display
                            nowrap
                            table
                            table-hover
                            table-bordered
                        "
                    >

                        <thead>

                            <tr>

                                <th>
                                    Sl No
                                </th>

                                <th>
                                    Booking Id
                                </th>

                                <th>
                                    Booking Date
                                </th>

                                <th>
                                    Customer Name
                                </th>

                                <th>
                                    Caravan Name
                                </th>

                                <th>
                                    Travel Period
                                </th>

                                <th>
                                    Total
                                    <br>
                                    Amount
                                </th>

                                <th>
                                    Booking
                                    <br>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach(
                                $CaravanBookingData
                                as $key =>
                                $val
                            )

                                <tr>

                                    <td>
                                        {{ ++$key }}
                                    </td>


                                    {{-- Booking ID + Eye --}}

                                    <td>

                                        @if(
                                            isset($val['link']) &&
                                            $val['link'] == 1
                                        )

                                            <div>

                                                <a
                                                    href="javascript:void(0)"
                                                    class="viewInvoice"
                                                    data-id="{{ $val['oderID'] }}"
                                                    data-platform="website"
                                                    title="View Invoice"
                                                >

                                                    {{ $val['invoice_id'] }}

                                                </a>

                                            </div>


                                            <div>

                                                <a
                                                    href="javascript:void(0)"
                                                    class="viewInvoice"
                                                    data-id="{{ $val['oderID'] }}"
                                                    data-platform="website"
                                                    title="View Invoice"
                                                >

                                                    <i class="fa fa-eye"></i>

                                                </a>

                                            </div>

                                        @else

                                            {{ $val['invoice_id'] }}

                                        @endif

                                    </td>


                                    <td>
                                        {{ $val['booking_date'] }}
                                    </td>


                                    <td>
                                        {{ $val['customer_name'] }}
                                    </td>


                                    <td>
                                        {{ $val['caravan_name'] }}
                                    </td>


                                    <td>
                                        {{ $val['travel_period'] }}
                                    </td>


                                    <td>
                                        {{ $val['total_amount'] }}
                                    </td>
                                    <td>{{ $val['status'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
$(document).ready(function () {
    $('#datepicker-autoclose').datepicker({
        autoclose: true,
        format: "dd-mm-yyyy",
        todayHighlight: true
    });


    $(document).on('click','#resetSession',
        function () {
            window.location.href =window.location.pathname;
        }
    );

    $(document).on('click','.viewInvoice',
        function (event) {
            event.preventDefault();
            var orderID = $(this).data('id');
            var platform =$(this).data('platform');

            if (!orderID) {
                alert('Order ID is missing.');
                return;
            }

            $.ajax({
                type:"POST",
                url:"{{ route('caravan-oprsn') }}",
                data: {
                    _token:"{{ csrf_token() }}",
                    orderID: orderID,
                    platform: platform,
                    request_type:"get_invoice_html"
                },
                success:
                    function (data) {
                        var response = typeof data === 'string' ? JSON.parse(data)  : data;
                        if (response.status == 1) {
                            var invoiceUrl = response.data || response.file_path;
                            if (invoiceUrl) {
                                window.open(invoiceUrl,'_blank');
                            } else {
                                alert('Invoice URL not found.');
                            }

                        } 
                    },
            });

        }
    );

    $(document).on('click', '#exportData', function () {

        let caravanId = $("#caravan_id").val();
        let reportType = $("#report_type").val();
        let checkDate = $("#datepicker-autoclose").val();

        $.ajax({
            type: "POST",
            url: "{{ url('caravan-oprsn') }}",
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}',
            },

            data: {
                request_type: "export_caravan_booking_report",
                caravan_id: caravanId,
                report_type: reportType,
                check_date: checkDate
            },

            success: function (data) {
                var downloadLink = document.createElement("a");
                var blob = new Blob(["\ufeff", data], {type: 'text/csv;charset=utf-8;'});
                var url = URL.createObjectURL(blob);
                downloadLink.href = url;
                downloadLink.download = "Caravan_Booking_Report_" + new Date().getTime() + ".csv";
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                URL.revokeObjectURL(url);

            },

            error: function (xhr) {
                alert('Unable to export Caravan Booking Report.');
            }
        });

    });

});
</script>

@endsection