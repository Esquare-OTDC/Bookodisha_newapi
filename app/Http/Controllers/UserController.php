<?php

namespace App\Http\Controllers;

use App\AirModels\FlightMaster;
use App\AttributeValue;
use App\BlockedHotel;
use App\BlockedMmtInventory;
use App\CancelPolicy;
use App\CaravanBooking;
use App\CaravanMasterInventory;
use App\City;
use App\Country;
use App\Coupon;
use App\CtpRatePlans;
use App\CustomerRefund;
use App\EmailTemplate;
use App\FoodItem;
use App\GstDetail;
use App\GstTable;
use App\HallModels\Hall;
use App\HallModels\HallBooking;
use App\HallModels\HallProperty;
use App\HotelAvailability;
use App\HotelRoom;
use App\HotelRoomPricing;
use App\HotelSale;
use App\MasterCar;
use App\MasterCaravan;
use App\MasterHotel;
use App\MasterInventory;
use App\MenuDetail;
use App\MerchantProduct;
use App\OrderDetail;
use App\OrderMaster;
use App\PageContent;
use App\PasswordRemQuestion;
use App\PaymentHistory;
use App\PropertyAccount;
use App\RentalAvailability;
use App\RentalMasterInventory;
use App\Service;
use App\ServiceAttribute;
use App\ServiceReview;
use App\SightSeenPricing;
use App\SmsTemplate;
use App\State;
use App\StaticReview;
use App\SubuserAccess;
use App\Ticket;
use App\TicketAvailability;
use App\Tour;
use App\TourAvailability;
use App\User;
use App\VendorProfile;
use App\VendorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use PDF;
use Session;

class UserController extends Controller
{

    public $site;
    public $frontendUrl;
    public $ecoStartDate;

    public function __construct()
    {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
        $this->ecoStartDate = date("Y-m-d", strtotime("2025-07-03"));
    }

    public function staticreview()
    {
        if (!(parent::checkViewPrivilege(40))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $StaticReviews = StaticReview::all();
        $Services = Service::all();

        return view('users.static-review', compact('StaticReviews', 'Services'));
    }

    public function staticreviewAddRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(40))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'service_type' => 'required|string',
            'status' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('static-review')->withErrors($validate)->withInput();
        } else {
            $staticFeedback = new StaticReview([
                'name' => $request->name,
                'service_type' => $request->service_type,
                'status' => $request->status
            ]);
            if ($staticFeedback->save()) {
                Session::flash('success', 'Review added successful.');
                return Redirect::to('static-review');
            } else {
                Session::flash('success', 'Unable to add review');
                return Redirect::to('static-review');
            }
        }
    }

    public function settingOprsn(Request $request)
    {
        if ($request->request_type == 'delete-review') {
            if (!(parent::checkWritePrivilege(40))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('static_reviews')->whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Review deleted successfully.';
            }
        } elseif ($request->request_type == 'save-review-changes') {
            if (!(parent::checkWritePrivilege(40))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $StaticFeedback = StaticReview::find($request->Id);
                $StaticFeedback->name = $request->attrName;
                $StaticFeedback->status = $request->attrStatus;
                if ($StaticFeedback->save()) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Changes saved successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to save cheanges.';
                }
            }
        } elseif ($request->request_type == 'get_vendor_profile') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $VendorProfile = VendorProfile::where('vendor_id', $vendor_id)->first();
            $responce['data'] = $VendorProfile;
        } elseif ($request->request_type == 'delete-popular') {
            if (!(parent::checkWritePrivilege(78))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Data = PageContent::find($request->Id);
                if (!empty($Data)) {
                    $image = public_path($Data->image);
                    if (file_exists($image) && !empty($Data->image)) {
                        unlink($image);
                    }
                    $Data->delete();
                    $responce['status'] = 1;
                    $responce['message'] = 'Item deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete';
                }
            }
        } elseif ($request->request_type == 'get_service_property') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $responce['data'] = [];
            if ($request->serviceType == 'hotel') {
                $MasterHotel = MasterHotel::select('name', 'id')->where(['vender_id' => $vendor_id, 'status' => 'publish'])->get()->toArray();
                $responce['data'] = $MasterHotel;
            } elseif ($request->serviceType == 'rental') {
                $MasterCar = MasterCar::select('title as name', 'id')->where(['vendor_id' => $vendor_id, 'status' => 'publish'])->get()->toArray();
                $responce['data'] = $MasterCar;
            }
            $responce['status'] = 1;
        } elseif ($request->request_type == 'export_contactus_email') {
            $Data = DB::table('contact_us_table')->get();
            $csv = "documents/contactus_" . time() . ".csv";
            $csvname = public_path($csv);
            $headerArr = array('Name', 'Email', 'Phone', 'Mail Content', 'Recipient', 'Unit Name', 'Date');
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($Data)) {
                foreach ($Data as $value) {

                    $data['name'] = $value->name;
                    $data['email'] = $value->email;
                    $data['phone'] = $value->phone;
                    $data['content'] = $value->content;
                    $data['recipient'] = $value->recipient;
                    $data['unit_name'] = $value->unit_name;
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }
        echo json_encode($responce);
        exit;
    }

    public function vendorDashboard()
    {
        //        if (!(parent::checkViewPrivilege(1))) {
        //            Session::flash('success', 'You are not autherised to view this page.');
        //            return redirect()->back();
        //        }
        return view('users.vendor-dashboard');
    }

    public function adminDashboard()
    {
        //        if (!(parent::checkViewPrivilege(1))) {
        //            Session::flash('success', 'You are not autherised to view this page.');
        //            return redirect()->back();
        //        }
        return view('users.dashboard');
    }

    public function dashboard()
    {
        $CustomersQry = OrderMaster::select(DB::raw('COUNT(DISTINCT(customer_id)) AS totCustomer'));
        $OnlineOrdersQry = OrderMaster::where('order_type', 'online')->where('status', '<>', 'cancelled');
        $OfflineOrdersQry = OrderMaster::where('order_type', 'offline')->where('status', '<>', 'cancelled');
        $CompletedOrdersQry = OrderMaster::where('status', 'completed');
        $PendingOrdersQry = OrderMaster::where('status', 'pending');
        $CancelledOrdersQry = OrderMaster::where('status', 'cancelled')->where('payment_status', 'success');
        $TotalOrders = OrderMaster::count();
        // Top Booking
        $HotelOrdersQry = OrderMaster::select('service_name', DB::raw('sum(total_rooms) AS totBooked'))->where('service_type', 'hotel')->where('status', 'completed');
        $RentalOrdersQry = OrderMaster::select('service_name', DB::raw('sum(service_quantity) AS totBooked'))->where('service_type', 'car')->where('status', 'completed');
        $TourOrdersQry = OrderMaster::select('service_name', DB::raw('sum(total_guests) AS totBooked'))->where('service_type', 'tour')->where('status', 'completed');
        $TicketOrdersQry = OrderMaster::select('service_name', DB::raw('sum(total_guests) AS totBooked'))->where('service_type', 'ticketing')->where('status', 'completed');
        $vendor_id = 0;
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $CustomersQry->where('vendor_id', $vendor_id);
            $OnlineOrdersQry->where('vendor_id', $vendor_id);
            $OfflineOrdersQry->where('vendor_id', $vendor_id);
            $CompletedOrdersQry->where('vendor_id', $vendor_id);
            $PendingOrdersQry->where('vendor_id', $vendor_id);
            $CancelledOrdersQry->where('vendor_id', $vendor_id);
            $TotalOrders = OrderMaster::where('vendor_id', $vendor_id)->count();
            $HotelOrdersQry->where('vendor_id', $vendor_id);
            $RentalOrdersQry->where('vendor_id', $vendor_id);
            $TourOrdersQry->where('vendor_id', $vendor_id);
            $TicketOrdersQry->where('vendor_id', $vendor_id);
            if ($vendor_id == 3) {
                $OnlineOrdersQry->where('created_at', '>', $this->ecoStartDate);
                $OfflineOrdersQry->where('created_at', '>', $this->ecoStartDate);
                $CompletedOrdersQry->where('created_at', '>', $this->ecoStartDate);
                $PendingOrdersQry->where('created_at', '>', $this->ecoStartDate);
                $CancelledOrdersQry->where('created_at', '>', $this->ecoStartDate);
                $TotalOrders = OrderMaster::where('vendor_id', $vendor_id)->where('created_at', '>', $this->ecoStartDate)->count();
                $HotelOrdersQry->where('created_at', '>', $this->ecoStartDate);
            }

        }
        $Customers = $CustomersQry->count();
        $OnlineOrders = $OnlineOrdersQry->count();
        $OfflineOrders = $OfflineOrdersQry->count();
        $CompletedOrders = $CompletedOrdersQry->count();
        $PendingOrders = $PendingOrdersQry->count();
        $CancelledOrders = $CancelledOrdersQry->count();

        if (!empty($TotalOrders)) {
            $CompletedPercent = round(($CompletedOrders / $TotalOrders) * 100);
            $PendingPercent = round(($PendingOrders / $TotalOrders) * 100);
            $CancelPercent = round(($CancelledOrders / $TotalOrders) * 100);
        } else {
            $CompletedPercent = 0;
            $PendingPercent = 0;
            $CancelPercent = 0;
        }


        // Top Booking
        $TopBookings = array();
        $HotelOrders = $HotelOrdersQry->groupBy('service_name')->orderBy('totBooked', 'desc')->limit(3)->get();
        if (!empty($HotelOrders)) {
            // echo "<pre>";print_r($HotelOrders);exit;
            foreach ($HotelOrders as $value) {
                $TopBookings[] = array('service_type' => 'Hotel', 'prop_name' => $value->service_name, 'booked' => $value->totBooked);
            }
        }
        $RentalOrders = $RentalOrdersQry->groupBy('service_name')->orderBy('totBooked', 'desc')->take(3)->get();
        if (!empty($RentalOrders)) {
            foreach ($RentalOrders as $value) {
                $TopBookings[] = array('service_type' => 'Rental', 'prop_name' => $value->service_name, 'booked' => $value->totBooked);
            }
        }
        $TourOrders = $TourOrdersQry->groupBy('service_name')->orderBy('totBooked', 'desc')->take(3)->get();
        if (!empty($TourOrders)) {
            foreach ($TourOrders as $value) {
                $TopBookings[] = array('service_type' => 'Tour', 'prop_name' => $value->service_name, 'booked' => $value->totBooked);
            }
        }
        $TicketOrders = $TicketOrdersQry->groupBy('service_name')->orderBy('totBooked', 'desc')->take(3)->get();
        if (!empty($TicketOrders)) {
            foreach ($TicketOrders as $value) {
                $TopBookings[] = array('service_type' => 'Ticketing', 'prop_name' => $value->service_name, 'booked' => $value->totBooked);
            }
        }

        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime('+30 days'));
        $Properties = array();
        $AllProps = array();
        $ReservationData = array();
        $VendorServices = array();
        $Availability = $BookingCancelled = $AvailableTotal = $AvailTotal = $AvailBooked = 0;
        $ReservationDataObj = json_encode([]);
        $service_type = 'hotel';
        $propIds = array();
        if (isset($_REQUEST['daterange'])) {
            $daterange = explode(' - ', $_REQUEST['daterange']);
            $start_date = date("Y-m-d", strtotime($daterange[0]));
            $end_date = date("Y-m-d", strtotime($daterange[1]));
            $service_type = $_REQUEST['service_type'];
            $propIds = isset($_REQUEST['property']) ? $_REQUEST['property'] : [];
        }
        if (Auth::user()->access_type == 'vendor') {
            $VendorServices = json_decode(Auth::user()->services, 1);
            $VendorServices = array_filter($VendorServices, function ($val) {
                return ($val == 'hotel' || $val == 'rental');
            });
            if (in_array('hotel', $VendorServices) && $service_type == 'hotel') {
                $PropertiesQry = MasterHotel::where(['vender_id' => $vendor_id, 'status' => 'publish']);
                $AllProps = $PropertiesQry->pluck('name', 'id')->toArray();
                if (!empty($propIds)) {
                    $PropertiesQry->whereIn('id', $propIds);
                }
                $Properties = $PropertiesQry->pluck('name', 'id')->toArray();
                if (!empty($Properties)) {
                    foreach ($Properties as $id => $name) {
                        $MasterInventory = MasterInventory::select(DB::raw('sum(total_booked) AS totBooked'), DB::raw('sum(total_available) AS totAvailable'))
                            ->where('hotel_id', $id)
                            ->whereBetween('date', [$start_date, $end_date])
                            ->first();
                        $totBooked = !empty($MasterInventory->totBooked) ? $MasterInventory->totBooked : 0;
                        $totAvailable = !empty($MasterInventory->totAvailable) ? $MasterInventory->totAvailable : 0;
                        $OrderMaster = OrderMaster::select(DB::raw('sum(total_rooms) AS totCancelled'))
                            ->where(['service_type' => 'hotel', 'service_name_id' => $id, 'status' => 'cancelled', 'payment_status' => 'success'])
                            ->whereBetween('cancel_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                            ->first();
                        $totalCancelled = !empty($OrderMaster->totCancelled) ? $OrderMaster->totCancelled : 0;
                        $ReservationData[] = array(
                            'prop_id' => $id,
                            'prop_name' => $name,
                            'totBooked' => $totBooked,
                            'totAvailable' => $totAvailable,
                            'totalCancelled' => $totalCancelled
                        );
                    }
                    $AvailabilityData = MasterInventory::select(DB::raw('sum(total_booked) AS totBooked'), DB::raw('sum(initial_quantity) AS totQuantity'), DB::raw('sum(total_available) AS totalAvailable'))
                        ->whereIn('hotel_id', array_keys($Properties))
                        ->whereBetween('date', [$start_date, $end_date])
                        ->first();
                    // echo "<pre>";print_r($AvailabilityData);exit;
                    $AvailBooked = !empty($AvailabilityData->totBooked) ? $AvailabilityData->totBooked : 0;
                    $AvailTotal = !empty($AvailabilityData->totQuantity) ? $AvailabilityData->totQuantity : 100;
                    $AvailableTotal = !empty($AvailabilityData->totalAvailable) ? $AvailabilityData->totalAvailable : 0;
                    $Availability = round(($AvailableTotal / $AvailTotal) * 100);


                    $OrderMaster = OrderMaster::select(DB::raw('sum(total_rooms) AS totCancelled'))
                        ->whereIn('service_name_id', array_keys($Properties))
                        ->where(['service_type' => 'hotel', 'status' => 'cancelled', 'payment_status' => 'success'])
                        ->whereBetween('cancel_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                        ->first();
                    $BookingCancelled = !empty($OrderMaster->totCancelled) ? $OrderMaster->totCancelled : 0;
                }
            } elseif (in_array('rental', $VendorServices) && $service_type == 'rental') {
                $PropertiesQry = MasterCar::where(['vendor_id' => $vendor_id, 'status' => 'publish']);
                $AllProps = $PropertiesQry->pluck('title', 'id')->toArray();
                if (!empty($propIds)) {
                    $PropertiesQry->whereIn('id', $propIds);
                }
                $Properties = $PropertiesQry->pluck('title', 'id')->toArray();
                if (!empty($Properties)) {
                    foreach ($Properties as $id => $name) {
                        $MasterInventory = RentalMasterInventory::select(DB::raw('sum(total_booked) AS totBooked'), DB::raw('sum(total_available) AS totAvailable'))
                            ->where('car_id', $id)
                            ->whereBetween('date', [$start_date, $end_date])
                            ->first();
                        $totBooked = !empty($MasterInventory->totBooked) ? $MasterInventory->totBooked : 0;
                        $totAvailable = !empty($MasterInventory->totAvailable) ? $MasterInventory->totAvailable : 0;
                        $OrderMaster = OrderMaster::select(DB::raw('sum(service_quantity) AS totCancelled'))
                            ->where(['service_type' => 'car', 'service_name_id' => $id, 'status' => 'cancelled', 'payment_status' => 'success'])
                            ->whereBetween('cancel_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                            ->first();
                        $totalCancelled = !empty($OrderMaster->totCancelled) ? $OrderMaster->totCancelled : 0;
                        $ReservationData[] = array(
                            'prop_id' => $id,
                            'prop_name' => $name,
                            'totBooked' => $totBooked,
                            'totAvailable' => $totAvailable,
                            'totalCancelled' => $totalCancelled
                        );
                    }
                    $AvailabilityData = RentalMasterInventory::select(DB::raw('sum(total_booked) AS totBooked'), DB::raw('sum(initial_quantity) AS totQuantity'), DB::raw('sum(total_available) AS totalAvailable'))
                        ->whereIn('car_id', array_keys($Properties))
                        ->whereBetween('date', [$start_date, $end_date])
                        ->first();
                    $AvailBooked = !empty($AvailabilityData->totBooked) ? $AvailabilityData->totBooked : 0;
                    $AvailTotal = !empty($AvailabilityData->totQuantity) ? $AvailabilityData->totQuantity : 100;
                    $AvailableTotal = !empty($AvailabilityData->totalAvailable) ? $AvailabilityData->totalAvailable : 0;
                    $Availability = round(($AvailableTotal / $AvailTotal) * 100);

                    $OrderMaster = OrderMaster::select(DB::raw('sum(service_quantity) AS totCancelled'))
                        ->whereIn('service_name_id', array_keys($Properties))
                        ->where(['service_type' => 'car', 'status' => 'cancelled', 'payment_status' => 'success'])
                        ->whereBetween('cancel_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                        ->first();
                    $BookingCancelled = !empty($OrderMaster->totCancelled) ? $OrderMaster->totCancelled : 0;
                }
            }
            $ReservationDataObj = json_encode($ReservationData);
        }
        return view('users.dashboard', compact('Customers', 'OnlineOrders', 'OfflineOrders', 'CompletedPercent', 'PendingPercent', 'CancelPercent', 'VendorServices', 'Properties', 'ReservationData', 'Availability', 'BookingCancelled', 'AvailableTotal', 'AvailTotal', 'AvailBooked', 'ReservationDataObj', 'start_date', 'end_date', 'service_type', 'AllProps', 'TopBookings'));
    }

    public function profileEdit()
    {
        $CountryDetail = DB::table('countries')->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        $StateDetail = DB::table('states')->where(['country_id' => Auth::user()->country])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        $CityDetail = DB::table('cities')->where(['state_id' => Auth::user()->state])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
        $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();

        return view('users.profile-edit', compact('CountryDetail', 'StateDetail', 'CityDetail', 'PasswordRemQstn'));
    }

    public function postProfileEdit(Request $request)
    {
        $user = Auth::user();

        $validate = Validator::make($request->all(), [
            'first_name' => 'required|string|min:3|max:15',
            'last_name' => 'required|string|min:3|max:15',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            'password_rem_quetion' => 'required',
            'password_rem_ans' => 'required',
            'password' => 'nullable|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
            'photo' => 'mimes:jpeg,png,jpg',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('profile-edit')->withErrors($validate)->withInput();
        } else {
            if ($request->password) {
                $user->password = bcrypt($request->password);
            }
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->country = $request->country;
            $user->state = $request->state;
            $user->city = $request->city;
            $user->pincode = $request->pincode;
            $user->address = $request->address;
            $user->password_rem_quetion = $request->password_rem_quetion;
            $user->password_rem_ans = $request->password_rem_ans;
            $user->payment_merchand_id = $request->payment_merchand_id;
            $user->modified_by = Auth::user()->id;

            $UploadDir = 'images/profile/';
            if ($request->hasFile('photo')) {
                if ($request->file('photo')->isValid()) {
                    $old_banner = public_path($user->photo);
                    if (!empty($user->photo) && file_exists($old_banner)) {
                        unlink($old_banner);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('photo')->getClientOriginalName());
                    $image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->photo->extension();
                    $request->photo->move(public_path($UploadDir), $image);
                    $user->photo = $UploadDir . $image;
                }
            }
            $user->save();

            Session::flash('success', 'Your profile details updated successfully.');
            return Redirect::to('profile-edit');
        }
    }

    public function cityStateDetails(Request $request)
    {
        if ($request->request_type == 'get_state_details') {
            $stateHtml = '<option value="">Select State</option>';
            $StateDetail = State::where([['country_id', '=', $request->countryId]])->orderBy('name', 'ASC')->get()->toArray();

            foreach ($StateDetail as $states) {
                $stateHtml .= "<option value=" . $states['id'] . ">" . $states['name'] . "</option>";
            }
            echo $stateHtml;
            exit;
        }

        if ($request->request_type == 'get_city_details') {
            $cityHtml = '<option value="">Select City</option>';
            $CityDetail = City::where([['state_id', '=', $request->stateId]])->orderBy('name', 'ASC')->get()->toArray();

            foreach ($CityDetail as $city) {
                $cityHtml .= "<option value=" . $city['id'] . ">" . $city['name'] . "</option>";
            }
            echo $cityHtml;
            exit;
        }
    }

    public function userDetails()
    {
        if (!(parent::checkViewPrivilege(75))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.user-details');
    }

    public function getUserDetails(Request $request)
    {
        $this->layout = "ajax";
        $this->modelClass = "User";
        $this->autoRender = false;

        $aColumns = array('first_name', 'email', 'phone', 'photo', 'birth_date', 'address', 'city', 'state', 'country', 'pincode', 'created_at', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "users";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = ' WHERE role = 4 ';
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exportQuery = "SELECT * FROM   $sTable $sWhere $sOrder";
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        // echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        //print_r($aResultFilterTotal);exit;
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            /* "sEcho" => intval($_GET['sEcho']),
              "iTotalRecords" => $iTotal,
              "iTotalDisplayRecords" => $iFilteredTotal,
              "aaData" => array()
             */
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );
        $City = City::pluck('name', 'id')->toArray();
        $State = State::pluck('name', 'id')->toArray();
        $Country = Country::pluck('name', 'id')->toArray();

        $in = 1;
        foreach ($rResult as $aRow) {
            $row = array();
            $currentstatus = ($aRow->status == 1) ? 'Deactivate' : 'Activate';
            $delete = (Auth::user()->access_type == 'superadmin') ? '<li class="deleteUser" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>' : '';

            $row[] = $aRow->first_name . ' ' . $aRow->last_name;
            $row[] = $aRow->email;
            $row[] = !empty($aRow->phone) ? $aRow->phone : 'N/A';
            $row[] = !empty($aRow->photo) ? '<img height="60" width="80" src="' . $aRow->photo . '">' : 'N/A';
            $row[] = !empty($aRow->birth_date) ? date("d-M-Y", strtotime($aRow->birth_date)) : 'N/A';
            $row[] = !empty($aRow->address) ? $aRow->address : 'N/A';
            $row[] = (!empty($aRow->city) && isset($City[$aRow->city])) ? $City[$aRow->city] : 'N/A';
            $row[] = (!empty($aRow->state) && isset($State[$aRow->state])) ? $State[$aRow->state] : 'N/A';
            $row[] = (!empty($aRow->country) && isset($Country[$aRow->country])) ? $Country[$aRow->country] : 'N/A';
            $row[] = !empty($aRow->pincode) ? $aRow->pincode : 'N/A';
            $row[] = date("d-M-Y", strtotime($aRow->created_at)) . '<br>' . date("h:i a", strtotime($aRow->created_at));
            $row[] = ($aRow->status == 1) ? 'Active' : 'Inactive';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li class="statusModify" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '"><a href="javascript:void(0)">' . $currentstatus . '</a></li>
                    ' . $delete . '
                </ul>
            </div>';

            $output['data'][] = $row;
            $in++;
        }
        $output['exportQuery'] = $exportQuery;

        echo json_encode($output);
        exit;
    }

    public function userOprsn(Request $request)
    {
        if ($request->request_type == 'delete_user') {
            if (!(parent::checkWritePrivilege(75))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $user = User::find($request->userId);
                if (!empty($user)) {
                    $image = public_path($user->photo);
                    if (file_exists($image) && !empty($user->photo)) {
                        unlink($image);
                    }
                    DB::table('service_reviews')->where('user_id', $user->id)->delete();
                    $user->delete();
                    $responce['status'] = 1;
                    $responce['message'] = 'User deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete user !';
                }
            }
        } elseif ($request->request_type == 'userStatusModify') {
            if (!(parent::checkWritePrivilege(75))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $status = ($request->status == 1) ? 0 : 1;
                $userData = User::find($request->userId);
                $statusMessage = ($request->status == 1) ? 'User deactivated successfully' : 'User activated successfully';

                $updateData = ['status' => $status];
                if ($status == 1) {
                    $updateData['wrong_attempts'] = 0;
                }
                $StatusModify = User::whereId($request->userId)->update($updateData);
                if ($StatusModify) {
                    $responce['status'] = 1;
                    $responce['message'] = $statusMessage;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to modify status';
                }
            }
        } elseif ($request->request_type == 'export_user_details') {
            $Users = DB::select($request->exportQuery);
            $csv = "documents/user_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Name', 'Email', 'Phone', 'Birth Date', 'Address', 'City', 'State', 'Country', 'Zip Code', 'Join Date', 'Status');

            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            $City = City::pluck('name', 'id')->toArray();
            $State = State::pluck('name', 'id')->toArray();
            $Country = Country::pluck('name', 'id')->toArray();
            if (!empty($Users)) {
                foreach ($Users as $value) {
                    $data['name'] = $value->first_name . ' ' . $value->last_name;
                    $data['email'] = $value->email;
                    $data['phone'] = $value->phone;
                    $data['birth_date'] = !empty($value->birth_date) ? date("Y-m-d", strtotime($value->birth_date)) : 'N/A';
                    $data['address'] = !empty($value->address) ? $value->address : 'N/A';
                    $data['city'] = (!empty($value->city) && isset($City[$value->city])) ? $City[$value->city] : 'N/A';
                    $data['state'] = (!empty($value->state) && isset($State[$value->state])) ? $State[$value->state] : 'N/A';
                    $data['country'] = (!empty($value->country) && isset($Country[$value->country])) ? $Country[$value->country] : 'N/A';
                    $data['pincode'] = !empty($value->pincode) ? $value->pincode : 'N/A';
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));
                    $data['status'] = ($value->status == 1) ? 'Active' : 'Inactive';

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_agent_details') {
            $Users = DB::select($request->exportQuery);
            $csv = "documents/user_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Name', 'Email', 'Phone', 'Vendor', 'Commission', 'Status', 'Address', 'City', 'State', 'Country', 'Zip Code', 'Join Date');

            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            $City = City::pluck('name', 'id')->toArray();
            $State = State::pluck('name', 'id')->toArray();
            $Country = Country::pluck('name', 'id')->toArray();
            $Vendor = User::where('role', 2)->pluck('company', 'id')->toArray();
            if (!empty($Users)) {
                foreach ($Users as $value) {
                    $data['name'] = $value->first_name . ' ' . $value->last_name;
                    $data['email'] = $value->email;
                    $data['phone'] = $value->phone;
                    $data['vendor'] = !empty(isset($Vendor[$value->vendor_id])) ? $Vendor[$value->vendor_id] : 'N/A';
                    $data['comm'] = $value->agent_comission;
                    $data['status'] = ($value->status == 1) ? 'Active' : 'Inactive';
                    $data['address'] = !empty($value->address) ? $value->address : 'N/A';
                    $data['city'] = (!empty($value->city) && isset($City[$value->city])) ? $City[$value->city] : 'N/A';
                    $data['state'] = (!empty($value->state) && isset($State[$value->state])) ? $State[$value->state] : 'N/A';
                    $data['country'] = (!empty($value->country) && isset($Country[$value->country])) ? $Country[$value->country] : 'N/A';
                    $data['pincode'] = !empty($value->pincode) ? $value->pincode : 'N/A';
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }

        echo json_encode($responce);
        exit;
    }

    public function vendorDetails()
    {
        if (!(parent::checkViewPrivilege(3))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.vendor-details');
    }

    public function getVendorDetails(Request $request)
    {
        $this->layout = "ajax";
        $this->modelClass = "User";
        $this->autoRender = false;

        $aColumns = array('company', 'first_name', 'last_name', 'email', 'phone', 'payment_merchand_id', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "users";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = ' WHERE role = 2 ';
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        //print_r($aResultFilterTotal);exit;
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            /* "sEcho" => intval($_GET['sEcho']),
              "iTotalRecords" => $iTotal,
              "iTotalDisplayRecords" => $iFilteredTotal,
              "aaData" => array()
             */
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        $in = 1;
        foreach ($rResult as $aRow) {
            $row = array();
            $currentstatus = ($aRow->status == 1) ? 'Deactivate' : 'Activate';

            $row[] = $aRow->company;
            $row[] = $aRow->first_name;
            $row[] = $aRow->last_name;
            $row[] = $aRow->email;
            $row[] = $aRow->phone;
            $row[] = $aRow->payment_merchand_id;
            $row[] = ($aRow->status == 1) ? 'Active' : 'Inactive';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('vendor-edit', $aRow->id) . '">Edit</a></li>
                    <li class="deleteVendor" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                    <li class="statusModify" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '"><a href="javascript:void(0)">' . $currentstatus . '</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
            $in++;
        }

        echo json_encode($output);
        exit;
    }

    public function vendorOprsn(Request $request)
    {
        if ($request->request_type == 'delete_vendor') {
            if (!(parent::checkWritePrivilege(3))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $deleteVendor = User::find($request->vendorId)->delete();
                if ($deleteVendor) {
                    User::where('vendor_id', $request->vendorId)->delete();
                    CancelPolicy::where('vendor_id', $request->vendorId)->delete();
                    GstTable::where('vendor_id', $request->vendorId)->delete();
                    $Hotels = MasterHotel::where('vender_id', $request->vendorId)->pluck('name', 'id');
                    if (!empty($Hotels)) {
                        foreach ($Hotels as $key => $value) {
                            $MasterHotel = MasterHotel::find($key);
                            if (!empty($MasterHotel)) {
                                $banner_image = public_path($MasterHotel->banner_image);
                                if (file_exists($banner_image) && !empty($MasterHotel->banner_image)) {
                                    unlink($banner_image);
                                }
                                $feature_image = public_path($MasterHotel->feature_image);
                                if (file_exists($feature_image) && !empty($MasterHotel->feature_image)) {
                                    unlink($feature_image);
                                }
                                $gallery = json_decode($MasterHotel->gallery, 1);
                                foreach ($gallery as $images) {
                                    $image = public_path($images);
                                    if (file_exists($image)) {
                                        unlink($image);
                                    }
                                }
                                $MasterHotel->delete();
                                $HotelRooms = HotelRoom::where('hotel_id', $MasterHotel->id)->get();
                                if (!empty($HotelRooms)) {
                                    foreach ($HotelRooms as $rooms) {
                                        $room_image = public_path($rooms->image);
                                        if (file_exists($room_image) && !empty($rooms->image)) {
                                            unlink($room_image);
                                        }
                                        $gallery = json_decode($rooms->gallery, 1);
                                        foreach ($gallery as $images) {
                                            $image = public_path($images);
                                            if (file_exists($image)) {
                                                unlink($image);
                                            }
                                        }
                                    }
                                    HotelRoom::where('hotel_id', $MasterHotel->id)->delete();
                                }
                                MasterInventory::where('hotel_id', $MasterHotel->id)->delete();
                                BlockedHotel::where('hotel_id', $MasterHotel->id)->delete();
                                HotelAvailability::where('hotel_id', $MasterHotel->id)->delete();
                                HotelRoomPricing::where('hotel_id', $MasterHotel->id)->delete();
                                HotelSale::where('hotel_id', $MasterHotel->id)->delete();
                            }
                        }
                    }
                    $Cars = MasterCar::where('vendor_id', $request->vendorId)->pluck('title', 'id');
                    if (!empty($Cars)) {
                        foreach ($Cars as $key => $value) {
                            $MasterCar = MasterCar::find($key);
                            if (!empty($MasterCar)) {
                                $banner_image = public_path($MasterCar->banner_image);
                                if (file_exists($banner_image) && !empty($MasterCar->banner_image)) {
                                    unlink($banner_image);
                                }
                                $feature_image = public_path($MasterCar->feature_image);
                                if (file_exists($feature_image) && !empty($MasterCar->feature_image)) {
                                    unlink($feature_image);
                                }
                                $gallery = json_decode($MasterCar->gallery, 1);
                                foreach ($gallery as $images) {
                                    $image = public_path($images);
                                    if (file_exists($image)) {
                                        unlink($image);
                                    }
                                }
                                $MasterCar->delete();

                                RentalMasterInventory::where('car_id', $MasterCar->id)->delete();
                                RentalAvailability::where('car_id', $MasterCar->id)->delete();
                            }
                        }
                    }
                    $Tours = Tour::where('vendor_id', $request->vendorId)->pluck('name', 'id');
                    if (!empty($Tours)) {
                        foreach ($Tours as $key => $value) {
                            $Tour = Tour::find($key);
                            if (!empty($Tour)) {
                                $banner_image = public_path($Tour->banner_image);
                                if (file_exists($banner_image) && !empty($Tour->banner_image)) {
                                    unlink($banner_image);
                                }
                                $feature_image = public_path($Tour->feature_image);
                                if (file_exists($feature_image) && !empty($Tour->feature_image)) {
                                    unlink($feature_image);
                                }
                                $gallery = json_decode($Tour->gallery, 1);
                                foreach ($gallery as $images) {
                                    $image = public_path($images);
                                    if (file_exists($image)) {
                                        unlink($image);
                                    }
                                }
                                $Tour->delete();

                                SightSeenPricing::where('tour_id', $Tour->id)->delete();
                                TourAvailability::where('tour_id', $Tour->id)->delete();
                            }
                        }
                    }
                    $Tickets = Ticket::where('vendor_id', $request->vendorId)->pluck('name', 'id');
                    if (!empty($Tickets)) {
                        foreach ($Tickets as $key => $value) {
                            $Ticket = Ticket::find($key);
                            if (!empty($Ticket)) {
                                $banner_image = public_path($Ticket->banner_image);
                                if (file_exists($banner_image) && !empty($Ticket->banner_image)) {
                                    unlink($banner_image);
                                }
                                $feature_image = public_path($Ticket->feature_image);
                                if (file_exists($feature_image) && !empty($Ticket->feature_image)) {
                                    unlink($feature_image);
                                }
                                $gallery = json_decode($Ticket->gallery, 1);
                                foreach ($gallery as $images) {
                                    $image = public_path($images);
                                    if (file_exists($image)) {
                                        unlink($image);
                                    }
                                }
                                $Ticket->delete();

                                TicketAvailability::where('ticket_id', $Ticket->id)->delete();
                            }
                        }
                    }
                    $FoodItems = FoodItem::where('vendor_id', $request->vendorId)->pluck('item_name', 'id');
                    if (!empty($FoodItems)) {
                        foreach ($FoodItems as $key => $value) {
                            $Item = FoodItem::find($key);
                            if (!empty($Item)) {
                                $banner_image = public_path($Item->image);
                                if (file_exists($banner_image) && !empty($Item->image)) {
                                    unlink($banner_image);
                                }
                                $Item->delete();
                            }
                        }
                    }
                    $MerchantProducts = MerchantProduct::where('vendor_id', $request->vendorId)->pluck('name', 'id');
                    if (!empty($MerchantProducts)) {
                        foreach ($MerchantProducts as $key => $value) {
                            $Item = MerchantProduct::find($key);
                            if (!empty($Item)) {
                                $banner_image = public_path($Item->feature_image);
                                if (file_exists($banner_image) && !empty($Item->feature_image)) {
                                    unlink($banner_image);
                                }
                                $gallery = json_decode($Item->gallery_image, 1);
                                foreach ($gallery as $images) {
                                    $image = public_path($images);
                                    if (file_exists($image)) {
                                        unlink($image);
                                    }
                                }
                                $Item->delete();
                            }
                        }
                    }

                    $responce['status'] = 1;
                    $responce['message'] = 'Vendor details deleted successfully';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete the vendor';
                }
            }
        } elseif ($request->request_type == 'vendorStatusModify') {
            if (!(parent::checkWritePrivilege(3))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $status = ($request->status == 1) ? 0 : 1;
                $statusMessage = ($request->status == 1) ? 'Vendor deactivate successful' : 'Vendor activate successful';

                $updateData = ['status' => $status];
                if ($status == 1) {
                    $updateData['wrong_attempts'] = 0;
                }
                $StatusModify = User::whereId($request->vendorId)->update($updateData);
                if ($StatusModify) {
                    if ($status == 0) {
                        User::where('vendor_id', $request->vendorId)->update(['status' => 0]);
                        $Hotels = MasterHotel::where('vender_id', $request->vendorId)->pluck('id')->toArray();
                        if (!empty($Hotels)) {
                            MasterHotel::where('vender_id', $request->vendorId)->update(['status' => 'draft']);
                            HotelRoom::whereIn('hotel_id', $Hotels)->update(['status' => 'draft']);
                        }
                        MasterCar::where('vendor_id', $request->vendorId)->update(['status' => 'draft']);
                        Tour::where('vendor_id', $request->vendorId)->update(['status' => 'draft']);
                        Ticket::where('vendor_id', $request->vendorId)->update(['status' => 'draft']);
                        FoodItem::where('vendor_id', $request->vendorId)->update(['status' => 'draft']);
                        MerchantProduct::where('vendor_id', $request->vendorId)->update(['status' => 'draft']);
                    }
                    $responce['status'] = 1;
                    $responce['message'] = $statusMessage;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to modify status';
                }
            }
        } elseif ($request->request_type == 'get_property_images') {
            $VendorData = VendorRequest::find($request->vendorId);
            if (!empty($VendorData)) {
                $images = json_decode($VendorData->property_image, 1);
                $images = array_map(function ($val) {
                    return $this->site . $val;
                }, $images);

                $responce['status'] = 1;
                $responce['data'] = $images;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get image.';
            }
        } elseif ($request->request_type == 'delete_vendor_request') {
            if (!(parent::checkWritePrivilege(82))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $VendorRequest = VendorRequest::find($request->vendorId);
                if (!empty($VendorRequest)) {
                    $feature_image = public_path($VendorRequest->gst_certificate);
                    if (file_exists($feature_image) && !empty($VendorRequest->gst_certificate)) {
                        unlink($feature_image);
                    }
                    $gallery = json_decode($VendorRequest->property_image, 1);
                    foreach ($gallery as $images) {
                        $image = public_path($images);
                        if (file_exists($image)) {
                            unlink($image);
                        }
                    }
                    $VendorRequest->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Vendor request deleted successfully';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete the vendor request';
                }
            }
        } elseif ($request->request_type == 'approve_vendor_request') {
            if (!(parent::checkWritePrivilege(82))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $VendorRequest = VendorRequest::find($request->vendorId);
                if (!empty($VendorRequest)) {

                    $user = new User([
                        'services' => $request->services,
                        'company' => $VendorRequest->enterprise_name,
                        'first_name' => $VendorRequest->first_name,
                        'last_name' => $VendorRequest->last_name,
                        'email' => $VendorRequest->email,
                        'phone' => $VendorRequest->phone,
                        'password' => bcrypt($request->password),
                        'admin_commission' => $request->commission,
                        'role' => 2,
                        'access_type' => 'vendor',
                        'vendor_id' => 0,
                        'created_by' => Auth::user()->id,
                    ]);
                    $user->save();
                    $VendorRequest->admin_commission = $request->commission;
                    $VendorRequest->status = 1;
                    $VendorRequest->save();

                    $UserTemplete = EmailTemplate::where('ref_code', 'VendorRegistrationApproved')->first();
                    $Message = str_replace(array("~firstname~", "~lastname~", "~website_url~", "~site_url~", "~adminurl~", "~email~", "~password~"), array($VendorRequest->first_name, $VendorRequest->last_name, $this->frontendUrl, $this->site, $this->site, $VendorRequest->email, $request->password), $UserTemplete->source);
                    $Subject = $UserTemplete->subject;
                    Mail::to($VendorRequest->email)->send(new \App\Mail\RegistrationMailUser($Message, $Subject));

                    $responce['status'] = 1;
                    $responce['message'] = 'Vendor request approved successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to approve vendor request';
                }
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function vendorAdd()
    {
        if (!(parent::checkWritePrivilege(3))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $CountryDetail = DB::table('countries')->pluck('name', 'id')->toArray();
        $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();
        $Services = Service::all()->toArray();

        return view('users.vendor-add', compact('CountryDetail', 'PasswordRemQstn', 'Services'));
    }

    public function vendorAddRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'company' => 'required',
            'admin_commission' => 'required',
            'services' => 'required',
            'first_name' => 'required|string|min:3|max:15',
            'last_name' => 'required|string|min:3|max:15',
            'email' => 'required|email|unique:users',
            'phone' => 'required|digits:10|unique:users',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            'password_rem_quetion' => 'required',
            'password_rem_ans' => 'required',
            'password' => 'confirmed|required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
            'password_confirmation' => 'required|min:8'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('vendor-add')->withErrors($validate)->withInput();
        } else {
            $user = DB::table('users')->insert([
                'services' => json_encode($request->services),
                'company' => $request->company,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'role' => 2,
                'access_type' => 'vendor',
                'vendor_id' => 0,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'pincode' => $request->pincode,
                'address' => $request->address,
                'admin_commission' => $request->admin_commission,
                'password_rem_quetion' => $request->password_rem_quetion,
                'password_rem_ans' => $request->password_rem_ans,
                'payment_merchand_id' => $request->payment_merchand_id,
                'created_by' => Auth::user()->id,
                'modified_by' => Auth::user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Session::flash('success', 'Vendor details added successfully.');
            return Redirect::to('vendor-details');
        }
    }

    public function vendorEdit($id = null)
    {
        if (!(parent::checkWritePrivilege(3))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $VendorDetails = User::find($id);
        if (!empty($VendorDetails)) {
            $CountryDetail = DB::table('countries')->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $StateDetail = DB::table('states')->where(['country_id' => $VendorDetails->country])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $CityDetail = DB::table('cities')->where(['state_id' => $VendorDetails->state])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();
            $Services = Service::all()->toArray();

            return view('users.vendor-edit', compact('CountryDetail', 'StateDetail', 'CityDetail', 'PasswordRemQstn', 'VendorDetails', 'Services'));
        } else {
            return redirect()->back();
        }
    }

    public function vendorEditRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'company' => 'required',
            'admin_commission' => 'required',
            'services' => 'required',
            'first_name' => 'required|string|min:3|max:15',
            'last_name' => 'required|string|min:3|max:15',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            'password_rem_quetion' => 'required',
            'password_rem_ans' => 'required',
            'password' => 'nullable|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('vendor-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $user = User::find($request->id);
            $user->services = json_encode($request->services);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->country = $request->country;
            $user->state = $request->state;
            $user->city = $request->city;
            $user->pincode = $request->pincode;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->admin_commission = $request->admin_commission;
            $user->password_rem_quetion = $request->password_rem_quetion;
            $user->password_rem_ans = $request->password_rem_ans;
            $user->payment_merchand_id = $request->payment_merchand_id;
            $user->modified_by = Auth::user()->id;
            $user->updated_at = date('Y-m-d H:i:s');
            if (!empty($request->password)) {
                $user->password = bcrypt($request->password);
            }
            $user->save();
            DB::table('users')->where(['vendor_id' => $request->id, 'access_type' => 'vendor'])->update(['services' => json_encode($request->services)]);

            Session::flash('success', 'Vendor details updated successfully.');
            return Redirect::to('vendor-details');
        }
    }

    public function staffDetails()
    {
        if (!(parent::checkViewPrivilege(4))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.staff-details');
    }

    public function getStaffDetails(Request $request)
    {
        $this->layout = "ajax";
        $this->modelClass = "User";
        $this->autoRender = false;

        $aColumns = array('first_name', 'email', 'phone', 'access_type', 'vendor_id', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "users";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        if (Auth::user()->role == 1) {
            $sWhere = 'WHERE vendor_id != 0  AND id != ' . Auth::user()->id;
        } else if (Auth::user()->role == 2 && Auth::user()->vendor_id == 0) {
            $sWhere = 'WHERE vendor_id = ' . Auth::user()->id;
        } else if (Auth::user()->role == 2 && Auth::user()->vendor_id != 0) {
            $sWhere = 'WHERE vendor_id = ' . Auth::user()->vendor_id . ' AND id != ' . Auth::user()->id;
        } else if (Auth::user()->role == 3) {
            $sWhere = 'WHERE vendor_id != 0 AND access_type = "vendor" AND id != ' . Auth::user()->id;
        }
        $sWhere .= ' AND access_type != "agent"';
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        $rResult = DB::select($sQuery);
        //print_r($rResult);exit;

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        //print_r($aResultFilterTotal);exit;
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            /* "sEcho" => intval($_GET['sEcho']),
              "iTotalRecords" => $iTotal,
              "iTotalDisplayRecords" => $iFilteredTotal,
              "aaData" => array()
             */
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        $in = 1;
        foreach ($rResult as $aRow) {
            $row = array();
            $currentstatus = ($aRow->status == 1) ? 'Deactivate' : 'Activate';
            $vendorData = User::find($aRow->vendor_id);

            $privilege_link = ($aRow->vendor_id == Auth::user()->id) ? '<li><a href="' . url('manage-privilege', $aRow->id) . '">Manage Privilege</a></li>' : '';

            $user_type = ($aRow->user_role == 'agent_staff') ? 'Offline Agent' : 'Sub-user';

            $row[] = $aRow->first_name . ' ' . $aRow->last_name;
            $row[] = $aRow->email;
            $row[] = $aRow->phone;
            $row[] = $user_type;
            $row[] = $vendorData->company;
            $row[] = ($aRow->status == 1) ? 'Active' : 'Inactive';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('subuser-edit', $aRow->id) . '">Edit</a></li>
                    <li class="deleteStaff" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                    <li class="statusModify" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '"><a href="javascript:void(0)">' . $currentstatus . '</a></li>
                    ' . $privilege_link . '
                </ul>
            </div>';

            $output['data'][] = $row;
            $in++;
        }

        echo json_encode($output);
        exit;
    }

    public function staffOprsn(Request $request)
    {
        if ($request->request_type == 'delete_staff') {
            if (!(parent::checkWritePrivilege(5))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $StaffData = User::find($request->staffId);
                $suerType = ($StaffData->access_type == 'vendor') ? 'Staff' : 'Agent';

                $deleteStaff = User::find($request->staffId)->delete();
                if ($deleteStaff) {
                    $responce['status'] = 1;
                    $responce['message'] = $suerType . ' deleted successful';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete staff!';
                }
            }
        } elseif ($request->request_type == 'staffStatusModify') {
            if (!(parent::checkWritePrivilege(5))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $status = ($request->status == 1) ? 0 : 1;
                $StaffData = User::find($request->staffId);
                $suerType = ($StaffData->access_type == 'vendor') ? 'Staff' : 'Agent';
                $statusMessage = ($request->status == 1) ? $suerType . ' deactivate successful' : $suerType . ' activate successful';

                $updateData = ['status' => $status];
                if ($status == 1) {
                    $updateData['wrong_attempts'] = 0;
                }
                $StatusModify = User::whereId($request->staffId)->update($updateData);
                if ($StatusModify) {
                    $responce['status'] = 1;
                    $responce['message'] = $statusMessage;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to modify status';
                }
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function staffAdd()
    {
        if (!(parent::checkWritePrivilege(4))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $CountryDetail = DB::table('countries')->pluck('name', 'id')->toArray();
        $VendorDetails = DB::table('users')->where('role', '2')->orderBy('company', 'asc')->pluck('company', 'id')->toArray();
        $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();

        return view('users.staff-add', compact('CountryDetail', 'PasswordRemQstn', 'VendorDetails'));
    }

    public function staffAddRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'vendor' => 'required',
            'first_name' => 'required|string|min:3|max:25',
            'last_name' => 'required|string|min:3|max:25',
            'email' => 'required|email|unique:users',
            'phone' => 'required|digits:10|unique:users',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            'user_payment' => 'required|string',
            'user_book_from' => 'required|string',
            'password' => 'confirmed|required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
            'password_confirmation' => 'required|min:8'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('subuser-add')->withErrors($validate)->withInput();
        } else {
            $VendorData = User::find($request->vendor);
            $user_role = ($request->user_role == 'agent_staff') ? 'agent_staff' : '';

            $user = DB::table('users')->insert([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'role' => 3,
                'access_type' => (Auth::user()->role == 1 && $request->vendor == Auth::user()->id) ? 'superadmin' : 'vendor',
                'user_role' => $user_role,
                'user_payment' => $request->user_payment,
                'user_book_from' => $request->user_book_from,
                'vendor_id' => $request->vendor,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'pincode' => $request->pincode,
                'address' => $request->address,
                'password_rem_quetion' => $request->password_rem_quetion,
                'password_rem_ans' => $request->password_rem_ans,
                'services' => $VendorData->services,
                'created_by' => Auth::user()->id,
                'modified_by' => Auth::user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Session::flash('success', 'Subuser details added successfully.');
            return Redirect::to('subuser-details');
        }
    }

    public function staffEdit($id = null)
    {
        if (!(parent::checkWritePrivilege(4))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $StaffDetails = User::find($id);
        if (!empty($StaffDetails)) {
            $CountryDetail = DB::table('countries')->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $StateDetail = DB::table('states')->where(['country_id' => $StaffDetails->country])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $CityDetail = DB::table('cities')->where(['state_id' => $StaffDetails->state])->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();
            $VendorDetails = DB::table('users')->where('role', '2')->orderBy('company', 'asc')->pluck('company', 'id')->toArray();

            return view('users.staff-edit', compact('CountryDetail', 'StateDetail', 'CityDetail', 'PasswordRemQstn', 'VendorDetails', 'StaffDetails'));
        } else {
            return redirect()->back();
        }
    }

    public function staffEditRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'vendor' => 'required',
            'first_name' => 'required|string|min:3|max:25',
            'last_name' => 'required|string|min:3|max:25',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            // 'password_rem_quetion' => 'required',
            // 'password_rem_ans' => 'required',
            'password' => 'nullable|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('subuser-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $user_role = ($request->user_role == 'agent_staff') ? 'agent_staff' : '';
            $user = User::find($request->id);
            $user->vendor_id = $request->vendor;
            $user->access_type = (Auth::user()->role == 1 && $request->vendor == Auth::user()->id) ? 'superadmin' : 'vendor';
            $user->user_role = $user_role;
            $user->user_payment = $request->user_payment;
            $user->user_book_from = $request->user_book_from;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->country = $request->country;
            $user->state = $request->state;
            $user->city = $request->city;
            $user->pincode = $request->pincode;
            $user->address = $request->address;
            $user->password_rem_quetion = $request->password_rem_quetion;
            $user->password_rem_ans = $request->password_rem_ans;
            $user->payment_merchand_id = $request->payment_merchand_id;
            $user->modified_by = Auth::user()->id;
            $user->updated_at = date('Y-m-d H:i:s');
            if (!empty($request->password)) {
                $user->password = bcrypt($request->password);
            }
            $user->save();

            Session::flash('success', 'Subuser details updated successfully.');
            return Redirect::to('subuser-details');
        }
    }

    public function manageHomepage()
    {
        if (!(parent::checkViewPrivilege(34))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $HomepageData = PageContent::where('type', 'home-page')->get();
        $gallery = array();
        $count = 1;
        $content = json_decode($HomepageData[0]->content, 1);
        $banner_image = json_decode($content['banner_image']);
        foreach ($banner_image as $value) {
            $gallery[] = ['id' => $count, 'src' => $this->site . $value];
            $count++;
        }
        $gallery = json_encode($gallery);

        return view('users.manage-homepage', compact('HomepageData', 'gallery', 'content'));
    }

    public function homepageEditRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(34))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'email' => 'required|string',
            'logo' => 'mimes:jpeg,png,jpg',
            'images.*' => 'mimes:jpeg,png,jpg',
            'phone' => 'required|string',
            'fb_link' => 'required|string',
            'twitter_link' => 'required|string',
            'youtube_link' => 'required|string',
            'instagram_link' => 'required|string',
            'pinterest_link' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('manage-homepage')->withErrors($validate)->withInput();
        } else {
            $HomepageData = PageContent::find($request->id);
            $content = json_decode($HomepageData->content, 1);
            $content['contact_email'] = $request->email;
            $content['contact_phone'] = $request->phone;
            $content['fb_link'] = $request->fb_link;
            $content['twitter_link'] = $request->twitter_link;
            $content['youtube_link'] = $request->youtube_link;
            $content['instagram_link'] = $request->instagram_link;
            $content['pinterest_link'] = $request->pinterest_link;

            $UploadDir = 'images/frontend/';
            $gallery_images = array();
            if ($request->hasFile('logo')) {
                if ($request->file('logo')->isValid()) {
                    $old_logo = public_path($content['logo']);
                    if (file_exists($old_logo)) {
                        unlink($old_logo);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('logo')->getClientOriginalName());
                    $logo_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->logo->extension();
                    $request->logo->move(public_path($UploadDir), $logo_image);
                    $content['logo'] = $UploadDir . $logo_image;
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                    $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $file->extension();
                    $file->move(public_path($UploadDir), $gallery_image);
                    array_push($gallery_images, $UploadDir . $gallery_image);
                }
            }
            $old_gallery = !empty($content['banner_image']) ? json_decode($content['banner_image']) : [];
            $preload_data = ($request->oldimage) ? $request->oldimage : [];
            if (count($preload_data) != count($old_gallery)) {
                $temp = 1;
                foreach ($old_gallery as $key => $value) {
                    if (!in_array($temp, $preload_data)) {
                        if (file_exists(public_path($value))) {
                            unlink(public_path($value));
                            unset($old_gallery[$key]);
                        }
                    }
                    $temp++;
                }
            }
            $gallery_images = array_merge($old_gallery, $gallery_images);
            $content['banner_image'] = json_encode($gallery_images);
            $HomepageData->content = json_encode($content);
            $HomepageData->update_user = Auth::user()->id;
            if ($HomepageData->save()) {
                Session::flash('success', 'Homepage details updated successful.');
                return Redirect::to('manage-homepage');
            } else {
                Session::flash('success', 'Unable to update homepage details!');
                return Redirect::to('manage-homepage');
            }
        }
    }

    public function managePages()
    {
        if (!(parent::checkViewPrivilege(35))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.manage-pages');
    }

    public function getPages(Request $request)
    {

        $aColumns = array('title', 'image', 'content', 'slug', 'updated_at', 'id');
        $sIndexColumn = "id";
        $sTable = "page_contents";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $sWhere = 'WHERE type = "pages"';
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $condition1 .= ' AND vender_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $condition2 .= ' AND name LIKE "%' . $_POST['searchValue2'] . '%"';
            }
            $sWhere .= $condition1 . $condition2;
        }
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $row[] = $aRow->title;
            $row[] = !empty($aRow->image) ? '<img src="' . $aRow->image . '"  height="80" width="100">' : 'N/A';
            $row[] = (strlen($aRow->content) > 50) ? substr(utf8_encode($aRow->content), 0, 50) . '...' : utf8_encode($aRow->content); //$aRow->content;
            $row[] = $aRow->slug;
            $row[] = date("M d Y", strtotime($aRow->updated_at));
            $row[] = '<a href="' . url('edit-pages', $aRow->id) . '" class="btn btn-primary btn-sm" data-id="' . $aRow->id . '"><i class="fa fa-edit"></i>Edit</a>';
            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function addPages(Request $request)
    {
        if (!(parent::checkWritePrivilege(35))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        return view('users.add-pages');
    }

    public function addPageRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string',
            'slug' => 'required|string',
            'banner_image' => 'mimes:jpeg,png,jpg',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-pages')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/pages/';
            $banner_image = '';
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                }
            }
            $Page = new PageContent([
                'type' => 'pages',
                'title' => $request->title,
                'content' => addslashes($request->content),
                'image' => !empty($banner_image) ? $UploadDir . $banner_image : '',
                'slug' => $request->slug,
                'create_user' => Auth::user()->id,
            ]);
            if ($Page->save()) {
                Session::flash('success', 'Page added successful.');
                return Redirect::to('manage-pages');
            } else {
                Session::flash('success', 'Unable to add page');
                return Redirect::to('add-pages');
            }
        }
    }

    public function editPages($id = null)
    {
        if (!(parent::checkWritePrivilege(35))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $PageContent = PageContent::find($id);
        if (!empty($PageContent)) {
            $PageContent->image = !empty($PageContent->image) ? $this->site . $PageContent->image : $PageContent->image;
            return view('users.edit-pages', compact('PageContent'));
        } else {
            return redirect()->back();
        }
    }

    public function editPageRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string',
            'slug' => 'required|string',
            'banner_image' => 'mimes:jpeg,png,jpg',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-pages/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $PageContent = PageContent::find($request->id);
            $PageContent->title = $request->title;
            $PageContent->content = addslashes($request->content);
            $PageContent->slug = $request->slug;

            $UploadDir = 'images/pages/';
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $old_image = public_path($PageContent->image);
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                    $PageContent->image = $UploadDir . $banner_image;
                }
            }
            $PageContent->update_user = Auth::user()->id;
            if ($PageContent->save()) {
                Session::flash('success', 'Page updated successful.');
                return Redirect::to('manage-pages');
            } else {
                Session::flash('success', 'Unable to update page');
                return Redirect::to('edit-pages/' . $request->id);
            }
        }
    }

    public function manageExclusives()
    {
        if (!(parent::checkViewPrivilege(84))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.manage-exclusives');
    }

    public function getExclusives(Request $request)
    {

        $aColumns = array('title', 'url', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "page_contents";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $sWhere = 'WHERE type = "exclusive"';
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $condition1 .= ' AND vender_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $condition2 .= ' AND name LIKE "%' . $_POST['searchValue2'] . '%"';
            }
            $sWhere .= $condition1 . $condition2;
        }
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $currentstatus = ($aRow->status == 1) ? 'Deactivate' : 'Activate';
            $link = (strlen($aRow->url) > 20) ? substr(utf8_encode($aRow->url), 0, 20) . '...' : utf8_encode($aRow->url);

            $row[] = $aRow->title;
            $row[] = '<a href="' . $aRow->url . '" target="_blank">' . $link . '</a>';
            $row[] = ($aRow->status == 1) ? 'Active' : 'Inactive';
            $row[] = $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('edit-exclusives', $aRow->id) . '">Edit</a></li>
                    <li class="statusModify" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '"><a href="javascript:void(0)">' . $currentstatus . '</a></li>
                    <li class="deleteExclusive" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function addExclusives(Request $request)
    {
        if (!(parent::checkWritePrivilege(84))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        return view('users.add-exclusives');
    }

    public function addExclusivesRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'url' => 'required|string',
            'status' => 'required',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-exclusives')->withErrors($validate)->withInput();
        } else {
            $Page = new PageContent([
                'type' => 'exclusive',
                'title' => $request->title,
                'url' => $request->url,
                'status' => $request->status,
                'create_user' => Auth::user()->id
            ]);
            if ($Page->save()) {
                Session::flash('success', 'Exclusive added successful.');
                return Redirect::to('manage-exclusives');
            } else {
                Session::flash('success', 'Unable to add exclusive');
                return Redirect::to('add-exclusives');
            }
        }
    }

    public function editExclusives($id = null)
    {
        if (!(parent::checkWritePrivilege(84))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $PageContent = PageContent::find($id);
        if (!empty($PageContent)) {
            return view('users.edit-exclusives', compact('PageContent'));
        } else {
            return redirect()->back();
        }
    }

    public function editExclusivesRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'url' => 'required|string',
            'status' => 'required',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-exclusives/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $PageContent = PageContent::find($request->id);
            $PageContent->title = $request->title;
            $PageContent->url = $request->url;
            $PageContent->status = $request->status;
            $PageContent->update_user = Auth::user()->id;

            if ($PageContent->save()) {
                Session::flash('success', 'Exclusive updated successfully.');
                return Redirect::to('manage-exclusives');
            } else {
                Session::flash('success', 'Unable to update exclusive');
                return Redirect::to('edit-exclusives/' . $request->id);
            }
        }
    }

    public function manageSlider(Request $request)
    {
        if (!(parent::checkViewPrivilege(81))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.manage-slider');
    }

    public function getSlider(Request $request)
    {

        $aColumns = array('section', 'image', 'start_time', 'content', 'url', 'status', 'create_user', 'id');
        $sIndexColumn = "id";
        $sTable = "page_contents";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condition = ' AND create_user = ' . $vendor_id;
        }
        $sWhere = 'WHERE type = "slider"' . $vendor_condition;
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $condition1 .= ' AND vender_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $condition2 .= ' AND name LIKE "%' . $_POST['searchValue2'] . '%"';
            }
            $sWhere .= $condition1 . $condition2;
        }
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $createdBy = 'N/A';
            $User = User::find($aRow->create_user);
            if (!empty($User)) {
                $createdBy = ($User->access_type == 'vendor') ? $User->company : 'Admin';
            }
            $currentstatus = ($aRow->status == 1) ? 'Deactivate' : 'Activate';
            $options = '';
            if (Auth::user()->access_type == 'superadmin') {
                $options .= '<li class="statusModify" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '"><a href="javascript:void(0)">' . $currentstatus . '</a></li><li class="deleteSlider" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>';
            }
            $link = (strlen($aRow->url) > 20) ? substr(utf8_encode($aRow->url), 0, 20) . '...' : utf8_encode($aRow->url);
            $content = (strlen($aRow->content) > 20) ? substr(utf8_encode($aRow->content), 0, 20) . '...' : utf8_encode($aRow->content);

            $row[] = $aRow->section;
            $row[] = !empty($aRow->image) ? '<img src="' . $aRow->image . '"  height="80" width="100">' : 'N/A';
            $row[] = ($aRow->display_type == 'partial') ? date("d-M-Y h:i a", strtotime($aRow->start_time)) . ' -<br>' . date("d-M-Y h:i a", strtotime($aRow->end_time)) : 'All Time';
            $row[] = !empty($aRow->content) ? '<a title="Click to view full data" href="javascript:void(0)" class="banner-content" data-content="' . utf8_encode($aRow->content) . '" data-toggle="modal" data-target="#viewModal">' . $content . '</a>' : 'N/A';
            $row[] = !empty($aRow->url) ? '<a href="' . $aRow->url . '" target="_blank">' . $link . '</a>' : 'N/A';
            $row[] = ($aRow->status == 1) ? 'Active' : 'Inactive';
            $row[] = $createdBy;
            $row[] = $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('edit-slider', $aRow->id) . '">Edit</a></li>
                    ' . $options . '
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function sliderOprsn(Request $request)
    {
        if ($request->request_type == 'change_slider_status') {
            if (!(parent::checkWritePrivilege(81))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Slider = PageContent::find($request->Id);
                if (!empty($Slider)) {
                    $status = ($Slider->status == 1) ? 0 : 1;
                    $stat_msg = ($Slider->status == 1) ? 'deactivated' : 'activated';
                    $Slider->status = $status;
                    $Slider->save();
                    $responce['status'] = 1;
                    $responce['message'] = "Slider $stat_msg successfully.";
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to do this operation.';
                }
            }
        } elseif ($request->request_type == 'delete_slider') {
            if (!(parent::checkWritePrivilege(81))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Slider = PageContent::find($request->Id);
                if (!empty($Slider)) {
                    $image = public_path($Slider->image);
                    if (file_exists($image) && !empty($Slider->image)) {
                        unlink($image);
                    }
                    $Slider->delete();
                    $responce['status'] = 1;
                    $responce['message'] = "Slider deleted successfully.";
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to do this operation.';
                }
            }
        } elseif ($request->request_type == 'change_exclusive_status') {
            if (!(parent::checkWritePrivilege(84))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Slider = PageContent::find($request->Id);
                if (!empty($Slider)) {
                    $status = ($Slider->status == 1) ? 0 : 1;
                    $stat_msg = ($Slider->status == 1) ? 'deactivated' : 'activated';
                    $Slider->status = $status;
                    $Slider->save();
                    $responce['status'] = 1;
                    $responce['message'] = "Exclusive $stat_msg successfully.";
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to do this operation.';
                }
            }
        } elseif ($request->request_type == 'delete_exclusive') {
            if (!(parent::checkWritePrivilege(84))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Slider = PageContent::find($request->Id);
                if (!empty($Slider)) {
                    $Slider->delete();
                    $responce['status'] = 1;
                    $responce['message'] = "Exclusive deleted successfully.";
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to do this operation.';
                }
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function addSlider(Request $request)
    {
        if (!(parent::checkWritePrivilege(81))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Services = Service::pluck('name', 'slug')->toArray();
        $Services = array('home' => 'Home') + $Services;

        return view('users.add-slider', compact('Services'));
    }

    public function addSliderRequest(Request $request)
    {
        //        echo "<pre>";print_r($request->all());exit;
        $validate = Validator::make($request->all(), [
            'section' => 'required|string|min:3|max:100',
            'link' => 'string',
            'display_type' => 'required|string',
            'content' => 'string',
            'slider_image' => 'required|mimes:jpeg,png,jpg',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-slider')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/frontend/';
            $banner_image = '';
            if ($request->hasFile('slider_image')) {
                if ($request->file('slider_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('slider_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->slider_image->extension();
                    $request->slider_image->move(public_path($UploadDir), $banner_image);
                }
            }
            $start_time = $end_time = '';
            if ($request->display_type == 'partial') {
                $start_time = date("Y-m-d H:i:s", strtotime($request->start_date));
                $end_time = date("Y-m-d H:i:s", strtotime($request->end_date));
            }
            $status = 1;
            $vendor_id = '';
            if (Auth::user()->access_type == 'vendor') {
                $status = 0;
                $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            } else {
                $vendor_id = (Auth::user()->role == 1) ? Auth::user()->id : Auth::user()->vendor_id;
            }

            $Page = new PageContent([
                'type' => 'slider',
                'section' => $request->section,
                'image' => !empty($banner_image) ? $UploadDir . $banner_image : '',
                'link' => $request->link,
                'content' => addslashes($request->content),
                'display_type' => $request->display_type,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'vendor_id' => $vendor_id,
                'create_user' => Auth::user()->id,
                'status' => $status
            ]);
            if ($Page->save()) {
                Session::flash('success', 'Slider added successfully.');
                return Redirect::to('manage-slider');
            } else {
                Session::flash('success', 'Unable to add slider');
                return Redirect::to('add-slider');
            }
        }
    }

    public function editSlider($id = null)
    {
        if (!(parent::checkWritePrivilege(35))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $PageContent = PageContent::find($id);
        if (!empty($PageContent)) {
            $PageContent->image = !empty($PageContent->image) ? $this->site . $PageContent->image : $PageContent->image;

            $Services = Service::pluck('name', 'slug')->toArray();
            $Services = array('home' => 'Home') + $Services;

            return view('users.edit-slider', compact('PageContent', 'Services'));
        } else {
            return redirect()->back();
        }
    }

    public function editSliderRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'section' => 'required|string|min:3|max:100',
            'link' => 'string',
            'display_type' => 'required|string',
            'content' => 'string',
            'slider_image' => 'mimes:jpeg,png,jpg',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-slider/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $PageContent = PageContent::find($request->id);
            $UploadDir = 'images/frontend/';
            $banner_image = '';
            if ($request->hasFile('slider_image')) {
                if ($request->file('slider_image')->isValid()) {
                    $old_image = public_path($PageContent->image);
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('slider_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->slider_image->extension();
                    $request->slider_image->move(public_path($UploadDir), $banner_image);
                    $PageContent->image = $UploadDir . $banner_image;
                }
            }
            $start_time = $end_time = '';
            if ($request->display_type == 'partial') {
                $start_time = date("Y-m-d H:i:s", strtotime($request->start_date));
                $end_time = date("Y-m-d H:i:s", strtotime($request->end_date));
            }

            $PageContent->section = $request->section;
            $PageContent->url = $request->url;
            $PageContent->content = addslashes($request->content);
            $PageContent->display_type = $request->display_type;
            $PageContent->start_time = $start_time;
            $PageContent->end_time = $end_time;
            $PageContent->update_user = Auth::user()->id;


            if ($PageContent->save()) {
                Session::flash('success', 'Slider updated successfully.');
                return Redirect::to('manage-slider');
            } else {
                Session::flash('success', 'Unable to update slider');
                return Redirect::to('edit-slider/' . $request->id);
            }
        }
    }

    public function sendMail()
    {
        if (!(parent::checkWritePrivilege(36))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        // if (Auth::user()->role == 1)
        //     $AllUser = DB::table('users')->where('id', '!=', Auth::user()->id)->get();
        // else if (Auth::user()->role == 2)
        //     $AllUser = DB::table('users')->where('role', '=', '3')->where('vendor_id', '=', Auth::user()->id)->get();
        $AllUser = DB::table('users')->where('role', '4')->orWhereRaw("role = 3 AND access_type = 'agent'")->get();

        return view('users.send-mail', compact('AllUser'));
    }

    public function sendMailOprsn(Request $request)
    {
        if ($request->request_type == 'send_to_all_user') {
            // if (Auth::user()->role == 1)
            //     $AllUser = DB::table('users')->where('id', '!=', Auth::user()->id)->get();
            // else if (Auth::user()->role == 2)
            //     $AllUser = DB::table('users')->where('role', '=', '3')->where('vendor_id', '=', Auth::user()->id)->get();
            $AllUser = DB::table('users')->where('role', '4')->orWhereRaw("role = 3 AND access_type = 'agent'")->get();
            $message = $request->message . '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
            if (!empty($AllUser)) {
                foreach ($AllUser as $usersData) {
                    Mail::to($usersData->email)->send(new \App\Mail\RegistrationMailUser($message, $request->subject));
                }
                echo "Mail send successful";
            } else {
                echo 'No users found to send mail';
            }
        } elseif ($request->request_type == 'send_to_custom_user') {
            $message = $request->message . '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
            foreach ($request->userIds as $userId) {
                $UserDetails = User::find($userId);
                try {
                    Mail::to($UserDetails->email)->send(new \App\Mail\RegistrationMailUser($message, $request->subject));
                }
                catch(\Exception $e) {

                }
            }
            echo "Mail send successful";
        }
    }

    public function serviceReview()
    {
        if (!(parent::checkViewPrivilege(37))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.service-review');
    }

    public function getServiceReview(Request $request)
    {

        $aColumns = array('service', 'service_id', 'user_id', 'rate_number', 'content', 'publish_date', 'status', 'id', 'service');
        $sIndexColumn = "id";
        $sTable = "service_reviews";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = 'WHERE `vendor_id` = ' . $vender_id;

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $service_name = 'N/A';
            $UserDetails = User::find($aRow->user_id);
            if ($aRow->service == 'hotel') {
                $serviceDetails = MasterHotel::find($aRow->service_id);
                if (!empty($serviceDetails)) {
                    $service_name = $serviceDetails->name;
                }
            } elseif ($aRow->service == 'car') {
                $serviceDetails = MasterCar::find($aRow->service_id);
                if (!empty($serviceDetails)) {
                    $service_name = $serviceDetails->title;
                }
            } elseif ($aRow->service == 'tour') {
                $serviceDetails = Tour::find($aRow->service_id);
                if (!empty($serviceDetails)) {
                    $service_name = $serviceDetails->name;
                }
            } elseif ($aRow->service == 'ticketing') {
                $serviceDetails = Ticket::find($aRow->service_id);
                if (!empty($serviceDetails)) {
                    $service_name = $serviceDetails->name;
                }
            } elseif ($aRow->service == 'merchandise') {
                $serviceDetails = MerchantProduct::find($aRow->service_id);
                if (!empty($serviceDetails)) {
                    $service_name = $serviceDetails->name;
                }
            }

            $status_link = ($aRow->status == 0) ? '<li><a href="javascript:void(0);" class="change-status" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '">Approve</a></li>' : '';
            $row[] = strtoupper($aRow->service);
            $row[] = $service_name;
            $row[] = (!empty($UserDetails)) ? $UserDetails->first_name . ' ' . $UserDetails->last_name . '<br>Phone: ' . $UserDetails->phone . '<br>Email: ' . $UserDetails->email : 'N/A';
            $row[] = $aRow->rate_number;
            $row[] = $aRow->content;
            $row[] = date("M d Y", strtotime($aRow->publish_date));
            $row[] = ($aRow->status == 1) ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Approved</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">Not approved</span>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    ' . $status_link . '
                    <li><a href="javascript:void(0)" class="delete-data" data-id="' . $aRow->id . '">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function reviewOprsn(Request $request)
    {
        if ($request->request_type == 'approve_review') {
            if (!(parent::checkWritePrivilege(37))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Review = ServiceReview::find($request->Id);
                $Review->status = 1;
                $Review->save();
                $service_id = $Review->service_id;
                $service_type = $Review->service;

                $ServiceReviews = db::table('service_reviews')
                    ->select(DB::raw('SUM(rate_number)/count(*) as reviewScore'), DB::raw('count(*) as totReview'))
                    ->where(['service' => $service_type, 'service_id' => $service_id, 'status' => 1])
                    ->first();
                if ($service_type == 'hotel') {
                    MasterHotel::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                } elseif ($service_type == 'car') {
                    MasterCar::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                } elseif ($service_type == 'tour') {
                    Tour::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                } elseif ($service_type == 'ticketing') {
                    Ticket::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                } elseif ($service_type == 'merchant') {
                    MerchantProduct::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                }
                $responce['status'] = 1;
                $responce['message'] = 'Review approved successfully';
            }
        } elseif ($request->request_type == 'delete_review') {
            if (!(parent::checkWritePrivilege(37))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $ServiceReview = ServiceReview::find($request->Id);
                if (!empty($ServiceReview)) {
                    $service_type = $ServiceReview->service;
                    $service_id = $ServiceReview->service_id;
                    $ServiceReview->delete();
                    $ServiceReviews = db::table('service_reviews')
                        ->select(DB::raw('SUM(rate_number)/count(*) as reviewScore'), DB::raw('count(*) as totReview'))
                        ->where(['service' => $service_type, 'service_id' => $service_id, 'status' => 1])
                        ->first();
                    if ($service_type == 'hotel') {
                        MasterHotel::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                    } elseif ($service_type == 'car') {
                        MasterCar::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                    } elseif ($service_type == 'tour') {
                        Tour::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                    } elseif ($service_type == 'ticketing') {
                        Ticket::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                    } elseif ($service_type == 'merchant') {
                        MerchantProduct::find($service_id)->update(['review_score' => round($ServiceReviews->reviewScore), 'review_count' => $ServiceReviews->totReview]);
                    }
                    $responce['status'] = 1;
                    $responce['message'] = 'Review deleted successfully';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete Review';
                }
            }
        } elseif ($request->request_type == 'delete_coupon') {
            if (!(parent::checkWritePrivilege(39))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Coupon = Coupon::find($request->couponId);
                if (!empty($Coupon)) {
                    $Coupon->delete();
                    $responce['status'] = 1;
                    $responce['message'] = 'Coupon deleted successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete coupon.';
                }
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function agentDetails()
    {
        if (!(parent::checkViewPrivilege(5))) {
            return redirect()->back();
        }
        return view('users.agent-details');
    }

    public function getAgentDetails(Request $request)
    {

        $aColumns = array('first_name', 'email', 'phone', 'vendor_id', 'agent_comission', 'status', 'id', 'last_name');
        $sIndexColumn = "id";
        $sTable = "users";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        if (Auth::user()->role == 1) {
            $sWhere = 'WHERE vendor_id != 0  AND id != ' . Auth::user()->id;
        } else if (Auth::user()->role == 2 && Auth::user()->vendor_id == 0) {
            $sWhere = 'WHERE vendor_id = ' . Auth::user()->id;
        } else if (Auth::user()->role == 2 && Auth::user()->vendor_id != 0) {
            $sWhere = 'WHERE vendor_id = ' . Auth::user()->vendor_id . ' AND id != ' . Auth::user()->id;
        } else if (Auth::user()->role == 3) {
            $sWhere = 'WHERE vendor_id != 0 AND id != ' . Auth::user()->id;
        }
        $sWhere .= ' AND access_type = "agent"';
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere $sOrder $sLimit";
        $exportQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {

            $row = array();
            $currentstatus = ($aRow->status == 1) ? 'Deactivate' : 'Activate';
            $vendorData = User::find($aRow->vendor_id);

            $row[] = $aRow->first_name . ' ' . $aRow->last_name;
            $row[] = $aRow->email;
            $row[] = $aRow->phone;
            $row[] = $vendorData->company;
            $row[] = $aRow->agent_comission;
            $row[] = ($aRow->status == 1) ? 'Active' : 'Inactive';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('agent-edit', $aRow->id) . '">Edit</a></li>
                    <li class="statusModify" data-id="' . $aRow->id . '" data-status="' . $aRow->status . '"><a href="javascript:void(0)">' . $currentstatus . '</a></li>
                    <li class="deleteStaff" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exportQuery;
        echo json_encode($output);
        exit;
    }

    public function agentAdd()
    {
        if (!(parent::checkWritePrivilege(5))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $CountryDetail = DB::table('countries')->pluck('name', 'id')->toArray();
        $VendorDetails = DB::table('users')->where('role', '2')->pluck('company', 'id')->toArray();
        $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();

        return view('users.agent-add', compact('CountryDetail', 'PasswordRemQstn', 'VendorDetails'));
    }

    public function agentAddRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor' => 'required',
            'first_name' => 'required|string|min:3|max:50',
            'last_name' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users',
            'phone' => 'required|digits:10|unique:users',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            // 'password_rem_quetion' => 'required',
            // 'password_rem_ans' => 'required',
            'password' => 'confirmed|required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
            'password_confirmation' => 'required|min:8',
            'agent_comission' => 'required|numeric'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('agent-add')->withErrors($validate)->withInput();
        } else {
            $password = base64_encode($request->password);
            $agent_block_date = !empty($request->agent_block_date) ? explode(',', $request->agent_block_date) : [];
            $agent_block_date = array_map(function($val) { return date("Y-m-d", strtotime($val)); } , $agent_block_date);

            $user = DB::table('users')->insert([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($password),
                'role' => 3,
                'access_type' => 'agent',
                'agent_comission' => $request->agent_comission,
                'commission_taken' => isset($request->commission_taken) && $request->commission_taken == 1 ? $request->commission_taken : 0,
                'night_limit_commission' => isset($request->night_limit_commission) && $request->night_limit_commission == 1 ? $request->night_limit_commission : 0,
                'foreign_visitors_commission' => isset($request->foreign_visitors_commission) && $request->foreign_visitors_commission == 1 ? $request->foreign_visitors_commission : 0,
                'agent_block_date' => implode(',', $agent_block_date),
                'vendor_id' => $request->vendor,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'pincode' => $request->pincode,
                'address' => $request->address,
                'password_rem_quetion' => $request->password_rem_quetion,
                'password_rem_ans' => $request->password_rem_ans,
                'payment_merchand_id' => $request->payment_merchand_id,
                'created_by' => Auth::user()->id,
                'modified_by' => Auth::user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Session::flash('success', 'Agent added successful.');
            return Redirect::to('agent-details');
        }
    }

    public function agentEdit($id = null)
    {
        if (!(parent::checkWritePrivilege(5))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $StaffDetails = User::find($id);
        if (!empty($StaffDetails)) {
            $CountryDetail = DB::table('countries')->pluck('name', 'id')->toArray();
            $StateDetail = DB::table('states')->where(['country_id' => $StaffDetails->country])->pluck('name', 'id')->toArray();
            $CityDetail = DB::table('cities')->where(['state_id' => $StaffDetails->state])->pluck('name', 'id')->toArray();
            $PasswordRemQstn = PasswordRemQuestion::where('category', 'password_reminder')->get()->toArray();
            $VendorDetails = DB::table('users')->where('role', '2')->pluck('company', 'id')->toArray();
            $agent_block_date = !empty($StaffDetails->agent_block_date) ? explode(',', $StaffDetails->agent_block_date) : [];
            $agent_block_date = array_map(function($val) { return date("d-m-Y", strtotime($val)); } , $agent_block_date);
            $StaffDetails->agent_block_date = implode(',', $agent_block_date);

            return view('users.agent-edit', compact('CountryDetail', 'StateDetail', 'CityDetail', 'PasswordRemQstn', 'VendorDetails', 'StaffDetails'));
        } else {
            return redirect()->back();
        }
    }

    public function agentEditRequest(Request $request)
    {
        // echo "<pre>";print_r($request->all());exit;
        $validate = Validator::make($request->all(), [
            'vendor' => 'required',
            'first_name' => 'required|string|min:3|max:50',
            'last_name' => 'required|string|min:3|max:50',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            // 'password_rem_quetion' => 'required',
            // 'password_rem_ans' => 'required',
            'agent_comission' => 'required|numeric',
            'password' => 'nullable|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('agent-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $user = User::find($request->id);
            $user->vendor_id = $request->vendor;
            $user->agent_comission = $request->agent_comission;
            $user->commission_taken = isset($request->commission_taken) && $request->commission_taken == 1 ? $request->commission_taken : 0;
            $user->night_limit_commission = isset($request->night_limit_commission) && $request->night_limit_commission == 1 ? $request->night_limit_commission : 0;
            $user->foreign_visitors_commission = isset($request->foreign_visitors_commission) && $request->foreign_visitors_commission == 1 ? $request->foreign_visitors_commission : 0;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->country = $request->country;
            $user->state = $request->state;
            $user->city = $request->city;
            $user->pincode = $request->pincode;
            $user->address = $request->address;
            $user->password_rem_quetion = $request->password_rem_quetion;
            $user->password_rem_ans = $request->password_rem_ans;
            $user->payment_merchand_id = $request->payment_merchand_id;
            $user->modified_by = Auth::user()->id;
            $user->updated_at = date('Y-m-d H:i:s');
            $agent_block_date = !empty($request->agent_block_date) ? explode(',', $request->agent_block_date) : [];
            $agent_block_date = array_map(function($val) { return date("Y-m-d", strtotime($val)); } , $agent_block_date);
            $user->agent_block_date = implode(',', $agent_block_date);
            if (!empty($request->password)) {
                $password = base64_encode($request->password);
                $user->password = bcrypt($password);
            }
            $user->save();

            Session::flash('success', 'Agent details updated successful.');
            return Redirect::to('agent-details');
        }
    }

    public function managePrivilege($id = null)
    {
        if (!(parent::checkWritePrivilege(4))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $UserDetails = User::find($id);
        if (!empty($UserDetails) && $UserDetails->vendor_id == Auth::user()->id) {
            $Services = Service::pluck('slug', 'id')->toArray();
            $Previlege = !empty($UserDetails->privilege) ? json_decode($UserDetails->privilege, 1) : [];
            $menu_type = (Auth::user()->access_type == 'superadmin') ? 'superadmin' : 'vendor';

            $user_service = array('default');
            if (Auth::user()->access_type == 'superadmin') {
                $user_service = array_merge($user_service, $Services);
            } else {
                $vendor_services = json_decode(Auth::user()->services, 1);
                $user_service = array_merge($user_service, $vendor_services);
            }

            $MenuDetail = MenuDetail::where('parent_id', 0)
                ->whereIn('access_type', array('all', $menu_type))
                ->whereIn('service', $user_service)
                ->pluck('menu_name', 'id');
            $MenuList = array();
            foreach ($MenuDetail as $key => $value) {
                $SubMenuDetail = MenuDetail::where('parent_id', $key)
                    ->whereIn('access_type', array('all', $menu_type))
                    ->whereIn('service', $user_service)
                    ->pluck('menu_name', 'id');
                $ChildMenu = array();
                if (!empty($SubMenuDetail)) {
                    foreach ($SubMenuDetail as $chk => $val) {
                        $ChildMenu[] = array(
                            'id' => $chk,
                            'menu_name' => $val
                        );
                    }
                }
                $MenuList[] = array(
                    'id' => $key,
                    'menu_name' => $value,
                    'child_menu' => $ChildMenu
                );
            }

            return view('users.manage-privilege', compact('MenuList', 'Previlege', 'UserDetails'));
        } else {
            return redirect()->back();
        }
    }

    public function savePrivilege(Request $request)
    {

        $User = User::find($request->user_id);
        if (!empty($User) && $User->role == 3) {
            unset($_POST['_token']);
            unset($_POST['user_id']);
            $privilege = json_encode($_POST);
            $User->privilege = $privilege;
            $User->save();
            Session::flash('success', 'Privilege save successful.');
            return Redirect::to('manage-privilege/' . $User->id);
        } else {
            Session::flash('success', 'Unable to save privilege.');
            return Redirect::to('manage-privilege/' . $User->id);
        }
    }

    public function gstDetails()
    {
        if (!(parent::checkViewPrivilege(38))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $GstDetails = GstDetail::get();

        return view('users.gst-details', compact('GstDetails'));
    }

    public function gstOprsn(Request $request)
    {
        if ($request->name == 'save_gst_value') {
            if (!(parent::checkWritePrivilege(38))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $GSTData = GstDetail::find($request->pk);
                if (!empty($GSTData)) {
                    $GSTData->value = $request->value;
                    $GSTData->save();
                    $responce['status'] = 1;
                    $responce['message'] = 'GST value update successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to update data';
                }
            }
        } elseif ($request->request_type == 'delete-gst-rule') {
            if (!(parent::checkWritePrivilege(56))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $GST = GstTable::find($request->Id)->delete();
                if ($GST) {
                    $responce['status'] = 1;
                    $responce['message'] = 'GST rule delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete GST rule.';
                }
            }
        } elseif ($request->request_type == 'delete-refund-policy') {
            if (!(parent::checkWritePrivilege(55))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Policy = CancelPolicy::find($request->Id)->delete();
                if ($Policy) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Refund policy delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete Refund policy.';
                }
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function couponList()
    {
        if (!(parent::checkViewPrivilege(39))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.coupon-list');
    }

    public function getCouponDetails(Request $request)
    {

        $aColumns = array('service_type', 'access_type', 'coupon_name', 'coupon_code', 'coupon_amount', 'min_order_amount', 'coupon_use_type', 'frequency_per_user', 'frequency', 'already_used', 'start_date', 'end_date', 'description', 'id');
        $sIndexColumn = "id";
        $sTable = "coupons";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = ' WHERE vendor_id = ' . $vender_id;
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $row[] = strtoupper($aRow->service_type);
            $row[] = $aRow->access_type;
            $row[] = $aRow->coupon_name;
            $row[] = $aRow->coupon_code;
            $row[] = $aRow->coupon_amount;
            $row[] = ($aRow->multi_usage) == 1 ? 'Yes' : 'No';
            $row[] = $aRow->min_order_amount;
            $row[] = $aRow->coupon_use_type;
            $row[] = $aRow->frequency_per_user;
            $row[] = $aRow->frequency;
            $row[] = $aRow->already_used;
            $row[] = date("M d Y", strtotime($aRow->start_date));
            $row[] = date("M d Y", strtotime($aRow->end_date));
            $row[] = wordwrap($aRow->description, 50, "<br>\n");
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('coupon-edit', $aRow->id) . '">Edit</a></li>
                    <li class="deleteCoupon" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function addCoupon()
    {
        if (!(parent::checkWritePrivilege(39))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Services = Service::get();

        return view('users.coupon-add', compact('Services'));
    }

    public function couponAddRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required|numeric',
            'service_type' => 'required|string',
            'coupon_name' => 'required|string|min:3|max:64',
            'coupon_code' => 'required|string|min:3|max:32|unique:coupons',
            'coupon_amount' => 'required|numeric',
            'min_order_amount' => 'required|numeric',
            'description' => 'required|string',
            'coupon_use_type' => 'required|string',
            'frequency_per_user' => 'required|numeric',
            'frequency' => 'required|numeric',
            'check_date' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-coupon')->withErrors($validate)->withInput();
        } else {
            $check_date = explode(" - ", $request->check_date);
            $Coupon = new Coupon([
                'vendor_id' => $request->vendor_id,
                'service_type' => $request->service_type,
                'access_type' => $request->access_type,
                'coupon_name' => $request->coupon_name,
                'coupon_code' => $request->coupon_code,
                'coupon_amount' => $request->coupon_amount,
                'min_order_amount' => $request->min_order_amount,
                'description' => addslashes($request->description),
                'coupon_use_type' => $request->coupon_use_type,
                'frequency_per_user' => $request->frequency_per_user,
                'frequency' => $request->frequency,
                'already_used' => 0,
                'start_date' => date('Y-m-d', strtotime($check_date[0])),
                'end_date' => date('Y-m-d', strtotime($check_date[1])),
                'status' => $request->status,
                'multi_usage' => $request->multi_usage,
                'created_by' => Auth::user()->id,
            ]);
            if ($Coupon->save()) {
                Session::flash('success', 'Coupon added successful.');
                return Redirect::to('coupons');
            } else {
                Session::flash('success', 'Unable to add Coupon.');
                return Redirect::to('add-coupon');
            }
        }
    }

    public function couponEdit($id = null)
    {
        if (!(parent::checkWritePrivilege(39))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $CouponDetails = Coupon::find($id);
        if (!empty($CouponDetails)) {
            $Services = Service::get();

            return view('users.coupon-edit', compact('CouponDetails', 'Services'));
        } else {
            return redirect()->back();
        }
    }

    public function couponEditRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'service_type' => 'required|string',
            'coupon_name' => 'required|string|min:3|max:64',
            'coupon_code' => 'required|string|min:3|max:32',
            'coupon_amount' => 'required|numeric',
            'min_order_amount' => 'required|numeric',
            'description' => 'required|string',
            'coupon_use_type' => 'required|string',
            'frequency_per_user' => 'required|numeric',
            'frequency' => 'required|numeric',
            'check_date' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('coupon-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $check_date = explode(" - ", $request->check_date);
            $Coupon = Coupon::find($request->id);

            $Coupon->service_type = $request->service_type;
            $Coupon->access_type = $request->access_type;
            $Coupon->coupon_name = $request->coupon_name;
            $Coupon->coupon_code = $request->coupon_code;
            $Coupon->coupon_amount = $request->coupon_amount;
            $Coupon->min_order_amount = $request->min_order_amount;
            $Coupon->description = addslashes($request->description);
            $Coupon->coupon_use_type = $request->coupon_use_type;
            $Coupon->frequency_per_user = $request->frequency_per_user;
            $Coupon->frequency = $request->frequency;
            $Coupon->start_date = date('Y-m-d', strtotime($check_date[0]));
            $Coupon->end_date = date('Y-m-d', strtotime($check_date[1]));
            $Coupon->status = $request->status;
            $Coupon->multi_usage = $request->multi_usage;
            $Coupon->modified_by = Auth::user()->id;

            if ($Coupon->save()) {
                Session::flash('success', 'Coupon details updated successfully.');
                return Redirect::to('coupons');
            } else {
                Session::flash('success', 'Unable to save coupon details.');
                return Redirect::to('coupon-edit/' . $request->id);
            }
        }
    }

    public function hotelOrders()
    {
        if (!(parent::checkViewPrivilege(28))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterHotelQuery = MasterHotel::where('vender_id', $vender_id);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $MasterHotelQuery->whereIn('id', array_values($SubuserAccess));
            }
        }
        $MasterHotel = $MasterHotelQuery->pluck('name', 'id');

        $AgentData = User::where(['access_type' => 'agent', 'role' => 3, 'vendor_id' => $vender_id])->pluck('first_name', 'id');
        $Agents = json_encode($AgentData);
        $OfflineAgents = User::where(['access_type' => 'vendor', 'role' => 3, 'user_role' => 'agent_staff', 'vendor_id' => $vender_id])->pluck('first_name');

        $CouponData = Coupon::where(['service_type' => 'hotel', 'vendor_id' => $vender_id])->pluck('coupon_name', 'coupon_code')->toArray();
        $Coupons = json_encode($CouponData);

        return view('users.hotel-orders', compact('Vendors', 'MasterHotel', 'Agents', 'OfflineAgents', 'Coupons'));
    }

    public function getHotelOrders(Request $request)
    {
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('vendor_name', 'invoice_id', 'service_name', 'customer_name', 'customer_phone', 'created_at', 'room_details', 'start_date', 'total_order_price', 'order_type', 'status', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        } else {
            $aColumns = array('invoice_id', 'service_name', 'customer_name', 'customer_phone', 'created_at', 'room_details', 'start_date', 'total_order_price', 'order_type', 'status', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        }

        $sIndexColumn = "id";
        $sTable = "order_masters";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY created_at DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = $staff_condition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
            if ($vender_id == 3) {
                $vendor_condtition .= ' AND `created_at` > "'. $this->ecoStartDate .'" ';
            }
            if (Auth::user()->role == 3) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $staff_condition = ' AND service_name_id in (' . implode(',', $SubuserAccess) . ')';
                }
                if (Auth::user()->user_role == 'agent_staff') {
                    $staff_condition .= ' AND customer_id = "' . Auth::user()->id . '" ';
                }
            }
        }
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND service_type = "hotel" AND status != "partially-cancelled" ' . $staff_condition;
        $searchColumns = array('service_name', 'invoice_id', 'transaction_id', 'order_type', 'created_at', 'start_date', 'end_date', 'service_name_id', 'payment_gateway', 'customer_id', 'request_from', 'book_from', 'coupon_code');
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) || (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7']))) {
            $condition1 = $condition2 = $condition3 = $condition4 = $condition5 = $condition6 = $condition7 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                if ($_POST['searchValue1'] == 'all') {
                    $condition1 .= ' AND status != "partially-cancelled"';
                } else {
                    if ($_POST['searchValue1'] == 'cancelled') {
                        $condition1 .= ' AND ((status = "' . $_POST['searchValue1'] . '" OR status = "partially-cancelled") AND payment_status = "success")';
                    } else {
                        $condition1 .= ' AND status = "' . $_POST['searchValue1'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue2'])) {
                $_POST['searchValue2'] = parent::cleanString($_POST['searchValue2']);
                $condition2 .= ' AND vendor_id = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) {
                if (in_array($_POST['searchValue3'], $searchColumns)) {
                    if ($_POST['searchValue3'] == 'created_at' || $_POST['searchValue3'] == 'start_date' || $_POST['searchValue3'] == 'end_date') {
                        $dates = explode(' - ', $_POST['searchValue4']);
                        $start = date('Y-m-d', strtotime($dates[0]));
                        $end = date('Y-m-d', strtotime($dates[1]));
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                    } else {
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue5'])) {
                $_POST['searchValue5'] = parent::cleanString($_POST['searchValue5']);
                $condition5 .= ' AND service_name_id = "' . $_POST['searchValue5'] . '"';
            }
            if (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7'])) {
                $condition4 .= ' AND (room_slug like "%~' . $_POST['searchValue6'] . '~%" OR room_slug like "' . $_POST['searchValue6'] . '" OR room_slug like "%' . $_POST['searchValue6'] . '~%" OR room_slug like "%~' . $_POST['searchValue6'] . '%") AND  start_date <= "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '" AND end_date > "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '"';
            }
            if (!empty($_POST['searchValue7']) && !empty($_POST['searchValue8'])) {
                $_POST['searchValue8'] = parent::cleanString($_POST['searchValue8']);
                $condition4 .= ' AND service_name_id like "' . $_POST['searchValue8'] . '" AND  start_date <= "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '" AND end_date > "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '"';
            }
            if (!empty($_POST['searchValue9'])) {
                $_POST['searchValue9'] = parent::cleanString($_POST['searchValue9']);
                if ($_POST['searchValue9'] == 'foreign_citizen'){
                $condition6 .= ' AND foreign_visitor = 1';
                }else{
                    $condition6 .= ' AND order_type = "' . $_POST['searchValue9'] . '"';
                }

            }
            if (!empty($_POST['searchValue10'])) {
                $_POST['searchValue10'] = parent::cleanString($_POST['searchValue10']);
                $condition7 .= ' AND payment_gateway = "' . $_POST['searchValue10'] . '"';
            }
            $sWhere .= $condition1 . $condition2 . $condition3 . $condition4 . $condition5 . $condition6 . $condition7;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }
        //        echo $_POST['searchValue7'];exit;
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $printQuery = $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $partial_cancel = $cancel_option = $cancel_option_policy = '';
            // $cancel_option = ($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && $aRow->start_date >= date("Y-m-d")) ? '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>' : '';
            if ($aRow->status == 'completed' && $aRow->payment_status == 'success' && ($aRow->start_date >= date("Y-m-d") || $aRow->order_type == 'CMO' || Auth::user()->access_type == 'superadmin')) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-amount="' . $aRow->total_order_price . '">Cancel Booking (Refund full)</a></li>';
                $cancel_option_policy = '<li><a href="javascript:void(0);" class="cancel_booking_policy" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-toggle="modal" data-target="#cancelPolicyModal">Cancel Booking (Refund As policy)</a></li>';
            } elseif ($aRow->status == 'pending' && $aRow->order_type == 'offline' && $aRow->payment_gateway == 'hdfc' && ($aRow->offline_link_expiry < date("Y-m-d H:i:s") ||  $aRow->start_date <= date("Y-m-d"))) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>';
            }
            $part_th_date = date("Y-m-d", strtotime($aRow->start_date .' +3 Days'));
            $partial_cancel = ($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && ($aRow->start_date > date("Y-m-d") || $part_th_date >= date("Y-m-d"))) ? '<li><a href="' . url('cancel-options/' . $aRow->id . '/' . $aRow->order_id) . '" target="_blank" class="partial_cancel" data-status="' . $aRow->status . '" data-id="' . $aRow->order_id . '">Modify Booking</a></li>' : '';
            $date = 'Check-in : ' . date("M d Y", strtotime($aRow->start_date)) . '<br>Check-out : ' . date("M d Y", strtotime($aRow->end_date));

            if (Auth::user()->access_type == 'superadmin') {
                $row[] = $aRow->vendor_name;
            }
            $room_details = json_decode($aRow->room_details, 1);
            $rooms = '';
            foreach ($room_details as $room) {
                $rooms .= $room['quantity'] . ' ' . $room['room_name'] . ',';
            }
            $rooms = rtrim($rooms, ',');

            $arrival_data = !empty($aRow->expected_arrival_time) ? $aRow->expected_arrival_time : 'N/A';
            $arrival_data .= !empty($aRow->expected_arrival_time) ? '<br> Need Pickup' : '';
            $order_type = ($aRow->order_type != 'online' && $aRow->book_from == 'blocked') ? $aRow->order_type . '<br>(' . $aRow->book_from . ')' : $aRow->order_type;
            // $sr_ctzen = ($aRow->senior_citizen == 1) ? '<br>(Sr. Citizen)' : '';

            $row[] = $aRow->invoice_id;
            $row[] = $aRow->service_name;
            $row[] = $aRow->customer_name;
            $row[] = $aRow->customer_phone;
            $row[] = date("M d Y H:i:s", strtotime($aRow->created_at));
            $row[] = wordwrap($rooms,30,"<br>\n");
            $row[] = $date;
            $row[] = $aRow->total_order_price;
            // $row[] = $arrival_data;
            $row[] = $order_type;
            $row[] = $aRow->status;
            $row[] = $aRow->payment_method;
            $row[] = $aRow->payment_status;
            $row[] = !empty($aRow->transaction_id) ? $aRow->transaction_id : "Nill";
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->id . '">Order Details</a></li>
                    <li><a href="javascript:void(0);" class="user_details" data-toggle="modal" data-target="#userDetailsModal" data-id="' . $aRow->id . '">User Details</a></li>
                    <li><a href="javascript:void(0);" class="update_order" data-toggle="modal" data-target="#updateOrderModal" data-id="' . $aRow->id . '">Update Booking Details</a></li>
                    ' . $cancel_option . $cancel_option_policy . $partial_cancel . '
                </ul>
            </div>';
            $output['data'][] = $row;


        }
        $output['exportQuery'] = $exQuery;
        $output['printQuery'] = $printQuery;

        echo json_encode($output);
        exit;
    }

    public function orderOprsn(Request $request)
    {

        if ($request->request_type == 'get_hotel_order_details') {
            $OrderMaster = OrderMaster::find($request->orderId);

            if (!empty($OrderMaster)) {
                $html = '<table class="table table-hover table-bordered"><tr><th>Booking Id</th><th>Booking Date</th><th>Hotel Name</th><th>Selected Room type</th><th>Guest Details</th><th>Check_in-Check_out</th><th>Total Amount</th><th>Discount Type</th><th>STATUS</th></tr>';
                $room_html = '';
                $room_details = json_decode($OrderMaster->room_details, 1);
                foreach ($room_details as $value) {
                    $room_html .= $value['quantity'] . ' ' . $value['room_name'] . ', ';
                }
                $room_html = trim($room_html, ', ');
                $html .= '<tr><td>' . $OrderMaster->invoice_id . '</td><td>' . date("M d Y H:i:s", strtotime($OrderMaster->created_at)) . '</td><td>' . $OrderMaster->service_name . '</td><td>[' . $room_html . ']</td><td>Adult - ' . $OrderMaster->total_adults . '<br>Child - ' . $OrderMaster->total_child . '</td><td>[' . date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date)) . ']</td><td>' . $OrderMaster->total_order_price . '</td><td>' . $OrderMaster->coupon_name . '</td><td>' . $OrderMaster->payment_status . '</td></tr>';
                $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
                $book_naration = !empty($OrderMaster->book_naration) ? $OrderMaster->book_naration : 'N/A';
                $html .= '<tr><td colspan="5" style="text-align:left;"><strong>Transaction Id</strong> : ' . $txn_id . '</td><td colspan="5" style="text-align:left;"><strong>Payment Method</strong> : ' . $OrderMaster->payment_method . '</td></tr>';
                $html .= '<tr><td colspan="9" style="text-align:left;"><strong>Booking Naration</strong> : ' . $book_naration . '</td></tr></table>';
                $html .= '<h3 class="text-center">Payment Details</h3>';
                $html .= '<table class="table table-hover table-bordered"><tr><th>Room Type</th><th>No. of Room</th><th>Rate</th><th>Amount</th></tr>';

                $Coupon_percent = $sgst = $cgst = 0;
                if ($OrderMaster->tax_amount > 0) {
                    $GstData = GstDetail::pluck('value', 'name');
                    $cgst = $GstData['CGST'];
                    $sgst = $GstData['SGST'];
                }
                if ($OrderMaster->coupon_amount > 0) {
                    $Coupon = Coupon::where('coupon_code', $OrderMaster->coupon_code)->first();
                    if (!empty($Coupon)) {
                        $Coupon_percent = $Coupon->coupon_amount;
                    }
                }
                $difference = strtotime(date("Y-m-d", strtotime($OrderMaster->end_date))) - strtotime(date("Y-m-d", strtotime($OrderMaster->start_date)));
                $totalNight = floor($difference / (60 * 60 * 24));
                $totalNight = ($totalNight == 0) ? 1 : $totalNight;
                $room_pricing = '';
                // $OrderDeatil = OrderDetail::select('service_item_name', DB::raw('sum(unit_total_price) as grossPrice'), DB::raw('sum(total_room_price) as totalRoomPrice'), DB::raw('sum(tax_amount) as totTax'))
                //         ->where('order_master_id', $OrderMaster->id)
                //         ->groupBy('service_item_id')
                //         ->get();
                // foreach ($OrderDeatil as $value) {
                //     $discount = $value->grossPrice * ($Coupon_percent / 100);
                //     $afterDiscount = $value->grossPrice - $discount;
                //     $cgstPrice = $sgstPrice = 0;
                //     if ($cgst > 0 || $sgst > 0) {
                //         $cgstPrice = ($value->totTax / ($cgst + $sgst)) * $cgst;
                //         $sgstPrice = ($value->totTax / ($cgst + $sgst)) * $sgst;
                //     }
                //     $totalRoomPrice = $afterDiscount + $cgstPrice + $sgstPrice;

                //     $room_pricing .= '<tr><td>' . $value->service_item_name . '</td><td>' . number_format($value->grossPrice, 2) . '</td><td>' . number_format($discount, 2) . '</td><td>' . number_format($afterDiscount, 2) . '</td><td >' . number_format($cgstPrice, 2) . '</td><td >' . number_format($sgstPrice, 2) . '</td><td >' . number_format($totalRoomPrice, 2) . '</td></tr>';
                // }
                $OrderDeatil = OrderDetail::select('service_item_name', DB::raw('count(service_item_id) as totalRoom'), DB::raw('sum(service_item_id) as totQty'), DB::raw('sum(unit_total_price - total_extrabed_price) as totalRoomPrice'), DB::raw('sum(total_extrabed_price) as totextraBedPrice'), DB::raw('sum(extra_bed) as extraBed'))
                    ->where('order_master_id', $OrderMaster->id)
                    ->groupBy('service_item_id')
                    ->get();

                foreach ($OrderDeatil as $value) {
                    $rate = $value->totalRoomPrice / $value->totalRoom;
                    $roomQty = $value->totalRoom / $totalNight;
                    $extraPerson = $value->extraBed / $totalNight;

                    $room_pricing .= '<tr><td>' . $value->service_item_name . '</td><td>' . $roomQty . '</td><td>' . number_format($rate, 2) . '</td><td>' . number_format($value->totalRoomPrice, 2) . '</td></tr>';
                    if ($value->extraBed > 0) {
                        $room_pricing .= '<tr><td>Extra Person (' . $extraPerson . ')</td><td></td><td>' . number_format($value->totextraBedPrice / $value->extraBed, 2) . '</td><td>' . number_format($value->totextraBedPrice, 2) . '</td></tr>';
                    }
                }
                $room_pricing .= '<tr><th style="text-align: right;" colspan="3">Total :</th><td style="text-align: center;">' . number_format($OrderMaster->total_service_price, 2) . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="3">Discount :</th><td style="text-align: center;">' . number_format($OrderMaster->coupon_amount, 2) . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="3">Net Total :</th><td style="text-align: center;">' . number_format($OrderMaster->sub_total_price, 2) . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="3">GST :</th><td style="text-align: center;">' . number_format($OrderMaster->tax_amount, 2) . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="3">Grand Total</th><td  style="text-align: center;">' . number_format($OrderMaster->total_order_price, 2) . '</td</tr>';

                $html .= $room_pricing . '</table>';
                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
        } elseif ($request->request_type == 'get_hall_order_details') {

    $OrderMaster = OrderMaster::find($request->orderId);

    if (!empty($OrderMaster)) {

       $bookingSlots = DB::table('t_booking')
            ->where('hall_id', $OrderMaster->service_name_id)
            ->where('booking_date', date('Y-m-d', strtotime($OrderMaster->created_at)))
            ->where('totalPrice', $OrderMaster->sub_total_price)
            ->where('is_deleted', 0)
            ->select('slot_type', 'booking_date', 'price_breakup', 'totalPrice')
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get();

        $slotText = '';
        $paymentRows = '';
        $totalServicePrice = 0;

        foreach ($bookingSlots as $slot) {
            $slotType = !empty($slot->slot_type)
                ? ucwords(strtolower(str_replace('_', ' ', $slot->slot_type)))
                : 'N/A';

            $bookingDate = !empty($slot->booking_date)
                ? date('M d Y', strtotime($slot->booking_date))
                : 'N/A';

            $slotDisplay = $slotType . '<br>' . $bookingDate;

            $slotText .= $slotDisplay . '<hr style="margin: 3px 0;">';

            $totalPrice = !empty($slot->totalPrice) ? $slot->totalPrice : 0;
            $pricePerDay = $totalPrice;

            $paymentRows .= '<tr>
                <td>' . $OrderMaster->service_name . '</td>
                <td>' . $slotDisplay . '</td>
                <td>' . number_format($pricePerDay, 2) . '</td>
                <td>' . number_format($totalPrice, 2) . '</td>
            </tr>';

            $totalServicePrice += $totalPrice;
        }

        if (!empty($slotText)) {
            $slotText = rtrim($slotText, '<hr style="margin: 3px 0;">');
        } else {
            $slotText = 'N/A';
        }

        if ($paymentRows == '') {
            $paymentRows = '<tr>
                <td>' . $OrderMaster->service_name . '</td>
                <td>N/A</td>
                <td>' . number_format($OrderMaster->sub_total_price, 2) . '</td>
                <td>' . number_format($OrderMaster->sub_total_price, 2) . '</td>
            </tr>';

            $totalServicePrice = !empty($OrderMaster->sub_total_price) ? $OrderMaster->sub_total_price : 0;
        }

        $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
        $book_naration = !empty($OrderMaster->book_naration) ? $OrderMaster->book_naration : 'N/A';
        $payment_method = !empty($OrderMaster->payment_method) ? $OrderMaster->payment_method : 'N/A';
        $status = !empty($OrderMaster->payment_status) ? $OrderMaster->payment_status : 'N/A';
        $discountType = !empty($OrderMaster->coupon_name) ? $OrderMaster->coupon_name : 'N/A';

        $couponAmount = !empty($OrderMaster->coupon_amount) ? $OrderMaster->coupon_amount : 0;
        $subTotalPrice = !empty($OrderMaster->sub_total_price) ? $OrderMaster->sub_total_price : 0;
        $taxAmount = !empty($OrderMaster->tax_amount) ? $OrderMaster->tax_amount : 0;
        $totalOrderPrice = !empty($OrderMaster->total_order_price) ? $OrderMaster->total_order_price : 0;

        $html = '<table class="table table-hover table-bordered">
            <tr>
                <th>Booking Id</th>
                <th>Booking Date</th>
                <th>Hall Name</th>
                <th>Selected Slot</th>
                <th>Guest Details</th>
                <th>Total Amount</th>
                <th>Discount Type</th>
                <th>STATUS</th>
            </tr>';

        $html .= '<tr>
            <td>' . $OrderMaster->invoice_id . '</td>
            <td>' . date("M d Y H:i:s", strtotime($OrderMaster->created_at)) . '</td>
            <td>' . $OrderMaster->service_name . '</td>
            <td>' . $slotText . '</td>
            <td>Customer - ' . $OrderMaster->customer_name . '<br>Phone - ' . $OrderMaster->customer_phone . '</td>
            <td>' . number_format($totalOrderPrice, 2) . '</td>
            <td>' . $discountType . '</td>
            <td>' . $status . '</td>
        </tr>';

        $html .= '<tr>
            <td colspan="4" style="text-align:left;">
                <strong>Transaction Id</strong> : ' . $txn_id . '
            </td>
            <td colspan="4" style="text-align:left;">
                <strong>Payment Method</strong> : ' . $payment_method . '
            </td>
        </tr>';

        $html .= '<tr>
            <td colspan="8" style="text-align:left;">
                <strong>Booking Naration</strong> : ' . $book_naration . '
            </td>
        </tr>';

        $html .= '</table>';

        $html .= '<h3 class="text-center">Payment Details</h3>';

        $html .= '<table class="table table-hover table-bordered">
            <tr>
                <th>Hall Name</th>
                <th>Slot</th>
                <th>Rate</th>
                <th>Amount</th>
            </tr>';

        $html .= $paymentRows;

        $html .= '<tr>
            <th style="text-align: right;" colspan="3">Total :</th>
            <td style="text-align: center;">' . number_format($totalServicePrice, 2) . '</td>
        </tr>';

        $html .= '<tr>
            <th style="text-align: right;" colspan="3">Discount :</th>
            <td style="text-align: center;">' . number_format($couponAmount, 2) . '</td>
        </tr>';

        $html .= '<tr>
            <th style="text-align: right;" colspan="3">Net Total :</th>
            <td style="text-align: center;">' . number_format($subTotalPrice, 2) . '</td>
        </tr>';

        $html .= '<tr>
            <th style="text-align: right;" colspan="3">GST :</th>
            <td style="text-align: center;">' . number_format($taxAmount, 2) . '</td>
        </tr>';

        $html .= '<tr>
            <th style="text-align: right;" colspan="3">Grand Total</th>
            <td style="text-align: center;">' . number_format($totalOrderPrice, 2) . '</td>
        </tr>';

        $html .= '</table>';

        $responce['status'] = 1;
        $responce['data'] = $html;

    } else {
        $responce['status'] = 0;
        $responce['message'] = 'Unable to get hall order details';
    }
        } elseif ($request->request_type == 'get_hotel_customer_details') {
            $OrderMaster = OrderMaster::find($request->orderId);
            if (!empty($OrderMaster)) {
                $orderData = array(
                    'id' => $OrderMaster->id,
                    'invoice_id' => $OrderMaster->invoice_id,
                    'customer_name' => $OrderMaster->customer_name,
                    'customer_email' => $OrderMaster->customer_email,
                    'customer_phone' => $OrderMaster->customer_phone,
                    'gst_regd_no' => $OrderMaster->gst_regd_no,
                    'gst_company_name' => $OrderMaster->gst_company_name,
                    'customer_checkin' => $OrderMaster->customer_checkin,
                    'customer_checkout' => $OrderMaster->customer_checkout,
                    'payment_gateway' => $OrderMaster->payment_gateway,
                    'payment_method' => $OrderMaster->payment_method
                );
                $responce['status'] = 1;
                $responce['data'] = $orderData;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
            } elseif ($request->request_type == 'get_hall_customer_details') {

            $OrderMaster = OrderMaster::find($request->orderId);

            if (!empty($OrderMaster)) {
                $orderData = array(
                    'id' => $OrderMaster->id,
                    'invoice_id' => $OrderMaster->invoice_id,
                    'customer_name' => $OrderMaster->customer_name,
                    'customer_email' => $OrderMaster->customer_email,
                    'customer_phone' => $OrderMaster->customer_phone,
                    'gst_regd_no' => $OrderMaster->gst_regd_no,
                    'gst_company_name' => $OrderMaster->gst_company_name,
                    'customer_checkin' => $OrderMaster->customer_checkin,
                    'customer_checkout' => $OrderMaster->customer_checkout,
                    'payment_gateway' => $OrderMaster->payment_gateway,
                    'payment_method' => $OrderMaster->payment_method
                );

                $responce['status'] = 1;
                $responce['data'] = $orderData;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }

            } elseif ($request->request_type == 'save_hall_customer_details') {

            $OrderMasterNew = OrderMaster::find($request->orderId);

            if (!empty($OrderMasterNew)) {
                $OrderMasterNew->customer_name = $request->customer_name;
                $OrderMasterNew->customer_email = $request->customer_email;
                $OrderMasterNew->customer_phone = $request->customer_phone;
                $OrderMasterNew->gst_regd_no = $request->gst_regd_no;
                $OrderMasterNew->gst_company_name = $request->gst_company_name;
                $OrderMasterNew->customer_checkin = $request->customer_checkin;
                $OrderMasterNew->customer_checkout = $request->customer_checkout;

                if ($OrderMasterNew->payment_gateway == 'credit') {
                    $OrderMasterNew->payment_gateway = $request->payment_gateway;
                    $OrderMasterNew->payment_method = $request->payment_gateway;
                }

                $OrderMasterNew->save();

                $responce['status'] = 1;
                $responce['message'] = 'Order details updated successfully.';
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to update order details';
            }

            } elseif ($request->request_type == 'save_hotel_customer_details') {

            $OrderMasterNew = OrderMaster::find($request->orderId);
            if (!empty($OrderMasterNew)) {
                $OrderMasterNew->customer_name = $request->customer_name;
                $OrderMasterNew->customer_email = $request->customer_email;
                $OrderMasterNew->customer_phone = $request->customer_phone;
                $OrderMasterNew->gst_regd_no = $request->gst_regd_no;
                $OrderMasterNew->gst_company_name = $request->gst_company_name;
                $OrderMasterNew->customer_checkin = $request->customer_checkin;
                $OrderMasterNew->customer_checkout = $request->customer_checkout;
                if ($OrderMasterNew->payment_gateway == 'credit') {
                    $OrderMasterNew->payment_gateway = $request->payment_gateway;
                    $OrderMasterNew->payment_method = $request->payment_gateway;
                }
                $OrderMasterNew->customer_checkout = $request->customer_checkout;

                $HotelInvoice = EmailTemplate::where('ref_code', 'hotelInvoice')->first();
                if (!empty($HotelInvoice)) {
                    $MasterHotel = MasterHotel::find($OrderMasterNew->service_name_id);
                    $Vendor = User::find($OrderMasterNew->vendor_id);
                    $payUId = 'N/A';
                    if ($OrderMasterNew->payment_gateway == 'hdfc') {
                        $PaymentHistory = PaymentHistory::find($OrderMasterNew->payment_id);
                        $payUId = $PaymentHistory->mihpayid;
                    }
                    $room_html = '';
                    $RoomDetails = json_decode($OrderMasterNew->room_details, 1);
                    foreach ($RoomDetails as $value) {
                        $room_html .= $value['quantity'] . ' ' . $value['room_name'] . ', ';
                    }
                    $room_html = trim($room_html, ', ');
                    $check_date = date("d M Y", strtotime($OrderMasterNew->start_date)) . ' - ' . date("d M Y", strtotime($OrderMasterNew->end_date));
                    $Coupon_percent = $sgst = $cgst = 0;
                    $difference = strtotime(date("Y-m-d", strtotime($OrderMasterNew->end_date))) - strtotime(date("Y-m-d", strtotime($OrderMasterNew->start_date)));
                    $totalNight = floor($difference / (60 * 60 * 24));
                    $totalNight = ($totalNight == 0) ? 1 : $totalNight;

                    $hotelGSTNo = (!empty($MasterHotel->gst_number)) ? $MasterHotel->gst_number : 'N/A';
                    $hotelRegdCompany = (!empty($MasterHotel->gst_legal_name)) ? $MasterHotel->gst_legal_name : 'N/A';
                    $customerGSTNo = (!empty($OrderMasterNew->gst_regd_no)) ? '<u><b>GSTN No: '. $OrderMasterNew->gst_regd_no .'</b></u>' : '';
                    $customerGSTCompany = (!empty($OrderMasterNew->gst_company_name)) ? '<u><b>Company Name: '. $OrderMasterNew->gst_company_name .'</b></u>' : '';

                    $txn_id = !empty($OrderMasterNew->transaction_id) ? $OrderMasterNew->transaction_id : 'N/A';

                    $OrderDeatil = OrderDetail::select('service_item_name', DB::raw('count(service_item_id) as totalRoom'), DB::raw('sum(service_item_id) as totQty'), DB::raw('sum(unit_total_price - total_extrabed_price) as totalRoomPrice'), DB::raw('sum(total_extrabed_price) as totextraBedPrice'), DB::raw('sum(extra_bed) as extraBed'))
                        ->where('order_master_id', $OrderMasterNew->id)
                        ->groupBy('service_item_id')
                        ->get();
                    $room_pricing = '';
                    foreach ($OrderDeatil as $value) {
                        $rate = $value->totalRoomPrice / $value->totalRoom;
                        $roomQty = $value->totalRoom / $totalNight;
                        $extraPerson = $value->extraBed / $totalNight;

                        $room_pricing .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMasterNew->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMasterNew->service_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->service_item_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $check_date . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $roomQty . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($rate, 2) . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($value->totalRoomPrice, 2) . '</td></tr>';
                        if ($value->extraBed > 0) {
                            $room_pricing .= '<tr><td align="center" valign="middle" colspan="2" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000"></td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">Extra Person (' . $extraPerson . ')</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $check_date . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000"></td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($value->totextraBedPrice / $value->extraBed, 2) . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($value->totextraBedPrice, 2) . '</td></tr>';
                        }
                    }
                    $customer_address = $OrderMasterNew->customer_address1;
                    $customer_address .= !empty($OrderMasterNew->customer_city) ? ',<br>'. $OrderMasterNew->customer_city : '';
                    $customer_address .= !empty($OrderMasterNew->customer_state) ? ',<br>'. $OrderMasterNew->customer_state : '';
                    $customer_address .= !empty($OrderMasterNew->customer_country) ? ',<br>'. $OrderMasterNew->customer_country : '';
                    $customer_address .= !empty($OrderMasterNew->customer_zipcode) ? ', '. $OrderMasterNew->customer_zipcode : '';

                    $discount = !empty($OrderMasterNew->coupon_amount) ? number_format($OrderMasterNew->coupon_amount, 2) : '0.00';
                    $tspinword = parent::AmountInWords($OrderMasterNew->total_service_price);
                    $payment_method = ($OrderMasterNew->payment_method == 'credit') ? 'N/A' : strtoupper($OrderMasterNew->payment_method);
                    $Message = str_replace(
                        array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~hoteladdress~", "~hotelemail~", "~hotelgst~", "~hotelgstcompany~", "~vendorLogo~", "~orderdate~", "~roomfeesdetails~", "~totalserviceprice~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~couponname~", "~tspinword~", "~payuid~"),
                        array($this->site, $OrderMasterNew->customer_name, $OrderMasterNew->customer_phone, $OrderMasterNew->customer_email, $customer_address, $customerGSTNo, $customerGSTCompany, $MasterHotel->real_address, $MasterHotel->contact_email, $hotelGSTNo, $hotelRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMasterNew->created_at)) .'<br><b>'. $OrderMasterNew->invoice_serial .'</b>', $room_pricing, number_format($OrderMasterNew->total_service_price, 2), $discount, number_format($OrderMasterNew->sub_total_price, 2), number_format($OrderMasterNew->tax_amount, 2), number_format($OrderMasterNew->total_order_price, 2), $payment_method, $txn_id, $OrderMasterNew->coupon_name, $tspinword, $payUId),
                        $HotelInvoice->source
                    );
                    $OrderMasterNew->invoice = $Message;
                }
                $ConfirmTemplate = EmailTemplate::where('ref_code','hotelConfirmMail')->first();
                if (!empty($ConfirmTemplate)) {
                    $guest_html = ($OrderMasterNew->total_child > 0) ? '<strong>Adult: </strong>'. $OrderMasterNew->total_adults .', <strong>Child (Age 6y and below): </strong>'. $OrderMasterNew->total_child : '<strong>Adult:</strong>'. $OrderMasterNew->total_adults;

                    $arrival_data = !empty($OrderMasterNew->expected_arrival_time) ? '<td><strong>Expected Arrival:</strong> '. $OrderMasterNew->expected_arrival_time .'</td>' : '';
                                        $arrival_data .= !empty($OrderMasterNew->need_pickup) ? '<td><strong>Need Pickup:</strong> '. $OrderMasterNew->need_pickup .'</td>' : '';

                    $maplocation = 'https://maps.google.com/maps?q=loc:'. $MasterHotel->map_lat .','. $MasterHotel->map_lng;
                    $mapImage = $this->site . 'images/frontend/MAP.jpg';

                    $msg = str_replace(array("~vendorLogo~", "~username~", "~hotelname~", "~checkindate~", "~checkintime~", "~checkoutdate~", "~checkouttime~", "~nights~", "~totalguest~", "~roomdetails~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~hoteladdress~", "~arrivaldetails~", "~invoiceid~", "~bookingqrCode~", "~maplocation~", "~mapimage~", "~managername~", "~contactno~"),
                            array($this->site . $Vendor->photo, $OrderMasterNew->customer_name, $OrderMasterNew->service_name, date("d M Y", strtotime($OrderMasterNew->start_date)), $MasterHotel->check_in_time, date("d M Y", strtotime($OrderMasterNew->end_date)), $MasterHotel->check_out_time, $totalNight, $guest_html, $room_html, number_format($OrderMasterNew->total_order_price, 2), $OrderMasterNew->transaction_id, $OrderMasterNew->payment_method, $MasterHotel->terms_conditions, $MasterHotel->real_address, $arrival_data, '<h3>'. $OrderMasterNew->service_name .'</h3><b>Booking ID : '. $OrderMasterNew->invoice_id .'</b>', $this->site . $OrderMasterNew->qr_code, $maplocation, $mapImage, $MasterHotel->manager_name, $MasterHotel->reception_contact), $ConfirmTemplate->source);

                    $OrderMasterNew->confimation_voucher = $msg;

                }

                $OrderMasterNew->save();

                $responce['status'] = 1;
                $responce['message'] = 'Order details updated successfully.';
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to update order details';
            }
        } elseif ($request->request_type == 'get_car_order_details') {
            $OrderMaster = OrderMaster::find($request->orderId);
            if (!empty($OrderMaster)) {
                $html = '<table class="table table-hover table-bordered"><tr><th>Invoice Id</th><th>Booking Date</th><th>Mode of Transport</th><th>Quantity</th><th>Distance</th><th>Start/End Date</th><th>Total Amount</th><th>STATUS</th></tr>';

                $html .= '<tr><td>' . $OrderMaster->invoice_id . '</td><td>' . date("M d Y h:i a", strtotime($OrderMaster->created_at)) . '</td><td>' . $OrderMaster->service_name . '</td><td>' . $OrderMaster->service_quantity . '</td><td>' . $OrderMaster->travel_distance . '</td><td>[' . date("M d Y h:i a", strtotime($OrderMaster->start_date . ' ' . $OrderMaster->start_time)) . ' - ' . date("M d Y h:i a", strtotime($OrderMaster->end_date . ' ' . $OrderMaster->end_time)) . ']</td><td>' . $OrderMaster->total_order_price . '</td><td>' . $OrderMaster->payment_status . '</td></tr>';
                $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
                $html .= '<tr><td colspan="5" style="text-align:left;"><strong>Transaction Id</strong> : ' . $txn_id . '</td><td colspan="5" style="text-align:left;"><strong>Payment Method</strong> : ' . $OrderMaster->payment_gateway . '</td></tr></table>';
                $html .= '<h3 class="text-center">Payment Details</h3>';
                $html .= '<table class="table table-hover table-bordered"><tr><th>Pick up</th><th>Drop</th><th>Start Date</th><th>End Date</th><th>Distance</th><th>Amount</th></tr>';
                $OrderDeatil = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                $room_pricing = '';
                foreach ($OrderDeatil as $value) {
                    $room_pricing .= '<tr><td>' . $value->pickup_address . ', ' . $value->pickup_city . '</td><td>' . $value->drop_address . ', ' . $value->drop_city . '</td><td>' . date("M d Y h:i a", strtotime($value->start_date . ' ' . $value->start_time)) . '</td><td>' . date("M d Y h:i a", strtotime($value->end_date . ' ' . $value->end_time)) . '</td><td>' . $value->distance . '</td><td >' . $value->total_room_price . '</td></tr>';
                }
                $room_pricing .= '<tr><th style="text-align: right;" colspan="5">Total</th><td  style="text-align: center;">' . $OrderMaster->total_service_price . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="5">Discount</th><td  style="text-align: center;">' . $OrderMaster->coupon_amount . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="5">Net Total</th><td  style="text-align: center;">' . $OrderMaster->sub_total_price . '</td</tr>';
                $room_pricing .= '<tr><th style="text-align: right;" colspan="5">GST</th><td  style="text-align: center;">' . $OrderMaster->tax_amount . '</td</tr>';
                if ($OrderMaster->guide_charge > 0 && $OrderMaster->days_for_guide > 0) {
                    $room_pricing .= '<tr><th style="text-align: right;" colspan="5">Guide Charge</th><td  style="text-align: center;">' . $OrderMaster->guide_charge . '</td</tr>';
                }
                $room_pricing .= '<tr><th style="text-align: right;" colspan="5">Grand Total</th><td  style="text-align: center;">' . $OrderMaster->total_order_price . '</td</tr>';
                $html .= $room_pricing . '</table>';
                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
        } elseif ($request->request_type == 'get_tour_order_details') {
            $OrderMaster = OrderMaster::find($request->orderId);
            if (!empty($OrderMaster)) {
                $html = '<table class="table table-hover table-bordered"><tr><th>Invoice Id</th><th>Booking Date</th><th>Tour Name</th><th>Tour Date</th><th>No. of Person</th><th>Total Amount</th><th>STATUS</th></tr>';
                $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
                $check_date = date("d M Y", strtotime($OrderMaster->start_date));
                $accomodation = '';
                if ($OrderMaster->service_category == 'package') {
                    $accomodation .= '<h3 class="text-center">Accomodation Details</h3>';
                    $accomodation .= '<table class="table table-hover table-bordered"><tr><th>Hotel Name</th><th>Room name</th><th>Check In/Out</th><th>Quantity</th></tr>';
                    $OrderDetail = OrderDetail::select('service_name', 'start_date', 'end_date', 'service_item_name', DB::raw('SUM(service_item_quantity)as totQty'))
                        ->where('order_master_id', $OrderMaster->id)
                        ->groupBy('start_date')
                        ->get();
                    foreach ($OrderDetail as $value) {
                        $accomodation .= '<tr><td>' . $value->service_name . '</td><td>' . $value->service_item_name . '</td><td>' . date("M d Y", strtotime($value->start_date)) . ' - ' . date("M d Y", strtotime($value->end_date)) . '</td><td>' . $value->totQty . '</td></tr>';
                    }
                    $accomodation .= '</table>';
                }
                $html .= '<tr><td>' . $OrderMaster->invoice_id . '</td><td>' . date("M d Y H:i:s", strtotime($OrderMaster->created_at)) . '</td><td>' . $OrderMaster->service_name . '</td><td>' . $check_date . '</td><td>' . $OrderMaster->total_guests . '</td><td>' . $OrderMaster->total_order_price . '</td><td>' . $OrderMaster->payment_status . '</td></tr>';
                $html .= '<tr><td colspan="5" style="text-align:left;"><strong>Transaction Id</strong> : ' . $txn_id . '</td><td colspan="5" style="text-align:left;"><strong>Payment Method</strong> : ' . $OrderMaster->payment_method . '</td></tr></table>';
                $html .= $accomodation;

                $html .= '<h3 class="text-center">Payment Details</h3>';
                $html .= '<table class="table table-hover table-bordered"><tr><th>Service Name</th><th>Quantity</th><th>Rate</th><th>Total</th></tr>';
                $html .= '<tr><td>Adult</td><td>' . $OrderMaster->total_adults . '</td><td>' . number_format($OrderMaster->adult_price, 2) . '</td><td>' . number_format($OrderMaster->total_adults * $OrderMaster->adult_price, 2) . '</td></tr>';
                if ($OrderMaster->total_child > 0) {
                    $html .= '<tr><td>Child</td><td>' . $OrderMaster->total_child . '</td><td>' . number_format($OrderMaster->child_price, 2) . '</td><td>' . number_format($OrderMaster->total_child * $OrderMaster->child_price, 2) . '</td></tr>';
                }
                $html .= '<tr><th style="text-align: right;" colspan="3">Total :</th><td style="text-align: center;">' . number_format($OrderMaster->total_service_price, 2) . '</td</tr>';
                $html .= '<tr><th style="text-align: right;" colspan="3">Discount :</th><td style="text-align: center;">' . number_format($OrderMaster->coupon_amount, 2) . '</td</tr>';
                $html .= '<tr><th style="text-align: right;" colspan="3">Net Total :</th><td style="text-align: center;">' . number_format($OrderMaster->sub_total_price, 2) . '</td</tr>';
                $html .= '<tr><th style="text-align: right;" colspan="3">GST :</th><td style="text-align: center;">' . number_format($OrderMaster->tax_amount, 2) . '</td</tr>';
                $html .= '<tr><th style="text-align: right;" colspan="3">Grand Total</th><td  style="text-align: center;">' . number_format($OrderMaster->total_order_price, 2) . '</td</tr>';

                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
        } elseif ($request->request_type == 'get_ticket_order_details') {
            $OrderMaster = OrderMaster::find($request->orderId);
            if (!empty($OrderMaster)) {
                $html = '<table class="table table-hover table-bordered"><tr><th>Invoice Id</th><th>Order Id</th><th>Booking Date</th><th>Ticket Type</th><th>Ticket Name</th><th>Ticket Date</th><th>Duration</th><th>No. of Person</th><th>Total Amount</th><th>STATUS</th></tr>';
                $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
                $duration = !empty($OrderMaster->start_time) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All day';
                $guest = 'Adult : ' . $OrderMaster->total_adults . '<br>Child : ' . $OrderMaster->total_child;
                $html .= '<tr><td>' . $OrderMaster->invoice_id . '</td><td>' . $OrderMaster->order_id . '</td><td>' . date("M d Y H:i:s", strtotime($OrderMaster->created_at)) . '</td><td>' . $OrderMaster->service_category . '</td><td>' . $OrderMaster->service_name . '</td><td>' . date("M d Y", strtotime($OrderMaster->start_date)) . '</td><td>' . $duration . '</td><td>' . $guest . '</td><td>' . $OrderMaster->total_order_price . '</td><td>' . $OrderMaster->payment_status . '</td></tr>';
                $html .= '<tr><td colspan="5" style="text-align:left;"><strong>Transaction Id</strong> : ' . $txn_id . '</td><td colspan="5" style="text-align:left;"><strong>Payment Method</strong> : ' . $OrderMaster->payment_gateway . '</td></tr></table>';

                $html .= '<h3 class="text-center">Payment Details</h3>';
                $html .= '<table class="table table-hover table-bordered"><tr><th>Service Name</th><th>Quantity</th><th>Rate</th><th>Total</th></tr>';
                $html .= '<tr><td>Adult</td><td>' . $OrderMaster->total_adults . '</td><td>' . number_format($OrderMaster->adult_price, 2) . '</td><td>' . number_format($OrderMaster->total_adults * $OrderMaster->adult_price, 2) . '</td></tr>';
                if ($OrderMaster->total_child > 0) {
                    $html .= '<tr><td>Child</td><td>' . $OrderMaster->total_child . '</td><td>' . number_format($OrderMaster->child_price, 2) . '</td><td>' . number_format($OrderMaster->total_child * $OrderMaster->child_price, 2) . '</td></tr>';
                }
                $occupied_service = '';
                $extra_services = json_decode($OrderMaster->room_details, 2);
                if (!empty($extra_services)) {
                    foreach ($extra_services as $val) {
                        $occupied_service .= '<tr><td>' . $val['name'] . '</td><td>' . $val['quantity'] . '</td><td>' . number_format($val['price'], 2) . '</td><td>' . number_format($val['price'] * $val['quantity'], 2) . '</td></tr>';
                    }
                }
                $html .= $occupied_service;
                $html .= '<tr><td colspan="3" style="text-align: right;">Sub Total</td><td>' . number_format($OrderMaster->sub_total_price, 2) . '</td></tr><tr><td colspan="3" style="text-align: right;">GST</td><td>' . number_format($OrderMaster->tax_amount, 2) . '</td></tr><tr><td colspan="3" style="text-align: right;">Grand Total</td><td>' . number_format($OrderMaster->total_order_price, 2) . '</td></tr>';
                $html .= '</table>';

                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
        } elseif ($request->request_type == 'get_food_order_details') {
            $OrderMaster = OrderMaster::find($request->orderId);
            if (!empty($OrderMaster)) {
                $html = '<table class="table table-hover table-bordered"><tr><th>Invoice Id</th><th>Order Id</th><th>Booking Date</th><th>Supplier</th><th>Total Amount</th><th>STATUS</th></tr>';
                $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
                $html .= '<tr><td>' . $OrderMaster->invoice_id . '</td><td>' . $OrderMaster->order_id . '</td><td>' . date("M d Y H:i:s", strtotime($OrderMaster->created_at)) . '</td><td>' . $OrderMaster->service_name . '</td><td>' . $OrderMaster->total_order_price . '</td><td>' . $OrderMaster->payment_status . '</td></tr>';
                $html .= '<tr><td colspan="10" style="text-align:left;"><strong>Transaction Id</strong> : ' . $txn_id . '</td></tr></table>';

                $html .= '<h3 class="text-center">Payment Details</h3>';
                $html .= '<table class="table table-hover table-bordered"><tr><th>Item Name</th><th>Quantity</th><th>Price</th><th>Total Amount</th></tr>';
                $OrderDeatil = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                $food_pricing = '';
                foreach ($OrderDeatil as $value) {
                    $food_pricing .= '<tr><td>' . $value->service_item_name . '</td><td>' . $value->service_item_quantity . '</td><td>' . number_format($value->service_item_price, 2) . '</td><td>' . number_format($value->total_room_price, 2) . '</td></tr>';
                }
                $html .= $food_pricing;
                $html .= '<tr><td colspan="3" style="text-align: right;">Sub Total</td><td>' . number_format($OrderMaster->sub_total_price, 2) . '</td></tr><tr><td colspan="3" style="text-align: right;">GST</td><td>' . number_format($OrderMaster->tax_amount, 2) . '</td></tr><tr><td colspan="3" style="text-align: right;">Grand Total</td><td>' . number_format($OrderMaster->total_order_price, 2) . '</td></tr></table>';
                // $html .= '<tr><th style="text-align: right;" colspan="3">Grand Total</th><td  style="text-align: center;">' . $OrderMaster->total_order_price . '</td</tr></table>';

                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
        } elseif ($request->request_type == 'get_merchant_order_details') {
            $OrderMaster = OrderMaster::find($request->orderId);
            $OrderDetail = OrderDetail::where(['order_master_id' => $request->orderId, 'vendor_id' => $request->vendorId])->get();
            if (!empty($OrderMaster)) {
                $detail = '';
                $total_amt = 0;
                if (!empty($OrderDetail)) {
                    foreach ($OrderDetail as $value) {
                        $detail .= '<tr><td>' . $value->service_item_name . '</td><td>' . $value->service_item_quantity . '</td><td>' . number_format($value->unit_total_price, 2) . '</td><td>' . number_format($value->total_room_price, 2) . '</td></tr>';
                        $total_amt += $value->total_room_price;
                    }
                }
                $total_amt = number_format($total_amt, 2);
                $html = '<table class="table table-hover table-bordered"><tr><th>Invoice Id</th><th>Order Id</th><th>Booking Date</th><th>Total Amount</th><th>STATUS</th></tr>';
                $txn_id = !empty($OrderMaster->transaction_id) ? $OrderMaster->transaction_id : 'N/A';
                $html .= '<tr><td>' . $OrderMaster->invoice_id . '</td><td>' . $OrderMaster->order_id . '</td><td>' . date("M d Y H:i:s", strtotime($OrderMaster->created_at)) . '</td><td>' . $total_amt . '</td><td>' . $OrderMaster->payment_status . '</td></tr>';
                $html .= '<tr><td colspan="10" style="text-align:left;"><strong>Transaction Id</strong> : ' . $txn_id . '</td></tr></table>';

                $html .= '<h3 class="text-center">Payment Details</h3>';
                $html .= '<table class="table table-hover table-bordered"><tr><th>Item Name</th><th>Quantity</th><th>Price</th><th>Total Amount</th></tr>';
                $html .= $detail;
                $html .= '<tr><th style="text-align: right;" colspan="3">Grand Total</th><td  style="text-align: center;">' . $total_amt . '</td</tr></table>';

                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get order details';
            }
        } elseif ($request->request_type == 'get_customer_details') {
            $Customer_details = OrderMaster::find($request->orderId);
            if (!empty($Customer_details)) {
                $responce['status'] = 1;
                $responce['data'] = $Customer_details;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get user details';
            }
        } elseif ($request->request_type == 'cancel_order') {
            $OrderMaster = OrderMaster::find($request->orderId); //where(['order_id' => $request->orderId, 'status' => $request->orderStatus])->first();
            if ($OrderMaster->service_type == 'hotel' && !(parent::checkWritePrivilege(28))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } elseif ($OrderMaster->service_type == 'car' && !(parent::checkWritePrivilege(30))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } elseif ($OrderMaster->service_type == 'tour' && !(parent::checkWritePrivilege(31))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } elseif ($OrderMaster->service_type == 'ticketing' && !(parent::checkWritePrivilege(32))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                if ($OrderMaster->status == 'pending') {
                    $OrderMaster->status = 'cancelled';
                    $OrderMaster->cancel_reason = 'Cancelled by vendor';
                    $OrderMaster->cancel_date = date("Y-m-d H:i:s");
                    $OrderMaster->refund_amount = 0;
                    $OrderMaster->refund_tax = 0;

                    if ($OrderMaster->service_type == 'hotel') {
                        $MasterHotel = MasterHotel::find($OrderMaster->service_name_id);
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);
                            if ($OrderMaster->book_from == 'blocked') {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_blocked' => DB::raw('total_blocked + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            } else {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_available' => DB::raw('total_available + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            }
                        }
                        $days = 0;
                        if ($OrderMaster->start_date == $OrderMaster->end_date) {
                            $days = 1;
                        } else {
                            $difference = strtotime($OrderMaster->end_date) - strtotime($OrderMaster->start_date);
                            $days = round($difference / (60 * 60 * 24));
                        }
                        $RoomDetails = json_decode($OrderMaster->room_details, 1);
                        foreach ($RoomDetails as $roomId => $room) {
                            $HotelRoom = HotelRoom::find($roomId);
                            for($i = 0; $i < $days; $i++) {
                                $checkInDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . $i . ' days'));
                                $MasterInventory = MasterInventory::where(['hotel_id' => $OrderMaster->service_name_id,'room_id' => $roomId,'date' => $checkInDate])->first();
                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $checkInDate])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && !empty($MasterInventory) && $OrderMaster->book_from != 'blocked') {
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                    <AvailRateUpdate locatorID="1">
                                                        <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                        <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                    </AvailRateUpdate>
                                                </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && !empty($MasterInventory) && $OrderMaster->book_from != 'blocked') {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                    ->where('block_date', $MasterInventory->date)
                                                    ->where('rooms', $MasterInventory->room_id)
                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                    ->get()->toArray();
                                    if (!empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        // Availability
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                        }
                    } elseif ($OrderMaster->service_category == 'package') {
                        // $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        $OrderDetails = OrderDetail::select('service_name_id', 'service_item_id', 'start_date', DB::raw('SUM(service_item_quantity)as totQty'))
                                        ->where('order_master_id', $OrderMaster->id)
                                        ->groupBy('start_date')
                                        ->orderBy('start_date', 'asc')->get();
                        DB::table('order_details')->where('order_master_id', $OrderMaster->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);
                        foreach ($OrderDetails as $value) {
                            $rQty = (int)$value->totQty;
                            if ($OrderMaster->book_from == 'blocked') {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_blocked' => DB::raw('total_blocked + '. $rQty),
                                        'total_booked' => DB::raw('total_booked - '. $rQty),
                                        'total_tour_booking' => DB::raw('total_tour_booking - '. $rQty),
                                        'total_offline_pending' => DB::raw('total_offline_pending - '. $rQty)
                                    ]);
                            } else {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_available' => DB::raw('total_available + '. $rQty),
                                        'total_booked' => DB::raw('total_booked - '. $rQty),
                                        'total_tour_booking' => DB::raw('total_tour_booking - '. $rQty),
                                        'total_offline_pending' => DB::raw('total_offline_pending - '. $rQty)
                                    ]);
                            }
                            $MasterHotel = MasterHotel::find($value->service_name_id);
                            $HotelRoom = HotelRoom::find($value->service_item_id);
                            $MasterInventory = MasterInventory::where(["date" => $value->start_date, 'room_id' => $value->service_item_id])->first();
                            if (!empty($MasterInventory)) {
                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && $OrderMaster->book_from != 'blocked') {
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                    <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                        <AvailRateUpdate locatorID="1">
                                            <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                            <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                        </AvailRateUpdate>
                                    </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && $OrderMaster->book_from != 'blocked') {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                    ->where('block_date', $MasterInventory->date)
                                                    ->where('rooms', $MasterInventory->room_id)
                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                    ->get()->toArray();
                                    if (!empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        // Availability
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                        }
                    } elseif ($OrderMaster->service_type == 'car') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);

                            $difference = strtotime(date("Y-m-d", strtotime($value->end_date))) - strtotime(date("Y-m-d", strtotime($value->start_date)));
                            $days = floor($difference / (60 * 60 * 24));
                            $cal_day = ($days == 0) ? 1 : $days + 1;
                            for ($i = 0; $i < $cal_day; $i++) {
                                $checkDate = date("Y-m-d", strtotime($value->start_date . ' + ' . $i . ' days'));
                                DB::table('rental_master_inventory')->where(['car_id' => $value->service_item_id, 'date' => checkDate])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_available' => DB::raw('total_available + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            }
                        }
                    }
                    $OrderMaster->save();
                    $responce['status'] = 1;
                    $responce['message'] = 'Booking cancelled successfully.';
                } elseif ($OrderMaster->status == 'completed') {
                    $OrderMaster->status = 'cancelled';
                    $OrderMaster->cancel_reason = $request->cancelReason;
                    $OrderMaster->cancel_date = date("Y-m-d H:i:s");


                    $service_type = $OrderMaster->service_type;

                    if ($OrderMaster->service_type == 'tour') {
                        $service_type = ($OrderMaster->service_category == 'sight seeing') ? 'sight-seeing' : 'package';
                    } elseif ($OrderMaster->service_type == 'car') {
                        $service_type = 'rental';
                    } elseif ($OrderMaster->service_type == 'ticketing') {
                        $service_type = str_replace(' ', '-', trim(strtolower($OrderMaster->service_category)));
                    }
                    $refund_percent = 100;
                    $refund_amount = $OrderMaster->total_order_price;
                    $OrderMaster->refund_amount = $refund_amount;
                    $OrderMaster->refund_tax = $OrderMaster->tax_amount;
                    $PaymentHistory = PaymentHistory::find($OrderMaster->payment_id);
                    // if ($OrderMaster->order_type == 'online' && !empty($PaymentHistory) && $OrderMaster->payment_gateway == 'paytm' && $refund_amount > 0) {

                    //     require_once public_path('paytm_lib/config_paytm.php');
                    //     require_once public_path('paytm_lib/encdec_paytm.php');

                    //     $paytmParams = array();
                    //     $reference_id = date("dmY") . time();
                    //     $paytmParams["body"] = array(
                    //         "mid"          => PAYTM_MERCHANT_MID,
                    //         "txnType"      => "REFUND",
                    //         "orderId"      => $PaymentHistory->transaction_id,
                    //         "txnId"        => $PaymentHistory->mihpayid,
                    //         "refId"        => $reference_id,
                    //         "refundAmount" => $refund_amount,
                    //     );
                    //     $checksum = generateSign($paytmParams["body"], PAYTM_MERCHANT_KEY);
                    //     $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), PAYTM_MERCHANT_KEY);
                    //     $paytmParams["head"] = array(
                    //         "signature"	  => $checksum
                    //     );
                    //     $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

                    //     $url = "https://securegw-stage.paytm.in/refund/apply";
                    //     if (PAYTM_ENVIRONMENT == 'PROD') {
                    //         $url = "https://securegw.paytm.in/refund/apply";
                    //     }

                    //     $ch = curl_init($url);
                    //     curl_setopt($ch, CURLOPT_POST, 1);
                    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                    //     $responseJson = curl_exec($ch);
                    //     $response = json_decode($responseJson, 1);

                    //     $response_timestamp = $signature = $txn_timestamp = $result_code = $result_msg = $refund_txn_id = $response_json = $refund_status = '';

                    //     $response_timestamp = isset($response['head']['responseTimestamp']) ? $response['head']['responseTimestamp'] : '';
                    //     $signature = isset($response['head']['signature']) ? $response['head']['signature'] : '';
                    //     $txn_timestamp = isset($response['body']['txnTimestamp']) ? date("Y-m-d H:i:s", strtotime($response['body']['txnTimestamp'])) : '';
                    //     $result_code = isset($response['body']['resultInfo']['resultCode']) ? $response['body']['resultInfo']['resultCode'] : '';
                    //     $result_msg = isset($response['body']['resultInfo']['resultMsg']) ? $response['body']['resultInfo']['resultMsg'] : '';
                    //     $refund_status = isset($response['body']['resultInfo']['resultStatus']) ? $response['body']['resultInfo']['resultStatus'] : '';
                    //     $refund_txn_id = isset($response['body']['refundId']) ? $response['body']['refundId'] : '';
                    //     $response_json = $responseJson;

                    //     $RefundData = new CustomerRefund([
                    //         'vendor_id' => $OrderMaster->vendor_id,
                    //         'order_id' => $request->orderId,
                    //         'invoice_id' => $OrderMaster->invoice_id,
                    //         'order_type' => $OrderMaster->order_type,
                    //         'service_type' => $service_type,
                    //         'customer_id' => $OrderMaster->customer_id,
                    //         'order_date' => $OrderMaster->created_at,
                    //         'cancel_date' => date("Y-m-d"),
                    //         'paid_amount' => $OrderMaster->total_order_price,
                    //         'refund_amount' => $refund_amount,
                    //         'refund_percent' => $refund_percent,
                    //         'payment_method' => $OrderMaster->payment_gateway,
                    //         'response_timestamp' => $response_timestamp,
                    //         'signature' => $signature,
                    //         'txn_timestamp' => $txn_timestamp,
                    //         'result_code' => $result_code,
                    //         'result_msg' => $result_msg,
                    //         'refund_txn_id' => $refund_txn_id,
                    //         'response_json' => $response_json,
                    //         'refund_status' => $refund_status,
                    //         'client_txn_id' => $PaymentHistory->transaction_id,
                    //         'pg_txn_id' => $PaymentHistory->mihpayid,
                    //         'reference_id' => $reference_id,
                    //         'payment_id' => $PaymentHistory->id
                    //     ]);
                    // }
                    $s_type = ($OrderMaster->service_type == 'car') ? 'rental' : $OrderMaster->service_type;
                    if (!empty($PaymentHistory) && $OrderMaster->payment_gateway == 'hdfc' && $refund_amount > 0) {
                        require_once public_path('paytm_lib/config_paytm.php');

                        if (!empty($OrderMaster->hdfc_key) && !empty($OrderMaster->hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                            $HDFC_KEY = $OrderMaster->hdfc_key;
                            $HDFC_SALT = $OrderMaster->hdfc_salt;
                        }

                        $command = "cancel_refund_transaction";
                        $var1 = $PaymentHistory->mihpayid;                  //mihpayid
                        $reference_id = $var2 = date('dmY') . time();       //request id
                        $var3 = $refund_amount;                             //amount

                        $key = $HDFC_KEY;
                        $salt = $HDFC_SALT;

                        $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
                        $hash = strtolower(hash('sha512', $hash_str));

                        $r = array('key' => $key, 'hash' => $hash, 'command' => $command, 'var1' => $var1, 'var2' => $var2, 'var3' => $var3);
                        $qs = http_build_query($r);
                        $wsUrl = VERIFY_URL; //"https://test.payu.in/merchant/postservice.php?form=2";

                        $c = curl_init();
                        curl_setopt($c, CURLOPT_URL, $wsUrl);
                        curl_setopt($c, CURLOPT_POST, 1);
                        curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                        $o = curl_exec($c);
                        // if (curl_errno($c)) {
                        //     $sad = curl_error($c);
                        //     throw new Exception($sad);
                        // }
                        curl_close($c);
                        $valueSerialized = @unserialize($o);
                        $response = json_decode($o, 1);

                        if (isset($response['status']) && $response['status'] != 1) {
                            $responce['status'] = 0;
                            $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                            echo json_encode($responce);
                            exit;
                        }

                        $result_msg = $response['msg'];
                        $refund_txn_id = isset($response['bank_ref_num']) ? $response['bank_ref_num'] : '';
                        $rquest_id = isset($response['request_id']) ? $response['request_id'] : '';

                        $RefundData = new CustomerRefund([
                            'vendor_id' => $OrderMaster->vendor_id,
                            'order_id' => $request->orderId,
                            'invoice_id' => $OrderMaster->invoice_id,
                            'order_type' => $OrderMaster->order_type,
                            'service_type' => $s_type,
                            'service_id' => $OrderMaster->service_name_id,
                            'customer_id' => $OrderMaster->customer_id,
                            'order_date' => $OrderMaster->created_at,
                            'cancel_date' => date("Y-m-d"),
                            'paid_amount' => $OrderMaster->total_order_price,
                            'refund_amount' => $refund_amount,
                            'refund_percent' => $refund_percent,
                            'payment_method' => $OrderMaster->payment_gateway,
                            'client_txn_id' => $PaymentHistory->transaction_id,
                            'pg_txn_id' => $PaymentHistory->mihpayid,
                            'refund_status' => 'PENDING',
                            'result_msg' => $result_msg,
                            'reference_id' => $rquest_id,
                            'refund_txn_id' => $refund_txn_id,
                            'response_json' => json_encode($response),
                            'payment_id' => $PaymentHistory->id
                        ]);
                        $RefundData->save();
                        // if ($response['status'] != 1) {
                        //     $responce['status'] = 0;
                        //     $responce['message'] = $result_msg;
                        //     echo json_encode($responce);
                        //     exit;
                        // }
                    } else {
                        $RefundData = new CustomerRefund([
                            'vendor_id' => $OrderMaster->vendor_id,
                            'order_id' => $request->orderId,
                            'invoice_id' => $OrderMaster->invoice_id,
                            'order_type' => $OrderMaster->order_type,
                            'service_type' => $s_type,
                            'service_id' => $OrderMaster->service_name_id,
                            'customer_id' => $OrderMaster->customer_id,
                            'order_date' => $OrderMaster->created_at,
                            'cancel_date' => date("Y-m-d"),
                            'paid_amount' => $OrderMaster->total_order_price,
                            'refund_amount' => $refund_amount,
                            'refund_percent' => $refund_percent,
                            'payment_method' => $OrderMaster->payment_gateway,
                            'refund_status' => 'success'
                        ]);
                        $RefundData->save();
                    }
                    $OrderMaster->save();
                    $Message = $Subject = '';
                    $Message = "<p style='color:#000000;'>Dear " . $OrderMaster->customer_name . ",</p>";
                    $Message .= "<p style='color:#000000;'>Your reservation booking for " . $OrderMaster->service_name . " having invoice no " . $OrderMaster->invoice_id . " has been cancelled.</p>";
                    $Message .= "<p style='color:#000000;'>An amount of &#8377;" . number_format($refund_amount, 2) . " will be refunded soon.</p>";
                    if ($service_type == 'sight-seeing' && $OrderMaster->service_name_id == 30) {
                        $Message = "<p style='color:#000000;'>Dear " . $OrderMaster->customer_name . ",</p>";
                        $Message .= "<p style='color:#000000;'>This is to notify you that due to some unavoidable circumstances we have suspended the sightseeing tour package to Satapada Chilika Lake from OTDC Transport Unit,Puri. The full booking amount of &#8377;" . number_format($refund_amount, 2) . " will be refunded to your account very soon.The cancel voucher is as follows. We are very sorry for the inconvenience caused.</p>";
                    }

                    $Vendor = User::find($OrderMaster->vendor_id);
                    $To = $OrderMaster->customer_email;
                    $service_email = '';
                    $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? $OrderMaster->gst_regd_no : 'N/A';

                    $User = User::find($OrderMaster->customer_id);
                    $mobileNumber = $OrderMaster->customer_phone;

                    if ($service_type == 'hotel') {
                        $MasterHotel = MasterHotel::find($OrderMaster->service_name_id);
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'asc')->get();
                        foreach ($OrderDetails as $value) {
                            $HotelRoom = HotelRoom::find($value->service_item_id);
                            $MasterInventory = MasterInventory::where(["date" => $value->start_date, 'room_id' => $value->service_item_id])->first();
                            if (!empty($MasterInventory)) {
                                if ($OrderMaster->book_from == 'blocked') {
                                    $MasterInventory->total_blocked += 1;
                                    $MasterInventory->total_booked -= 1;
                                    if ($OrderMaster->payment_status == 'success') {
                                        // if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= 1;
                                        // } else {
                                        //     $MasterInventory->total_online_completed -= 1;
                                        // }
                                    } else {
                                        // if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= 1;
                                        // } else {
                                        //     $MasterInventory->total_online_pending -= 1;
                                        // }
                                    }
                                } else {
                                    $MasterInventory->total_available += 1;
                                    $MasterInventory->total_booked -= 1;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type != 'online' || ($OrderMaster->order_type == 'online' && !empty($OrderMaster->offline_long_url))) {
                                            $MasterInventory->total_offline_completed -= 1;
                                        } else {
                                            $MasterInventory->total_online_completed -= 1;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type != 'online' || ($OrderMaster->order_type == 'online' && !empty($OrderMaster->offline_long_url))) {
                                            $MasterInventory->total_offline_pending -= 1;
                                        } else {
                                            $MasterInventory->total_online_pending -= 1;
                                        }
                                    }
                                }
                                $MasterInventory->save();
                            }
                            OrderDetail::find($value->id)->update(['status' => 'cancelled']);
                        }

                        $days = 0;
                        if ($OrderMaster->start_date == $OrderMaster->end_date) {
                            $days = 1;
                        } else {
                            $difference = strtotime($OrderMaster->end_date) - strtotime($OrderMaster->start_date);
                            $days = round($difference / (60 * 60 * 24));
                        }
                        $RoomDetails = json_decode($OrderMaster->room_details, 1);
                        foreach ($RoomDetails as $roomId => $room) {
                            $HotelRoom = HotelRoom::find($roomId);
                            for($i = 0; $i < $days; $i++) {
                                $checkInDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . $i . ' days'));
                                $MasterInventory = MasterInventory::where(['hotel_id' => $OrderMaster->service_name_id,'room_id' => $roomId,'date' => $checkInDate])->first();
                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $checkInDate])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && !empty($MasterInventory) && $OrderMaster->book_from != 'blocked') {
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                    <AvailRateUpdate locatorID="1">
                                                        <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                        <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                    </AvailRateUpdate>
                                                </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && !empty($MasterInventory) && $OrderMaster->book_from != 'blocked') {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                    ->where('block_date', $MasterInventory->date)
                                                    ->where('rooms', $MasterInventory->room_id)
                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                    ->get()->toArray();
                                    if (!empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        // Availability
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                        }
                        // parent::updateMmtInventory($OrderMaster->vendor_id, $OrderMaster->service_name_id);

                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $room_html = '';
                        $room_details = json_decode($OrderMaster->room_details, 1);
                        foreach ($room_details as $value) {
                            $room_html .= $value['quantity'] . ' ' . $value['room_name'] . ', ';
                        }
                        $room_html = trim($room_html, ', ');
                        $HotelInvoice = EmailTemplate::where('ref_code', 'hotelCancelInvoice')->first();
                        $vendorGSTNo = (!empty($MasterHotel->gst_number)) ? $MasterHotel->gst_number : 'N/A';

                        $customer_address = $OrderMaster->customer_address1;
                        $customer_address .= !empty($OrderMaster->customer_city) ? ',<br>'. $OrderMaster->customer_city : '';
                        $customer_address .= !empty($OrderMaster->customer_state) ? ',<br>'. $OrderMaster->customer_state : '';
                        $customer_address .= !empty($OrderMaster->customer_country) ? ',<br>'. $OrderMaster->customer_country : '';
                        $customer_address .= !empty($OrderMaster->customer_zipcode) ? ', '. $OrderMaster->customer_zipcode : '';
                        $customer_address .= (!empty($OrderMaster->gst_regd_no)) ? '<br><u><b>GSTN No: ' . $OrderMaster->gst_regd_no . '</b></u>' : '';
                        $customer_address .= (!empty($OrderMaster->gst_company_name)) ? '<br><u><b>Company Name: ' . $OrderMaster->gst_company_name . '</b></u>' : '';

                        $Subject = $HotelInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~hoteladdress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~hotelname~", "~roomdetails~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~hotelemail~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~hotelgst~", "~usergst~", "~invoiceserial~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $customer_address, $MasterHotel->real_address, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $room_html, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $MasterHotel->contact_email, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo, $OrderMaster->invoice_serial),
                            $HotelInvoice->source
                        );
                        if ($OrderMaster->vendor_id != 3) {
                            $service_email = $MasterHotel->contact_email;
                            if (!empty($MasterHotel->additional_email)) {
                                $service_email = !empty($service_email) ? $service_email .','. $MasterHotel->additional_email : $MasterHotel->additional_email;
                            }
                        }

                        if (!empty($User) && ($User->access_type == 'agent' || $User->user_role == 'agent_staff')) {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'hotelCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~hoteladdress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~hotelname~", "~roomdetails~", "~checkdate~", "~hotelemail~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $MasterHotel->real_address, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $room_html, $check_date, $MasterHotel->contact_email, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'rental') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                        foreach ($OrderDetails as $route) {
                            $difference = strtotime(date("Y-m-d", strtotime($route->end_date))) - strtotime(date("Y-m-d", strtotime($route->start_date)));
                            $days = floor($difference / (60 * 60 * 24));
                            $cal_day = ($days == 0) ? 1 : $days + 1;
                            for ($i = 0; $i < $cal_day; $i++) {
                                $checkDate = date("Y-m-d", strtotime($route->start_date . ' + ' . $i . ' days'));
                                $MasterInventory = RentalMasterInventory::where(["date" => $checkDate, "car_id" => $route->service_name_id])->first();
                                if (!empty($MasterInventory)) {
                                    $MasterInventory->total_available += 1;
                                    $MasterInventory->total_booked -= 1;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= 1;
                                        } else {
                                            $MasterInventory->total_online_completed -= 1;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= 1;
                                        } else {
                                            $MasterInventory->total_online_pending -= 1;
                                        }
                                    }
                                    $MasterInventory->save();
                                }
                            }
                            OrderDetail::find($route->id)->update(['status' => 'cancelled']);
                        }

                        $MasterCar = MasterCar::find($OrderMaster->service_name_id);
                        $vendorGSTNo = (!empty($MasterCar->gst_number)) ? $MasterCar->gst_number : 'N/A';

                        $service_email = $MasterCar->contact_email;
                        if (!empty($MasterCar->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $MasterCar->additional_email : $MasterCar->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $RentalInvoice = EmailTemplate::where('ref_code', 'rentalCancelInvoice')->first();
                        $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~vendorgst~", "~usergst~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo),
                            $RentalInvoice->source
                        );

                        if (!empty($User) && ($User->access_type == 'agent' || $User->user_role == 'agent_staff')) {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'rentalCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'package') {
                        // $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'asc')->get();
                        $OrderDetails = OrderDetail::select('service_name_id', 'service_item_id', 'start_date', DB::raw('SUM(service_item_quantity)as totQty'))
                                        ->where('order_master_id', $OrderMaster->id)
                                        ->groupBy('start_date')
                                        ->orderBy('start_date', 'asc')->get();
                        OrderDetail::where('order_master_id', $OrderMaster->id)->update(['status' => 'cancelled']);
                        foreach ($OrderDetails as $value) {
                            $rQty = (int)$value->totQty;
                            $MasterInventory = MasterInventory::where(["date" => $value->start_date, 'room_id' => $value->service_item_id])->first();
                            if (!empty($MasterInventory)) {
                                if ($OrderMaster->book_from == 'blocked') {
                                    $MasterInventory->total_blocked += $rQty;
                                    $MasterInventory->total_booked -= $rQty;
                                    $MasterInventory->total_tour_booking -= $rQty;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_completed -= $rQty;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_pending -= $rQty;
                                        }
                                    }
                                } else {
                                    $MasterInventory->total_available += $rQty;
                                    $MasterInventory->total_booked -= $rQty;
                                    $MasterInventory->total_tour_booking -= $rQty;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_completed -= $rQty;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_pending -= $rQty;
                                        }
                                    }
                                }
                                $MasterInventory->save();
                                $MasterHotel = MasterHotel::find($value->service_name_id);
                                $HotelRoom = HotelRoom::find($value->service_item_id);
                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && $OrderMaster->book_from != 'blocked') {
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                    <AvailRateUpdate locatorID="1">
                                                        <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                        <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                    </AvailRateUpdate>
                                                </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && $OrderMaster->book_from != 'blocked') {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                    ->where('block_date', $MasterInventory->date)
                                                    ->where('rooms', $MasterInventory->room_id)
                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                    ->get()->toArray();
                                    if (!empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        // Availability
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                        }
                        // parent::updateMmtInventory($OrderMaster->vendor_id, $OrderMaster->service_name_id);

                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $service_email = $Tour->contact_email;
                        if (!empty($Tour->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Tour->additional_email : $Tour->additional_email;
                        }
                        $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $PackageInvoice = EmailTemplate::where('ref_code', 'packageCancelInvoice')->first();
                        $Subject = $PackageInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $vendorGSTNo = (!empty($Tour->gst_number)) ? $Tour->gst_number : 'N/A';
                        $Message .= str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"), array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id), $PackageInvoice->source);

                        if (!empty($User) && ($User->access_type == 'agent' || $User->user_role == 'agent_staff')) {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'packageCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~ticketquantity~", "~canceldate~"), array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $guest_data, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))), $CustomerInvoice->source);
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'sight-seeing') {
                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $service_email = $Tour->contact_email;
                        if (!empty($Tour->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Tour->additional_email : $Tour->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        $SightseenInvoice = EmailTemplate::where('ref_code', 'sightseenCancelInvoice')->first();
                        $Subject = $SightseenInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $vendorGSTNo = (!empty($Tour->gst_number)) ? $Tour->gst_number : 'N/A';
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $SightseenInvoice->source
                        );

                        if (!empty($User) && ($User->access_type == 'agent' || $User->user_role == 'agent_staff')) {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'sightseenCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~ticketquantity~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $OrderMaster->total_guests, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'experience-ticketing' || $service_type == 'events' || $service_type == 'entry-ticket') {
                        $Ticket = Ticket::find($OrderMaster->service_name_id);
                        $service_email = $Ticket->contact_email;
                        if (!empty($Ticket->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Ticket->additional_email : $Ticket->additional_email;
                        }
                        $TicketInvoice = EmailTemplate::where('ref_code', 'ticketCancelInvoice')->first();
                        $Subject = $TicketInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $duration = (!empty($OrderMaster->start_time)) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All Day';
                        $vendorGSTNo = (!empty($Ticket->gst_number)) ? $Ticket->gst_number : 'N/A';

                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), ($OrderMaster->service_name_id == '24') ? '6<sup>th</sup> ' . $OrderMaster->service_name : $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $TicketInvoice->source
                        );


                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'ticketCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~duration~", "~ticketquantity~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $duration, $guest_data, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    }elseif ($service_type == 'hall'){
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        $BookingData = HallBooking::where('booking_id', $OrderMaster->order_id)->first();
                        $slotType = $BookingData->slot_type;
                        $slotType = $BookingData->slot_type;
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);

                            $difference = strtotime(date("Y-m-d", strtotime($value->end_date))) - strtotime(date("Y-m-d", strtotime($value->start_date)));
                            $days = floor($difference / (60 * 60 * 24));
                            $cal_day = ($days == 0) ? 1 : $days + 1;
                            for ($i = 0; $i < $cal_day; $i++) {
                                $checkDate = date("Y-m-d", strtotime($value->start_date . ' + ' . $i . ' days'));
                                if($slotType == 'FULL_DAY'){
                                    DB::table('t_hall_inventory')
                                        ->where('hall_id', $OrderMaster->service_item_id)
                                        ->where('inventory_date', $checkDate)
                                        ->update([
                                            'first_half_available' => '0',
                                            'second_half_available' => '0',
                                            'inventory_slot_type' => null,
                                            'updated_at' => now()
                                        ]);
                                }
                                if($slotType == 'FIRST_HALF'){
                                    DB::table('t_hall_inventory')
                                        ->where('hall_id', $OrderMaster->service_item_id)
                                        ->where('inventory_date', $checkDate)
                                        ->update([
                                            'first_half_available' => '0',
                                            'updated_at' => now(),
                                            'inventory_slot_type' => DB::raw("
                                                CASE
                                                    WHEN second_half_available = 0 THEN NULL
                                                    WHEN second_half_available = 1 THEN 2
                                                    ELSE inventory_slot_type
                                                END
                                            ")
                                        ]);
                                }
                                if($slotType == 'SECOND_HALF'){
                                    DB::table('t_hall_inventory')
                                        ->where('hall_id', $OrderMaster->service_item_id)
                                        ->where('inventory_date', $checkDate)
                                        ->update([
                                            'second_half_available' => '0',
                                            'updated_at' => now(),
                                            'inventory_slot_type' => DB::raw("
                                                CASE
                                                    WHEN first_half_available = 0 THEN NULL
                                                    WHEN first_half_available = 1 THEN 2
                                                    ELSE inventory_slot_type
                                                END
                                            ")
                                        ]);
                                }

                            }
                        }
                    }
                    OrderMaster::find($OrderMaster->id)->update(['cancel_voucher' => $Message]);
                    if (!empty($User) && ($User->access_type == 'agent' || $User->user_role == 'agent_staff')) {
                        $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancelAgent')->first();
                        if (!empty($SmsTemplate)) {
                            $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($OrderMaster->customer_name . ',', $OrderMaster->service_name, $OrderMaster->invoice_id, "\n", $OrderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
                            parent::sendSms($OrderMaster->customer_phone, $sms_txt, $SmsTemplate->templete_id);
                            $mobileNumber = $User->phone;
                        }
                    }
                    $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancel')->first();
                    if (!empty($SmsTemplate)) {
                        $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($OrderMaster->customer_name . ',', $OrderMaster->service_name, $OrderMaster->invoice_id, number_format($refund_amount, 2), "\n", $OrderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
                        parent::sendSms($mobileNumber, $sms_txt, $SmsTemplate->templete_id);
                    }
                    $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

                    $Vendor = User::find($OrderMaster->vendor_id);
                    $admin = User::where('role', 1)->first();
                    $bcc = [$Vendor->email, $admin->email];
                    if (!empty($service_email)) {
                        $bcc = array_merge($bcc, explode(',', $service_email));
                    }
                    try {
                        Mail::to($To)
                            ->bcc($bcc)
                            ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
                    }
                    catch(\Exception $e) {}

                    $responce['status'] = 1;
                    $responce['message'] = 'Booking cancelled successfully.';
                }
            }
        } elseif ($request->request_type == 'view_refund_amount') {
            $responce['message'] = '';
            $OrderMaster = OrderMaster::find($request->ID);
            if (!empty($OrderMaster)) {
                $cancel_message = '';
                $Today = date("Y-m-d", strtotime($request->date));
                $difference = strtotime($OrderMaster->start_date) - strtotime($Today);
                $days = round($difference / (60 * 60 * 24));
                if ($days == 0) {
                    $days = 1;
                }
                $service_type = $OrderMaster->service_type;
                if ($OrderMaster->service_type == 'tour') {
                    $service_type = ($OrderMaster->service_category == 'sight seeing') ? 'sight-seeing' : 'package';
                } elseif ($OrderMaster->service_type == 'car') {
                    $service_type = 'rental';
                } elseif ($OrderMaster->service_type == 'ticketing') {
                    $service_type = str_replace(' ', '-', trim(strtolower($OrderMaster->service_category)));
                }
                $CancelPolicy = CancelPolicy::where(['vendor_id' => $OrderMaster->vendor_id, 'service_type' => $service_type])
                    ->whereRaw('start < ' . $days)
                    ->whereRaw('end >=' . $days)
                    ->first();
                $refund_percent = 100;
                if (!empty($CancelPolicy)) {
                    $refund_percent = $CancelPolicy->percent;
                }
                $refund_amount = $refund_tax = 0;
                if ($OrderMaster->service_type == 'hotel') {
                    $policy[0] = 100;
                    // $Today = date("Y-m-d");
                    $HotelPolicy = CancelPolicy::where(['vendor_id' => $OrderMaster->vendor_id, 'service_type' => $service_type])->orderBy('start', 'asc')->pluck('percent', 'start')->toArray();
                    if (!empty($HotelPolicy)) {
                        $policy = $HotelPolicy;
                    }
                    $PolicyMin = array_keys($policy);
                    $comp_date = date("Y-m-d H:i", strtotime($request->date ." ". date("H:i")));
                    $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'asc')->get();
                    foreach ($OrderDetails as $key => $value) {
                        $in_date_time = $value->start_date .' '. date("H:i", strtotime($value->start_time));
                        $diff = strtotime($in_date_time) - strtotime($comp_date);
                        $day = $diff / (60 * 60 * 24);
                        // $diff = strtotime($value->start_date) - strtotime($Today);
                        // $day = round($diff / (60 * 60 * 24));
                        if ($day == 0) {
                            $day = 1;
                        }
                        $filter_res = array_filter($PolicyMin, function ($n) use ($day) {
                            return $n < $day;
                        });
                        $ref_percent = $policy[end($filter_res)];
                        if ($key == 0 && $ref_percent == 100) {
                            $refund_tax = $OrderMaster->tax_amount;
                            $refund_amount = $OrderMaster->total_order_price;
                            break;
                        }
                        $ref_tax = ceil($value->tax_amount * ($ref_percent / 100));
                        $ref_amt = ceil(($value->unit_total_price - $value->coupon_amount) * ($ref_percent / 100)) + $ref_tax;
                        $refund_tax += $ref_tax;
                        $refund_amount += $ref_amt;
                    }
                    $refund_tax = round($refund_tax, 2);
                    $refund_amount = round($refund_amount, 2);
                } elseif ($service_type == 'package') {
                    $Tour = Tour::find($OrderMaster->service_name_id);
                    $child_policy = json_decode($Tour->child_price_policy, 1);
                    $refund_policy = array();
                    if ($OrderMaster->price_type == 'singleoccupancy') {
                        $refund_policy = json_decode($Tour->single_share_policy, 1);
                    } elseif ($OrderMaster->price_type == 'doubleoccupancy') {
                        $refund_policy = json_decode($Tour->double_share_policy, 1);
                    } else {
                        $refund_policy = json_decode($Tour->triple_share_policy, 1);
                    }
                    $policy_type = '';
                    if ($days > 7) {
                        $policy_type = 'refund_before_7d';
                    } elseif ($days <= 7 && $days > 1) {
                        $policy_type = 'refund_within_7d';
                    } else {
                        $policy_type = 'refund_within_24hr';
                    }
                    foreach ($refund_policy as $value) {
                        $refund_value = ceil(($value['price'] * $OrderMaster->total_adults) * ($value[$policy_type] / 100));
                        $refund_amount += $refund_value;
                    }

                    if ($OrderMaster->total_child > 0) {
                        foreach ($child_policy as $value) {
                            $refund_value = ceil(($value['price'] * $OrderMaster->total_child) * ($value[$policy_type] / 100));
                            $refund_amount += $refund_value;
                        }
                    }

                    if ($OrderMaster->tax_amount > 0) {
                        $GstTable = GstTable::where(['vendor_id' => $Tour->vendor_id, 'service_type' => $service_type])
                            ->where('min_amount', '<=', $OrderMaster->sub_total_price)
                            ->orderBy('min_amount', 'DESC')
                            ->first();
                        if (!empty($GstTable)) {
                            $GstData = json_decode($GstTable->gst, 1);
                            foreach ($GstData as $key => $val) {
                                $gst_val = ceil($refund_amount * ((float) $val / 100));
                                $refund_tax += $gst_val;
                            }
                        } else {
                            $GSTData = GstDetail::pluck('value', 'name')->toArray();
                            foreach ($GSTData as $key => $val) {
                                $gst_val = ceil($refund_amount * ((float) $val / 100));
                                $refund_tax += $gst_val;
                            }
                        }
                    }
                    $refund_tax = round($refund_tax, 2);
                    $refund_amount = round($refund_amount + $refund_tax, 2);
                } elseif ($service_type == 'rental') {
                    $refund_guide_charge = ($OrderMaster->guide_charge > 0) ? ceil($OrderMaster->guide_charge * ($refund_percent / 100)) : 0;
                    $refund_tax = ceil($OrderMaster->tax_amount * ($refund_percent / 100));
                    $refund_amount = ceil($OrderMaster->sub_total_price * ($refund_percent / 100)) + $refund_tax + $refund_guide_charge;
                }elseif($service_type == 'hall'){
                    $CancelPolicy = CancelPolicy::where(['vendor_id' => $OrderMaster->vendor_id, 'service_type' => $service_type])
                    ->whereRaw('start < ' . abs($days))
                    ->whereRaw('end >=' . abs($days))
                    ->first();
                    if (!empty($CancelPolicy)) {
                        $refund_percent = $CancelPolicy->percent;
                    }
                    $refund_tax = ceil($OrderMaster->tax_amount * ($refund_percent / 100));
                    $refund_amount = ceil($OrderMaster->sub_total_price * ($refund_percent / 100)) + $refund_tax;
                } else {
                    $refund_tax = ceil($OrderMaster->tax_amount * ($refund_percent / 100));
                    $refund_amount = ceil($OrderMaster->sub_total_price * ($refund_percent / 100)) + $refund_tax;
                }
                $responce['status'] = 1;
                $responce['sub_total'] = round($refund_amount - $refund_tax, 2);
                $responce['gst'] = $refund_tax;
                $responce['total_refund'] = $refund_amount;
                $responce['min_date'] = date("Y-m-d", strtotime($OrderMaster->created_at));
                $responce['max_date'] = date("Y-m-d", strtotime($OrderMaster->start_date));
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Invalid Order id';
            }
        } elseif ($request->request_type == 'cancel_order_policy') {
            $OrderMaster = OrderMaster::find($request->orderId); //where(['order_id' => $request->orderId, 'status' => $request->orderStatus])->first();
            if ($OrderMaster->service_type == 'hotel' && !(parent::checkWritePrivilege(28))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } elseif ($OrderMaster->service_type == 'car' && !(parent::checkWritePrivilege(30))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } elseif ($OrderMaster->service_type == 'tour' && !(parent::checkWritePrivilege(31))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } elseif ($OrderMaster->service_type == 'ticketing' && !(parent::checkWritePrivilege(32))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            }elseif ($OrderMaster->service_type == 'hall' && !(parent::checkWritePrivilege(32))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                if ($OrderMaster->status == 'pending') {
                    $OrderMaster->status = 'cancelled';
                    $OrderMaster->cancel_reason = 'Cancelled by vendor';
                    $OrderMaster->cancel_date = date("Y-m-d H:i:s");
                    $OrderMaster->refund_amount = 0;
                    $OrderMaster->refund_tax = 0;

                    if ($OrderMaster->service_type == 'hotel') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);
                            if ($OrderMaster->book_from == 'blocked') {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_blocked' => DB::raw('total_blocked + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            } else {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_available' => DB::raw('total_available + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            }
                        }
                    } elseif ($OrderMaster->service_category == 'package') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);
                            if ($OrderMaster->book_from == 'blocked') {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_blocked' => DB::raw('total_blocked + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_tour_booking' => DB::raw('total_tour_booking - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            } else {
                                DB::table('master_inventory')->where(['room_id' => $value->service_item_id, 'date' => $value->start_date])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_available' => DB::raw('total_available + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_tour_booking' => DB::raw('total_tour_booking - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            }
                        }
                    } elseif ($OrderMaster->service_type == 'car') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);

                            $difference = strtotime(date("Y-m-d", strtotime($value->end_date))) - strtotime(date("Y-m-d", strtotime($value->start_date)));
                            $days = floor($difference / (60 * 60 * 24));
                            $cal_day = ($days == 0) ? 1 : $days + 1;
                            for ($i = 0; $i < $cal_day; $i++) {
                                $checkDate = date("Y-m-d", strtotime($value->start_date . ' + ' . $i . ' days'));
                                DB::table('rental_master_inventory')->where(['car_id' => $value->service_item_id, 'date' => $checkDate])
                                    ->update([
                                        'updated_at' => date("Y-m-d H:i:s"),
                                        'total_available' => DB::raw('total_available + 1'),
                                        'total_booked' => DB::raw('total_booked - 1'),
                                        'total_offline_pending' => DB::raw('total_offline_pending - 1')
                                    ]);
                            }
                        }
                    }elseif($OrderMaster->service_type == 'hall'){
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'ASC')->get();
                        $BookingData = HallBooking::where('booking_id', $OrderMaster->order_id)->first();
                        $slotType = $BookingData->slot_type;
                        foreach ($OrderDetails as $value) {
                            DB::table('order_details')->where('id', $value->id)->update(['status' => 'cancelled', 'refund_amount' => 0, 'refund_tax' => 0]);

                            $difference = strtotime(date("Y-m-d", strtotime($value->end_date))) - strtotime(date("Y-m-d", strtotime($value->start_date)));
                            $days = floor($difference / (60 * 60 * 24));
                            $cal_day = ($days == 0) ? 1 : $days + 1;
                            for ($i = 0; $i < $cal_day; $i++) {
                                $checkDate = date("Y-m-d", strtotime($value->start_date . ' + ' . $i . ' days'));
                                if($slotType == 'FULL_DAY'){
                                    DB::table('t_hall_inventory')
                                        ->where('hall_id', $OrderMaster->service_item_id)
                                        ->where('inventory_date', $checkDate)
                                        ->update([
                                            'first_half_available' => '0',
                                            'second_half_available' => '0',
                                            'inventory_slot_type' => null,
                                            'updated_at' => now()
                                        ]);
                                }
                                if($slotType == 'FIRST_HALF'){
                                    DB::table('t_hall_inventory')
                                        ->where('hall_id', $OrderMaster->service_item_id)
                                        ->where('inventory_date', $checkDate)
                                        ->update([
                                            'first_half_available' => '0',
                                            'updated_at' => now(),
                                            'inventory_slot_type' => DB::raw("
                                                CASE
                                                    WHEN second_half_available = 0 THEN NULL
                                                    WHEN second_half_available = 1 THEN 2
                                                    ELSE inventory_slot_type
                                                END
                                            ")
                                        ]);
                                }
                                if($slotType == 'SECOND_HALF'){
                                    DB::table('t_hall_inventory')
                                        ->where('hall_id', $OrderMaster->service_item_id)
                                        ->where('inventory_date', $checkDate)
                                        ->update([
                                            'second_half_available' => '0',
                                            'updated_at' => now(),
                                            'inventory_slot_type' => DB::raw("
                                                CASE
                                                    WHEN first_half_available = 0 THEN NULL
                                                    WHEN first_half_available = 1 THEN 2
                                                    ELSE inventory_slot_type
                                                END
                                            ")
                                        ]);
                                }

                            }
                        }
                    }
                    $OrderMaster->save();
                    $responce['status'] = 1;
                    $responce['message'] = 'Booking cancelled successfully.';
                } elseif ($OrderMaster->status == 'completed') {
                    $OrderMaster->status = 'cancelled';
                    $OrderMaster->cancel_reason = $request->cancelReason;
                    $OrderMaster->cancel_date = date("Y-m-d H:i:s", strtotime($request->date ." ". date("H:i:s")));
                    $Today = date("Y-m-d", strtotime($request->date));

                    $difference = strtotime($OrderMaster->start_date) - strtotime($Today);
                    $days = round($difference / (60 * 60 * 24));
                    if ($days == 0) {
                        $days = 1;
                    }

                    $service_type = $OrderMaster->service_type;
                    if ($OrderMaster->service_type == 'tour') {
                        $service_type = ($OrderMaster->service_category == 'sight seeing') ? 'sight-seeing' : 'package';
                    } elseif ($OrderMaster->service_type == 'car') {
                        $service_type = 'rental';
                    } elseif ($OrderMaster->service_type == 'ticketing') {
                        $service_type = str_replace(' ', '-', trim(strtolower($OrderMaster->service_category)));
                    }
                    $CancelPolicy = CancelPolicy::where(['vendor_id' => $OrderMaster->vendor_id, 'service_type' => $service_type])
                        ->whereRaw('start < ' . $days)
                        ->whereRaw('end >=' . $days)
                        ->first();
                    $refund_percent = 100;
                    if (!empty($CancelPolicy)) {
                        $refund_percent = $CancelPolicy->percent;
                    }
                    $refund_amount = $refund_tax = $refund_guide_charge = 0;
                    if ($OrderMaster->service_type == 'hotel') {
                        $policy[0] = 100;
                        $HotelPolicy = CancelPolicy::where(['vendor_id' => $OrderMaster->vendor_id, 'service_type' => $service_type])->orderBy('start', 'asc')->pluck('percent', 'start')->toArray();
                        if (!empty($HotelPolicy)) {
                            $policy = $HotelPolicy;
                        }
                        $PolicyMin = array_keys($policy);
                        $comp_date = date("Y-m-d H:i", strtotime($request->date ." ". date("H:i")));
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'asc')->get();
                        foreach ($OrderDetails as $key => $value) {
                            $in_date_time = $value->start_date .' '. date("H:i", strtotime($value->start_time));
                            $diff = strtotime($in_date_time) - strtotime($comp_date);
                            $day = $diff / (60 * 60 * 24);
                            // $diff = strtotime($value->start_date) - strtotime($Today);
                            // $day = round($diff / (60 * 60 * 24));
                            if ($day == 0) {
                                $day = 1;
                            }
                            $filter_res = array_filter($PolicyMin, function ($n) use ($day) {
                                return $n < $day;
                            });
                            $ref_percent = $policy[end($filter_res)];
                            if ($key == 0 && $ref_percent == 100) {
                                $refund_tax = $OrderMaster->tax_amount;
                                $refund_amount = $OrderMaster->total_order_price;
                                break;
                            }
                            $ref_tax = ceil($value->tax_amount * ($ref_percent / 100));
                            $ref_amt = ceil(($value->unit_total_price - $value->coupon_amount) * ($ref_percent / 100)) + $ref_tax;
                            $refund_tax += $ref_tax;
                            $refund_amount += $ref_amt;
                        }
                        $refund_percent = null;
                        $refund_tax = round($refund_tax, 2);
                        $refund_amount = round($refund_amount, 2);
                    } elseif ($service_type == 'package') {
                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $child_policy = json_decode($Tour->child_price_policy, 1);
                        $refund_policy = array();
                        if ($OrderMaster->price_type == 'singleoccupancy') {
                            $refund_policy = json_decode($Tour->single_share_policy, 1);
                        } elseif ($OrderMaster->price_type == 'doubleoccupancy') {
                            $refund_policy = json_decode($Tour->double_share_policy, 1);
                        } else {
                            $refund_policy = json_decode($Tour->triple_share_policy, 1);
                        }
                        $policy_type = '';
                        if ($days > 7) {
                            $policy_type = 'refund_before_7d';
                        } elseif ($days <= 7 && $days > 1) {
                            $policy_type = 'refund_within_7d';
                        } else {
                            $policy_type = 'refund_within_24hr';
                        }
                        foreach ($refund_policy as $value) {
                            $refund_value = ceil(($value['price'] * $OrderMaster->total_adults) * ($value[$policy_type] / 100));
                            $refund_amount += $refund_value;
                        }

                        if ($OrderMaster->total_child > 0) {
                            foreach ($child_policy as $value) {
                                $refund_value = ceil(($value['price'] * $OrderMaster->total_child) * ($value[$policy_type] / 100));
                                $refund_amount += $refund_value;
                            }
                        }

                        if ($OrderMaster->tax_amount > 0) {
                            $GstTable = GstTable::where(['vendor_id' => $Tour->vendor_id, 'service_type' => $service_type])
                                ->where('min_amount', '<=', $OrderMaster->sub_total_price)
                                ->orderBy('min_amount', 'DESC')
                                ->first();
                            if (!empty($GstTable)) {
                                $GstData = json_decode($GstTable->gst, 1);
                                foreach ($GstData as $key => $val) {
                                    $gst_val = ceil($refund_amount * ((float) $val / 100));
                                    $refund_tax += $gst_val;
                                }
                            } else {
                                $GSTData = GstDetail::pluck('value', 'name')->toArray();
                                foreach ($GSTData as $key => $val) {
                                    $gst_val = ceil($refund_amount * ((float) $val / 100));
                                    $refund_tax += $gst_val;
                                }
                            }
                        }
                        $refund_tax = round($refund_tax, 2);
                        $refund_amount = round($refund_amount + $refund_tax, 2);
                    } elseif ($service_type == 'rental') {
                        $refund_guide_charge = ($OrderMaster->guide_charge > 0) ? ceil($OrderMaster->guide_charge * ($refund_percent / 100)) : 0;
                        $refund_tax = ceil($OrderMaster->tax_amount * ($refund_percent / 100));
                        $refund_amount = ceil($OrderMaster->sub_total_price * ($refund_percent / 100)) + $refund_tax + $refund_guide_charge;
                    } elseif($service_type == 'hall'){
                        $CancelPolicy = CancelPolicy::where(['vendor_id' => $OrderMaster->vendor_id, 'service_type' => $service_type])
                            ->whereRaw('start < ' . abs($days))
                            ->whereRaw('end >=' . abs($days))
                            ->first();
                            if (!empty($CancelPolicy)) {
                                $refund_percent = $CancelPolicy->percent;
                            }
                            $refund_tax = ceil($OrderMaster->tax_amount * ($refund_percent / 100));
                            $refund_amount = ceil($OrderMaster->sub_total_price * ($refund_percent / 100)) + $refund_tax;
                    }else {
                        $refund_tax = ceil($OrderMaster->tax_amount * ($refund_percent / 100));
                        $refund_amount = ceil($OrderMaster->sub_total_price * ($refund_percent / 100)) + $refund_tax;
                    }
                    $OrderMaster->refund_amount = $refund_amount;
                    $OrderMaster->refund_tax = $refund_tax;
                    $PaymentHistory = PaymentHistory::find($OrderMaster->payment_id);
                    // if ($OrderMaster->order_type == 'online' && !empty($PaymentHistory) && $OrderMaster->payment_gateway == 'paytm' && $refund_amount > 0) {

                    //     require_once public_path('paytm_lib/config_paytm.php');
                    //     require_once public_path('paytm_lib/encdec_paytm.php');

                    //     $paytmParams = array();
                    //     $reference_id = date("dmY") . time();
                    //     $paytmParams["body"] = array(
                    //         "mid"          => PAYTM_MERCHANT_MID,
                    //         "txnType"      => "REFUND",
                    //         "orderId"      => $PaymentHistory->transaction_id,
                    //         "txnId"        => $PaymentHistory->mihpayid,
                    //         "refId"        => $reference_id,
                    //         "refundAmount" => $refund_amount,
                    //     );
                    //     $checksum = generateSign($paytmParams["body"], PAYTM_MERCHANT_KEY);
                    //     $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), PAYTM_MERCHANT_KEY);
                    //     $paytmParams["head"] = array(
                    //         "signature"	  => $checksum
                    //     );
                    //     $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

                    //     $url = "https://securegw-stage.paytm.in/refund/apply";
                    //     if (PAYTM_ENVIRONMENT == 'PROD') {
                    //         $url = "https://securegw.paytm.in/refund/apply";
                    //     }

                    //     $ch = curl_init($url);
                    //     curl_setopt($ch, CURLOPT_POST, 1);
                    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                    //     $responseJson = curl_exec($ch);
                    //     $response = json_decode($responseJson, 1);

                    //     $response_timestamp = $signature = $txn_timestamp = $result_code = $result_msg = $refund_txn_id = $response_json = $refund_status = '';

                    //     $response_timestamp = isset($response['head']['responseTimestamp']) ? $response['head']['responseTimestamp'] : '';
                    //     $signature = isset($response['head']['signature']) ? $response['head']['signature'] : '';
                    //     $txn_timestamp = isset($response['body']['txnTimestamp']) ? date("Y-m-d H:i:s", strtotime($response['body']['txnTimestamp'])) : '';
                    //     $result_code = isset($response['body']['resultInfo']['resultCode']) ? $response['body']['resultInfo']['resultCode'] : '';
                    //     $result_msg = isset($response['body']['resultInfo']['resultMsg']) ? $response['body']['resultInfo']['resultMsg'] : '';
                    //     $refund_status = isset($response['body']['resultInfo']['resultStatus']) ? $response['body']['resultInfo']['resultStatus'] : '';
                    //     $refund_txn_id = isset($response['body']['refundId']) ? $response['body']['refundId'] : '';
                    //     $response_json = $responseJson;

                    //     $RefundData = new CustomerRefund([
                    //         'vendor_id' => $OrderMaster->vendor_id,
                    //         'order_id' => $request->orderId,
                    //         'invoice_id' => $OrderMaster->invoice_id,
                    //         'order_type' => $OrderMaster->order_type,
                    //         'service_type' => $service_type,
                    //         'customer_id' => $OrderMaster->customer_id,
                    //         'order_date' => $OrderMaster->created_at,
                    //         'cancel_date' => date("Y-m-d"),
                    //         'paid_amount' => $OrderMaster->total_order_price,
                    //         'refund_amount' => $refund_amount,
                    //         'refund_percent' => $refund_percent,
                    //         'payment_method' => $OrderMaster->payment_gateway,
                    //         'response_timestamp' => $response_timestamp,
                    //         'signature' => $signature,
                    //         'txn_timestamp' => $txn_timestamp,
                    //         'result_code' => $result_code,
                    //         'result_msg' => $result_msg,
                    //         'refund_txn_id' => $refund_txn_id,
                    //         'response_json' => $response_json,
                    //         'refund_status' => $refund_status,
                    //         'client_txn_id' => $PaymentHistory->transaction_id,
                    //         'pg_txn_id' => $PaymentHistory->mihpayid,
                    //         'reference_id' => $reference_id,
                    //         'payment_id' => $PaymentHistory->id
                    //     ]);
                    // }
                    $s_type = ($OrderMaster->service_type == 'car') ? 'rental' : $OrderMaster->service_type;
                    if (!empty($PaymentHistory) && $OrderMaster->payment_gateway == 'hdfc' && $refund_amount > 0) {
                        require_once public_path('paytm_lib/config_paytm.php');

                        if (!empty($OrderMaster->hdfc_key) && !empty($OrderMaster->hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                            $HDFC_KEY = $OrderMaster->hdfc_key;
                            $HDFC_SALT = $OrderMaster->hdfc_salt;
                        }

                        $command = "cancel_refund_transaction";
                        $var1 = $PaymentHistory->mihpayid;                  //mihpayid
                        $reference_id = $var2 = date('dmY') . time();       //request id
                        $var3 = $refund_amount;                             //amount

                        $key = $HDFC_KEY;
                        $salt = $HDFC_SALT;

                        $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
                        $hash = strtolower(hash('sha512', $hash_str));

                        $r = array('key' => $key, 'hash' => $hash, 'command' => $command, 'var1' => $var1, 'var2' => $var2, 'var3' => $var3);
                        $qs = http_build_query($r);
                        $wsUrl = VERIFY_URL; //"https://test.payu.in/merchant/postservice.php?form=2";

                        $c = curl_init();
                        curl_setopt($c, CURLOPT_URL, $wsUrl);
                        curl_setopt($c, CURLOPT_POST, 1);
                        curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                        $o = curl_exec($c);
                        // if (curl_errno($c)) {
                        //     $sad = curl_error($c);
                        //     throw new Exception($sad);
                        // }
                        curl_close($c);
                        $valueSerialized = @unserialize($o);
                        $response = json_decode($o, 1);

                        if (isset($response['status']) && $response['status'] != 1) {
                            $responce['status'] = 0;
                            $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                            echo json_encode($responce);
                            exit;
                        }

                        $result_msg = $response['msg'];
                        $refund_txn_id = isset($response['bank_ref_num']) ? $response['bank_ref_num'] : '';
                        $rquest_id = isset($response['request_id']) ? $response['request_id'] : '';

                        $RefundData = new CustomerRefund([
                            'vendor_id' => $OrderMaster->vendor_id,
                            'order_id' => $request->orderId,
                            'invoice_id' => $OrderMaster->invoice_id,
                            'order_type' => $OrderMaster->order_type,
                            'service_type' => $s_type,
                            'service_id' => $OrderMaster->service_name_id,
                            'customer_id' => $OrderMaster->customer_id,
                            'order_date' => $OrderMaster->created_at,
                            'cancel_date' => date("Y-m-d", strtotime($Today)),
                            'paid_amount' => $OrderMaster->total_order_price,
                            'refund_amount' => $refund_amount,
                            'refund_percent' => $refund_percent,
                            'payment_method' => $OrderMaster->payment_gateway,
                            'client_txn_id' => $PaymentHistory->transaction_id,
                            'pg_txn_id' => $PaymentHistory->mihpayid,
                            'refund_status' => 'PENDING',
                            'result_msg' => $result_msg,
                            'reference_id' => $rquest_id,
                            'refund_txn_id' => $refund_txn_id,
                            'response_json' => json_encode($response),
                            'payment_id' => $PaymentHistory->id
                        ]);
                        $RefundData->save();
                        // if ($response['status'] != 1) {
                        //     $responce['status'] = 0;
                        //     $responce['message'] = $result_msg;
                        //     echo json_encode($responce);
                        //     exit;
                        // }
                    } else {
                        $RefundData = new CustomerRefund([
                            'vendor_id' => $OrderMaster->vendor_id,
                            'order_id' => $request->orderId,
                            'invoice_id' => $OrderMaster->invoice_id,
                            'order_type' => $OrderMaster->order_type,
                            'service_type' => $s_type,
                            'service_id' => $OrderMaster->service_name_id,
                            'customer_id' => $OrderMaster->customer_id,
                            'order_date' => $OrderMaster->created_at,
                            'cancel_date' => date("Y-m-d", strtotime($Today)),
                            'paid_amount' => $OrderMaster->total_order_price,
                            'refund_amount' => $refund_amount,
                            'refund_percent' => $refund_percent,
                            'payment_method' => $OrderMaster->payment_gateway,
                            'refund_status' => 'success'
                        ]);
                        $RefundData->save();
                    }
                    $OrderMaster->save();
                    $Subject = '';
                    $Message = "<p style='color:#000000;'>Dear " . $OrderMaster->customer_name . ",</p>";
                    $Message .= "<p style='color:#000000;'>Your reservation booking for " . $OrderMaster->service_name . " having invoice no " . $OrderMaster->invoice_id . " has been cancelled.</p>";
                    $Message .= "<p style='color:#000000;'>An amount of &#8377;" . number_format($refund_amount, 2) . " will be refunded soon.</p>";
                    $Vendor = User::find($OrderMaster->vendor_id);
                    $To = $OrderMaster->customer_email;
                    $service_email = '';
                    $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? $OrderMaster->gst_regd_no : 'N/A';

                    $User = User::find($OrderMaster->customer_id);
                    $mobileNumber = $OrderMaster->customer_phone;

                    if ($service_type == 'hotel') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'asc')->get();
                        $MasterHotel = MasterHotel::find($OrderMaster->service_name_id);
                        foreach ($OrderDetails as $value) {
                            $HotelRoom = HotelRoom::find($value->service_item_id);
                            $MasterInventory = MasterInventory::where(["date" => $value->start_date, 'room_id' => $value->service_item_id])->first();
                            if (!empty($MasterInventory)) {
                                if ($OrderMaster->book_from == 'blocked') {
                                    $MasterInventory->total_blocked += 1;
                                    $MasterInventory->total_booked -= 1;
                                    if ($OrderMaster->payment_status == 'success') {
                                        // if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= 1;
                                        // } else {
                                        //     $MasterInventory->total_online_completed -= 1;
                                        // }
                                    } else {
                                        // if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= 1;
                                        // } else {
                                        //     $MasterInventory->total_online_pending -= 1;
                                        // }
                                    }
                                } else {
                                    $MasterInventory->total_available += 1;
                                    $MasterInventory->total_booked -= 1;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type != 'online' || ($OrderMaster->order_type == 'online' && !empty($OrderMaster->offline_long_url))) {
                                            $MasterInventory->total_offline_completed -= 1;
                                        } else {
                                            $MasterInventory->total_online_completed -= 1;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type != 'online' || ($OrderMaster->order_type == 'online' && !empty($OrderMaster->offline_long_url))) {
                                            $MasterInventory->total_offline_pending -= 1;
                                        } else {
                                            $MasterInventory->total_online_pending -= 1;
                                        }
                                    }
                                }
                                $MasterInventory->save();
                            }
                            OrderDetail::find($value->id)->update(['status' => 'cancelled']);
                        }

                        $days = 0;
                        if ($OrderMaster->start_date == $OrderMaster->end_date) {
                            $days = 1;
                        } else {
                            $difference = strtotime($OrderMaster->end_date) - strtotime($OrderMaster->start_date);
                            $days = round($difference / (60 * 60 * 24));
                        }
                        $RoomDetails = json_decode($OrderMaster->room_details, 1);
                        foreach ($RoomDetails as $roomId => $room) {
                            $HotelRoom = HotelRoom::find($roomId);
                            for($i = 0; $i < $days; $i++) {
                                $checkInDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . $i . ' days'));
                                $MasterInventory = MasterInventory::where(['hotel_id' => $OrderMaster->service_name_id,'room_id' => $roomId,'date' => $checkInDate])->first();
                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $checkInDate])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && !empty($MasterInventory) && $OrderMaster->book_from != 'blocked') {
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                    <AvailRateUpdate locatorID="1">
                                                        <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                        <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                    </AvailRateUpdate>
                                                </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && !empty($MasterInventory) && $OrderMaster->book_from != 'blocked') {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                    ->where('block_date', $MasterInventory->date)
                                                    ->where('rooms', $MasterInventory->room_id)
                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                    ->get()->toArray();
                                    if (!empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        // Availability
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                        }
                        // parent::updateMmtInventory($OrderMaster->vendor_id, $OrderMaster->service_name_id);

                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $room_html = '';
                        $room_details = json_decode($OrderMaster->room_details, 1);
                        foreach ($room_details as $value) {
                            $room_html .= $value['quantity'] . ' ' . $value['room_name'] . ', ';
                        }
                        $room_html = trim($room_html, ', ');
                        $HotelInvoice = EmailTemplate::where('ref_code', 'hotelCancelInvoice')->first();
                        $vendorGSTNo = (!empty($MasterHotel->gst_number)) ? $MasterHotel->gst_number : 'N/A';

                        $customer_address = $OrderMaster->customer_address1;
                        $customer_address .= !empty($OrderMaster->customer_city) ? ',<br>'. $OrderMaster->customer_city : '';
                        $customer_address .= !empty($OrderMaster->customer_state) ? ',<br>'. $OrderMaster->customer_state : '';
                        $customer_address .= !empty($OrderMaster->customer_country) ? ',<br>'. $OrderMaster->customer_country : '';
                        $customer_address .= !empty($OrderMaster->customer_zipcode) ? ', '. $OrderMaster->customer_zipcode : '';
                        $customer_address .= (!empty($OrderMaster->gst_regd_no)) ? '<br><u><b>GSTN No: ' . $OrderMaster->gst_regd_no . '</b></u>' : '';
                        $customer_address .= (!empty($OrderMaster->gst_company_name)) ? '<br><u><b>Company Name: ' . $OrderMaster->gst_company_name . '</b></u>' : '';

                        $Subject = $HotelInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~hoteladdress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~hotelname~", "~roomdetails~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~hotelemail~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~hotelgst~", "~usergst~", "~invoiceserial~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $customer_address, $MasterHotel->real_address, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $room_html, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $MasterHotel->contact_email, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo, $OrderMaster->invoice_serial),
                            $HotelInvoice->source
                        );
                        if ($OrderMaster->vendor_id != 3) {
                            $service_email = $MasterHotel->contact_email;
                            if (!empty($MasterHotel->additional_email)) {
                                $service_email = !empty($service_email) ? $service_email .','. $MasterHotel->additional_email : $MasterHotel->additional_email;
                            }
                        }

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'hotelCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~hoteladdress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~hotelname~", "~roomdetails~", "~checkdate~", "~hotelemail~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $MasterHotel->real_address, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $room_html, $check_date, $MasterHotel->contact_email, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'rental') {
                        $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                        foreach ($OrderDetails as $route) {
                            $difference = strtotime(date("Y-m-d", strtotime($route->end_date))) - strtotime(date("Y-m-d", strtotime($route->start_date)));
                            $days = floor($difference / (60 * 60 * 24));
                            $cal_day = ($days == 0) ? 1 : $days + 1;
                            for ($i = 0; $i < $cal_day; $i++) {
                                $checkDate = date("Y-m-d", strtotime($route->start_date . ' + ' . $i . ' days'));
                                $MasterInventory = RentalMasterInventory::where(["date" => $checkDate, "car_id" => $route->service_name_id])->first();
                                if (!empty($MasterInventory)) {
                                    $MasterInventory->total_available += 1;
                                    $MasterInventory->total_booked -= 1;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= 1;
                                        } else {
                                            $MasterInventory->total_online_completed -= 1;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= 1;
                                        } else {
                                            $MasterInventory->total_online_pending -= 1;
                                        }
                                    }
                                    $MasterInventory->save();
                                }
                            }
                            OrderDetail::find($route->id)->update(['status' => 'cancelled']);
                        }

                        $MasterCar = MasterCar::find($OrderMaster->service_name_id);
                        $vendorGSTNo = (!empty($MasterCar->gst_number)) ? $MasterCar->gst_number : 'N/A';

                        $service_email = $MasterCar->contact_email;
                        if (!empty($MasterCar->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $MasterCar->additional_email : $MasterCar->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $RentalInvoice = EmailTemplate::where('ref_code', 'rentalCancelInvoice')->first();
                        $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~vendorgst~", "~usergst~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo),
                            $RentalInvoice->source
                        );

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'rentalCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'package') {
                        // $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->orderBy('start_date', 'asc')->get();
                        $OrderDetails = OrderDetail::select('service_name_id', 'service_item_id', 'start_date', DB::raw('SUM(service_item_quantity)as totQty'))
                                        ->where('order_master_id', $OrderMaster->id)
                                        ->groupBy('start_date')
                                        ->orderBy('start_date', 'asc')->get();
                        OrderDetail::where('order_master_id', $OrderMaster->id)->update(['status' => 'cancelled']);
                        foreach ($OrderDetails as $value) {
                            $rQty = (int)$value->totQty;
                            $MasterInventory = MasterInventory::where(["date" => $value->start_date, 'room_id' => $value->service_item_id])->first();
                            if (!empty($MasterInventory)) {
                                if ($OrderMaster->book_from == 'blocked') {
                                    $MasterInventory->total_blocked += $rQty;
                                    $MasterInventory->total_booked -= $rQty;
                                    $MasterInventory->total_tour_booking -= $rQty;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_completed -= $rQty;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_pending -= $rQty;
                                        }
                                    }
                                } else {
                                    $MasterInventory->total_available += $rQty;
                                    $MasterInventory->total_booked -= $rQty;
                                    $MasterInventory->total_tour_booking -= $rQty;
                                    if ($OrderMaster->payment_status == 'success') {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_completed -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_completed -= $rQty;
                                        }
                                    } else {
                                        if ($OrderMaster->order_type == 'offline') {
                                            $MasterInventory->total_offline_pending -= $rQty;
                                        } else {
                                            $MasterInventory->total_online_pending -= $rQty;
                                        }
                                    }
                                }
                                $MasterInventory->save();

                                $MasterHotel = MasterHotel::find($value->service_name_id);
                                $HotelRoom = HotelRoom::find($value->service_item_id);
                                $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                ->first();
                                if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id) && $OrderMaster->book_from != 'blocked') {

                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                ->where('block_date', $MasterInventory->date)
                                                ->where('rooms', $MasterInventory->room_id)
                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                ->get()->toArray();
                                    $closed = 'false';
                                    if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                        $closed = "true";
                                    }
                                    $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                    <AvailRateUpdate locatorID="1">
                                                        <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                        <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                    </AvailRateUpdate>
                                                </AvailRateUpdateRQ>';
                                    DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                }
                                if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id) && $OrderMaster->book_from != 'blocked') {
                                    $closed = "Open";
                                    $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                    ->where('block_date', $MasterInventory->date)
                                                    ->where('rooms', $MasterInventory->room_id)
                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                    ->get()->toArray();
                                    if (!empty($BlockedMmt)) {
                                        $closed = "Close";
                                    }
                                    $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                    if (!empty($InvRateplanData)) {
                                        // Availability
                                        $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                    <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                        <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                            <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                            <RestrictionStatus Status="'. $closed .'" />
                                                        </AvailStatusMessage>
                                                    </AvailStatusMessages>
                                                </OTA_HotelAvailNotifRQ>';
                                        DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('cancel_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                    }
                                }
                            }
                        }
                        // parent::updateMmtInventory($OrderMaster->vendor_id, $OrderMaster->service_name_id);

                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $service_email = $Tour->contact_email;
                        if (!empty($Tour->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Tour->additional_email : $Tour->additional_email;
                        }
                        $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $PackageInvoice = EmailTemplate::where('ref_code', 'packageCancelInvoice')->first();
                        $Subject = $PackageInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $vendorGSTNo = (!empty($Tour->gst_number)) ? $Tour->gst_number : 'N/A';
                        $Message .= str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"), array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id), $PackageInvoice->source);

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'packageCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~ticketquantity~", "~canceldate~"), array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $guest_data, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))), $CustomerInvoice->source);
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'sight-seeing') {
                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $service_email = $Tour->contact_email;
                        if (!empty($Tour->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Tour->additional_email : $Tour->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        $SightseenInvoice = EmailTemplate::where('ref_code', 'sightseenCancelInvoice')->first();
                        $Subject = $SightseenInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $vendorGSTNo = (!empty($Tour->gst_number)) ? $Tour->gst_number : 'N/A';
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $SightseenInvoice->source
                        );

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'sightseenCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~ticketquantity~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $OrderMaster->total_guests, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    } elseif ($service_type == 'experience-ticketing' || $service_type == 'events' || $service_type == 'entry-ticket') {
                        $Ticket = Ticket::find($OrderMaster->service_name_id);
                        $service_email = $Ticket->contact_email;
                        if (!empty($Ticket->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Ticket->additional_email : $Ticket->additional_email;
                        }
                        $TicketInvoice = EmailTemplate::where('ref_code', 'ticketCancelInvoice')->first();
                        $Subject = $TicketInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $duration = (!empty($OrderMaster->start_time)) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All Day';
                        $vendorGSTNo = (!empty($Ticket->gst_number)) ? $Ticket->gst_number : 'N/A';

                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), ($OrderMaster->service_name_id == '24') ? '6<sup>th</sup> ' . $OrderMaster->service_name : $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $TicketInvoice->source
                        );

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'ticketCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~duration~", "~ticketquantity~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $duration, $guest_data, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                        if (Auth::user()->user_role == 'agent_staff') {
                            $Message = str_replace('Disclaimer: This is an electronically generated invoice, hence does not require a signature.', 'Naration: '. $OrderMaster->book_naration, $Message);
                        }
                    }elseif($service_type == 'hall'){
                        $Hall = Hall::find($OrderMaster->service_name_id);
                        $Property = HallProperty::find($Hall->property_id);
                        $service_email = $Property->contact_email;
                        if (!empty($Property->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Property->additional_email : $Property->additional_email;
                        }
                        $TicketInvoice = EmailTemplate::where('ref_code', 'hallCancelInvoice')->first();
                        $Subject = $TicketInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        //$guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $duration = (!empty($OrderMaster->start_time)) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All Day';
                        $vendorGSTNo = (!empty($Property->gst_number)) ? $Property->gst_number : 'N/A';

                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), ($OrderMaster->service_name_id == '24') ? '6<sup>th</sup> ' . $OrderMaster->service_name : $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $TicketInvoice->source
                        );
                    }
                    OrderMaster::find($OrderMaster->id)->update(['cancel_voucher' => $Message]);
                    if (!empty($User) && $User->access_type == 'agent') {
                        $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancelAgent')->first();
                        if (!empty($SmsTemplate)) {
                            $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($OrderMaster->customer_name . ',', $OrderMaster->service_name, $OrderMaster->invoice_id, "\n", $OrderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
                            parent::sendSms($OrderMaster->customer_phone, $sms_txt, $SmsTemplate->templete_id);
                            $mobileNumber = $User->phone;
                        }
                    }
                    $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancel')->first();
                    if (!empty($SmsTemplate)) {
                        $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($OrderMaster->customer_name . ',', $OrderMaster->service_name, $OrderMaster->invoice_id, number_format($refund_amount, 2), "\n", $OrderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
                        parent::sendSms($mobileNumber, $sms_txt, $SmsTemplate->templete_id);
                    }
                    $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

                    $Vendor = User::find($OrderMaster->vendor_id);
                    $admin = User::where('role', 1)->first();
                    $bcc = [$Vendor->email, $admin->email];
                    if (!empty($service_email)) {
                        $bcc = array_merge($bcc, explode(',', $service_email));
                    }
                    try {
                        Mail::to($To)
                            ->bcc($bcc)
                            ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
                    }
                    catch(\Exception $e) {}

                    $responce['status'] = 1;
                    $responce['message'] = 'Booking cancelled successfully.';
                }
            }
        } elseif ($request->request_type == 'export_hotel_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/hotel_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Booking Id', 'Transaction Id', 'PayU Id', 'Invoice Serial', 'Order Type', 'Book Though', 'Customer Name', 'Customer Email', 'Customer Phone', 'Senior Citizen', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Customer GST No', 'Customer GST Company Name', 'Customer GST Company Address', 'Agent Name', 'Hotel Name', 'Selected Rooms', 'Total Rooms', 'Booking Date', 'Booking Time', 'Check-In', 'Check-Out', 'Expected Arrival Time', 'Need Pickup', 'Adult', 'Child', 'No of Nights', 'Total room Nights', 'Gross price', 'Discount', 'Coupon Name', 'Coupon Code', 'Sub Total Price', 'CGST', 'SGST', 'Service Charge', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $payuId = 'N/A';
                    if (!empty($value->payment_id)) {
                        $PaymentHistory = PaymentHistory::find($value->payment_id);
                        $payuId = (!empty($PaymentHistory) && !empty($PaymentHistory->mihpayid)) ? $PaymentHistory->mihpayid : 'N/A';
                    }

                    $UserData = User::find($value->customer_id);
                    $agent_name = '';
                    if (!empty($UserData) && $UserData->access_type == 'agent') {
                        $agent_name = $UserData->first_name . ' ' . $UserData->last_name;
                    }
                    $difference = strtotime($value->end_date) - strtotime($value->start_date);
                    $days = round($difference / (60 * 60 * 24));
                    $night = ($days == 0) ? 1 : $days;
                    if (Auth::user()->access_type == 'superadmin') {
                        $data['vendor'] = $value->vendor_name;
                    }
                    $rooms = json_decode($value->room_details, 1);
                    $html = '';
                    foreach ($rooms as $room) {
                        $html .= $room['quantity'] . ' ' . $room['room_name'] . ',';
                    }
                    $html = trim($html, ', ');
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;
                    $data['mihpayid'] = $payuId;
                    $data['invoice_serial'] = !empty($value->invoice_serial) ? $value->invoice_serial ."\t" : 'N/A';
                    $data['order_type'] = $value->order_type;
                    $data['request_from'] = $value->request_from;

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['sr_citizen'] = ($value->senior_citizen == 1) ? 'Yes' : '';
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;
                    $data['gst_regd_no'] = $value->gst_regd_no;
                    $data['gst_company_name'] = $value->gst_company_name;
                    $data['gst_company_address'] = $value->gst_company_address;
                    $data['agent_name'] = $agent_name;

                    $data['service_name'] = $value->service_name;
                    $data['Rooms'] = '[' . $html . ']';
                    $data['total_rooms'] = $value->total_rooms;

                    $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_time'] = date("h:i a", strtotime($value->created_at));
                    $data['start_date'] = date("Y-m-d", strtotime($value->start_date));
                    $data['end_date'] = date("Y-m-d", strtotime($value->end_date));
                    $data['expected_arrival'] = !empty($value->expected_arrival_time) ? $value->expected_arrival_time : 'N/A';
                    $data['need_pickup'] = !empty($value->need_pickup) ? $value->need_pickup : 'No';
                    $data['total_adults'] = $value->total_adults;
                    $data['total_child'] = $value->total_child;
                    $data['Nights'] = $night;
                    $data['Total_room_Nights'] = $value->total_rooms * $night;

                    $data['total_service_price'] = $value->total_service_price;
                    $data['coupon_amount'] = $value->coupon_amount;
                    $data['coupon_name'] = $value->coupon_name;
                    $data['coupon_code'] = $value->coupon_code;
                    $data['sub_total_price'] = $value->sub_total_price;
                    $data['cgst'] = round($value->tax_amount / 2, 2);
                    $data['sgst'] = round($value->tax_amount / 2, 2);
                    $data['service_charge'] = $value->service_charge;
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_method;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_hotel_detailed_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/hotel_order_detailed_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Booking Id', 'Transaction Id', 'Order Type', "Book Through", 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Customer GST No', 'Customer GST Company Name', 'Customer GST Company Address', 'Agent Name', 'Hotel Name', 'Selected Rooms', 'Total Rooms', 'Booking Date', 'Booking Time', 'Check-In', 'Check-Out', 'Expected Arrival Time', 'Need Pickup', 'Adult', 'Child', 'No of Nights', 'Total room Nights', 'Gross price', 'Discount', 'Coupon Name', 'Sub Total Price', 'CGST', 'SGST', 'Service Charge', 'Total Price', 'Status', 'Payment Method', 'Payment Status', 'Room Name', 'Adult', 'Child', 'Extra Bed', 'Room Price', 'Extra Bed Price', 'Gross Price', 'CGST', 'SGST', 'Total Room Price');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $UserData = User::find($value->customer_id);
                    $agent_name = '';
                    if (!empty($UserData) && $UserData->access_type == 'agent') {
                        $agent_name = $UserData->first_name . ' ' . $UserData->last_name;
                    }
                    $difference = strtotime($value->end_date) - strtotime($value->start_date);
                    $days = round($difference / (60 * 60 * 24));
                    $night = ($days == 0) ? 1 : $days;

                    $OrderDeatils = DB::select('SELECT service_item_name, total_adult, total_child, extra_bed, sum(unit_total_price - (extra_bed * extra_bed_price))AS roomPrice, SUM(extra_bed * extra_bed_price)AS extraPrice, SUM(tax_amount) AS totTax, SUM(total_room_price)AS totPrice FROM `order_details` WHERE `order_master_id` = "' . $value->id . '" GROUP BY room_number');
                    foreach ($OrderDeatils as $detail) {

                        if (Auth::user()->access_type == 'superadmin') {
                            $data['vendor'] = $value->vendor_name;
                        }
                        $rooms = json_decode($value->room_details, 1);
                        $html = '';
                        foreach ($rooms as $room) {
                            $html .= $room['quantity'] . ' ' . $room['room_name'] . ',';
                        }
                        $html = trim($html, ', ');
                        $data['invoice_id'] = $value->invoice_id;
                        $data['transaction_id'] = $value->transaction_id;
                        $data['order_type'] = $value->order_type;
                        $data['request_from'] = $value->request_from;

                        $data['customer_name'] = $value->customer_name;
                        $data['customer_email'] = $value->customer_email;
                        $data['customer_phone'] = $value->customer_phone;
                        $data['customer_address1'] = $value->customer_address1;
                        $data['customer_city'] = $value->customer_city;
                        $data['customer_state'] = $value->customer_state;
                        $data['customer_country'] = $value->customer_country;
                        $data['customer_zipcode'] = $value->customer_zipcode;
                        $data['gst_regd_no'] = $value->gst_regd_no;
                        $data['gst_company_name'] = $value->gst_company_name;
                        $data['gst_company_address'] = $value->gst_company_address;
                        $data['agent_name'] = $agent_name;

                        $data['service_name'] = $value->service_name;
                        $data['Rooms'] = '[' . $html . ']';
                        $data['total_rooms'] = $value->total_rooms;

                        $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                        $data['created_time'] = date("h:i a", strtotime($value->created_at));
                        $data['start_date'] = date("Y-m-d", strtotime($value->start_date));
                        $data['end_date'] = date("Y-m-d", strtotime($value->end_date));
                        $data['expected_arrival'] = !empty($value->expected_arrival_time) ? $value->expected_arrival_time : 'N/A';
                        $data['need_pickup'] = !empty($value->need_pickup) ? $value->need_pickup : 'No';
                        $data['total_adults'] = $value->total_adults;
                        $data['total_child'] = $value->total_child;
                        $data['Nights'] = $night;
                        $data['Total_room_Nights'] = $value->total_rooms * $night;

                        $data['total_service_price'] = $value->total_service_price;
                        $data['coupon_amount'] = $value->coupon_amount;
                        $data['coupon_name'] = $value->coupon_name;
                        $data['sub_total_price'] = $value->sub_total_price;
                        $data['cgst'] = round($value->tax_amount / 2, 2);
                        $data['sgst'] = round($value->tax_amount / 2, 2);
                        $data['service_charge'] = $value->service_charge;
                        $data['total_order_price'] = $value->total_order_price;
                        $data['status'] = $value->status;
                        $data['payment_gateway'] = $value->payment_method;
                        $data['payment_status'] = $value->payment_status;
                        $data['service_item_name'] = $detail->service_item_name;
                        $data['room_adult'] = $detail->total_adult;
                        $data['room_child'] = $detail->total_child;
                        $data['extra_bed'] = $detail->extra_bed;
                        $data['roomPrice'] = round($detail->roomPrice, 2);
                        $data['extraPrice'] = round($detail->extraPrice, 2);
                        $data['gross'] = round($detail->roomPrice + $detail->extraPrice, 2);
                        $data['cGst'] = round($detail->totTax / 2, 2);
                        $data['sGst'] = round($detail->totTax / 2, 2);
                        $data['totPrice'] = round($detail->totPrice, 2);
                        fputcsv($fp, $data);
                    }
                    fputcsv($fp, array());
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_rental_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/rental_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'PayU Id', 'Order Type', 'Book Though', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Customer GST No', 'Customer GST Company Name', 'Customer GST Company Address', 'Agent Name', 'Mode of Transport', 'quantity', 'Booking Date', 'Booking Time', 'Start Date', 'Start Time', 'End Date', 'End Time', 'Distance', 'Day Breakup', 'Breakup Details', 'Guide Service', 'Gross price', 'Discount', 'Coupon Name', 'Sub Total Price', 'CGST', 'SGST', 'Guide Charge', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $payuId = 'N/A';
                    if (!empty($value->payment_id)) {
                        $PaymentHistory = PaymentHistory::find($value->payment_id);
                        $payuId = (!empty($PaymentHistory) && !empty($PaymentHistory->mihpayid)) ? $PaymentHistory->mihpayid : 'N/A';
                    }
                    $UserData = User::find($value->customer_id);
                    $agent_name = '';
                    if (!empty($UserData) && $UserData->access_type == 'agent') {
                        $agent_name = $UserData->first_name . ' ' . $UserData->last_name;
                    }
                    $breakUp = json_decode($value->room_request, 1);
                    $breakHtml = '';
                    foreach ($breakUp as $val) {
                        $breakHtml .= date("Y-m-d h:i a", strtotime($val['date']['startDate'])) . '(' . $val['pickup_city'] . ') - ' . date("Y-m-d h:i a", strtotime($val['date']['endDate'])) . '(' . $val['drop_city'] . '), ';
                    }
                    $breakHtml = rtrim($breakHtml, ', ');
                    if (Auth::user()->access_type == 'superadmin') {
                        $data['vendor'] = $value->vendor_name;
                    }
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;
                    $data['mihpayid'] = $payuId;
                    $data['order_type'] = $value->order_type;
                    $data['request_from'] = $value->request_from;

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;
                    $data['gst_regd_no'] = $value->gst_regd_no;
                    $data['gst_company_name'] = $value->gst_company_name;
                    $data['gst_company_address'] = $value->gst_company_address;
                    $data['agent_name'] = $agent_name;

                    $data['service_name'] = $value->service_name;
                    $data['service_quantity'] = $value->service_quantity;
                    $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_time'] = date("h:i a", strtotime($value->created_at));
                    $data['start_date'] = $value->start_date;
                    $data['start_time'] = $value->start_time;
                    $data['end_date'] = $value->end_date;
                    $data['end_time'] = $value->end_time;
                    $data['travel_distance'] = $value->travel_distance;
                    $data['day_break'] = count($breakUp);
                    $data['breakup_details'] = '[' . $breakHtml . ']';
                    $data['guide_service'] = ($value->days_for_guide > 0) ? $value->days_for_guide : 'N/A';

                    $data['total_service_price'] = $value->total_service_price;
                    $data['coupon_amount'] = $value->coupon_amount;
                    $data['coupon_name'] = $value->coupon_name;
                    $data['sub_total_price'] = $value->sub_total_price;
                    $data['cgst'] = round($value->tax_amount / 2, 2);
                    $data['sgst'] = round($value->tax_amount / 2, 2);
                    $data['guide_charge'] = $value->guide_charge;
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_method;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_ticket_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/rental_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'PayU Id', 'Order Type', 'Book Though', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Customer GST No', 'Customer GST Company Name', 'Customer GST Company Address', 'Agent Name', 'Ticket Type', 'Ticket Name', 'Booking Date', 'Booking Time', 'Ticket Date', 'Duration', 'Adult', 'Child', 'Gross price', 'Discount', 'Coupon Name', 'Sub Total Price', 'CGST', 'SGST', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $payuId = 'N/A';
                    if (!empty($value->payment_id)) {
                        $PaymentHistory = PaymentHistory::find($value->payment_id);
                        $payuId = (!empty($PaymentHistory) && !empty($PaymentHistory->mihpayid)) ? $PaymentHistory->mihpayid : 'N/A';
                    }
                    $UserData = User::find($value->customer_id);
                    $agent_name = '';
                    if (!empty($UserData) && $UserData->access_type == 'agent') {
                        $agent_name = $UserData->first_name . ' ' . $UserData->last_name;
                    }
                    if (Auth::user()->access_type == 'superadmin') {
                        $data['vendor'] = $value->vendor_name;
                    }
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;
                    $data['mihpayid'] = $payuId;
                    $data['order_type'] = $value->order_type;
                    $data['request_from'] = $value->request_from;

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;
                    $data['gst_regd_no'] = $value->gst_regd_no;
                    $data['gst_company_name'] = $value->gst_company_name;
                    $data['gst_company_address'] = $value->gst_company_address;
                    $data['agent_name'] = $agent_name;

                    $data['service_category'] = $value->service_category;
                    $data['service_name'] = $value->service_name;
                    $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_at'] = date("h:i a", strtotime($value->created_at));
                    $data['start_date'] = date("Y-m-d", strtotime($value->start_date));
                    $data['duration'] = !empty($value->start_time) ? '[' . $value->start_time . ' - ' . $value->end_time . ']' : 'Full day';
                    $data['total_adults'] = $value->total_adults;
                    $data['total_child'] = $value->total_child;

                    $data['total_service_price'] = $value->total_service_price;
                    $data['coupon_amount'] = $value->coupon_amount;
                    $data['coupon_name'] = $value->coupon_name;
                    $data['sub_total_price'] = $value->sub_total_price;
                    $data['cgst'] = round($value->tax_amount / 2, 2);
                    $data['sgst'] = round($value->tax_amount / 2, 2);
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_method;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_tour_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/rental_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'PayU Id', 'Order Type', 'Book Though', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address', 'City', 'State', 'Country', 'Zipcode', 'Customer GST No', 'Customer GST Company Name', 'Customer GST Company Address', 'Agent Name', 'Tour Type', 'Tour Name', 'Booking Date', 'Booking Time', 'Start Date', 'End Date', 'Adult', 'Child', 'Gross price', 'Discount', 'Coupon Name', 'Sub Total Price', 'CGST', 'SGST', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $payuId = 'N/A';
                    if (!empty($value->payment_id)) {
                        $PaymentHistory = PaymentHistory::find($value->payment_id);
                        $payuId = (!empty($PaymentHistory) && !empty($PaymentHistory->mihpayid)) ? $PaymentHistory->mihpayid : 'N/A';
                    }
                    $UserData = User::find($value->customer_id);
                    $agent_name = '';
                    if (!empty($UserData) && $UserData->access_type == 'agent') {
                        $agent_name = $UserData->first_name . ' ' . $UserData->last_name;
                    }
                    if (Auth::user()->access_type == 'superadmin') {
                        $data['vendor'] = $value->vendor_name;
                    }
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;
                    $data['mihpayid'] = $payuId;
                    $data['order_type'] = $value->order_type;
                    $data['request_from'] = $value->request_from;

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;
                    $data['gst_regd_no'] = $value->gst_regd_no;
                    $data['gst_company_name'] = $value->gst_company_name;
                    $data['gst_company_address'] = $value->gst_company_address;
                    $data['agent_name'] = $agent_name;

                    $data['service_category'] = $value->service_category;
                    $data['service_name'] = $value->service_name;
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_date'] = date("h:i a", strtotime($value->created_at));
                    $data['start_date'] = $value->start_date;
                    $data['end_date'] = $value->end_date;
                    $data['total_adults'] = $value->total_adults;
                    $data['total_child'] = $value->total_child;

                    $data['total_service_price'] = $value->total_service_price;
                    $data['coupon_amount'] = $value->coupon_amount;
                    $data['coupon_name'] = $value->coupon_name;
                    $data['sub_total_price'] = $value->sub_total_price;
                    $data['cgst'] = round($value->tax_amount / 2, 2);
                    $data['sgst'] = round($value->tax_amount / 2, 2);
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_method;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_food_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/food_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Food Supplier', 'Booking Date', 'Booking Time', 'Gross price', 'Discount', 'Sub Total Price', 'CGST', 'SGST', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    if (Auth::user()->access_type == 'superadmin') {
                        $data['vendor'] = $value->vendor_name;
                    }
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;
                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;

                    $data['service_name'] = $value->service_name;
                    $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_time'] = date("h:i a", strtotime($value->created_at));

                    $data['total_service_price'] = $value->total_service_price;
                    $data['coupon_amount'] = $value->coupon_amount;
                    $data['sub_total_price'] = $value->sub_total_price;
                    $data['cgst'] = round($value->tax_amount / 2,  2);
                    $data['sgst'] = round($value->tax_amount / 2,  2);
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_gateway;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_food_detailed_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/food_order_detailed_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Food Supplier', 'Booking Date', 'Booking Time', 'Gross price', 'Discount', 'Sub Total Price', 'CGST', 'SGST', 'Total Price', 'Status', 'Payment Method', 'Payment Status', 'Item Name', 'Quantity', 'Unit Price', 'Total Price');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {

                    $OrderDetails = OrderDetail::where('order_master_id', $value->id)->get();
                    foreach ($OrderDetails as $val) {
                        if (Auth::user()->access_type == 'superadmin') {
                            $data['vendor'] = $value->vendor_name;
                        }
                        $data['invoice_id'] = $value->invoice_id;
                        $data['transaction_id'] = $value->transaction_id;
                        $data['customer_name'] = $value->customer_name;
                        $data['customer_email'] = $value->customer_email;
                        $data['customer_phone'] = $value->customer_phone;
                        $data['customer_address1'] = $value->customer_address1;
                        $data['customer_city'] = $value->customer_city;
                        $data['customer_state'] = $value->customer_state;
                        $data['customer_country'] = $value->customer_country;
                        $data['customer_zipcode'] = $value->customer_zipcode;

                        $data['service_name'] = $value->service_name;
                        $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                        $data['created_time'] = date("h:i a", strtotime($value->created_at));

                        $data['total_service_price'] = $value->total_service_price;
                        $data['coupon_amount'] = $value->coupon_amount;
                        $data['sub_total_price'] = $value->sub_total_price;
                        $data['cgst'] = round($value->tax_amount / 2,  2);
                        $data['sgst'] = round($value->tax_amount / 2,  2);
                        $data['total_order_price'] = $value->total_order_price;
                        $data['status'] = $value->status;
                        $data['payment_gateway'] = $value->payment_gateway;
                        $data['payment_status'] = $value->payment_status;
                        $data['item_name'] = $val->service_item_name;
                        $data['item_qty'] = $val->service_item_quantity;
                        $data['item_price'] = $val->service_item_price;
                        $data['item_total_status'] = $val->total_room_price;
                        fputcsv($fp, $data);
                    }
                    fputcsv($fp, array());
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_merchant_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/merchant_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);
            $headerArr = array('Invoice Id', 'Transaction Id', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Booking Date', 'Booking Time', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    if (Auth::user()->access_type == 'superadmin') {
                        $vendor = User::find($value->vendor_id);
                        $data['vendor'] = $vendor->company;
                    }
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;
                    $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_time'] = date("h:i a", strtotime($value->created_at));

                    $data['total_order_price'] = $value->total_room_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_gateway;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'export_merchant_detailed_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/merchant_order_detailed_report" . time() . ".csv";
            $csvname = public_path($csv);
            // print_r($OrderMaster);exit;
            $headerArr = array('Invoice Id', 'Transaction Id', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Booking Date', 'Booking Time', 'Total Price', 'Status', 'Payment Method', 'Payment Status', 'Item Name', 'Quantity', 'Unit Price', 'Total Price');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $vendor = User::find($value->vendor_id);
                    $OrderDetails = OrderDetail::where(['order_master_id' => $value->order_master_id, 'vendor_id' => $value->vendor_id])->get();
                    foreach ($OrderDetails as $val) {
                        if (Auth::user()->access_type == 'superadmin') {
                            $data['vendor'] = $vendor->company;
                        }
                        $data['invoice_id'] = $value->invoice_id;
                        $data['transaction_id'] = $value->transaction_id;

                        $data['customer_name'] = $value->customer_name;
                        $data['customer_email'] = $value->customer_email;
                        $data['customer_phone'] = $value->customer_phone;
                        $data['customer_address1'] = $value->customer_address1;
                        $data['customer_city'] = $value->customer_city;
                        $data['customer_state'] = $value->customer_state;
                        $data['customer_country'] = $value->customer_country;
                        $data['customer_zipcode'] = $value->customer_zipcode;
                        $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                        $data['created_time'] = date("h:i a", strtotime($value->created_at));

                        $data['total_order_price'] = $value->total_room_price;
                        $data['status'] = $value->status;
                        $data['payment_gateway'] = $value->payment_gateway;
                        $data['payment_status'] = $value->payment_status;
                        $data['item_name'] = $val->service_item_name;
                        $data['item_qty'] = $val->service_item_quantity;
                        $data['item_price'] = $val->service_item_price;
                        $data['item_total_status'] = $val->total_room_price;
                        fputcsv($fp, $data);
                    }
                    fputcsv($fp, array());
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }elseif($request->request_type == 'export_hall_summary_report'){
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/rental_hall_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'PayU Id', 'Order Type', 'Book Though', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address', 'City', 'State', 'Country', 'Zipcode', 'Customer GST No', 'Customer GST Company Name', 'Customer GST Company Address', 'Agent Name', 'Tour Type', 'Tour Name', 'Booking Date', 'Booking Time', 'Start Date', 'End Date', 'Adult', 'Child', 'Gross price', 'Discount', 'Coupon Name', 'Sub Total Price', 'CGST', 'SGST', 'Total Price', 'Status', 'Payment Method', 'Payment Status');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $payuId = 'N/A';
                    if (!empty($value->payment_id)) {
                        $PaymentHistory = PaymentHistory::find($value->payment_id);
                        $payuId = (!empty($PaymentHistory) && !empty($PaymentHistory->mihpayid)) ? $PaymentHistory->mihpayid : 'N/A';
                    }
                    $UserData = User::find($value->customer_id);
                    $agent_name = '';
                    if (!empty($UserData) && $UserData->access_type == 'agent') {
                        $agent_name = $UserData->first_name . ' ' . $UserData->last_name;
                    }
                    if (Auth::user()->access_type == 'superadmin') {
                        $data['vendor'] = $value->vendor_name;
                    }
                    $data['invoice_id'] = $value->invoice_id;
                    $data['transaction_id'] = $value->transaction_id;
                    $data['mihpayid'] = $payuId;
                    $data['order_type'] = $value->order_type;
                    $data['request_from'] = $value->request_from;

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_email'] = $value->customer_email;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_address1'] = $value->customer_address1;
                    $data['customer_city'] = $value->customer_city;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_zipcode'] = $value->customer_zipcode;
                    $data['gst_regd_no'] = $value->gst_regd_no;
                    $data['gst_company_name'] = $value->gst_company_name;
                    $data['gst_company_address'] = $value->gst_company_address;
                    $data['agent_name'] = $agent_name;

                    $data['service_category'] = $value->service_category;
                    $data['service_name'] = $value->service_name;
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_date'] = date("h:i a", strtotime($value->created_at));
                    $data['start_date'] = $value->start_date;
                    $data['end_date'] = $value->end_date;
                    $data['total_adults'] = $value->total_adults;
                    $data['total_child'] = $value->total_child;

                    $data['total_service_price'] = $value->total_service_price;
                    $data['coupon_amount'] = $value->coupon_amount;
                    $data['coupon_name'] = $value->coupon_name;
                    $data['sub_total_price'] = $value->sub_total_price;
                    $data['cgst'] = round($value->tax_amount / 2, 2);
                    $data['sgst'] = round($value->tax_amount / 2, 2);
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_method;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'print_hotel_order') {
            $OrderMaster = DB::select($request->printQuery);

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:30%;"><strong>User & Transaction Information</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Date of Booking</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Name Of Hotel</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Period Of Booking</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Room Type</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>No Of Room</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:20%;"><strong>Payment Details</strong></th></tr>';

            foreach ($OrderMaster as $value) {
                $room_data = json_decode($value->room_details, 1);
                $rooms = '';
                foreach ($room_data as $val) {
                    $rooms .= $val['room_name'] . ',<br>';
                }
                $rooms = rtrim($rooms, ',<br>');
                $sgst = $cgst = $cgstPrice = $sgstPrice = 0;
                if ($value->tax_amount > 0) {
                    $cgstPrice = $sgstPrice = round($value->tax_amount / 2, 2);
                }
                $expect_arrival = !empty($value->expected_arrival_time) ? 'Expected Arrival Time: ' . $value->expected_arrival_time : '';
                $need_pickup = !empty($value->need_pickup) ? 'Need Pickup: ' . $value->need_pickup : '';

                $html .= '<tr style="font-size:14px;">'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:30%;">'
                    . 'User Name: ' . $value->customer_name . '<br>Mobile No: ' . $value->customer_phone . '<br>Email Id: ' . $value->customer_email . '<br>' . $expect_arrival . '<br>' . $need_pickup . '<br>Discount type: ' . $value->coupon_name . '<br>Booking Id: ' . $value->invoice_id . '<br>Mode Of payment: ' . $value->order_type . '<br>PG: ' . $value->payment_method . '<br>TXN Id: ' . $value->transaction_id . '<br>Grand total: ' . $value->total_order_price . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y h:i a", strtotime($value->created_at)) . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $value->service_name . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y", strtotime($value->start_date)) . ' - ' . date("d-m-Y ", strtotime($value->end_date)) . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $rooms . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $value->total_rooms . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:20%;">'
                    . 'Room Cost: ' . $value->total_service_price . '<br>Discount: ' . $value->coupon_amount . '<br>After Discount: ' . $value->sub_total_price . '<br>SGST: ' . $sgstPrice . '<br>CGST: ' . $cgstPrice . '<br>Service Charge: ' . $value->service_charge . '<br>Grand Total: ' . $value->total_order_price . '</td>'
                    . '</tr>';
            }
            $html .= '</table></div></body></html>';
            $file = 'documents/Order_' . time() . '.pdf';
            $pdfname = public_path($file);
            PDF::loadHTML(html_entity_decode($html))->save($pdfname);
            echo $this->site . $file;
            exit;
            // return response()->download($pdfname)->deleteFileAfterSend(true);

        } elseif ($request->request_type == 'print_sightseeing_order') {
            $OrderMaster = DB::select($request->printQuery);

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:30%;"><strong>User & Transaction Information</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Date of Booking</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Tour Name</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Tour Date</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>No Of Seat Booked</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:20%;"><strong>Payment Details</strong></th></tr>';

            foreach ($OrderMaster as $value) {
                if ($value->service_category == 'sight seeing') {
                    $sgst = $cgst = $cgstPrice = $sgstPrice = 0;
                    if ($value->tax_amount > 0) {
                        $cgstPrice = $sgstPrice = round($value->tax_amount / 2, 2);
                    }

                    $html .= '<tr style="font-size:14px;">'
                        . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:30%;">'
                        . 'User Name: ' . $value->customer_name . '<br>Mobile No: ' . $value->customer_phone . '<br>Email Id: ' . $value->customer_email . '<br>Discount type: ' . $value->coupon_name . '<br>Booking Id: ' . $value->invoice_id . '<br>Mode Of payment: ' . $value->order_type . '<br>PG: ' . $value->payment_method . '<br>TXN Id: ' . $value->transaction_id . '<br>Grand total: ' . $value->total_order_price . '</td>'
                        . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y h:i a", strtotime($value->created_at)) . '</td>'
                        . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $value->service_name . '</td>'
                        . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y", strtotime($value->start_date)) . '</td>'
                        . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $value->total_guests . '</td>'
                        . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:20%;">'
                        . 'Tour Cost: ' . $value->total_service_price . '<br>Discount: ' . $value->coupon_amount . '<br>After Discount: ' . $value->sub_total_price . '<br>SGST: ' . $sgstPrice . '<br>CGST: ' . $cgstPrice . '<br>Grand Total: ' . $value->total_order_price . '</td>'
                        . '</tr>';
                }
            }
            $html .= '</table></div></body></html>';
            $file = 'documents/Order_' . time() . '.pdf';
            $pdfname = public_path($file);
            PDF::loadHTML(html_entity_decode($html))->save($pdfname);
            echo $this->site . $file;
            exit;
            // return response()->download($pdfname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'print_pacakge_order') {
            $OrderMaster = DB::select($request->printQuery);

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:30%;"><strong>User & Transaction Information</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Hotel Name</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Tour Date</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Room Type</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>No Of Room</strong></th></tr>';

            foreach ($OrderMaster as $value) {
                if ($value->service_category == 'package') {
                    $OrderDeatils = OrderDetail::where('order_master_id', $value->id)->get();
                    foreach ($OrderDeatils as $key => $val) {
                        if ($key == 0) {
                            $html .= '<tr style="font-size:14px;">'
                                . '<td rowspan="' . count($OrderDeatils) . '" align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:30%;">'
                                . 'Tour Name: ' . $value->service_name . '<br>User Name: ' . $value->customer_name . '<br>Mobile No: ' . $value->customer_phone . '<br>Email Id: ' . $value->customer_email . '<br>Discount type: ' . $value->coupon_name . '<br>Booking Id: ' . $value->invoice_id . '<br>Booking Date: ' . date("d-m-Y h:i a", strtotime($value->created_at)) . '<br>Mode Of payment: ' . $value->order_type . '<br>PG: ' . $value->payment_method . '<br>TXN Id: ' . $value->transaction_id . '<br>Grand total: ' . $value->total_order_price . '<br>Adult: ' . $value->total_adults . ', Child: ' . $value->total_child . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $val->service_name . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y", strtotime($val->start_date)) . ' - ' . date("d-m-Y", strtotime($val->end_date)) . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $val->service_item_name . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $val->service_item_quantity . '</td>'
                                . '</tr>';
                        } else {
                            $html .= '<tr style="font-size:14px;">'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $val->service_name . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y", strtotime($val->start_date)) . ' - ' . date("d-m-Y", strtotime($val->end_date)) . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $val->service_item_name . '</td>'
                                . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $val->service_item_quantity . '</td>'
                                . '</tr>';
                        }
                    }
                }
            }
            $html .= '</table></div></body></html>';
            $file = 'documents/Order_' . time() . '.pdf';
            $pdfname = public_path($file);
            PDF::loadHTML(html_entity_decode($html))->save($pdfname);
            echo $this->site . $file;
            exit;
            // return response()->download($pdfname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'print_ticketing_order') {
            $OrderMaster = DB::select($request->printQuery);

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:30%;"><strong>User & Transaction Information</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Date of Booking</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Name Of Unit</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Ticket Date</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>No Of Ticket</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:20%;"><strong>Payment Details</strong></th></tr>';

            foreach ($OrderMaster as $value) {
                $sgst = $cgst = $cgstPrice = $sgstPrice = 0;
                if ($value->tax_amount > 0) {
                    $cgstPrice = $sgstPrice = round($value->tax_amount / 2, 2);
                }
                $html .= '<tr style="font-size:14px;">'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:30%;">'
                    . 'User Name: ' . $value->customer_name . '<br>Mobile No: ' . $value->customer_phone . '<br>Email Id: ' . $value->customer_email . '<br>Discount type: ' . $value->coupon_name . '<br>Booking Id: ' . $value->invoice_id . '<br>Mode Of payment: ' . $value->order_type . '<br>PG: ' . $value->payment_method . '<br>TXN Id: ' . $value->transaction_id . '<br>Grand total: ' . $value->total_order_price . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y h:i a", strtotime($value->created_at)) . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $value->service_name . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y", strtotime($value->start_date)) . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">Adult: ' . $value->total_adults . ', child:' . $value->total_child . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:20%;">'
                    . 'Ticket Cost: ' . $value->total_service_price . '<br>Discount: ' . $value->coupon_amount . '<br>After Discount: ' . $value->sub_total_price . '<br>SGST: ' . $sgstPrice . '<br>CGST: ' . $cgstPrice . '<br>Service Charge: ' . $value->service_charge . '<br>Grand Total: ' . $value->total_order_price . '</td>'
                    . '</tr>';
            }
            $html .= '</table></div></body></html>';
            $file = 'documents/Order_' . time() . '.pdf';
            $pdfname = public_path($file);
            PDF::loadHTML(html_entity_decode($html))->save($pdfname);
            echo $this->site . $file;
            exit;
            // return response()->download($pdfname)->deleteFileAfterSend(true);

        }elseif($request->request_type == 'print_hall_order'){
            $OrderMaster = DB::select($request->printQuery);

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:30%;"><strong>User & Transaction Information</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Date of Booking</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Name Of Unit</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booked Slot</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Hall Name</strong></th><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;width:20%;"><strong>Payment Details</strong></th></tr>';

            foreach ($OrderMaster as $value) {
                $sgst = $cgst = $cgstPrice = $sgstPrice = 0;
                if ($value->tax_amount > 0) {
                    $cgstPrice = $sgstPrice = round($value->tax_amount / 2, 2);
                }
                $HallDetails = Hall::where('id', $value->service_name_id)->first();
                $BookingData = HallBooking::where('booking_id', $value->order_id)->first();
                $html .= '<tr style="font-size:14px;">'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:30%;">'
                    . 'User Name: ' . $value->customer_name . '<br>Mobile No: ' . $value->customer_phone . '<br>Email Id: ' . $value->customer_email . '<br>Discount type: ' . $value->coupon_name . '<br>Booking Id: ' . $value->invoice_id . '<br>Mode Of payment: ' . $value->order_type . '<br>PG: ' . $value->payment_method . '<br>TXN Id: ' . $value->transaction_id . '<br>Grand total: ' . $value->total_order_price . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . date("d-m-Y h:i a", strtotime($value->created_at)) . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $value->service_name . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $BookingData->slot_type . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">' . $HallDetails->hall_name . '</td>'
                    . '<td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;width:20%;">'
                    . 'Hall Cost: ' . $value->sub_total_price . '<br>SGST: ' . $sgstPrice . '<br>CGST: ' . $cgstPrice . '<br>Grand Total: ' . $value->total_order_price . '</td>'
                    . '</tr>';
            }
            $html .= '</table></div></body></html>';
            $file = 'documents/Order_' . time() . '.pdf';
            $pdfname = public_path($file);
            PDF::loadHTML(html_entity_decode($html))->save($pdfname);
            echo $this->site . $file;
            exit;

        }elseif($request->request_type == 'export_hall_detailed_report'){
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/merchant_order_detailed_report" . time() . ".csv";
            $csvname = public_path($csv);
            // print_r($OrderMaster);exit;
            $headerArr = array('Invoice Id', 'Transaction Id', 'Customer Name', 'Customer Email', 'Customer Phone', 'customer Address1', 'City', 'State', 'Country', 'Zipcode', 'Booking Date', 'Booking Time', 'Total Price', 'Status', 'Payment Method', 'Payment Status', 'Item Name', 'Quantity', 'Unit Price', 'Total Price');
            if (Auth::user()->access_type == 'superadmin') {
                array_unshift($headerArr, "Vendor");
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $vendor = User::find($value->vendor_id);
                    $OrderDetails = OrderDetail::where(['order_master_id' => $value->id, 'vendor_id' => $value->vendor_id])->get();
                    foreach ($OrderDetails as $val) {
                        if (Auth::user()->access_type == 'superadmin') {
                            $data['vendor'] = $vendor->company;
                        }
                        $data['invoice_id'] = $value->invoice_id;
                        $data['transaction_id'] = $value->transaction_id;

                        $data['customer_name'] = $value->customer_name;
                        $data['customer_email'] = $value->customer_email;
                        $data['customer_phone'] = $value->customer_phone;
                        $data['customer_address1'] = $value->customer_address1;
                        $data['customer_city'] = $value->customer_city;
                        $data['customer_state'] = $value->customer_state;
                        $data['customer_country'] = $value->customer_country;
                        $data['customer_zipcode'] = $value->customer_zipcode;
                        $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                        $data['created_time'] = date("h:i a", strtotime($value->created_at));

                        $data['total_order_price'] = $value->total_room_price;
                        $data['status'] = $value->status;
                        $data['payment_gateway'] = $value->payment_gateway;
                        $data['payment_status'] = $value->payment_status;
                        $data['item_name'] = $val->service_item_name;
                        $data['item_qty'] = $val->service_item_quantity;
                        $data['item_price'] = $val->service_item_price;
                        $data['item_total_status'] = $val->total_room_price;
                        fputcsv($fp, $data);
                    }
                    fputcsv($fp, array());
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'generate_ticket_qr') {
            $OrderMaster = OrderMaster::find($request->orderId);
            if (!empty($OrderMaster)) {
                require_once public_path('QrCode/generateQrCode.php');
                $QrCodeData = array(
                    'invoiceId' => $OrderMaster->invoice_id,
                    'orderId' => $OrderMaster->order_id,
                    'txnId' => $OrderMaster->transaction_id,
                    'serviceType' => $OrderMaster->service_type,
                );
                $QrCode = generateQrCode(json_encode($QrCodeData));
                $OrderMaster->qr_code = $QrCode;
                $OrderMaster->qr_base64 = $this->site . $OrderMaster->qr_code;
                $OrderMaster->qr_verified = 0;
                $OrderMaster->save();

                $responce['status'] = 1;
                // $responce['data'] = $orderData;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get qr';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function rentalOrders()
    {
        if (!(parent::checkViewPrivilege(30))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $AgentData = User::where(['access_type' => 'agent', 'role' => 3, 'vendor_id' => $vender_id])->pluck('first_name', 'id');
        $Agents = json_encode($AgentData);

        return view('users.rental-orders', compact('Vendors', 'Agents'));
    }

    public function getRentalOrders(Request $request)
    {

        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('vendor_name', 'service_name', 'customer_name', 'customer_phone', 'created_at', 'start_date', 'days_for_guide', 'order_type', 'invoice_id', 'status', 'total_order_price', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        } else {
            $aColumns = array('service_name', 'customer_name', 'customer_phone', 'created_at', 'start_date', 'days_for_guide', 'order_type', 'invoice_id', 'status', 'total_order_price', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        }

        $sIndexColumn = "id";
        $sTable = "order_masters";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY created_at DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
            if (Auth::user()->user_role == 'agent_staff') {
                $vendor_condtition .= ' AND customer_id = "' . Auth::user()->id . '" ';
            }
        }
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND service_type = "car"';
        $searchColumns = array('service_name', 'invoice_id', 'transaction_id', 'created_at', 'start_date', 'end_date', 'customer_id', 'request_from');
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) || (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7']))) {
            $condition1 = $condition2 = $condition3 = $condition4 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                if ($_POST['searchValue1'] == 'all') {
                    $condition1 .= ' AND status != "partially-cancelled"';
                } else {
                    if ($_POST['searchValue1'] == 'cancelled') {
                        $condition1 .= ' AND ((status = "' . $_POST['searchValue1'] . '" OR status = "partially-cancelled") AND payment_status = "success")';
                    } else {
                        $condition1 .= ' AND status = "' . $_POST['searchValue1'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue2'])) {
                $_POST['searchValue2'] = parent::cleanString($_POST['searchValue2']);
                $condition2 .= ' AND vendor_id = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) {
                if (in_array($_POST['searchValue3'], $searchColumns)) {
                    if ($_POST['searchValue3'] == 'created_at' || $_POST['searchValue3'] == 'start_date' || $_POST['searchValue3'] == 'end_date') {
                        $dates = explode(' - ', $_POST['searchValue4']);
                        $start = date('Y-m-d', strtotime($dates[0]));
                        $end = date('Y-m-d', strtotime($dates[1]));
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                    } else {
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7'])) {
                $_POST['searchValue6'] = parent::cleanString($_POST['searchValue6']);
                $condition4 .= ' AND (service_name_id like "' . $_POST['searchValue6'] . '") AND start_date <= "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '" AND end_date >= "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '"';
            }
            $sWhere .= $condition1 . $condition2 . $condition3 . $condition4;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $day_break = json_decode($aRow->room_request, 1);
            $cancel_option = $cancel_option_policy = ''; //($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && $aRow->start_date >= date("Y-m-d")) ? '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>' : '';
            if ($aRow->status == 'completed' && $aRow->payment_status == 'success' && $aRow->start_date >= date("Y-m-d")) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-amount="' . $aRow->total_order_price . '">Cancel Booking (Refund full)</a></li>';
                $cancel_option_policy = '<li><a href="javascript:void(0);" class="cancel_booking_policy" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-toggle="modal" data-target="#cancelPolicyModal">Cancel Booking (Refund As policy)</a></li>';
            } elseif ($aRow->status == 'pending' && $aRow->order_type == 'offline' && $aRow->payment_gateway == 'hdfc' && ($aRow->offline_link_expiry < date("Y-m-d H:i:s") ||  $aRow->start_date <= date("Y-m-d"))) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>';
            }
            $date = 'Start : ' . date("M d Y h:i a", strtotime($aRow->start_date . ' ' . $aRow->start_time)) . '<br>End : ' . date("M d Y h:i a", strtotime($aRow->end_date . ' ' . $aRow->end_time));

            if (Auth::user()->access_type == 'superadmin') {
                $row[] = $aRow->vendor_name;
            }
            $row[] = $aRow->service_name;
            $row[] = $aRow->customer_name;
            $row[] = $aRow->customer_phone;
            $row[] = date("d M Y h:i a", strtotime($aRow->created_at));
            $row[] = $date;
            $row[] = (count($day_break) > 1) ? '<a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->id . '">' . count($day_break) . '</a>' : count($day_break);
            $row[] = ($aRow->days_for_guide > 0) ? $aRow->days_for_guide . ' days' : 'N/A';
            $row[] = $aRow->order_type;
            $row[] = $aRow->invoice_id;
            $row[] = $aRow->status;
            $row[] = $aRow->total_order_price;
            $row[] = $aRow->payment_method;
            $row[] = $aRow->payment_status;
            $row[] = !empty($aRow->transaction_id) ? $aRow->transaction_id : "N/A";
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->id . '">Order Details</a></li>
                    <li><a href="javascript:void(0);" class="user_details" data-toggle="modal" data-target="#userDetailsModal" data-id="' . $aRow->id . '">User Details</a></li>
                    ' . $cancel_option . $cancel_option_policy . '
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function tourOrders()
    {
        if (!(parent::checkViewPrivilege(31))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TourQry = Tour::where(['vendor_id' => $vendor_id]);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TourQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Tour = $TourQry->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $TourCity = Tour::select(DB::raw('DISTINCT(city) AS city'))->pluck('city')->toArray();

        $AgentData = User::where(['access_type' => 'agent', 'role' => 3, 'vendor_id' => $vendor_id])->pluck('first_name', 'id');
        $Agents = json_encode($AgentData);

        return view('users.tour-orders', compact('Vendors', 'Tour', 'TourCity', 'Agents'));
    }

    public function getTourOrders(Request $request)
    {

        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('vendor_name', 'invoice_id', 'service_name', 'customer_name', 'customer_phone', 'service_city', 'total_guests', 'service_category', 'created_at', 'start_date', 'order_type', 'status', 'total_order_price', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        } else {
            $aColumns = array('invoice_id', 'service_name', 'customer_name', 'customer_phone', 'service_city', 'total_guests', 'service_category', 'created_at', 'start_date', 'order_type', 'status', 'total_order_price', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        }

        $sIndexColumn = "id";
        $sTable = "order_masters";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY created_at DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = $staff_condition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $staff_condition = ' AND service_name_id in (' . implode(',', $SubuserAccess) . ')';
                    $vendor_condtition .= $staff_condition;
                }
                if (Auth::user()->user_role == 'agent_staff') {
                    $staff_condition .= ' AND customer_id = "' . Auth::user()->id . '" ';
                }
            }
        }
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND service_type = "tour"';
        $searchColumns = array('service_name', 'service_category', 'invoice_id', 'transaction_id', 'created_at', 'start_date', 'end_date', 'service_name_id', 'order_type', 'payment_gateway', 'service_city', 'service_category', 'customer_id', 'request_from');
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) || (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7']))) {
            $condition1 = $condition2 = $condition3 = $condition4 = $condition5 = $condition8 = $condition9 = $condition10 = $condition11 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                if ($_POST['searchValue1'] == 'all') {
                    $condition1 .= ' AND status != "partially-cancelled"';
                } else {
                    if ($_POST['searchValue1'] == 'cancelled') {
                        $condition1 .= ' AND ((status = "' . $_POST['searchValue1'] . '" OR status = "partially-cancelled") AND payment_status = "success")';
                    } else {
                        $condition1 .= ' AND status = "' . $_POST['searchValue1'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue2'])) {
                $_POST['searchValue2'] = parent::cleanString($_POST['searchValue2']);
                $condition2 .= ' AND vendor_id = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) {
                if (in_array($_POST['searchValue3'], $searchColumns)) {
                    if ($_POST['searchValue3'] == 'created_at' || $_POST['searchValue3'] == 'start_date' || $_POST['searchValue3'] == 'end_date') {
                        $dates = explode(' - ', $_POST['searchValue4']);
                        $start = date('Y-m-d', strtotime($dates[0]));
                        $end = date('Y-m-d', strtotime($dates[1]));
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                    } else {
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue5'])) {
                $_POST['searchValue5'] = parent::cleanString($_POST['searchValue5']);
                $condition5 .= ' AND service_name_id = "' . $_POST['searchValue5'] . '"';
            }
            if (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7'])) {
                $_POST['searchValue6'] = parent::cleanString($_POST['searchValue6']);
                $condition4 .= ' AND (service_name_id like "' . $_POST['searchValue6'] . '") AND start_date = "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '"';
            }
            if (!empty($_POST['searchValue8'])) {
                $_POST['searchValue8'] = parent::cleanString($_POST['searchValue8']);
                $condition8 .= ' AND order_type = "' . $_POST['searchValue8'] . '"';
            }
            if (!empty($_POST['searchValue9'])) {
                $_POST['searchValue9'] = parent::cleanString($_POST['searchValue9']);
                $condition9 .= ' AND payment_gateway = "' . $_POST['searchValue9'] . '"';
            }
            if (!empty($_POST['searchValue10'])) {
                $_POST['searchValue10'] = parent::cleanString($_POST['searchValue10']);
                $condition10 .= ' AND service_city = "' . $_POST['searchValue10'] . '"';
            }
            if (!empty($_POST['searchValue11'])) {
                $_POST['searchValue11'] = parent::cleanString($_POST['searchValue11']);
                $condition11 .= ' AND service_category = "' . $_POST['searchValue11'] . '"';
            }
            $sWhere .= $condition1 . $condition2 . $condition3 . $condition4 . $condition5 . $condition8 . $condition9 . $condition10 . $condition11;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $printQuery = $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere $sOrder $sLimit";

        //        $printSightQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere AND service_category = 'sight seeing' $sOrder $sLimit";
        //        $printPackageQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable $sWhere AND service_category = 'package' $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $cancel_option = $cancel_option_policy = ''; //($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && $aRow->start_date >= date("Y-m-d")) ? '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>' : '';
            if ($aRow->status == 'completed' && $aRow->payment_status == 'success') {    //  && $aRow->start_date >= date("Y-m-d")
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-amount="' . $aRow->total_order_price . '">Cancel Booking (Refund full)</a></li>';
                $cancel_option_policy = '<li><a href="javascript:void(0);" class="cancel_booking_policy" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-toggle="modal" data-target="#cancelPolicyModal">Cancel Booking (Refund As policy)</a></li>';
            } elseif ($aRow->status == 'pending' && $aRow->order_type == 'offline' && $aRow->payment_gateway == 'hdfc' && ($aRow->offline_link_expiry < date("Y-m-d H:i:s") || $aRow->start_date <= date("Y-m-d"))) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>';
            }
            $partial_cancel = ($aRow->service_category == 'sight seeing' && $aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && ($aRow->start_date > date("Y-m-d"))) ? '<li><a href="' . url('tour-cancel-options/' . $aRow->id . '/' . $aRow->order_id) . '" target="_blank" class="partial_cancel" data-status="' . $aRow->status . '" data-id="' . $aRow->order_id . '">Shift Booking</a></li>' : '';
            $date = !empty($aRow->end_date) ? date("M d Y", strtotime($aRow->start_date)) . ' - ' . date("M d Y", strtotime($aRow->end_date)) : date("M d Y", strtotime($aRow->start_date));

            if (Auth::user()->access_type == 'superadmin') {
                $row[] = $aRow->vendor_name;
            }
            $row[] = $aRow->invoice_id;
            $row[] = $aRow->service_name;
            $row[] = $aRow->customer_name;
            $row[] = $aRow->customer_phone;
            $row[] = $aRow->service_city;
            $row[] = $aRow->total_guests;
            $row[] = $aRow->service_category;
            $row[] = date("M d Y H:i:s", strtotime($aRow->created_at));
            $row[] = $date;
            $row[] = $aRow->order_type;
            $row[] = $aRow->status;
            $row[] = $aRow->total_order_price;
            $row[] = $aRow->payment_method;
            $row[] = $aRow->payment_status;
            $row[] = !empty($aRow->transaction_id) ? $aRow->transaction_id : "N/A";
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->id . '">Order Details</a></li>
                    <li><a href="javascript:void(0);" class="user_details" data-toggle="modal" data-target="#userDetailsModal" data-id="' . $aRow->id . '">User Details</a></li>
                    ' . $cancel_option . $cancel_option_policy . $partial_cancel .'
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;
        $output['printQuery'] = $printQuery;
        //        $output['printSightQuery'] = $printSightQuery;
        //        $output['printPackageQuery'] = $printPackageQuery;

        echo json_encode($output);
        exit;
    }

    public function ticketingOrders()
    {
        if (!(parent::checkViewPrivilege(32))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $AgentData = User::where(['access_type' => 'agent', 'role' => 3, 'vendor_id' => $vendor_id])->pluck('first_name', 'id');
        $Agents = json_encode($AgentData);

        return view('users.ticketing-orders', compact('Vendors', 'Agents'));
    }

    public function getTicketOrders(Request $request)
    {

        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('vendor_name', 'invoice_id', 'service_name', 'customer_name', 'customer_phone', 'total_guests', 'service_category', 'created_at', 'start_date', 'order_type', 'invoice_id', 'status', 'total_order_price', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        } else {
            $aColumns = array('invoice_id', 'service_name', 'customer_name', 'customer_phone', 'total_guests', 'service_category', 'created_at', 'start_date', 'order_type', 'invoice_id', 'status', 'total_order_price', 'payment_method', 'payment_status', 'transaction_id', 'id', 'end_date', 'customer_email');
        }

        $sIndexColumn = "id";
        $sTable = "order_masters";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY created_at DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $staff_condition = ' AND service_name_id in (' . implode(',', $SubuserAccess) . ')';
                    $vendor_condtition .= $staff_condition;
                }
                if (Auth::user()->user_role == 'agent_staff') {
                    $staff_condition .= ' AND customer_id = "' . Auth::user()->id . '" ';
                }
            }
        }
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND service_type = "ticketing"';
        $searchColumns = array('service_name', 'service_category', 'invoice_id', 'transaction_id', 'created_at', 'start_date', 'end_date', 'customer_id', 'request_from');
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) || (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7']))) {
            $condition1 = $condition2 = $condition3 = $condition4 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                if ($_POST['searchValue1'] == 'all') {
                    $condition1 .= ' AND status != "partially-cancelled"';
                } else {
                    if ($_POST['searchValue1'] == 'cancelled') {
                        $condition1 .= ' AND ((status = "' . $_POST['searchValue1'] . '" OR status = "partially-cancelled") AND payment_status = "success")';
                    } else {
                        $condition1 .= ' AND status = "' . $_POST['searchValue1'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue2'])) {
                $_POST['searchValue2'] = parent::cleanString($_POST['searchValue2']);
                $condition2 .= ' AND vendor_id = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) {
                if (in_array($_POST['searchValue3'], $searchColumns)) {
                    if ($_POST['searchValue3'] == 'created_at' || $_POST['searchValue3'] == 'start_date' || $_POST['searchValue3'] == 'end_date') {
                        $dates = explode(' - ', $_POST['searchValue4']);
                        $start = date('Y-m-d', strtotime($dates[0]));
                        $end = date('Y-m-d', strtotime($dates[1]));
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                    } else {
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7'])) {
                $_POST['searchValue6'] = parent::cleanString($_POST['searchValue6']);
                $condition4 .= ' AND (service_name_id like "' . $_POST['searchValue6'] . '") AND start_date = "' . date("Y-m-d", strtotime($_POST['searchValue7'])) . '"';
            }
            $sWhere .= $condition1 . $condition2 . $condition3 . $condition4;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $printQuery = $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $cancel_option = $cancel_option_policy = ''; //($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && $aRow->start_date >= date("Y-m-d")) ? '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>' : '';
            $part_th_date = date("Y-m-d", strtotime($aRow->start_date .' +3 Days'));
            if ($aRow->status == 'completed' && $aRow->payment_status == 'success' && ($aRow->start_date > date("Y-m-d") || $part_th_date >= date("Y-m-d"))) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-amount="' . $aRow->total_order_price . '">Cancel Booking (Refund full)</a></li>';
                $cancel_option_policy = '<li><a href="javascript:void(0);" class="cancel_booking_policy" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '" data-toggle="modal" data-target="#cancelPolicyModal">Cancel Booking (Refund As policy)</a></li>';
            } elseif ($aRow->status == 'pending' && $aRow->order_type == 'offline' && $aRow->payment_gateway == 'hdfc' && ($aRow->offline_link_expiry < date("Y-m-d H:i:s") ||  $aRow->start_date <= date("Y-m-d"))) {
                $cancel_option = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->id . '">Cancel Booking</a></li>';
            }
            if (Auth::user()->access_type == 'superadmin') {
                $row[] = $aRow->vendor_name;
            }
            $checkin = ($aRow->qr_verified == 1) ? '<br><span class="text-success">Checked In</span>' : '';
            $row[] = $aRow->invoice_id . $checkin;  //'<a href="javascript:void(0);" class="generateQr" data-id="'. $aRow->id .'">'. $aRow->invoice_id .'</a> ('. $aRow->id .')';
            $row[] = $aRow->service_name;
            $row[] = $aRow->customer_name;
            $row[] = $aRow->customer_phone;
            $row[] = $aRow->total_guests;
            $row[] = $aRow->service_category;
            $row[] = date("d M Y H:i:s", strtotime($aRow->created_at));
            $row[] = date("d M Y", strtotime($aRow->start_date));
            $row[] = $aRow->order_type;

            $row[] = $aRow->status;
            $row[] = $aRow->total_order_price;
            $row[] = $aRow->payment_method;
            $row[] = $aRow->payment_status;
            $row[] = !empty($aRow->transaction_id) ? $aRow->transaction_id : "N/A";
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->id . '">Order Details</a></li>
                    <li><a href="javascript:void(0);" class="user_details" data-toggle="modal" data-target="#userDetailsModal" data-id="' . $aRow->id . '">User Details</a></li>
                    ' . $cancel_option . $cancel_option_policy . '
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;
        $output['printQuery'] = $printQuery;

        echo json_encode($output);
        exit;
    }

    public function foodOrders()
    {
        if (!(parent::checkViewPrivilege(45))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        return view('users.food-orders', compact('Vendors'));
    }

    public function getFoodOrders(Request $request)
    {

        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('vendor_name', 'service_name', 'customer_name', 'customer_phone', 'created_at', 'order_id', 'invoice_id', 'status', 'total_order_price', 'payment_gateway', 'payment_status', 'transaction_id', 'id');
        } else {
            $aColumns = array('service_name', 'customer_name', 'customer_phone', 'created_at', 'order_id', 'invoice_id', 'status', 'total_order_price', 'payment_gateway', 'payment_status', 'transaction_id', 'id');
        }

        $sIndexColumn = "id";
        $sTable = "order_masters";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        }
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND service_type = "restaurant"';
        $searchColumns = array('service_name', 'invoice_id', 'transaction_id', 'created_at');
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) || (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7']))) {
            $condition1 = $condition2 = $condition3 = $condition4 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                if ($_POST['searchValue1'] == 'all') {
                    $condition1 .= ' AND status != "partially-cancelled"';
                } else {
                    if ($_POST['searchValue1'] == 'cancelled') {
                        $condition1 .= ' AND ((status = "' . $_POST['searchValue1'] . '" OR status = "partially-cancelled") AND payment_status = "success")';
                    } else {
                        $condition1 .= ' AND status = "' . $_POST['searchValue1'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue2'])) {
                $_POST['searchValue2'] = parent::cleanString($_POST['searchValue2']);
                $condition2 .= ' AND vendor_id = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) {
                if (in_array($_POST['searchValue3'], $searchColumns)) {
                    if ($_POST['searchValue3'] == 'created_at' || $_POST['searchValue3'] == 'start_date' || $_POST['searchValue3'] == 'end_date') {
                        $dates = explode(' - ', $_POST['searchValue4']);
                        $start = date('Y-m-d', strtotime($dates[0]));
                        $end = date('Y-m-d', strtotime($dates[1]));
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                    } else {
                        $condition3 .= ' AND ' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    }
                }
            }
            $sWhere .= $condition1 . $condition2 . $condition3;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            // $cancel_option = ($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->status != 'pending' && $aRow->start_date >= date("Y-m-d")) ? '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->order_id . '">Cancel Booking</a></li>' : '';

            if (Auth::user()->access_type == 'superadmin') {
                $row[] = $aRow->vendor_name;
            }
            $row[] = $aRow->service_name;
            $row[] = $aRow->customer_name;
            $row[] = $aRow->customer_phone;
            $row[] = date("M d Y  H:i:s", strtotime($aRow->created_at));
            $row[] = $aRow->order_id;
            $row[] = $aRow->invoice_id;
            $row[] = $aRow->status;
            $row[] = $aRow->total_order_price;
            $row[] = $aRow->payment_gateway;
            $row[] = $aRow->payment_status;
            $row[] = !empty($aRow->transaction_id) ? $aRow->transaction_id : "N/A";
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->id . '">Order Details</a></li>
                    <li><a href="javascript:void(0);" class="user_details" data-toggle="modal" data-target="#userDetailsModal" data-id="' . $aRow->id . '">User Details</a></li>
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function merchantOrders()
    {
        if (!(parent::checkViewPrivilege(52))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        return view('users.merchant-orders', compact('Vendors'));
    }

    public function getMerchantOrders(Request $request)
    {

        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('vendor_id', 'created_at', 'customer_name', 'customer_phone', 'order_id', 'invoice_id', 'total_room_price', 'status', 'payment_gateway', 'payment_status', 'transaction_id');
        } else {
            $aColumns = array('created_at', 'customer_name', 'customer_phone', 'order_id', 'invoice_id', 'total_room_price', 'status', 'payment_gateway', 'payment_status', 'transaction_id');
        }

        $sIndexColumn = "id";
        $sTable = "order_details";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND OD.vendor_id = ' . $vender_id;
        }
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND OD.service_type = "merchant"';
        $searchColumns = array('invoice_id', 'transaction_id', 'created_at');
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) || (!empty($_POST['searchValue6']) && !empty($_POST['searchValue7']))) {
            $condition1 = $condition2 = $condition3 = $condition4 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                if ($_POST['searchValue1'] == 'all') {
                    $condition1 .= ' AND status != "partially-cancelled"';
                } else {
                    if ($_POST['searchValue1'] == 'cancelled') {
                        $condition1 .= ' AND ((OM.status = "' . $_POST['searchValue1'] . '" OR OM.status = "partially-cancelled") AND OM.payment_status = "success")';
                    } else {
                        $condition1 .= ' AND OM.status = "' . $_POST['searchValue1'] . '"';
                    }
                }
            }
            if (!empty($_POST['searchValue2'])) {
                $_POST['searchValue2'] = parent::cleanString($_POST['searchValue2']);
                $condition2 .= ' AND OD.vendor_id = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3']) && !empty($_POST['searchValue4'])) {
                if (in_array($_POST['searchValue3'], $searchColumns)) {
                    if ($_POST['searchValue3'] == 'created_at' || $_POST['searchValue3'] == 'start_date' || $_POST['searchValue3'] == 'end_date') {
                        $dates = explode(' - ', $_POST['searchValue4']);
                        $start = date('Y-m-d', strtotime($dates[0]));
                        $end = date('Y-m-d', strtotime($dates[1]));
                        $condition3 .= ' AND OD.' . $_POST['searchValue3'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
                    } elseif ($_POST['searchValue3'] == 'invoice_id' || $_POST['searchValue3'] == 'transaction_id') {
                        $condition3 .= ' AND OM.' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    } else {
                        $condition3 .= ' AND OD.' . $_POST['searchValue3'] . ' LIKE "' . $_POST['searchValue4'] . '"';
                    }
                }
            }
            $sWhere .= $condition1 . $condition2 . $condition3;
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }
        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $exQuery = "SELECT SQL_CALC_FOUND_ROWS OD.vendor_id, OD.order_id, OD.order_master_id, SUM(OD.total_room_price) as total_room_price, OD.created_at, OM.customer_name, OM.customer_phone, OM.invoice_id, OM.status, OM.payment_gateway, OM.payment_status, OM.transaction_id, OM.customer_name, OM.customer_email, OM.customer_phone, OM.customer_address1, OM.customer_address2, OM.customer_city, OM.customer_state, OM.customer_zipcode, OM.customer_country FROM $sTable AS OD JOIN order_masters AS OM ON(OD.order_master_id = OM.id) $sWhere GROUP BY OD.order_id, OD.vendor_id $sOrder";
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS OD.vendor_id, OD.order_id, OD.order_master_id, SUM(OD.total_room_price) as total_room_price, OD.created_at, OM.customer_name, OM.customer_phone, OM.invoice_id, OM.status, OM.payment_gateway, OM.payment_status, OM.transaction_id  FROM $sTable AS OD JOIN order_masters AS OM ON(OD.order_master_id = OM.id) $sWhere GROUP BY OD.order_id, OD.vendor_id $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS OD.vendor_id, OD.order_id, OD.order_master_id, SUM(OD.total_room_price) as total_room_price, OD.created_at, OM.invoice_id, OM.status, OM.payment_gateway, OM.payment_status, OM.transaction_id  FROM $sTable AS OD JOIN order_masters AS OM ON(OD.order_master_id = OM.id) $sWhere GROUP BY OD.order_id, OD.vendor_id";
        $aResultTotal = DB::select($sQuery);
        $iTotal = count($aResultTotal);

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            //            $cancel_option = ($aRow->status != 'cancelled' && $aRow->status != 'partially-cancelled' && $aRow->start_date >= date("Y-m-d")) ? '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $aRow->status . '" data-id="' . $aRow->order_id . '">Cancel Booking</a></li>' : '';
            //            $OrderMaster = OrderMaster::find($aRow->order_master_id);
            if (Auth::user()->access_type == 'superadmin') {
                $Vendor = User::find($aRow->vendor_id);
                $row[] = $Vendor->company;
            }
            $row[] = date("M d Y H:i:s", strtotime($aRow->created_at));
            $row[] = $aRow->customer_name;
            $row[] = $aRow->customer_phone;
            $row[] = $aRow->order_id;
            $row[] = $aRow->invoice_id;
            $row[] = $aRow->total_room_price;
            $row[] = $aRow->status;
            $row[] = $aRow->payment_gateway;
            $row[] = $aRow->payment_status;
            $row[] = !empty($aRow->transaction_id) ? $aRow->transaction_id : "N/A";
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="javascript:void(0);" data-toggle="modal" data-target="#orderDetailsModal" class="order_details" data-id="' . $aRow->order_master_id . '" data-vendor="' . $aRow->vendor_id . '">Order Details</a></li>
                    <li><a href="javascript:void(0);" class="user_details" data-toggle="modal" data-target="#userDetailsModal" data-id="' . $aRow->order_master_id . '">User Details</a></li>
                </ul>
            </div>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function vendorProfile()
    {
        if (!(parent::checkViewPrivilege(54))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $VendorProfile = VendorProfile::where('vendor_id', $vendor_id)->first();
        $gallery = array();
        $profile_type = 'own';
        if (!empty($VendorProfile)) {
            $profile_type = $VendorProfile->profile_type;
            $VendorProfile->banner_image = !empty($VendorProfile->banner_image) ? json_decode($VendorProfile->banner_image) : [];
            $count = 1;

            foreach ($VendorProfile->banner_image as $value) {
                $gallery[] = ['id' => $count, 'src' => $this->site . $value];
                $count++;
            }
        }
        $gallery = json_encode($gallery);

        return view('users.vendor-profile', compact('VendorProfile', 'gallery', 'profile_type'));
    }

    public function saveVendorProfile(Request $request)
    {
        if (!(parent::checkWritePrivilege(54))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        if ($request->profile_type == 'own') {
            $validate = Validator::make($request->all(), [
                'profile_url' => 'required|string',
            ]);
        } else {
            $validate = Validator::make($request->all(), [
                'details' => 'required|string',
                'images.*' => 'mimes:jpeg,png,jpg',
            ]);
        }

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('vendor-profile')->withErrors($validate)->withInput();
        } else {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $UserData = User::find($vendor_id);
            $slug = str_replace(' ', '-', $UserData->company);
            $VendorProfile = VendorProfile::where('vendor_id', $vendor_id)->first();
            $UploadDir = 'images/profile/';
            $gallery_images = array();
            if (!empty($VendorProfile)) {
                $VendorProfile->slug = $slug;
                $VendorProfile->profile_type = $request->profile_type;
                if ($request->profile_type == 'own') {
                    $VendorProfile->profile_url = $request->profile_url;
                } else {
                    $VendorProfile->details = $request->details;

                    if ($request->hasFile('images')) {
                        foreach ($request->file('images') as $file) {
                            $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                            $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $file->extension();
                            $file->move(public_path($UploadDir), $gallery_image);
                            array_push($gallery_images, $UploadDir . $gallery_image);
                        }
                    }
                    $old_gallery = !empty($VendorProfile->banner_image) ? json_decode($VendorProfile->banner_image) : [];
                    $preload_data = ($request->oldimage) ? $request->oldimage : [];
                    if (count($preload_data) != count($old_gallery)) {
                        $temp = 1;
                        foreach ($old_gallery as $key => $value) {
                            if (!in_array($temp, $preload_data)) {
                                if (file_exists(public_path($value))) {
                                    unlink(public_path($value));
                                    unset($old_gallery[$key]);
                                }
                            }
                            $temp++;
                        }
                    }
                    $gallery_images = array_merge($old_gallery, $gallery_images);
                    $VendorProfile->banner_image = json_encode($gallery_images);
                }
            } else {
                if ($request->profile_type == 'own') {
                    $VendorProfile = new VendorProfile([
                        'vendor_id' => $vendor_id,
                        'slug' => $slug,
                        'profile_type' => $request->profile_type,
                        'profile_url' => $request->profile_url
                    ]);
                } else {
                    if ($request->hasFile('images')) {
                        foreach ($request->file('images') as $file) {
                            $filenameWithExt = str_replace(' ', '-', $file->getClientOriginalName());
                            $gallery_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $file->extension();
                            $file->move(public_path($UploadDir), $gallery_image);
                            array_push($gallery_images, $UploadDir . $gallery_image);
                        }
                    }
                    $VendorProfile = new VendorProfile([
                        'vendor_id' => $vendor_id,
                        'slug' => $slug,
                        'profile_type' => $request->profile_type,
                        'details' => addslashes($request->details),
                        'banner_image' => json_encode($gallery_images)
                    ]);
                }
            }
            if ($VendorProfile->save()) {
                Session::flash('success', 'Profile details saved successfully.');
                return Redirect::to('vendor-profile');
            } else {
                Session::flash('success', 'Unable to save Profile details.');
                return Redirect::to('vendor-profile');
            }
        }
    }

    public function refundPolicy()
    {
        if (!(parent::checkViewPrivilege(55))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.refund-policy');
    }

    public function getRefundPolicy(Request $request)
    {

        $aColumns = array('service_type', 'start', 'end', 'percent');
        $sIndexColumn = "id";
        $sTable = "cancel_policy";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = ' WHERE vendor_id = ' . $vender_id;
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable  $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $row[] = strtoupper($aRow->service_type);
            $row[] = $aRow->start;
            $row[] = $aRow->end;
            $row[] = $aRow->percent;
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('refund-policy-edit', $aRow->service_type) . '">Edit</a></li>
                    <li class="deletePolicy" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function addRefundPolicy()
    {
        if (!(parent::checkWritePrivilege(55))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Services = parent::serviceCategory();

        return view('users.add-refund-policy', compact('Services'));
    }

    public function policyAddRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required|numeric',
            'service_type' => 'required|string',
            'policy' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-refund-policy')->withErrors($validate)->withInput();
        } else {
            $policy = [];
            if (isset($request->policy)) {
                $i = 0;
                foreach ($request->policy as $value) {
                    $policy[$i] = [
                        'vendor_id' => $request->vendor_id,
                        'service_type' => $request->service_type,
                        'start' => $value['start'],
                        'end' => $value['end'],
                        'percent' => $value['percent'],
                    ];
                    $i++;
                }
            }
            CancelPolicy::insert($policy);
            Session::flash('success', 'Policy added successfully.');
            return Redirect::to('refund-policy');
        }
    }

    public function refundPolicyEdit($service = null)
    {
        if (!(parent::checkWritePrivilege(55))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $RefundPolicy = CancelPolicy::where(['service_type' => $service, 'vendor_id' => $vendor_id])->orderBy('start', 'asc')->get();
        if (!empty($RefundPolicy)) {
            $Services = parent::serviceCategory();

            return view('users.edit-refund-policy', compact('RefundPolicy', 'Services', 'service'));
        } else {
            return redirect()->back();
        }
    }

    public function policyEditRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required|numeric',
            'service_type' => 'required|string',
            'policy' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('refund-policy-edit/' . $request->service_type)->withErrors($validate)->withInput();
        } else {

            $policy = [];
            if (isset($request->policy)) {
                $i = 0;
                foreach ($request->policy as $value) {
                    $policy[$i] = [
                        'vendor_id' => $request->vendor_id,
                        'service_type' => $request->service_type,
                        'start' => $value['start'],
                        'end' => $value['end'],
                        'percent' => $value['percent'],
                    ];
                    $i++;
                }
            }
            if (!empty($policy)) {
                $RefundPolicy = DB::table('cancel_policy')->where(['service_type' => $request->service_type, 'vendor_id' => $request->vendor_id])->delete();
                CancelPolicy::insert($policy);
                Session::flash('success', 'Policy updated successfully.');
                return Redirect::to('refund-policy');
            } else {
                Session::flash('success', 'Unable to update refund policy.');
                return Redirect::to('refund-policy-edit/' . $request->service_type);
            }
        }
    }

    public function gstRules()
    {
        if (!(parent::checkViewPrivilege(56))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.gst-rules');
    }

    public function getGstDetails(Request $request)
    {

        $aColumns = array('service_type', 'min_amount', 'gst');
        $sIndexColumn = "id";
        $sTable = "gst_table";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = ' WHERE vendor_id = ' . $vender_id;
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $sTable  $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $GST = json_decode($aRow->gst, 1);
            $gst_val = '';
            foreach ($GST as $key => $value) {
                $gst_val .= $key . ' = ' . $value . '<br>';
            }

            $row[] = strtoupper($aRow->service_type);
            $row[] = $aRow->min_amount;
            $row[] = $gst_val;
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('gst-rules-edit', $aRow->id) . '">Edit</a></li>
                    <li class="deleteGST" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function addGstRule()
    {
        if (!(parent::checkWritePrivilege(56))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Services = parent::serviceCategory();

        return view('users.add-gst-rule', compact('Services'));
    }

    public function gstAddRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required|numeric',
            'service_type' => 'required|string',
            'min_amount' => 'required|numeric',
            'gst' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-gst-rule')->withErrors($validate)->withInput();
        } else {
            $gst_data = [];
            if (isset($request->gst)) {
                foreach ($request->gst as $val) {
                    $gst_data[$val['title']] = $val['value'];
                }
            }
            $GstTable = new GstTable([
                'vendor_id' => $request->vendor_id,
                'service_type' => $request->service_type,
                'min_amount' => $request->min_amount,
                'gst' => json_encode($gst_data)
            ]);
            if ($GstTable->save()) {
                Session::flash('success', 'GST rule added successfully.');
                return Redirect::to('gst-rules');
            } else {
                Session::flash('success', 'Unable to add GST rule.');
                return Redirect::to('add-gst-rule');
            }
        }
    }

    public function gstRulesEdit($id = null)
    {
        if (!(parent::checkWritePrivilege(56))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $GstTable = GstTable::find($id);
        if (!empty($GstTable)) {
            $GstTable->gst = json_decode($GstTable->gst, 1);
            $Services = parent::serviceCategory();

            return view('users.edit-gst-rule', compact('GstTable', 'Services'));
        } else {
            return redirect()->back();
        }
    }

    public function gstEditRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required|numeric',
            'service_type' => 'required|string',
            'min_amount' => 'required|numeric',
            'gst' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('gst-rules-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $GstTable = GstTable::find($request->id);
            $GstTable->service_type = $request->service_type;
            $GstTable->min_amount = $request->min_amount;
            $GstTable->service_type = $request->service_type;

            $gst_data = [];
            if (isset($request->gst)) {
                foreach ($request->gst as $val) {
                    $gst_data[$val['title']] = $val['value'];
                }
                $GstTable->gst = json_encode($gst_data);
            }

            if ($GstTable->save()) {
                Session::flash('success', 'GST rule updated successfully.');
                return Redirect::to('gst-rules');
            } else {
                Session::flash('success', 'Unable to save GST rule.');
                return Redirect::to('gst-rules-edit/' . $request->id);
            }
        }
    }

    public function refundHistory()
    {
        if (!(parent::checkViewPrivilege(74))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $service_type = array();
        $condition = '';
        if (Auth::user()->role == 3) {
            $UserPrivilege = !empty(Auth::user()->privilege) ? json_decode(Auth::user()->privilege, 1) : [];
            if (array_key_exists(28, $UserPrivilege) && $UserPrivilege[28] != 0) {
                array_push($service_type, 'hotel');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND (CR.service_type = "hotel" AND CR.service_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
            if (array_key_exists(30, $UserPrivilege) && $UserPrivilege[30] != 0) {
                array_push($service_type, 'rental');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'rental'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND (CR.service_type = "rental" AND CR.service_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
            if (array_key_exists(31, $UserPrivilege) && $UserPrivilege[31] != 0) {
                array_push($service_type, 'sight-seeing', 'package');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND ((CR.service_type = "sight-seeing" OR CR.service_type = "package") AND CR.service_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
            if (array_key_exists(32, $UserPrivilege) && $UserPrivilege[32] != 0) {
                array_push($service_type, 'ticketing');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND (CR.service_type = "ticketing" AND CR.service_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
        }
        $Status = CustomerRefund::select(DB::raw('DISTINCT(refund_status) as status'))->pluck('status')->toArray();
        $Status = json_encode($Status);

        return view('users.refund-history', compact('condition', 'Status'));
    }

    public function getRefundHistory(Request $request)
    {

        $aColumns = array('cancel_date', 'invoice_id', 'service_type', 'service_name', 'customer_name', 'paid_amount', 'refund_amount', 'payment_method', 'pg_txn_id', 'reference_id', 'bank_reference_num', 'refund_status', 'created_at', 'customer_phone');
        $sIndexColumn = "id";
        $sTable = "customer_refunds";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY CR.id DESC";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $vendor_condtition = '';
        if ($vender_id == 3) {
            $vendor_condtition = ' AND CR.`created_at` > "'. $this->ecoStartDate .'" ';
        }
        $sWhere = ' WHERE CR.vendor_id = '. $vender_id . $vendor_condtition .' AND OM.vendor_id = ' . $vender_id . ' AND OM.status = "cancelled" AND CR.payment_method = "hdfc"';
        if (Auth::user()->role == 3) {
            // echo html_entity_decode($_POST['searchValue3']);exit;
            $sWhere .= html_entity_decode($_POST['searchValue3']);
        }

        $searchColumns = array('invoice_id', 'service_type', 'service_name', 'customer_name', 'customer_phone', 'refund_amount', 'cancel_date', 'payment_method', 'reference_id', 'bank_reference_num', 'pg_txn_id');
        $condition1 = '';
        if (!empty($_POST['searchValue1']) && !empty($_POST['searchValue2'])) {
            //            $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
            if ($_POST['searchValue1'] == 'cancel_date') {
                $dates = explode(' - ', $_POST['searchValue2']);
                $start = date('Y-m-d', strtotime($dates[0]));
                $end = date('Y-m-d', strtotime($dates[1]));
                $condition1 .= ' AND CR.' . $_POST['searchValue1'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
            } elseif ($_POST['searchValue1'] == 'service_name' || $_POST['searchValue1'] == 'customer_name' || $_POST['searchValue1'] == 'customer_phone') {
                $condition1 .= ' AND OM.' . $_POST['searchValue1'] . ' LIKE "%' . $_POST['searchValue2'] . '%"';
            } else {
                $condition1 .= ' AND CR.' . $_POST['searchValue1'] . ' LIKE "%' . $_POST['searchValue2'] . '%"';
            }
        }
        $sWhere .= $condition1;

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS CR.id, CR.stop, CR.invoice_id, CR.service_type, OM.service_name, OM.book_from, OM.customer_name, OM.customer_phone, CR.paid_amount, OM.cancel_date, OM.start_date, CR.created_at, CR.refund_amount, OM.payment_method, CR.pg_txn_id, CR.reference_id, CR.bank_reference_num, CR.refund_status, CR.updated_at FROM $sTable AS CR JOIN order_masters AS OM ON(CR.invoice_id = OM.invoice_id) $sWhere $sOrder $sLimit";
        $exQuery = "SELECT CR.id, CR.stop, CR.invoice_id, CR.service_type, OM.service_name, OM.book_from, OM.customer_name, OM.customer_phone, CR.paid_amount, OM.cancel_date, OM.start_date, CR.created_at, CR.refund_amount, OM.payment_method, CR.pg_txn_id, CR.reference_id, CR.bank_reference_num, CR.refund_status, CR.updated_at, OM.cancel_reason, OM.gst_regd_no, OM.gst_company_name, OM.invoice_serial FROM $sTable AS CR JOIN order_masters AS OM ON(CR.invoice_id = OM.invoice_id) $sWhere $sOrder";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS CR.id, CR.stop, CR.invoice_id, CR.service_type, OM.service_name, OM.book_from, OM.customer_name, OM.customer_phone, CR.paid_amount, OM.cancel_date, OM.start_date, CR.created_at, CR.refund_amount, OM.payment_method, CR.pg_txn_id, CR.reference_id, CR.bank_reference_num, CR.refund_status, CR.updated_at FROM $sTable AS CR JOIN order_masters AS OM ON(CR.invoice_id = OM.invoice_id) $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = count($aResultTotal);

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            // $stopLink = (($aRow->refund_status == 'pending' || $aRow->refund_status == 'PENDING') && $aRow->stop == 0) ? '<br><a class="btn btn-danger stopRefund" data-id="'. $aRow->id .'">STOP</a>' : '';
            $initiateLink = '';
            if ($aRow->refund_status == 'failure') {
                $SuccessRefunds = CustomerRefund::where(['invoice_id' => $aRow->invoice_id, 'refund_status' => 'success'])->first();
                $initiateLink = (empty($SuccessRefunds) && $aRow->stop == 0) ? '<br><a class="btn btn-success initiateRefund" data-id="'. $aRow->id .'">Re-initiate</a>' : '';
            }
            $status = ($aRow->refund_status == 'success' || $aRow->refund_status == 'failure') ? $aRow->refund_status .'<br>'. date("d-M-Y h:i a", strtotime($aRow->updated_at)) : $aRow->refund_status;

            $row[] = date("d-M-Y h:i a", strtotime($aRow->cancel_date));
            $row[] = $aRow->invoice_id;
            $row[] = strtoupper($aRow->service_type) . (($aRow->service_type == 'hotel' && $aRow->book_from == 'blocked') ? '<br>(Blocked)' : '');
            $row[] = $aRow->service_name .'<br>Check in: '. date("d-M-Y", strtotime($aRow->start_date));
            $row[] = '<b>Name:</b> '. $aRow->customer_name .'<br><b>Phone:</b> '. $aRow->customer_phone;
            $row[] = $aRow->paid_amount;
            $row[] = $aRow->refund_amount;
            $row[] = strtoupper($aRow->payment_method);
            $row[] = $aRow->pg_txn_id;
            $row[] = $aRow->reference_id;
            $row[] = $aRow->bank_reference_num;
            $row[] = $status . $initiateLink;
            $row[] = date("d-M-Y h:i a", strtotime($aRow->created_at));
            //            $row[] = '<div class="btn-group">
            //                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
            //                <ul role="menu" class="dropdown-menu">
            //
            //                </ul>
            //            </div>';

            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function failurePayments()
    {
        if (!(parent::checkViewPrivilege(85))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }

        $service_type = array();
        $condition = '';
        if (Auth::user()->role == 3) {
            $UserPrivilege = !empty(Auth::user()->privilege) ? json_decode(Auth::user()->privilege, 1) : [];
            if (array_key_exists(28, $UserPrivilege) && $UserPrivilege[28] != 0) {
                array_push($service_type, 'hotel');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'hotel'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND (service_type = "hotel" AND service_name_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
            if (array_key_exists(30, $UserPrivilege) && $UserPrivilege[30] != 0) {
                array_push($service_type, 'rental');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'rental'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND (service_type = "rental" AND service_name_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
            if (array_key_exists(31, $UserPrivilege) && $UserPrivilege[31] != 0) {
                array_push($service_type, 'sight-seeing', 'package');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'tour'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND ((service_type = "sight-seeing" OR service_type = "package") AND service_name_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
            if (array_key_exists(32, $UserPrivilege) && $UserPrivilege[32] != 0) {
                array_push($service_type, 'ticketing');
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $condition .= ' AND (service_type = "ticketing" AND service_name_id in (' . implode(',', $SubuserAccess) . ')) ';
                }
            }
        }
        return view('users.failure-payments', compact('condition'));
    }

    public function getFailurePayments(Request $request)
    {
        $aColumns = array('invoice_id', 'service_type', 'service_name', 'customer_name', 'total_order_price', 'created_at', 'start_date', 'payment_method', 'transaction_id', 'id', 'customer_phone');
        $sIndexColumn = "id";
        $sTable = "order_masters";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = ' WHERE vendor_id = ' . $vender_id . ' AND payment_late_captured = 1 AND status = "cancelled" AND (payment_status = "pending" OR payment_status = "failure") ';
        if (Auth::user()->role == 3) {
            $sWhere .= html_entity_decode($_POST['searchValue3']);
        }
        $searchColumns = array('invoice_id', 'service_type', 'service_name', 'customer_name', 'customer_phone', 'refund_amount', 'cancel_date', 'payment_method', 'reference_id', 'bank_reference_num');
        $condition1 = '';
        if (!empty($_POST['searchValue1']) && !empty($_POST['searchValue2'])) {
            // $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
            if ($_POST['searchValue1'] == 'cancel_date') {
                $dates = explode(' - ', $_POST['searchValue2']);
                $start = date('Y-m-d', strtotime($dates[0]));
                $end = date('Y-m-d', strtotime($dates[1]));
                $condition1 .= ' AND CR.' . $_POST['searchValue1'] . ' BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"';
            } elseif ($_POST['searchValue1'] == 'service_name' || $_POST['searchValue1'] == 'customer_name' || $_POST['searchValue1'] == 'customer_phone') {
                $condition1 .= ' AND OM.' . $_POST['searchValue1'] . ' LIKE "%' . $_POST['searchValue2'] . '%"';
            } else {
                $condition1 .= ' AND CR.' . $_POST['searchValue1'] . ' LIKE "%' . $_POST['searchValue2'] . '%"';
            }
        }
        $sWhere .= $condition1;

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        // echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $check_date = date("d-M-Y", strtotime($aRow->start_date));
            if ($aRow->service_type == 'hotel' || $aRow->service_type == 'car' || $aRow->service_category == 'package') {
                $check_date = 'Check-in : ' . date("d-M-Y", strtotime($aRow->start_date)) . '<br>Check-out : ' . date("d-M-Y", strtotime($aRow->end_date));
            }
            $action_button = ($aRow->start_date >= date("Y-m-d")) ? '<br><button class="btn btn-primary m-t-5 successOrder" data-id="' . $aRow->id . '"><i class="fa fa-check"></i> success</button>' : '<br><button class="btn btn-primary m-t-5 successOrder" data-id="' . $aRow->id . '"><i class="fa fa-check"></i> success</button>';

            $room_details = json_decode($aRow->room_details, 1);
            $rooms = '';
            foreach ($room_details as $room) {
                $rooms .= $room['room_name'] . ',';
            }
            rtrim($rooms, ',');
            $service_name = ($aRow->service_type == 'hotel') ? $aRow->service_name . '<br>' . $rooms : $aRow->service_name;

            $row[] = $aRow->invoice_id;
            $row[] = strtoupper($aRow->service_type);
            $row[] = $aRow->service_name;
            $row[] = $aRow->customer_name . '<br>' . $aRow->customer_email . '<br>' . $aRow->customer_phone;
            $row[] = $aRow->total_order_price;
            $row[] = date("d-M-Y h:i a", strtotime($aRow->created_at));
            $row[] = $check_date;
            $row[] = $aRow->payment_method;
            $row[] = $aRow->transaction_id;
            $row[] = '<button class="btn btn-danger refundOrder" data-id="' . $aRow->id . '"><i class="fa fa-exchange"></i> Refund</button>'. $action_button;

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }


    public function refundOprsn(Request $request)
    {
        if ($request->request_type == 'export_refund_history') {
            $RefundData = DB::select($request->exportQuery);
            $csv = "documents/refund_report" . time() . ".csv";
            $csvname = public_path($csv);
            $headerArr = array('Booking Id', 'Invoice Slno', 'Service Type', 'Service Name', 'Check In', 'Customer Name', 'Customer Phone', 'Customer GST No', 'GST Company', 'Paid Amount', 'Refund Amount', 'Cancel Date', 'Cancel Time', 'Payment Method', 'PayU Id', 'Reference Id', 'Refund_txn_id', 'Status', 'Cancel Reason');
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($RefundData)) {
                foreach ($RefundData as $value) {
                    $data['invoice_id'] = $value->invoice_id;
                    $data['invoice_slno'] = !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A';
                    $data['service_type'] = $value->service_type;
                    $data['service_name'] = $value->service_name;
                    $data['start_date'] = date("Y-m-d", strtotime($value->start_date));

                    $data['customer_name'] = $value->customer_name;
                    $data['customer_phone'] = $value->customer_phone;
                    $data['customer_gst'] = $value->gst_regd_no;
                    $data['gst_company'] = $value->gst_company_name;
                    $data['paid_amount'] = $value->paid_amount;
                    $data['refund_amount'] = $value->refund_amount;
                    $data['cancel_date'] = date("Y-m-d", strtotime($value->cancel_date));
                    $data['cancel_time'] = date("h:i a", strtotime($value->created_at));
                    $data['payment_method'] = $value->payment_method;
                    $data['payu_id'] = $value->pg_txn_id;
                    $data['reference_id'] = $value->reference_id;
                    $data['bank_reference_num'] = $value->bank_reference_num;
                    $data['refund_status'] = $value->refund_status;
                    $data['cancel_reason'] = $value->cancel_reason;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'refund_falied_transaction') {
            $OrderMaster = OrderMaster::find($request->Id); //where(['order_id' => $request->orderId, 'status' => $request->orderStatus])->first();
            // if ($OrderMaster->service_type == 'hotel' && !(parent::checkWritePrivilege(28))) {
            //     $responce['status'] = 0;
            //     $responce['message'] = 'You are not autherised to do this operation.';
            // } elseif ($OrderMaster->service_type == 'car' && !(parent::checkWritePrivilege(30))) {
            //     $responce['status'] = 0;
            //     $responce['message'] = 'You are not autherised to do this operation.';
            // } elseif ($OrderMaster->service_type == 'tour' && !(parent::checkWritePrivilege(31))) {
            //     $responce['status'] = 0;
            //     $responce['message'] = 'You are not autherised to do this operation.';
            // } elseif ($OrderMaster->service_type == 'ticketing' && !(parent::checkWritePrivilege(32))) {
            //     $responce['status'] = 0;
            //     $responce['message'] = 'You are not autherised to do this operation.';
            if (!(parent::checkWritePrivilege(85))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                if (!empty($OrderMaster)) {
                    $OrderMaster->status = 'cancelled';
                    $OrderMaster->cancel_reason = 'Cancelled due to payment failure';
                    $OrderMaster->cancel_date = date("Y-m-d H:i:s");

                    $service_type = $OrderMaster->service_type;

                    if ($OrderMaster->service_type == 'tour') {
                        $service_type = ($OrderMaster->service_category == 'sight seeing') ? 'sight-seeing' : 'package';
                    } elseif ($OrderMaster->service_type == 'car') {
                        $service_type = 'rental';
                    } elseif ($OrderMaster->service_type == 'ticketing') {
                        $service_type = str_replace(' ', '-', trim(strtolower($OrderMaster->service_category)));
                    }
                    $refund_percent = 100;
                    $refund_amount = $OrderMaster->total_order_price;
                    $PaymentResponse = json_decode($OrderMaster->payment_error_response, 1);
                    $PaymentHistory = $PaymentResponse['transaction_details'][$OrderMaster->transaction_id];

                    $s_type = ($OrderMaster->service_type == 'car') ? 'rental' : $OrderMaster->service_type;

                    require_once public_path('paytm_lib/config_paytm.php');

                    if (!empty($OrderMaster->hdfc_key) && !empty($OrderMaster->hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                        $HDFC_KEY = $OrderMaster->hdfc_key;
                        $HDFC_SALT = $OrderMaster->hdfc_salt;
                    }

                    $command = "cancel_refund_transaction";
                    $var1 = $PaymentHistory['mihpayid'];                  //mihpayid
                    $reference_id = $var2 = date('dmY') . time();       //request id
                    $var3 = $refund_amount;                             //amount

                    $key = $HDFC_KEY;
                    $salt = $HDFC_SALT;

                    $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
                    $hash = strtolower(hash('sha512', $hash_str));

                    $r = array('key' => $key, 'hash' => $hash, 'command' => $command, 'var1' => $var1, 'var2' => $var2, 'var3' => $var3);
                    $qs = http_build_query($r);
                    $wsUrl = VERIFY_URL; //"https://test.payu.in/merchant/postservice.php?form=2";

                    $c = curl_init();
                    curl_setopt($c, CURLOPT_URL, $wsUrl);
                    curl_setopt($c, CURLOPT_POST, 1);
                    curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                    $o = curl_exec($c);
                    // if (curl_errno($c)) {
                    //     $sad = curl_error($c);
                    //     throw new Exception($sad);
                    // }
                    curl_close($c);
                    $valueSerialized = @unserialize($o);
                    $response = json_decode($o, 1);

                    if (isset($response['status']) && $response['status'] != 1) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                        echo json_encode($responce);
                        exit;
                    }

                    $result_msg = $response['msg'];
                    $refund_txn_id = isset($response['bank_ref_num']) ? $response['bank_ref_num'] : '';
                    $rquest_id = isset($response['request_id']) ? $response['request_id'] : '';

                    $RefundData = new CustomerRefund([
                        'vendor_id' => $OrderMaster->vendor_id,
                        'order_id' => $request->orderId,
                        'invoice_id' => $OrderMaster->invoice_id,
                        'order_type' => $OrderMaster->order_type,
                        'service_type' => $s_type,
                        'service_id' => $OrderMaster->service_name_id,
                        'customer_id' => $OrderMaster->customer_id,
                        'order_date' => $OrderMaster->created_at,
                        'cancel_date' => $OrderMaster->cancel_date,
                        'paid_amount' => $OrderMaster->total_order_price,
                        'refund_amount' => $refund_amount,
                        'refund_percent' => $refund_percent,
                        'payment_method' => $OrderMaster->payment_gateway,
                        'client_txn_id' => $OrderMaster->transaction_id,
                        'pg_txn_id' => $PaymentHistory['mihpayid'],
                        'refund_status' => 'PENDING',
                        'result_msg' => $result_msg,
                        'reference_id' => $rquest_id,
                        'refund_txn_id' => $refund_txn_id,
                        'response_json' => json_encode($response),
                        'payment_id' => $OrderMaster->payment_id
                    ]);
                    $RefundData->save();
                    // if ($response['status'] != 1) {
                    //     $responce['status'] = 0;
                    //     $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                    //     echo json_encode($responce);
                    //     exit;
                    // }
                    $OrderMaster->payment_status = 'success';
                    // $OrderMaster->payment_gateway_error = 0;
                    $OrderMaster->refund_amount = $refund_amount;
                    $OrderMaster->refund_tax = $OrderMaster->tax_amount;
                    $OrderMaster->save();
                    $Subject = '';
                    $Message = "<p style='color:#000000;'>Dear " . $OrderMaster->customer_name . ",</p>";
                    $Message .= "<p style='color:#000000;'>Your booking for " . $OrderMaster->service_name . " having invoice no " . $OrderMaster->invoice_id . " has been cancelled due to payment failure.</p>";
                    $Message .= "<p style='color:#000000;'>An amount of &#8377;" . number_format($refund_amount, 2) . " will be refunded soon.</p>";
                    $Vendor = User::find($OrderMaster->vendor_id);
                    $To = $OrderMaster->customer_email;
                    $service_email = '';
                    $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? $OrderMaster->gst_regd_no : 'N/A';

                    $User = User::find($OrderMaster->customer_id);
                    $mobileNumber = $OrderMaster->customer_phone;

                    if ($service_type == 'hotel') {

                        $MasterHotel = MasterHotel::find($OrderMaster->service_name_id);
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $room_html = '';
                        $room_details = json_decode($OrderMaster->room_details, 1);
                        foreach ($room_details as $value) {
                            $room_html .= $value['quantity'] . ' ' . $value['room_name'] . ', ';
                        }
                        $room_html = trim($room_html, ', ');
                        $HotelInvoice = EmailTemplate::where('ref_code', 'hotelCancelInvoice')->first();
                        $vendorGSTNo = (!empty($MasterHotel->gst_number)) ? $MasterHotel->gst_number : 'N/A';

                        $customer_address = $OrderMaster->customer_address1;
                        $customer_address .= !empty($OrderMaster->customer_city) ? ',<br>'. $OrderMaster->customer_city : '';
                        $customer_address .= !empty($OrderMaster->customer_state) ? ',<br>'. $OrderMaster->customer_state : '';
                        $customer_address .= !empty($OrderMaster->customer_country) ? ',<br>'. $OrderMaster->customer_country : '';
                        $customer_address .= !empty($OrderMaster->customer_zipcode) ? ', '. $OrderMaster->customer_zipcode : '';
                        $customer_address .= (!empty($OrderMaster->gst_regd_no)) ? '<br><u><b>GSTN No: ' . $OrderMaster->gst_regd_no . '</b></u>' : '';
                        $customer_address .= (!empty($OrderMaster->gst_company_name)) ? '<br><u><b>Company Name: ' . $OrderMaster->gst_company_name . '</b></u>' : '';

                        $Subject = $HotelInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~hoteladdress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~hotelname~", "~roomdetails~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~hotelemail~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~hotelgst~", "~usergst~", "~invoiceserial~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $customer_address, $MasterHotel->real_address, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $room_html, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $MasterHotel->contact_email, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo, $OrderMaster->invoice_serial),
                            $HotelInvoice->source
                        );
                        if ($OrderMaster->vendor_id != 3) {
                            $service_email = $MasterHotel->contact_email;
                            if (!empty($MasterHotel->additional_email)) {
                                $service_email = !empty($service_email) ? $service_email .','. $MasterHotel->additional_email : $MasterHotel->additional_email;
                            }
                        }

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'hotelCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~hoteladdress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~hotelname~", "~roomdetails~", "~checkdate~", "~hotelemail~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $MasterHotel->real_address, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $room_html, $check_date, $MasterHotel->contact_email, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                    } elseif ($service_type == 'rental') {
                        $MasterCar = MasterCar::find($OrderMaster->service_name_id);
                        $vendorGSTNo = (!empty($MasterCar->gst_number)) ? $MasterCar->gst_number : 'N/A';

                        $service_email = $MasterCar->contact_email;
                        if (!empty($MasterCar->additional_email)) {
                            $service_mail = !empty($service_mail) ? $service_mail .','. $MasterCar->additional_email : $MasterCar->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $RentalInvoice = EmailTemplate::where('ref_code', 'rentalCancelInvoice')->first();
                        $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~vendorgst~", "~usergst~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo),
                            $RentalInvoice->source
                        );

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'rentalCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                    } elseif ($service_type == 'package') {
                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $service_email = $Tour->contact_email;
                        if (!empty($Tour->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Tour->additional_email : $Tour->additional_email;
                        }
                        $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $PackageInvoice = EmailTemplate::where('ref_code', 'packageCancelInvoice')->first();
                        $Subject = $PackageInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $vendorGSTNo = (!empty($Tour->gst_number)) ? $Tour->gst_number : 'N/A';
                        $Message .= str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"), array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->service_name, $check_date, $OrderMaster->total_order_price, number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id), $PackageInvoice->source);

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'packageCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~ticketquantity~", "~canceldate~"), array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $guest_data, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))), $CustomerInvoice->source);
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                    } elseif ($service_type == 'sight-seeing') {
                        $Tour = Tour::find($OrderMaster->service_name_id);
                        $service_email = $Tour->contact_email;
                        if (!empty($Tour->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Tour->additional_email : $Tour->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        $SightseenInvoice = EmailTemplate::where('ref_code', 'sightseenCancelInvoice')->first();
                        $Subject = $SightseenInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $vendorGSTNo = (!empty($Tour->gst_number)) ? $Tour->gst_number : 'N/A';
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $SightseenInvoice->source
                        );

                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'sightseenCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~ticketquantity~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $OrderMaster->total_guests, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                    } elseif ($service_type == 'experience-ticketing' || $service_type == 'events' || $service_type == 'entry-ticket') {
                        $Ticket = Ticket::find($OrderMaster->service_name_id);
                        $service_email = $Ticket->contact_email;
                        if (!empty($Ticket->additional_email)) {
                            $service_email = !empty($service_email) ? $service_email .','. $Ticket->additional_email : $Ticket->additional_email;
                        }
                        $TicketInvoice = EmailTemplate::where('ref_code', 'ticketCancelInvoice')->first();
                        $Subject = $TicketInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date));
                        $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                        $duration = (!empty($OrderMaster->start_time)) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All Day';
                        $vendorGSTNo = (!empty($Ticket->gst_number)) ? $Ticket->gst_number : 'N/A';

                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorname~", "~vendorgst~", "~usergst~", "~vendorLogo~", "~invoiceid~", "~canceldate~", "~servicename~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~paymentmethod~", "~txnid~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $Vendor->company, $vendorGSTNo, $customerGSTNo, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), ($OrderMaster->service_name_id == '24') ? '6<sup>th</sup> ' . $OrderMaster->service_name :$OrderMaster->service_name, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', $OrderMaster->payment_method, $OrderMaster->transaction_id),
                            $TicketInvoice->source
                        );


                        if (!empty($User) && $User->access_type == 'agent') {
                            $CustomerInvoice = EmailTemplate::where('ref_code', 'ticketCancelAgentInvoice')->first();
                            if (!empty($CustomerInvoice)) {
                                $To = $User->email;
                                $msg = str_replace(
                                    array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~checkdate~", "~duration~", "~ticketquantity~", "~canceldate~"),
                                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $check_date, $duration, $guest_data, date("M d Y h:i a", strtotime($OrderMaster->cancel_date))),
                                    $CustomerInvoice->source
                                );
                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subject));
                            }
                        }
                    }elseif($service_type == 'caravan'){
                        $MasterCaravan = MasterCaravan::find($OrderMaster->service_name_id);
                        $vendorGSTNo = (!empty($MasterCaravan->gst_number)) ? $MasterCaravan->gst_number : 'N/A';

                        $service_email = $MasterCaravan->contact_email;
                        if (!empty($MasterCaravan->additional_email)) {
                            $service_mail = !empty($service_mail) ? $service_mail .','. $MasterCaravan->additional_email : $MasterCaravan->additional_email;
                        }
                        $check_date = date("M d Y", strtotime($OrderMaster->start_date)) . ' - ' . date("M d Y", strtotime($OrderMaster->end_date));
                        $RentalInvoice = EmailTemplate::where('ref_code', 'caravanCancelInvoice')->first();
                        $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                        $Message .= str_replace(
                            array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~vendorLogo~", "~invoiceid~", "~orderdate~", "~servicename~", "~quantity~", "~checkdate~", "~ordertotal~", "~refundamount~", "~paymentstatus~", "~canceldate~", "~paymentmethod~", "~txnid~", "~vendorname~", "~vendorgst~", "~usergst~"),
                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $this->site . $Vendor->photo, $OrderMaster->invoice_id, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->service_name, $OrderMaster->service_quantity, $check_date, number_format($OrderMaster->total_order_price, 2), number_format($refund_amount, 2), 'CANCELLED', date("M d Y h:i a", strtotime($OrderMaster->cancel_date)), $OrderMaster->payment_method, $OrderMaster->transaction_id, $Vendor->company, $vendorGSTNo, $customerGSTNo),
                            $RentalInvoice->source
                        );
                    }
                    OrderMaster::find($OrderMaster->id)->update(['cancel_voucher' => $Message]);
                    if (!empty($User) && $User->access_type == 'agent') {
                        $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancelAgent')->first();
                        if (!empty($SmsTemplate)) {
                            $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($OrderMaster->customer_name . ',', $OrderMaster->service_name, $OrderMaster->invoice_id, "\n", $OrderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
                            parent::sendSms($OrderMaster->customer_phone, $sms_txt, $SmsTemplate->templete_id);
                            $mobileNumber = $User->phone;
                        }
                    }
                    $SmsTemplate = SmsTemplate::where('ref_code', 'BookingCancel')->first();
                    if (!empty($SmsTemplate)) {
                        $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($OrderMaster->customer_name . ',', $OrderMaster->service_name, $OrderMaster->invoice_id, number_format($refund_amount, 2), "\n", $OrderMaster->vendor_name, "\n\n"), $SmsTemplate->source);
                        parent::sendSms($mobileNumber, $sms_txt, $SmsTemplate->templete_id);
                    }
                    $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

                    $Vendor = User::find($OrderMaster->vendor_id);
                    $admin = User::where('role', 1)->first();
                    $bcc = [$Vendor->email, $admin->email];
                    if (!empty($service_email)) {
                        $bcc = array_merge($bcc, explode(',', $service_email));
                    }
                    Mail::to($To)
                        ->bcc($bcc)
                        ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));

                    $responce['status'] = 1;
                    $responce['message'] = 'Refund initiated successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid booking Id.';
                }
            }
        } elseif ($request->request_type == 'stop_refund_process') {
            if (!(parent::checkWritePrivilege(74))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $CustomerRefund = CustomerRefund::find($request->Id);
                if (!empty($CustomerRefund)) {
                    $CustomerRefund->stop = 1;
                    $CustomerRefund->save();
                    $responce['status'] = 1;
                    $responce['message'] = 'Refund stop successful. No further refund will be initiated after current transaction failure.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to stop refund.';
                }
            }
        } elseif ($request->request_type == 'success_falied_transaction') {
            if (!(parent::checkWritePrivilege(85))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $OrderMaster = OrderMaster::find($request->Id);
                if (!empty($OrderMaster)) {
                    if ($OrderMaster->service_type == 'hotel') {
                        $room_details = json_decode($OrderMaster->room_details, 1);
                        $chko_date = ($OrderMaster->start_date == $OrderMaster->end_date) ? date("Y-m-d", strtotime($OrderMaster->end_date)) : date("Y-m-d", strtotime($OrderMaster->end_date .'-1 days'));
                        $days = ($OrderMaster->start_date == $OrderMaster->end_date) ? 1 : round((strtotime($OrderMaster->end_date) - strtotime($OrderMaster->start_date)) / (60 * 60 * 24));
                        $BlockedHotel = BlockedHotel::where('hotel_id', $OrderMaster->service_name_id)
                            ->whereBetween('block_date', [date("Y-m-d", strtotime($OrderMaster->start_date)), $chko_date])
                            ->pluck('rooms')->toArray();
                        $BlockedRooms = array();
                        if (!empty($BlockedHotel)) {
                            $BlockedRoomsStr = implode(",", $BlockedHotel);
                            $BlockedRooms = explode(",", $BlockedRoomsStr);
                            $BlockedRooms = array_unique($BlockedRooms);
                        }
                        $BookStatus = 1;
                        for ($i = 0; $i < $days; $i++) {
                            $checkDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . $i . ' days'));
                            foreach ($room_details as $key => $value) {
                                if (!empty($BlockedRooms) && in_array($key, $BlockedRooms)) {
                                    $BookStatus = 0;
                                    break 2;
                                }
                                $MasterInventory = MasterInventory::where(['date' => $checkDate, 'room_id' => $key])->first();
                                $total_available = !empty($MasterInventory) ? $MasterInventory->total_available : 0;
                                if ($total_available < $value['quantity']) {
                                    $BookStatus = 0;
                                    break 2;
                                }
                            }
                        }
                        if ($BookStatus == 0) {
                            $responce['status'] = 0;
                            $responce['message'] = 'Sorry!, rooms not available to success the booking.';
                            echo json_encode($responce);
                            exit;
                        }
                    }
                    $OrderMaster->status = 'completed';
                    $OrderMaster->cancel_reason = '';
                    $OrderMaster->cancel_date = null;
                    $OrderMaster->payment_status = 'success';

                    require_once public_path('paytm_lib/config_paytm.php');
                    if (!empty($OrderMaster->hdfc_key) && !empty($OrderMaster->hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                        $HDFC_KEY = $OrderMaster->hdfc_key;
                        $HDFC_SALT = $OrderMaster->hdfc_salt;
                    }
                    $command = "verify_payment";
                    $var1 = $OrderMaster->transaction_id;
                    $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                    $hash_verify_payment = strtolower(hash('sha512', $hash_str));

                    $r = array('key' => $HDFC_KEY, 'hash' => $hash_verify_payment, 'var1' => $var1, 'command' => $command);
                    $qs = http_build_query($r);
                    $wsUrl = VERIFY_URL;
                    $c = curl_init();
                    curl_setopt($c, CURLOPT_URL, $wsUrl);
                    curl_setopt($c, CURLOPT_POST, 1);
                    curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                    $o = curl_exec($c);
                    curl_close($c);

                    $valueSerialized = @unserialize($o);
                    $verify_response = json_decode($o, true);
                    // print_r($verify_response);exit;
                    if ($verify_response['status'] == 1 && isset($verify_response['transaction_details'][$OrderMaster->transaction_id])) {
                        $check_response = $verify_response['transaction_details'][$OrderMaster->transaction_id];
                        if ($check_response['status'] == 'success') {
                            $PaymentHistory = PaymentHistory::where('transaction_id', $OrderMaster->transaction_id)->first();
                            if (!empty($PaymentHistory)) {
                                $PaymentHistory->mihpayid = $check_response['mihpayid'];
                                $PaymentHistory->mode = $check_response['mode'];
                                $PaymentHistory->status = $check_response['status'];
                                $PaymentHistory->unmapped_status = $check_response['unmappedstatus'];
                                $PaymentHistory->card_category = isset($check_response['card_type']) ? $check_response['card_type'] : '';
                                $PaymentHistory->discount = isset($check_response['discount']) ? $check_response['discount'] : '';
                                $PaymentHistory->net_amount_debit = isset($check_response['net_amount_debit']) ? $check_response['net_amount_debit'] : '';
                                $PaymentHistory->added_on = isset($check_response['addedon']) ? $check_response['addedon'] : '';
                                $PaymentHistory->field1 = isset($check_response['field1']) ? $check_response['field1'] : '';
                                $PaymentHistory->field2 = isset($check_response['field2']) ? $check_response['field2'] : '';
                                $PaymentHistory->field3 = isset($check_response['field3']) ? $check_response['field3'] : '';
                                $PaymentHistory->field4 = isset($check_response['field4']) ? $check_response['field4'] : '';
                                $PaymentHistory->field5 = isset($check_response['field5']) ? $check_response['field5'] : '';
                                $PaymentHistory->field6 = isset($check_response['field6']) ? $check_response['field6'] : '';
                                $PaymentHistory->field7 = isset($check_response['field7']) ? $check_response['field7'] : '';
                                $PaymentHistory->field8 = isset($check_response['field8']) ? $check_response['field8'] : '';
                                $PaymentHistory->field9 = isset($check_response['field9']) ? $check_response['field9'] : '';
                                $PaymentHistory->payment_source = isset($check_response['payment_source']) ? $check_response['payment_source'] : '';
                                $PaymentHistory->PG_TYPE = isset($check_response['PG_TYPE']) ? $check_response['PG_TYPE'] : '';
                                $PaymentHistory->bank_ref_num = isset($check_response['bank_ref_num']) ? $check_response['bank_ref_num'] : '';
                                $PaymentHistory->bank_code = isset($check_response['bankcode']) ? $check_response['bankcode'] : '';
                                $PaymentHistory->error = isset($check_response['error_code']) ? $check_response['error_code'] : '';
                                $PaymentHistory->error_Message = isset($check_response['error_Message']) ? $check_response['error_Message'] : '';
                                $PaymentHistory->name_on_card = isset($check_response['name_on_card']) ? $check_response['name_on_card'] : '';
                                $PaymentHistory->card_number = isset($check_response['card_no']) ? $check_response['card_no'] : '';
                                // $PaymentHistory->cardhash = $request->cardhash;
                                $PaymentHistory->payment_response = $o;
                                $PaymentHistory->save();
                            }
                            $payment_method = '';
                            if (!empty($check_response['bankcode']))
                                $payment_method .= $check_response['bankcode'];
                            elseif (!empty($check_response['mode']))
                                $payment_method .= $check_response['mode'];
                            elseif (!empty($check_response['PG_TYPE']))
                                $payment_method .= $check_response['PG_TYPE'];
                            else
                                $payment_method .= 'N/A';


                            if (!empty($PaymentHistory->field8) && $payment_method == 'UPI') {
                                $payment_method .= '('. $PaymentHistory->field8 .')';
                            }
                            $OrderMaster->payment_method = $payment_method;

                            $Vendor_mail = array();
                            $Vendor = User::find($OrderMaster->vendor_id);
                            array_push($Vendor_mail, $Vendor->email);

                            $To = $OrderMaster->customer_email;
                            $Subject = $Message = $service_mail = '';
                            $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? $OrderMaster->gst_regd_no : '';
                            $customerGSTCompany = (!empty($OrderMaster->gst_company_name)) ? '<u><b>Company Name: '. $OrderMaster->gst_company_name .'</b></u>' : '';

                            // Generate Qr Code
                            require_once public_path('QrCode/generateQrCode.php');
                            $QrCodeData = array(
                                'invoiceId' => $OrderMaster->invoice_id,
                                'orderId' => $OrderMaster->order_id,
                                'txnId' => $OrderMaster->transaction_id,
                                'serviceType' => $OrderMaster->service_type,
                            );
                            $QrCode = generateQrCode(json_encode($QrCodeData));
                            $OrderMaster->qr_code = $QrCode;
                            $OrderMaster->qr_verified = 0;

                            require_once public_path('pushNotification.php');
                            $MasterHotel = $CarDetails = $TourDetails = $TicketDetails = array();
                            $sms_txt = $sms_txt_admin = $manager_contact = $reception_contact = $user_templete_id = '';
                            $SmsTemplate = SmsTemplate::where('ref_code', 'BookingConfirmUser')->first();
                            if (!empty($SmsTemplate)) {
                                $user_templete_id = $SmsTemplate->templete_id;
                                $var1 = $OrderMaster->customer_name;
                                $var2 = $OrderMaster->invoice_id;
                                $var4 = $OrderMaster->service_name;
                                $var4 = (strlen($var4) > 30) ? substr(utf8_encode($var4), 0, 27) .'...' : $var4;
                                $var5 = $OrderMaster->invoice_id;
                                $var6 = "\n". $OrderMaster->vendor_name;
                                $var8 = " \n\n";
                                $var3 = $var5 = $var7 = '';
                                if ($OrderMaster->service_type == 'hotel') {
                                    $var3 = ($OrderMaster->total_rooms > 1) ? $OrderMaster->total_rooms .' rooms' : $OrderMaster->total_rooms .' room';

                                    $var5 = date("d M Y", strtotime($OrderMaster->start_date)) .' to '. date("d M Y", strtotime($OrderMaster->end_date));
                                    $MasterHotel = MasterHotel::find($OrderMaster->service_name_id);
                                    $manager_contact = $MasterHotel->contact_number;
                                    $reception_contact = $MasterHotel->reception_contact;
                                    $var7 = $MasterHotel->reception_contact;
                                } elseif ($OrderMaster->service_type == 'car') {
                                    $var5 = date("d M Y", strtotime($OrderMaster->start_date)) .' to '. date("d M Y", strtotime($OrderMaster->end_date));
                                    $CarDetails = MasterCar::find($OrderMaster->service_name_id);
                                    $var7 = $CarDetails->contact_number;
                                    $manager_contact = $CarDetails->contact_number;
                                } elseif ($OrderMaster->service_type == 'tour' && $OrderMaster->service_category == 'sight seeing') {
                                    $var5 = date("d M Y", strtotime($OrderMaster->start_date));
                                    $TourDetails = Tour::find($OrderMaster->service_name_id);
                                    $var7 = $TourDetails->contact_number;
                                    $manager_contact = $TourDetails->contact_number;
                                } elseif ($OrderMaster->service_type == 'tour' && $OrderMaster->service_category == 'package') {
                                    $var5 = date("d M Y", strtotime($OrderMaster->start_date)) .' to '. date("d M Y", strtotime($OrderMaster->end_date));
                                    $TourDetails = Tour::find($OrderMaster->service_name_id);
                                    $var7 = $TourDetails->contact_number;
                                    $manager_contact = $TourDetails->contact_number;
                                } elseif ($OrderMaster->service_type == 'ticketing') {
                                    $var5 = ($OrderMaster->service_name_id != '24') ? date("d M Y", strtotime($OrderMaster->start_date)) .'('. $OrderMaster->start_time .'-'. $OrderMaster->end_time .')' : date("d M Y", strtotime($OrderMaster->start_date));
                                    $TicketDetails = Ticket::find($OrderMaster->service_name_id);
                                    $var7 = $TicketDetails->contact_number;
                                    $manager_contact = $TicketDetails->contact_number;
                                } elseif ($OrderMaster->service_type == 'restaurant') {
                                    $services = json_decode($OrderMaster->room_request, 1);
                                    $var2 = (count($services) == 1) ? '1 item' : count($services) .' items';
                                } elseif ($OrderMaster->service_type == 'merchant') {
                                    $services = json_decode($OrderMaster->room_request, 1);
                                    $var2 = (count($services) == 1) ? '1 item' : count($services) .' items';
                                    $var7 = "\nOdisha Tourism";
                                }elseif ($OrderMaster->service_type == 'caravan') {
                                    $var5 = date("d M Y", strtotime($OrderMaster->start_date)) .' to '. date("d M Y", strtotime($OrderMaster->end_date));
                                    $CarDetails = MasterCaravan::find($OrderMaster->service_name_id);
                                    $var7 = $CarDetails->contact_number;
                                    $manager_contact = $CarDetails->contact_number;
                                }
                                $sms_txt = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~", "~var8~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7, $var8), $SmsTemplate->source);
                                parent::sendSms($OrderMaster->customer_phone, $sms_txt, $user_templete_id);
                            }
                            $invoicemap = '';
                            if ($OrderMaster->service_type == 'hotel') {
                                $MasterHotel = MasterHotel::find($OrderMaster->service_name_id);
                                $invoice_serial = $OrderMaster->invoice_serial;

                                if (date("Y-m-d", strtotime($OrderMaster->created_at)) >= "2023-04-01" && $OrderMaster->vendor_id == 1) {
                                    $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))
                                                ->where(['service_type' => 'hotel', 'service_name_id' => $OrderMaster->service_name_id, 'payment_status' => 'success'])  // 'vendor_id' => 3, , 'payment_gateway' => 'hdfc'
                                                ->where('status', '!=', 'partially-cancelled')
                                                ->where('created_at', '>=', date("Y-m-d", strtotime("2023-04-01")))
                                                ->first();
                                    if ($LastOrder->totOrder > 0) {
                                        $LastInvoiceId = (int)$LastOrder->totOrder + 1;
                                        $invoice_serial = $MasterHotel->serial_prefix .'0'. $LastInvoiceId;
                                    } else {
                                        $invoice_serial = $MasterHotel->serial_prefix .'01';
                                    }
                                } elseif ($OrderMaster->vendor_id == 3) {
                                    $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))
                                                ->where(['service_type' => 'hotel', 'service_name_id' => $OrderMaster->service_name_id, 'payment_status' => 'success'])  // 'vendor_id' => 3, , 'payment_gateway' => 'hdfc'
                                                ->where('status', '!=', 'partially-cancelled')
                                                ->where('created_at', '>=', date("Y-m-d", strtotime("2023-09-14")))
                                                ->first();
                                    if ($LastOrder->totOrder > 0) {
                                        $LastInvoiceId = (int)$LastOrder->totOrder + 1;
                                        $invoice_serial = $MasterHotel->serial_prefix .'0'. $LastInvoiceId;
                                    } else {
                                        $invoice_serial = $MasterHotel->serial_prefix .'01';
                                    }
                                }
                                OrderMaster::find($OrderMaster->id)->update(['invoice_serial' => $invoice_serial, 'payment_status' => $request->status, 'status' => 'completed']);

                                $OrderDeatilData = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                                foreach ($OrderDeatilData as $detail) {
                                    $MasterInventory = MasterInventory::where(['room_id' => $detail->service_item_id, 'date' => $detail->start_date])->first();
                                    if (!empty($MasterInventory)) {
                                        if ($MasterInventory->total_available > 0) {
                                            $MasterInventory->total_available -= 1;
                                            $MasterInventory->total_booked += 1;
                                            $MasterInventory->total_online_completed += 1;
                                        } else {
                                            $MasterInventory->total_booked += 1;
                                            $MasterInventory->total_online_completed += 1;
                                        }
                                        $MasterInventory->save();
                                    }
                                }
                                $orderDetailUpdate = DB::table("order_details")->where('order_master_id', $OrderMaster->id)->update(['status' => 'completed']);

                                $difference = strtotime(date("Y-m-d", strtotime($OrderMaster->end_date))) - strtotime(date("Y-m-d", strtotime($OrderMaster->start_date)));
                                $totalNight = floor($difference / (60 * 60 * 24));
                                $totalNight = ($totalNight == 0) ? 1 : $totalNight;
                                $RoomDetails = json_decode($OrderMaster->room_details, 1);
                                foreach ($RoomDetails as $roomId => $room) {
                                    $HotelRoom = HotelRoom::find($roomId);
                                    for($i = 0; $i < $totalNight; $i++) {
                                        $checkInDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . $i . ' days'));
                                        $MasterInventory = MasterInventory::where(['hotel_id' => $OrderMaster->service_name_id,'room_id' => $roomId,'date' => $checkInDate])->first();
                                        if (!empty($MasterInventory)) {
                                            $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $checkInDate])
                                                                ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                                ->first();
                                            if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id)) {
                                                $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                            ->where('block_date', $MasterInventory->date)
                                                            ->where('rooms', $MasterInventory->room_id)
                                                            ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                            ->get()->toArray();
                                                $closed = 'false';
                                                if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                                    $closed = "true";
                                                }
                                                $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                            <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                                <AvailRateUpdate locatorID="1">
                                                                    <DateRange from="'. $MasterInventory->date . '" to="' . $MasterInventory->date .'"/>
                                                                    <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                                </AvailRateUpdate>
                                                            </AvailRateUpdateRQ>';
                                                DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('online_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                            }
                                            if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id)) {
                                                $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                                ->where('block_date', $MasterInventory->date)
                                                                ->where('rooms', $MasterInventory->room_id)
                                                                ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                                ->get()->toArray();
                                                $closed = "Open";
                                                if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                                    $closed = "Close";
                                                }
                                                $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                                if (!empty($InvRateplanData)) {
                                                    // Availability
                                                    $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                                <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                                    <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                                        <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                                        <RestrictionStatus Status="'. $closed .'" />
                                                                    </AvailStatusMessage>
                                                                </AvailStatusMessages>
                                                            </OTA_HotelAvailNotifRQ>';
                                                    DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('online_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                                }
                                            }
                                        }
                                    }
                                }

                                $HotelInvoice = EmailTemplate::where('ref_code','hotelInvoice')->first();
                                if (!empty($HotelInvoice)) {
                                    $Subject = $HotelInvoice->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                    $room_html = '';
                                    $room_details = json_decode($OrderMaster->room_details, 1);
                                    foreach ($room_details as $value) {
                                        $room_html .= $value['quantity'] .' x '. $value['room_name'] .', ';
                                    }
                                    $room_html = trim($room_html, ', ');
                                    $check_date = date("d M Y", strtotime($OrderMaster->start_date)) .' - '. date("d M Y", strtotime($OrderMaster->end_date));
                                    $sgst = $cgst = 0;

                                    $hotelGSTNo = (!empty($MasterHotel->gst_number)) ? $MasterHotel->gst_number : 'N/A';
                                    $hotelRegdCompany = (!empty($MasterHotel->gst_legal_name)) ? $MasterHotel->gst_legal_name : 'N/A';

                                    $OrderDeatil = OrderDetail::select('service_item_name', DB::raw('count(service_item_id) as totalRoom'), DB::raw('sum(service_item_id) as totQty'), DB::raw('sum(unit_total_price - total_extrabed_price) as totalRoomPrice'), DB::raw('sum(total_extrabed_price) as totextraBedPrice'), DB::raw('sum(extra_bed) as extraBed'))
                                            ->where('order_master_id', $OrderMaster->id)
                                            ->groupBy('service_item_id')
                                            ->get();
                                    $room_pricing = '';
                                    foreach ($OrderDeatil as $value) {
                                        $rate = $value->totalRoomPrice / $value->totalRoom;
                                        $roomQty = $value->totalRoom / $totalNight;
                                        $extraPerson = $value->extraBed / $totalNight;

                                        $room_pricing .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMaster->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMaster->service_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->service_item_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $check_date . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $roomQty . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($rate, 2) . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($value->totalRoomPrice, 2) . '</td></tr>';
                                        if ($value->extraBed > 0) {
                                            $room_pricing .= '<tr><td align="center" valign="middle" colspan="2" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000"></td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">Extra Person ('. $extraPerson .')</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $check_date . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000"></td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($value->totextraBedPrice / $value->extraBed, 2) . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . number_format($value->totextraBedPrice, 2) . '</td></tr>';
                                        }
                                    }
                                    $arrival_data = !empty($OrderMaster->expected_arrival_time) ? '<td><strong>Expected Arrival:</strong> '. $OrderMaster->expected_arrival_time .'</td>' : '';
                                    $arrival_data .= !empty($OrderMaster->need_pickup) ? '<td><strong>Need Pickup:</strong> '. $OrderMaster->need_pickup .'</td>' : '';

                                    $maplocation = 'https://maps.google.com/maps?q=loc:'. $MasterHotel->map_lat .','. $MasterHotel->map_lng;
                                    $mapImage = $this->site . 'images/frontend/MAP.jpg';
                                    $invoicemap = '<p style="text-align:right;"><a href="'. $maplocation .'" target="_blank"><img style="border:1px solid #000;padding:6px;border-radius:4px" width="80" height="80" src="'. $mapImage .'" alt=""></a></p>';

                                    $ConfirmTemplate = EmailTemplate::where('ref_code','hotelConfirmMail')->first();
                                    $agent_mail_body = '';
                                    $guest_html = ($OrderMaster->total_child > 0) ? '<strong>Adult: </strong>'. $OrderMaster->total_adults .', <strong>Child (Age 6y and below): </strong>'. $OrderMaster->total_child : '<strong>Adult:</strong>'. $OrderMaster->total_adults;
                                    $service_mail = $MasterHotel->contact_email;
                                    if (!empty($MasterHotel->additional_email)) {
                                        $service_mail = !empty($service_mail) ? $service_mail .','. $MasterHotel->additional_email : $MasterHotel->additional_email;
                                    }
                                    if (!empty($ConfirmTemplate)) {
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $agent_mail_body = $msg = str_replace(array("~vendorLogo~", "~username~", "~hotelname~", "~checkindate~", "~checkintime~", "~checkoutdate~", "~checkouttime~", "~nights~", "~totalguest~", "~roomdetails~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~hoteladdress~", "~arrivaldetails~", "~invoiceid~", "~bookingqrCode~", "~maplocation~", "~mapimage~", "~managername~", "~contactno~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), $MasterHotel->check_in_time, date("d M Y", strtotime($OrderMaster->end_date)), $MasterHotel->check_out_time, $totalNight, $guest_html, $room_html, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $MasterHotel->terms_conditions, $MasterHotel->real_address, $arrival_data, '<h3>'. $OrderMaster->service_name .'</h3><b>Booking ID : '. $OrderMaster->invoice_id .'</b>', $this->site . $OrderMaster->qr_code, $maplocation, $mapImage, $MasterHotel->manager_name, $MasterHotel->reception_contact), $ConfirmTemplate->source);
                                        $OrderMaster->confimation_voucher = $msg;
                                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                                        if ($OrderMaster->vendor_id == 3) {
                                            $service_mail = '';
                                            try {
                                                Mail::to($To)->bcc($MasterHotel->contact_email)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                            }
                                            catch(\Exception $e) {}
                                        } else {
                                            try {
                                                Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                            }
                                            catch(\Exception $e) {}
                                        }
                                    }

                                    $User = User::find($OrderMaster->customer_id);
                                    if (!empty($User) && $User->access_type == 'agent') {
                                        $To = $User->email;
                                        parent::sendSms($User->phone, $sms_txt, $user_templete_id);
                                    }
                                    $customer_address = $OrderMaster->customer_address1;
                                    $customer_address .= !empty($OrderMaster->customer_city) ? ',<br>'. $OrderMaster->customer_city : '';
                                    $customer_address .= !empty($OrderMaster->customer_state) ? ',<br>'. $OrderMaster->customer_state : '';
                                    $customer_address .= !empty($OrderMaster->customer_country) ? ',<br>'. $OrderMaster->customer_country : '';
                                    $customer_address .= !empty($OrderMaster->customer_zipcode) ? ', '. $OrderMaster->customer_zipcode : '';
                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~hoteladdress~", "~hotelemail~", "~hotelgst~", "~hotelgstcompany~", "~vendorLogo~", "~orderdate~", "~roomfeesdetails~", "~totalserviceprice~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~couponname~", "~payuid~"),
                                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $customer_address, $customerGSTNo, $customerGSTCompany, $MasterHotel->real_address, $MasterHotel->contact_email, $hotelGSTNo, $hotelRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)) .'<br><b>'. $invoice_serial .'</b>', $room_pricing, number_format($OrderMaster->total_service_price, 2), number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, $OrderMaster->coupon_name, $PaymentHistory->mihpayid), $HotelInvoice->source);
                                    $OrderMaster->invoice = $Message;

                                }
                                $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmHotel')->first();
                                if (!empty($SmsTemplateAdmin)) {
                                    $var1 = $OrderMaster->total_rooms .' rooms';
                                    $var2 = $OrderMaster->service_name;
                                    $var2 = (strlen($var2) > 30) ? substr(utf8_encode($var2), 0, 27) .'...' : $var2;
                                    $var3 = $OrderMaster->customer_name;
                                    $var4 = ' '. $OrderMaster->customer_phone;
                                    $var5 = date("d M Y", strtotime($OrderMaster->start_date));
                                    $var6 = date("d M Y", strtotime($OrderMaster->end_date));
                                    $var7 = $OrderMaster->invoice_id;

                                    $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7), $SmsTemplateAdmin->source);
                                    $sms_recipient = array();

                                    if (!empty($manager_contact))
                                        array_push($sms_recipient, $manager_contact);
                                    if (!empty($reception_contact))
                                        array_push($sms_recipient, $reception_contact);
                                    if (!empty($MasterHotel->additional_phone))
                                        $sms_recipient = array_merge($sms_recipient, explode(",", $MasterHotel->additional_phone));
                                    if (!empty($sms_recipient)) {
                                        $to_sms = implode(',', array_slice($sms_recipient,0,3));
                                        parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                                    }
                                }
                            }
                            elseif ($OrderMaster->service_type == 'car') {
                                $orderDetailUpdate = DB::table("order_details")->where('order_master_id', $OrderMaster->id)->update(['status' => 'completed']);
                                $CarDetails = MasterCar::find($OrderMaster->service_name_id);
                                $RentalInvoice = EmailTemplate::where('ref_code', 'rentalInvoice')->first();
                                if (!empty($RentalInvoice)) {
                                    $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                                    $check_date = date("d M Y", strtotime($OrderMaster->start_date)) .' '. date("h:i a", strtotime($OrderMaster->start_time)) .' - <br>' . date("d M Y", strtotime($OrderMaster->end_date)) .' '. date("h:i a", strtotime($OrderMaster->end_time));

                                    $vendorGSTNo = (!empty($CarDetails->gst_number)) ? $CarDetails->gst_number : 'N/A';
                                    $vendorRegdCompany = (!empty($CarDetails->gst_legal_name)) ? $CarDetails->gst_legal_name : 'N/A';
                                    $guide_text = ($OrderMaster->days_for_guide > 0) ? $OrderMaster->days_for_guide : 'N/A';

                                    $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                                    $routes = '';$routes_agent = ''; $route_confirm = '';$count = 1;

                                    foreach($OrderDetails as $route) {
                                        $room_price = $route->unit_total_price;

                                        $routes .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMaster->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $OrderMaster->service_name .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y h:i a", strtotime($route->start_date .' '. $route->start_time)) .' - <br>'. date("d M Y h:i a", strtotime($route->end_date .' '. $route->end_time)) .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $route->distance . ' KM</td><td align="right" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. number_format($route->unit_total_price , 2) .'</td></tr>';
                                        $routes_agent .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $route->pickup_address .', '. $route->pickup_city . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $route->drop_address .', '. $route->drop_city .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y h:i a", strtotime($route->start_date .' '. $route->start_time)) .' - <br>'. date("d M Y h:i a", strtotime($route->end_date .' '. $route->end_time)) .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $route->distance . ' KM</td></tr>';
                                        $route_confirm .= '<tr><td width="10%" rowspan="3">'. $count .'</td><td><strong>Pick up Location</strong>: ' . $route->pickup_address .', '. $route->pickup_city . '</td><td><strong>Drop Location</strong>: '. $route->drop_address .', '. $route->drop_city .'</td></tr><tr><td><strong>Start Date</strong>: ' . date("d M Y h:i a", strtotime($route->start_date .' '. $route->start_time)) .'</td><td><strong>End Date</strong>: '. date("d M Y h:i a", strtotime($route->end_date .' '. $route->end_time)) .'</td></tr><tr><td colspan="2"><strong>Distance</strong>: ' . $route->distance . ' KM</td></tr><tr><td colspan="3">&nbsp;</td></tr>';
                                        $count++;

                                        $difference = strtotime(date("Y-m-d", strtotime($route->end_date))) - strtotime(date("Y-m-d", strtotime($route->start_date)));
                                        $days = floor($difference / (60 * 60 * 24));
                                        $cal_day = ($days == 0) ? 1 : $days + 1;
                                        for($i = 0; $i < $cal_day; $i++) {
                                            $checkDate = date("Y-m-d", strtotime($route->start_date .' + '. $i .' days'));
                                            $MasterInventory = RentalMasterInventory::where(["date" => $checkDate, "car_id" => $route->service_name_id])->first();
                                            if (!empty($MasterInventory)) {
                                                if ($MasterInventory->total_available > 0) {
                                                    $MasterInventory->total_available -= 1;
                                                    $MasterInventory->total_booked += 1;
                                                    $MasterInventory->total_online_completed += 1;
                                                } else {
                                                    $MasterInventory->total_booked += 1;
                                                    $MasterInventory->total_online_completed += 1;
                                                }
                                                $MasterInventory->save();
                                            }
                                        }
                                    }
                                    $maps = '';
                                    $CarBooking = CarBooking::where('booking_id', $OrderMaster->order_id)->first();
                                    if ($CarBooking->rental_type != 'user_defined') {
                                        $maps = '<td align="left" valign="top" style="font-family:Arial, Helvetica, sans-serif; color:#000; font-size:12px; line-height:22px;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" colspan="2" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Route</strong></th></tr><tr><th align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Address</strong></th><th align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>City</strong></th></tr>';
                                        $travel_route = json_decode($OrderMaster->travel_route, 1);
                                        foreach($travel_route as $rmap) {
                                            $maps .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $rmap['dropPonitDetails'] . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $rmap['dropPointCity'] .'</td>';
                                        }
                                        $maps .= '</table></td>';
                                    }
                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~orderdetails~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~guidecharge~", "~payuid~"),
                                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $routes, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, number_format($OrderMaster->guide_charge, 2), $PaymentHistory->mihpayid), $RentalInvoice->source);
                                    $service_mail = $CarDetails->contact_email;
                                    if (!empty($CarDetails->additional_email)) {
                                        $service_mail = !empty($service_mail) ? $service_mail .','. $CarDetails->additional_email : $CarDetails->additional_email;
                                    }
                                    $OrderMaster->invoice = $Message;

                                    $User = User::find($OrderMaster->customer_id);
                                    if (!empty($User) && $User->access_type == 'agent') {
                                        $CustomerInvoice = EmailTemplate::where('ref_code', 'rentalAgentInvoice')->first();
                                        if (!empty($CustomerInvoice)) {
                                            $To = $User->email;
                                            $Subj = $CustomerInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                                            $agent_name = str_replace(array('Agent Discount (', ')'), array('', ''), $OrderMaster->coupon_name);
                                            $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~termsconditions~", "~guideservice~", "~invoiceid~", "~agentname~"),
                                                    array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, $route_confirm, $CarDetails->terms_conditions, $guide_text, $OrderMaster->invoice_id, $agent_name), $CustomerInvoice->source);
                                            try {
                                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subj));
                                            }
                                            catch(\Exception $e) {}
                                            parent::sendSms($User->phone, $sms_txt, $user_templete_id);
                                        }
                                    }
                                    $ConfirmTemplate = EmailTemplate::where('ref_code','rentalConfirmMail')->first();
                                    if (!empty($ConfirmTemplate)) {
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~guideservice~", "~invoiceid~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, $route_confirm, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $CarDetails->terms_conditions, $guide_text, $OrderMaster->invoice_id), $ConfirmTemplate->source);
                                        $OrderMaster->confimation_voucher = $msg;
                                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                                        try {
                                            Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                        }
                                        catch(\Exception $e) {}
                                    }
                                }
                                $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmPackageRental')->first();
                                if (!empty($SmsTemplateAdmin)) {
                                    $var1 = $OrderMaster->service_name;
                                    $var1 = (strlen($var1) > 30) ? substr(utf8_encode($var1), 0, 27) .'...' : $var1;
                                    $var2 = $OrderMaster->customer_name;
                                    $var3 = $OrderMaster->customer_phone;
                                    $var4 = date("d M Y", strtotime($OrderMaster->start_date));
                                    $var5 = date("d M Y", strtotime($OrderMaster->end_date));
                                    $var6 = $OrderMaster->invoice_id;

                                    $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($var1, $var2, $var3, $var4, $var5, $var6), $SmsTemplateAdmin->source);
                                    $sms_recipient = array();
                                    if (!empty($manager_contact))
                                        array_push($sms_recipient, $manager_contact);
                                    if (!empty($CarDetails->additional_phone))
                                        $sms_recipient = array_merge($sms_recipient, explode(",", $CarDetails->additional_phone));
                                    if (!empty($sms_recipient)) {
                                        $to_sms = implode(',', array_slice($sms_recipient,0,3));
                                        parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                                    }
                                }
                            }
                            elseif ($OrderMaster->service_type == 'tour') {
                                $TourDetails = Tour::find($OrderMaster->service_name_id);
                                $vendorGSTNo = (!empty($TourDetails->gst_number)) ? $TourDetails->gst_number : 'N/A';
                                $vendorRegdCompany = (!empty($TourDetails->gst_legal_name)) ? $TourDetails->gst_legal_name : 'N/A';

                                if ($OrderMaster->service_category == 'sight seeing') {
                                    $SightseenInvoice = EmailTemplate::where('ref_code','sightseenInvoice')->first();
                                    $Subject = $SightseenInvoice->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                    $check_date = date("d M Y", strtotime($OrderMaster->start_date));
                                    $tour_include = json_decode($TourDetails->include, 1);
                                    $tour_exclude = json_decode($TourDetails->exclude, 1);
                                    $inc_html = $exc_html = '';
                                    foreach ($tour_include as $inc) {
                                        $inc_html .= '<tr>';
                                        if (!is_null($inc['title'])) {
                                            $inc_html .= '<th align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000"><strong>'. $inc['title'] .'</strong></th>'
                                                        .'<td align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000">'. $inc['content'] .'</td>';
                                        } else {
                                            $inc_html .= '<td colspan="2" align="left" valign="middle" style="padding: 10px;color: #000;border-right:1px solid #000; border-bottom:1px solid #000">'. $inc['content'] .'</td>';
                                        }
                                        $inc_html .= '</tr>';
                                    }
                                    foreach ($tour_exclude as $exc) {
                                        $exc_html .= '<tr>';
                                        if (!is_null($exc['title'])) {
                                            $exc_html .= '<th align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000"><strong>'. $exc['title'] .'</strong></th>'
                                                        .'<td align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $exc['content'] .'</td>';
                                        } else {
                                            $exc_html .= '<td colspan="2" align="left" valign="middle" style="padding: 10px;color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $exc['content'] .'</td>';
                                        }
                                        $exc_html .= '</tr>';
                                    }
                                    $gst_amount = $OrderMaster->tax_amount;
                                    $cgst = $sgst = number_format($gst_amount / 2, 2);
                                    $unit_price = number_format($OrderMaster->total_service_price / $OrderMaster->total_guests, 2);

                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~ticketquantity~", "~unitprice~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~payuid~"),
                                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date, $OrderMaster->total_guests, $unit_price, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount. 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, $PaymentHistory->mihpayid), $SightseenInvoice->source);
                                    $OrderMaster->invoice = $Message;

                                    $User = User::find($OrderMaster->customer_id);
                                    if (!empty($User) && $User->access_type == 'agent') {
                                        $CustomerInvoice = EmailTemplate::where('ref_code', 'sightseenAgentInvoice')->first();
                                        if (!empty($CustomerInvoice)) {
                                            $To = $User->email;
                                            $Subj = $CustomerInvoice->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                            $agent_name = str_replace(array('Agent Discount (', ')'), array('', ''), $OrderMaster->coupon_name);
                                            $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkintime~", "~totalguest~", "~checkouttime~","~termsconditions~", "~tourinclude~", "~tourexclude~", "~invoiceid~", "~agentname~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), date("h:i a", strtotime($TourDetails->duration_start .' '. $TourDetails->duration_start_text)), $OrderMaster->total_guests, date("h:i a", strtotime($TourDetails->duration_end .' '. $TourDetails->duration_end_text)), $TourDetails->terms_conditions, $inc_html, $exc_html, $OrderMaster->invoice_id, $agent_name), $CustomerInvoice->source);
                                            try {
                                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subj));
                                            }
                                            catch(\Exception $e) {}
                                            parent::sendSms($User->phone, $sms_txt, $user_templete_id);
                                        }
                                    }
                                    $seat_no = 'N/A';
                                    $seatDate = parent::maxDateForSeat($OrderMaster->service_name_id);
                                    if ($seatDate < $OrderMaster->start_date) {
                                        $CustomerSeat = OrderMaster::select(DB::raw('sum(total_guests) as totSeat'))
                                            ->where(['service_type' => 'tour', 'service_name_id' => $OrderMaster->service_name_id, 'payment_status' => 'success'])
                                            // ->where('status', '!=', 'partially-cancelled')
                                            ->where('start_date', date("Y-m-d", strtotime($OrderMaster->start_date)))
                                            ->first();
                                        $seatArr = array();
                                        $i = 1;
                                        while ($i <=  $OrderMaster->total_guests) {
                                            array_push($seatArr, $CustomerSeat->totSeat + $i);
                                            $i++;
                                        }
                                        $seat_no = implode(',', $seatArr);
                                        OrderMaster::find($OrderMaster->id)->update(['gate_number' => $seat_no, 'payment_status' => 'success', 'status' => 'completed']);
                                    }

                                    if ($TourDetails->id == '34' || $TourDetails->id == '35' || $TourDetails->id == '36'){
                                        $seat_no = 'N/A';
                                    }
                                    $ConfirmTemplate = EmailTemplate::where('ref_code','sightseenConfirmMail')->first();
                                    if (!empty($ConfirmTemplate)) {
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkintime~", "~totalguest~", "~checkouttime~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~tourinclude~", "~tourexclude~", "~invoiceid~", "~seatno~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), date("h:i a", strtotime($TourDetails->duration_start .' '. $TourDetails->duration_start_text)), $OrderMaster->total_guests, date("h:i a", strtotime($TourDetails->duration_end .' '. $TourDetails->duration_end_text)), number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $TourDetails->terms_conditions, $inc_html, $exc_html, $OrderMaster->invoice_id, $seat_no), $ConfirmTemplate->source);
                                        $OrderMaster->confimation_voucher = $msg;
                                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                                        try {
                                            Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                        }
                                        catch(\Exception $e) {}
                                    }
                                    $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmSightSeeing')->first();
                                    if (!empty($SmsTemplateAdmin)) {
                                        $var1 = $OrderMaster->total_guests .' persons';
                                        $var2 = $OrderMaster->service_name;
                                        $var2 = (strlen($var2) > 30) ? substr(utf8_encode($var2), 0, 27) .'...' : $var2;
                                        $var3 = $OrderMaster->customer_name;
                                        $var4 = $OrderMaster->customer_phone;
                                        $var5 = date("d M Y", strtotime($OrderMaster->start_date));
                                        $var6 = $OrderMaster->invoice_id;
                                        $var7 = "\n\n";

                                        $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($var1, $var2, $var3, $var4, $var5, $var6, $var7), $SmsTemplateAdmin->source);
                                        $sms_recipient = array();
                                        if (!empty($manager_contact))
                                            array_push($sms_recipient, $manager_contact);
                                        if (!empty($TourDetails->additional_phone))
                                            $sms_recipient = array_merge($sms_recipient, explode(",", $TourDetails->additional_phone));
                                        if (!empty($sms_recipient)) {
                                            $to_sms = implode(',', array_slice($sms_recipient,0,3));
                                            parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                                        }
                                    }
                                }
                                else {
                                    $Itinerary = json_decode($TourDetails->itinerary, 1);
                                    $difference = strtotime($OrderMaster->end_date) - strtotime($OrderMaster->start_date);
                                    $days = round($difference / (60 * 60 * 24));
                                    $total_adult = $OrderMaster->total_adults;
                                    $temp = 0;
                                    $count = 0;
                                    foreach ($Itinerary as $itnr) {
                                        if ($itnr['hotel'] != 'No Accommodation') {
                                            $checkInDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . $temp . ' days'));
                                            $checkOutDate = date("Y-m-d", strtotime($OrderMaster->start_date . ' + ' . ($temp + 1) . ' days'));
                                            $room_quantity = ($total_adult > 3) ? 2 : 1;
                                            $MasterHotel = array();
                                            if ($itnr['hotel'] != 'other' && $itnr['hotel'] != 'No Accommodation' && !empty($itnr['hotel'])) {
                                                $MasterHotel = MasterHotel::find($itnr['hotel']);
                                                $HotelRoom = HotelRoom::find($itnr['hotelRoom']);
                                                $MasterInventory = MasterInventory::where(['hotel_id' => $itnr['hotel'], 'room_id' => $itnr['hotelRoom'], 'date' => $checkInDate])->first();
                                                if (!empty($MasterInventory)) {
                                                    if ($MasterInventory->total_available > 0) {
                                                        $MasterInventory->total_available -= $room_quantity;
                                                        $MasterInventory->total_booked += $room_quantity;
                                                        $MasterInventory->total_tour_booking += $room_quantity;
                                                    } else {
                                                        $MasterInventory->total_booked += $room_quantity;
                                                        $MasterInventory->total_tour_booking += $room_quantity;
                                                    }
                                                    $MasterInventory->save();

                                                    $FullBlockData = BlockedHotel::where(['hotel_id' => $MasterHotel->id, 'block_date' => $MasterInventory->date])
                                                                    ->whereRaw("find_in_set('". $HotelRoom->id ."',rooms)")
                                                                    ->first();
                                                    if (!empty($MasterHotel->mmt_hotel_id) && !empty($HotelRoom->mmt_room_id)) {
                                                        $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                                    ->where('block_date', $MasterInventory->date)
                                                                    ->where('rooms', $MasterInventory->room_id)
                                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'mmt')")
                                                                    ->get()->toArray();
                                                        $closed = 'false';
                                                        if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                                            $closed = "true";
                                                        }
                                                        $Xml = '<?xml version="1.0" encoding="UTF-8" ?>
                                                                    <AvailRateUpdateRQ hotelCode="'. $MasterHotel->mmt_hotel_id .'" timeStamp="'. time() .'">
                                                                        <AvailRateUpdate locatorID="1">
                                                                            <DateRange from="'. $checkInDate . '" to="' . $checkInDate .'"/>
                                                                            <Availability code="'. $HotelRoom->mmt_room_id .'" count="'. $MasterInventory->total_available .'" closed="'. $closed .'" />
                                                                        </AvailRateUpdate>
                                                                    </AvailRateUpdateRQ>';
                                                        DB::insert("INSERT INTO `mmt_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('online_package_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->mmt_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->mmt_room_id . "', '". $Xml ."', '0', '" . date('Y-m-d H:i:s') . "', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                                    }
                                                    if (!empty($MasterHotel->ctp_hotel_id) && !empty($HotelRoom->ctp_room_id)) {
                                                        $closed = "Open";
                                                        $BlockedMmt = BlockedMmtInventory::where('hotel_id', $MasterHotel->id)
                                                                    ->where('block_date', $MasterInventory->date)
                                                                    ->where('rooms', $MasterInventory->room_id)
                                                                    ->whereRaw("(platform LIKE 'all' OR platform LIKE 'cleartrip')")
                                                                    ->get()->toArray();
                                                        if (!empty($FullBlockData) || !empty($BlockedMmt)) {
                                                            $closed = "Close";
                                                        }
                                                        $InvRateplanData = CtpRatePlans::where(['room_type_code' => $HotelRoom->ctp_room_id])->first();
                                                        if (!empty($InvRateplanData)) {
                                                            $Xml = '<OTA_HotelAvailNotifRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0" EchoToken="1234">
                                                                        <AvailStatusMessages HotelCode="'. $MasterHotel->ctp_hotel_id .'">
                                                                            <AvailStatusMessage BookingLimit="'. $MasterInventory->total_available .'">
                                                                                <StatusApplicationControl Start="'. $MasterInventory->date .'" End="'. $MasterInventory->date .'" InvTypeCode="'. $HotelRoom->ctp_room_id .'" RatePlanCode="'. $InvRateplanData->rate_plan_code .'" />
                                                                                <RestrictionStatus Status="'. $closed .'" />
                                                                            </AvailStatusMessage>
                                                                        </AvailStatusMessages>
                                                                    </OTA_HotelAvailNotifRQ>';
                                                            DB::insert("INSERT INTO `ctp_availability_logs`(`request_source`, `vendor_id`, `hotel_id`, `hotel_code`, `room_id`, `room_code`, `request_data`, `status`, `created_at`, `request_type`, `quantity`, `date`) VALUES ('online_package_booking', '". $MasterHotel->vender_id ."', '". $MasterHotel->id ."', '". $MasterHotel->ctp_hotel_id ."', '". $HotelRoom->id ."', '". $HotelRoom->ctp_room_id . "', '". $Xml ."', '0', '". date('Y-m-d H:i:s') ."', 'inventory', '". $MasterInventory->total_available ."', '". $MasterInventory->date ."')");
                                                        }
                                                    }
                                                }
                                            }
                                            $temp++;
                                        }
                                    }
                                    $orderDetailUpdate = DB::table("order_details")->where('order_master_id', $OrderMaster->id)->update(['status' => 'completed']);
                                    $PackageInvoice = EmailTemplate::where('ref_code','packageInvoice')->first();
                                    $Subject = $PackageInvoice->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                    $check_date = date("d M Y", strtotime($OrderMaster->start_date)) .' - '. date("d M Y", strtotime($OrderMaster->end_date));
                                    $guest_data = 'Adult: '. $OrderMaster->total_adults .', Child: '. $OrderMaster->total_child;
                                    $OrderDetail = OrderDetail::select('service_name', 'service_name_id', 'start_date', 'end_date', 'service_item_name', DB::raw('SUM(service_item_quantity)as totQty'))
                                            ->where('order_master_id', $OrderMaster->id)
                                            ->groupBy('start_date')
                                            ->get();
                                    $room_details = '';
                                    $hotel_emails = array();
                                    foreach ($OrderDetail as $value) {
                                        $Hotels = MasterHotel::find($value->service_name_id);
                                        if(!empty($Hotels) && !empty($Hotels->contact_email)) {
                                            array_push($hotel_emails, $Hotels->contact_email);
                                        }
                                        $room_details .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->service_name . '</td>'
                                                . '<td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($value->start_date)) . ' - ' . date("d M Y", strtotime($value->end_date)) . '</td>'
                                                . '<td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->service_item_name . '</td>'
                                                . '<td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $value->totQty . '</td></tr>';
                                    }
                                    $gst_amount = $OrderMaster->tax_amount;
                                    $cgst = $sgst = number_format($gst_amount / 2, 2);
                                    $childData = '';
                                    if ($OrderMaster->total_child > 0) {
                                        $childData = '<tr style="font-size:14px;"><td colspan="3" align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;"></td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->total_child .' Child</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->child_price .'</td><td align="right" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. number_format($OrderMaster->total_child * $OrderMaster->child_price, 2) .'</td></tr>';
                                    }
                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~totaladult~", "~adultprice~", "~totaladultprice~", "~childdata~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~payuid~"),
                                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("M d Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date, $OrderMaster->total_adults, number_format($OrderMaster->adult_price, 2), number_format($OrderMaster->total_adults * $OrderMaster->adult_price, 2), $childData, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, $PaymentHistory->mihpayid), $PackageInvoice->source);
                                    $OrderMaster->invoice = $Message;

                                    $User = User::find($OrderMaster->customer_id);
                                    if (!empty($User) && $User->access_type == 'agent') {
                                        $CustomerInvoice = EmailTemplate::where('ref_code', 'packageAgentInvoice')->first();
                                        if (!empty($CustomerInvoice)) {
                                            $To = $User->email;
                                            $Subj = $CustomerInvoice->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                            $agent_name = str_replace(array('Agent Discount (', ')'), array('', ''), $OrderMaster->coupon_name);
                                            $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkoutdate~", "~adult~", "~child~", "~termsconditions~", "~accommodation~", "~invoiceid~", "~agentname~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), date("d M Y", strtotime($OrderMaster->end_date)), $OrderMaster->total_adults, $OrderMaster->total_child, $TourDetails->terms_conditions, $room_details, $OrderMaster->invoice_id, $agent_name), $CustomerInvoice->source);
                                            try {
                                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subj));
                                            }
                                            catch(\Exception $e) {}
                                            parent::sendSms($User->phone, $sms_txt, $user_templete_id);
                                        }
                                    }
                                    $ConfirmTemplate = EmailTemplate::where('ref_code','packageConfirmMail')->first();
                                    if (!empty($ConfirmTemplate)) {
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~checkoutdate~", "~adult~", "~child~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~accommodation~", "~invoiceid~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), date("d M Y", strtotime($OrderMaster->end_date)), $OrderMaster->total_adults, $OrderMaster->total_child, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $TourDetails->terms_conditions, $room_details, $OrderMaster->invoice_id), $ConfirmTemplate->source);
                                        $OrderMaster->confimation_voucher = $msg;
                                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                                        try {
                                            Mail::to($To)->bcc($hotel_emails)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                        }
                                        catch(\Exception $e) {}
                                    }
                                    $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmPackageRental')->first();
                                    if (!empty($SmsTemplateAdmin)) {
                                        $var1 = $OrderMaster->service_name;
                                        $var1 = (strlen($var1) > 30) ? substr(utf8_encode($var1), 0, 27) .'...' : $var1;
                                        $var2 = $OrderMaster->customer_name;
                                        $var3 = $OrderMaster->customer_phone;
                                        $var4 = date("d M Y", strtotime($OrderMaster->start_date));
                                        $var5 = date("d M Y", strtotime($OrderMaster->end_date));
                                        $var6 = $OrderMaster->invoice_id;

                                        $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($var1, $var2, $var3, $var4, $var5, $var6), $SmsTemplateAdmin->source);
                                        $sms_recipient = array();
                                        if (!empty($manager_contact))
                                            array_push($sms_recipient, $manager_contact);
                                        if (!empty($TourDetails->additional_phone))
                                            $sms_recipient = array_merge($sms_recipient, explode(",", $TourDetails->additional_phone));
                                        if (!empty($sms_recipient)) {
                                            $to_sms = implode(',', array_slice($sms_recipient,0,3));
                                            parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                                        }
                                    }
                                }
                                $service_mail = $TourDetails->contact_email;
                                if (!empty($TourDetails->additional_email)) {
                                    $service_mail = !empty($service_mail) ? $service_mail .','. $TourDetails->additional_email : $TourDetails->additional_email;
                                }
                                $OrderMaster->invoice = $Message;
                            }
                            elseif ($OrderMaster->service_type == 'ticketing') {
                                $TicketDetails = Ticket::find($OrderMaster->service_name_id);
                                $TicketInvoice = EmailTemplate::where('ref_code', 'ticketInvoice')->first();
                                $Subject = $TicketInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                                $check_date = date("d M Y", strtotime($OrderMaster->start_date));
                                $guest_data = 'Adult: ' . $OrderMaster->total_adults . ', Child: ' . $OrderMaster->total_child;
                                $duration = (!empty($OrderMaster->start_time)) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All Day';

                                $vendorGSTNo = (!empty($TicketDetails->gst_number)) ? $TicketDetails->gst_number : 'N/A';
                                $vendorRegdCompany = (!empty($TicketDetails->gst_legal_name)) ? $TicketDetails->gst_legal_name : 'N/A';

                                $childData = '';
                                if ($OrderMaster->total_child > 0 && $OrderMaster->total_adults > 0) {
                                    $childData = '<tr style="font-size:14px;"><td colspan="3" align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;"></td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->total_child .' Child</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->child_price .'</td><td align="right" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. number_format($OrderMaster->total_child * $OrderMaster->child_price, 2) .'</td></tr>';
                                }
                                $extraService = $extraPrice = '';
                                $UsedService = json_decode($OrderMaster->room_details, 1);
                                foreach ($UsedService as $value) {
                                    if ($value['quantity'] >= 1) {
                                        $extraService .= '<tr><td><strong>'. $value['name'] .':</strong>'. $value['quantity'] .'</td></tr>';
                                        $extraPrice .= '<tr style="font-size:14px;"><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->invoice_id .'</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $value['name'] .'</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $check_date .'</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $value['quantity'] .'</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $value['price'] .'</td><td align="right" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. number_format($value['quantity'] * $value['price'], 2) .'</td></tr>';
                                    }
                                }
                                $childData .= $extraPrice;
                                $duration = ($OrderMaster->service_name_id == '24') ? '' : $duration;
                                if ($OrderMaster->total_adults == 0 && $TicketDetails->id == 22) {
                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~totaladult~ Adult", "~adultprice~", "~totaladultprice~", "~childdata~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~payuid~"),
                                        array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date .'<br>'. $duration, $OrderMaster->total_child.' Student', number_format($OrderMaster->child_price, 2), number_format($OrderMaster->total_child * $OrderMaster->child_price, 2), $childData, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, $PaymentHistory->mihpayid), $TicketInvoice->source);
                                }else{
                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~totaladult~", "~adultprice~", "~totaladultprice~", "~childdata~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~payuid~"),
                                        array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, ($OrderMaster->service_name_id == '24') ? '6<sup>th</sup> ' . $OrderMaster->service_name : $OrderMaster->service_name, $check_date .'<br>'. $duration, $OrderMaster->total_adults, number_format($OrderMaster->adult_price, 2), number_format($OrderMaster->total_adults * $OrderMaster->adult_price, 2), $childData, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, $PaymentHistory->mihpayid), $TicketInvoice->source);
                                }

                                if ($OrderMaster->vendor_id == 268 || $OrderMaster->vendor_id == 6048) { // 268  ,  6048
                                    $Message = str_replace($OrderMaster->total_adults .' Adult', $OrderMaster->total_adults, $Message);
                                }
                                $service_mail = $TicketDetails->contact_email;
                                if (!empty($TicketDetails->additional_email)) {
                                    $service_mail = !empty($service_mail) ? $service_mail .','. $TicketDetails->additional_email : $TicketDetails->additional_email;
                                }
                                if ($TicketDetails->id == 22) {
                                    $Message = str_replace('Child', 'Student', $Message);
                                }
                                $OrderMaster->invoice = $Message;

                                $User = User::find($OrderMaster->customer_id);
                                if (!empty($User) && $User->access_type == 'agent' && $OrderMaster->vendor_id != 268) { // 268  ,  6048
                                    $CustomerInvoice = EmailTemplate::where('ref_code', 'ticketAgentInvoice')->first();
                                    if (!empty($CustomerInvoice)) {
                                        $To = $User->email;
                                        $Subj = $CustomerInvoice->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $agent_name = str_replace(array('Agent Discount (', ')'), array('', ''), $OrderMaster->coupon_name);
                                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~duration~", "~adult~", "~child~", "~termsconditions~", "~extraservice~", "~invoiceid~", "~agentname~"),
                                            array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), $duration, $OrderMaster->total_adults, $OrderMaster->total_child, $TicketDetails->terms_conditions, $extraService, $OrderMaster->invoice_id, $agent_name), $CustomerInvoice->source);
                                        if ($TicketDetails->id == 22) {
                                            $msg = str_replace('Child', 'Student', $msg);
                                        }
                                        try {
                                            Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subj));
                                        }
                                        catch(\Exception $e) {}
                                        parent::sendSms($User->phone, $sms_txt, $user_templete_id);
                                    }
                                }
                                if ($OrderMaster->vendor_id == 268 || $OrderMaster->vendor_id == 6048) { // 268  ,  6048
                                    require_once public_path('s3_file_upload/s3_file_upload.php');
                                    if ($s3->putObjectFile($OrderMaster->qr_code, 'odishatourism', 'qrcodes/'. $OrderMaster->qr_code)) {
                                        $OrderMaster->qr_base64 = 'https://odishatourism.s3.us-west-1.amazonaws.com/qrcodes/'. $OrderMaster->qr_code;
                                    }
                                    $ConfirmTemplate = EmailTemplate::where('ref_code','specialticketConfirmMail')->first();
                                    if (!empty($ConfirmTemplate)) {
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $check_date = '['. date("h:i a", strtotime($OrderMaster->start_time)) .' - '. date("h:i a", strtotime($OrderMaster->end_time)) .'] | '. date("d M Y", strtotime($OrderMaster->start_date));
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;

                                        $TicketCategory = str_replace("Rourkela City Festival", "", trim($OrderMaster->service_name));
                                        $TicketCategory = str_replace("(", "", trim($TicketCategory));
                                        $TicketCategory = str_replace(")", "", trim($TicketCategory));
                                        $ticketbg = '';
                                        if(trim($TicketCategory) == 'GOLD'){
                                            $ticketbg = 'https://rklcityfest.bookodisha.com/voucher/gold_bg.jpg';
                                            $TicketCategoryName = '<span style="color:#b78627;">'.$TicketCategory.'(Standing)</span>';
                                        }else if(trim($TicketCategory) == 'DIAMOND'){
                                            $ticketbg = 'https://rklcityfest.bookodisha.com/voucher/diamond_bg.jpg';
                                            $TicketCategoryName = '<span style="color:#5e025a;">'.$TicketCategory.'(Seating)</span>';
                                        }else if(trim($TicketCategory) == 'PLATINUM'){
                                            $ticketbg = 'https://rklcityfest.bookodisha.com/voucher/platinum_bg.jpg';
                                            $TicketCategoryName = '<span style="color:#01348f;">'.$TicketCategory.'(Seating)</span>';
                                        }
                                        $msg = str_replace(array("~bookingqrCode~", "~gateno~", "~servicename~", "~invoiceid~", "~adult~", "~checkdate~", "~ticketbg~"),
                                                array($OrderMaster->qr_base64, $TicketDetails->gate_no, $TicketCategoryName, $OrderMaster->invoice_id, $OrderMaster->total_adults, $check_date, $ticketbg), $ConfirmTemplate->source);
                                        // $msg = str_replace(array("~bookingqrCode~", "~gateno~", "~servicename~", "~invoiceid~", "~adult~", "~checkdate~"),
                                        //         array($OrderMaster->qr_base64, $TicketDetails->gate_no, $OrderMaster->service_name, $OrderMaster->invoice_id, $OrderMaster->total_adults, $check_date), $ConfirmTemplate->source);
                                        if ($TicketDetails->id == 22) {
                                            $msg = str_replace('Child', 'Student', $msg);
                                        }
                                        $OrderMaster->confimation_voucher = $msg;
                                        try {
                                            Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                        }
                                        catch(\Exception $e) {}
                                    }
                                } else {
                                    $ConfirmTemplate = EmailTemplate::where('ref_code','ticketConfirmMail')->first();
                                    if (!empty($ConfirmTemplate)) {
                                        if (empty($extraService)) {
                                            $extraService = '<tr><td colspan="2">N/A</tr>';
                                        }
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $duration = ($OrderMaster->service_name_id == '24') ? '[06 Jan 2026 - 08 Jan 2026]' : $duration;
                                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~checkindate~", "~duration~", "~adult~", "~child~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~extraservice~", "~invoiceid~", "~bookingqrCode~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, ($OrderMaster->service_name_id == '24') ? '6<sup>th</sup> ' . $OrderMaster->service_name : $OrderMaster->service_name, date("d M Y", strtotime($OrderMaster->start_date)), $duration, $OrderMaster->total_adults, $OrderMaster->total_child, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $TicketDetails->terms_conditions, $extraService, $OrderMaster->invoice_id, $this->site . $OrderMaster->qr_code), $ConfirmTemplate->source);
                                        $OrderMaster->confimation_voucher = $msg;
                                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                                        if ($TicketDetails->id == 22) {
                                            $msg = str_replace('Child', 'Student', $msg);
                                        }
                                        try {
                                            Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                        }
                                        catch(\Exception $e) {}
                                    }
                                }

                                $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmTicket')->first();
                                if (!empty($SmsTemplateAdmin)) {
                                    $var1 = $OrderMaster->total_guests .' tickets';
                                    $var2 = str_replace('Odisha Walks-', '', $OrderMaster->service_name);
                                    $var2 = (strlen($var2) > 20) ? substr(utf8_encode($var2), 0, 17) .'...' : $var2;
                                    $var3 = $OrderMaster->customer_name;
                                    $var4 = $OrderMaster->customer_phone;
                                    $var5 = ($OrderMaster->service_name_id != '24') ? date("d M Y", strtotime($OrderMaster->start_date)) .'('. $OrderMaster->start_time .'-'. $OrderMaster->end_time .')' : date("d M Y", strtotime($OrderMaster->start_date));
                                    $var6 = $OrderMaster->invoice_id;

                                    $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~", "~var7~"), array($var1, $var2, $var3, $var4, $var5, $var6, "%0a%0a"), $SmsTemplateAdmin->source);
                                    $sms_recipient = array();
                                    if (!empty($manager_contact))
                                        array_push($sms_recipient, $manager_contact);
                                    if (!empty($TicketDetails->additional_phone))
                                        $sms_recipient = array_merge($sms_recipient, explode(",", $TicketDetails->additional_phone));
                                    if (!empty($sms_recipient)) {
                                        $to_sms = implode(',', array_slice($sms_recipient,0,3));
                                        parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                                    }
                                }
                            }elseif ($OrderMaster->service_type == 'caravan') {
                                $orderDetailUpdate = DB::table("order_details")->where('order_master_id', $OrderMaster->id)->update(['status' => 'completed']);
                                $CarDetails = MasterCaravan::find($OrderMaster->service_name_id);
                                $RentalInvoice = EmailTemplate::where('ref_code', 'caravanInvoice')->first();
                                if (!empty($RentalInvoice)) {
                                    $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                                    $check_date = date("d M Y", strtotime($OrderMaster->start_date)) .' '. date("h:i a", strtotime($OrderMaster->start_time)) .' - <br>' . date("d M Y", strtotime($OrderMaster->end_date)) .' '. date("h:i a", strtotime($OrderMaster->end_time));

                                    $vendorGSTNo = (!empty($CarDetails->gst_number)) ? $CarDetails->gst_number : 'N/A';
                                    $vendorRegdCompany = (!empty($CarDetails->gst_legal_name)) ? $CarDetails->gst_legal_name : 'N/A';
                                    $guide_text = ($OrderMaster->days_for_guide > 0) ? $OrderMaster->days_for_guide : 'N/A';

                                    $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
                                    $routes = '';$routes_agent = ''; $route_confirm = '';$count = 1;
                                    $CarBooking = CaravanBooking::where('booking_id', $OrderMaster->order_id)->first();

                                    foreach($OrderDetails as $route) {
                                        $room_price = $route->unit_total_price;

                                        $routes .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMaster->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $OrderMaster->service_name .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($route->start_date)) .' - <br>'. date("d M Y", strtotime($route->end_date)) .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $CarBooking->no_of_days . ' days</td><td align="right" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. number_format($OrderMaster->sub_total_price , 2) .'</td></tr>';

                                        $routes_agent .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $route->pickup_address .', '. $route->pickup_city . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $route->drop_address .', '. $route->drop_city .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($route->start_date)) .' - <br>'. date("d M Y", strtotime($route->end_date)) .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $route->days . ' days</td></tr>';

                                        $route_confirm .= '<tr><td width="10%" rowspan="3">'. $count .'</td><td><strong>Pick up Location</strong>: ' . $route->pickup_address .', '. $route->pickup_city . '</td></tr><tr><td><strong>Start Date</strong>: ' . date("d M Y", strtotime($route->start_date)) .'</td><td><strong>End Date</strong>: '. date("d M Y", strtotime($route->end_date)) .'</td></tr><tr><td colspan="2"><strong>Days </strong>: ' . $CarBooking->no_of_days . ' days</td></tr><tr><td colspan="3">&nbsp;</td></tr>';
                                        $count++;

                                        $difference = strtotime(date("Y-m-d", strtotime($route->end_date))) - strtotime(date("Y-m-d", strtotime($route->start_date)));
                                        $days = floor($difference / (60 * 60 * 24));
                                        $cal_day = ($days == 0) ? 1 : $days + 1;
                                        for($i = 0; $i < $cal_day; $i++) {
                                            $checkDate = date("Y-m-d", strtotime($route->start_date .' + '. $i .' days'));
                                            $MasterInventory = CaravanMasterInventory::where(["date" => $checkDate, "caravan_id" => $route->service_name_id])->first();
                                            if (!empty($MasterInventory)) {
                                                if ($MasterInventory->total_available > 0) {
                                                    $MasterInventory->total_available -= 1;
                                                    $MasterInventory->total_booked += 1;
                                                    $MasterInventory->total_online_completed += 1;
                                                } else {
                                                    $MasterInventory->total_booked += 1;
                                                    $MasterInventory->total_online_completed += 1;
                                                }
                                                $MasterInventory->save();
                                            }
                                        }
                                    }
                                    $maps = '';
                                    if ($CarBooking->rental_type != 'user_defined') {
                                        $maps = '<td align="left" valign="top" style="font-family:Arial, Helvetica, sans-serif; color:#000; font-size:12px; line-height:22px;"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;"><tr><th align="center" colspan="2" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Route</strong></th></tr><tr><th align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Address</strong></th><th align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>City</strong></th></tr>';
                                        $travel_route = json_decode($OrderMaster->travel_route, 1);
                                        foreach($travel_route as $rmap) {
                                            $maps .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $rmap['dropPonitDetails'] . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $rmap['dropPointCity'] .'</td>';
                                        }
                                        $maps .= '</table></td>';
                                    }
                                    $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~orderdetails~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~guidecharge~", "~payuid~"),
                                            array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $routes, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, number_format($OrderMaster->guide_charge, 2), $PaymentHistory->mihpayid), $RentalInvoice->source);
                                    $service_mail = $CarDetails->contact_email;
                                    if (!empty($CarDetails->additional_email)) {
                                        $service_mail = !empty($service_mail) ? $service_mail .','. $CarDetails->additional_email : $CarDetails->additional_email;
                                    }
                                    $OrderMaster->invoice = $Message;

                                    $User = User::find($OrderMaster->customer_id);
                                    if (!empty($User) && $User->access_type == 'agent') {
                                        $CustomerInvoice = EmailTemplate::where('ref_code', 'rentalAgentInvoice')->first();
                                        if (!empty($CustomerInvoice)) {
                                            $To = $User->email;
                                            $Subj = $CustomerInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
                                            $agent_name = str_replace(array('Agent Discount (', ')'), array('', ''), $OrderMaster->coupon_name);
                                            $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~termsconditions~", "~guideservice~", "~invoiceid~", "~agentname~"),
                                                    array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, $route_confirm, $CarDetails->terms_conditions, $guide_text, $OrderMaster->invoice_id, $agent_name), $CustomerInvoice->source);
                                            try {
                                                Mail::to($OrderMaster->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $Subj));
                                            }
                                            catch(\Exception $e) {}
                                            parent::sendSms($User->phone, $sms_txt, $user_templete_id);
                                        }
                                    }
                                    $ConfirmTemplate = EmailTemplate::where('ref_code','caravanConfirmMail')->first();
                                    if (!empty($ConfirmTemplate)) {
                                        $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                                        $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~guideservice~", "~invoiceid~"),
                                                array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, $route_confirm, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $CarDetails->terms_conditions, $guide_text, $OrderMaster->invoice_id), $ConfirmTemplate->source);
                                        $OrderMaster->confimation_voucher = $msg;
                                        $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                                        try {
                                            Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                                        }
                                        catch(\Exception $e) {}
                                    }
                                }
                                $SmsTemplateAdmin = SmsTemplate::where('ref_code', 'BookingConfirmPackageRental')->first();
                                if (!empty($SmsTemplateAdmin)) {
                                    $var1 = $OrderMaster->service_name;
                                    $var1 = (strlen($var1) > 30) ? substr(utf8_encode($var1), 0, 27) .'...' : $var1;
                                    $var2 = $OrderMaster->customer_name;
                                    $var3 = $OrderMaster->customer_phone;
                                    $var4 = date("d M Y", strtotime($OrderMaster->start_date));
                                    $var5 = date("d M Y", strtotime($OrderMaster->end_date));
                                    $var6 = $OrderMaster->invoice_id;

                                    $sms_txt_admin = str_replace(array("~var1~", "~var2~", "~var3~", "~var4~", "~var5~", "~var6~"), array($var1, $var2, $var3, $var4, $var5, $var6), $SmsTemplateAdmin->source);
                                    $sms_recipient = array();
                                    if (!empty($manager_contact))
                                        array_push($sms_recipient, $manager_contact);
                                    if (!empty($CarDetails->additional_phone))
                                        $sms_recipient = array_merge($sms_recipient, explode(",", $CarDetails->additional_phone));
                                    if (!empty($sms_recipient)) {
                                        $to_sms = implode(',', array_slice($sms_recipient,0,3));
                                        parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                                    }
                                }
                            }
                            $admin = User::where('role', 1)->first();
                            $receipent = array_merge($Vendor_mail, array($admin->email));
                            if (!empty($service_mail)) {
                                $receipent = array_merge($receipent, explode(',', $service_mail));
                            }
                            $tspinword = parent::AmountInWords($OrderMaster->total_service_price);
                            $Message = str_replace('~tspinword~', $tspinword, $Message);
                            $frontUrl = str_replace('/tourism/', '/', $this->frontendUrl);
                            $EmailBody = '<div style="display:flex;gap:10px;justify-content:space-between;"><p style="width:70%;">Dear '. $OrderMaster->customer_name .',<br><br> please <a href="'. $frontUrl . 'user/booking-history"><b>click here</b></a> to check booking details / cancel booking.<br>Please copy the following url and paste it in your browser if you are unable to click the link. <br><br>'. $frontUrl . 'user/booking-history </p>'. $invoicemap .'</div>';
                            // $EmailBody = '<p>Dear '. $OrderMaster->customer_name .',<br><br> please <a href="'. $frontUrl . 'user/booking-history"><b>click here</b></a> to check booking details / cancel booking.</p><p>Please copy the following url and paste it in your browser if you are unable to click the link. <br><br>'. $frontUrl . 'user/booking-history</p>'. $invoicemap;
                            $EmailBody .= $Message;
                            $EmailBody .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';

                            $OrderMaster->invoice = $Message;
                            $OrderMaster->save();
                            try {
                                Mail::to($To)
                                    ->bcc($receipent)
                                    ->send(new \App\Mail\RegistrationMailUser($EmailBody, $Subject));
                            }
                            catch(\Exception $e) {}

                            $responce['status'] = 1;
                            $responce['message'] = 'Booking success.';
                        } else {
                            $responce['status'] = 0;
                            $responce['message'] = 'Unable to get payment details. Please try after some time.';
                        }
                    } else {
                        $responce['status'] = 0;
                        $responce['message'] = 'Unable to get transaction details. Please try after some time.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid booking Id.';
                }
            }
        } elseif ($request->request_type == 'reinitiate_refund_process') {
            if (!(parent::checkWritePrivilege(74))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $CustomerRefund = CustomerRefund::find($request->Id);
                if (!empty($CustomerRefund)) {
                    $OrderMaster = OrderMaster::where('invoice_id', $CustomerRefund->invoice_id)->first();
                    if (!empty($OrderMaster)) {
                        require_once public_path('paytm_lib/config_paytm.php');

                        if (!empty($OrderMaster->hdfc_key) && !empty($OrderMaster->hdfc_salt) && PAYTM_ENVIRONMENT == 'PROD') {
                            $HDFC_KEY = $OrderMaster->hdfc_key;
                            $HDFC_SALT = $OrderMaster->hdfc_salt;
                        }
                        $key = $HDFC_KEY;
                        $salt = $HDFC_SALT;

                        $command = "cancel_refund_transaction";
                        $var1 = $CustomerRefund->pg_txn_id;                  // mihpayid
                        $reference_id = $var2 = date('dmY') . time();        // request id
                        $var3 = $CustomerRefund->refund_amount;              // amount

                        $hash_str = $key . '|' . $command . '|' . $var1 . '|' . $salt;
                        $hash = strtolower(hash('sha512', $hash_str));

                        $r = array('key' => $key, 'hash' => $hash, 'command' => $command, 'var1' => $var1, 'var2' => $var2, 'var3' => $var3);
                        $qs = http_build_query($r);
                        $wsUrl = VERIFY_URL; //"https://test.payu.in/merchant/postservice.php?form=2";

                        $c = curl_init();
                        curl_setopt($c, CURLOPT_URL, $wsUrl);
                        curl_setopt($c, CURLOPT_POST, 1);
                        curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
                        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
                        $o = curl_exec($c);

                        curl_close($c);
                        $valueSerialized = @unserialize($o);
                        $result = json_decode($o, 1);
                        $result_msg = isset($result['msg']) ? $result['msg'] : '';
                        if (isset($result['status']) && $result['status'] == 1) {
                            $refund_txn_id = isset($result['bank_ref_num']) ? $result['bank_ref_num'] : '';
                            $rquest_id = isset($result['request_id']) ? $result['request_id'] : $reference_id;

                            DB::insert("INSERT INTO `customer_refunds`(`vendor_id`, `order_id`, `invoice_id`, `order_type`, `service_type`, `service_id`, `customer_id`, `order_date`, `cancel_date`, `paid_amount`, `refund_amount`, `refund_percent`, `payment_method`, `client_txn_id`, `pg_txn_id`, `reference_id`, `result_msg`, `refund_txn_id`, `response_json`, `payment_id`, `refund_status`, `created_at`, `updated_at`) SELECT `vendor_id`, `order_id`, `invoice_id`, `order_type`, `service_type`, `service_id`, `customer_id`, `order_date`, `cancel_date`, `paid_amount`, `refund_amount`, `refund_percent`, `payment_method`, `client_txn_id`, `pg_txn_id`, '". $rquest_id ."', '". $result_msg ."', '". $refund_txn_id ."', '". json_encode($result) ."', `payment_id`, 'PENDING', '". date("Y-m-d H:i:s") ."', '". date("Y-m-d H:i:s") ."' FROM `customer_refunds` WHERE `id`=". $CustomerRefund->id);

                            $CustomerRefund->stop = 1;
                            $CustomerRefund->save();

                            $responce['status'] = 1;
                            $responce['message'] = 'Refund initiated successfully.';
                        } else {
                            $responce['status'] = 0;
                            $responce['message'] = 'Unable to initiate refund. '. $result_msg;
                        }
                    } else {
                        $responce['status'] = 0;
                        $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to initiate refund. Please try after some time.';
                }
            }
        }  elseif ($request->request_type == 'check_falied_transaction') {
            // if (!(parent::checkWritePrivilege(74))) {
            //     $responce['status'] = 0;
            //     $responce['message'] = 'You are not autherised to do this operation.';
            // } else {
                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => $this->site .'cron/order/lateCapturedPendingOrders.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                ));
                $response = curl_exec($curl);
                curl_close($curl);


                $responce['status'] = 1;
                $responce['message'] = 'Success.';
            // }
        }
        echo json_encode($responce);
        exit;
    }

    public function manageMostPopular(Request $request)
    {
        if (!(parent::checkViewPrivilege(78))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('users.manage-most-popular');
    }

    public function getMostPopular(Request $request)
    {

        $aColumns = array('title', 'image', 'price', 'url', 'content', 'updated_at', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "page_contents";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC ";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $sWhere = 'WHERE type = "most-popular"';

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();

            $row[] = $aRow->title;
            $row[] = !empty($aRow->image) ? '<img src="' . $aRow->image . '"  height="80" width="100">' : 'N/A';
            $row[] = $aRow->price;
            $row[] = (strlen($aRow->url) > 50) ? '<a href="' . $aRow->url . '" target="_blank">' . substr(utf8_encode($aRow->url), 0, 50) . '...</a>' : '<a href="' . $aRow->url . '" target="_blank">' . utf8_encode($aRow->url) . '</a>';
            $row[] = (strlen($aRow->content) > 50) ? substr(utf8_encode($aRow->content), 0, 50) . '...' : utf8_encode($aRow->content); //$aRow->content;
            $row[] = date("d M Y", strtotime($aRow->updated_at));
            $row[] = ($aRow->status == 1) ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">Draft</span>';
            //            $row[] = '<a href="' . url('edit-most-popular', $aRow->id) . '" class="btn btn-primary btn-sm" data-id="' . $aRow->id . '"><i class="fa fa-edit"></i>Edit</a>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('edit-most-popular', $aRow->id) . '">Edit</a></li>
                    <li class="deletePopular" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function addMostPopular(Request $request)
    {
        if (!(parent::checkWritePrivilege(78))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        return view('users.add-most-popular');
    }

    public function addMostPopularRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string',
            'url' => 'required|string',
            'image' => 'required|mimes:jpeg,png,jpg',
            'price' => 'required|numeric',
            'section' => 'required|string',
            'slug' => 'required|string',
            'status' => 'required|numeric',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-most-popular')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/pages/';
            $banner_image = '';
            if ($request->hasFile('image')) {
                if ($request->file('image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->image->extension();
                    $request->image->move(public_path($UploadDir), $banner_image);
                }
            }
            $Page = new PageContent([
                'type' => 'most-popular',
                'title' => $request->title,
                'content' => addslashes($request->content),
                'image' => !empty($banner_image) ? $UploadDir . $banner_image : '',
                'slug' => $request->slug,
                'url' => $request->url,
                'price' => $request->price,
                'section' => $request->section,
                'status' => $request->status,
                'create_user' => Auth::user()->id
            ]);
            if ($Page->save()) {
                Session::flash('success', 'Content added successful.');
                return Redirect::to('manage-most-popular');
            } else {
                Session::flash('success', 'Unable to add Content');
                return Redirect::to('add-most-popular');
            }
        }
    }

    public function editMostPopular($id = null)
    {
        if (!(parent::checkWritePrivilege(78))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $PageContent = PageContent::find($id);
        if (!empty($PageContent)) {
            $PageContent->image = $this->site . $PageContent->image;
            return view('users.edit-most-popular', compact('PageContent'));
        } else {
            return redirect()->back();
        }
    }

    public function editMostPopularRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:100',
            'content' => 'required|string',
            'url' => 'required|string',
            'image' => 'mimes:jpeg,png,jpg',
            'price' => 'required|numeric',
            'section' => 'required|string',
            'slug' => 'required|string',
            'status' => 'required|numeric',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-most-popular/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $PageContent = PageContent::find($request->id);
            $PageContent->title = $request->title;
            $PageContent->content = addslashes($request->content);
            $PageContent->slug = $request->slug;
            $PageContent->url = $request->url;
            $PageContent->price = $request->price;
            $PageContent->section = $request->section;
            $PageContent->status = $request->status;

            $UploadDir = 'images/pages/';
            if ($request->hasFile('image')) {
                if ($request->file('image')->isValid()) {
                    $old_image = public_path($PageContent->image);
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->image->extension();
                    $request->image->move(public_path($UploadDir), $banner_image);
                    $PageContent->image = $UploadDir . $banner_image;
                }
            }
            $PageContent->update_user = Auth::user()->id;
            if ($PageContent->save()) {
                Session::flash('success', 'Content updated successful.');
                return Redirect::to('manage-most-popular');
            } else {
                Session::flash('success', 'Unable to update Content');
                return Redirect::to('edit-most-popular/' . $request->id);
            }
        }
    }

    public function vendorRequests()
    {
        if (!(parent::checkViewPrivilege(82))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Services = Service::all()->toArray();

        return view('users.vendor-requests', compact('Services'));
    }

    public function getVendorRequests(Request $request)
    {
        $this->layout = "ajax";
        $this->modelClass = "VendorRequest";
        $this->autoRender = false;

        $aColumns = array('enterprise_name', 'first_name', 'email', 'phone', 'designation', 'address', 'gst_number', 'service_offered', 'property_image', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "vendor_requests";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = " ORDER BY id DESC";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = ' WHERE 1 ';
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            /* "sEcho" => intval($_GET['sEcho']),
              "iTotalRecords" => $iTotal,
              "iTotalDisplayRecords" => $iFilteredTotal,
              "aaData" => array()
             */
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        $in = 1;
        foreach ($rResult as $aRow) {
            $row = array();
            $currentstatus = ($aRow->status == 0) ? '<li class="approveVendor" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Approve</a></li>' : '';
            $gst_data = $aRow->gst_number . '<br><a href="' . $aRow->gst_certificate . '" target="_blank">View Certificate</a>';
            $content = (strlen($aRow->service_offered) > 30) ? '<a title="Click to view full data" href="javascript:void(0)" class="banner-content" data-content="' . utf8_encode($aRow->service_offered) . '" data-toggle="modal" data-target="#viewModal">' . substr(utf8_encode($aRow->service_offered), 0, 30) . '...' . '</a>' : utf8_encode($aRow->service_offered);

            $row[] = $aRow->enterprise_name;
            $row[] = $aRow->first_name . ' ' . $aRow->last_name;
            $row[] = $aRow->email;
            $row[] = $aRow->phone;
            $row[] = $aRow->designation;
            $row[] = wordwrap($aRow->address, 40, "<br>\n");
            $row[] = $gst_data;
            $row[] = $content;
            $row[] = '<a href="javascript:void(0)" class="view-image" data-id="' . $aRow->id . '" >View Images</a>';
            $row[] = ($aRow->status == 1) ? 'Approved' : 'Unapproved';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    ' . $currentstatus . '
                    <li class="deleteVendor" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
            $in++;
        }

        echo json_encode($output);
        exit;
    }

    public function linkAccounts()
    {
        if (!(parent::checkViewPrivilege(83))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', 2)->pluck('company', 'id')->toArray();

        return view('users.link-accounts', compact('Vendors'));
    }

    public function getAccounts(Request $request)
    {
        $this->layout = "ajax";
        $this->modelClass = "PropertyAccount";
        $this->autoRender = false;

        $aColumns = array('vendor_id', 'service_type', 'service_id', 'hdfc_mid', 'hdfc_key', 'hdfc_salt', 'id');
        $sIndexColumn = "id";
        $sTable = "property_accounts";
        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_POST['order'])) {
            $sOrder = "ORDER BY ";
            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" . $aColumns[intval($_POST['order'][$i]['column'])] . "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') . ", ";
                }
            }
            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }
        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = ' WHERE 1 ';
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2']) || !empty($_POST['searchValue3'])) {
            $condition1 = '';
            $condition2 = '';
            $condition3 = '';
            if (!empty($_POST['searchValue1'])) {
                $condition1 .= ' AND vendor_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $condition2 .= ' AND service_type = "' . $_POST['searchValue2'] . '"';
            }
            if (!empty($_POST['searchValue3'])) {
                $condition2 .= ' AND service_id = "' . $_POST['searchValue3'] . '"';
            }
            $sWhere .= $condition1 . $condition2 . $condition3;
        }
        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $sWhere .= " AND (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['search']['value'] . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $_POST['sSearch_' . $i] . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
        //        echo $sQuery;exit;
        $rResult = DB::select($sQuery);

        /* Data set length after filtering */
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        //print_r($aResultFilterTotal);exit;
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        /* Total data set length */
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        /*
         * Output
         */
        $output = array(
            /* "sEcho" => intval($_GET['sEcho']),
              "iTotalRecords" => $iTotal,
              "iTotalDisplayRecords" => $iFilteredTotal,
              "aaData" => array()
             */
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );
        $Vendors = User::where('role', 2)->pluck('company', 'id')->toArray();

        $in = 1;
        foreach ($rResult as $aRow) {
            $row = array();
            $service_name = 'N/A';
            if ($aRow->service_type == 'hotel') {
                $MasterHotel = MasterHotel::find($aRow->service_id);
                if (!empty($MasterHotel)) {
                    $service_name = $MasterHotel->name;
                }
            } elseif ($aRow->service_type == 'rental') {
                $MasterCar = MasterCar::find($aRow->service_id);
                if (!empty($MasterCar)) {
                    $service_name = $MasterCar->title;
                }
            } elseif ($aRow->service_type == 'tour') {
                $Tour = Tour::find($aRow->service_id);
                if (!empty($Tour)) {
                    $service_name = $Tour->name;
                }
            } elseif ($aRow->service_type == 'ticketing') {
                $Ticket = Ticket::find($aRow->service_id);
                if (!empty($Ticket)) {
                    $service_name = $Ticket->name;
                }
            }
            $vendor_name = isset($Vendors[$aRow->vendor_id]) ? $Vendors[$aRow->vendor_id] : 'N/A';
            $row[] = $vendor_name;
            $row[] = $aRow->service_type;
            $row[] = $service_name;
            $row[] = $aRow->hdfc_mid;
            $row[] = $aRow->hdfc_key;
            $row[] = $aRow->hdfc_salt;
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . url('account-edit', $aRow->id) . '">Edit</a></li>
                    <li class="deleteAccount" data-id="' . $aRow->id . '"><a href="javascript:void(0)">Delete</a></li>
                </ul>
            </div>';

            $output['data'][] = $row;
            $in++;
        }

        echo json_encode($output);
        exit;
    }

    public function accountOprsn(Request $request)
    {
        if ($request->request_type == 'get_vendor_services') {
            $Vendor = User::find($request->vendorId);
            if (!empty($Vendor)) {
                $Services = ($Vendor->services) ? json_decode($Vendor->services, 1) : [];
                $html = '<option value="">Select Property Type</option>';
                foreach ($Services as $value) {
                    if ($value != 'mmt-integration') {
                        $html .= '<option value="' . $value . '">' . ucwords($value) . '</option>';
                    }
                }
                $responce['status'] = 1;
                $responce['data'] = $html;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to get property types.';
            }
        } elseif ($request->request_type == 'get_vendor_property') {
            $html = '<option value="">Select Property</option>';
            if ($request->service_type == 'hotel') {
                $MasterHotel = MasterHotel::where('vender_id', $request->vendorId)->pluck('name', 'id');
                if (!empty($MasterHotel)) {
                    foreach ($MasterHotel as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            } elseif ($request->service_type == 'rental') {
                $MasterCar = MasterCar::where('vendor_id', $request->vendorId)->pluck('title', 'id');
                if (!empty($MasterCar)) {
                    foreach ($MasterCar as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            } elseif ($request->service_type == 'tour') {
                $Tour = Tour::where('vendor_id', $request->vendorId)->pluck('name', 'id');
                if (!empty($Tour)) {
                    foreach ($Tour as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            } elseif ($request->service_type == 'ticketing') {
                $Ticket = Ticket::where('vendor_id', $request->vendorId)->pluck('name', 'id');
                if (!empty($Ticket)) {
                    foreach ($Ticket as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            }
            elseif ($request->service_type == 'hall') {
                $HallProperty = HallProperty::where('vender_id', $request->vendorId)->pluck('property_name', 'id');
                if (!empty($HallProperty)) {
                    foreach ($HallProperty as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            }elseif ($request->service_type == 'caravan') {
                $HallProperty = MasterCaravan::where('vender_id', $request->vendorId)->pluck('title', 'id');
                if (!empty($HallProperty)) {
                    foreach ($HallProperty as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            }elseif ($request->service_type == 'flight') {
                $HallProperty = FlightMaster::where('vendor_id', $request->vendorId)->pluck('flight_number', 'id');
                if (!empty($HallProperty)) {
                    foreach ($HallProperty as $key => $value) {
                        $html .= '<option value="' . $key . '">' . $value . '</option>';
                    }
                }
            }
            $responce['status'] = 1;
            $responce['data'] = $html;
        } elseif ($request->request_type == 'delete_account') {
            if (!(parent::checkWritePrivilege(83))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                PropertyAccount::find($request->accountId)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Account details deleted successfully.';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function accountAdd()
    {
        if (!(parent::checkWritePrivilege(83))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', 2)->pluck('company', 'id')->toArray();

        return view('users.account-add', compact('Vendors'));
    }

    public function accountAddRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'service_type' => 'required',
            'hdfc_key' => 'required|string|max:32',
            'hdfc_salt' => 'required|string|max:64',
            'hdfc_mid' => 'required|string|max:16',
            'service_id' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('account-add')->withErrors($validate)->withInput();
        } else {
            $ExistAccount = PropertyAccount::where(['vendor_id' => $request->vendor_id, 'service_type' => $request->service_type, 'service_id' => $request->service_id])->first();
            if (!empty($ExistAccount)) {
                Session::flash('success', 'There is already an account available for the property.');
                return Redirect::to('link-accounts');
            } else {
                $Account = new PropertyAccount([
                    'vendor_id' => $request->vendor_id,
                    'service_type' => $request->service_type,
                    'service_id' => $request->service_id,
                    'hdfc_key' => $request->hdfc_key,
                    'hdfc_salt' => $request->hdfc_salt,
                    'hdfc_mid' => $request->hdfc_mid,
                    'created_by' => Auth::user()->id,
                ]);
                if ($Account->save()) {
                    Session::flash('success', 'Account details added successfully.');
                    return Redirect::to('link-accounts');
                } else {
                    Session::flash('success', 'Unable to add account details.');
                    return Redirect::to('account-add');
                }
            }
        }
    }

    public function accountEdit($id = null)
    {
        if (!(parent::checkWritePrivilege(3))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }

        $PropertyAccount = PropertyAccount::find($id);
        if (!empty($PropertyAccount)) {
            $Vendors = User::where('role', 2)->pluck('company', 'id')->toArray();

            $Vendor = User::find($PropertyAccount->vendor_id);
            if (!empty($Vendor)) {
                $Services = ($Vendor->services) ? json_decode($Vendor->services, 1) : [];
                $html = '<option value="">Select Property Type</option>';
                $indexexists = array_search('mmt-integration', $Services);
                if ($indexexists !== false) {
                    unset($Services[$indexexists]);
                }
                // unset($Services[array_search('mmt-integration', $Services)]);
            }
            $property = array();
            if ($PropertyAccount->service_type == 'hotel') {
                $property = MasterHotel::where('vender_id', $PropertyAccount->vendor_id)->pluck('name', 'id');
            } elseif ($PropertyAccount->service_type == 'rental') {
                $property = MasterCar::where('vendor_id', $PropertyAccount->vendor_id)->pluck('title', 'id');
            } elseif ($PropertyAccount->service_type == 'tour') {
                $property = Tour::where('vendor_id', $PropertyAccount->vendor_id)->pluck('name', 'id');
            } elseif ($PropertyAccount->service_type == 'ticketing') {
                $property = Ticket::where('vendor_id', $PropertyAccount->vendor_id)->pluck('name', 'id');
            }

            return view('users.account-edit', compact('Vendors', 'PropertyAccount', 'Services', 'property'));
        } else {
            return redirect()->back();
        }
    }

    public function accountEditRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'service_type' => 'required',
            'hdfc_key' => 'required|string|max:32',
            'hdfc_salt' => 'required|string|max:64',
            'hdfc_mid' => 'required|string|max:16',
            'service_id' => 'required',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('account-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $Account = PropertyAccount::find($request->id);

            $Account->vendor_id = $request->vendor_id;
            $Account->service_type = $request->service_type;
            $Account->service_id = $request->service_id;
            $Account->hdfc_key = $request->hdfc_key;
            $Account->hdfc_salt = $request->hdfc_salt;
            $Account->hdfc_mid = $request->hdfc_mid;
            $Account->modified_by = Auth::user()->id;

            if ($Account->save()) {
                Session::flash('success', 'Account details updated successfully.');
                return Redirect::to('link-accounts');
            } else {
                Session::flash('success', 'Unable to update account details.');
                return Redirect::to('account-edit/' . $request->id);
            }
        }
    }

    public function bookingVoucher($id = null)
    {
        // if (!(parent::checkViewPrivilege(28))) {
        //     Session::flash('success', 'You are not autherised to view this page.');
        //     return redirect()->back();
        // }
        $html = '';
        $OrderMaster = OrderMaster::find($id);
        if (!empty($OrderMaster)) {
            $html = $OrderMaster->confimation_voucher;
        }
        return view('users.booking-voucher', compact('html'));
    }

    public function ticketingQr($id = null)
    {
        // if (!(parent::checkViewPrivilege(28))) {
        //     Session::flash('success', 'You are not autherised to view this page.');
        //     return redirect()->back();
        // }
        $html = '';
        $OrderMaster = OrderMaster::find($id);

        return view('users.booking-qr', compact('OrderMaster'));
    }
}
