@extends('layouts.app')

@section('title','Caravan Availability')

@section('content')

<link href="{{ asset('plugins/components/full-calender/lib/main.css') }}" rel="stylesheet" />
<script src="{{ asset('plugins/components/full-calender/lib/main.js') }}"></script>


<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Home</li>
                <li class="breadcrumb-item active">Caravan</li>
                <li class="breadcrumb-item active">Caravan Availability</li>
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
            <div class="white-box">
                <form>
                    <select id="car_id" class="form-control">
                        <option value="">Select Vehicle</option>
                        @foreach ($MasterCaravan as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
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
        var carId = '';

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

        $('#car_id').on('change',function() {
            carId = $(this).val();
            if (carId != '') {
                getAvailablityData(carId, CurrentMonth, CurrentYear);
            }
        });

        $('.fc-prev-button').click(function(){
            if(CurrentMonth > 1) {
                CurrentMonth--;
            } else if(CurrentMonth == 1) {
                CurrentMonth = 12;
                CurrentYear--;
            }
            if (carId != '') {
                getAvailablityData(carId, CurrentMonth, CurrentYear);
            }
        });

        $('.fc-next-button').click(function(){
            if(CurrentMonth < 12) {
                CurrentMonth++;
            } else if(CurrentMonth == 12) {
                CurrentMonth = 1;
                CurrentYear++;
            }
            if (carId != '') {
                getAvailablityData(carId, CurrentMonth, CurrentYear);
            }
        });

        $('.fc-today-button').click(function(){
            CurrentMonth = new Date().getMonth() + 1;
            CurrentYear = new Date().getFullYear();

            if (carId != '') {
                getAvailablityData(carId, CurrentMonth, CurrentYear);
            }
        });

        function getAvailablityData(carId = '', month = '', year = '') {
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
                url: "{{route('caravanOprsn')}}",
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {caravanId: carId, month: month, year: year, request_type: "get_availability_data"},
                success: function (data) {
                    var responce = $.parseJSON(data);
                    if (responce.status == 0) {
                        alert(responce.message);
                    } else {
                        let Rooms = responce.data;
//                        console.log(Rooms);
                        for (let i = 0; i < Rooms.length; i++) {
                            calendar.addEvent({
                                title: Rooms[i]['title'],
                                start: Rooms[i]['start'],
                                color: Rooms[i]['color'],
                                allDay: true
                            });
                        }
                    }
                }
            });
        }
    });
</script>

@endsection
