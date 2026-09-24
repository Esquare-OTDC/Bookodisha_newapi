<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Flight Ticket - Odisha Tourism</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: Arial, Helvetica, sans-serif;
            color: #1a1a1a;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        td {
            font-family: Arial, Helvetica, sans-serif;
        }
        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        .outer-table {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #dcdcdc;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #000000;
        }
        .section-heading {
            font-size: 15px;
            font-weight: bold;
            color: #0b57d0;
            padding-top: 15px;
            padding-bottom: 10px;
            text-decoration: underline;
        }
        .info-label {
            font-size: 14px;
            color: #333333;
            line-height: 1.4;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        .grid-table th {
            border: 1px solid #000000;
            padding: 6px 8px;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            background-color: #ffffff;
            color: #000000;
        }
        .grid-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
            font-size: 13px;
            color: #000000;
            vertical-align: middle;
        }
        .grid-table th.header-bg {
            background-color: #e6e6e6;
        }
        .grid-table td.center {
            text-align: center;
        }
        .grid-table td.left {
            text-align: left;
        }
        .notes-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
        }
        .notes-table th {
            border: 1px solid #000000;
            background-color: #e6e6e6;
            padding: 6px 8px;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
        }
        .notes-table td {
            border: 1px solid #000000;
            padding: 5px 8px;
            font-size: 12px;
            color: #000000;
            line-height: 1.4;
            vertical-align: top;
        }
        .red-note {
            color: #d90000;
            font-weight: bold;
        }
    </style>
</head>

<body style="margin: 0; padding: 20px 0; background-color: #f4f6f9;">
    <!-- Main Email Container Table -->
    <table class="outer-table" width="100%" border="0" cellspacing="0" cellpadding="0" align="center" style="max-width: 800px; margin: 0 auto; background-color: #ffffff; border: 1px solid #dcdcdc;">
        <tr>
            <td style="padding: 25px 30px;">
                <!-- TOP HEADER: PNR & LOGO -->
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="left" valign="middle" style="font-size: 18px; color: #083b8f; font-weight: 900;">
                            <strong>PNR / Booking Reference : {{ $flightBooking->pnr_code ?? 'NA' }}</strong>
                        </td>
                        <td align="right" valign="middle">
                            <!-- IndiaOne Air Logo (or inline styled placeholder) -->
                            <div style="font-family: Arial, sans-serif; text-align: right;">

                            </div>
                        </td>
                    </tr>
                </table>

                <div class="section-heading"
                    style="font-size: 15px; font-weight: bold; color: #0b57d0; padding-top: 20px; padding-bottom: 8px;">
                    Flight Details :-</div>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 15px;">
                    <tr>
                        <!-- ONWARD FLIGHT -->
                        <td width="{{ (isset($flightBooking->booking_type) && $flightBooking->booking_type == 'roundtrip') ? '48%' : '100%' }}"
                            valign="top">
                            @if(isset($flightBooking->booking_type) && $flightBooking->booking_type == 'roundtrip')
                                <div
                                    style="font-size: 14px; font-weight: bold; color: #0b57d0; padding-bottom: 4px; margin-bottom: 6px; border-bottom: 1px solid #0b57d0;">
                                    Onward Flight</div>
                            @endif
                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                <tr>
                                    <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Flight No.
                                            :</strong> {{ $departureFlight->flight_number ?? 'NA' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Date of
                                            Travel :</strong>
                                        {{ date('d-M-y', strtotime($flightBooking->onward_flight_date)) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Departure
                                            :</strong> {{ $departureSourceFrom->city_name ?? 'NA' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Arrival
                                            :</strong> {{ $departureSourceTo->city_name ?? 'NA' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Departure
                                            Time :</strong>
                                        {{ date('h:i A', strtotime($departureSchedule->departure_time)) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Arrival Time
                                            :</strong> {{ date('h:i A', strtotime($departureSchedule->arrival_time)) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                        @if(isset($flightBooking->booking_type) && $flightBooking->booking_type == 'roundtrip')
                            <td width="4%">&nbsp;</td>
                            <!-- RETURN FLIGHT -->
                            <td width="48%" valign="top">
                                <div
                                    style="font-size: 14px; font-weight: bold; color: #0b57d0; padding-bottom: 4px; margin-bottom: 6px; border-bottom: 1px solid #0b57d0;">
                                    Return Flight</div>
                                <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                    <tr>
                                        <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Flight No.
                                                :</strong> {{ $returnFlight->flight_number ?? 'NA' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Date of
                                                Travel :</strong>
                                            {{ date('d-M-y', strtotime($flightBooking->return_flight_date)) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Departure
                                                :</strong> {{ $returnSourceFrom->city_name ?? 'NA' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Arrival
                                                :</strong> {{ $returnSourceTo->city_name ?? 'NA' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Departure
                                                Time :</strong>
                                            {{ date('h:i A', strtotime($returnSchedule->departure_time)) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 14px; color: #000000; line-height: 1.4;"><strong>Arrival Time
                                                :</strong> {{ date('h:i A', strtotime($returnSchedule->arrival_time)) }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        @endif
                    </tr>
                </table>

                <div class="section-heading" style="font-size: 15px; font-weight: bold; color: #0b57d0; padding-top: 10px; padding-bottom: 8px;">Onward Passenger Details :-</div>

                @if (!empty($passenegers))
                <table class="grid-table" width="100%"  style="border-collapse: collapse; border: 1px solid #000000; margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Pax Name</th>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Pax Type</th>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Pax Status</th>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Date of Travel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($passenegers))

                        @foreach ($passenegers as $passenger)
                        @if ($passenger->journey_type == 'DEPARTURE')
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ $passenger->title.' '.$passenger->first_name.' '.$passenger->last_name }}</td>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ $passenger->passenger_type }}</td>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ $passenger->order_status == 1 ? 'Confirmed':'Cancel' }}</td>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ date('d-M-y', strtotime($flightBooking->onward_flight_date)) }}</td>
                        </tr>
                        @endif

                        @endforeach
                        @endif
                    </tbody>
                </table>
                @endif
                @if(isset($flightBooking->booking_type) && $flightBooking->booking_type == 'roundtrip')
                @if (!empty($passenegers))
                <div class="section-heading" style="font-size: 15px; font-weight: bold; color: #0b57d0; padding-top: 10px; padding-bottom: 8px;">Return Passenger Details :-</div>
                <table class="grid-table" width="100%"  style="border-collapse: collapse; border: 1px solid #000000; margin-bottom: 15px;">
                    <thead>
                        <tr>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Pax Name</th>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Pax Type</th>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Pax Status</th>
                            <th align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Date of Travel</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($passenegers))

                        @foreach ($passenegers as $passenger)

                        @if ($passenger->journey_type == 'RETURN')
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ $passenger->title.' '.$passenger->first_name.' '.$passenger->last_name }}</td>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ $passenger->passenger_type }}</td>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ $passenger->order_status == 1 ? 'Confirmed':'Cancel' }}</td>
                            <td align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; text-align: center;">{{ date('d-M-y', strtotime($flightBooking->return_flight_date)) }}</td>
                        </tr>
                        @endif
                        @endforeach
                        @endif
                    </tbody>
                </table>
                @endif
                @endif
                <!-- SECTION 3: CHARGES -->
                <div class="section-heading" style="font-size: 15px; font-weight: bold; color: #0b57d0; padding-top: 10px; padding-bottom: 8px;">Charges :-</div>

                <table class="grid-table" width="100%"  style="border-collapse: collapse; border: 1px solid #000000; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th width="25%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Duration</th>
                            <th width="40%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Cancellation</th>
                            <th width="35%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Reschedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">No Show</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">No refund</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">No Change Allowed</td>
                        </tr>
                        <tr>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">0 - 4 Hours</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">No Change Allowed</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">No Change Allowed</td>
                        </tr>
                        <tr>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">4 Hours to 3 Days</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">INR 1000</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">INR 1000</td>
                        </tr>
                        <tr>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">3 Days to 365 Days</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">INR 1000</td>
                            <td align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">INR 1000</td>
                        </tr>
                    </tbody>
                </table>

                <!-- SECTION 4: BAGGAGE INFORMATION -->
                <table class="grid-table" width="100%"  style="border-collapse: collapse; border: 1px solid #000000; margin-bottom: 25px;">
                    <thead>
                        <tr style="background-color: #ffffff;">
                            <th colspan="4" align="center" style="border: 1px solid #000000; padding: 8px; font-size: 14px; font-weight: bold; text-align: center;">Baggage Information</th>
                        </tr>
                        <tr style="background-color: #ffffff;">
                            <th width="20%" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;"></th>
                            <th width="30%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Hand / Cabin Baggage</th>
                            <th width="30%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Checked In Baggage</th>
                            <th width="20%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; text-align: center;">Excess Baggage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;">Adult</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">One Bag upto 03 Kgs - Free</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">One Bag upto 8 kgs – Free</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">INR 315 per Kg</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;">Child</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">One Bag upto 03 Kgs - Free</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">One Bag upto 8 kgs – Free</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">INR 315 per Kg</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;">Infant</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">One Bag upto 03 Kgs - Free</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">Nil</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">Nil</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;">Maximum Weight</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">Upto 03 Kgs only</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">Upto 21 Kgs per baggage</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">-</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;">Dimension</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">H: 30cm W: 30cm D: 20cm</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">-</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">-</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold;">Note*</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">We recommend placing it under the seat in front.</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">All Check- in baggage must be properly packed in suitable Containers.</td>
                            <td style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px;">The carriage of excess baggage is subject to space availability.</td>
                        </tr>
                    </tbody>
                </table>

                <div style="height: 30px; line-height: 30px;">&nbsp;</div>

                <table class="notes-table" width="100%"  style="border-collapse: collapse; border: 1px solid #000000;">
                    <thead>
                        <tr style="background-color: #e6e6e6;">
                            <th width="8%" align="center" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; background-color: #e6e6e6; text-align: center;">Sr No</th>
                            <th width="92%" align="left" style="border: 1px solid #000000; padding: 6px 8px; font-size: 13px; font-weight: bold; background-color: #e6e6e6; text-align: left;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">1</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Check-In Counter Opening time at airport: At least 2 hours before the scheduled departure</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">2</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Check-In Counter Closing time at airport: 45 minutes before scheduled departure (D-45 min)</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">3</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Web Check-in facility shall be available from 48 hours &amp; up to 2 hours prior to departure.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">4</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Boarding gate closing time at airport: 25 Minutes before scheduled departure. Please report at the departure gate at the indicated Boarding time.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">5</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">All Passengers have to carry valid photo identification with them throughout the journey.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">6</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Flight time are subject to change and applicable regulatory approvals.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">7</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Changes/Cancellations in the bookings can be made only up to 2 hours prior to scheduled departure with payment of change / cancellation fee and difference in fare if applicable.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">8</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">OTDC will not be responsible for any losses incurred by the passengers for transfer / connections to other carriers.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">9</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">OTDC reserves the right without assigning any reason to cancel, divert, terminate, postpone, prepone, reschedule or delay of any flight where we reasonably consider this to be justified by circumstances beyond our control or for safety reasons.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">10</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">OTDC will not be liable in any way for delays/schedule change/cancellations /diversions whether due to bad weather, Government regulation or for instances beyond the control.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">11</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Data Protection Notice: Your personal data will be processed in accordance with the applicable carrier’s privacy policy and, where your booking is made. These are available at https://www.iatatravelcentre.com/privacy.htm you should read this documentation, which applies to your booking and specifies, for example, how your personal data is collected, stored, used, disclosed and transferred..</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">12</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">Dangerous goods are strictly prohibited on flights neither in Hand bag nor in Checked-in Bag . Categories include Explosives, Gases, Flammable Liquids & Solids, Oxidizers, Toxics, Radioactive Items, Corrosives, E-Cigarettes, Lighters, Matchbox, Infection Substances and Miscellaneous items like Dry Ice, Copra/Khopra/Dry Coconut and Magnets.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">13</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">There is no lavatory in the aircraft</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">14</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">MKG belongs to Muskegon County Airport in Norton Shores, Michigan, United States. So wherever MKG is written for Malkangiri, it should be changed to Malkangiri.</td>
                        </tr>
                        <tr>
                            <td align="center" style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px; text-align: center;">15</td>
                            <td style="border: 1px solid #000000; padding: 5px 8px; font-size: 12px;">For BBI to PYB flight, instead of “Direct” you can mention “Via Malkangiri (no change of Aircraft)” – this is optional and for passengers’ information/clarification.</td>
                        </tr>
                    </tbody>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
