@extends('layouts.app')

@section('title', 'Edit Ticket / Event')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css" type="text/css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.js"></script>

<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item">Ticketing</li>
                <li class="breadcrumb-item active">Edit ticketing</li>
            </ol>
        </div>
    </div>

    @if(Session::has('success'))
    <p class="flashMessage" style="color: #3bbc2e; text-align: center;">
        {{ Session::get('success') }}
        @php
        Session::forget('success');
        @endphp
    </p>
    @endif

    <div class="row">
        <div class="col-sm-12">
            <div class="header-section">
                <h2 id="PageHeading">Edit Ticket / Event</h2>
            </div>
            <div class="row">
                <form class="form-horizontal" action="{{ route('ticket-edit-request') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" value="{{ $TicketDetails->id }}">
                    <div class="col-md-9">
                        <div class="white-box">
                            <div class="form-group">
                                <label class="col-md-12" for="name">Name<span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="name" placeholder="Name of the Ticket" value="{{ $TicketDetails->name }}" required>
                                    @if ($errors->has('name'))
                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12" for="content">Content<span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <textarea name="content" cols="10" rows="5" required>{{ $TicketDetails->content }}</textarea>
                                    @if ($errors->has('content'))
                                    <span class="text-danger">{{ $errors->first('content') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Category<span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Events" <?= ($TicketDetails->category == 'Events') ? 'selected' : '' ?>>Events</option>
                                        <option value="Entry Ticket" <?= ($TicketDetails->category == 'Entry Ticket') ? 'selected' : '' ?>>Entry Ticket</option>
                                        <option value="Experience Ticketing" <?= ($TicketDetails->category == 'Experience Ticketing') ? 'selected' : '' ?>>Experience Ticketing</option>
                                    </select>
                                    @if ($errors->has('category'))
                                    <span class="text-danger">{{ $errors->first('category') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2 bookingMode" style="display:{{ ($TicketDetails->category == 'Experience Ticketing') ? 'block' : 'none' }}">
                                <label class="col-md-12">Booking Mode<span style="color:red;">*</span></label>
                                <div class="col-md-12">
                                    <select class="form-control" id="booking_mode" name="booking_mode" required>                                        
                                        <option value="sharing" <?= ($TicketDetails->booking_mode == 'sharing') ? 'selected' : '' ?>>Sharing</option>
                                        <option value="reserve" <?= ($TicketDetails->booking_mode == 'reserve') ? 'selected' : '' ?>>Reserve</option>
                                    </select>                                    
                                </div>
                            </div>
                            <div class="form-group mt-2 durationDiv" style="{{ ($TicketDetails->category != 'Events') ? 'display: none' : '' }}">
                                <label class="col-md-12">Duration</label>
                                <div id="durationContent">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control datepicker-autoclose" name="start_date" placeholder="Start Date" value="{{ $TicketDetails->start_date }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control datepicker-autoclose" name="end_date" placeholder="End Date" value="{{ $TicketDetails->end_date }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">Booking Start Date</label>
                                <div class="input-group">
                                    <input type="text" class="form-control check-quantity" name="book_start_date" id="datepicker-autoclose" placeholder="dd-mm-yyyy" value="{{ $TicketDetails->book_start_date }}"> <span class="input-group-addon"><i class="icon-calender"></i></span>
                                </div>
                                @if ($errors->has('book_start_date'))
                                <span class="text-danger">{{ $errors->first('book_start_date') }}</span>
                                @endif
                            </div>
                            <div class="form-group col-md-6">
                                <label>Booking End Time (for same day)<span style="color:red;">*</span></label>
                                <div class="input-group clockpicker" data-autoclose="true">
                                    <input type="text" class="form-control" name="book_end_time" value="{{ $TicketDetails->book_end_time }}" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                </div>
                                @if ($errors->has('book_end_time'))
                                <span class="text-danger">{{ $errors->first('book_end_time') }}</span>
                                @endif
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">No. of days from Current Date (To set default Booking Date)</label>
                                <div class="col-md-12">
                                    <input type="number" class="form-control" name="days_from_start_date" placeholder="Days ahead from current date" value="{{ $TicketDetails->days_from_start_date }}" min="0">
    <!--                                @if ($errors->has('days_from_start_date'))
                                    <span class="text-danger">{{ $errors->first('days_from_start_date') }}</span>
                                    @endif                                -->
                                </div>
                            </div>
                            <div class="form-group" id="sightDiv">
                                <label class="col-md-12">Select Week Days (Not Available)</label>
                                <div class="col-md-12">
                                    <select class="form-control" name="not_available[]" id="not_available" multiple="multiple">
                                        @foreach ($Days as $val)
                                        <option value="{{ $val }}" {{ (in_array($val, $TicketDetails->not_available)) ? 'selected' : '' }}>{{ $val }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('not_available'))
                                    <span class="text-danger">{{ $errors->first('not_available') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Youtube Video</label>
                                <div class="col-md-12">
                                    <input type="text" class="form-control" name="video" value="{{ $TicketDetails->video }}" placeholder="Youtube Video Link">
                                    @if ($errors->has('video'))
                                    <span class="text-danger">{{ $errors->first('video') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Banner Image</label>
                                <div class="col-md-12">
                                    <input type="file" id="input-file-now" class="dropify" name="banner_image" data-default-file="{{ $TicketDetails->banner_image }}">
                                    @if ($errors->has('banner_image'))
                                    <span class="text-danger">{{ $errors->first('banner_image') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="col-md-12">Gallery</label>
                                <div class="col-md-12">
                                    <div id="gallery-image" style="padding-top: .5rem;"></div>
                                    @if ($errors->has('images'))
                                    <span class="text-danger">{{ $errors->first('images') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Booking Type<span style="color:red;">*</span></h3>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <select class="form-control" name="ticket_type" id="ticket_type" required>
                                        <option value="Full Day Booking" <?= ($TicketDetails->ticket_type == 'Full Day Booking') ? 'selected' : '' ?>>Full Day Booking</option>
                                        <option value="Slot Booking" <?= ($TicketDetails->ticket_type == 'Slot Booking') ? 'selected' : '' ?>>Slot Booking</option>
                                    </select>
                                    @if ($errors->has('ticket_type'))
                                    <span class="text-danger">{{ $errors->first('ticket_type') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Max Ticket Per Transaction<span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" name="max_ticket_per_txn" min="1" value="{{ $TicketDetails->max_ticket_per_txn }}" required>
                                    @if ($errors->has('max_ticket_per_txn'))
                                    <span class="text-danger">{{ $errors->first('max_ticket_per_txn') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Max Ticket per User Per Day<span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" name="max_ticket_per_user_per_day" min="1" value="{{ $TicketDetails->max_ticket_per_user_per_day }}" required>
                                    @if ($errors->has('max_ticket_per_user_per_day'))
                                    <span class="text-danger">{{ $errors->first('max_ticket_per_user_per_day') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12" id="bookTypeDiv">
                                    <?php if ($TicketDetails->ticket_type == 'Full Day Booking') { ?>
                                        <div class="col-md-6">
                                            <label>Start Time<span style="color:red;">*</span></label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                                                <input type="text" class="form-control" name="start_time" placeholder="Start Time" value="{{ $TicketDetails->start_time }}" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label>End Time<span style="color:red;">*</span></label>
                                            <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                                                <input type="text" class="form-control" name="end_time" placeholder="End Time" value="{{ $TicketDetails->end_time }}" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 m-t-10">
                                            <label>Max ticket per day online</label>
                                            <input type="number" class="form-control" name="max_people" value="{{ $TicketDetails->max_people }}" min="1">
                                        </div>
                                        <div class="col-md-6 m-t-10">
                                            <label>Max Ticket per day offline</label>
                                            <input type="number" class="form-control" name="max_people_offline" value="{{ $TicketDetails->max_people_offline }}" min="1">
                                        </div>
                                        <?php } else {
                                        if (!empty($TicketDetails->slots)) { ?>
                                            <table class="display nowrap table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center" colspan="5">Slots</th>
                                                    </tr>
                                                    <tr>
                                                        <th class="text-center">Start Time</th>
                                                        <th class="text-center">End Time</th>
                                                        <th class="text-center">Online Ticket</th>
                                                        <th class="text-center">Offline Ticket</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="slot-container">
                                                    <?php
                                                    $count = 1;
                                                    foreach ($TicketDetails->slots as $value) { ?>
                                                        <tr id="slt{{ $count }}">
                                                            <td>
                                                                <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                                                                    <input type="text" class="form-control" name="slots[{{ $count }}][from_time]" value="{{ $value['from_time'] }}" placeholder="Start Time" required=""> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true">
                                                                    <input type="text" class="form-control" name="slots[{{ $count }}][to_time]" value="{{ $value['to_time'] }}" placeholder="End Time" required=""> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control" name="slots[{{ $count }}][max_ticket]" value="{{ $value['max_ticket'] }}" title="Max ticket online" placeholder="Online ticket" min="1" required="">
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control" name="slots[{{ $count }}][max_ticket_offline]" value="{{ $value['max_ticket_offline'] }}" title="Max ticket offline" placeholder="offline ticket" min="1" required="">
                                                            </td>
                                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i{{ $count }}"></i>
                                                            </td>
                                                        </tr>
                                                    <?php $count++;
                                                    } ?>
                                                </tbody>
                                            </table> <span class="btn btn-info btn-sm" id="addNewRow" style="float: right;"><i class="icon-plus"></i> Add Slot</span>

                                    <?php }
                                    } ?>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Terms & Conditions<span style="color:red;">*</span></h3>
                            <hr>
                            <div class="row">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <textarea name="terms_conditions" cols="10" rows="5" required>{{ $TicketDetails->terms_conditions }}</textarea>
                                        @if ($errors->has('terms_conditions'))
                                        <span class="text-danger">{{ $errors->first('terms_conditions') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="white-box">
                            <h3 class="box-title">Extra Services</h3><hr>
                            <div class="row">
                                <div class="form-group mt-2">
                                    <div class="col-md-12">
                                        <table class="display nowrap table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Title</th>
                                                    <th class="text-center">Price</th>
                                                    <th class="text-center" colspan="2">Max Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody id="service-container">
                                                <?php
                                                if (!empty($TicketDetails->extra_services)) {
                                                    $countr = 0;
                                                    foreach ($TicketDetails->extra_services as $key => $value) {
                                                ?>
                                                        <tr id="ser{{ $countr }}">
                                                            <td>
                                                                <input type="text" class="form-control" name="extra_services[{{ $countr }}][title]" value="{{ $value['name'] }}">
                                                            </td>
                                                            <td>
                                                                <input type="number" min="1" class="form-control" name="extra_services[{{ $countr }}][price]" value="{{ $value['price'] }}">
                                                            </td>
                                                            <td>
                                                                <input type="number" min="1" class="form-control" name="extra_services[{{ $countr }}][quantity]" value="{{ $value['maxquantity'] }}">
                                                            </td>
                                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteService fa fa-trash" id="j{{ $countr }}"></i>
                                                            </td>
                                                        </tr>
                                                <?php $countr++;
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                        <span class="btn btn-info btn-sm" id="addNewService" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                    </div>
                                </div>
                            </div>
                        </div> -->
                        <div class="white-box">
                            <h3 class="box-title">FAQS</h3>
                            <hr>
                            <div class="row">
                                <div class="form-group mt-2">
                                    <div class="col-md-12">
                                        <table class="display nowrap table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Title</th>
                                                    <th class="text-center" colspan="2">Content</th>
                                                </tr>
                                            </thead>
                                            <tbody id="faq-container">
                                                <?php
                                                if (!empty($TicketDetails->faqs)) {
                                                    $countr = 0;
                                                    foreach ($TicketDetails->faqs as $key => $value) {
                                                ?>
                                                        <tr id="{{ $countr }}">
                                                            <td>
                                                                <input type="text" class="form-control" name="faqs[{{ $countr }}][title]" value="{{ $key }}">
                                                            </td>
                                                            <td>
                                                                <textarea rows="2" class="form-control" name="faqs[{{ $countr }}][content]" style="overflow: auto;resize: vertical;">{{ $value }}</textarea>
                                                            </td>
                                                            <td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i{{ $countr }}"></i>
                                                            </td>
                                                        </tr>
                                                <?php $countr++;
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                        <span class="btn btn-info btn-sm" id="addNewFaq" style="float: right;"><i class="icon-plus"></i> Add item</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Location</h3>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label>City<span style="color:red;">*</span></label>
                                    <select class="form-control select2" name="city" required>
                                        <option value="">Select City</option>
                                        <?php
                                        foreach ($CityDetail as $value) {
                                            $checked = '';
                                            if ($TicketDetails->city == $value) {
                                                $checked = 'selected';
                                            }
                                            echo '<option value="' . $value . '" ' . $checked . '>' . $value . '</option>';
                                        }
                                        ?>
                                    </select>
                                    @if ($errors->has('city'))
                                    <span class="text-danger">{{ $errors->first('city') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label>Place (Use for location filter)<span style="color:red;">*</span></label>
                                    <input class="form-control" name="place" type="text" value="{{ $TicketDetails->place }}" required>
                                    @if ($errors->has('place'))
                                    <span class="text-danger">{{ $errors->first('place') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Address</label>
                                    <input id="autocomplete" class="form-control" name="address" value="{{ $TicketDetails->address }}" placeholder="Enter address" onFocus="geolocate()" type="text" />
                                    @if ($errors->has('address'))
                                    <span class="text-danger">{{ $errors->first('address') }}</span>
                                    @endif
                                    <input type="hidden" id="map_lat" name="map_lat" value="{{ $TicketDetails->map_lat }}">
                                    <input type="hidden" id="map_lng" name="map_lng" value="{{ $TicketDetails->map_lng }}">
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Contact Information</h3>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Contact Email<span style="color:red;">*</span></label>
                                    <input type="email" class="form-control" name="contact_email" value="{{ $TicketDetails->contact_email }}" placeholder="Contact email" required />
                                    @if ($errors->has('contact_email'))
                                    <span class="text-danger">{{ $errors->first('contact_email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="" for="name">Contact Number<span style="color:red;">*</span></label>
                                    <input type="text" class="form-control numvalidate" name="contact_number" value="{{ $TicketDetails->contact_number }}" placeholder="Contact Number" required maxlength="10" />
                                    @if ($errors->has('contact_number'))
                                    <span class="text-danger">{{ $errors->first('contact_number') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Additional Email</label>
                                    <input type="text" class="form-control" name="additional_email" placeholder="Additional Contact Email" autocomplete="off" value="{{ $TicketDetails->additional_email }}">
                                    @if ($errors->has('additional_email'))
                                    <span class="text-danger">{{ $errors->first('additional_email') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Additional Contact Number</label>
                                    <input type="text" class="form-control" name="additional_phone" placeholder="Additional Contact Number" autocomplete="off" value="{{ $TicketDetails->additional_phone }}">
                                    @if ($errors->has('additional_phone'))
                                    <span class="text-danger">{{ $errors->first('additional_phone') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if (Auth::user()->role == 2)
                        <div class="white-box">
                            <h3 class="box-title">Assign Sub user</h3><hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <select id="subUser" name="sub_user[]" class="form-control" multiple="multiple">
                                        @foreach ($SubUser as $val)
                                        <option value="{{ $val['id'] }}" {{ ($val['checked'] == 1) ? 'selected' : '' }}>{{ $val['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('sub_user'))
                                    <span class="text-danger">{{ $errors->first('sub_user') }}</span>
                                    @endif
                                </div>
                            </div>                            
                        </div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <div class="white-box">
                            <h3 class="box-title">Publish</h3>
                            <hr>
                            <div class="form-group">
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio1" value="publish" {{ ($TicketDetails->status == 'publish') ? 'checked' : '' }}>
                                    <label for="radio1">Publish</label>
                                </div>
                                <div class="radio radio-info">
                                    <input type="radio" name="status" id="radio2" value="draft" {{ ($TicketDetails->status == 'draft') ? 'checked' : '' }}>
                                    <label for="radio2">Draft</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" style="float: right;margin-top: -20px;">Submit</button>
                        </div>
                        <?php if (Auth::user()->access_type == 'superadmin') { ?>
                            <div class="white-box">
                                <h3 class="box-title">Vendor<span style="color:red;">*</span></h3>
                                <hr>
                                <select class="form-control select2" id="venderId" name="vendor_id" required>
                                    <option value="">Select Vendor</option>
                                    <?php
                                    foreach ($Vendors as $key => $value) {
                                        $checked = '';
                                        if ($TicketDetails->vendor_id == $key) {
                                            $checked = 'selected';
                                        }
                                        echo '<option value="' . $key . '" ' . $checked . '>' . $value . '</option>';
                                    }
                                    ?>
                                </select>
                                @if ($errors->has('vendor_id'))
                                <span class="text-danger">{{ $errors->first('vendor_id') }}</span>
                                @endif
                            </div>
                        <?php } else { ?>
                            <input type="hidden" id="vendor" name="vendor_id" class="form-control" value="{{ (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id }}">
                        <?php } ?>
                        <?php foreach ($CarAttributes as $attrs => $terms) { ?>
                            <div class="white-box">
                                <div style="font-size: 15px;"><strong>Attribute: {{$attrs}}</strong></div>
                                <hr>
                                <div class="input-group">
                                    <ul class="icheck-list">
                                        <?php foreach ($terms as $key => $values) {
                                            $checked = '';
                                            if (isset($TicketDetails->property[$attrs]) && in_array($values, $TicketDetails->property[$attrs])) {
                                                $checked = 'checked';
                                            }
                                        ?>
                                            <li>
                                                <input type="checkbox" class="check" name="property[{{ $attrs }}][]" value="{{ $key .'~'. $values }}" {{ $checked }} data-checkbox="icheckbox_flat-blue">
                                                <label>{{ $values }}</label>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="white-box">
                            <h3 class="box-title">Feature Image</h3>
                            <hr>
                            <div class="form-group">
                                <input type="file" class="dropify" name="feature_image" data-default-file="{{ $TicketDetails->feature_image }}" />
                                @if ($errors->has('feature_image'))
                                <span class="text-danger">{{ $errors->first('feature_image') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">Pricing</h3>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Adult Price<span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" name="adult_price" min="1" value="{{ $TicketDetails->adult_price }}" required>
                                    @if ($errors->has('adult_price'))
                                    <span class="text-danger">{{ $errors->first('adult_price') }}</span>
                                    @endif
                                </div>
                                <div class="form-group col-md-12">
                                    <label class="" for="name">Child Price<span style="color:red;">*</span></label>
                                    <input type="number" class="form-control" name="child_price" min="0" value="{{ $TicketDetails->child_price }}" required>
                                    @if ($errors->has('child_price'))
                                    <span class="text-danger">{{ $errors->first('child_price') }}</span>
                                    @endif
                                </div>
                                <!-- <div class="form-group col-md-12">
                                    <label>Service Charge</label>
                                    <input type="number" class="form-control" name="service_fee" min="0" value="{{ $TicketDetails->service_fee }}">
                                    @if ($errors->has('service_fee'))
                                    <span class="text-danger">{{ $errors->first('service_fee') }}</span>
                                    @endif
                                </div> -->
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">GST Applicable</h3>
                            <hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="gst_applicable" value="1" {{ ($TicketDetails->gst_applicable == 1) ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="gst_applicable" value="0" {{ ($TicketDetails->gst_applicable == 0) ? 'checked' : '' }}> No </label>
                                </div>
                            </div>
                        </div>
                        <div class="white-box">
                            <h3 class="box-title">GST Number</h3>
                            <hr>
                            <div class="form-group">
                                <input type="text" name="gst_number" class="form-control" value="{{ $TicketDetails->gst_number }}">
                            </div>
                            <h3 class="box-title">Company Name</h3>
                            <hr>
                            <div class="form-group">
                                <input type="text" name="gst_legal_name" class="form-control" value="{{ $TicketDetails->gst_legal_name }}" placeholder="Enter Company Name">
                            </div>
                        </div>
                        <!-- <div class="white-box">
                            <h3 class="box-title">Paytm MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="paytm_mid" class="form-control" value="{{ $TicketDetails->paytm_mid }}" placeholder="Enter Paytm MID">
                            </div>
                            <h3 class="box-title">HDFC MID</h3><hr>
                            <div class="form-group">
                                <input type="text" name="hdfc_mid" class="form-control" value="{{ $TicketDetails->hdfc_mid }}" placeholder="Enter HDFC MID">
                            </div>
                        </div> -->
                        <div class="white-box">
                            <h3 class="box-title">Show Price</h3>
                            <hr>
                            <div class="form-group">
                                <div class="radio-list m-l-20">
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="1" {{ ($TicketDetails->show_price == 1) ? 'checked' : '' }}> Yes </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="show_price" value="0" {{ ($TicketDetails->show_price == 0) ? 'checked' : '' }}> No </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
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
        width: 400px !important;
    }

    .multiselect-container.dropdown-menu {
        width: 400px !important;
        height: 250px !important;
        overflow-y: auto;
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

    .icheck-list li label {
        display: inline;
        color: black;
    }

    .icheck-list {
        padding-right: 0px;
    }

    .icheck-list li {
        padding-bottom: 8px;
    }

    .image-uploader {
        min-height: 20rem;
    }
</style>
<script src="https://cdn.ckeditor.com/4.15.1/standard/ckeditor.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#not_available').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Days'
        });
        $('#subUser').multiselect({
            includeSelectAllOption: true,
            nonSelectedText: 'Select Sub user'
        });
        $('#datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'dd-mm-yyyy',
            startDate: '-0m',
        });
        $('.clockpicker').clockpicker({
            donetext: 'Done',
        });
        $('.datepicker-autoclose').datepicker({
            autoclose: true,
            todayHighlight: true,
            startDate: '-0m'
        });
        var length = '<?= !empty($TicketDetails->slots) ? count($TicketDetails->slots) : 0 ?>';
        var length2 = '<?= !empty($TicketDetails->faqs) ? count($TicketDetails->faqs) : 0 ?>';
        var length3 = '<?= !empty($TicketDetails->extra_services) ? count($TicketDetails->extra_services) : 0 ?>';
        $('.dropify').dropify();
        let data = '<?= $gallery; ?>';
        $('#gallery-image').imageUploader({
            preloaded: JSON.parse(data),
            imagesInputName: 'images',
            preloadedInputName: 'oldimage',
            // maxSize: 2 * 1024 * 1024,
            maxFiles: 10
        });

        $(document).on('change', '#category', function() {
            let category = $(this).val();
            if (category == 'Events') {
                $(".bookingMode").hide();
                $("#booking_mode").val('sharing');
                $(".durationDiv").show();
                $("#durationContent").html('<div class="col-md-6"><div class="input-group"><input type="text" class="form-control datepicker-autoclose" name="start_date" placeholder="Start Date" required> <span class="input-group-addon"><i class="icon-calender"></i></span></div></div><div class="col-md-6"><div class="input-group"><input type="text" class="form-control datepicker-autoclose" name="end_date" placeholder="End Date" required> <span class="input-group-addon"><i class="icon-calender"></i></span></div></div>');
                $('.datepicker-autoclose').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    startDate: '-0m'
                });
            }else if (category == 'Experience Ticketing') {
                $(".bookingMode").show();                
            }else {
                $("#durationContent").html('');
                $(".durationDiv").hide();
                $(".bookingMode").hide();
                $("#booking_mode").val('sharing');
            }

        });

        $(document).on('change', '#ticket_type', function() {
            let ticket_type = $(this).val();
            if (ticket_type == 'Full Day Booking') {
                $("#bookTypeDiv").html('<div class="col-md-6"><label>Start Time</label><div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true"><input class="form-control" name="start_time" placeholder="Start Time" required> <span class="input-group-addon"><span class="glyphicon glyphicon-time"></span></span></div></div><div class="col-md-6"><label>End Time</label><div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true"><input class="form-control" name="end_time" placeholder="End Time" required> <span class="input-group-addon"><span class="glyphicon glyphicon-time"></span></span></div></div><div class="col-md-6 m-t-10"><label>Max Ticket per day online</label><input type="number" class="form-control" name="max_people" min="1"></div><div class="col-md-6 m-t-10"><label>Max Ticket per day offline</label><input type="number" class="form-control" name="max_people_offline" min="1"></div>');
                $('.clockpicker').clockpicker({
                    donetext: 'Done',
                });
            } else if (ticket_type == 'Slot Booking') {
                $("#bookTypeDiv").html('<table class="display nowrap table table-bordered"><thead><tr><th class="text-center" colspan="5">Slots</th></tr><tr><th class="text-center">Start Time</th><th class="text-center">End Time</th><th class="text-center">Online Ticket</th><th class="text-center">Offline Ticket</th><th></th></tr></thead><tbody id="slot-container"></tbody></table> <span class="btn btn-info btn-sm" id="addNewRow" style="float: right;"><i class="icon-plus"></i> Add Slot</span>');
                $('#addNewRow').click();
            }
        });

        $(document).on('click', '#addNewRow', function() {
            length++;
            $("#slot-container").append('<tr id="slt' + length + '"><td><div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true"> <input type="text" class="form-control" name="slots[' + length + '][from_time]" placeholder="Start Time" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span></div></td><td><div class="input-group clockpicker" data-placement="bottom" data-align="top" data-autoclose="true"> <input type="text" class="form-control" name="slots[' + length + '][to_time]" placeholder="End Time" required> <span class="input-group-addon"> <span class="glyphicon glyphicon-time"></span> </span></div></td><td><input type="number" class="form-control" name="slots[' + length + '][max_ticket]" title="Max ticket online" placeholder="Online ticket" min="1" required></td><td><input type="number" class="form-control" name="slots[' + length + '][max_ticket_offline]" title="Max ticket offline" placeholder="Offline ticket" min="1" required></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i' + length + '"></i></td></tr>');
            $('.clockpicker').clockpicker({
                donetext: 'Done',
            });
        });

        $(document).on('click', '.deleteRow', function() {
            var id = $(this).attr('id').replace('i', '');
            $("tr").remove("#slt" + id);
        });

        $(document).on('click', '#addNewFaq', function() {
            length2++;
            $("#faq-container").append('<tr id="' + length2 + '"><td><input type="text" class="form-control" name="faqs[' + length2 + '][title]"></td><td><textarea rows="2" class="form-control" name="faqs[' + length2 + '][content]" style="overflow: auto;resize: vertical;"></textarea></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteRow fa fa-trash" id="i' + length2 + '"></i></td></tr>');
        });

        $(document).on('click', '.deleteRow', function() {
            var id = $(this).attr('id').replace('i', '');
            $("tr").remove("#" + id);
        });

        $(document).on('click', '#addNewService', function() {
            length3++;
            $("#service-container").append('<tr id="ser' + length3 + '"><td><input type="text" class="form-control" name="extra_services[' + length3 + '][title]" required></td><td><input type="number" min="0" class="form-control" name="extra_services[' + length3 + '][price]" required></td><td><input type="number" min="0" class="form-control" name="extra_services[' + length3 + '][quantity]" required></td><td style="width:7%"><i class="btn btn-danger btn-sm deleteService fa fa-trash" id="j' + length3 + '"></i></td></tr>');
        });

        $(document).on('click', '.deleteService', function() {
            var id = $(this).attr('id').replace('j', '');
            $("tr").remove("#ser" + id);
        });
    });
    let placeSearch;
    let autocomplete;
    const componentForm = {};

    CKEDITOR.replace('content');
    CKEDITOR.replace('terms_conditions');

    function geolocate() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const geolocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                const circle = new google.maps.Circle({
                    center: geolocation,
                    radius: position.coords.accuracy,
                });
                autocomplete.setBounds(circle.getBounds());
            });
        }
    }

    function initAutocomplete() {
        autocomplete = new google.maps.places.Autocomplete(document.getElementById("autocomplete"), {
            types: ['address'],
            componentRestrictions: {
                country: ['IN']
            }
        });
        autocomplete.setFields(["address_component", "geometry"]);
        autocomplete.addListener("place_changed", fillInAddress);
    }

    function fillInAddress() {
        const place = autocomplete.getPlace();
        document.getElementById("map_lat").value = place.geometry.location.lat();
        document.getElementById("map_lng").value = place.geometry.location.lng();
    }
</script>

@endsection