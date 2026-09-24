<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Session;
Use App\User;
Use App\Country;
Use App\State;
Use App\City;
Use App\Service;
Use App\AvailableSlot;
Use App\FoodCategory;
Use App\FoodItem;

class RestaurantController extends Controller {

    public $site;
    public $frontendUrl;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    public function availableSlots() {
        if (!(parent::checkViewPrivilege(42))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $AvailableSlots = AvailableSlot::all();

        return view('restaurant.available-slots', compact('Vendors', 'AvailableSlots'));
    }

    public function slotOprsn(Request $request) {
        if ($request->request_type == 'delete_slot') {
            if (!(parent::checkWritePrivilege(42))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $slot = AvailableSlot::find($request->Id);
                if ($slot->delete()) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Slot delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete slot.';
                }
            }
        } elseif ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(43))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('food_categories')->whereIn('id', $item_array)->update(['status' => 'publish', 'updated_by' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Items publish successfully.';
            }
        } elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(43))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('food_categories')->whereIn('id', $item_array)->update(['status' => 'draft', 'updated_by' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Items moved to draft successfully.';
            }
        } elseif ($request->request_type == 'delete') {
            if (!(parent::checkWritePrivilege(43))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('food_categories')->whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Category deleted successfully.';
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function addAvailableSlots() {
        if (!(parent::checkWritePrivilege(42))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
        $CityDetail = City::where(['state_id' => 4013])->pluck('name', 'id')->toArray();

        return view('restaurant.add-slots', compact('Days', 'CityDetail'));
    }

    public function addSlotsRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:64|unique:available_slots',
                    'start_time' => 'required|string',
                    'end_time' => 'required|string',
                    'availability' => 'required',
                    'city' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-available-slots')->withErrors($validate)->withInput();
        } else {
            $start_time = date("H:i a", strtotime($request->start_time));
            $end_time = date("H:i a", strtotime($request->end_time));
            $Slot = new AvailableSlot([
                'name' => trim($request->name),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'availability' => implode(",", $request->availability),
                'city' => implode(",", $request->city)
            ]);
            if ($Slot->save()) {
                Session::flash('success', 'Slot added successful.');
                return Redirect::to('available-slots');
            } else {
                Session::flash('success', 'Unable to add slot');
                return Redirect::to('add-available-slots');
            }
        }
    }

    public function editAvailableSlots($id = null) {
        if (!(parent::checkWritePrivilege(42))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $SlotDetails = AvailableSlot::find($id);
        if (!empty($SlotDetails)) {
            $SlotDetails->availability = explode(',', $SlotDetails->availability);
            $SlotDetails->city = explode(',', $SlotDetails->city);
            $Days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
            $CityDetail = City::where(['state_id' => 4013])->pluck('name', 'id')->toArray();

            return view('restaurant.edit-slots', compact('SlotDetails', 'Days', 'CityDetail'));
        } else {
            return redirect()->back();
        }
    }
    
    public function editSlotsRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:64',
                    'start_time' => 'required|string',
                    'end_time' => 'required|string',
                    'availability' => 'required',
                    'city' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-available-slots/'. $request->id)->withErrors($validate)->withInput();
        } else {
            $Slot = AvailableSlot::find($request->id);
            $ExistSlot = AvailableSlot::where('name', $request->name)->get()->toArray();
            if (empty($ExistSlot)) {
                $Slot->name = trim($request->name);
            }
            $start_time = date("H:i a", strtotime($request->start_time));
            $end_time = date("H:i a", strtotime($request->end_time));

            $Slot->start_time = $start_time;
            $Slot->end_time = $end_time;
            $Slot->availability = implode(",", $request->availability);
            $Slot->city = implode(",", $request->city);

            if ($Slot->save()) {
                Session::flash('success', 'Slot update successful.');
                return Redirect::to('available-slots');
            } else {
                Session::flash('success', 'Unable to add slot');
                return Redirect::to('edit-available-slots/'. $request->id);
            }
        }
    }
    
    public function foodCategory() {
        if (!(parent::checkViewPrivilege(43))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }

        return view('restaurant.food-category');
    }
    
    public function getFoodCategory(Request $request) {
        
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'name', 'slot', 'status');
        } else {
            $aColumns = array('id', 'name', 'slot', 'status');
        }
        $sIndexColumn = "id";
        $sTable = "food_categories";
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
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $vendor_condtition = ' AND vendor_id = ' . $vendor_id;
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;
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
        $AvailableSlots = AvailableSlot::pluck('name', 'id');
        foreach ($rResult as $aRow) {

            $row = array();
            $alloted_slot = explode(',', $aRow->slot);
            $slots = '';
            foreach ($alloted_slot as $value) {
                $slots .= $AvailableSlots[$value] . '<br>';
            }
            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            if (Auth::user()->access_type == 'superadmin') {
                $vendorData = User::find($aRow->vendor_id);
                $row[] = (!empty($vendorData)) ? $vendorData->company : 'N/A';
            }
            $row[] = $aRow->name;
            $row[] = $slots;
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            $row[] = '<a href="' . url('edit-food-category', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';

            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }
    
    public function addFoodCategory() {
        if (!(parent::checkWritePrivilege(43))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $Slots = AvailableSlot::all();
        $AvailableSlots = array();
        foreach ($Slots as $value) {
            $AvailableSlots[$value->id] = $value->name .' ('. date('h:i a', strtotime($value->start_time)) .'-'. date('h:i a', strtotime($value->end_time)) .')';
        }

        return view('restaurant.add-food-category', compact('AvailableSlots', 'Vendors'));
    }

    public function addCategoryRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:128',
                    'vendor_id' => 'required|numeric',
                    'status' => 'required|string',
                    'slot' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-food-category')->withErrors($validate)->withInput();
        } else {
            $Category = new FoodCategory([
                'name' => trim($request->name),
                'vendor_id' => $request->vendor_id,
                'status' => $request->status,
                'slot' => implode(",", $request->slot),
                'created_by' => Auth::user()->id
            ]);
            if ($Category->save()) {
                Session::flash('success', 'Category added successful.');
                return Redirect::to('food-category');
            } else {
                Session::flash('success', 'Unable to add Category');
                return Redirect::to('add-food-category');
            }
        }
    }
    
    public function editFoodCategory($id = null) {
        if (!(parent::checkWritePrivilege(43))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $CategoryDetails = FoodCategory::find($id);
        if (!empty($CategoryDetails)) {
            $CategoryDetails->slot = explode(',', $CategoryDetails->slot);
            
            $Vendors = User::where('role', '2')->pluck('company', 'id');
            $Slots = AvailableSlot::all();
            $AvailableSlots = array();
            foreach ($Slots as $value) {
                $AvailableSlots[$value->id] = $value->name .' ('. date('h:i a', strtotime($value->start_time)) .'-'. date('h:i a', strtotime($value->end_time)) .')';
            }

            return view('restaurant.edit-food-category', compact('CategoryDetails', 'AvailableSlots', 'Vendors'));
        } else {
            return redirect()->back();
        }
    }
    
    public function editFoodCategoryRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:128',
                    'vendor_id' => 'required|numeric',
                    'status' => 'required|string',
                    'slot' => 'required'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-food-category/'. $request->id)->withErrors($validate)->withInput();
        } else {
            $Category = FoodCategory::find($request->id);

            $Category->name = $request->name;
            $Category->vendor_id = $request->vendor_id;
            $Category->slot = implode(",", $request->slot);
            $Category->status = $request->status;
            $Category->updated_by = Auth::user()->id;

            if ($Category->save()) {
                Session::flash('success', 'Slot update successful.');
                return Redirect::to('food-category');
            } else {
                Session::flash('success', 'Unable to add slot');
                return Redirect::to('edit-food-category/'. $request->id);
            }
        }
    }
    
    public function foodItems() {
        if (!(parent::checkViewPrivilege(44))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', 2)->pluck('company', 'id');
        return view('restaurant.food-items', compact('Vendors'));
    }
    
    public function getFoodItems(Request $request) {
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'image', 'item_name', 'category_id', 'price', 'short_description', 'slot', 'status');
        } else {
            $aColumns = array('id', 'image', 'item_name', 'category_id', 'price', 'short_description', 'slot', 'status');
        }
        
        $sIndexColumn = "id";
        $sTable = "food_items";
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
        $searchColumns = array('item_name', 'category_id', 'price');
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
        
        $vendorData = User::where('role', 2)->pluck('company', 'id');
        $CategoryData = FoodCategory::pluck('name', 'id');
        $AvailableSlots = AvailableSlot::pluck('name', 'id');
        
        foreach ($rResult as $aRow) {
            $row = array();
            
            $alloted_slot = explode(',', $aRow->slot);
//            $slots = '';
//            foreach ($alloted_slot as $value) {
//                $slots .= $AvailableSlots[$value] . '<br>';
//            }
            $vegFlag = ($aRow->is_veg == 1) ? '<i class="fa fa fa-circle fa-border text-success" style="border:1px solid green;" aria-hidden="true"></i>' : '<i class="fa fa fa-circle fa-border text-danger" style="border:1px solid red;" aria-hidden="true"></i>';
            
            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            if (Auth::user()->access_type == 'superadmin') {
                $row[] = isset($vendorData[$aRow->vendor_id]) ? $vendorData[$aRow->vendor_id] : 'N/A';
            }
            $row[] = '<img src="'. $aRow->image .'" alt="item Image" height="60" width="60">';
            $row[] = '<span style="float:left;">'. $vegFlag .'&nbsp;'. $aRow->item_name .'</span>';
            $row[] = $CategoryData[$aRow->category_id];
            $row[] = $aRow->price;
            $row[] = wordwrap($aRow->short_description,30,"<br>\n");
//            $row[] = $slots;
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            $row[] = '<a href="' . url('edit-food-item', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }
    
    public function foodOprsn(Request $request) {
        if ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(44))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('food_items')->whereIn('id', $item_array)->update(['status' => 'publish', 'updated_by' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Items publish successfully.';
            }
        } elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(44))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('food_items')->whereIn('id', $item_array)->update(['status' => 'draft', 'updated_by' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Items moved to draft successfully.';
            }
        } elseif ($request->request_type == 'delete') {
            if (!(parent::checkWritePrivilege(44))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                $FoodItems = FoodItem::whereIn('id', $item_array)->pluck('image', 'id')->toArray();
                $FoodItems = array_merge($FoodItems);
                $result = array_map(function($FoodItems) { return public_path($FoodItems); }, $FoodItems);
                array_map('unlink', $FoodItems);
                DB::table('food_items')->whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Items deleted successfully.';
            }
        }  elseif ($request->request_type == 'get_food_category') {
            $FoodCategory = FoodCategory::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            if (!empty($FoodCategory)) {
                $responce['status'] = 1;
                $responce['data'] = $FoodCategory;
            } else {
                $responce['status'] = 0;
                $responce['data'] = array();
            }
        }
        echo json_encode($responce);
        exit;
    }
    
    public function addFoodItem() {
        if (!(parent::checkWritePrivilege(44))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $AvailableSlots = AvailableSlot::pluck('name', 'id');
        $FoodCategory = FoodCategory::pluck('name', 'id');

        return view('restaurant.add-food-item', compact('AvailableSlots', 'Vendors', 'FoodCategory'));
    }

    public function addFoodItemRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required|numeric',
                    'category_id' => 'required|numeric',
//                    'slot' => 'required',
                    'item_name' => 'required|string|min:3|max:256',
                    'image' => 'required|mimes:jpeg,png,jpg',
                    'price' => 'required|numeric',
                    'is_veg' => 'required|numeric',
                    'status' => 'required|string',
                    'max_cart_qty' => 'required|numeric',
                    'max_qty' => 'required|numeric'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-food-item')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/food/';
            if ($request->hasFile('image')) {
                if ($request->file('image')->isValid()) {
                    $filenameWithExt = str_replace(' ', '-', $request->file('image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->image->extension();
                    $request->image->move(public_path($UploadDir), $banner_image);
                }
            }
            $FoodItem = new FoodItem([
                'vendor_id' => $request->vendor_id,
                'category_id' => $request->category_id,
//                'slot' => implode(",", $request->slot),
                'item_name' => trim($request->item_name),
                'short_description' => trim($request->short_description),
                'image' => $UploadDir . $banner_image,
                'price' => $request->price,
                'is_veg' => $request->is_veg,
                'status' => $request->status,
                'max_cart_qty' => $request->max_cart_qty,
                'max_qty' => $request->max_qty,
                'created_by' => Auth::user()->id
            ]);
            if ($FoodItem->save()) {
                Session::flash('success', 'Food added successful.');
                return Redirect::to('food-items');
            } else {
                Session::flash('success', 'Unable to add food');
                return Redirect::to('add-food-item');
            }
        }
    }
    
    public function editFoodItem($id = null) {
        if (!(parent::checkWritePrivilege(44))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $ItemDetails = FoodItem::find($id);
        if (!empty($ItemDetails)) {
            $ItemDetails->image = $this->site . $ItemDetails->image;
            $Vendors = User::where('role', '2')->pluck('company', 'id');
            $AvailableSlots = AvailableSlot::pluck('name', 'id');
            $FoodCategory = FoodCategory::pluck('name', 'id');

            return view('restaurant.edit-food-item', compact('AvailableSlots', 'Vendors', 'FoodCategory', 'ItemDetails'));
        } else {
            return redirect()->back();
        }
    }
    
    public function editFoodItemRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required|numeric',
                    'category_id' => 'required|numeric',
//                    'slot' => 'required',
                    'item_name' => 'required|string|min:3|max:256',
                    'image' => 'mimes:jpeg,png,jpg',
                    'price' => 'required|numeric',
                    'is_veg' => 'required|numeric',
                    'status' => 'required|string',
                    'max_cart_qty' => 'required|numeric',
                    'max_qty' => 'required|numeric'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-food-item/'. $request->id)->withErrors($validate)->withInput();
        } else {
            $FoodItem = FoodItem::find($request->id);
            $UploadDir = 'images/food/';
            if ($request->hasFile('image')) {
                if ($request->file('image')->isValid()) {
                    $old = public_path($FoodItem->image);
                    if (file_exists($old)) {
                        unlink($old);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->image->extension();
                    $request->image->move(public_path($UploadDir), $banner_image);
                    $FoodItem->image = $UploadDir . $banner_image;
                }
            }
            $FoodItem->vendor_id = $request->vendor_id;
            $FoodItem->category_id = $request->category_id;
//            $FoodItem->slot = implode(",", $request->slot);
            $FoodItem->item_name = trim($request->item_name);
            $FoodItem->short_description = trim($request->short_description);
            
            $FoodItem->price = $request->price;
            $FoodItem->is_veg = $request->is_veg;
            $FoodItem->status = $request->status;
            $FoodItem->max_cart_qty = $request->max_cart_qty;
            $FoodItem->max_qty = $request->max_qty;
            $FoodItem->updated_by = Auth::user()->id;

            if ($FoodItem->save()) {
                Session::flash('success', 'Food update successful.');
                return Redirect::to('food-items');
            } else {
                Session::flash('success', 'Unable to update food');
                return Redirect::to('edit-food-item/'. $request->id);
            }
        }
    }
}
