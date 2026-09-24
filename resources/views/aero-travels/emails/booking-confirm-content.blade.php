<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation</title>
</head>

<body style="margin:0; padding:0; background-color:#ffffff; font-family:Arial, Helvetica, sans-serif; color:#000000; font-size:12px;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;">
        <tr>
            <td align="center">

                <table width="650" cellpadding="0" cellspacing="0" border="0"
                       style="width:650px; max-width:650px; background:#ffffff;">

                    <!-- Heading -->
                    <tr>
                        <td style="padding:15px 0 5px 0; font-size:15px; color:#1f4e79;">
                            <span style="font-size:18px;">1.</span>
                            <span style="color:#1f4e79;">Booking Confirmation</span>
                        </td>
                    </tr>


                    <!-- Greeting -->
                    <tr>
                        <td style="padding:0 0 14px 0; line-height:18px;">
                            Dear {{ $orderMaster->customer_name }},
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td style="padding:0 0 14px 0; line-height:18px;">
                            Greetings from Odisha Tourism.
                        </td>
                    </tr>

                    <!-- Confirmation -->
                    <tr>
                        <td style="padding:0 0 12px 0; line-height:18px;">
                            Thank you for choosing IndiaOne Air for your upcoming journey.
                            Your Booking is confirmed from
                            <strong>{{ $departureSourceFrom->city_name ?? 'NA' }}</strong> – <strong>{{ $departureSourceTo->city_name ?? 'NA' }}</strong> @if($flightBooking->booking_type == 'roundtrip') and <strong>{{ $returnSourceTo->city_name ?? 'NA' }}</strong> – <strong>{{ $returnSourceFrom->city_name ?? 'NA' }}</strong> @endif booked under
                            <strong>PNR {{ $flightBooking->pnr_code ?? 'NA' }}.</strong>
                            Ticket copy is attached for details.
                        </td>
                    </tr>

                    <!-- Check-in Information -->
                    <tr>
                        <td style="padding:0 0 14px 0; line-height:18px;">
                            We look forward to welcoming you onboard. Please note Web check-in
                            will open 48 hours before departure.
                        </td>
                    </tr>

                    <!-- Passenger Rights -->
                    <tr>
                        <td style="padding:0 0 15px 0; line-height:18px;">
                            For more information on passenger rights, please visit
                            <a href="https://www.civilaviation.gov.in/ministry-documents/passenger-charter-of-rights"
                               style="color:#1f4e79; text-decoration:underline;">
                                https://www.civilaviation.gov.in/ministry-documents/passenger-charter-of-rights
                            </a>
                        </td>
                    </tr>

                    <!-- Footer Message -->
                    <tr>
                        <td style="padding:0 0 14px 0; line-height:18px;">
                            Odisha Tourism wishes you a safe and pleasant journey.
                        </td>
                    </tr>



                    <!-- Signature -->
                    <tr>
                        <td style="padding:0 0 5px 0; line-height:17px;">
                            With regards,<br>
                            Odisha Tourism
                        </td>
                    </tr>

                    <!-- Logo -->
                    <tr>
                        <td style="padding:5px 0 20px 0;">

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>