@extends('layouts.app')

@section('title', 'Check Availability')

@section('content')
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f8;
            color: #333;
        }
        .search-bar-panel {
            background: #ffffff;
            color: #333333;
            padding: 20px 15px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .search-bar-panel label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .search-bar-panel .form-control {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            color: #333333;
            height: 42px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .search-bar-panel .form-control:focus {
            background-color: #ffffff;
            color: #333333;
            border-color: #0078bc;
            box-shadow: 0 0 0 3px rgba(0, 120, 188, 0.15);
        }
        .btn-search {
            background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
            color: #ffffff;
            height: 42px;
            margin-top: 25px;
            font-weight: 600;
            text-transform: uppercase;
            border: none;
            border-radius: 4px;
            width: 100%;
            transition: opacity 0.2s;
        }
        .btn-search:hover, .btn-search:focus {
            opacity: 0.9;
            color: #ffffff;
        }

        /* --- Main Content Layout --- */
        .main-container {
            margin-bottom: 40px;
        }

        /* --- Flight Listing (Using primary brand blue) --- */
        .panel-flights {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .panel-flights h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #0078bc;
            padding-bottom: 10px;
        }
        .flight-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .flight-card:hover {
            border-color: #0078bc;
            background-color: #f0f8ff;
        }
        .flight-card.active {
            border-color: #0078bc;
            background-color: #e6f4fc;
            box-shadow: 0 0 8px rgba(0, 120, 188, 0.2);
        }
        .flight-card.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background-color: #0078bc;
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
        }
        .flight-airline {
            font-weight: 700;
            font-size: 14px;
            color: #0078bc;
        }
        .flight-time {
            font-size: 13px;
            font-weight: 500;
            margin-top: 5px;
        }
        .flight-duration {
            font-size: 11px;
            color: #757575;
        }
        .flight-price {
            font-weight: 700;
            color: #0078bc;
            font-size: 16px;
            text-align: right;
        }

        /* --- Seat Selection Layout --- */
        .panel-seat-layout {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .panel-seat-layout h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #00beda;
            padding-bottom: 10px;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            margin-right: 15px;
        }
        .legend-box {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            margin-right: 6px;
        }
        .legend-available { border: 2px solid #80deea; background-color: #e0f7fa; }
        .legend-selected { background: linear-gradient(to right, #0078bc 1%, #00beda 100%); }
        .legend-booked { background-color: #e0e0e0; cursor: not-allowed; }

        /* The 9-Seater Compact Cabin */
        .plane-cabin {
            background-color: #fafafa;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            padding: 30px 20px;
            max-width: 320px;
            margin: 0 auto;
            position: relative;
        }
        .cabin-nose {
            text-align: center;
            font-size: 11px;
            color: #9e9e9e;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .seat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .row-label {
            font-size: 11px;
            font-weight: bold;
            color: #9e9e9e;
            width: 20px;
            text-align: center;
        }
        .seat {
            width: 38px;
            height: 38px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .seat.available {
            background: #e0f7fa;
            border: 2px solid #80deea;
            color: #0078bc;
            cursor: pointer;
        }

        /* Online booked */
        .seat.online-booked {
            background: #0099cc;
            border: 2px solid #0099cc;
            color: #ffffff;
            cursor: not-allowed;
        }


        /* Offline reserved */
        .seat.offline-reserved {
            background: #d9d9d9;
            border: 2px solid #bfbfbf;
            color: #666666;
            cursor: not-allowed;
        }


        /* User-selected seat */
        .seat.selected {
            background: #0078bc;
            border: 2px solid #0078bc;
            color: #ffffff;
            cursor: pointer;
        }
        .seat.available:hover {
            background-color: #b2ebf2;
            transform: scale(1.05);
        }
        .seat.selected {
            background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
            border: none;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 120, 188, 0.4);
        }
        .seat.booked {
            background-color: #e0e0e0;
            border: 2px solid #bdbdbd;
            color: #9e9e9e;
            cursor: not-allowed;
        }
        .aisle-space {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Summary Panel styling */
        .booking-summary-box {
            margin-top: 25px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
        }
        .btn-book-now {
            background: linear-gradient(to right, #0078bc 1%, #00beda 100%);
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: none;
            margin-top: 15px;
            transition: opacity 0.2s;
        }
        .btn-book-now:hover {
            opacity: 0.9;
            color: #ffffff;
        }
        .btn-book-now[disabled] {
            background: #b0bec5 !important;
            cursor: not-allowed;
            opacity: 1;
        }
        .cabin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .cabin-header h3 {
            flex: 1;
            margin-bottom: 0 !important;
        }

        #downloadPassengerBtn {
            background: linear-gradient(
                to right,
                #0078bc 1%,
                #00beda 100%
            );
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 4px;
            padding: 9px 14px;
        }

        #downloadPassengerBtn:disabled {
            background: #b0bec5;
            cursor: not-allowed;
        }

        @media (max-width: 767px) {
            .cabin-header {
                display: block;
            }

            .cabin-header h3 {
                margin-bottom: 15px !important;
            }

            #downloadPassengerBtn {
                width: 100%;
            }
        }


        @media (max-width: 767px) {
            .search-bar-panel {
                border-radius: 0;
            }
            .btn-search {
                margin-top: 10px;
            }
            .panel-flights {
                margin-bottom: 20px;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-xs-12">
                <div class="search-bar-panel">
                    <form id="searchForm" onsubmit="return false;">
                        @csrf
                        <div class="row">
                            <div class="col-sm-3 col-xs-12 form-group">
                                <label for="fromLoc">
                                    <i class="fa fa-plane-departure" style="color:#0078bc;"></i> From
                                </label>
                                <select id="fromLoc" name="from_airport_id" class="form-control">
                                    <option value=""> Select Departure Airport</option>
                                        @foreach($SelectedCity as $airport)
                                            <option value="{{ $airport->id }}" {{ old('from_city') == $airport->id ? 'selected' : '' }} > {{ $airport->city_name }}
                                            ({{ $airport->airport_code }})
                                            </option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3 col-xs-12 form-group">
                                <label for="toLoc">
                                    <i class="fa fa-plane-arrival" style="color:#00beda;"></i>To
                                </label>
                                <select id="toLoc" name="to_airport_id" class="form-control">
                                    <option value="">Select Destination Airport </option>
                                        @foreach($SelectedCity as $airport)
                                            <option value="{{ $airport->id }}" {{ old('to_city') == $airport->id ? 'selected' : '' }} >
                                                {{ $airport->city_name }}
                                                ({{ $airport->airport_code }})
                                            </option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="col-sm-3 col-xs-12 form-group">
                                <label for="departDate">
                                    <i class="fa fa-calendar" style="color:#0078bc;" ></i> Date
                                </label>
                                <input type="date" id="departDate" name="date" class="form-control" value="{{ date('Y-m-d') }}" >
                            </div>

                            <div class="col-sm-3 col-xs-12">
                                <button type="button" id="searchBtn" class="btn btn-search btn-block" >
                                    <i class="fa fa-search"></i>Search Flights
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row main-container">
            <div class="col-md-4 col-sm-5 col-xs-12">
                <div class="panel-flights">
                    <h3> Available Flights </h3>
                    <div id="flightsContainer">
                    </div>
                </div>
            </div>

            <div class="col-md-8 col-sm-7 col-xs-12">
                <div class="panel-seat-layout">
                    <div class="cabin-header">
                        <h3>
                            Cabin Seating - Flight
                            <span id="currentFlightNo">--</span>
                        </h3>
                        <button type="button" id="downloadPassengerBtn" class="btn btn-primary" disabled>
                            <i class="fa fa-download"></i>
                            Download Passenger List
                        </button>
                    </div>

                    <div class="seat-legend">
                        <span class="legend-item">
                            <span class="legend-box legend-available"></span>
                            Available Online
                        </span>
                        <span class="legend-item">
                            <span class="legend-box legend-selected"></span>
                            Online Booked
                        </span>
                        <span class="legend-item">
                            <span class="legend-box legend-booked"></span>
                            Offline Reserved
                        </span>
                    </div>
                    <div id="inventorySummary" class="inventory-summary" style="display:none;">
                        <div class="row">
                            <div class="col-sm-4 col-xs-6">
                                <div class="summary-item">
                                    <strong> Online Booked: </strong>
                                    <span id="onlineBooked"> 0 </span>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xs-6">
                                <div class="summary-item">
                                    <strong> Online Available:</strong>
                                    <span id="onlineAvailable"> 0 </span>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xs-6">
                                <div class="summary-item">
                                    <strong> Offline Reserved: </strong>
                                    <span id="offlineBooked"> 0 </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="plane-cabin">
                        <div class="cabin-nose">
                            Front / Cockpit
                        </div>
                        <div id="seatMapContainer">
                            <div class="empty-state">
                                <i class="fa fa-chair"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


@endsection
