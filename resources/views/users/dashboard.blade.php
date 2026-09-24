@extends('layouts.app')

@section('title','Dashboard')

@section('content')

@if(Session::has('success'))
<p class="flashMessage" style="color: #3bbc2e; text-align: center;">
    {{ Session::get('success') }}
    @php
    Session::forget('success');
    @endphp
</p>
@endif
<style>
    .media {
        /*min-height: 120px;*/
    }
</style>
<div class="row m-0">
    <div class="col-md-3 col-sm-6 info-box">
        <div class="media">
            <div class="media-left">
                <span class="icoleaf bg-primary text-white"><i class="mdi mdi-checkbox-marked-circle-outline"></i></span>
            </div>
            <div class="media-body m-b-5">
                <h3 class="info-count text-blue">{{ $Customers }}</h3>
                <p class="info-text font-12">Customers</p>
                <span class="hr-line"></span>
                <!--<p class="info-ot font-15">Target<span class="label label-rounded label-success">300</span></p>-->
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 info-box">
        <div class="media">
            <div class="media-left">
                <span class="icoleaf bg-primary text-white"><i class="mdi mdi-comment-text-outline"></i></span>
            </div>
            <div class="media-body">
                <h3 class="info-count text-blue">{{ $OnlineOrders }}</h3>
                <p class="info-text font-12">Online Bookings</p>
                <span class="hr-line"></span>
                <!--<p class="info-ot font-15">Total Pending<span class="label label-rounded label-danger">154</span></p>-->
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 info-box">
        <div class="media">
            <div class="media-left">
                <span class="icoleaf bg-primary text-white"><i class="mdi mdi-coin"></i></span>
            </div>
            <div class="media-body">
                <h3 class="info-count text-blue">{{ $OfflineOrders }}</h3>
                <p class="info-text font-12">Offline Bookings</p>
                <span class="hr-line"></span>
                <!--<p class="info-ot font-15">March : <span class="text-blue font-semibold">&#36;514578</span></p>-->
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 info-box b-r-0">
        <div class="media">
            <div class="media-left p-r-5">
                <div id="earning" class="e" data-percent="{{ $CompletedPercent }}">
                    <div id="pending" class="p" data-percent="{{ $CancelPercent }}"></div>
                    <!-- <div id="booking" class="b" data-percent="{{ $PendingPercent }}"></div> -->
                </div>
            </div>
            <div class="media-body">
                <!-- <h2 class="text-blue font-22 m-t-0">Report</h2> -->
                <ul class="p-0">
                    <li><i class="fa fa-circle m-r-5 text-primary"></i>{{ $CompletedPercent }}% Completed</li>
                    <li><i class="fa fa-circle m-r-5 text-primary"></i>{{ $CancelPercent }}% Cancelled</li>
                    <!-- <li><i class="fa fa-circle m-r-5 text-info"></i>{{ $PendingPercent }}% Pending</li> -->
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="white-box">
                <div class="row">
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <input class="form-control input-daterange-datepicker" id="daterange" type="text">
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    @if (Auth::user()->access_type == 'vendor' && (in_array('hotel', $VendorServices) || in_array('rental', $VendorServices)))
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="white-box">

                <div class="row">
                    <div class="col-md-6">
                        <h3>Reservation Statistics</h3>
                    </div>
                    <div class="col-md-6">
                    @if (Auth::user()->id == 1)
                    <button type="button" id="exportContactUs" class="btn btn-sm btn-primary pull-right">Export Contact-us Emails</button>
                    @endif
                    </div>
                </div>
                <div class="row m-t-5">
                    <form method="GET">
                        <!-- @csrf -->
                        <div class="col-md-4">
                            <!-- <label>Property</label> -->
                            <input name="daterange" class="form-control input-daterange-datepicker" id="daterange" type="text">
                        </div>
                        <div class="col-md-2">
                            <!-- <label>Service Type</label> -->
                            <select class="form-control" name="service_type" id="service_type">
                                @foreach ($VendorServices as $val)
                                <option value="{{ $val }}" {{ ($val == $service_type) ? 'selected' : '' }}>{{ strtoupper($val) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <!-- <label>Property</label> -->
                            <div id="propSelection">
                                <select class="form-control" name="property[]" id="properties" multiple>
                                    @foreach ($AllProps as $key => $val)
                                    <option value="{{ $key }}" {{ array_key_exists($key, $Properties) ? 'selected' : '' }}>{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
                <div class="row m-t-10">
                    @foreach ($Properties as $key => $val)
                    <div class="col-md-6 m-t-20">
                        <div id="chartContainer{{ $key }}" style="height: 300px; width: 80%;"></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-sm-8">
            <div class="white-box" style="height: 438px;">
                <h5><b>Property Status</b></h5>
                <div class="table-responsive1">
                    <table class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Property Name</th>
                            <th>Available</th>
                            <th>Booked</th>
                            <th>Cancelled</th>
                        </thead>
                        <tbody>
                            @foreach ($ReservationData as $value)
                            <tr>
                                <td>{{ $value['prop_name'] }}</td>
                                <td>{{ $value['totAvailable'] }}</td>
                                <td>{{ $value['totBooked'] }}</td>
                                <td>{{ $value['totalCancelled'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-4">
            <div class="white-box text-center">
            <h5 class="m-t-10"><b>Available Inventory<br>({{ $AvailableTotal }})</b></h5>
                <div class="progress" style="height:24px;margin-top: 15px;">
                    <div class="progress-bar" role="progressbar" aria-valuenow="{{ $AvailableTotal }}" title="Available: {{ $AvailableTotal }}" aria-valuemin="0" aria-valuemax="{{ $AvailTotal }}" style="width:{{ $Availability }}%">
                    {{ $Availability }}%
                    </div>
                </div>
                <!-- <progress id="file" value="{{ $AvailableTotal }}" max="{{ $AvailTotal }}" title="Available: {{ $AvailableTotal }}" style="height: 60px;">{{ $Availability }}%</progress> -->
                <!-- <input data-plugin="knob" data-width="80" data-height="80" data-linecap=round data-fgColor="#00bbd9" value="{{ $Availability }}" data-skin="tron" data-angleOffset="180" data-readOnly="true" data-thickness=".2" /> -->
                
            </div>
            <div class="white-box">
                <div id="totalchartContainer" style="height: 200px; width: 80%;"></div>
            </div>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="white-box">
                <h5><b>Top Bookings</b></h5>
                <div class="table-responsive">
                    <table class="display nowrap table table-hover table-bordered">
                        <thead>
                            <th>Service Type</th>
                            <th>Property Name</th>
                            <th>Booked</th>
                        </thead>
                        <tbody>
                            @foreach ($TopBookings as $value)
                            <tr>
                                <td>{{ $value['service_type'] }}</td>
                                <td>{{ $value['prop_name'] }}</td>
                                <td>{{ $value['booked'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .progress .progress-bar {
        line-height:24px;
        font-size: 14px;
        background: #c0504e;
    }
    .canvasjs-chart-toolbar {
        display: none;
    }

    a.canvasjs-chart-credit {
        display: none;
    }

    .table-responsive1 {
        max-height: 350px;
        overflow-y: auto;
    }

    .multiselect-container>li>a>label.checkbox {
        color: #000 !important;
    }

    .multiselect-container>li>a>label {
        padding: 3px 3px 3px 10px;
    }

    .multiselect-clear-filter {
        background-color: #fff;
        margin-right: 5px;
        color: #b0b0b0;
    }

    .multiselect.dropdown-toggle.btn.btn-default {
        width: 350px !important;
        overflow: hidden;
    }

    .multiselect-container.dropdown-menu {
        width: 350px !important;
        max-height: 250px;
        overflow-y: scroll;
    }

    .multiselect-container .input-group {
        margin: 4px 8px;
    }

    .input-group {
        width: 100% !important;
    }

    .dropdown-menu>.active>a,
    .dropdown-menu>.active>a:focus,
    .dropdown-menu>.active>a:hover {
        background-color: #fff;
    }

    label.checkbox {
        margin-left: 20px !important;
    }

    .checkbox input[type=checkbox] {
        opacity: 1;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>
<script type="text/javascript" src="{{ asset('plugins/components/moment/moment.min.js') }}"></script>
<script src="{{ asset('plugins/components/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('plugins/components/custom-chart/canvasjs.min.js') }}"></script>
<script src="{{ asset('plugins/components/knob/jquery.knob.js') }}"></script>
<script>
    $(function() {
        $('[data-plugin="knob"]').knob();
    });
</script>
<script>
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function() {
        window.history.go(1);
    };
    $('#properties').multiselect({
        includeSelectAllOption: true,
    });
    // $(".multiselect-selected-text").text('Choose Property');
    $('.input-daterange-datepicker').daterangepicker({
        autoApply: true,
        startDate: moment(new Date('{{ $start_date }}')),
        endDate: moment(new Date('{{ $end_date }}')),
        maxDate: moment().add('+120','days'),
        locale: {
            format: 'DD-MM-YYYY'
        }
    });

    $(document).on('change', '#service_type', function() {
        let serviceType = $(this).val();
        $.ajax({
            type: "POST",
            url: "{{url('setting-oprsn')}}",
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}',
            },
            data: {
                serviceType: serviceType,
                request_type: "get_service_property"
            },
            success: function(data) {
                var responce = $.parseJSON(data);
                let properties = responce.data;
                let html = '<select class="form-control" name="property[]" id="properties" multiple>';
                for (const key in properties) {
                    html += '<option value="' + properties[key].id + '">' + properties[key].name + '</option>';
                }
                html += '</select>';
                $('#propSelection').html('');
                $('#propSelection').html(html);
                $('#properties').multiselect({
                    includeSelectAllOption: true,
                });
                $(".multiselect-selected-text").text('Choose Property');
                // $('#room').html('<option value="">Select Room</option>'+ data);
                // $('#hotel_name').val($("#hotel option:selected" ).text());
            }
        });
    });

    var ReservationData = JSON.parse('<?php echo $ReservationDataObj; ?>');
    if (ReservationData) {
        for (const key in ReservationData) {
            let chartContainer = 'chartContainer' + ReservationData[key].prop_id;
            var chart = new CanvasJS.Chart(chartContainer, {
                theme: "light1",
                exportEnabled: true,
                animationEnabled: true,
                title: {
                    text: ReservationData[key].prop_name
                },
                data: [{
                    type: "pie",
                    startAngle: 25,
                    toolTipContent: "<b>{label}</b>: {y}",
                    showInLegend: "true",
                    legendText: "{label}",
                    indexLabelFontSize: 16,
                    indexLabel: "{label}({y})",
                    dataPoints: [{
                            y: ReservationData[key].totBooked,
                            label: "Booked"
                        },
                        {
                            y: ReservationData[key].totAvailable,
                            label: "Available"
                        },

                    ]
                }]
            });
            chart.render();
        }
    }

    // var totchart = new CanvasJS.Chart("totalchartContainer", {
    //     theme: "light1",
    //     exportEnabled: true,
    //     animationEnabled: true,
    //     title: {
    //         text: "Total Bookings"
    //     },
    //     data: [{
    //         type: "pie",
    //         startAngle: 25,
    //         toolTipContent: "<b>{label}</b>: {y}",
    //         showInLegend: "true",
    //         legendText: "{label}",

    //         dataPoints: [
    //             { y: Number('{{ $AvailBooked }}'), label: "Booked" },
    //             { y: Number('{{ $AvailableTotal }}'), label: "Available" },
    //             { y: Number('{{ $BookingCancelled }}'), label: "Cancelled" },

    //         ]
    //     }]
    // });
    var totchart = new CanvasJS.Chart("totalchartContainer", {
        animationEnabled: true,
        title: {
            text: "Total Bookings"
        },
        axisX: {
            interval: 1
        },
        axisY: {
            // title: "Expenses in Billion Dollars",
            includeZero: true,
        },
        data: [{
            type: "bar",
            toolTipContent: "<b>{label}</b>({y})",
            dataPoints: [{
                    label: "Booked",
                    y: Number('{{ $AvailBooked }}'),
                    tooltip: "Total Booked"
                },
                {
                    label: "Available",
                    y: Number('{{ $AvailableTotal }}'),
                    tooltip: "Total Available"
                },
                {
                    label: "Cancelled",
                    y: Number('{{ $BookingCancelled }}'),
                    tooltip: "Total Cancelled"
                },
            ]
        }]
    });
    totchart.render();

    $(document).on('click', '#exportContactUs', function() {
        $.ajax({
            type: "POST",
            url: "{{url('setting-oprsn')}}",
            headers: {
                'X-CSRF-Token': '{{ csrf_token() }}',
            },
            data: {
                request_type: "export_contactus_email"
            },
            success: function(data) {
                var downloadLink = document.createElement("a");
                var blob = new Blob(["\ufeff", data]);
                var url = URL.createObjectURL(blob);
                downloadLink.href = url;
                downloadLink.download = "Contact_us_emails_"+ new Date().getTime() +".csv";  //Name the file here
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        });
    });
</script>

@endsection