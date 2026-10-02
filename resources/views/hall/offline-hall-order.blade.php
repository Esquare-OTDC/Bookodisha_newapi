@extends('layouts.app')

@section('title', 'Create Offline Hall Order')

@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    Home
                </li>

                <li class="breadcrumb-item">
                    Hall
                </li>

                <li class="breadcrumb-item active">
                    Create Offline Hall Order
                </li>
            </ol>

        </div>
    </div>

    @if(Session::has('success'))
        <div class="alert alert-success text-center">
            {{ Session::get('success') }}
        </div>
    @endif

    @if(Session::has('failure'))
        <div class="alert alert-danger text-center">
            {{ Session::get('failure') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2>
                    Create Offline Hall Order
                </h2>
            </div>

            <form action="{{ route('create-offline-hall-order') }}" method="POST" id="offlineHallOrderForm">
                @csrf

                <input type="hidden" name="request_type" id="request_type" value="create_hall_order">
                <input type="hidden" name="selected_hall_details" id="selected_hall_details">
                <input type="hidden" name="hall_details" id="hall_details">
                <input type="hidden" name="hall_id" id="hall_id">
                <input type="hidden" name="slot_id" id="slot_id">
                <input type="hidden" name="booking_type" id="booking_type">
                <input type="hidden" name="available_half" id="available_half">
                <input type="hidden" name="start_date" id="start_date">
                <input type="hidden" name="end_date" id="end_date">
                <input type="hidden" name="total_days" id="total_days" value="1">
                <input type="hidden" name="total_halls" id="total_halls" value="0">
                <input type="hidden" name="hall_price" id="hall_price" value="0">
                <input type="hidden" name="total_service_price" id="total_service_price"  value="0">
                <input type="hidden" name="sub_total_price" id="sub_total_price" value="0">
                <input type="hidden" name="tax_amount" id="tax_amount" value="0">
                <input type="hidden" name="tax_percentage"  id="tax_percentage" value="0">
                <input type="hidden" name="total_order_price" id="total_order_price" value="0">

                {{-- Hall section --}}
                <div class="white-box hall-section">
                    <h3 class="box-title">
                        Hall Details
                    </h3>
                    <hr>

                    <div class="row">
                        {{-- Property --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>
                                    Property
                                </label>
                                <span class="required-field">
                                    *
                                </span>
                                <select class="form-control" id="property_id" name="property_id" required>
                                    <option value="">
                                        Select Property
                                    </option>
                                    @foreach(($MasterProperty ?? []) as $propertyId => $propertyName)
                                        <option value="{{ $propertyId }}" {{ old('property_id') == $propertyId ? 'selected' : '' }}>
                                            {{ $propertyName }}
                                        </option>
                                    @endforeach
                                </select>

                                @if($errors->has('property_id'))
                                    <span class="text-danger">
                                        {{ $errors->first('property_id') }}
                                    </span>
                                @endif

                            </div>
                        </div>

                        {{-- Hall Category --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label> Hall Category </label>
                                <span class="required-field"> * </span>
                                <select class="form-control" id="hall_category_id" name="hall_category_id" required>
                                    <option value="">
                                        Select Hall Category
                                    </option>

                                    @foreach(($hallCategory ?? []) as $categoryId => $categoryName)
                                        <option value="{{ $categoryId }}" {{ old('hall_category_id') == $categoryId ? 'selected' : '' }}>
                                            {{ $categoryName }}
                                        </option>
                                    @endforeach
                                </select>

                                @if($errors->has('hall_category_id'))
                                    <span class="text-danger">
                                        {{ $errors->first('hall_category_id') }}
                                    </span>
                                @endif

                            </div>

                        </div>

                        {{-- Book From --}}
                        @if( Auth::user()->role == 3 && !empty(Auth::user()->user_book_from) && Auth::user()->user_book_from != 'both')
                            <input type="hidden" name="book_from" id="book_from" value="{{ Auth::user()->user_book_from }}">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>
                                        Book From
                                    </label>

                                    <input type="text" class="form-control" value="{{ ucfirst(Auth::user()->user_book_from) }} Inventory"readonly>

                                </div>
                            </div>
                        @else

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label> Book From </label>
                                    <span class="required-field"> * </span>
                                    <select class="form-control" name="book_from" id="book_from" required>
                                        <option value="live" {{ old('book_from', 'live') == 'live' ? 'selected' : '' }} >
                                            Live Inventory
                                        </option>

                                        <option value="blocked" {{ old('book_from') == 'blocked' ? 'selected' : '' }} >
                                            Blocked Inventory
                                        </option>
                                    </select>
                                </div>
                            </div>
                        @endif

                        {{-- Booking Date --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label> Booking Date </label>
                                <span class="required-field"> * </span>
                                <input type="text" class="form-control" name="check_date" id="check_date" value="{{ old('check_date', date('d M Y')) }}" autocomplete="off" readonly required>

                                @if($errors->has('check_date'))
                                    <span class="text-danger">
                                        {{ $errors->first('check_date') }}
                                    </span>
                                @endif

                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Slot</label><span class="required-field">*</span>
                                <select class="form-control" name="slot_type" id="slot_type">
                                    <option value="FULL_DAY">FULL DAY</option>
                                    <option value="FIRST_HALF">FIRST HALF</option>
                                    <option value="SECOND_HALF">SECOND HALF</option>
                                </select>
                            </div>
                        </div>
                        {{-- Check Availability --}}
                        <div class="col-md-4">
                            <div class="form-group availability-button-section">
                                <button type="button" class="btn btn-info" id="checkAvailability"></i>
                                    Check Availability
                                </button>
                            </div>
                        </div>
                    </div>


                    <div id="availabilityLoader" class="text-center" style="display:none;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>
                            Checking Hall availability...
                        </p>
                    </div>

                    <div id="availabilityMessage" style="display:none;"></div>
                    <div class="hall-result-section" style="display:none;">
                        <hr>

                        <h3 class="box-title">
                            Available Halls
                        </h3>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:45%;">
                                            Hall Details
                                        </th>

                                        <th style="width:55%;">
                                            Slot and Price
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="hallData"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- User section --}}
                <div class="white-box user-section" style="display:none;">
                    <h3 class="box-title"> User Details </h3>
                    <hr>

                    <div class="row">
                        <div class="col-md-8">
                            {{-- Name --}}
                            <div class="form-group">
                                <label> Name </label>
                                <span class="required-field"> * </span>
                                <input type="text" class="form-control" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" required>

                                @if($errors->has('customer_name'))
                                    <span class="text-danger">
                                        {{ $errors->first('customer_name') }}
                                    </span>
                                @endif

                            </div>

                            {{-- Email --}}
                            <div class="form-group">
                                <label> Email </label>
                                <span class="required-field">*</span>
                                <input type="email" class="form-control" name="customer_email" id="customer_email" value="{{ old('customer_email') }}" required>

                                @if($errors->has('customer_email'))
                                    <span class="text-danger">
                                        {{ $errors->first('customer_email') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Phone --}}
                            <div class="form-group">
                                <label> Phone </label>
                                <span class="required-field"> * </span>
                                <input type="text" class="form-control" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" maxlength="10" required>

                                @if($errors->has('customer_phone'))
                                    <span class="text-danger">
                                        {{ $errors->first('customer_phone') }}
                                    </span>
                                @endif

                            </div>

                            {{-- Address --}}
                            <div class="form-group">
                                <label> Address </label>
                                <input type="text" class="form-control payment-required-field" name="customer_address1" id="customer_address1" value="{{ old('customer_address1') }}">
                            </div>

                            {{-- Country --}}
                            <div class="form-group">
                                <label> Country </label>
                                <select class="form-control payment-required-field" name="customer_country" id="customer_country">

                                    <option value="">
                                        Select Country
                                    </option>

                                    @foreach(($CountryData ?? []) as $countryKey => $country)
                                        @php
                                            if (is_object($country)) {
                                                $countryId = $country->id ?? $country->country_id ?? $countryKey;

                                                $countryName = $country->country_name ?? $country->name ?? '';
                                            } else {
                                                $countryId = $countryKey;
                                                $countryName = $country;
                                            }
                                        @endphp

                                        <option value="{{ $countryName . '~' . $countryId }}">
                                            {{ $countryName }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>

                            {{-- State --}}
                            <div class="form-group">
                                <label> State </label>
                                <select class="form-control payment-required-field" name="customer_state" id="customer_state">
                                    <option value="">
                                        Select State
                                    </option>
                                </select>
                            </div>

                            {{-- City --}}
                            <div class="form-group">
                                <label>
                                    City
                                </label>

                                <select class="form-control payment-required-field" name="customer_city" id="customer_city">
                                    <option value="">
                                        Select City
                                    </option>
                                </select>
                            </div>

                            {{-- Zip Code --}}
                            <div class="form-group">
                                <label> Zip Code </label>
                                <input type="text" class="form-control payment-required-field" name="customer_zipcode" id="customer_zipcode" value="{{ old('customer_zipcode') }}" maxlength="6">
                            </div>

                            {{-- GST --}}
                            <div class="form-group">
                                <label>
                                    GST Registration Number
                                </label>

                                <input type="text" class="form-control" name="gst_regd_no" id="gst_regd_no" value="{{ old('gst_regd_no') }}">
                            </div>

                            {{-- Company Name --}}
                            <div class="form-group">
                                <label>
                                    Registered Company Name
                                </label>

                                <input type="text" class="form-control" name="gst_company_name" id="gst_company_name" value="{{ old('gst_company_name') }}">
                            </div>

                            {{-- Company Address --}}
                            <div class="form-group">
                                <label> Registered Company Address</label>

                                <input type="text" class="form-control" name="gst_company_address" id="gst_company_address" value="{{ old('gst_company_address') }}">

                            </div>

                            {{-- Booking Narration --}}
                            <div class="form-group">
                                <label> Booking Narration</label>
                                <span class="required-field"> * </span>

                                <textarea class="form-control" name="book_naration" id="book_naration" rows="3"  maxlength="100"  placeholder="Please enter MR number, date or payment details"  required>{{ old('book_naration') }}</textarea>

                                @if($errors->has('book_naration'))
                                    <span class="text-danger">
                                        {{ $errors->first('book_naration') }}
                                    </span>
                                @endif

                            </div>

                            {{-- Payment Method --}}
                            <div class="form-group">
                                <label> Payment Method </label>
                                <span class="required-field"> *</span>

                                <select class="form-control" name="payment_gateway" id="payment_gateway" required>
                                    <option value=""> Select Payment Method</option>

                                    <option value="cash" {{ old('payment_gateway') == 'cash' ? 'selected' : '' }}> Cash </option>

                                    <option value="upi" {{ old('payment_gateway') == 'upi' ? 'selected' : '' }}> UPI </option>

                                    <option value="card" {{ old('payment_gateway') == 'card' ? 'selected' : '' }}> Card </option>

                                    <!-- <option value="hdfc" {{ old('payment_gateway') == 'hdfc' ? 'selected' : '' }} >  HDFC </option> -->

                                    <option value="credit" {{ old('payment_gateway') == 'credit' ? 'selected' : '' }}> Credit </option>

                                </select>

                                @if($errors->has('payment_gateway'))
                                    <span class="text-danger">
                                        {{ $errors->first('payment_gateway') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Booking summary --}}
                        <div class="col-md-4">
                            <div class="booking-summary">
                                <h4 class="text-center">
                                    <u> Booking Details </u>
                                </h4>
                                <div id="bookingDetails"></div>
                                <hr>

                                <h4 class="text-center">
                                    <u> Payment Breakup</u>
                                </h4>

                                <div id="priceDetails"></div>

                            </div>
                        </div>

                        <div class="col-md-12 form-action-section">
                            <button type="button" class="btn btn-default" id="backToHall">
                                Back
                            </button>

                            <button type="submit" class="btn btn-primary" id="bookNow">
                                Book Now
                            </button>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .required-field {
        color: red;
    }

    .availability-button-section {
        padding-top: 26px;
    }

    #availabilityLoader {
        padding: 25px;
    }

    #availabilityLoader p {
        margin-top: 10px;
    }

    #availabilityMessage {
        margin-top: 15px;
        text-align: center;
    }

    .hall-result-section {
        margin-top: 15px;
    }

    .hall-name {
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 600;
    }

    .hall-information {
        margin: 0;
        color: #666;
    }

    .slot-price-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .hall-slot-dropdown {
        margin-bottom: 10px;
    }

    .confirm-hall-btn {
        margin-top: 5px;
    }

    .booking-summary {
        border: 1px solid #ddd;
        padding: 15px;
        background: #fff;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 12px;
    }

    .summary-label {
        font-weight: 600;
    }

    .summary-value {
        text-align: right;
    }

    .form-action-section {
        margin-top: 20px;
    }
</style>

<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>


<script>
    $(document).ready(function () {

        var selectedHall = null;
        var today = moment().startOf('day');

        $('#check_date').daterangepicker({
            singleDatePicker: true,
            autoApply: true,
            minDate: today,
            startDate: today,
            locale: {
                format: 'DD MMM YYYY'
            }
        });

        $('#check_date').val(
            today.format('DD MMM YYYY')
        );

        updateHiddenDates();

        $('#check_date').on('apply.daterangepicker',
            function (event, picker) {

                var selectedDate =
                    picker.startDate.format('DD MMM YYYY');

                $(this).val(selectedDate);

                updateHiddenDates();

                resetHallSelection();
                hideHallResults();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Update Hidden Date Fields
        |--------------------------------------------------------------------------
        | Hall booking is for ONE day only.
        |--------------------------------------------------------------------------
        */

        function updateHiddenDates() {
            var checkDate = $('#check_date').val();
            if (!checkDate) {
                return;
            }

            var selectedDate = moment(checkDate,'DD MMM YYYY', true);

            if (!selectedDate.isValid()) {
                return;
            }

            var formattedDate = selectedDate.format('YYYY-MM-DD');

            $('#start_date').val(
                formattedDate
            );

            $('#end_date').val(
                formattedDate
            );

            $('#total_days').val(1);
        }


        /*
        |--------------------------------------------------------------------------
        | Property / Category / Book From Change
        |--------------------------------------------------------------------------
        */

        $('#property_id, #hall_category_id, #book_from').on('change', function () {
                resetHallSelection();
                hideHallResults();
            }
        );


        function hideHallResults() {
            $('.hall-result-section').hide();
            $('#hallData').html('');
            $('#availabilityMessage').hide().html('');
        }

        /*
        |--------------------------------------------------------------------------
        | Check Hall Availability
        |--------------------------------------------------------------------------
        */

        $('#checkAvailability').on('click',function () {
            var propertyId = $('#property_id').val();
            var hallCategoryId = $('#hall_category_id').val();
            var bookFrom = $('#book_from').val();
            var checkDate = $('#check_date').val();
            var slotType = $('#slot_type').val();

            if (!propertyId) {
                alert('Please select a property.');
                return;
            }

            if (!hallCategoryId) {
                alert('Please select Hall Category.');
                return;
            }

            if (!bookFrom) {
                alert('Please select Book From.');
                return;
            }

            if (!checkDate) {
                alert('Please select Booking Date.');
                return;
            }

            updateHiddenDates();
            resetHallSelection();
            $('#availabilityLoader').show();
            $('#availabilityMessage').hide().html('');
            $('.hall-result-section').hide();
            $('#hallData').html('');

            requestHallAvailability(function (response) {
                $('#availabilityLoader').hide();

                var hallItems = getAvailabilityItems(response);
                    if (!isSuccessResponse(response) || hallItems.length === 0) {
                        showAvailabilityMessage( response.message || 'No Hall is available for the selected date.','warning'
                        );
                        return;
                    }
                renderHallRows(hallItems);
            },

            function (message) {
                $('#availabilityLoader').hide();
                showAvailabilityMessage(message,'danger');
            }
            );
        });


        /*
        |--------------------------------------------------------------------------
        | AJAX - Check Hall Availability
        |--------------------------------------------------------------------------
        */

        function requestHallAvailability(successCallback,errorCallback) {
            $.ajax({

                type: 'POST',
                url: "{{ route('create-offline-hall-order') }}",
                data: {

                    _token: "{{ csrf_token() }}",
                    request_type: 'check_hall_availability',
                    property_id: $('#property_id').val(),
                    hall_category_id: $('#hall_category_id').val(),
                    book_from: $('#book_from').val(),
                    check_date: $('#check_date').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    slot_type: $('#slot_type').val()

                },

                success: function (response) {
                    successCallback(parseAjaxResponse(response));
                },

                error: function (xhr) {
                    errorCallback(getAjaxError(xhr,'Unable to check Hall availability.'));
                }
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Render Hall Rows
        |--------------------------------------------------------------------------
        */

        function renderHallRows(items) {
            var groupedHalls = {};
            var selectedSlotType = $('#slot_type').val();

            $.each(items,function (index, item) {
                var hallId = parseInt(item.hall_id || item.id || 0);

                if (!hallId) {
                    return;
                }

                if (!groupedHalls[hallId]) {
                    groupedHalls[hallId] = {
                        hall_id:hallId,
                        hall_name: item.hall_name || item.name || '',
                        hall_type: item.hcategory_name || item.hall_type || item.category_name || '',
                            slots: []
                        };
                }

                createSlots(groupedHalls[hallId].slots,item);
            });


            var html = '';

            $.each(groupedHalls,function (hallId, hall) {
                var selectedSlot = null;
                $.each(hall.slots,function (slotIndex,slot) {
                    if (slot.slot_type === selectedSlotType) {
                        selectedSlot = slot;
                        return false;
                    }
                });

                if (!selectedSlot) {
                    return;
                }

                var displaySlotName = selectedSlotType === 'FULL_DAY' ? 'Full Day' : selectedSlotType === 'FIRST_HALF' ? 'First Half' : 'Second Half';

                    html +=
                        '<tr>' +
                            '<td>' +
                                '<h4 class="hall-name">' +
                                    escapeHtml(
                                        hall.hall_name
                                    ) +
                                '</h4>' +

                                (hall.hall_type ? '<p class="hall-information">' + escapeHtml(hall.hall_type) + '</p>' : '') +
                            '</td>' +
                            '<td>' +
                                '<span class="slot-price-label">' +
                                    'Price: ₹' +
                                    parseFloat(
                                        selectedSlot.unit_price || 0
                                    ).toFixed(2) +' (' + displaySlotName + ')' +
                                '</span>' +
                                '<button ' +'type="button" ' + 'class="btn btn-primary btn-block confirm-hall-btn" ' + 'data-hall-id="' + hall.hall_id + '" ' +
                                    'data-hall-name="' + escapeAttribute(hall.hall_name) +'" ' +
                                    'data-hall-type="' + escapeAttribute(hall.hall_type) + '" ' +
                                    'data-slot-id="' + selectedSlot.slot_id + '" ' +
                                    'data-slot-type="' + selectedSlot.slot_type + '" ' +
                                    'data-slot-label="' + displaySlotName + '" ' +
                                    'data-booking-type="' + selectedSlot.booking_type + '" ' +
                                    'data-available-half="' + selectedSlot.available_half + '" ' +
                                    'data-unit-price="' + selectedSlot.unit_price + '">' +
                                    'Confirm Hall' +
                                '</button>' +
                            '</td>' +
                        '</tr>';
                }
            );


            if (!html) {
                showAvailabilityMessage('No Hall slot is available for the selected date.','warning');
                return;
            }

            $('#hallData')
                .html(html);

            $('.hall-result-section')
                .show();
        }

        /*
        |--------------------------------------------------------------------------
        | Create Slot Options
        |--------------------------------------------------------------------------
        */

        function createSlots(slotList,item) {
            var slotType = normaliseToken(item.booking_type || item.slot_type || item.slotType || '');
            var availableHalf = normaliseToken(item.available_half || item.availableHalf || '');
            var slotId = item.slot_id || item.slotId || item.id || 0;

            if (slotType === 'FULL_DAY') {
                addUniqueSlot(slotList,{
                    slot_id:slotId,
                    slot_type: 'FULL_DAY',
                    slot_label:  'Full Day',
                    booking_type: item.booking_type || 'FULL_DAY',
                    available_half: 'FULL_DAY',
                    unit_price: getSlotPrice(item,'FULL_DAY')
                });
                return;
            }

            if (slotType === 'FIRST_HALF' || availableHalf === 'FIRST_HALF') {
                addUniqueSlot(slotList,{
                    slot_id: slotId,
                    slot_type: 'FIRST_HALF',
                    slot_label: 'First Half',
                    booking_type: item.booking_type || 'HALF_DAY',
                    available_half: 'FIRST_HALF',
                    unit_price: getSlotPrice(item,'FIRST_HALF')
                });
            }

            if (slotType === 'SECOND_HALF' || availableHalf === 'SECOND_HALF') {
                addUniqueSlot(slotList,{
                    slot_id: slotId,
                    slot_type: 'SECOND_HALF',
                    slot_label: 'Second Half',
                    booking_type: item.booking_type || 'HALF_DAY',
                    available_half: 'SECOND_HALF',
                    unit_price: getSlotPrice(item,'SECOND_HALF')
                });
            }

            if (slotType === 'HALF_DAY' && (availableHalf === 'FIRST_HALF_OR_SECOND_HALF' || availableHalf ==='BOTH' || availableHalf === 'FULL_DAY' || availableHalf === '')) {

                addUniqueSlot(slotList,{
                    slot_id: slotId,
                    slot_type:'FIRST_HALF',
                    slot_label:'First Half',
                    booking_type: item.booking_type || 'HALF_DAY',
                    available_half: 'FIRST_HALF',
                    unit_price: getSlotPrice(item,'FIRST_HALF')
                });

                addUniqueSlot(slotList,{
                    slot_id: slotId,
                    slot_type:'SECOND_HALF',
                    slot_label: 'Second Half',
                    booking_type: item.booking_type || 'HALF_DAY',
                    available_half:'SECOND_HALF',
                    unit_price: getSlotPrice(item,'SECOND_HALF')
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Slot Price
        |--------------------------------------------------------------------------
        */

        function getSlotPrice(item,slotType) {
            var price = 0;
            if (slotType === 'FULL_DAY') {
                price =item.full_day_price || item.price || item.slot_price || item.sell_price || 0;
            }

            if (slotType === 'FIRST_HALF') {
                price =item.first_half_price || item.half_day_price || item.price || item.slot_price || item.sell_price || 0;
            }

            if (slotType === 'SECOND_HALF') {
                price = item.second_half_price || item.half_day_price || item.price || item.slot_price || item.sell_price || 0;
            }

            price = parseFloat(price);
            
            return isNaN(price) ? 0 : price;
        }


        /*
        |--------------------------------------------------------------------------
        | Add Unique Slot
        |--------------------------------------------------------------------------
        */

        function addUniqueSlot(slotList,newSlot) {
            var exists = slotList.some(function (slot) {
                return (
                    String(slot.slot_id) === String(newSlot.slot_id) && String(slot.slot_type) === String(newSlot.slot_type)
                );
            });

            if (!exists) {
                slotList.push(newSlot);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Confirm Hall
        |--------------------------------------------------------------------------
        */

        $(document).on('click','.confirm-hall-btn',function () {
            var button = $(this);
            var hallId = parseInt(button.data('hall-id'));
            var slotId = parseInt(button.attr('data-slot-id'));
            var slotType = button.attr('data-slot-type');
            var slotLabel = button.attr('data-slot-label');
            var bookingType = button.attr('data-booking-type');
            var availableHalf = button.attr('data-available-half');
            var unitPrice = parseFloat(button.attr('data-unit-price') || 0);


            if (!slotId) {
                alert('Selected Hall slot is invalid.');
                return;
            }

            var hallToConfirm = {
                hall_id:hallId,
                hall_name: button.data('hall-name') || '',
                hall_type: button.data('hall-type') || '',
                slot_id:slotId,
                slot_type:slotType,
                slot_label:slotLabel,
                booking_type: bookingType,
                available_half: availableHalf,
                unit_price: unitPrice,
                total_price: unitPrice,
                days: 1
            };

            var bookFromText = getBookFromText();
            var confirmationMessage = 'Are you sure you want to book from ' + bookFromText + '?';

            if (!window.confirm(confirmationMessage)) {
                return;
            }

            button.prop('disabled',true)
                    .html(
                        '<i class="fa fa-spinner fa-spin"></i> ' +
                        'Checking...'
                    );

                requestHallAvailability(
                    function (response) {
                        button.prop('disabled',false).text('Confirm Hall');
                        var items = getAvailabilityItems(response);

                        if (!isSuccessResponse(response) || items.length === 0) {
                            alert(response.message || 'The selected Hall is no longer available.');
                            return;
                        }

                        if (!isSelectedSlotAvailable(items,hallToConfirm)) {

                            alert(hallToConfirm.hall_name + ' - ' + hallToConfirm.slot_label + ' is no longer available.');
                            return;
                        }

                        selectedHall = hallToConfirm;

                        storeSelectedHallDetails();
                        renderBookingSummary();


                        $('.hall-section').hide(500);
                        $('.user-section').show(500);
                        $('html, body').animate({scrollTop: $('.user-section') .offset() .top - 80},500);
                    },


                    function (message) {
                        button.prop('disabled',false).text('Confirm Hall');
                        alert(message);
                    }
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Check Selected Slot
        |--------------------------------------------------------------------------
        */

        function isSelectedSlotAvailable(items,hallToConfirm) {
            var available = false;

            $.each(items,function (index, item) {
                var responseHallId = parseInt(item.hall_id || item.id || 0);

                if (responseHallId !== parseInt(hallToConfirm.hall_id)) {
                    return;
                }

                var itemSlots = [];
                createSlots(itemSlots,item);
                    $.each(itemSlots,function (slotIndex,slot) {
                        if (parseInt(slot.slot_id) === parseInt(hallToConfirm.slot_id) && String(slot.slot_type) === String(hallToConfirm.slot_type)) {

                                available = true;
                            }
                        }
                    );
                }
            );

            return available;
        }


        /*
        |--------------------------------------------------------------------------
        | Store Selected Hall
        |--------------------------------------------------------------------------
        */

        function storeSelectedHallDetails() {

            if (!selectedHall) {
                clearSelectedHallInputs();
                return;
            }

            var hallArray = [selectedHall];

            $('#selected_hall_details').val(JSON.stringify( hallArray));
            $('#hall_details').val(JSON.stringify( hallArray));
            $('#hall_id').val(selectedHall.hall_id);
            $('#slot_id').val(selectedHall.slot_id);
            $('#slot_type').val(selectedHall.slot_type);
            $('#booking_type').val(selectedHall.booking_type);
            $('#available_half').val(selectedHall.available_half);

            $('#total_halls').val(1);

            updatePriceInputs(selectedHall.total_price);
        }


        /*
        |--------------------------------------------------------------------------
        | Clear Selected Hall
        |--------------------------------------------------------------------------
        */

        function clearSelectedHallInputs() {
            $('#selected_hall_details').val('');
            $('#hall_details').val('');
            $('#hall_id').val('');
            $('#slot_id').val('');
            $('#booking_type').val('');
            $('#available_half').val('');
            $('#total_halls').val(0);

            updatePriceInputs(0);
        }


        /*
        |--------------------------------------------------------------------------
        | Booking Summary
        |--------------------------------------------------------------------------
        */

        function renderBookingSummary() {

            if (!selectedHall) {
                return;
            }

            var propertyName = $('#property_id option:selected').text().trim().replace(/\s+/g,' ');
            var bookingDetails = '';

            bookingDetails += createSummaryRow('Property',propertyName);
            bookingDetails += createSummaryRow('Booking Date', $('#check_date').val());
            bookingDetails += createSummaryRow('Hall', selectedHall.hall_name);
            bookingDetails += createSummaryRow('Hall Type',selectedHall.hall_type);
            bookingDetails += createSummaryRow('Slot', selectedHall.slot_label);
            bookingDetails += createSummaryRow('Number of Days','1');

            $('#bookingDetails').html(bookingDetails);

            var priceDetails = '';

            priceDetails += createSummaryRow('Hall Price', '₹' + parseFloat(selectedHall.total_price || 0).toFixed(2));

            priceDetails += createSummaryRow('GST','₹0.00');

            priceDetails += createSummaryRow('Total Price', '₹' + parseFloat(selectedHall.total_price || 0 ).toFixed(2));

            $('#priceDetails') .html( priceDetails);
        }


        /*
        |--------------------------------------------------------------------------
        | Summary Row
        |--------------------------------------------------------------------------
        */

        function createSummaryRow(label,value) {
            return (
                '<div class="summary-row">' +
                    '<span class="summary-label">' +
                        escapeHtml(
                            label
                        ) +
                    '</span>' +
                    '<span class="summary-value">' +
                        escapeHtml(
                            value
                        ) +
                    '</span>' +
                '</div>'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Back To Hall
        |--------------------------------------------------------------------------
        */

        $('#backToHall').on('click',function () {
            selectedHall = null;
            clearSelectedHallInputs();

            $('#bookingDetails').html('');
            $('#priceDetails').html('');
            $('.user-section').hide(500);
            $('.hall-section').show(500);
            $('html, body').animate({scrollTop: $('.hall-section') .offset() .top - 80},500);
        });


        /*
        |--------------------------------------------------------------------------
        | Country -> State
        |--------------------------------------------------------------------------
        */

        $('#customer_country').on('change',function () {
                var countryValue = $(this).val();
                $('#customer_state').html( '<option value="">' + 'Select State' + '</option>');
                $('#customer_city').html( '<option value="">' + 'Select City' + '</option>');

                if (!countryValue) {
                    return;
                }

                var countryParts = countryValue.split('~');
                var countryId = countryParts[1] || '';

                if (!countryId) {
                    alert( 'Invalid country selection.');
                    return;
                }

                $('#customer_state').prop('disabled',true);

                $.ajax({
                    type: 'POST',
                    url: "{{ route('create-offline-hall-order') }}",

                    data: {
                        _token: "{{ csrf_token() }}",
                        request_type: 'get_states_country',
                        countryId: countryId
                    },

                    success: function (response) {
                        $('#customer_state').prop('disabled', false);
                        response = parseAjaxResponse(response);

                        var optionsHtml = getOptionsHtml(response);
                        if (!optionsHtml) {
                            alert(response.message || 'No states found.');
                            return;
                        }

                        $('#customer_state').html(optionsHtml);
                    },

                    error: function (xhr) {
                        $('#customer_state')
                            .prop('disabled', false);

                        alert(getAjaxError( xhr, 'Unable to load states.'));
                    }
                });
            }
        );


        /*
        |--------------------------------------------------------------------------
        | State -> City
        |--------------------------------------------------------------------------
        */

        $('#customer_state').on('change', function () {
            var stateValue = $(this).val();
        
            $('#customer_city').html('<option value="">' +'Select City' +'</option>');
            if (!stateValue) {
                return;
            }

            var stateParts = stateValue.split('~');
            var stateId = stateParts[1] || '';

            if (!stateId) {
                alert('Invalid state selection.');
                return;
            }

            $('#customer_city').prop('disabled',true);
                $.ajax({
                    type: 'POST',
                    url: "{{ route('create-offline-hall-order') }}",
                    data: {

                        _token: "{{ csrf_token() }}",
                        request_type: 'get_city_state',
                        stateId: stateId

                    },

                    success: function (response) {
                        $('#customer_city').prop('disabled', false);
                        response = parseAjaxResponse(response);
                        var optionsHtml = getOptionsHtml(response);

                        if (!optionsHtml) {
                            alert(response.message || 'No cities found.');
                            return;
                        }

                        $('#customer_city').html(optionsHtml);
                    },

                    error: function (xhr) {
                        $('#customer_city').prop('disabled',false);
                        alert(getAjaxError(xhr,'Unable to load cities.'));
                    }
                });
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Payment Required Fields
        |--------------------------------------------------------------------------
        */

        $('#payment_gateway').on('change',function () {
                updatePaymentRequiredFields();
            }
        );

        updatePaymentRequiredFields();

        function updatePaymentRequiredFields() {
            var paymentMethod = $('#payment_gateway').val();

            if (paymentMethod === 'hdfc') {
                $('.payment-required-field')
                    .attr('required',true);
            } else {
                $('.payment-required-field')
                    .removeAttr('required');
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Submit Form
        |--------------------------------------------------------------------------
        */

        $('#offlineHallOrderForm').on('submit',function (event) {
            if (!selectedHall) {
                event.preventDefault();
                alert('Please select and confirm a Hall.');
                return false;
            }

            if (!$('.user-section').is(':visible')) {
                event.preventDefault();
                alert('Please confirm the selected Hall.');
                return false;
            }

            var phone = $('#customer_phone') .val() .trim();

            if (!/^[0-9]{10}$/.test(phone)) {
                event.preventDefault();
                alert('Please enter a valid 10 digit phone number.');

                return false;
            }

            updateHiddenDates();
            storeSelectedHallDetails();

            $('#request_type').val('create_hall_order');

            $('#bookNow').prop('disabled',true)
                .html(
                    '<i class="fa fa-spinner fa-spin"></i> ' +
                    'Booking...'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Price Inputs
        |--------------------------------------------------------------------------
        */

        function updatePriceInputs(totalPrice) {
            totalPrice = parseFloat( totalPrice || 0);

            var taxAmount = 0;
            var finalPrice = totalPrice +taxAmount;

            $('#hall_price') .val (totalPrice.toFixed(2));
            $('#total_service_price').val(totalPrice.toFixed(2));
            $('#sub_total_price').val(totalPrice.toFixed(2));
            $('#tax_amount').val(taxAmount.toFixed(2));
            $('#tax_percentage').val(0);
            $('#total_order_price').val(finalPrice.toFixed(2));
        }


        /*
        |--------------------------------------------------------------------------
        | Reset Hall Selection
        |--------------------------------------------------------------------------
        */

        function resetHallSelection() {
            selectedHall = null;
            clearSelectedHallInputs();

            $('#bookingDetails').html('');
            $('#priceDetails').html('');
            $('.user-section').hide();
            $('.hall-section').show();
            $('.confirm-hall-btn').prop('disabled',false).text('Confirm Hall');
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - Book From Text
        |--------------------------------------------------------------------------
        */

        function getBookFromText() {

            var bookFromText =$('#book_from option:selected').text().trim().replace(/\s+/g,' ');

            if (!bookFromText) {
                bookFromText = $('#book_from').val() === 'blocked' ? 'Blocked Inventory' : 'Live Inventory';
            }

            return bookFromText;
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - Availability Items
        |--------------------------------------------------------------------------
        */

        function getAvailabilityItems(response) {
            if (response.data && Array.isArray(response.data)) {
                return response.data;
            }

            if (response.content && Array.isArray(response.content)) {
                return response.content;
            }

            if (response.content && response.content.data && Array.isArray(response.content.data)) {
                return response.content.data;
            }

            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - Success Response
        |--------------------------------------------------------------------------
        */
        function isSuccessResponse(response) {

            return (response.status == 1 || response.status === true);
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - Options HTML
        |--------------------------------------------------------------------------
        */

        function getOptionsHtml(response) {
            if (typeof response.data ==='string') {
                return response.data;
            }


            if (typeof response.content === 'string') {

                return response.content;
            }

            if (typeof response.html === 'string') {
                return response.html;
            }

            return '';
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - Availability Message
        |--------------------------------------------------------------------------
        */

        function showAvailabilityMessage(message,type) {
            $('#availabilityMessage')
                .removeClass(
                    'alert-success ' +
                    'alert-warning ' +
                    'alert-danger'
                )
                .addClass('alert alert-' + type)
                .html(message)
                .show();
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - Parse AJAX Response
        |--------------------------------------------------------------------------
        */

        function parseAjaxResponse(response) {
            if (typeof response === 'string') {
                try {

                    return JSON.parse(response);
                } catch (error) {
                    return {
                        status: 0,
                        message: 'Invalid server response.'
                    };
                }
            }
            return response;
        }


        /*
        |--------------------------------------------------------------------------
        | Helper - AJAX Error
        |--------------------------------------------------------------------------
        */

        function getAjaxError(xhr,defaultMessage) {
            if (xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        return response.message;
                    }
                } catch (error) {
                    console.log(xhr.responseText);
                }
            }
            return defaultMessage;
        }

        /*
        |--------------------------------------------------------------------------
        | Helper - Normalise Token
        |--------------------------------------------------------------------------
        */
        function normaliseToken(value) {
            return String(value || '')
                .trim()
                .toUpperCase()
                .replace(/[\s-]+/g,'_');
        }

        function escapeHtml(value) {
            return $('<div>')
                .text(value === null ? '' : value)
                .html();
        }

        function escapeAttribute(value) {
            return String(value === null ? '' : value)
                .replace(/&/g,'&amp;')
                .replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;')
                .replace(/</g,'&lt;')
                .replace(/>/g,'&gt;');
        }

    });
</script>
@endsection
