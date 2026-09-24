<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ConferenceTraits;
use Session;

class ConferenceHallBookingController extends Controller
{
    use ConferenceTraits;

    public function allConference()
    {
        return view('conference.all-conference');
    }

    public function conferenceAttributeTermAddRequest(Request $request)
    {
        return view('conference.conference-attribute');
    }

    public function conferenceAttributeTerm()
    {
        $Facilities = 'Facilities';

        $AttributeTerms = [
            ['id' => 1, 'name' => 'Projector'],
            ['id' => 2, 'name' => 'LED Screen'],
            ['id' => 3, 'name' => 'Audio System / Microphone'],
            ['id' => 4, 'name' => 'Wi-Fi'],
            ['id' => 5, 'name' => 'Air Conditioning'],
            ['id' => 6, 'name' => 'Video Conferencing Setup'],
            ['id' => 7, 'name' => 'Catering Services'],
            ['id' => 8, 'name' => 'Parking Facility'],
        ];

        return view('conference.conference-attribute-terms', compact('Facilities', 'AttributeTerms'));
    }

    public function manageConferenceRooms()
    {
        $RoomAttributes = [];
        $HotelRooms = [];

        return view('conference.manage-conference-rooms', compact(
            'RoomAttributes',
            'HotelRooms'
        ));
    }

    public function addConference()
    {
        if (!(parent::checkWritePrivilege(7))) {
            Session::flash('error', 'You are not authorized to do this operation.');
            return redirect()->back();
        }

        return view('conference.add-conference');
    }
}