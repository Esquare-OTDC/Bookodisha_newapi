<?php

namespace App\Http\Controllers;

use App\AttributeValue;
use App\BlockedCaravan;
use App\BlockedVehicle;
use App\CaravanAvailability;
use App\CaravanMasterInventory;
use App\City;
use Exception;
use App\Country;
use App\Coupon;
use App\EmailTemplate;
use App\GstDetail;
use App\GstTable;
use App\MasterCaravan;
use App\OrderDetail;
use App\OrderLog;
use App\OrderMaster;
use App\PasswordRemQuestion;
use App\PaymentHistory;
use App\PropertyAccount;
use App\RentalAvailability;
use App\RentalMasterInventory;
use App\Service;
use App\ServiceAttribute;
use App\SmsTemplate;
use App\State;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Session;
use PDF;
use Validator, Redirect, Response;

class CaravanController extends Controller
{
    public $site;
    public $frontendUrl;

    public function __construct()
    {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }
    protected $caravan_type = '';

    public function allCaravan()
    {
        if (!(parent::checkViewPrivilege(16))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');

        return view('caravan.all-caravan', compact('Vendors'));
    }

    public function getCaravanDetails(Request $request)
    {
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'title', 'city', 'contact_email', 'contact_number', 'status');
        } else {
            $aColumns = array('id', 'title', 'city', 'contact_email', 'contact_number', 'status');
        }

        $sIndexColumn = "id";
        $sTable = "master_caravans";
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
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
        $vendor_condtition = '';
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;
        $searchColumns = array('title', 'city');
        if (!empty($_POST['searchValue1']) || (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3']))) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND vendor_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3'])) {
                if (in_array($_POST['searchValue2'], $searchColumns)) {
                    $_POST['searchValue3'] = parent::cleanString($_POST['searchValue3']);
                    $condition2 .= ' AND ' . $_POST['searchValue2'] . ' LIKE "' . $_POST['searchValue3'] . '"';
                }
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
        $rResult = DB::select($sQuery);
        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $row = array();
            $User = User::find($aRow->vendor_id);

            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            if (Auth::user()->access_type == 'superadmin') {
                $row[] = !empty($User) ? $User->company : 'N/A';
            }
            $row[] = $aRow->title;
            $row[] = $aRow->city;
            $row[] = $aRow->contact_email;
            $row[] = $aRow->contact_number;
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="' . route('caravan-edit', $aRow->slug) . '">Edit</a></li>
                    <li><a href="javascript:void(0)" class="deleteCaravan" data-id="' . $aRow->id . '">Delete</a></li>
                </ul>
            </div>';
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function editCaravan($id = null)
    {
        if (!(parent::checkWritePrivilege(16))) {
            Session::flash('success', 'You are not authorised to do this operation.');
            return redirect()->back();
        }

        $CaravanDetailsQry = MasterCaravan::where('slug', $id);
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $CaravanDetailsQry->where('vendor_id', $vendor_id);
        }

        $CaravanDetails = $CaravanDetailsQry->first();
        if (empty($CaravanDetails)) {
            return redirect()->back();
        }
        $CaravanDetails->feature_image = asset($CaravanDetails->feature_image);
        $CaravanDetails->banner_image = asset($CaravanDetails->banner_image);
        $CaravanDetails->gallery = json_decode($CaravanDetails->gallery, true);

        $gallery = [];
        $count = 1;
        if (!empty($CaravanDetails->gallery)) {
            foreach ($CaravanDetails->gallery as $value) {
                $gallery[] = [
                    'id' => $count,
                    'src' => asset($value)
                ];
                $count++;
            }
        }
        $gallery = json_encode($gallery);

        $property = json_decode($CaravanDetails->property, true);
        $data = [];

        if (!empty($property)) {
            foreach ($property as $key1 => $attribute) {
                foreach ($attribute as $key2 => $terms) {
                    $data[$key1][$key2] = $terms['name'];
                }
            }
        }
        $CaravanDetails->property = $data;
        $CaravanDetails->faqs = json_decode($CaravanDetails->faqs, true);
        $Vendors = User::where('role', 2)->pluck('company', 'id');
        $Attributes = ServiceAttribute::where('service', 'caravan')->pluck('name', 'id');

        $CaravanAttributes = [];
        foreach ($Attributes as $key => $value) {
            $AttributeValue = AttributeValue::where('attr_id', $key)
                ->pluck('name', 'id')
                ->toArray();

            $CaravanAttributes[$value] = $AttributeValue;
        }


        $CityDetail = City::where('state_id', Auth::user()->state)
            ->pluck('name', 'id')
            ->toArray();

        return view('caravan.edit-caravan',compact('Vendors','CaravanDetails','CaravanAttributes','CityDetail','gallery'));
    }

    public function caravanEditRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'title' => 'required|string|min:3|max:100',
        ]);
        $carBookType = $request->car_book_type;
        $priceField = 'price_per_day' . $carBookType;
        $quantityField = 'quantity' . $carBookType;


        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('caravan-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $slug = str_replace(' ', '-', trim(strtolower($request->title)));
            $Caravan = MasterCaravan::find($request->id);

            $master_qty = $change_value = 0;
            if ($Caravan->quantity < $request->quantity) {
                $master_qty = $request->quantity;
                $change_value = $request->quantity - $Caravan->quantity;
            }
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) {
                return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) {
                return !empty(trim($n)); });


            $Caravan->vendor_id = $request->vendor_id;
            $Caravan->title = trim($request->title);
            $Caravan->slug = $slug;
            $Caravan->content = addslashes($request->contents);
            $Caravan->city = $request->city;
            $Caravan->address = addslashes($request->address);
            $Caravan->map_lat = $request->map_lat;
            $Caravan->map_lng = $request->map_lng;
            $Caravan->video = $request->video;
            $Caravan->quantity = $request->quantity;
            $Caravan->car_book_type = $carBookType;
            $Caravan->price_per_day = $request->$priceField;
            $Caravan->quantity = $request->$quantityField;
            $Caravan->contact_email = $request->contact_email;
            $Caravan->contact_number = $request->contact_number;
            $Caravan->additional_email = implode(',', $add_email);
            $Caravan->additional_phone = implode(',', $add_phone);
            $Caravan->terms_conditions = $request->terms_conditions;
            $Caravan->status = $request->status;
            $Caravan->gst_applicable = $request->gst_applicable;
            $Caravan->update_user = Auth::user()->id;
            $Caravan->show_price = $request->show_price;
            $Caravan->gst_number = $request->gst_number;
            $Caravan->gst_legal_name = $request->gst_legal_name;


            $UploadDir = 'images/caravans/';
            $gallery_images = array();
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $old_banner = public_path($Caravan->banner_image);
                    if (file_exists($old_banner)) {
                        unlink($old_banner);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                    $Caravan->banner_image = $UploadDir . $banner_image;
                }
            }
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $old_feature = public_path($Caravan->feature_image);
                    if (file_exists($old_feature)) {
                        unlink($old_feature);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                    $Caravan->feature_image = $UploadDir . $feature_image;
                }
            }
            $old_gallery = !empty($Caravan->gallery) ? json_decode($Caravan->gallery, true) : [];
            $preloaded = $request->oldimage ?? [];

            $updated_gallery = [];
            if (!empty($preloaded)) {
                foreach ($preloaded as $index) {
                    if (isset($old_gallery[$index - 1])) {
                        $updated_gallery[] = $old_gallery[$index - 1];
                    }
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $filename = time() . '_' . uniqid() . '.' . $file->extension();
                    $file->move(public_path('images/caravans/'), $filename);
                    $updated_gallery[] = 'images/caravans/' . $filename;
                }
            }
            foreach ($old_gallery as $old) {
                if (!in_array($old, $updated_gallery)) {
                    if (file_exists(public_path($old))) {
                        unlink(public_path($old));
                    }
                }
            }
            $Caravan->gallery = json_encode(array_values($updated_gallery));

            $faqs = '';
            if ($request->faqs) {
                $data = array();
                foreach ($request->faqs as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $faqs = json_encode($data);
            }
            $Caravan->faqs = $faqs;

            $AttributeValues = AttributeValue::pluck('icon', 'id');

            $property_slug_data = AttributeValue::where('id', $request->car_book_type)
                ->get(['name', 'icon', 'slug']);

            $withoutSlug = $property_slug_data->map(function ($item) {
                return [
                    'name' => $item->name,
                    'icon' => $item->icon,
                ];
            })->toArray();
            $result = [
                "Vehicle Type" => $withoutSlug
            ];
            $property_slug = $property_slug_data->pluck('name')->first();
            $Caravan->property_slug = $property_slug;
            $Caravan->property = json_encode($result);
            if ($Caravan->save()) {

                Session::flash('success', 'Caravan details updated successfully.');
                return Redirect::to('all-caravan');
            } else {
                Session::flash('success', 'Unable to update Caravan details!');
                return Redirect::to('caravan-edit/' . $request->id);
            }
        }

    }


    public function addCaravan()
    {
        if (!(parent::checkWritePrivilege(16))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $Attributes = ServiceAttribute::where('service', 'caravan')->pluck('name', 'id');

        $CarAttributes = array();
        foreach ($Attributes as $key => $value) {
            $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
            $CarAttributes = $AttributeValue; 
        }

        $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();

        return view('caravan.add-caravan', compact('Vendors', 'CarAttributes', 'CityDetail'));
    }

    public function caravanAddRequest(Request $request)
    {
        $priceField = 'price_per_day' . $request->car_book_type;
        $quantityField = 'quantity' . $request->car_book_type;

        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'title' => 'required|string|min:3|max:100',
            'contents' => 'required|string',
            'feature_image' => 'required|mimes:jpeg,png,jpg',
            'banner_image' => 'required|mimes:jpeg,png,jpg',
            'images.*' => 'required|mimes:jpeg,png,jpg',
            'address' => 'required|string',
            'city' => 'required|string',
            'status' => 'required|string',
            $quantityField => 'required|numeric',
            'car_book_type' => 'required|numeric',
            'terms_conditions' => 'required|string',
            'contact_number' => 'required|digits:10',
            'contact_email' => 'required|email',
            'show_price' => 'required',
            $priceField => 'required|numeric',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('caravan-add')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/caravans/';
            $gallery_images = array();
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                }
            }
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
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
            $faqs = '';
            if ($request->faqs) {
                $data = array();
                foreach ($request->faqs as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $faqs = json_encode($data);
            }
            $slug = Str::uuid()->toString() . '-' . Str::random(16);

            $property_slug_data = AttributeValue::where('id', $request->car_book_type)->get(['name', 'icon', 'slug']); 
            $withoutSlug = $property_slug_data->map(function ($item) {
                return [
                    'name' => $item->name,
                    'icon' => $item->icon,
                ];
            })->toArray();
            $result = [
                "Vehicle Type" => $withoutSlug
            ];
            $property_slug = $property_slug_data->pluck('name')->first(); // create a pluck for slug
    
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) {
                return !empty(trim($n));
            });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) {
                return !empty(trim($n));
            });
           
            $Caravan = new MasterCaravan([
                'vendor_id' => $request->vendor_id,
                'title' => trim($request->title),
                'slug' => $slug,
                'content' => addslashes($request->contents),
                'feature_image' => $UploadDir . $feature_image,
                'banner_image' => $UploadDir . $banner_image,
                'gallery' => json_encode($gallery_images),
                'city' => $request->city,
                'address' => addslashes($request->address),
                'map_lat' => $request->map_lat,
                'map_lng' => $request->map_lng,
                'video' => $request->video,
                'faqs' => $faqs,
                'quantity' => $request->$quantityField,
                'price_per_day' => $request->$priceField,
                'contact_email' => $request->contact_email,
                'contact_number' => $request->contact_number,
                'additional_email' => implode(",", $add_email),
                'additional_phone' => implode(",", $add_phone),
                'terms_conditions' => $request->terms_conditions,
                'status' => $request->status,
                'gst_applicable' => $request->gst_applicable,
                'create_user' => Auth::user()->id,
                'show_price' => $request->show_price,
                'gst_number' => $request->gst_number,
                'gst_legal_name' => $request->gst_legal_name,
                'car_book_type' => $request->car_book_type,
                'property' => @json_encode($result),
                'property_slug' => $property_slug,
            ]);
            if ($Caravan->save()) {
                $CaravanId = $Caravan->id;
                $date = date("Y-m-d");
                $inventory = array();
                for ($i = 0; $i < 120; $i++) {
                    $date1 = date("Y-m-d", strtotime($date . ' + ' . $i . ' days'));
                    $inventory[$i] = [
                        'vendor_id' => $Caravan->vendor_id,
                        'caravan_id' => $CaravanId,
                        'days' => 1,
                        'date' => $date1,
                        'initial_quantity' => $request->$quantityField,
                        'total_available' => $request->$quantityField,
                        'total_booked' => 0,
                        'total_blocked' => 0,
                        'total_online_completed' => 0,
                        'total_online_pending' => 0,
                        'total_offline_completed' => 0,
                        'total_offline_pending' => 0,
                    ];
                }
                CaravanMasterInventory::insert($inventory);

                Session::flash('success', 'Caravan added successfully.');
                return Redirect::to('all-caravan');
            } else {
                Session::flash('success', 'Unable to add Caravan');
                return Redirect::to('caravan-add');
            }
        }
    }

    public function caravanAttribute()
    {
        if (!(parent::checkViewPrivilege(18))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $CarAttributes = ServiceAttribute::where('service', 'caravan')->get();

        return view('caravan.add-attributes', compact('CarAttributes'));
    }


    public function caravanAttributeAddRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(18))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);
        $slug = Str::uuid()->toString() . '-' . Str::random(16);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('caravan-attribute')->withErrors($validate)->withInput();
        } else {
            $CarAttribute = new ServiceAttribute([
                'name' => $request->name,
                'service' => 'caravan',
                'slug' => $slug
            ]);
            if ($CarAttribute->save()) {
                Session::flash('success', 'Caravan attribute added successfully.');
                return Redirect::to('caravan-attribute');
            } else {
                Session::flash('success', 'Unable to add attribute');
                return Redirect::to('caravan-attribute');
            }
        }
    }

    public function caravanAttributeTerm($id = null)
    {
        if (!(parent::checkViewPrivilege(18))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $ServiceAttribute = ServiceAttribute::find($id);
        $AttributeTerms = AttributeValue::where('attr_id', $id)->get();
        $site_url = $this->site;

        return view('caravan.caravan-attribute-term', compact('ServiceAttribute', 'AttributeTerms', 'site_url'));
    }

    public function caravanAttributeTermAddRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(18))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'attr_id' => 'required|numeric',
            'icon' => 'mimes:jpeg,png,jpg'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('caravan-attribute-terms/' . $request->attr_id)->withErrors($validate)->withInput();
        } else {
            $icon_image = '';
            $UploadDir = 'images/attributes/';
            if ($request->hasFile('icon')) {
                if ($request->file('icon')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('icon')->getClientOriginalName());
                    $icon = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->icon->extension();
                    $request->icon->move(public_path($UploadDir), $icon);
                    $icon_image = $UploadDir . $icon;
                }
            }
            $AttributeTerm = new AttributeValue([
                'name' => $request->name,
                'icon' => $icon_image,
                'attr_id' => $request->attr_id,
                'slug' => Str::uuid()->toString() . '-' . Str::random(16),
            ]);
            if ($AttributeTerm->save()) {
                Session::flash('success', 'Attribute term added successfully.');
                return Redirect::to('caravan-attribute-terms/' . $request->attr_id);
            } else {
                Session::flash('success', 'Unable to add attribute term');
                return Redirect::to('caravan-attribute-terms/' . $request->attr_id);
            }
        }
    }


    /**
     * Author: Esha Pandey
     * Purpose: Display list of all blocked caravans
     */
    public function caravanBlockData()
    {
        if (!(parent::checkViewPrivilege(57))) {
            Session::flash('error', 'You are not authorised to view this page.');
            return redirect()->back();
        }
        $blockedCaravans = BlockedCaravan::orderBy('block_date', 'desc')->get();

        return view('caravan.caravan-block', compact('blockedCaravans'));
    }

    /**
     * Load form page to block caravans
     */
    public function blockedCaravan()
    {
        if (!(parent::checkViewPrivilege(57))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('caravan.blocked-caravan');
    }

    /**
     * Handle caravan blocking request submission
     **/
    public function blockRentalCaravan()
    {
        if (!(parent::checkWritePrivilege(57))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterCaravan = MasterCaravan::where('status', 'publish')->where('vendor_id', $vender_id)->orderBy('title', 'ASC')->pluck('title', 'id');
        $caravanHtml = '';
        foreach ($MasterCaravan as $key => $value) {
            $caravanHtml .= '<option value="' . $key . '">' . $value . '</option>';
        }
        return view('caravan.block-caravan', compact('MasterCaravan', 'caravanHtml'));
    }

    /**
     * Handle caravan blocking request submission
     */

    public function blockRentalCaravanRequest(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'caravan_id' => 'required|numeric',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'quantity' => 'required|numeric|min:1',
            'block_reason' => 'required|string'
        ]);

        if ($validate->fails()) {
            return Redirect::to('block-rental-caravan')
                ->withErrors($validate)
                ->withInput();
        }

        $caravan_id = $request->caravan_id;
        $quantity = (int) $request->quantity;

        $start = date('Y-m-d', strtotime($request->from_date));
        $end = date('Y-m-d', strtotime($request->to_date));

        DB::beginTransaction();

        try {
            $caravan = MasterCaravan::find($caravan_id);
            if (!$caravan) {
                return back()->with('error', 'Caravan not found');
            }

            $current = $start;
            $data = [];

            while ($current <= $end) {
                $inventory = CaravanMasterInventory::where([
                    'caravan_id' => $caravan_id,
                    'date' => $current
                ])->lockForUpdate()->first();
                if (!$inventory) {
                    $inventory = CaravanMasterInventory::create([
                        'caravan_id' => $caravan_id,
                        'date' => $current,
                        'total_available' => $caravan->initial_quantity,
                        'total_blocked' => 0
                    ]);
                }
                if ($inventory->total_available < $quantity) {
                    DB::rollBack();
                    return back()->with('error', "Only {$inventory->total_available} caravan(s) available on $current");
                }
                $data[] = [
                    'vendor_id' => $caravan->vendor_id,
                    'caravan_id' => $caravan_id,
                    'vehicle_name' => $caravan->title,
                    'block_date' => $current,
                    'block_reason' => $request->block_reason,
                    'quantity' => $quantity,
                    'status' => 1,
                    'created_by' => Auth::user()->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $inventory->total_available -= $quantity;
                $inventory->total_blocked += $quantity;
                $inventory->save();
                $current = date('Y-m-d', strtotime($current . ' +1 day'));
            }

            BlockedCaravan::insert($data);

            DB::commit();

            Session::flash('success', 'Caravan blocked successfully.');
            return Redirect::to('caravan-block-data');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     *  Check the number of available Caravans
     */
    public function checkCaravanAvailability(Request $request)
    {
        $caravanId = $request->caravan_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        if (!$caravanId || !$fromDate || !$toDate) {
            return response()->json([
                'status' => false,
                'available' => 0
            ]);
        }
        $minAvailable = CaravanMasterInventory::where('caravan_id', $caravanId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->min('total_available');

        return response()->json([
            'status' => true,
            'available' => max($minAvailable ?? 0, 0)
        ]);
    }


    public function caravanInventory()
    {
        if (!(parent::checkViewPrivilege(60))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterCaravan = MasterCaravan::where('status', 'publish')->where('vendor_id', $vendor_id)->orderBy('title', 'ASC')->pluck('title', 'id');

        return view('caravan.caravan-inventory', compact('MasterCaravan'));
    }

    public function manageCaravanInventory()
    {
        if (!(parent::checkViewPrivilege(69))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterCarQuery = MasterCaravan::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterCarQuery->where('vendor_id', $vender_id);
        }
        $MasterCaravan = $MasterCarQuery->orderBy('title', 'ASC')->pluck('title', 'id');
        return view('caravan.manage-caravan-inventory', compact('MasterCaravan'));
    }

    public function caravanAvailability()
    {
        if (!(parent::checkViewPrivilege(59))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterCarQuery = MasterCaravan::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterCarQuery->where('vendor_id', $vendor_id);
        }
        $MasterCaravan = $MasterCarQuery->orderBy('title', 'ASC')->pluck('title', 'id');

        return view('caravan.caravan-availability', compact('MasterCaravan'));
    }

    public function caravanOprsn(Request $request)
    {
        if ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_caravans')->whereIn('id', $item_array)->update(['status' => 'publish', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicles publish successfully.';
            }
        } elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_caravans')->whereIn('id', $item_array)->update(['status' => 'draft', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicles moved to draft successfully.';
            }
        } elseif ($request->request_type == 'show_price') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_caravans')->whereIn('id', $item_array)->update(['show_price' => 1, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicle price shown successfully.';
            }
        } elseif ($request->request_type == 'hide_price') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('master_caravans')->whereIn('id', $item_array)->update(['show_price' => 0, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'vehicle price hidden successfully.';
            }
        } elseif ($request->request_type == 'delete_car') {
            if (!(parent::checkWritePrivilege(16))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterCar = MasterCaravan::find($request->Id);
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
                    CaravanMasterInventory::where('caravan_id', $MasterCar->id)->delete();
                    CaravanAvailability::where('caravan_id', $MasterCar->id)->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Vehicle deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid vehicle id.';
                }
            }
        } elseif ($request->request_type == 'delete-attribute') {
            if (!(parent::checkWritePrivilege(18))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('service_attributes')->whereIn('id', $item_array)->delete();
                $AttrTerms = AttributeValue::whereIn('attr_id', $item_array)->get();
                if (!empty($AttrTerms)) {
                    foreach ($AttrTerms as $terms) {
                        $image = public_path($terms->icon);
                        if (file_exists($image) && !empty($terms->icon)) {
                            unlink($image);
                        }
                    }
                }
                AttributeValue::whereIn('attr_id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Attributes deleted successfully.';
            }
        } elseif ($request->request_type == 'delete-attribute-term') {
            if (!(parent::checkWritePrivilege(18))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                $AttrTerms = AttributeValue::whereIn('id', $item_array)->get();
                if (!empty($AttrTerms)) {
                    foreach ($AttrTerms as $terms) {
                        $image = public_path($terms->icon);
                        if (file_exists($image) && !empty($terms->icon)) {
                            unlink($image);
                        }
                    }
                }
                AttributeValue::whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Attribute terms deleted successfully.';
            }
        } elseif ($request->request_type == 'save-attribute-changes') {
            if (!(parent::checkWritePrivilege(18))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $ServiceSttribute = ServiceAttribute::find($request->Id);
                $ServiceSttribute->name = $request->attrName;
                if ($ServiceSttribute->save()) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Changes saved successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to save cheanges.';
                }
            }
        } elseif ($request->request_type == 'save-terms-changes') {
            if (!(parent::checkWritePrivilege(18))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $AttributeTerm = AttributeValue::find($request->id);
                if (!empty($AttributeTerm)) {
                    $validate = Validator::make($request->all(), [
                        'name' => 'required|string',
                        'icon' => 'mimes:jpeg,png,jpg',
                    ]);
                    if ($validate->fails()) {
                        $errors = $validate->errors();
                        $responce['status'] = 0;
                        if ($errors->has('name')) {
                            $responce['message'] = $errors->first('name');
                        } elseif ($errors->has('attrStatus')) {
                            $responce['message'] = $errors->first('attrStatus');
                        } elseif ($errors->has('icon')) {
                            $responce['message'] = $errors->first('icon');
                        }
                    } else {
                        $UploadDir = 'images/attributes/';
                        if ($request->hasFile('icon')) {
                            if ($request->file('icon')->isValid()) {
                                $img = public_path($AttributeTerm->icon);
                                if (file_exists($img) && !empty($AttributeTerm->icon)) {
                                    unlink($img);
                                }
                                $filenameWithExt = str_replace(' ', '-', $request->file('icon')->getClientOriginalName());
                                $icon = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->icon->extension();
                                $request->icon->move(public_path($UploadDir), $icon);
                                $AttributeTerm->icon = $UploadDir . $icon;
                            }
                        }
                        $AttributeTerm->name = $request->name;
                        $AttributeTerm->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Changes saved successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid attribute id.';
                }
            }
        } elseif ($request->request_type == 'get_vehicle_quantity') {
            $block_date = date("Y-m-d", strtotime($request->date));
            $OrderData = CaravanMasterInventory::where('caravan_id', $request->caravanId)
                ->where('date', $block_date)
                ->first();
            if (!empty($OrderData)) {
                $responce['status'] = 1;
                $responce['quantity'] = $OrderData->total_available;
            } else {
                $Mastercar = MasterCaravan::find($request->caravanId);
                $responce['status'] = 1;
                $responce['quantity'] = $Mastercar->quantity;
            }
        } elseif ($request->request_type == 'delete-blocked-vehicle') {
            if (!(parent::checkWritePrivilege(57))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = CaravanAvailability::find($request->Id);
                if (!empty($BlockedData)) {
                    if ($BlockedData->block_date >= date("Y-m-d")) {
                        $MasterInventory = CaravanMasterInventory::where(['caravan_id' => $BlockedData->caravan_id, 'date' => $BlockedData->block_date])->first();
                        $MasterInventory->total_available += $BlockedData->quantity;
                        $MasterInventory->total_blocked -= $BlockedData->quantity;
                        $MasterInventory->save();
                    }
                    $BlockedData->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        } elseif ($request->request_type == 'delete-blocked-vehicles') {
            if (!(parent::checkWritePrivilege(57))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = BlockedCaravan::find($request->Id);
                if (!empty($BlockedData)) {
                    $BlockedData->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        } elseif ($request->request_type == 'get_availability_data') {
            $month = (strlen((string) $request->month) == 1) ? '0' . $request->month : $request->month;
            $check_date = $request->year . '-' . $month;
            $MasterInventory = CaravanMasterInventory::select('total_available', 'total_booked', 'date')
                ->where(['caravan_id' => $request->caravanId])
                ->where('date', 'like', $check_date . '%')
                ->get();
            if (!empty($MasterInventory)) {
                $available_data = array();
                foreach ($MasterInventory as $value) {
                    array_push($available_data, array('title' => 'Available: ' . $value->total_available, 'color' => 'green', 'description' => 'Total Available', 'start' => $value->date));
                    array_push($available_data, array('title' => 'Booked: ' . $value->total_booked, 'color' => 'red', 'description' => 'Total Booked', 'start' => $value->date));
                }
                $responce['status'] = 1;
                $responce['data'] = $available_data;
            } else {
                $responce['status'] = 0;
                $responce['data'] = [];
            }
        } elseif ($request->request_type == 'get_caravan_details') {
            $Mastercar = MasterCaravan::find($request->carId);
            if (!empty($Mastercar)) {
                $responce['status'] = 1;
                $responce['data'] = $Mastercar;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Caravan details not found';
            }
        }elseif ($request->request_type == 'calculate_price') {
            $CarDetails = MasterCaravan::find($request->carId);
            if (empty($CarDetails)) {
                $responce['status'] = 0;
                $responce['message'] = 'Caravan details not found';
                echo json_encode($responce);
                exit;
            }

            $quantity = max(1, (int) $request->service_quantity);
            $price_breakup = [];
            $sub_total_price = 0;
            $quantity_array = [];
            $service_details = json_decode($request->day_breakup_details,true);

            if (empty($service_details) || !is_array($service_details)) {
                $responce['status'] = 0;
                $responce['message'] = 'Invalid booking details.';
                echo json_encode($responce);
                exit;
            }

            foreach ($service_details as $val) {
                if (empty($val['date']['startDate']) || empty($val['date']['endDate'])) {

                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid booking date.';
                    echo json_encode($responce);
                    exit;
                }

                $startTimestamp = strtotime($val['date']['startDate']);
                $endTimestamp = strtotime($val['date']['endDate']);

                if ($startTimestamp === false || $endTimestamp === false || $endTimestamp <= $startTimestamp) {
                    $responce['status'] = 0;
                    $responce['message'] = 'Please select a valid booking date range.';

                    echo json_encode($responce);
                    exit;
                }
            }

            $start_date = date('Y-m-d',strtotime($service_details[0]['date']['startDate']));
            $lastBooking = end($service_details);
            $end_date = date('Y-m-d',strtotime($lastBooking['date']['endDate']));

            $BlockedVehicle = BlockedCaravan::where('caravan_id',$CarDetails->id)
            ->whereBetween('block_date', [$start_date, $end_date])
            ->exists();

            if ($BlockedVehicle) {
                $responce['status'] = 0;
                $responce['message'] ='Sorry! The vehicle is not available for selected dates.';
                echo json_encode($responce);
                exit;
            }
            $pricePerDay = (float) $CarDetails->price_per_day;
            foreach ($service_details as $val) {
                $startDate = date('Y-m-d',strtotime($val['date']['startDate']));
                $endDate = date('Y-m-d',strtotime($val['date']['endDate']));
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $bookingDays = (int) $start->diff($end)->days;
                $bookingDays = max(1, $bookingDays);
                $cal_day = $bookingDays + 1;

                for ($i = 0; $i < $cal_day; $i++) {
                    $checkDate = date('Y-m-d',strtotime($startDate . ' + ' . $i . ' days'));
                    $MasterInventory = CaravanMasterInventory::where('caravan_id',$CarDetails->id)
                    ->whereDate('date', $checkDate)
                    ->first();

                    if (empty($MasterInventory) || $MasterInventory->total_available < $quantity) {
                        $quantity_array[] = date('d-m-Y',strtotime($checkDate));
                    }
                }

                if (!empty($quantity_array)) {

                    $responce['status'] = 0;
                    $responce['message'] ='Sorry! The vehicle is not available on date ' . implode(', ', array_unique($quantity_array));

                    echo json_encode($responce);
                    exit;
                }

                $totalPrice = $bookingDays * $pricePerDay * $quantity;
                $price_breakup[] = [
                    'price_per_day' => $pricePerDay,
                    'booking_days' => $bookingDays,
                    'service_quantity' => $quantity,
                    'booking_date' => [
                        'startDate' => date('d-m-Y h:i a',strtotime($val['date']['startDate'])),
                        'endDate' => date('d-m-Y h:i a', strtotime($val['date']['endDate']))
                    ],
                    'totalPrice' => $totalPrice
                ];
                $sub_total_price += $totalPrice;
            }

            $serviceFee = (float) $CarDetails->service_fee;
            $totalOrderPrice = round($sub_total_price + $serviceFee,2);

            $responce['status'] = 1;
            $responce['totalOrderPrice'] = $totalOrderPrice;
            $responce['price_breakup'] = $price_breakup;

            echo json_encode($responce);
            exit;
        }elseif ($request->request_type == 'verify_coupon') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $coupons = Coupon::where(['service_type' => 'caravan', 'coupon_code' => $request->coupon_code, 'vendor_id' => $vendor_id])
                ->where('start_date', '<=', date("Y-m-d", strtotime($request->checkIn)))
                ->where('end_date', '>=', date("Y-m-d", strtotime($request->checkIn)))
                ->where('status', 'publish')
                ->first();
            if (!empty($coupons)) {
                if ($coupons->frequency > $coupons->already_used && $coupons->min_order_amount <= $request->order_value) {
                    $coupon_data = array(
                        'coupon_name' => $coupons->coupon_name,
                        'coupon_code' => $coupons->coupon_code,
                        'coupon_value' => $coupons->coupon_amount
                    );
                    $days = 0;
                    $Mastercar = MasterCaravan::find($request->carId);
                    $checkin = date("Y-m-d", strtotime($request->checkIn));
                    $extra_price = 0;
                    if ($Mastercar->gst_applicable == 1) {
                        $GSTData = GstDetail::pluck('value', 'name')->toArray();
                        $GstTable = GstTable::where(['vendor_id' => $Mastercar->vendor_id, 'service_type' => 'caravan'])
                            ->orderBy('min_amount', 'asc')
                            ->pluck('gst', 'min_amount')->toArray();
                        $GstDetails = array();
                        if (!empty($GstTable)) {
                            foreach ($GstTable as $k => $gst) {
                                $GstDetails[$k] = json_decode($gst, 1);
                            }
                        } else {
                            $GstDetails[0] = $GSTData;
                        }
                        $GstMin = array_keys($GstDetails);
                    }
                    $GstShowData = $tax_array = array();
                    $total_gst = $total_coupon_amt = $SubTotal = $grossPrice = 0;
                    $pricingData = json_decode($request->pricingData, 1);

                    foreach ($pricingData as $value) {
                        $grossPrice += $value['totalPrice'];
                        $coupon_amt = round($value['totalPrice'] * ($coupon_data['coupon_value'] / 100), 2);
                        $total_coupon_amt += $coupon_amt;
                        $price = $value['totalPrice'] - $coupon_amt;
                        $SubTotal += $price;
                        if ($Mastercar->gst_applicable == 1) {
                            $filter_res = array_filter($GstMin, function ($n) use ($price) {
                                return $n <= $price;
                            });
                            if (!empty($filter_res)) {
                                $tempr = $GstDetails[end($filter_res)];
                                if (!empty($tax_array)) {
                                    foreach ($tempr as $g_name => $g_val) {
                                        $tax_amt = ceil($price * ($g_val / 100));
                                        $tax_array[$g_name] += $tax_amt;
                                    }
                                } else {
                                    foreach ($tempr as $g_name => $g_val) {
                                        $tax_amt = ceil($price * ($g_val / 100));
                                        $tax_array[$g_name] = $tax_amt;
                                    }
                                }
                            }
                        }
                    }
                    foreach ($tax_array as $key => $val) {
                        $GstShowData[] = [
                            'gst_name' => $key,
                            'gst_percentage' => $tempr[$key],
                            'gst_value' => ceil($val)
                        ];
                        $total_gst += $val;
                    }
                    $responce['status'] = 1;
                    $responce['gst_data'] = $GstShowData;
                    $responce['grossPrice'] = ceil($grossPrice);
                    $responce['subTotalPrice'] = ceil($SubTotal);
                    $responce['discount_amount'] = ceil($total_coupon_amt);
                    $responce['totalOrderPrice'] = ceil($SubTotal + $total_gst + $Mastercar->service_fee);
                    $responce['coupon_data'] = $coupon_data;
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Coupon is not applicable.';
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Invalid coupon code.';
            }
        } elseif ($request->request_type == 'change_master_inventory') {
            if (!(parent::checkWritePrivilege(69))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterInventory = CaravanMasterInventory::find($request->inventoryId);
                if (!empty($MasterInventory)) {
                    $changeType = $request->changeType;
                    $changeQty = $request->changeQty;
                    $initial_qty = $total_available = 0;
                    if ($changeType == 'decrease') {
                        if ($changeQty > $MasterInventory->total_available) {
                            $responce['status'] = 0;
                            $responce['message'] = 'You can decrease maximum ' . $MasterInventory->total_available . ' number of vehicles.';
                        } else {
                            $initial_qty = $MasterInventory->initial_quantity - $changeQty;
                            $total_available = $MasterInventory->total_available - $changeQty;
                        }
                    } else {
                        $initial_qty = $MasterInventory->initial_quantity + $changeQty;
                        $total_available = $MasterInventory->total_available + $changeQty;
                    }
                    if (!isset($responce['message'])) {
                        $MasterInventory->initial_quantity = $initial_qty;
                        $MasterInventory->total_available = $total_available;
                        $MasterInventory->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Inventory updated successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid data input.';
                }
            }
        } elseif ($request->request_type == 'change_block_inventory') {
            if (!parent::checkWritePrivilege(69)) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not authorised to do this operation.';
            } else {
                DB::beginTransaction();
                try {
                    $MasterInventory = CaravanMasterInventory::where('id',$request->inventoryId)->lockForUpdate()->first();

                    if (empty($MasterInventory)) {
                        throw new Exception('Invalid data input.');
                    }

                    $changeQty = (int) $request->releaseQty;
                    if ($changeQty > $MasterInventory->total_blocked) {
                        throw new Exception('You can release maximum ' .$MasterInventory->total_blocked .' number of vehicles.');
                    }

                    if ($changeQty < 1) {
                        throw new Exception('You can release minimum 1 vehicle.');
                    }
                    $blockedInventory = BlockedCaravan::where('caravan_id',$MasterInventory->caravan_id)
                    ->whereDate('block_date', $request->block_date)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->first();

                    if (empty($blockedInventory)) {
                        throw new Exception('No blocked inventory found for this date.');
                    }

                    if ($changeQty > $blockedInventory->quantity) {
                        throw new Exception('You can release maximum ' . $blockedInventory->quantity . ' vehicles for this date.');
                    }

                    $blockedInventory->quantity -= $changeQty;

                    if ($blockedInventory->quantity == 0) {
                        $blockedInventory->status = 0;
                    }

                    $blockedInventory->save();

                    $MasterInventory->total_available += $changeQty;
                    $MasterInventory->total_blocked -= $changeQty;
                    $MasterInventory->save();

                    DB::commit();
                    $responce['status'] = 1;
                    $responce['message'] = 'Inventory updated successfully.';
                } catch (\Exception $e) {
                    DB::rollBack();
                    $responce['status'] = 0;
                    $responce['message'] = $e->getMessage();
                }
            }
        } elseif ($request->request_type == 'export_caravan_mis_report') {
            $MasterCarQuery = MasterCaravan::where('status', 'publish');
            if (Auth::user()->access_type == 'vendor') {
                $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                $MasterCarQuery->where('vendor_id', $vender_id);
            }
            $MasterCar = $MasterCarQuery->pluck('title', 'id');

            $headerArr = array('Date');
            $headerArr = array_merge($headerArr, $MasterCar->toArray());

            $csv = "documents/caravan_mis_report" . time() . ".csv";
            $csvname = public_path($csv);
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);

            $MisData = array();
            foreach ($MasterCar as $key => $value) {
                $MasterInventory = CaravanMasterInventory::select('date', DB::raw('SUM(total_booked) as totalBook'))
                    ->where('caravan_id', $key)
                    ->where('date', '>=', date("Y-m-d"))
                    ->groupBy('date')
                    ->pluck('totalBook', 'date');
                foreach ($MasterInventory as $dates => $qty) {
                    $MisData[$dates][$key] = $qty;
                }
            }
            if (!empty($MisData)) {
                foreach ($MisData as $key => $value) {
                    $data['date'] = $key;
                    foreach ($MisData[$key] as $rid => $qty) {
                        $data[$rid] = $qty;
                    }
                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        } elseif ($request->request_type == 'get_invoice_html') {
            try {
                if (empty($request->orderID)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Order ID is required.'
                    ]);
                }
                $OrderMaster = OrderMaster::where('order_id',$request->orderID)
                    ->where('service_type','caravan')
                    ->first();

                if (empty($OrderMaster)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Caravan order not found.'
                    ]);
                }

                $bookings = DB::table('caravan_bookings as cb')
                    ->join('master_caravans as mc','cb.caravan_id','=','mc.id')
                    ->join('order_masters as om','cb.booking_id','=','om.order_id')
                    ->select(
                        'om.invoice_id',
                        'om.order_id',
                        'om.order_type',
                        'mc.title as caravan_name',
                        'cb.start_date',
                        'cb.end_date',
                        'cb.no_of_days',
                        'cb.totalPrice as booking_amount'
                    )
                    ->where('om.order_id',$request->orderID)
                    ->where('om.service_type','caravan')
                    ->orderBy('cb.start_date','asc')
                    ->get();
                if ($bookings->isEmpty()) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Caravan booking details not found.'
                    ]);
                }

                $CaravanInvoice = DB::table('email_templates')
                    ->where('ref_code','caravanInvoice')
                    ->first();
                if (empty($CaravanInvoice) || empty($CaravanInvoice->source)) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Caravan invoice template not found.'
                    ]);
                }

                $customerName = $OrderMaster->customer_name ?? '';
                $customerPhone = $OrderMaster->customer_phone ?? $OrderMaster->phone ?? $OrderMaster->mobile ?? '';
                $customerEmail =  $OrderMaster->customer_email  ?? $OrderMaster->email ?? '';
                $paymentMethod = $OrderMaster->payment_method ?? $OrderMaster->payment_mode ?? 'PAYTM';
                $transactionId = $OrderMaster->transaction_id ?? $OrderMaster->txnid ?? '';
                $paymentReference = $OrderMaster->payuid ?? '';
                $grandTotal = (float) ($OrderMaster->total_order_price ?? 0);

                $mainLogo = '';
                $mainLogoPath = public_path('images/frontend/otdc_white.jpg');
                if (file_exists($mainLogoPath)) {
                    $mainLogoType = pathinfo($mainLogoPath, PATHINFO_EXTENSION);
                    $mainLogo ='data:image/' . $mainLogoType . ';base64,' . base64_encode( file_get_contents($mainLogoPath));
                }
                $companyLogo = '';
                $companyLogoPath = public_path('images/profile/otdc1_1_1746204931.png');

                if (file_exists($companyLogoPath)) {

                    $companyLogoType = pathinfo($companyLogoPath,PATHINFO_EXTENSION);
                    $companyLogo ='data:image/' . $companyLogoType . ';base64,' . base64_encode(file_get_contents($companyLogoPath));
                }
                $caravanPricing = '';
                foreach ($bookings as $booking) {
                    $bookingId = htmlspecialchars($booking->invoice_id ?? '', ENT_QUOTES,'UTF-8');
                    $caravanName = htmlspecialchars($booking->caravan_name ?? '', ENT_QUOTES,'UTF-8');

                    $startDate = '';
                    if (!empty($booking->start_date)) {
                        $startDate = date('d M Y',strtotime($booking->start_date));
                    }

                    $endDate = '';
                    if (!empty($booking->end_date)) {
                        $endDate = date('d M Y',strtotime($booking->end_date));
                    }

                    if (!empty($startDate) && !empty($endDate)) {
                        $dateRange = $startDate . ' - ' . $endDate;
                    } elseif (!empty($startDate)) {
                        $dateRange = $startDate;
                    } else {
                        $dateRange = '-';
                    }


                    if (!empty($booking->no_of_days)) {
                        $days = $booking->no_of_days;
                    } elseif (!empty($booking->start_date) && !empty($booking->end_date)) {
                        $start = Carbon::parse($booking->start_date);
                        $end = Carbon::parse($booking->end_date);
                        $days = $start->diffInDays($end) + 1;
                    } else {
                        $days = 1;
                    }

                    $amount = (float) ($booking->booking_amount ?? 0);
                    $caravanPricing .= '

                        <tr>
                            <td align="center" valign="middle" style="color:#000; border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"> ' . $bookingId . '
                            </td>
                            <td align="center" valign="middle" style=" color:#000; border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;">
                                ' . $caravanName . '
                            </td>
                            <td align="center" valign="middle" style="color:#000; border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"> ' .
                                htmlspecialchars($dateRange,ENT_QUOTES,'UTF-8')
                                . '
                            </td>
                            <td align="center" valign="middle" style="color:#000;  border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;">
                                ' . $days . '
                            </td>
                            <td align="right" valign="middle" style="color:#000; border-bottom:1px solid #000; padding:5px;"> ' . number_format($amount,2) . '
                            </td>
                        </tr>
                    ';
                }

                $orderDateValue = $OrderMaster->created_at ?? $OrderMaster->created_on ?? now();
                $orderDate = date('d M Y h:i a',strtotime($orderDateValue));
                $Message = str_replace(
                    array(
                        "~otdcLogo~",
                        "~username~",
                        "~usermobile~",
                        "~usermail~",
                        "~vendorLogo~",
                        "~orderdate~",
                        "~orderdetails~",
                        "~ordertotal~",
                        "~paymentmethod~",
                        "~txnid~",
                        "~payuid~"
                    ),
                    array(
                        $mainLogo,
                        $customerName,
                        $customerPhone,
                        $customerEmail,
                        $companyLogo,
                        $orderDate,
                        $caravanPricing,
                        number_format($grandTotal,2),
                        $paymentMethod,
                        $transactionId,
                        $paymentReference
                    ),
                    $CaravanInvoice->source
                );

                $finalHtml = html_entity_decode($Message, ENT_QUOTES,'UTF-8');

                $directory = public_path('documents');
                if (!is_dir($directory)) {
                    mkdir($directory,0755,true);
                }

                $invoiceNumber =!empty($OrderMaster->invoice_id) ? $OrderMaster->invoice_id : $OrderMaster->order_id;
                $safeInvoiceNumber = preg_replace('/[^A-Za-z0-9_-]/','_',$invoiceNumber);
                $file ='documents/Caravan_Invoice_' . $safeInvoiceNumber . '_' . time() .'.pdf';
                $pdfname = public_path($file);

                PDF::loadHTML($finalHtml)->save($pdfname);
                return response()->json([
                    'status' => 1,
                    'message' =>'Invoice generated successfully.',
                    'data' => asset($file)
                ]);

            } catch (\Throwable $e) {
                return response()->json([
                    'status' =>0,
                    'message' => $e->getMessage() . ' | File: ' . basename($e->getFile()) . ' | Line: ' .$e->getLine()
                ]);
            }
        }
        elseif ($request->request_type == 'export_caravan_summary_report') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/caravan_order_summary_report" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Invoice Id', 'Transaction Id', 'PayU Id', 'Order Type', 'Book Though', 'Customer Name', 'Customer Email', 'Customer Phone', 'Customer Address', 'City', 'State', 'Country', 'Zipcode', 'Caravan Name','Booking Date', 'Booking Time', 'Start Date', 'Start Time', 'End Date','Discount', 'Coupon Name','Total Price', 'Status', 'Payment Method', 'Payment Status');
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
                    $data['service_name'] = $value->service_name;
                    $data['created_date'] = date("Y-m-d", strtotime($value->created_at));
                    $data['created_time'] = date("h:i a", strtotime($value->created_at));
                    $data['start_date'] = $value->start_date;
                    $data['start_time'] = $value->start_time;
                    $data['end_date'] = $value->end_date;
                    $data['end_time'] = $value->end_time;
                    $data['coupon_name'] = $value->coupon_name;
                    $data['total_order_price'] = $value->total_order_price;
                    $data['status'] = $value->status;
                    $data['payment_gateway'] = $value->payment_method;
                    $data['payment_status'] = $value->payment_status;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }
        elseif ($request->request_type == 'export_caravan_avaliability_report') {
            $user = Auth::user();
            $MasterCaravanQuery = MasterCaravan::whereNull('deleted_at');
            if ($user->access_type == 'vendor') {
                $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
                $MasterCaravanQuery->where('vendor_id', $vendorId);
            }

            $MasterCaravan = $MasterCaravanQuery->pluck('title', 'id');
            $CaravanId = (int) $request->caravan_id;
            if (!$MasterCaravan->has($CaravanId)) {
                abort(403, 'You cannot access the selected caravan.');
            }

            $SelectedCaravan = MasterCaravan::where('id', $CaravanId)
                ->whereNull('deleted_at')
                ->first();
            $TotalCaravan = (int) ($SelectedCaravan->quantity ?? 0);
            $range = preg_split('/\s+-\s+/', trim($request->check_date));

            if (!is_array($range) || count($range) != 2) {
                return back()->withErrors([
                    'check_date' => 'Please select a valid date range.'
                ]);
            }
            try {
                $startDate = Carbon::createFromFormat('d-m-Y',trim($range[0]))->startOfDay();
                $endDate = Carbon::createFromFormat('d-m-Y',trim($range[1]))->startOfDay();
            } catch (\Exception $e) {

                return back()->withErrors([
                    'check_date' => 'Please select a valid date range.'
                ]);
            }

            if ($endDate->lt($startDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            // Get Inventory Data
            $inventoryData = CaravanMasterInventory::where('caravan_id', $CaravanId)
                ->whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ])
                ->select('date',DB::raw('SUM(total_booked + total_blocked) as total_booked'))
                ->groupBy('date')
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });

            // Create CSV
            $csv = "documents/caravan_availability_report_" . time() . ".csv";
            $csvname = public_path($csv);
            if (!file_exists(dirname($csvname))) {
                mkdir(dirname($csvname), 0755, true);
            }

            $fp = fopen($csvname, 'w');

            fputcsv($fp, [
                'Date',
                'Total Caravan',
                'Booked Caravan',
                'Available Caravan'
            ]);

            $period = CarbonPeriod::create($startDate->copy(),$endDate->copy());
            foreach ($period as $date) {
                $formattedDate = $date->format('Y-m-d');
                $booked = (int) ($inventoryData->get($formattedDate)->total_booked ?? 0);
                $available = max($TotalCaravan - $booked, 0);
                fputcsv($fp, [
                    $date->format('d-M-Y'),
                    $TotalCaravan,
                    $booked,
                    $available 
                ]);
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }
        elseif ($request->request_type == 'export_caravan_booking_report') {
            $user = Auth::user();
            $caravanQuery = MasterCaravan::query()
                ->whereNull('deleted_at');

            if ($user->access_type == 'vendor') {
                $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
                $caravanQuery->where('vendor_id', $vendorId);
            }

            $MasterCaravan = $caravanQuery->pluck('title', 'id');
            $caravan_id = $request->caravan_id ?? 0;
            $report_type = $request->report_type ?? 'book_date';
            $check_date = $request->check_date;

            try {
                $date = Carbon::createFromFormat('d-m-Y',$check_date)->format('Y-m-d');
            } catch (\Exception $exception) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Please select a valid date.'
                ], 422);
            }
            if (!in_array($report_type, ['book_date', 'travel_date'])) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Invalid report type.'
                ], 422);
            }

            if (!empty($caravan_id) &&  (int) $caravan_id !== 0 && !$MasterCaravan->has((int) $caravan_id)) {
                abort(403, 'You cannot access the selected caravan.');
            }

            $query = DB::table('caravan_bookings as cb')
                ->join('master_caravans as mc', 'cb.caravan_id', '=', 'mc.id')
                ->join('order_masters as om', 'cb.booking_id', '=', 'om.order_id')
                ->select(
                    'om.invoice_id',
                    'om.order_id',
                    'cb.created_at as booking_date',
                    'om.customer_name',
                    'mc.title as caravan_name',
                    'cb.start_date',
                    'cb.end_date',
                    'cb.totalPrice as total_amount',
                    'om.status'
                )
                ->where('om.service_type', 'caravan');
            if ($user->access_type == 'vendor') {
                $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
                $query->where('cb.vendor_id', $vendorId);
            }

            if (!empty($caravan_id) && (int) $caravan_id !== 0) {
                $query->where('cb.caravan_id', (int) $caravan_id);
            }

            if ($report_type == 'book_date') {
                $query->whereDate('cb.created_at', $date);
            } else {
                $query->whereDate('cb.start_date', '<=', $date)
                    ->whereDate('cb.end_date', '>=', $date);
            }

            $bookings = $query
                ->orderBy('cb.created_at', 'desc')
                ->get();

            $csv = 'documents/caravan_booking_report_' . time() . '.csv';
            $csvname = public_path($csv);
            if (!file_exists(dirname($csvname))) {
                mkdir(dirname($csvname), 0755, true);
            }
            $fp = fopen($csvname, 'w');
            fputcsv($fp, [
                'Sl No',
                'Booking Id',
                'Booking Date',
                'Customer Name',
                'Caravan Name',
                'Travel Period',
                'Total Amount',
                'Booking Status'
            ]);

            $slNo = 1;

            foreach ($bookings as $row) {
                $bookingDate = !empty($row->booking_date) ? Carbon::parse($row->booking_date)->format('d-m-Y')  : 'N/A';
                $travelPeriod = 'N/A';

                if (!empty($row->start_date) && !empty($row->end_date)) {
                    $startDate = Carbon::parse($row->start_date)->format('d-m-Y');
                    $endDate = Carbon::parse($row->end_date)->format('d-m-Y');
                    $travelPeriod = ($startDate == $endDate) ? $startDate : $startDate . ' - ' . $endDate;
                } elseif (!empty($row->start_date)) {

                    $travelPeriod = Carbon::parse($row->start_date)->format('d-m-Y');
                }

                fputcsv($fp, [
                    $slNo++,
                    $row->invoice_id ?? 'N/A',
                    $bookingDate,
                    $row->customer_name ?? 'N/A',
                    $row->caravan_name ?? 'N/A',
                    $travelPeriod,
                    number_format((float) ($row->total_amount ?? 0), 2),
                    $row->status ?? 'N/A'
                ]);
            }

            fclose($fp);

            return response()
                ->download($csvname)
                ->deleteFileAfterSend(true);
        }

        echo json_encode($responce);
        exit;
    }

    public function getCaravanMasterInventory()
    {
        $aColumns = array('date', 'caravan_id', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked');
        $sIndexColumn = "id";
        $sTable = "caravan_master_inventory";
        $sLimit = "";
        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }
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

        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $sWhere = 'WHERE `vendor_id` = ' . $vender_id;
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND caravan_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $check_date = explode(' - ', $_POST['searchValue2']);
                $start = date("Y-m-d", strtotime($check_date[0]));
                $end = date("Y-m-d", strtotime($check_date[1]));
                $condition2 .= ' AND date between "' . $start . '" AND "' . $end . '"';
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

        $sQuery = "SELECT SQL_CALC_FOUND_ROWS *  FROM $sTable $sWhere $sOrder $sLimit";
        $rResult = DB::select($sQuery);

        $sQuery = "SELECT FOUND_ROWS() as totalrow";
        $aResultFilterTotal = DB::select($sQuery);
        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;
        $sQuery = "SELECT COUNT(`" . $sIndexColumn . "`) as countindex FROM $sTable $sWhere";
        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );
        $MasterCar = MasterCaravan::pluck('title', 'id');

        foreach ($rResult as $aRow) {
            $row = array();

            $row[] = date("d M Y", strtotime($aRow->date));
            $row[] = $MasterCar[$aRow->caravan_id];
            $row[] = '<a href="javascript:void(0)" class="change-qty" title="Click to change quantity" data-toggle="modal" data-target="#changeQtyModal" data-available="' . $aRow->total_available . '" data-id="' . $aRow->id . '" data-qty="' . $aRow->initial_quantity . '">' . $aRow->initial_quantity . '</a>';
            $row[] = $aRow->total_available;
            $row[] = $aRow->total_booked;
            $row[] = ($aRow->total_blocked > 0)  ? '<a href="javascript:void(0)" class="release-qty" title="Click to release quantity" data-toggle="modal" data-target="#changeBlockModal" data-blocked="' . $aRow->total_blocked . '" data-id="' . $aRow->id . '" data-date="' . $aRow->date . '">' . $aRow->total_blocked . '</a>' : $aRow->total_blocked;

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    public function getCaravanInventory(Request $request)
    {

        $aColumns = array('date', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked');
        $sIndexColumn = "id";
        $sTable = "caravan_master_inventory";
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
        $sWhere = 'WHERE `vendor_id` = ' . $vender_id;
        if (!empty($_POST['searchValue1']) || !empty($_POST['searchValue2'])) {
            $condition1 = '';
            $condition2 = '';
            if (!empty($_POST['searchValue1'])) {
                $_POST['searchValue1'] = parent::cleanString($_POST['searchValue1']);
                $condition1 .= ' AND caravan_id = "' . $_POST['searchValue1'] . '"';
            }
            if (!empty($_POST['searchValue2'])) {
                $check_date = explode(' - ', $_POST['searchValue2']);
                $start = date("Y-m-d", strtotime($check_date[0]));
                $end = date("Y-m-d", strtotime($check_date[1]));
                $condition2 .= ' AND date between "' . $start . '" AND "' . $end . '"';
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
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS date, SUM(`initial_quantity`) initial_quantity, SUM(`total_available`) total_available, SUM(`total_booked`) total_booked, SUM(`total_blocked`) total_blocked FROM $sTable $sWhere GROUP BY date $sOrder $sLimit";

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
            $OrderMaster = OrderMaster::select(DB::raw('sum(service_quantity) AS totCancelled'))
                ->where(['service_type' => 'caravan', 'service_name_id' => $_POST['searchValue1'], 'status' => 'cancelled', 'payment_status' => 'success'])
                ->where('start_date', '<=', $aRow->date)
                ->where('end_date', '>=', $aRow->date)
                ->first();
            $total_cancelled = 0;
            if (!empty($OrderMaster)) {
                $total_cancelled = $OrderMaster->totCancelled;
            }

            $row[] = date("d M Y", strtotime($aRow->date));
            $row[] = $aRow->initial_quantity;
            $row[] = $aRow->total_available;
            $row[] = ($aRow->total_booked > 0) ? '<a href="' . url('rental-orders?vehicleId=' . $_POST['searchValue1'] . '&date=' . $aRow->date) . '" target="_blank" >' . $aRow->total_booked . '</a>' : $aRow->total_booked;
            $row[] = $aRow->total_blocked;
            $row[] = $total_cancelled;

            $output['data'][] = $row;
        }
        echo json_encode($output);
        exit;
    }

    /**
     * Caravan MIS Report
     *
     * Builds a date-wise summary of upcoming bookings
     * for each caravan, with vendor-based access control.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function caravanMisReport()
    {
        if (!(parent::checkViewPrivilege(73))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $MasterCaravanQuery = MasterCaravan::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $MasterCaravanQuery->where('vendor_id', $vender_id);
        }
        $MasterCaravan = $MasterCaravanQuery->pluck('title', 'id');

        $MisData = array();
        foreach ($MasterCaravan as $key => $value) {
            $MasterInventory = CaravanMasterInventory::select('date', DB::raw('SUM(total_booked) as totalBook'))
                ->where('caravan_id', $key)
                ->where('date', '>=', date("Y-m-d"))
                ->groupBy('date')
                ->pluck('totalBook', 'date');
            foreach ($MasterInventory as $dates => $qty) {
                $MisData[$dates][$key] = $qty;
            }
        }

        return view('caravan.caravan-mis-report', compact('MisData', 'MasterCaravan'));
    }

    public function caravanBookingReport(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error','Your session has expired. Please login again.');
        }

        $caravanQuery = MasterCaravan::query()
            ->whereNull('deleted_at');

        if ($user->access_type == 'vendor') {
            $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
            $caravanQuery->where('vendor_id',$vendorId);
        }


        $MasterCaravan = $caravanQuery
            ->orderBy('title','asc')
            ->pluck('title','id');

        $shouldFetch = $request->has('check_date');
        $caravan_id = $request->filled('caravan_id') ? $request->caravan_id : 0;
        $report_type = $request->filled('report_type') ? $request->report_type : 'book_date';
        $check_date = $request->filled('check_date') ? $request->check_date : Carbon::today()->format('d-m-Y');

        try {
            $date = Carbon::createFromFormat('d-m-Y',$check_date)->format('Y-m-d');
        } catch (\Exception $exception) {
            return redirect()
                ->route('caravan-booking-report')
                ->withErrors([
                    'check_date' =>'Please select a valid date.'
                ]);
        }

        $query = DB::table('caravan_bookings as cb')
            ->join('master_caravans as mc','cb.caravan_id','=','mc.id')
            ->join('order_masters as om','cb.booking_id','=','om.order_id')
            ->select(
                'om.invoice_id',
                'om.order_id',
                'cb.created_at as booking_date',
                'om.customer_name',
                'mc.title as caravan_name',
                'cb.start_date',
                'cb.end_date',
                'cb.totalPrice as total_amount',
                'om.status',
                'cb.booking_id as bookingID'
            )
            ->where('om.service_type','caravan');

        if ($user->access_type == 'vendor') {
            $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
            $query->where('cb.vendor_id',$vendorId);
        }

        if (!empty($caravan_id) && (int) $caravan_id !== 0) {
            $query->where('cb.caravan_id',(int) $caravan_id);
        }

        if ($report_type == 'book_date') {
            $query->whereDate('cb.created_at',$date);

        } else {
            $query
                ->whereDate('cb.start_date','<=',$date)
                ->whereDate('cb.end_date','>=',$date);
        }

        if ($shouldFetch) {
            $bookings = $query
                ->orderBy('cb.created_at','desc')
                ->get();
        } else {
            $bookings = collect();
        }

        $CaravanBookingData = [];

        foreach ($bookings as $row) {
            $bookingDate = !empty($row->booking_date) ? Carbon::parse($row->booking_date)->format('d-m-Y') : 'N/A';

            $travelPeriod = 'N/A';
            if (!empty($row->start_date) && !empty($row->end_date)) {
                $startDate = Carbon::parse($row->start_date)->format('d-m-Y');
                $endDate = Carbon::parse($row->end_date)->format('d-m-Y');

                if ($startDate == $endDate) {
                    $travelPeriod = $startDate;
                } else {
                    $travelPeriod = $startDate . ' - ' . $endDate;
                }

            } elseif (!empty($row->start_date)) {
                $travelPeriod = Carbon::parse($row->start_date)->format('d-m-Y');
            }

            $CaravanBookingData[] = [
                'invoice_id' => $row->invoice_id ?? 'N/A',
                'order_id' => $row->order_id ?? '',
                'booking_date' => $bookingDate,
                'customer_name' =>$row->customer_name ?? 'N/A',
                'caravan_name' => $row->caravan_name ?? 'N/A',
                'travel_period' =>$travelPeriod,
                'total_amount' => number_format((float) ($row->total_amount ?? 0), 2),
                'status' => $row->status ?? 'N/A',
                'oderID' => $row->order_id ?? '',
                'link' => 1
            ];
        }

        return view('caravan.caravan-booking-report',
            compact(
                'MasterCaravan',
                'CaravanBookingData',
                'caravan_id',
                'report_type',
                'check_date'
            )
        );
    }

    public function caravanAvailabilityReport(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error','Your session has expired. Please login again.');
        }

        $caravanQuery = MasterCaravan::query() ->whereNull('deleted_at');

        if ($user->access_type == 'vendor') {
            $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
            $caravanQuery->where('vendor_id',$vendorId);
        }

        $MasterCaravan = $caravanQuery
            ->orderBy('title','asc')
            ->pluck('title','id');

        $CaravanId = $request->get('caravan_id');
        if (empty($CaravanId) && $MasterCaravan->isNotEmpty()) {

            $CaravanId = $MasterCaravan ->keys() ->first();
        }

        $CaravanId = !empty($CaravanId) ? (int) $CaravanId : null;

        if ($CaravanId !== null && !$MasterCaravan->has($CaravanId)) {
            abort(403,'You cannot access the selected caravan.');
        }

        $SelectedCaravan = null;

        if ($CaravanId) {
            $SelectedCaravan = MasterCaravan::where('id',$CaravanId)
                ->whereNull('deleted_at')
                ->first();
        }

        $CaravanName = $SelectedCaravan ? $SelectedCaravan->title : '';
        $TotalCaravan = $SelectedCaravan ? (int) ($SelectedCaravan->quantity ?? 0) : 0;
        $startDate = Carbon::today()
            ->startOfDay();

        $endDate = Carbon::today()
            ->addDays(30)
            ->endOfDay();

        if ($request->filled('check_date')) {
            try {
                $range = preg_split('/\s+-\s+/',trim($request->get('check_date')));
                if (!is_array($range) || count($range) != 2) {
                    throw new \Exception(
                        'Invalid date range'
                    );
                }

                $startDate = Carbon::createFromFormat('d-m-Y',trim($range[0]))->startOfDay();
                $endDate = Carbon::createFromFormat('d-m-Y',trim($range[1]))->endOfDay();

            } catch (\Exception $exception) {
                return redirect()
                    ->route('caravan-availability-report')
                    ->withErrors([
                        'check_date' =>'Please select a valid date range.'
                    ]);
            }
        }

        if ($endDate->lt($startDate)) {
            $oldStartDate = $startDate->copy();
            $startDate =$endDate ->copy()->startOfDay();
            $endDate = $oldStartDate->copy()->endOfDay();
        }
        $CaravanReportData = [];

        $period = CarbonPeriod::create(
            $startDate->copy()->startOfDay(),
            $endDate->copy()->startOfDay()
        );

        $inventoryData = CaravanMasterInventory::where('caravan_id', $CaravanId)
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->select('date',DB::raw('SUM(total_booked + total_blocked) as total_booked'))
            ->groupBy('date')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $booked = (int) ($inventoryData->get($formattedDate)->total_booked ?? 0);
            $available = max($TotalCaravan - $booked, 0);
            $CaravanReportData[] = [
                'date' => $formattedDate,
                'display_date' => $date->format('d-M-Y'),
                'booked' => $booked,
                'total' => $TotalCaravan,
                'available' => $available
            ];
        }

        return view('caravan.caravan-availability-report',
            [
                'MasterCaravan' =>$MasterCaravan,
                'CaravanId' =>$CaravanId,
                'CaravanName' =>$CaravanName,
                'CaravanReportData' =>$CaravanReportData,
                'start_date' =>$startDate->format('Y-m-d'),
                'end_date' =>$endDate->format('Y-m-d')
            ]
        );
    }

    public function caravanOrders()
    {
        if (!(parent::checkViewPrivilege(30))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $AgentData = User::where(['access_type' => 'agent', 'role' => 3, 'vendor_id' => $vender_id])->pluck('first_name', 'id');
        $Agents = json_encode($AgentData);

        return view('caravan.caravan-orders', compact('Vendors', 'Agents'));
    }

    public function getCaravanOrders(Request $request)
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
        $sWhere = 'WHERE 1 ' . $vendor_condtition . ' AND service_type = "caravan"';
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

       
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM   $sTable $sWhere $sOrder $sLimit";
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
            $cancel_option = $cancel_option_policy = ''; 
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

    public function caravanOfflineOrder() {
        if (!(parent::checkViewPrivilege(58))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $MasterCaravan = MasterCaravan::where('status', 'publish')->where('vendor_id', $vender_id)->orderBy('title', 'ASC')->pluck('title', 'id');
        $CountryData = Country::pluck('name', 'id');
        $CityData = City::where(['state_id' => '4013'])->pluck('name', 'id')->toArray();
        $CityDetail = array_values($CityData);

        return view('caravan.caravan-offline-order', compact('MasterCaravan', 'CityDetail', 'CountryData'));
    }

    public function createCaravanOrder(Request $request)
    {
        if (!(parent::checkWritePrivilege(58))) {
            Session::flash('success', 'You are not authorised to do this operation.');
            return redirect()->back();
        }

        $MasterCar = $CarDetails = MasterCaravan::find($request->service_name_id);

        if (empty($CarDetails)) {
            Session::flash('success', 'Caravan not found.');
            return Redirect::to('caravan-offline-order');
        }

        $state = explode('~', $request->customer_state);
        $country = explode('~', $request->customer_country);

        $service_details = json_decode($request->room_request, true);

        if (empty($service_details) || !is_array($service_details)) {
            Session::flash('success', 'Invalid booking details.');
            return Redirect::to('caravan-offline-order');
        }

        $bookFlag = 1;
        $quantity_array = [];
        $route_map = [];
        $inventoryDates = [];

        $pickup_address = $service_details[0]['pickup_point'];

        $startDateTime = new DateTime($service_details[0]['date']['startDate']);
        $start_date = $startDateTime->format('Y-m-d');
        $start_time = $startDateTime->format('H:i:s');

        $end_data = end($service_details);

        $endDateTime = new DateTime($end_data['date']['endDate']);
        $end_date = $endDateTime->format('Y-m-d');
        $end_time = $endDateTime->format('H:i:s');

        $drop_city = $end_data['drop_city'];

        $BlockedVehicle = BlockedCaravan::where('caravan_id', $CarDetails->id)
            ->whereBetween('block_date', [$start_date, $end_date])
            ->get();

        if ($BlockedVehicle->isNotEmpty()) {
            Session::flash('success','Unable to create order. Sorry!, the vehicle is not available for these dates. Please change the date and try again.');

            return Redirect::to('caravan-offline-order');
        }

        $dailyPrice = (float) $CarDetails->price_per_day;
        $serviceQuantity = max(1, (int) $request->service_quantity);

        $total_service_price = 0;
        $total_travel_hour = 0;
        $travel_distance = 0;

        $rental_breakdown = json_decode($request->rental_breakdown, true);

        if (!is_array($rental_breakdown)) {
            $rental_breakdown = [];
        }

        foreach ($service_details as $index => $val) {
            $routeStart = new DateTime($val['date']['startDate']);
            $routeEnd = new DateTime($val['date']['endDate']);

            if ($routeEnd <= $routeStart) {
                Session::flash('success', 'End date and time must be after start date and time.');
                return Redirect::to('caravan-offline-order');
            }

            $durationSeconds = $routeEnd->getTimestamp() - $routeStart->getTimestamp();
            $billableDays = max(1, (int) ceil($durationSeconds / 86400));
            $routePrice = $dailyPrice * $billableDays * $serviceQuantity;
            $total_service_price += $routePrice;
            $total_travel_hour += ($durationSeconds / 3600);
            $routeDistance = isset($val['day_km']) ? (float) $val['day_km'] : 0;

            $travel_distance += $routeDistance;

            $route_map[] = [
                'dropPointCity' => $val['drop_city'],
                'dropPonitDetails' => $val['drop_point']
            ];

            if (isset($rental_breakdown[$index])) {
                $rental_breakdown[$index]['total_days'] = $billableDays;
                $rental_breakdown[$index]['totalPrice'] = $routePrice;
                $rental_breakdown[$index]['daily_price'] = $dailyPrice;
                $rental_breakdown[$index]['distance_price'] = 0;
                $rental_breakdown[$index]['halt_price'] = 0;
            }

            $inventoryDate = clone $routeStart;
            $lastInventoryDate = clone $routeEnd;

            while ($inventoryDate->format('Y-m-d') <= $lastInventoryDate->format('Y-m-d')) {
                $dateKey = $inventoryDate->format('Y-m-d');
                $inventoryDates[$dateKey] = $dateKey;
                $inventoryDate->modify('+1 day');
            }
        }


        foreach ($inventoryDates as $checkDate) {
            $MasterInventory = CaravanMasterInventory::where([
                'date' => $checkDate,
                'caravan_id' => $MasterCar->id
            ])->first();

            if (empty($MasterInventory) || $MasterInventory->total_available < 1) {
                $bookFlag = 0;
                $quantity_array[] = date('d-m-Y', strtotime($checkDate));
            }
        }

        if ($bookFlag == 0) {
            Session::flash('success','Unable to create order. Sorry!, the vehicle is not available on date ' . implode(', ', $quantity_array) .'. Please change date and try again.');
            return Redirect::to('caravan-offline-order');
        }

        $coupon_percent = 0;
        $coupon_amt = 0;

        $coupons = Coupon::where(['service_type' => 'caravan','coupon_code' => $request->coupon_code])
            ->where('start_date', '<=', $start_date)
            ->where('end_date', '>=', $start_date)
            ->first();

        if (!empty($coupons)) {
            $coupon_percent = (float) $coupons->coupon_amount;
            $coupon_amt = ceil($total_service_price * ($coupon_percent / 100));
        }

        $coupon_amt = min($coupon_amt, $total_service_price);
        $total_order_price = max(0, $total_service_price - $coupon_amt);

        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $VendorData = User::find($vendor_id);

        if (empty($VendorData)) {
            Session::flash('success', 'Vendor details not found.');
            return Redirect::to('caravan-offline-order');
        }

        $lastIdData = OrderMaster::orderBy('id', 'desc')->first();
        $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
        $booking_id = 'OT-' . time() . '-' . $vendor_id . '-' . $lastId;


        $userId = null;
        if (Auth::user()->user_role == 'agent_staff') {
            $userId = Auth::user()->id;
        } else {
            $UserDetails = User::where('email', $request->customer_email)
                ->orWhere('phone', $request->customer_phone)
                ->first();
            if (!empty($UserDetails)) {
                $userId = $UserDetails->id;
            } else {
                $NewUser = new User([
                    'email' => $request->customer_email,
                    'password' => bcrypt(rand(10000000, 99999999)),
                    'first_name' => $request->customer_name,
                    'phone' => $request->customer_phone,
                    'pincode' => $request->zipcode,
                    'address' => $request->customer_address1,
                    'address2' => $request->address2,
                    'country' => $country[1] ?? null,
                    'state' => $state[1] ?? null,
                    'city' => $request->customer_city,
                    'vendor_id' => 0,
                    'role' => 4,
                    'access_type' => 'customer',
                    'login_type' => 'email',
                    'status' => 1,
                    'create_account_approval' => 0
                ]);
                if ($NewUser->save()) {
                    $userId = $NewUser->id;
                }
            }
        }

        $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))
            ->where('service_type', 'caravan')
            ->where('status', '!=', 'partially-cancelled')
            ->first();

        if ($LastOrder->totOrder > 0) {
            $LastInvoiceId = (int) $LastOrder->totOrder + 1;
            $invoice_id = date('dmY') . 'R00' . $LastInvoiceId;
        } else {
            $invoice_id = date('dmY') . 'R001';
        }


        $status = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'completed' : 'pending';
        $payment_status = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'success' : 'pending';

        $payment_method = ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') ? 'cash' : '';
        $Paytm_Mid = '';
        $split_status = 0;

        if ($request->payment_gateway == 'paytm') {
            $Paytm_Mid = $CarDetails->paytm_mid;
            $split_status = 1;
        } elseif ($request->payment_gateway == 'hdfc') {
            $Paytm_Mid = $CarDetails->hdfc_mid;
            $split_status = 0;
        }

        $admin_amount = 0;
        $vendor_amount = 0;

        $admin_percent = (float) $VendorData->admin_commission;

        if (!empty($Paytm_Mid) && $admin_percent > 0) {
            $vendor_amount = $total_order_price - ceil($total_order_price * ($admin_percent / 100));
            $admin_amount = $total_order_price - $vendor_amount;
        } else {
            $admin_amount = $total_order_price;
            $vendor_amount = 0;
        }

        $order_type = (Auth::user()->user_role == 'agent_staff') ? Auth::user()->first_name : 'offline';

        $OrderMaster = new OrderMaster([
            'order_id' => $booking_id,
            'invoice_id' => $invoice_id,
            'vendor_id' => $vendor_id,
            'vendor_name' => $VendorData->company,
            'order_type' => $order_type,
            'customer_id' => $userId,
            'customer_name' => trim($request->customer_name),
            'customer_email' => trim($request->customer_email),
            'customer_phone' => trim($request->customer_phone),
            'customer_address1' => trim($request->customer_address1),
            'customer_city' => trim($request->customer_city),
            'customer_state' => trim($state[0]),
            'customer_zipcode' => trim($request->customer_zipcode),
            'customer_country' => trim($country[0]),
            'book_naration' => addslashes(trim($request->book_naration)),
            'service_type' => 'caravan',
            'service_name' => $MasterCar->title,
            'service_name_id' => $MasterCar->id,
            'service_city' => $MasterCar->city,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'room_request' => $request->room_request,
            'service_quantity' => $serviceQuantity,
            'pickup_address' => $pickup_address,
            'drop_location' => $drop_city,
            'travel_route' => json_encode($route_map),
            'travel_distance' => $travel_distance,
            'travel_hour' => $total_travel_hour,
            'rental_breakdown' => json_encode($rental_breakdown),
            'total_service_price' => $total_service_price,
            'sub_total_price' => 0,
            'coupon_name' => $request->coupon_name,
            'coupon_code' => $request->coupon_code,
            'coupon_amount' => $coupon_amt,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'service_charge' => 0,
            'total_order_price' => $total_order_price,
            'status' => $status,
            'payment_gateway' => $request->payment_gateway,
            'payment_method' => $payment_method,
            'split_initiate_status' => $split_status,
            'vendor_amount' => $vendor_amount,
            'payment_status' => $payment_status,
            'request_from' => 'web'
        ]);

        if (!$OrderMaster->save()) {
            Session::flash('success', 'Unable to create order.');
            return Redirect::to('caravan-offline-order');
        }

        $OrderMasterId = $OrderMaster->id;
        $OrderMasterNew = OrderMaster::find($OrderMasterId);

        if ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
            require_once public_path('QrCode/generateQrCode.php');
            $QrCodeData = [
                'invoiceId' => $OrderMasterNew->invoice_id,
                'orderId' => $OrderMasterNew->order_id,
                'txnId' => $OrderMasterNew->transaction_id,
                'serviceType' => $OrderMasterNew->service_type
            ];

            $QrCode = generateQrCode(json_encode($QrCodeData));
            $OrderMasterNew->qr_code = $QrCode;
            $OrderMasterNew->qr_verified = 0;
        }

        require_once public_path('paytm_lib/config_paytm.php');

        if ($request->payment_gateway == 'hdfc') {

            $PropertyAccount = PropertyAccount::where([
                'service_type' => 'caravan',
                'service_id' => $OrderMasterNew->service_name_id
            ])->first();

            if (!empty($PropertyAccount) && PAYTM_ENVIRONMENT == 'PROD') {
                $HDFC_KEY = $PropertyAccount->hdfc_key;
                $HDFC_SALT = $PropertyAccount->hdfc_salt;
                $MERCHANT_ID = $PropertyAccount->hdfc_mid;
            }
            if (empty($HDFC_KEY) || empty($HDFC_SALT) || empty($MERCHANT_ID)) {
                OrderMaster::find($OrderMasterId)->delete();
                Session::flash('success','HDFC payment configuration is missing. Please contact the administrator.');
                return Redirect::to('caravan-offline-order');
            }

            $txn_id = 'TXN' . time() . rand(10000, 99999999);
            $command = 'create_invoice';
            $var1_arr = [
                'amount' => $OrderMasterNew->total_order_price,
                'txnid' => $txn_id,
                'productinfo' => parent::cleanString($OrderMasterNew->service_name),
                'firstname' => $OrderMasterNew->customer_name,
                'email' => $OrderMasterNew->customer_email,
                'phone' => $OrderMasterNew->customer_phone,
                'address1' => parent::cleanString($OrderMasterNew->customer_address1),
                'city' => parent::cleanString($OrderMasterNew->customer_city),
                'state' => parent::cleanString($OrderMasterNew->customer_state),
                'country' => parent::cleanString($OrderMasterNew->customer_country),
                'zipcode' => parent::cleanString($OrderMasterNew->customer_zipcode),
                'validation_period' => 30,
                'send_email_now' => '1',
                'send_sms' => '1',
                'time_unit' => 'M'
            ];

            $var1 = json_encode($var1_arr);
            $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
            $hash = strtolower(hash('sha512', $hash_str));

            $r = [
                'key' => $HDFC_KEY,
                'hash' => $hash,
                'command' => $command,
                'var1' => $var1
            ];

            $qs = http_build_query($r);
            $wsUrl = VERIFY_URL;

            $c = curl_init();

            curl_setopt($c, CURLOPT_URL, $wsUrl);
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, $qs);
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($c, CURLOPT_TIMEOUT, 60);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);

            $o = curl_exec($c);

            $curl_error = curl_error($c);
            $curl_errno = curl_errno($c);
            $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);

            curl_close($c);
            $verify_response = json_decode($o, true);

            if (is_array($verify_response) && isset($verify_response['Status']) && $verify_response['Status'] == 'Success' && !empty($verify_response['URL'])) {

                $link_expiry = date('Y-m-d H:i:s',strtotime('+30 minutes'));
                $PaymentHistory = new PaymentHistory([
                    'order_id' => $OrderMasterNew->order_id,
                    'vendor_id' => $OrderMasterNew->vendor_id,
                    'user_id' => $OrderMasterNew->customer_id,
                    'payment_method' => 'hdfc',
                    'transaction_id' => $txn_id,
                    'amount' => $OrderMasterNew->total_order_price,
                    'product_info' => parent::cleanString($OrderMasterNew->service_name),
                    'first_name' => $OrderMasterNew->customer_name,
                    'email' => $OrderMasterNew->customer_email,
                    'phone' => $OrderMasterNew->customer_phone,
                    'address1' => $OrderMasterNew->customer_address1,
                    'city' => $OrderMasterNew->customer_city,
                    'state' => $OrderMasterNew->customer_state,
                    'country' => $OrderMasterNew->customer_country,
                    'zipcode' => $OrderMasterNew->customer_zipcode,
                    'udf1' => $OrderMasterNew->invoice_id,
                    'udf2' => $OrderMasterNew->vendor_id,
                    'udf3' => $OrderMasterNew->vendor_name,
                    'udf4' => $OrderMasterNew->service_type,
                    'status' => 'pending'
                ]);

                if (!$PaymentHistory->save()) {
                    OrderMaster::find($OrderMasterId)->delete();
                    Session::flash('success','Unable to save payment details. Please try again.');
                    return Redirect::to('caravan-offline-order');
                }
                $OrderMasterNew->offline_short_url = $verify_response['URL'];
                $OrderMasterNew->offline_long_url = $verify_response['URL'];
                $OrderMasterNew->offline_link_expiry = $link_expiry;
                $OrderMasterNew->payment_id = $PaymentHistory->id;
                $OrderMasterNew->transaction_id = $txn_id;
                $OrderMasterNew->hdfc_key = $HDFC_KEY;
                $OrderMasterNew->hdfc_salt = $HDFC_SALT;
                $OrderMasterNew->paytm_mid = $MERCHANT_ID;
                $OrderMasterNew->save();
            } else {
                OrderMaster::find($OrderMasterId)->delete();
                Session::flash('success','Sorry!, Could not place order due to a technical issue in generating payment link. Please try again.');

                return Redirect::to('caravan-offline-order');
            }
        }

        $OrderDetailsData = [];
        $count = 0;

        foreach ($service_details as $index => $val) {
            $routeStart = new DateTime($val['date']['startDate']);
            $routeEnd = new DateTime($val['date']['endDate']);
            $durationSeconds = $routeEnd->getTimestamp() - $routeStart->getTimestamp();
            $billableDays = max(1, (int) ceil($durationSeconds / 86400));
            $routePrice = $dailyPrice * $billableDays * $serviceQuantity;

            $route_coupon = 0;

            if ($total_service_price > 0 && $coupon_amt > 0) {
                $route_coupon = round($coupon_amt * ($routePrice / $total_service_price),2);
            }
            $routeFinalPrice = max(0, $routePrice - $route_coupon);
            $OrderDetailsData[$count] = [
                'order_id' => $booking_id,
                'order_master_id' => $OrderMasterId,
                'service_type' => 'caravan',
                'service_name' => $MasterCar->title,
                'service_name_id' => $MasterCar->id,
                'service_city' => $MasterCar->city,
                'start_date' => $routeStart->format('Y-m-d'),
                'end_date' => $routeEnd->format('Y-m-d'),
                'start_time' => $routeStart->format('H:i:s'),
                'end_time' => $routeEnd->format('H:i:s'),
                'pickup_address' => $val['pickup_point'],
                'pickup_city' => $val['pickup_city'],
                'drop_address' => $val['drop_point'],
                'drop_city' => $val['drop_city'],
                'service_item_quantity' => $serviceQuantity,
                'service_item_price' => ceil($routePrice),
                'unit_total_price' => ceil($routePrice),
                'coupon_amount' => ceil($route_coupon),
                'tax_percentage' => 0,
                'tax_amount' => 0,
                'total_room_price' => ceil($routeFinalPrice),
                'status' => $status
            ];

            $count++;
        }

        OrderDetail::insert($OrderDetailsData);

        foreach ($inventoryDates as $checkDate) {

            $MasterInventory = CaravanMasterInventory::where([
                'date' => $checkDate,
                'caravan_id' => $MasterCar->id
            ])->first();

            if (empty($MasterInventory) || $MasterInventory->total_available < 1) {
                Session::flash('success','Unable to create order. Inventory is not available for ' .
                    date('d-m-Y', strtotime($checkDate)) . '.');
                return Redirect::to('caravan-offline-order');
            }

            $MasterInventory->total_available -= 1;
            $MasterInventory->total_booked += 1;

            if ($status == 'pending') {
                $MasterInventory->total_offline_pending += 1;
            } else {
                $MasterInventory->total_offline_completed += 1;
            }
            $MasterInventory->save();
        }


        if (!empty($request->coupon_code)) {
            $Coupon = Coupon::where('coupon_code', $request->coupon_code)->first();
            if (!empty($Coupon)) {
                $Coupon->already_used = $Coupon->already_used + 1;
                $Coupon->save();
            }
        }

        $Vendor = User::find($OrderMasterNew->vendor_id);
        $RentalInvoice = EmailTemplate::where('ref_code', 'rentalInvoice')->first();

        if (!empty($RentalInvoice)) {
            $Subject = $RentalInvoice->subject . ' - ' . $OrderMasterNew->service_name . ' - Booking ID - ' . $OrderMasterNew->invoice_id;

            $check_date = date('d M Y', strtotime($OrderMasterNew->start_date)) . ' ' . date('h:i a', strtotime($OrderMasterNew->start_time)) . ' - <br>' . date('d M Y', strtotime($OrderMasterNew->end_date)) . ' ' . date('h:i a', strtotime($OrderMasterNew->end_time));

            $vendorGSTNo = 'N/A';
            $vendorRegdCompany = 'N/A';
            $customerGSTNo = '';
            $customerGSTCompany = '';
            $OrderDetails = OrderDetail::where('order_master_id',$OrderMasterNew->id)->get();
            $routes = '';
            $route_confirm = '';
            $count = 1;

            foreach ($OrderDetails as $route) {
                $routes .= '<tr>
                    <td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' .
                        $OrderMasterNew->invoice_id .
                    '</td>
                    <td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' .
                        $OrderMasterNew->service_name .
                    '</td>
                    <td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' .
                        date('d M Y h:i a', strtotime($route->start_date . ' ' . $route->start_time)) .
                        ' - <br>' .
                        date('d M Y h:i a', strtotime($route->end_date . ' ' . $route->end_time)) .
                    '</td>
                    <td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' .
                        (isset($service_details[$count - 1]['day_km']) ? $service_details[$count - 1]['day_km'] : 0) .
                        ' KM
                    </td>
                    <td align="right" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' .
                        number_format($route->unit_total_price, 2) .
                    '</td>
                </tr>';

                $route_confirm .= '<tr>
                    <td width="10%" rowspan="3">' . $count . '</td>
                    <td><strong>Pick up Location</strong>: ' .
                        $route->pickup_address . ', ' . $route->pickup_city .
                    '</td>
                    <td><strong>Drop Location</strong>: ' .
                        $route->drop_address . ', ' . $route->drop_city .
                    '</td>
                </tr>
                <tr>
                    <td><strong>Start Date</strong>: ' .
                        date('d M Y h:i a', strtotime($route->start_date . ' ' . $route->start_time)) .
                    '</td>
                    <td><strong>End Date</strong>: ' .
                        date('d M Y h:i a', strtotime($route->end_date . ' ' . $route->end_time)) .
                    '</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Distance</strong>: ' .
                        (isset($service_details[$count - 1]['day_km']) ? $service_details[$count - 1]['day_km'] : 0) .
                        ' KM
                    </td>
                </tr>
                <tr><td colspan="3">&nbsp;</td></tr>';

                $count++;
            }

            $txn_id = !empty($OrderMasterNew->transaction_id) ? $OrderMasterNew->transaction_id : 'N/A';
            $discount = !empty($OrderMasterNew->coupon_amount) ? $OrderMasterNew->coupon_amount : '0.00';
            $tspinword = parent::AmountInWords($OrderMasterNew->total_order_price);
            $Message = str_replace(
                [
                    '~otdcLogo~',
                    '~username~',
                    '~usermobile~',
                    '~usermail~',
                    '~useraddress~',
                    '~usergstno~',
                    '~usergstcompany~',
                    '~vendorgst~',
                    '~vendorgstcompany~',
                    '~vendorLogo~',
                    '~orderdate~',
                    '~orderdetails~',
                    '~totalserviceprice~',
                    '~couponname~',
                    '~couponamount~',
                    '~subtotal~',
                    '~gst~',
                    '~ordertotal~',
                    '~paymentmethod~',
                    '~txnid~',
                    '~tspinword~',
                    '~guidecharge~',
                    '~payuid~'
                ],
                [
                    $this->site,
                    $OrderMasterNew->customer_name,
                    $OrderMasterNew->customer_phone,
                    $OrderMasterNew->customer_email,
                    $OrderMasterNew->customer_address1,
                    $customerGSTNo,
                    $customerGSTCompany,
                    $vendorGSTNo,
                    $vendorRegdCompany,
                    $this->site . $Vendor->photo,
                    date('d M Y h:i a', strtotime($OrderMasterNew->created_at)),
                    $routes,
                    $OrderMasterNew->total_service_price,
                    $OrderMasterNew->coupon_name,
                    $discount,
                    0,
                    0,
                    $OrderMasterNew->total_order_price,
                    strtoupper($OrderMasterNew->payment_gateway),
                    $txn_id,
                    $tspinword,
                    0,
                    'N/A'
                ],
                $RentalInvoice->source
            );

            if ($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                $ConfirmTemplate = EmailTemplate::where('ref_code','rentalConfirmMail')->first();

                if (!empty($ConfirmTemplate)) {
                    $SubjConfirm = $ConfirmTemplate->subject . ' - ' . $OrderMasterNew->service_name . ' - Booking ID - ' . $OrderMasterNew->invoice_id;
                    $msg = str_replace([
                            '~vendorLogo~',
                            '~username~',
                            '~servicename~',
                            '~orderdetail~',
                            '~ordertotal~',
                            '~txnid~',
                            '~paymentmethod~',
                            '~termsconditions~',
                            '~guideservice~',
                            '~invoiceid~'
                        ],
                        [
                            $this->site . $Vendor->photo,
                            $OrderMasterNew->customer_name,
                            $OrderMasterNew->service_name,
                            $route_confirm,
                            $OrderMasterNew->total_order_price,
                            $OrderMasterNew->transaction_id,
                            strtoupper($OrderMasterNew->payment_gateway),
                            $CarDetails->terms_conditions,
                            '',
                            $OrderMasterNew->invoice_id
                        ],
                        $ConfirmTemplate->source
                    );

                    $OrderMasterNew->confimation_voucher = $msg;

                    $msg .= '<div style="margin-top:30px;text-align:center;">
                        <p style="font-family: Segoe UI;color:#333;">
                            Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a>
                            for any further questions or clarifications
                        </p>
                        <p style="font-family: Segoe UI;color:#333;">
                            <b>bookodisha.com support team</b>
                        </p>
                        <p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important;">
                            Please do not reply to this message. This email address is automated for delivering outbound messages.
                            <br>
                            Please check the web site for more information
                            <a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a>
                            <br>
                            Copyright &copy; 2022 Odisha Tourism. All rights reserved.
                        </p>
                    </div>';

                    Mail::to($OrderMasterNew->customer_email)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                }

                $sms_txt = '';
                $sms_txt_admin = '';
                $manager_contact = '';

                $SmsTemplate = SmsTemplate::where('ref_code','BookingConfirmUser')->first();

                if (!empty($SmsTemplate)) {

                    $var3 = $var5 = $var7 = '';
                    $var1 = $OrderMasterNew->customer_name;
                    $var2 = $OrderMasterNew->invoice_id;
                    $var4 = $OrderMasterNew->service_name;
                    $var4 = (strlen($var4) > 30) ? substr(utf8_encode($var4), 0, 27) . '...' : $var4;
                    $var6 = "\n" . $OrderMasterNew->vendor_name;
                    $var8 = "\n\n";
                    $var5 = date('d M Y', strtotime($OrderMasterNew->start_date)) . ' to ' . date('d M Y', strtotime($OrderMasterNew->end_date));
                    $var7 = $CarDetails->contact_number;
                    $manager_contact = $CarDetails->contact_number;
                    $sms_txt = str_replace(
                        [
                            '~var1~',
                            '~var2~',
                            '~var3~',
                            '~var4~',
                            '~var5~',
                            '~var6~',
                            '~var7~',
                            '~var8~'
                        ],
                        [
                            $var1,
                            $var2,
                            $var3,
                            $var4,
                            $var5,
                            $var6,
                            $var7,
                            $var8
                        ],
                        $SmsTemplate->source
                    );

                    parent::sendSms($OrderMasterNew->customer_phone,$sms_txt,$SmsTemplate->templete_id);
                }

                $SmsTemplateAdmin = SmsTemplate::where('ref_code','BookingConfirmPackageRental')->first();
                if (!empty($SmsTemplateAdmin)) {
                    $var1 = $OrderMasterNew->service_name;
                    $var1 = (strlen($var1) > 30) ? substr(utf8_encode($var1), 0, 27) . '...' : $var1;

                    $var2 = $OrderMasterNew->customer_name;
                    $var3 = $OrderMasterNew->customer_phone;
                    $var4 = date('d M Y', strtotime($OrderMasterNew->start_date));
                    $var5 = date('d M Y', strtotime($OrderMasterNew->end_date));
                    $var6 = $OrderMasterNew->invoice_id;

                    $sms_txt_admin = str_replace(
                        [
                            '~var1~',
                            '~var2~',
                            '~var3~',
                            '~var4~',
                            '~var5~',
                            '~var6~'
                        ],
                        [
                            $var1,
                            $var2,
                            $var3,
                            $var4,
                            $var5,
                            $var6
                        ],
                        $SmsTemplateAdmin->source
                    );

                    $sms_recipient = [];

                    if (!empty($manager_contact)) {
                        $sms_recipient[] = $manager_contact;
                    }

                    if (!empty($CarDetails->additional_phone)) {
                        $sms_recipient = array_merge($sms_recipient,explode(',', $CarDetails->additional_phone));
                    }

                    if (!empty($sms_recipient)) {
                        $to_sms = implode(',', array_slice($sms_recipient, 0, 3));
                        parent::sendSms($to_sms,$sms_txt_admin,$SmsTemplateAdmin->templete_id);
                    }
                }
            } else {
                $Message = str_replace(
                    'This is an electronically generated invoice, hence does not require a signature.',
                    'This is an estimated invoice for payment. You will get a confirmation voucher and invoice after payment.',
                    $Message
                );

                $extraMsg = 'Dear ' . $OrderMasterNew->customer_name .
                    ',<br><br> Please <a href="' . $OrderMasterNew->offline_short_url .
                    '"><strong>click here</strong></a> to complete the payment for booking ' .
                    $OrderMasterNew->invoice_id .
                    ' of amount Rs. ' . $OrderMasterNew->total_order_price;

                $extraMsg .= '<br><br>If the above link is not working, please go through the following url for payment. <br><br>' .
                    $OrderMasterNew->offline_long_url;

                $extraMsg .= '<br><br><strong>Note: Please complete the payment as soon as possible.<br>The provisional invoice for your booking is as follows.</strong><br><br>';

                $Message = $extraMsg . $Message;

                $Subject = 'Payment link for - ' . $OrderMasterNew->service_name . ' - Booking ID - ' .$OrderMasterNew->invoice_id;
            }

            $service_mail = $CarDetails->contact_email;

            if (!empty($CarDetails->additional_email)) {
                $service_mail = !empty($service_mail) ? $service_mail . ',' . $CarDetails->additional_email : $CarDetails->additional_email;
            }

            $OrderMasterNew->invoice = $Message;
            $OrderMasterNew->save();

            $Message .= '<div style="margin-top:30px;text-align:center;">
                <p style="font-family: Segoe UI;color:#333;">
                    Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a>
                    for any further questions or clarifications
                </p>
                <p style="font-family: Segoe UI;color:#333;">
                    <b>bookodisha.com support team</b>
                </p>
            </div>';

            $receipent = [$Vendor->email];

            if (!empty($service_mail)) {
                $receipent = array_merge(
                    $receipent,
                    explode(',', $service_mail)
                );
            }

            if (Auth::user()->user_role == 'agent_staff') {
                Mail::to($receipent)->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
            } else {
                Mail::to($OrderMasterNew->customer_email)
                    ->bcc($receipent)
                    ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
            }
        }

        Session::flash('success', 'Order created successfully.');
        return Redirect::to('caravan-offline-order');
    }
}