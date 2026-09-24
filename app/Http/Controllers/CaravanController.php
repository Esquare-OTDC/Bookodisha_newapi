<?php

namespace App\Http\Controllers;

use App\AttributeValue;
use App\BlockedCaravan;
use App\BlockedVehicle;
use App\CaravanAvailability;
use App\CaravanMasterInventory;
use App\City;
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
        //$sTable = "master_caravans";
        $sTable = "master_caravans";
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
        // $CaravanDetails->feature_image = $this->site . $CaravanDetails->feature_image;
        // $CaravanDetails->banner_image  = $this->site . $CaravanDetails->banner_image;
        $CaravanDetails->gallery = json_decode($CaravanDetails->gallery, true);

        $gallery = [];
        $count = 1;
        if (!empty($CaravanDetails->gallery)) {
            foreach ($CaravanDetails->gallery as $value) {
                $gallery[] = [
                    'id' => $count,
                    // 'src' => $this->site . $value
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

        return view(
            'caravan.edit-caravan',
            compact(
                'Vendors',
                'CaravanDetails',
                'CaravanAttributes',
                'CityDetail',
                'gallery'
            )
        );
    }

    public function caravanEditRequest(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'title' => 'required|string|min:3|max:100',
        ]);

        // Dynamic validation based on selected type
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

            // $Property = $request->property;
            // $property_slug_array = array();
            // if (!empty($Property)) {
            //     foreach ($Property as $key1 => $attribute) {
            //         foreach ($attribute as $key2 => $terms) {
            //             $temp = explode('~', $terms);
            //             $Property[$key1][$key2] = array('name' => $temp[1], 'icon' => $AttributeValues[$temp[0]]);
            //             $property_slug_array[] = $temp[1];
            //         }
            //     }
            // }
            // $Caravan->property_slug = implode("~",$property_slug_array);
            // $Caravan->property = json_encode($Property);


            $property_slug_data = AttributeValue::where('id', $request->car_book_type)
                ->get(['name', 'icon', 'slug']); // fetch once

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

                Session::flash('success', 'Caravan details updated successful.');
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
            $CarAttributes = $AttributeValue; //array_values($AttributeValue);
        }

        $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();

        return view('caravan.add-caravan', compact('Vendors', 'CarAttributes', 'CityDetail'));
    }

    public function caravanAddRequest(Request $request)
    {
        //dd($request->all());
        $priceField = 'price_per_day' . $request->car_book_type;
        $quantityField = 'quantity' . $request->car_book_type;

        // dd($request->all());

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
            // $property_slug_data = AttributeValue::where('id', $request->car_book_type)
            //     ->get(['name', 'icon', 'slug'])
            //     ->map(function ($item) {
            //         return [
            //             'name' => $item->name,
            //             'icon' => $item->icon,
            //             'slug' => $item->slug,
            //         ];
            //     })
            //     ->toArray();

            $property_slug_data = AttributeValue::where('id', $request->car_book_type)
                ->get(['name', 'icon', 'slug']); // fetch once
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
            //$AttributeValues = AttributeValue::pluck('icon', 'id');
            // $Property = $request->property;
            // $property_slug_array = array();
            // foreach ($Property as $key1 => $attribute) {
            //     foreach ($attribute as $key2 => $terms) {
            //         $temp = explode('~', $terms);
            //         $Property[$key1][$key2] = array('name' => $temp[1], 'icon' => $AttributeValues[$temp[0]]);
            //         $property_slug_array[] = $temp[1];
            //     }
            // }
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) {
                return !empty(trim($n));
            });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) {
                return !empty(trim($n));
            });
            // dd($property_slug);

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

                Session::flash('success', 'Caravan added successful.');
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
                Session::flash('success', 'Caravan attribute added successful.');
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
            //            'status' => 'required|string',
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
                //                'status' => $request->status
            ]);
            if ($AttributeTerm->save()) {
                Session::flash('success', 'Attribute term added successful.');
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
        //print_r($MasterCaravan);exit;
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
                $responce['message'] = 'vehicles publish successful.';
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
                //$ServiceSttribute->status = $request->attrStatus;
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
                        //                        'attrStatus' => 'required|string',
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
                        //                        $AttributeTerm->status = $request->attrStatus;

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
                    $responce['message'] = 'Delete successful.';
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
                    $responce['message'] = 'Delete successful.';
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
            //dd($MasterInventory, $check_date);
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
            $Mastercar = MasterCaravan::find($request->caravanId);
            if (!empty($Mastercar)) {
                $responce['status'] = 1;
                $responce['data'] = $Mastercar;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Car details not found';
            }
        } elseif ($request->request_type == 'calculate_price') {
            $CarDetails = MasterCaravan::find($request->caravanId);
            if (!empty($CarDetails)) {
                $GSTData = GstDetail::pluck('value', 'name')->toArray();
                $taxPrice = 0;
                $GstShowData = $tax_array = array();
                $total_gst = $total_coupon_amt = $SubTotal = 0;
                if ($CarDetails->gst_applicable == 1) {
                    $GstTable = GstTable::where(['vendor_id' => $CarDetails->vendor_id, 'service_type' => 'rental'])
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
                $bookFlag = 1;
                $quantity_array = $price_breakup = array();
                $service_details = json_decode($request->day_breakup_details, 1);
                $start_date = date("Y-m-d", strtotime($service_details[0]['date']['startDate']));
                $end_data = end($service_details);
                $end_date = date("Y-m-d", strtotime($end_data['date']['endDate']));
                $BlockedVehicle = BlockedCaravan::where('caravan_id', $CarDetails->id)
                    ->whereBetween('block_date', [$start_date, $end_date])
                    ->get()->toArray();
                if (!empty($BlockedVehicle)) {
                    $responce['status'] = 0;
                    $responce['message'] = 'Sorry! The vehicle is not available for selected dates.';
                    echo json_encode($responce);
                    exit;
                }
                $sub_total_price = 0;
                foreach ($service_details as $val) {
                    $totalPrice = $totalPriceKm = $totalPriceHr = $totalHour = $totalHaltPrice = $detention_charge = $detention_hour = $extrakm_price = $extrakm = 0;
                    $difference = strtotime(date("Y-m-d", strtotime($val['date']['endDate']))) - strtotime(date("Y-m-d", strtotime($val['date']['startDate'])));
                    $days = floor($difference / (60 * 60 * 24));

                    $checkinDate = new DateTime($val['date']['startDate']);
                    $checkoutDate = new DateTime($val['date']['endDate']);

                    $interval = $checkinDate->diff($checkoutDate);
                    $totalHour = $interval->format('%h') + ($interval->format('%d') * 24);

                    $cal_day = ($days == 0) ? 1 : $days;
                    for ($i = 0; $i < $cal_day; $i++) {
                        $checkDate = date("Y-m-d", strtotime($val['date']['startDate'] . ' + ' . $i . ' days'));
                        $MasterInventory = CaravanMasterInventory::where(["date" => $checkDate, "caravan_id" => $CarDetails->id])->first();
                        if (!empty($MasterInventory) && $MasterInventory->total_available < 1) {
                            $bookFlag = 0;
                            array_push($quantity_array, date("d-m-Y", strtotime($checkDate)));
                        }
                    }
                    if ($bookFlag == 0) {
                        $responce['status'] = 0;
                        $responce['message'] = 'Sorry! The vehicle is not available on date ' . implode(", ", $quantity_array);
                        echo json_encode($responce);
                        exit;

                    }

                    $cover_day = $days + 1;
                    $price_per_day = $CarDetails->price_per_day;
                    $totalPrice = (int) $cover_day * $price_per_day;

                    $price_breakup[] = array(
                        "price_per_days" => $price_per_day,
                        "total_days" => $cover_day,
                        "booking_date" => array(
                            "startDate" => date("d-m-Y h:i a", strtotime($val['date']['startDate'])),
                            "endDate" => date("d-m-Y h:i a", strtotime($val['date']['endDate']))
                        ),
                        "totalPrice" => $totalPrice
                    );


                    $sub_total_price += $totalPrice;
                    if ($CarDetails->gst_applicable == 1) {
                        $filter_res = array_filter($GstMin, function ($n) use ($totalPrice) {
                            return $n <= $totalPrice;
                        });
                        if (!empty($filter_res)) {
                            $tempr = $GstDetails[end($filter_res)];
                            if (!empty($tax_array)) {
                                foreach ($tempr as $g_name => $g_val) {
                                    //                                    $taxPercent += $g_val;
                                    $tax_amt = ceil($totalPrice * ($g_val / 100));
                                    $tax_array[$g_name] += $tax_amt;
                                }
                            } else {
                                foreach ($tempr as $g_name => $g_val) {
                                    //                                    $taxPercent += $g_val;
                                    $tax_amt = ceil($totalPrice * ($g_val / 100));
                                    $tax_array[$g_name] = $tax_amt;
                                }
                            }
                        }
                    }
                }
                $GstShowData = array();
                foreach ($tax_array as $key => $val) {
                    $GstShowData[] = [
                        'gst_name' => $key,
                        'gst_percentage' => $tempr[$key],
                        'gst_value' => round($val, 2)
                    ];
                    $total_gst += $val;
                }
                $taxPrice = round($total_gst, 2);
                $total_price = round($sub_total_price + $total_gst + $CarDetails->service_fee, 2);

                $responce['status'] = 1;
                $responce['gst_data'] = $GstShowData;
                $responce['grossPrice'] = $sub_total_price;
                $responce['subTotalPrice'] = $sub_total_price;
                $responce['totalOrderPrice'] = $total_price;
                $responce['price_breakup'] = $price_breakup;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Caravan details not found';
            }
        } elseif ($request->request_type == 'verify_coupon') {
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
            if (!(parent::checkWritePrivilege(69))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $MasterInventory = CaravanMasterInventory::find($request->inventoryId);
                if (!empty($MasterInventory)) {
                    $changeQty = $request->releaseQty;
                    $initial_qty = $total_available = 0;
                    if ($changeQty > $MasterInventory->total_blocked) {
                        $responce['status'] = 0;
                        $responce['message'] = 'You can release maximum ' . $MasterInventory->total_blocked . ' number of vehicles.';
                    } elseif ($changeQty < 1) {
                        $responce['status'] = 0;
                        $responce['message'] = 'You can release minimum 1 vehicle.';
                    } else {
                        $MasterInventory->total_available = $MasterInventory->total_available + $changeQty;
                        $MasterInventory->total_blocked = $MasterInventory->total_blocked - $changeQty;
                        $MasterInventory->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Inventory updated successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid data input.';
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

                /*
                |--------------------------------------------------------------------------
                | Validate Order ID
                |--------------------------------------------------------------------------
                */

                if (empty($request->orderID)) {

                    return response()->json([
                        'status' => 0,
                        'message' => 'Order ID is required.'
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Get Order Master
                |--------------------------------------------------------------------------
                */

                $OrderMaster = OrderMaster::where(
                    'order_id',
                    $request->orderID
                )
                    ->where(
                        'service_type',
                        'caravan'
                    )
                    ->first();


                if (empty($OrderMaster)) {

                    return response()->json([
                        'status' => 0,
                        'message' => 'Caravan order not found.'
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Get Caravan Booking Details
                |--------------------------------------------------------------------------
                */

                $bookings = DB::table('caravan_bookings as cb')

                    ->join(
                        'master_caravans as mc',
                        'cb.caravan_id',
                        '=',
                        'mc.id'
                    )

                    ->join(
                        'order_masters as om',
                        'cb.booking_id',
                        '=',
                        'om.order_id'
                    )

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

                    ->where(
                        'om.order_id',
                        $request->orderID
                    )

                    ->where(
                        'om.service_type',
                        'caravan'
                    )

                    ->orderBy(
                        'cb.start_date',
                        'asc'
                    )

                    ->get();


                if ($bookings->isEmpty()) {

                    return response()->json([
                        'status' => 0,
                        'message' => 'Caravan booking details not found.'
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Get Caravan Invoice Template
                |--------------------------------------------------------------------------
                */

                $CaravanInvoice = DB::table(
                    'email_templates'
                )
                    ->where(
                        'ref_code',
                        'caravanInvoice'
                    )
                    ->first();


                if (
                    empty($CaravanInvoice) ||
                    empty($CaravanInvoice->source)
                ) {

                    return response()->json([
                        'status' => 0,
                        'message' => 'Caravan invoice template not found.'
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Customer Details
                |--------------------------------------------------------------------------
                */

                $customerName =
                    $OrderMaster->customer_name
                    ?? '';


                $customerPhone =
                    $OrderMaster->customer_phone
                    ?? $OrderMaster->phone
                    ?? $OrderMaster->mobile
                    ?? '';


                $customerEmail =
                    $OrderMaster->customer_email
                    ?? $OrderMaster->email
                    ?? '';


                /*
                |--------------------------------------------------------------------------
                | Payment Details
                |--------------------------------------------------------------------------
                */

                $paymentMethod =
                    $OrderMaster->payment_method
                    ?? $OrderMaster->payment_mode
                    ?? 'PAYTM';


                $transactionId =
                    $OrderMaster->transaction_id
                    ?? $OrderMaster->txnid
                    ?? '';


                $paymentReference =
                    $OrderMaster->payuid
                    ?? '';


                /*
                |--------------------------------------------------------------------------
                | Grand Total
                |--------------------------------------------------------------------------
                */

                $grandTotal = (float) (
                    $OrderMaster->total_order_price
                    ?? 0
                );


                /*
                |--------------------------------------------------------------------------
                | Main Logo
                |--------------------------------------------------------------------------
                */

                $mainLogo = '';


                $mainLogoPath = public_path(
                    'images/frontend/otdc_white.jpg'
                );


                if (file_exists($mainLogoPath)) {

                    $mainLogoType = pathinfo(
                        $mainLogoPath,
                        PATHINFO_EXTENSION
                    );


                    $mainLogo =
                        'data:image/' .
                        $mainLogoType .
                        ';base64,' .
                        base64_encode(
                            file_get_contents(
                                $mainLogoPath
                            )
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Vendor Logo
                |--------------------------------------------------------------------------
                */

                $companyLogo = '';


                $companyLogoPath = public_path(
                    'images/profile/otdc1_1_1746204931.png'
                );


                if (file_exists($companyLogoPath)) {

                    $companyLogoType = pathinfo(
                        $companyLogoPath,
                        PATHINFO_EXTENSION
                    );


                    $companyLogo =
                        'data:image/' .
                        $companyLogoType .
                        ';base64,' .
                        base64_encode(
                            file_get_contents(
                                $companyLogoPath
                            )
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Build Caravan Booking Rows
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                | Exactly 5 TD columns matching template.
                |
                |--------------------------------------------------------------------------
                */

                $caravanPricing = '';


                foreach ($bookings as $booking) {


                    /*
                    |--------------------------------------------------------------------------
                    | Booking ID
                    |--------------------------------------------------------------------------
                    */

                    $bookingId = htmlspecialchars(
                        $booking->invoice_id
                        ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Mode of Transport
                    |--------------------------------------------------------------------------
                    */

                    $caravanName = htmlspecialchars(
                        $booking->caravan_name
                        ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Start Date
                    |--------------------------------------------------------------------------
                    */

                    $startDate = '';


                    if (!empty($booking->start_date)) {

                        $startDate = date(
                            'd M Y',
                            strtotime(
                                $booking->start_date
                            )
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | End Date
                    |--------------------------------------------------------------------------
                    */

                    $endDate = '';


                    if (!empty($booking->end_date)) {

                        $endDate = date(
                            'd M Y',
                            strtotime(
                                $booking->end_date
                            )
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Date Range
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty($startDate) &&
                        !empty($endDate)
                    ) {

                        $dateRange =
                            $startDate .
                            ' - ' .
                            $endDate;

                    } elseif (!empty($startDate)) {

                        $dateRange =
                            $startDate;

                    } else {

                        $dateRange =
                            '-';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Number of Days
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty($booking->no_of_days)
                    ) {

                        $days =
                            $booking->no_of_days;

                    } elseif (
                        !empty($booking->start_date) &&
                        !empty($booking->end_date)
                    ) {

                        $start =
                            Carbon::parse(
                                $booking->start_date
                            );


                        $end =
                            Carbon::parse(
                                $booking->end_date
                            );


                        $days =
                            $start->diffInDays(
                                $end
                            ) + 1;

                    } else {

                        $days = 1;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Amount
                    |--------------------------------------------------------------------------
                    */

                    $amount = (float) (
                        $booking->booking_amount
                        ?? 0
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Build Row
                    |--------------------------------------------------------------------------
                    */

                    $caravanPricing .= '

                <tr>

                    <td
                        align="center"
                        valign="middle"
                        style="
                            color:#000;
                            border-right:1px solid #000;
                            border-bottom:1px solid #000;
                            padding:5px;
                        "
                    >
                        ' . $bookingId . '
                    </td>


                    <td
                        align="center"
                        valign="middle"
                        style="
                            color:#000;
                            border-right:1px solid #000;
                            border-bottom:1px solid #000;
                            padding:5px;
                        "
                    >
                        ' . $caravanName . '
                    </td>


                    <td
                        align="center"
                        valign="middle"
                        style="
                            color:#000;
                            border-right:1px solid #000;
                            border-bottom:1px solid #000;
                            padding:5px;
                        "
                    >
                        ' .
                        htmlspecialchars(
                            $dateRange,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        . '
                    </td>


                    <td
                        align="center"
                        valign="middle"
                        style="
                            color:#000;
                            border-right:1px solid #000;
                            border-bottom:1px solid #000;
                            padding:5px;
                        "
                    >
                        ' . $days . '
                    </td>


                    <td
                        align="right"
                        valign="middle"
                        style="
                            color:#000;
                            border-bottom:1px solid #000;
                            padding:5px;
                        "
                    >
                        ' .
                        number_format(
                            $amount,
                            2
                        )
                        . '
                    </td>

                </tr>
            ';
                }


                /*
                |--------------------------------------------------------------------------
                | Order Date
                |--------------------------------------------------------------------------
                */

                $orderDateValue =
                    $OrderMaster->created_at
                    ?? $OrderMaster->created_on
                    ?? now();


                $orderDate = date(
                    'd M Y h:i a',
                    strtotime(
                        $orderDateValue
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | Replace Template Placeholders
                |--------------------------------------------------------------------------
                */

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

                        number_format(
                            $grandTotal,
                            2
                        ),

                        $paymentMethod,

                        $transactionId,

                        $paymentReference
                    ),


                    $CaravanInvoice->source
                );


                /*
                |--------------------------------------------------------------------------
                | Final HTML
                |--------------------------------------------------------------------------
                */

                $finalHtml = html_entity_decode(
                    $Message,
                    ENT_QUOTES,
                    'UTF-8'
                );


                /*
                |--------------------------------------------------------------------------
                | Create Documents Directory
                |--------------------------------------------------------------------------
                */

                $directory = public_path(
                    'documents'
                );


                if (!is_dir($directory)) {

                    mkdir(
                        $directory,
                        0755,
                        true
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | PDF File Name
                |--------------------------------------------------------------------------
                */

                $invoiceNumber =
                    !empty(
                    $OrderMaster->invoice_id
                )
                    ? $OrderMaster->invoice_id
                    : $OrderMaster->order_id;


                $safeInvoiceNumber = preg_replace(
                    '/[^A-Za-z0-9_-]/',
                    '_',
                    $invoiceNumber
                );


                $file =
                    'documents/Caravan_Invoice_' .
                    $safeInvoiceNumber .
                    '_' .
                    time() .
                    '.pdf';


                $pdfname = public_path(
                    $file
                );


                /*
                |--------------------------------------------------------------------------
                | Generate PDF
                |--------------------------------------------------------------------------
                */

                /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

                PDF::loadHTML(
                    $finalHtml
                )->save(
                        $pdfname
                    );


                /*
                |--------------------------------------------------------------------------
                | Success Response
                |--------------------------------------------------------------------------
                */

                return response()->json([

                    'status' => 1,

                    'message' =>
                        'Invoice generated successfully.',

                    'data' =>
                        asset(
                            $file
                        )

                ]);

            } catch (\Throwable $e) {


                \Log::error(

                    'Caravan Invoice Error',

                    [

                        'message' =>
                            $e->getMessage(),

                        'file' =>
                            $e->getFile(),

                        'line' =>
                            $e->getLine(),

                        'order_id' =>
                            $request->orderID

                    ]

                );


                return response()->json([

                    'status' =>
                        0,

                    'message' =>
                        $e->getMessage() .
                        ' | File: ' .
                        basename(
                            $e->getFile()
                        ) .
                        ' | Line: ' .
                        $e->getLine()

                ]);
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function getCaravanMasterInventory()
    {
        $aColumns = array('date', 'caravan_id', 'initial_quantity', 'total_available', 'total_booked', 'total_blocked');
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
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS *  FROM $sTable $sWhere $sOrder $sLimit";
        //  echo $sQuery;exit;
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
        $MasterCar = MasterCaravan::pluck('title', 'id');

        foreach ($rResult as $aRow) {
            $row = array();


            $row[] = date("d M Y", strtotime($aRow->date));
            $row[] = $MasterCar[$aRow->caravan_id];
            $row[] = '<a href="javascript:void(0)" class="change-qty" title="Click to change quantity" data-toggle="modal" data-target="#changeQtyModal" data-available="' . $aRow->total_available . '" data-id="' . $aRow->id . '" data-qty="' . $aRow->initial_quantity . '">' . $aRow->initial_quantity . '</a>';
            $row[] = $aRow->total_available;
            $row[] = $aRow->total_booked;
            $row[] = ($aRow->total_blocked > 0) ? '<a href="javascript:void(0)" class="release-qty" title="Click to release quantity" data-toggle="modal" data-target="#changeBlockModal" data-blocked="' . $aRow->total_blocked . '" data-id="' . $aRow->id . '">' . $aRow->total_blocked . '</a>' : $aRow->total_blocked;

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
        /*
        |--------------------------------------------------------------------------
        | Logged-in User
        |--------------------------------------------------------------------------
        */
        $user = Auth::user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Your session has expired. Please login again.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Caravan Dropdown
        |--------------------------------------------------------------------------
        */
        $caravanQuery = MasterCaravan::query()
            ->whereNull('deleted_at');


        /*
        |--------------------------------------------------------------------------
        | Vendor Filter
        |--------------------------------------------------------------------------
        */
        if ($user->access_type == 'vendor') {

            $vendorId = ($user->role == 2)
                ? $user->id
                : $user->vendor_id;

            $caravanQuery->where(
                'vendor_id',
                $vendorId
            );
        }


        $MasterCaravan = $caravanQuery
            ->orderBy(
                'title',
                'asc'
            )
            ->pluck(
                'title',
                'id'
            );


        /*
        |--------------------------------------------------------------------------
        | Check Whether Search Button Was Used
        |--------------------------------------------------------------------------
        |
        | Initial page:
        | today's date is displayed,
        | but booking data is not loaded.
        |
        |--------------------------------------------------------------------------
        */
        $shouldFetch = $request->has('check_date');


        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */
        $caravan_id = $request->filled('caravan_id')
            ? $request->caravan_id
            : 0;


        $report_type = $request->filled('report_type')
            ? $request->report_type
            : 'book_date';


        $check_date = $request->filled('check_date')
            ? $request->check_date
            : Carbon::today()->format('d-m-Y');


        /*
        |--------------------------------------------------------------------------
        | Convert Date
        |--------------------------------------------------------------------------
        */
        try {

            $date = Carbon::createFromFormat(
                'd-m-Y',
                $check_date
            )->format(
                    'Y-m-d'
                );

        } catch (\Exception $exception) {

            return redirect()
                ->route(
                    'caravan-booking-report'
                )
                ->withErrors([
                    'check_date' =>
                        'Please select a valid date.'
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Caravan Booking Query
        |--------------------------------------------------------------------------
        |
        | Same relationship style as Hall:
        |
        | caravan_bookings.booking_id
        | =
        | order_masters.order_id
        |
        |--------------------------------------------------------------------------
        */
        $query = DB::table('caravan_bookings as cb')

            ->join(
                'master_caravans as mc',
                'cb.caravan_id',
                '=',
                'mc.id'
            )

            ->join(
                'order_masters as om',
                'cb.booking_id',
                '=',
                'om.order_id'
            )

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

            ->where(
                'om.service_type',
                'caravan'
            );


        /*
        |--------------------------------------------------------------------------
        | Vendor Filter
        |--------------------------------------------------------------------------
        */
        if ($user->access_type == 'vendor') {

            $vendorId = ($user->role == 2)
                ? $user->id
                : $user->vendor_id;

            $query->where(
                'cb.vendor_id',
                $vendorId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Caravan Filter
        |--------------------------------------------------------------------------
        */
        if (
            !empty($caravan_id) &&
            (int) $caravan_id !== 0
        ) {

            $query->where(
                'cb.caravan_id',
                (int) $caravan_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */
        if ($report_type == 'book_date') {

            /*
             * Booking Date
             */
            $query->whereDate(
                'cb.created_at',
                $date
            );

        } else {

            /*
             * Travel Period
             *
             * Selected date falls between
             * start_date and end_date.
             */
            $query
                ->whereDate(
                    'cb.start_date',
                    '<=',
                    $date
                )
                ->whereDate(
                    'cb.end_date',
                    '>=',
                    $date
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Fetch Data Only After Search
        |--------------------------------------------------------------------------
        */
        if ($shouldFetch) {

            $bookings = $query
                ->orderBy(
                    'cb.created_at',
                    'desc'
                )
                ->get();

        } else {

            $bookings = collect();
        }


        /*
        |--------------------------------------------------------------------------
        | Prepare View Data
        |--------------------------------------------------------------------------
        */
        $CaravanBookingData = [];


        foreach ($bookings as $row) {

            /*
            |--------------------------------------------------------------------------
            | Booking Date
            |--------------------------------------------------------------------------
            */
            $bookingDate = !empty(
                $row->booking_date
            )
                ? Carbon::parse(
                    $row->booking_date
                )->format(
                        'd-m-Y'
                    )
                : 'N/A';


            /*
            |--------------------------------------------------------------------------
            | Travel Period
            |--------------------------------------------------------------------------
            */
            $travelPeriod = 'N/A';


            if (
                !empty($row->start_date) &&
                !empty($row->end_date)
            ) {

                $startDate = Carbon::parse(
                    $row->start_date
                )->format(
                        'd-m-Y'
                    );


                $endDate = Carbon::parse(
                    $row->end_date
                )->format(
                        'd-m-Y'
                    );


                if ($startDate == $endDate) {

                    $travelPeriod = $startDate;

                } else {

                    $travelPeriod =
                        $startDate .
                        ' - ' .
                        $endDate;
                }

            } elseif (!empty($row->start_date)) {

                $travelPeriod = Carbon::parse(
                    $row->start_date
                )->format(
                        'd-m-Y'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Final Row
            |--------------------------------------------------------------------------
            */
            $CaravanBookingData[] = [

                'invoice_id' =>
                    $row->invoice_id
                    ?? 'N/A',

                'order_id' =>
                    $row->order_id
                    ?? '',

                'booking_date' =>
                    $bookingDate,

                'customer_name' =>
                    $row->customer_name
                    ?? 'N/A',

                'caravan_name' =>
                    $row->caravan_name
                    ?? 'N/A',

                'travel_period' =>
                    $travelPeriod,

                'total_amount' =>
                    number_format(
                        (float) (
                            $row->total_amount
                            ?? 0
                        ),
                        2
                    ),

                'status' =>
                    $row->status
                    ?? 'N/A',

                /*
                 * Used by invoice eye button
                 */
                'oderID' =>
                    $row->order_id
                    ?? '',

                'link' =>
                    1
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */
        return view(
            'caravan.caravan-booking-report',
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
        /*
        |--------------------------------------------------------------------------
        | Get Authenticated User
        |--------------------------------------------------------------------------
        */
        $user = Auth::user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Your session has expired. Please login again.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Get Accessible Caravans
        |--------------------------------------------------------------------------
        */
        $caravanQuery = MasterCaravan::query()
            ->whereNull('deleted_at');

        if ($user->access_type == 'vendor') {

            $vendorId = (
                $user->role == 2
            )
                ? $user->id
                : $user->vendor_id;

            $caravanQuery->where(
                'vendor_id',
                $vendorId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Caravan Dropdown
        |--------------------------------------------------------------------------
        */
        $MasterCaravan = $caravanQuery
            ->orderBy(
                'title',
                'asc'
            )
            ->pluck(
                'title',
                'id'
            );

        /*
        |--------------------------------------------------------------------------
        | Selected Caravan
        |--------------------------------------------------------------------------
        */
        $CaravanId = $request->get(
            'caravan_id'
        );

        if (
            empty($CaravanId) &&
            $MasterCaravan->isNotEmpty()
        ) {

            $CaravanId = $MasterCaravan
                ->keys()
                ->first();
        }

        $CaravanId = !empty($CaravanId)
            ? (int) $CaravanId
            : null;

        /*
        |--------------------------------------------------------------------------
        | Check Access
        |--------------------------------------------------------------------------
        */
        if (
            $CaravanId !== null &&
            !$MasterCaravan->has(
                $CaravanId
            )
        ) {

            abort(
                403,
                'You cannot access the selected caravan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Caravan Details
        |--------------------------------------------------------------------------
        */
        $SelectedCaravan = null;

        if ($CaravanId) {

            $SelectedCaravan = MasterCaravan::where(
                'id',
                $CaravanId
            )
                ->whereNull(
                    'deleted_at'
                )
                ->first();
        }

        $CaravanName = $SelectedCaravan
            ? $SelectedCaravan->title
            : '';

        $TotalCaravan = $SelectedCaravan
            ? (int) (
                $SelectedCaravan->quantity
                ?? 0
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Default Date Range
        |--------------------------------------------------------------------------
        */
        $startDate = Carbon::today()
            ->startOfDay();

        $endDate = Carbon::today()
            ->addDays(30)
            ->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Selected Date Range
        |--------------------------------------------------------------------------
        */
        if ($request->filled('check_date')) {

            try {

                $range = preg_split(
                    '/\s+-\s+/',
                    trim(
                        $request->get(
                            'check_date'
                        )
                    )
                );

                if (
                    !is_array($range) ||
                    count($range) != 2
                ) {

                    throw new \Exception(
                        'Invalid date range'
                    );
                }

                $startDate = Carbon::createFromFormat(
                    'd-m-Y',
                    trim($range[0])
                )->startOfDay();

                $endDate = Carbon::createFromFormat(
                    'd-m-Y',
                    trim($range[1])
                )->endOfDay();

            } catch (\Exception $exception) {

                return redirect()
                    ->route(
                        'caravan-availability-report'
                    )
                    ->withErrors([
                        'check_date' =>
                            'Please select a valid date range.'
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Reverse Date Range If Needed
        |--------------------------------------------------------------------------
        */
        if (
            $endDate->lt(
                $startDate
            )
        ) {

            $oldStartDate =
                $startDate->copy();

            $startDate =
                $endDate
                    ->copy()
                    ->startOfDay();

            $endDate =
                $oldStartDate
                    ->copy()
                    ->endOfDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare Caravan Availability Data
        |--------------------------------------------------------------------------
        |
        | Booked is currently 0.
        |
        | Once CaravanBooking fields are confirmed,
        | booked data can be calculated here.
        |
        |--------------------------------------------------------------------------
        */
        $CaravanReportData = [];

        $period = CarbonPeriod::create(

            $startDate
                ->copy()
                ->startOfDay(),

            $endDate
                ->copy()
                ->startOfDay()
        );

        foreach ($period as $date) {

            /*
             * TODO:
             * Replace with actual booking count.
             */
            $booked = 0;

            $available =
                max(
                    $TotalCaravan -
                    $booked,
                    0
                );

            $CaravanReportData[] = [

                'date' =>
                    $date->format(
                        'Y-m-d'
                    ),

                'display_date' =>
                    $date->format(
                        'd-M-Y'
                    ),

                'booked' =>
                    $booked,

                'total' =>
                    $TotalCaravan,

                'available' =>
                    $available
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */
        return view(
            'caravan.caravan-availability-report',
            [
                'MasterCaravan' =>
                    $MasterCaravan,

                'CaravanId' =>
                    $CaravanId,

                'CaravanName' =>
                    $CaravanName,

                'CaravanReportData' =>
                    $CaravanReportData,

                'start_date' =>
                    $startDate->format(
                        'Y-m-d'
                    ),

                'end_date' =>
                    $endDate->format(
                        'Y-m-d'
                    )
            ]
        );
    }
}