<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
Use App\PasswordRemQuestion;
Use App\User;
Use App\Country;
Use App\State;
Use App\City;
Use App\Service;
use Session;
Use App\CategoryTable;
Use App\MerchantProduct;
Use App\ProductCategory;

class MerchantController extends Controller
{
    public $site;
    public $frontendUrl;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }
    
    public function getSubcategories($id = null, $CategoryData = array()) {
//        $categories = CategoryTable::where('parent_id', $id)->pluck('category_name', 'id');
//        foreach ($categories as $key => $value) {
//            $CategoryData[] = array(
//                'id' => $key,
//                'name' => $value,
//                'child' => $this->getSubcategories($key)
//            );
//        }
//        $categories = CategoryTable::where('parent_id', $id)->get(); //->pluck('category_name', 'id');
//        foreach ($categories as $value) {
//            $CategoryData .= '<option class="l2" value="'. $value->id .'">'. $value->category_name .'</option>';
//            $CategoryData .= $this->getSubcategories($value->id);
//        }
        $categories = CategoryTable::where('parent_id', $id)->get(); //->pluck('category_name', 'id');
        foreach ($categories as $value) {
            $CategoryData[$value->id] = $value->category_name;
            $CategoryData = $CategoryData + $this->getSubcategories($value->id);
        }
        return $CategoryData;     
    }
    
    public function categories() {
        if (!(parent::checkViewPrivilege(50))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $ParentCategory = CategoryTable::where('parent_id', 0)->get();//pluck('category_name', 'id');
        
        return view('merchant.categories', compact('ParentCategory'));
    }
    
    public function addEditCategories($id = null) {
        if (!(parent::checkWritePrivilege(50))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Category = array();
        if (!is_null($id)) {
            if (Auth::user()->access_type == 'vendor') {
                Session::flash('success', 'You are not autherised to do this operation.');
                return redirect()->back();
            }
            $Category = CategoryTable::find($id);
            $Category->image = (!empty($Category->image)) ? $this->site . $Category->image : '';
        }
        $CategoryData = array();
        $ParentCategory = CategoryTable::where('parent_id', 0)->get();
        if (!empty($ParentCategory)) {
            $i = 0;
            foreach ($ParentCategory as $value) {
                $CategoryData[(string)$value->id] = $value->category_name;
                $CategoryData = $CategoryData + $this->getSubcategories($value->id);
            }
        }
        return view('merchant.add-edit-categories', compact('CategoryData', 'Category'));
    }
    
    public function addCategoriesRequest(Request $request) {
        // echo "<pre>";print_r($request->all());exit;
        if (empty($request->id)) {
            $validate = Validator::make($request->all(), [
                'category_name' => 'required|string|min:3|max:255',
                'parent_id' => 'required|numeric', 
                'image' => 'mimes:jpeg,png,jpg',
            ]);
            if ($validate->fails()) {
                $errors = $validate->errors();
                return Redirect::to('add-edit-categories')->withErrors($validate)->withInput();
            } else {
                $UploadDir = 'images/merchant/';
                $cat_image = '';
                
                if ($request->hasFile('image')) {
                    if ($request->file('image')->isValid()) {
                        $filenameWithExt = str_replace(' ', '-', $request->file('image')->getClientOriginalName());
                        $image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->image->extension();
                        $request->image->move(public_path($UploadDir), $image);
                        $cat_image = $UploadDir . $image;
                    }
                }
                $Category = new CategoryTable([
                    'category_name' => trim($request->category_name),
                    'slug' => str_replace(' ', '-', trim(strtolower($request->category_name))),
                    'parent_id' => $request->parent_id,
                    'image' => $cat_image,
                    'created_by' => Auth::user()->id
                ]);
            
                if ($Category->save()) {
                    Session::flash('success', 'Category saved successful.');
                    return Redirect::to('categories');
                } else {
                    Session::flash('success', 'Unable to add slot');
                    return Redirect::to('add-edit-categories');
                }
            }
        } else {
            $validate = Validator::make($request->all(), [
                'category_name' => 'required|string|min:3|max:255',
                'parent_id' => 'required|numeric', 
                'image' => 'mimes:jpeg,png,jpg',
            ]);
            if ($validate->fails()) {
                $errors = $validate->errors();
                return Redirect::to('add-edit-categories/'.$request->id)->withErrors($validate)->withInput();
            } else {
                $UploadDir = 'images/merchant/';
                
                $Category = CategoryTable::find($request->id);
                $Category->category_name = trim($request->category_name);
                $Category->slug = str_replace(' ', '-', trim(strtolower($request->category_name)));
                $Category->parent_id = $request->parent_id;
                if ($request->hasFile('image')) {
                    if ($request->file('image')->isValid()) {
                        $old_feature = public_path($Category->image);
                        if (file_exists($old_feature) && !empty($Category->image)) {
                            unlink($old_feature);
                        }
                        $filenameWithExt = str_replace(' ', '-', $request->file('image')->getClientOriginalName());
                        $image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->image->extension();
                        $request->image->move(public_path($UploadDir), $image);
                        $Category->image = $UploadDir . $image;
                    }
                }
                if ($Category->save()) {
                    Session::flash('success', 'Category saved successful.');
                    return Redirect::to('categories');
                } else {
                    Session::flash('success', 'Unable to add slot');
                    return Redirect::to('add-edit-categories');
                }
            }
        }
    }
    
    public function merchantOprsn(Request $request) {
        if ($request->request_type == 'delete_category') {
            if (!(parent::checkWritePrivilege(50))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                if (CategoryTable::find($request->Id)->delete()) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Category removed successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete category.';
                }
            }
        } elseif ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(51))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('merchant_products')->whereIn('id', $item_array)->update(['status' => 'publish', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Product publish successful.';
            }
        } elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(51))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('merchant_products')->whereIn('id', $item_array)->update(['status' => 'draft', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Product moved to draft successfully.';
            }
        } elseif ($request->request_type == 'delete') {
            if (!(parent::checkWritePrivilege(51))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                $MerchantProduct = DB::table('merchant_products')->whereIn('id', $item_array)->get();
                if (!empty($MerchantProduct)) {
                    foreach ($MerchantProduct as $value) {
                        $feature_image = public_path($value->feature_image);
                        if (file_exists($feature_image) && !empty($value->feature_image)) {
                            unlink($feature_image);
                        }
                        $gallery = json_decode($value->gallery_image, 1);
                        foreach($gallery as $images) {
                            $image = public_path($images);
                            if (file_exists($image)) {
                                unlink($image);
                            }
                        }
                    }
                }
                DB::table('merchant_products')->whereIn('id', $item_array)->delete();
                $responce['status'] = 1;
                $responce['message'] = 'Product deleted successfully.';
            }
        } elseif ($request->request_type == 'get_subcategory') {
            $category = CategoryTable::where('parent_id', $request->categoryId)->pluck('category_name', 'id')->toArray();
            $options = '';
            if (!empty($category)) {
                foreach ($category as $key => $val) {
                    $options .= '<option value="' . $key . '">' . $val . '</option>';
                }
            }
            echo $options;
            exit;
        }
        echo json_encode($responce);
        exit;
    }
    
    public function merchantProduct() {
        if (!(parent::checkViewPrivilege(51))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        
        return view('merchant.products', compact('Vendors'));
    }
    
    public function getMerchantProduct(Request $request) {
        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'feature_image', 'name', 'sku', 'price', 'status');
        } else {
            $aColumns = array('id', 'feature_image', 'name', 'sku', 'price', 'status');
        }
        
        $sIndexColumn = "id";
        $sTable = "merchant_products";
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
        $searchColumns = array('name', 'sku', 'price');
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
        
        $vendorData = User::where('role', 2)->pluck('company', 'id');
        
        foreach ($rResult as $aRow) {
            $row = array();
            
            $row[] = '<div class="checkbox-fade fade-in-primary"><label><input type="checkbox" value="' . $aRow->id . '" class="itemcheck"><span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span></label></div>';
            if (Auth::user()->access_type == 'superadmin') {
                $row[] = isset($vendorData[$aRow->vendor_id]) ? $vendorData[$aRow->vendor_id] : 'N/A';
            }
            $row[] = '<img src="'. $aRow->feature_image .'" alt="Product Image" height="60" width="60">';
            $row[] = $aRow->name;
            $row[] = $aRow->sku;
            $row[] = $aRow->price;
            // $row[] = wordwrap($aRow->short_description,30,"<br>\n");
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            $row[] = '<a href="' . url('edit-merchant-product', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }
    
    public function addMerchantProduct() {
        if (!(parent::checkWritePrivilege(51))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        
        $CategoryData = array();
        $ParentCategory = CategoryTable::where('parent_id', 0)->get(); //->pluck('category_name', 'id');
        if (!empty($ParentCategory)) {
            $i = 0;
            foreach ($ParentCategory as $value) {
                $CategoryData[(string)$value->id] = $value->category_name;
                $CategoryData = $CategoryData + $this->getSubcategories($value->id);
            }
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        
        return view('merchant.add-merchant-product', compact('CategoryData', 'Vendors'));
    }
    
    public function ProductAddRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required',
                    'name' => 'required|string|min:3|max:255|unique:merchant_products',
                    'sku' => 'required|string|unique:merchant_products',
                    'descriptions' => 'required|string',
                    'category' => 'required',
                    'feature_image' => 'required|mimes:jpeg,png,jpg',
                    'images.*' => 'required|mimes:jpeg,png,jpg',
                    'price' => 'required|numeric',
                    'max_quantity' => 'required|numeric',
                    'status' => 'required|string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('add-merchant-product')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/merchant/';
            $gallery_images = array();
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
            $property = '';
            if (isset($request->properties)) {
                $data = array();
                foreach ($request->properties as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $property = json_encode($data);
            }
            $slug = str_replace(' ', '-', trim(strtolower($request->name)));
            
            $Product = new MerchantProduct([
                'vendor_id' => $request->vendor_id,
                'name' => trim($request->name),
                'slug' => $slug,
                'sku' => $request->sku,
                'category' => $request->category, //implode(',', $request->category),
                'descriptions' => addslashes($request->descriptions),
                'feature_image' => $UploadDir . $feature_image,
                'gallery_image' => json_encode($gallery_images),
                'price' => $request->price,
                'max_quantity' => $request->max_quantity,
                'properties' => $property,
                'status' => $request->status,
                'create_user' => Auth::user()->id,
            ]);
            if ($Product->save()) {
                $ProductId = $Product->id;
                $ProductCategory = new ProductCategory([
                    'category_id' => $request->category,
                    'product_id' => $ProductId
                ]);
                $ProductCategory->save();
                
//                $Prod_category = array();                
//                foreach ($request->category as $key => $value) {
//                    $Prod_category[$key]['category_id'] = $value;
//                    $Prod_category[$key]['product_id'] = $ProductId;
//                }
//                ProductCategory::insert($Prod_category);
                
                Session::flash('success', 'Product added successful.');
                return Redirect::to('merchant-products');
            } else {
                Session::flash('success', 'Unable to add product');
                return Redirect::to('add-merchant-product');
            }
        }
    }
    
    public function editMerchantProduct($id = null) {
        if (!(parent::checkWritePrivilege(51))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Product = MerchantProduct::find($id);
        if (!empty($Product)) {
            $Product->feature_image = $this->site . $Product->feature_image;
//            $Product->category = explode(',', $Product->category);
            $Product->properties = !empty($Product->properties) ? json_decode($Product->properties, 1) : [];
            $Product->gallery_image = json_decode($Product->gallery_image);
            $gallery = array();
            $count = 1;
            foreach ($Product->gallery_image as $value) {
                $gallery[] = ['id' => $count, 'src' => $this->site . $value];
                $count++;
            }
            $gallery = json_encode($gallery);
            
            $CategoryData = array();
            $ParentCategory = CategoryTable::where('parent_id', 0)->get(); //->pluck('category_name', 'id');
            if (!empty($ParentCategory)) {
                $i = 0;
                foreach ($ParentCategory as $value) {
                    $CategoryData[(string)$value->id] = $value->category_name;
                    $CategoryData = $CategoryData + $this->getSubcategories($value->id);
                }
            }
            $Vendors = User::where('role', '2')->pluck('company', 'id');
        } else {
            return redirect()->back();
        }
            
        return view('merchant.edit-merchant-product', compact('Product', 'CategoryData', 'gallery', 'Vendors'));
    }
    
    public function ProductEditRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required',
                    'name' => 'required|string|min:3|max:255',
                    'sku' => 'required|string',
                    'descriptions' => 'required|string',
                    'category' => 'required',
                    'feature_image' => 'mimes:jpeg,png,jpg',
                    'images.*' => 'mimes:jpeg,png,jpg',
                    'price' => 'required|numeric',
                    'max_quantity' => 'required|numeric',
                    'status' => 'required|string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('edit-merchant-product/'. $request->id)->withErrors($validate)->withInput();
        } else {
            $Product = MerchantProduct::find($request->id);
            $slug = str_replace(' ', '-', trim(strtolower($request->name)));
            
            $Product->name = trim($request->name);
            $Product->slug = $slug;
            $Product->sku = $request->sku;
            $Product->category = $request->category; //implode(',', $request->category);
            $Product->descriptions = addslashes($request->descriptions);
            $Product->price = $request->price;
            $Product->max_quantity = $request->max_quantity;            
            $Product->status = $request->status;
            $Product->update_user = Auth::user()->id;
            
            $UploadDir = 'images/merchant/';
            $gallery_images = array();
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $old_feature = public_path($Product->feature_image);
                    if (file_exists($old_feature)) {
                        unlink($old_feature);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                    $Product->feature_image = $UploadDir . $feature_image;
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
            $old_gallery = !empty($Product->gallery_image) ? json_decode($Product->gallery_image) : [];
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
            $Product->gallery_image = json_encode($gallery_images);
            
            $property = '';
            if (isset($request->properties)) {
                $data = array();
                foreach ($request->properties as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $property = json_encode($data);
            }
            $Product->properties = $property;
            
            if ($Product->save()) {
                DB::table('product_categories')->where('product_id', $request->id)->delete();
                
                $ProductCategory = new ProductCategory([
                    'category_id' => $request->category,
                    'product_id' => $Product->id
                ]);
                $ProductCategory->save();
                
                Session::flash('success', 'Product updated successful.');
                return Redirect::to('merchant-products');
            } else {
                Session::flash('success', 'Unable to update product');
                return Redirect::to('edit-merchant-product/'. $request->id);
            }
        }
    }
}
