@extends('layouts.app')

@section('title','Property Availability')

@section('content')

<link href="{{ asset('plugins/components/full-calender/lib/main.css') }}" rel="stylesheet" />
<script src="{{ asset('plugins/components/full-calender/lib/main.js') }}"></script>


<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Property</li>
                <li class="breadcrumb-item active">Hall Availability</li>
            </ol>
        </div>
    </div>

    @include('errors.message')

    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <form>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Property</label>
                                <span class="required_field">*</span>
                                <select class="form-control" id="property" name="property_id" required>
                                    <option value="">Select Property</option>
                                    @foreach ($MasterProperty as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('property_id'))
                                    <span class="text-danger">{{ $errors->first('property_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4" id="hallTypeSection">
                            <div class="form-group">
                                <label class="control-label">Type of Hall</label>
                                <span class="required_field">*</span>
                                <select class="form-control" id="hcategory" name="hcategory_id" required>
                                    <option value="">Select Type of Hall</option>
                                </select>
                                @if ($errors->has('hcategory_id'))
                                    <span class="text-danger">{{ $errors->first('hcategory_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4" id="hallNameSection">
                            <div class="form-group">
                                <label class="control-label">Name of Hall</label>
                                <span class="required_field">*</span>
                                <select class="form-control" id="hall" name="hall_id" required>
                                    <option value="">Select Name of Hall</option>
                                </select>
                                @if ($errors->has('hall_id'))
                                    <span class="text-danger">{{ $errors->first('hall_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:10px; color:red;font-weight:bold;">
                        FD = Full Day |
                        FH = First Half |
                        SH = Second Half
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12">
                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    #calendar {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 10px;
    }
    .fc-col-header, .fc-day, .fc-scrollgrid-sync-table, .fc-daygrid-body {
        width: 100% !important;
    }
    .active-room {
        background-color: #00bbd9 !important;
        color: #fff !important;
    }
</style>

<script type="text/javascript">
    $(document).ready(function () {
        var date = new Date();
        var CurrentMonth = new Date().getMonth() + 1;
        var CurrentYear = new Date().getFullYear();
        var propertyId = ''; var roomId = '';

        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
          displayEventTime: false,
          initialDate: new Date(),
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
          },
        });
        calendar.render();
        $('.fc-prev-button').click(function(){
            if(CurrentMonth > 1) {
                CurrentMonth--;
            } else if(CurrentMonth == 1) {
                CurrentMonth = 12;
                CurrentYear--;
            }
            if(roomId != '') {
                getAvailablityData(propertyId, roomId, CurrentMonth, CurrentYear);
            }
        });

        $('.fc-next-button').click(function(){
            if(CurrentMonth < 12) {
                CurrentMonth++;
            } else if(CurrentMonth == 12) {
                CurrentMonth = 1;
                CurrentYear++;
            }
            if(roomId != '') {
                getAvailablityData(propertyId, roomId, CurrentMonth, CurrentYear);
            }
        });

        $('.fc-today-button').click(function(){
            CurrentMonth = new Date().getMonth() + 1;
            CurrentYear = new Date().getFullYear();

            if(roomId != '') {
                getAvailablityData(propertyId, roomId, CurrentMonth, CurrentYear);
            }
        });

        function getAvailablityData(hotel = '', room = '', month = '', year = '') {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                displayEventTime: false,
                initialDate: new Date(year,(month - 1)),
                headerToolbar: {
                  left: 'prev,next today',
                  center: 'title',
                  right: ''
                },
            });
            calendar.render();
            $.ajax({
                type: "POST",
                url: "{{url('hall-oprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {propertyId: hotel, roomId: room, month: month, year: year, request_type: "get_availability_data"},


                success: function (response) {
                    if (response.status == 0) {
                        alert(response.message);
                    } else {
                        let Rooms = response.data;
                        for (let i = 0; i < Rooms.length; i++) {
                            calendar.addEvent({
                                title: Rooms[i].title,
                                start: Rooms[i].start,
                                color: Rooms[i].color,
                                allDay: true
                            });
                        }
                    }
                }

            });
        }

        var hallList = @json($HallList);
        var categoryList = @json($CategoryList);
        $(document).on('change', '#property', function () {
            let propertyId = $(this).val();
            let usedCategoryIds = [];

            $('#property_name').val($("#property option:selected").text());
            $('#hcategory').html('<option value="">Select Type of Hall</option>');
            $('#hall').html('<option value="">Select Name of Hall</option>');
            $('#hall_name').val('');

            $.each(hallList, function (index, hall) {
                if (propertyId == hall.property_id && $.inArray(hall.hcategory_id, usedCategoryIds) === -1) {
                    usedCategoryIds.push(hall.hcategory_id);

                    $.each(categoryList, function (catIndex, category) {
                        if (hall.hcategory_id == category.id) {
                            $('#hcategory').append(
                                '<option value="' + category.id + '">' + category.hcategory_name + '</option>'
                            );
                        }
                    });
                }
            });
        });

        $(document).on('change', '#hcategory', function () {
            let propertyId = $('#property').val();
            let hcategoryId = $(this).val();

            $('#hall').html('<option value="">Select Name of Hall</option>');
            $('#hall_name').val('');

            $.each(hallList, function (index, hall) {
                if (propertyId == hall.property_id && hcategoryId == hall.hcategory_id) {
                    $('#hall').append(
                        '<option value="' + hall.id + '">' + hall.hall_name + '</option>'
                    );
                }
            });
        });

        $(document).on('change', '#hall', function () {
            $('#hall_name').val($("#hall option:selected").text());

            propertyId = $('#property').val();
            roomId = $(this).val();

            if (propertyId && roomId) {
                getAvailablityData(
                    propertyId,
                    roomId,
                    CurrentMonth,
                    CurrentYear
                );
            }
        });
    });
</script>

@endsection
