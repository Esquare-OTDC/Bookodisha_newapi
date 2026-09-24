<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
Use App\PasswordRemQuestion;
Use App\User;
Use App\Country;
Use App\State;
Use App\City;
Use App\Service;
use Session;
Use App\ServiceAttribute;
Use App\AttributeValue;
Use App\OrderDetail;
Use App\OrderMaster;
Use App\Ticket;
Use App\TicketAvailability;
use App\SubuserAccess;
use App\Prebooking;
use App\EmailTemplate;

class TicketingController extends Controller {

    public $site;
    public $frontendUrl;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    public function allTickets() {
        if (!(parent::checkViewPrivilege(24))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');

        return view('ticketing.all-tickets', compact('Vendors'));
    }

    public function getAllTickets(Request $request) {

        if (Auth::user()->access_type == 'superadmin') {
            $aColumns = array('id', 'vendor_id', 'name', 'category', 'place', 'contact_email', 'contact_number', 'status');
        } else {
            $aColumns = array('id', 'name', 'category', 'place', 'contact_email', 'contact_number', 'status');
        }
        $sIndexColumn = "id";
        $sTable = "tickets";
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
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                $staff_condition = '';
                if (!empty($SubuserAccess)) {
                    $staff_condition = ' AND id in (' . implode(',', $SubuserAccess) . ')';
                }
                $vendor_condtition .= $staff_condition;
            }
        }
        $sWhere = 'WHERE id != "" ' . $vendor_condtition;
        $searchColumns = array('name', 'category', 'city');
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
            $row[] = $aRow->name;
            $row[] = strtoupper($aRow->category);
            $row[] = $aRow->place;
            $row[] = $aRow->contact_email;
            $row[] = $aRow->contact_number;
            $row[] = ($aRow->status == 'publish') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #28a745;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Publish</span>' : '<span class="bg-warning" style="font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">' . $aRow->status . '</span>';
            // $row[] = date("M d Y", strtotime($aRow->created_at));
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">
                    <li><a href="'. url('ticketing-edit', $aRow->id) .'">Edit</a></li>
                    <li><a href="javascript:void(0)" class="deleteTicket" data-id="'. $aRow->id .'">Delete</a></li>
                </ul>
            </div>';
            // $row[] = '<a href="' . url('ticketing-edit', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function ticketOprsn(Request $request) {
        if ($request->request_type == 'publish') {
            if (!(parent::checkWritePrivilege(24))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tickets')->whereIn('id', $item_array)->update(['status' => 'publish', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Items publish successful.';
            }
        }
        elseif ($request->request_type == 'draft') {
            if (!(parent::checkWritePrivilege(24))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tickets')->whereIn('id', $item_array)->update(['status' => 'draft', 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Items moved to draft successfully.';
            }
        }
        elseif ($request->request_type == 'show_price') {
            if (!(parent::checkWritePrivilege(7))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tickets')->whereIn('id', $item_array)->update(['show_price' => 1, 'update_user' => Auth::user()->id]);
                $responce['status'] = 1;
                $responce['message'] = 'Ticket price shown successfully.';
            }
        }
        elseif ($request->request_type == 'hide_price') {
            if (!(parent::checkWritePrivilege(7))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $item_array = json_decode($request->IdArray);
                DB::table('tickets')->whereIn('id', $item_array)->update(['show_price' => 0, 'update_user' => Auth::user()->id]);               
                $responce['status'] = 1;
                $responce['message'] = 'Ticket price hidden successfully.';
            }
        }
        elseif ($request->request_type == 'delete_ticket') {
            if (!(parent::checkWritePrivilege(24))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Ticket = Ticket::find($request->Id);
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
                    foreach($gallery as $images) {
                        $image = public_path($images);
                        if (file_exists($image)) {
                            unlink($image);
                        }
                    }
                    $Ticket->delete();
                    
                    TicketAvailability::where('ticket_id', $Ticket->id)->delete();
                    
                    $responce['status'] = 1;
                    $responce['message'] = 'Ticketing deleted successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid ticketing id.';
                }
            }
        }
        elseif ($request->request_type == 'delete-attribute') {
            if (!(parent::checkWritePrivilege(26))) {
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
        }
        elseif ($request->request_type == 'delete-attribute-term') {
            if (!(parent::checkWritePrivilege(26))) {
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
        }
        elseif ($request->request_type == 'save-attribute-changes') {
            if (!(parent::checkWritePrivilege(26))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $ServiceSttribute = ServiceAttribute::find($request->Id);
                $ServiceSttribute->name = $request->attrName;
                // $ServiceSttribute->status = $request->attrStatus;
                if ($ServiceSttribute->save()) {
                    $responce['status'] = 1;
                    $responce['message'] = 'Changes saved successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to save cheanges.';
                }
            }
        }
        elseif ($request->request_type == 'save-terms-changes') {
            if (!(parent::checkWritePrivilege(26))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $AttributeTerm = AttributeValue::find($request->id);
                if (!empty($AttributeTerm)) {
                    $validate = Validator::make($request->all(), [
                        'name' => 'required|string',
                        // 'attrStatus' => 'required|string',
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
                        // $AttributeTerm->status = $request->attrStatus;
                        
                        $AttributeTerm->save();
                        $responce['status'] = 1;
                        $responce['message'] = 'Changes saved successfully.';
                    }
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid attribute id.';
                }
            }
        }
        elseif ($request->request_type == 'delete-blocked-ticket') {
            if (!(parent::checkWritePrivilege(65))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $BlockedData = TicketAvailability::find($request->Id);
                if (!empty($BlockedData)) {
                    $BlockedData->delete();

                    $responce['status'] = 1;
                    $responce['message'] = 'Delete successful.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to delete data.';
                }
            }
        }
        elseif ($request->request_type == 'print_booking_report') {
            if (!(parent::checkWritePrivilege(93))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $TicketQuery = DB::table('tickets'); //Ticket::where('status', 'publish');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $TicketQuery->where('vendor_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $TicketQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $ticketId = $request->ticket_id;
                if ($ticketId != 0) {
                    $TicketQuery->where('id', $ticketId);
                }
                $Ticket = $TicketQuery->pluck('id')->toArray();
                $check_date = date("Y-m-d", strtotime($request->filter_date));
                $html = '';
                
                if ($request->report_type == 'book_date') {
                    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px;">
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;" colspan="12"><h2>(Booking Date - '. date("d-M-Y", strtotime($check_date)) .')</h2></th></tr>
                    <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking ID</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking Date</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Guest</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Ticket Name</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Ticket Date</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Time</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Total Amount</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking Source</strong></th>
                    <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;"><strong>Booking Status</strong></th></tr>';
                    
                    $OrderMaster = OrderMaster::where(['service_type' => 'ticketing', 'payment_status' => 'success'])
                                    ->whereIn('service_name_id', $Ticket)
                                    ->where('created_at', 'LIKE', $check_date .'%')
                                    ->where('status', '!=', 'partially-cancelled')
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
                    if (!empty($OrderMaster->toArray())) {
                        foreach ($OrderMaster as $value) {
                            $status = ($value->status == 'cancelled') ? 'Cancelled<br>('. date("d-M-Y", strtotime($value->cancel_date)) .')' : 'Confirmed';
                            $html .= '<tr style="font-size:14px;">
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->invoice_id .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . date("d-M-Y h:i a", strtotime($value->created_at)) . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $value->customer_name .'<br>'. $value->customer_phone .'<br>'. $value->customer_email . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $value->service_name . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . date("d-M-Y", strtotime($value->start_date)) . '</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->start_time .' - '. $value->end_time .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->total_order_price .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $value->order_type .'</td>
                            <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $status .'</td>
                            </tr>';
                        }
                    }
                    $html .= '</table></div></body></html>';
                } elseif ($request->report_type == 'stay_date') {
                    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">';

                    $Orders = OrderMaster::where(['status' => 'completed'])
                        ->where('service_type', 'ticketing')
                        ->whereIn('service_name_id', $Ticket)
                        ->where('start_date', $check_date)
                        ->get();
                    if (!empty($Orders->toArray())) {
                        $MisHotelData = array();
                        foreach ($Orders as $key => $value) {
                            $hotelId = $hotelName = $start_date = $time = '';
                            $start_date = date("d-M-Y", strtotime($value->start_date));
                            $time = $value->start_time .' - '. $value->end_time;
                            $hotelId = $value->service_name_id;
                            $hotelName = $value->service_name;
                            

                            $MisHotelData[$hotelId]['data'][] = array(
                                'invoice_id' => $value->invoice_id,
                                'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                                'guest_name' => $value->customer_name,
                                'guest_phone' => $value->customer_phone,
                                'guest_email' => $value->customer_email,
                                'unit_name' => $value->service_name,
                                'check_in' => $start_date, 
                                'time' => $time,
                                'occupancy' => $value->total_guests,
                                'total_amount' => $value->total_order_price,
                                'order_type' => $value->order_type,
                            );                        

                            $MisHotelData[$hotelId]['name'] = $hotelName;
                            $MisHotelData[$hotelId]['total_book'] = (isset($MisHotelData[$hotelId]['total_book'])) ? $MisHotelData[$hotelId]['total_book'] + 1 : 1;
                            $MisHotelData[$hotelId]['total_occupancy'] = (isset($MisHotelData[$hotelId]['total_occupancy'])) ? $MisHotelData[$hotelId]['total_occupancy'] + $value->total_guests : $value->total_guests;
                        }
                        foreach ($MisHotelData as $key => $val) {
                            $html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border:1px solid #000; border-radius: 4px; margin-bottom: 20px;">
                            <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000;" colspan="8"><h2>'. $val['name'] .' (Stay by Date - '. date("d-M-Y", strtotime($check_date)) .')</h2></th>
                            <tr><th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Booking ID</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Guest</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Booking Date</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Ticket Date</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Time</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Occupancy</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Total Amount</strong></th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000; padding:5px;"><strong>Booking Source</strong></th></tr>';
                            foreach($val['data'] as $details) {
                                $html .= '<tr style="font-size:14px;">
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['invoice_id'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['guest_name'] .'<br>'. $details['guest_phone'] .'<br>'. $details['guest_email'] . '</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['book_date'] . '</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">' . $details['check_in'] . '</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['time'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['occupancy'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['total_amount'] .'</td>
                                <td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $details['order_type'] .'</td></tr>';
                            }
                            $html .= '<tr style="font-size:14px;"><th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="5">Total Bookings:'. $val['total_book'] .'</th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $val['total_occupancy'] .'</th>
                            <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;"></th>
                            </tr></table>';
                            
                        }
                    }
                    $html .= '</div></body></html>';
                }
                
                $file = 'documents/Ticket_booking_report_' . date("d-m-Y h-i-a") . '.pdf';
                $pdfname = public_path($file);
                PDF::loadHTML(html_entity_decode($html))->save($pdfname);
                // echo $this->site . $file;
                // exit;
                $responce['status'] = 1;
                $responce['file_path'] = $file; 
                // return response()->download($pdfname)->deleteFileAfterSend(true);
            }
        }
        elseif ($request->request_type == 'export_booking_report') {
            if (!(parent::checkWritePrivilege(93))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $TicketQuery = DB::table('tickets'); //Ticket::where('status', 'publish');
                if (Auth::user()->access_type == 'vendor') {
                    $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
                    $TicketQuery->where('vendor_id', $vender_id);
                    if ((Auth::user()->role == 3)) {
                        $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                        if (!empty($SubuserAccess)) {
                            $TicketQuery->whereIn('id', array_values($SubuserAccess));
                        }
                    }
                }
                $ticketId = $request->ticket_id;
                if ($ticketId != 0) {
                    $TicketQuery->where('id', $ticketId);
                }
                $Ticket = $TicketQuery->pluck('id')->toArray();
                $check_date = date("Y-m-d", strtotime($request->filter_date));
                
                if ($request->report_type == 'book_date') {                   

                    $spreadsheet = new Spreadsheet(); 
                    $sheet = $spreadsheet->getActiveSheet();
                    $total_column = 9;

                    $sheet->mergeCellsByColumnAndRow(1, 1, $total_column, 1);
                    $sheet->setCellValueByColumnAndRow(1,1,'(Booking Date - '. date("d-M-Y", strtotime($check_date)) .')');
                    
                    $sheet->setCellValueByColumnAndRow(1,2,'Booking ID');
                    $sheet->setCellValueByColumnAndRow(2,2,'Booking Date');
                    $sheet->setCellValueByColumnAndRow(3,2,'Guest');
                    $sheet->setCellValueByColumnAndRow(4,2,'Ticket Name');
                    $sheet->setCellValueByColumnAndRow(5,2,'Ticket Date');
                    $sheet->setCellValueByColumnAndRow(6,2,'Time');
                    $sheet->setCellValueByColumnAndRow(7,2,'Total Amount');
                    $sheet->setCellValueByColumnAndRow(8,2,'Booking Source');
                    $sheet->setCellValueByColumnAndRow(9,2,'Booking Status');

                    $OrderMaster = OrderMaster::where(['service_type' => 'ticketing', 'payment_status' => 'success'])
                                    ->whereIn('service_name_id', $Ticket)
                                    ->where('created_at', 'LIKE', $check_date .'%')
                                    ->where('status', '!=', 'partially-cancelled')
                                    ->orderBy('created_at', 'DESC')
                                    ->get();
                    if (!empty($OrderMaster->toArray())) {
                        $i = 3;
                        foreach ($OrderMaster as $value) {
                            $status = ($value->status == 'cancelled') ? 'Cancelled , ('. date("d-M-Y", strtotime($value->cancel_date)) .')' : 'Confirmed';

                            $sheet->setCellValueByColumnAndRow(1,$i,$value->invoice_id);
                            $sheet->setCellValueByColumnAndRow(2,$i,date("d-M-Y h:i a", strtotime($value->created_at)));
                            $sheet->setCellValueByColumnAndRow(3,$i,$value->customer_name .' , '. $value->customer_phone .' , '. $value->customer_email);
                            $sheet->setCellValueByColumnAndRow(4,$i,$value->service_name);
                            $sheet->setCellValueByColumnAndRow(5,$i,date("d-M-Y", strtotime($value->start_date)));
                            $sheet->setCellValueByColumnAndRow(6,$i,$value->start_time .' - '. $value->end_time);
                            $sheet->setCellValueByColumnAndRow(7,$i,$value->total_order_price);
                            $sheet->setCellValueByColumnAndRow(8,$i,$value->order_type);
                            $sheet->setCellValueByColumnAndRow(9,$i,$status);
                            $i++;
                        }
                    }
                    $file_name = 'documents/Ticket_booking_report_'. date("d-m-Y h-i-s") .'.xlsx';
                    $xsl_name = public_path($file_name);

                    $writer = new Xlsx($spreadsheet); 
                    $writer->save($xsl_name);

                    $responce['status'] = 1;
                    $responce['file_path'] = $this->site . $file_name;
                } elseif ($request->report_type == 'stay_date') {
                    // $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Odisha Tourism</title></head><body style="color:#000;"><div style="margin:0 auto; width:760px; padding-left:10px; padding-right:10px; padding-bottom:10px; padding-top:10px; border:1px solid #333; border-radius: 4px; background:#fff;">';
                    
                    $Orders = OrderMaster::where(['status' => 'completed'])
                            ->where('service_type', 'ticketing')
                            ->whereIn('service_name_id', $Ticket)
                            ->where('start_date', $check_date)
                            ->get();
                    if (!empty($Orders->toArray())) {
                        $MisHotelData = array();
                        foreach ($Orders as $key => $value) {
                            $hotelId = $hotelName = $start_date = $time = '';
                            
                            $start_date = date("d-M-Y", strtotime($value->start_date));
                            $time = $value->start_time .' - '. $value->end_time;
                            $hotelId = $value->service_name_id;
                            $hotelName = $value->service_name;

                            $MisHotelData[$hotelId]['data'][] = array(
                                'invoice_id' => $value->invoice_id,
                                'book_date' => date("d-M-Y hI a", strtotime($value->created_at)),
                                'guest_name' => $value->customer_name,
                                'guest_phone' => $value->customer_phone,
                                'guest_email' => $value->customer_email,
                                'unit_name' => $value->service_name,
                                'check_in' => $start_date, 
                                'time' => $time,
                                'occupancy' => $value->total_guests,
                                'total_amount' => $value->total_order_price,
                                'order_type' => $value->order_type,
                            );                        

                            $MisHotelData[$hotelId]['name'] = $hotelName;
                            $MisHotelData[$hotelId]['total_book'] = (isset($MisHotelData[$hotelId]['total_book'])) ? $MisHotelData[$hotelId]['total_book'] + 1 : 1;
                            $MisHotelData[$hotelId]['total_occupancy'] = (isset($MisHotelData[$hotelId]['total_occupancy'])) ? $MisHotelData[$hotelId]['total_occupancy'] + $value->total_guests : $value->total_guests;
                        }
                        
                        $spreadsheet = new Spreadsheet(); 
                        $sheet = $spreadsheet->getActiveSheet();
                        $total_column = 8;

                        $i = 1;
                        foreach ($MisHotelData as $key => $val) {
                            
                            $sheet->mergeCellsByColumnAndRow(1, $i, $total_column, $i);
                            $sheet->setCellValueByColumnAndRow(1,$i,$val['name'] .' (Stay by Date - '. date("d-M-Y", strtotime($check_date)) .')');
                            $i++;

                            $sheet->setCellValueByColumnAndRow(1,$i,'Booking ID');
                            $sheet->setCellValueByColumnAndRow(2,$i,'Guest');
                            $sheet->setCellValueByColumnAndRow(3,$i,'Booking Date');
                            $sheet->setCellValueByColumnAndRow(4,$i,'Ticket Date');
                            $sheet->setCellValueByColumnAndRow(5,$i,'Time');
                            $sheet->setCellValueByColumnAndRow(6,$i,'Occupancy');
                            $sheet->setCellValueByColumnAndRow(7,$i,'Total Amount');
                            $sheet->setCellValueByColumnAndRow(8,$i,'Booking Source');                                                        
                            $i++;
                            
                            foreach($val['data'] as $details) {
                                $sheet->setCellValueByColumnAndRow(1,$i,$details['invoice_id']);
                                $sheet->setCellValueByColumnAndRow(2,$i,$details['guest_name'] .' , '. $details['guest_phone'] .' , '. $details['guest_email']);
                                $sheet->setCellValueByColumnAndRow(3,$i,$details['book_date']);
                                $sheet->setCellValueByColumnAndRow(4,$i,$details['check_in']);
                                $sheet->setCellValueByColumnAndRow(5,$i,$details['time']);
                                $sheet->setCellValueByColumnAndRow(6,$i,$details['occupancy']);
                                $sheet->setCellValueByColumnAndRow(7,$i,$details['total_amount']);
                                $sheet->setCellValueByColumnAndRow(8,$i,$details['order_type']);
                                $i++;
                            }
                            $sheet->mergeCellsByColumnAndRow(1, $i, 5, $i);
                            $sheet->setCellValueByColumnAndRow(1,$i,'Total Bookings:'. $val['total_book']);
                            // $i++;
                            $sheet->setCellValueByColumnAndRow(6,$i,$val['total_occupancy']);
                            $i += 2;
                            // $html .= '<tr style="font-size:14px;"><th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="6">Total Bookings:'. $val['total_book']  .' | '. implode(' | ', array_map(function ($v, $k) { return $k.':'.$v; },$MisHotelData[$key]['rooms'],array_keys($MisHotelData[$key]['rooms']))) .' | Total Booked Rooms:'. $val['total_rooms'] .'</th>
                            // <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;">'. $val['total_occupancy'] .'</th>
                            // <th align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;padding:5px;" colspan="4"></th>
                            // </tr></table>';
                            
                        }
                    }
                    $file_name = 'documents/Ticket_stay_by_date_report_'. date("d-m-Y h-i-s") .'.xlsx';
                    $xsl_name = public_path($file_name);

                    $writer = new Xlsx($spreadsheet); 
                    $writer->save($xsl_name);

                    $responce['status'] = 1;
                    $responce['file_path'] = $this->site . $file_name;
                    // $html .= '</div></body></html>';
                }
                
                // $file = 'documents/Booking_report_' . date("d-m-Y h-i-a") . '.pdf';
                // $pdfname = public_path($file);
                // PDF::loadHTML(html_entity_decode($html))->save($pdfname);
                // // echo $this->site . $file;
                // // exit;
                // $responce['status'] = 1;
                // $responce['file_path'] = $file; 
                // // return response()->download($pdfname)->deleteFileAfterSend(true);
            }
        }
        elseif ($request->request_type == 'approve_ticket_booking') {
            if (!(parent::checkWritePrivilege(65))) {
                $responce['status'] = 0;
                $responce['message'] = 'You are not autherised to do this operation.';
            } else {
                $Prebooking = Prebooking::find($request->Id);
                if (!empty($Prebooking)) {
                    $CustomerData = User::where('email', $Prebooking->email)->orWhere('phone', $Prebooking->phone)->first();
                    $userId = 0;
                    if (!empty($CustomerData)) {
                        $CustomerData->email = $Prebooking->email;
                        $CustomerData->phone = $Prebooking->phone;
                        $userId = $CustomerData->id;
                    } else {
                        $user = new User([
                            'email' => $Prebooking->email,
                            'password' => bcrypt(rand(10000000,99999999)),
                            'first_name' => $Prebooking->cust_first_name,
                            'last_name' => $Prebooking->cust_last_name,
                            'phone' => $Prebooking->phone,
                            'pincode' => $Prebooking->zipcode,
                            'vendor_id' => 0,
                            'role' => 4,
                            'login_type' => 'email',
                            'access_type' => 'customer',
                            'status' => 1,
                            'create_account_approval' => 1,
                            'email_verified_at' => date("Y-m-d H:i:s")
                        ]);
                        $user->save();
                        $userId = $user->id;
                    }
                    $Prebooking->customer_id = $userId;
                    $Prebooking->approve_status = 1;
                    $Prebooking->save();
                    
                    $admin = User::where('role', 1)->first();
                    $UserTemplete = EmailTemplate::where('ref_code', 'PrebookingApprove')->first();
                    $Message = str_replace(array("~firstname~", "~lastname~", "~website_url~", "~site_url~"), array($Prebooking->cust_first_name, $Prebooking->cust_last_name, $this->frontendUrl, $this->site), $UserTemplete->source);
                    $Subject = $UserTemplete->subject .' request for '. $Prebooking->service_name;
                    Mail::to($Prebooking->email)->bcc($admin->email)->send(new \App\Mail\RegistrationMailUser($Message, $Subject));

                    $responce['status'] = 1;
                    $responce['message'] = 'Approved successfully.';
                } else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Unable to approve. Please try again.';
                }
            }
        }
        elseif ($request->request_type == 'get_ticket_details') {
            $TicketData = Ticket::find($request->tourId);
            if (!empty($TicketData)) {
                if ((date("Y-m-d", strtotime($request->checkinDate)) == date("Y-m-d")) && strtotime($TicketData->book_end_time) <= strtotime(date("H:i:s"))) {
                    $responce['status'] = 0;
                    $responce['message'] = 'No tickets available for this date. Please choose different date!';
                    echo json_encode($responce);
                    exit;
                }
                $TicketInventory = DB::table('ticket_inventory')->where(['vendor_id' => $TicketData->vendor_id, 'date' => date("Y-m-d", strtotime($request->checkinDate)), 'ticket_id' => $TicketData->id])->first();
                if (!empty($TicketInventory)) {
                    $TicketData->max_people = $TicketInventory->offline_quantity;
                }
                $OrderDetails = OrderMaster::select(DB::raw('SUM(total_guests) AS tot_booked'))
                            ->where('service_name_id', $TicketData->id)
                            ->where('start_date', date("Y-m-d", strtotime($request->checkinDate)))
                            ->where('order_type', 'offline')
                            ->where('service_type', 'ticketing')
                            ->where('status', '!=', 'cancelled')
                            ->first();
                if (!empty($OrderDetails)) {
                    $TicketData->max_people -= $OrderDetails->tot_booked;
                }
                if ($TicketData->max_people < 1) {
                    $responce['message'] = 'No tickets available for this date. Please choose different date!';
                    $responce['status'] = 0;
                    echo json_encode($responce);
                    exit;
                } else {
                    $responce['status'] = 1;
                    $responce['maxTicket'] = $TicketData->max_people;
                    $responce['adult_price'] = $TicketData->adult_price;
                    $responce['max_ticket_per_txn'] = $TicketData->max_ticket_per_txn;
                    $responce['child_price'] = $TicketData->child_price;
                }
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to delete data.';
            }
        }
        elseif ($request->request_type == 'get_invoice_html') {
            $OrderMaster = OrderMaster::find($request->orderID);
            if (!empty($OrderMaster) && !empty($OrderMaster->invoice)) {

                $file = 'documents/Invoice_'. $OrderMaster->invoice_id .'_'. time() . '.pdf';
                $pdfname = public_path($file);
                $final_html = parent::convert_image_base64($OrderMaster->invoice);
                PDF::loadHTML(html_entity_decode($final_html))->save($pdfname);
                // echo $this->site . $file;
                // exit;

                $responce['status'] = 1;
                $responce['data'] = $this->site . $file;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Invoice for this order is not available.';
            }
        }
        elseif ($request->request_type == 'get_confirm_html') {
            $OrderMaster = OrderMaster::find($request->orderID);
            if (!empty($OrderMaster) && !empty($OrderMaster->confimation_voucher)) {

                $file = 'documents/Confirm_voucher_'. $OrderMaster->invoice_id .'_'. time() . '.pdf';
                $pdfname = public_path($file);
                $final_html = parent::convert_image_base64($OrderMaster->confimation_voucher);
                PDF::loadHTML(html_entity_decode($final_html))->save($pdfname);
                // echo $this->site . $file;
                // exit;

                $responce['status'] = 1;
                $responce['data'] = $this->site . $file;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Confirmation voucher for this order is not available.';
            }
        }
        elseif ($request->request_type == 'get_ticket_date') {
            $TicketData = Ticket::find($request->serviceId);
            if (!empty($TicketData)) {
                $start_date = $TicketData->start_date;
                $end_date = $TicketData->end_date;
                
                $responce['status'] = 1;
                $responce['start_date'] = $start_date;
                $responce['end_date'] = $end_date;
            } else {
                $responce['status'] = 0;
                $responce['message'] = 'Unable to delete data.';
            }
        }
        elseif ($request->request_type == 'export_ticket_booking_request') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/Ticket_booking_request_" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Request Date', 'Application No', 'Ticket Name', 'Customer Name', 'Customer Mobile no', 'Customer Email', 'Gender', 'Adult', 'Child', 'Country', 'State', 'City', 'Type of Experience', 'Experience Description', 'Publication', 'Social Media Handle', 'Social Media URL', 'Identity Type', 'Identity Files', 'Approval Status');
            
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    $idArr = json_decode($value->identity_file, 1);
                    $identities = array_map(function($val) { return $this->site . $val; } , $idArr);
                    
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));
                    $data['application_no'] = $value->application_no;
                    $data['service_name'] = $value->service_name;
                    $data['cust_name'] = $value->cust_first_name .' '. $value->cust_last_name;
                    $data['phone'] = $value->phone;
                    $data['email'] = $value->email;
                    $data['gender'] = $value->gender;
                    $data['adult'] = $value->adult;
                    $data['child'] = $value->child;
                    $data['customer_country'] = $value->customer_country;
                    $data['customer_state'] = $value->customer_state;
                    $data['customer_city'] = $value->customer_city;
                    
                    $data['type_of_experience'] = $value->type_of_experience .' - '. $value->year_of_experience .' year(s)';
                    $data['description_experience'] = $value->description_experience;
                    $data['publication'] = $value->publication;
                    $data['social_media_handle'] = $value->social_media_handle;
                    $data['social_media_handle_url'] = $value->social_media_handle_url;
                    $data['identity_type'] = $value->identity_type;
                    $data['identity_file'] = implode(' , ', $identities);
                    $data['approve_status'] = ($value->approve_status == 0) ? 'Requested' : 'Approved';

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }
        elseif ($request->request_type == 'export_customer_interests') {
            $OrderMaster = DB::select($request->exportQuery);
            $csv = "documents/Birdswalk_registration_" . time() . ".csv";
            $csvname = public_path($csv);

            $headerArr = array('Request Date', 'Application No', 'Ticket Name', 'Customer Name', 'Customer Mobile no', 'Customer Email', 'Gender', 'Country', 'State', 'City');
            
            $fp = fopen($csvname, 'w');
            fputcsv($fp, $headerArr);
            if (!empty($OrderMaster)) {
                foreach ($OrderMaster as $value) {
                    
                    $data['created_at'] = date("Y-m-d", strtotime($value->created_at));
                    $data['application_no'] = $value->application_no;
                    $data['service_name'] = $value->service_name;
                    $data['cust_name'] = $value->cust_first_name .' '. $value->cust_last_name;
                    $data['phone'] = $value->phone;
                    $data['email'] = $value->email;
                    $data['gender'] = $value->cust_gender;
                    $data['customer_country'] = $value->cust_country;
                    $data['customer_state'] = $value->cust_state;
                    $data['customer_city'] = $value->cust_city;

                    fputcsv($fp, $data);
                }
            }
            fclose($fp);
            return response()->download($csvname)->deleteFileAfterSend(true);
        }
        echo json_encode($responce);
        exit;
    }

    public function addTicket() {
        if (!(parent::checkWritePrivilege(24))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $Vendors = User::where('role', '2')->pluck('company', 'id');
        $Attributes = ServiceAttribute::where('service', 'ticket')->pluck('name', 'id');
        $CarAttributes = array();
        foreach ($Attributes as $key => $value) {
            $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
            $CarAttributes[$value] = $AttributeValue;
        }
        $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();
        $Days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

        $SubUser = array();
        if (Auth::user()->role == 2) {
            $SubUserData = User::where('vendor_id', Auth::user()->id)->Where('access_type', '<>', 'agent')->orderBy('first_name', 'asc')->get();
            if (!empty($SubUserData)) {
                foreach ($SubUserData as $value) {
                    $SubUser[$value->id] = $value->first_name . ' ' . $value->last_name;
                }
            }
        }

        return view('ticketing.add-ticket', compact('Vendors', 'CarAttributes', 'CityDetail', 'Days', 'SubUser'));
    }

    public function ticketAddRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required',
                    'name' => 'required|string|min:3|max:100',
                    'content' => 'required|string',
                    'feature_image' => 'required|mimes:jpeg,png,jpg',
                    'banner_image' => 'required|mimes:jpeg,png,jpg',
                    'images.*' => 'required|mimes:jpeg,png,jpg',
                    'address' => 'string',
                    'category' => 'required|string',
                    'booking_mode' => 'string',
                    'city' => 'required|string',
                    'place' => 'required|string',
                    'status' => 'required|string',
                    'adult_price' => 'required|numeric',
                    'child_price' => 'required|numeric',
                    'max_ticket_per_txn' => 'required|numeric',
                    'max_ticket_per_user_per_day' => 'required|numeric',
                    'terms_conditions' => 'required|string',
                    'contact_number' => 'required|digits:10',
                    'contact_email' => 'required|email',
                    'show_price' => 'required',
                    'days_from_start_date' => 'numeric',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('ticket-add')->withErrors($validate)->withInput();
        } else {
            $UploadDir = 'images/ticketing/';
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
            $slots = '';
            if ($request->slots) {
                $data = array();
                $count = 0;
                foreach ($request->slots as $key => $value) {
                    $data[$count]['from_time'] = date("h:i a", strtotime($value['from_time']));
                    $data[$count]['to_time'] = date("h:i a", strtotime($value['to_time']));
                    $data[$count]['max_ticket'] = $value['max_ticket'];
                    $data[$count]['max_ticket_offline'] = $value['max_ticket_offline'];
                    $count++;
                }
                $slots = json_encode($data);
            }
            $slug = str_replace(' ', '-', trim(strtolower($request->name)));
            $AttributeValues = AttributeValue::pluck('icon', 'id');
            $Property = $request->property;
            $property_slug_array = array();
            if (!empty($Property)) {
                foreach ($Property as $key1 => $attribute) {
                    foreach ($attribute as $key2 => $terms) {
                        $temp = explode('~', $terms);
                        $Property[$key1][$key2] = array('name' => $temp[1], 'icon' => $AttributeValues[$temp[0]]);
                        $property_slug_array[] = $temp[1];
                    }
                }
            }
            $start_date = $end_date = null;
            if ($request->category == 'Events') {
                $start_date = date("Y-m-d", strtotime($request->start_date));
                $end_date = date("Y-m-d", strtotime($request->end_date));
            }
            $start_time = $end_time = '';
            $max_pepole = $max_pepole_offline = 0;
            if ($request->ticket_type == 'Full Day Booking') {
                $start_time = date("h:i a", strtotime($request->start_time));
                $end_time = date("h:i a", strtotime($request->end_time));
                $max_pepole = $request->max_people;
                $max_pepole_offline = $request->max_people_offline;
            }
            $faqs = '';
            if ($request->faqs) {
                $data = array();
                foreach ($request->faqs as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $faqs = json_encode($data);
            }
            $services = '';
            if ($request->extra_services) {
                $data = array();
                foreach ($request->extra_services as $value) {
                    $data[] = array(
                        'name' => $value['title'],
                        'price' => $value['price'],
                        'quantity' => 0,
                        'maxquantity' => $value['quantity'],
                    );
                }
                $services = json_encode($data);
            }
            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) { return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) { return !empty(trim($n)); });
            
            $Ticketing = new Ticket([
                'vendor_id' => $request->vendor_id,
                'name' => trim($request->name),
                'slug' => $slug,
                'content' => addslashes($request->content),
                'feature_image' => $UploadDir . $feature_image,
                'banner_image' => $UploadDir . $banner_image,
                'gallery' => json_encode($gallery_images),
                'category' => $request->category,
                'booking_mode' => (!empty($request->booking_mode)) ? $request->booking_mode : 'sharing',
                'city' => $request->city,
                'place' => $request->place,
                'address' => addslashes($request->address),
                'map_lat' => $request->map_lat,
                'map_lng' => $request->map_lng,
                'video' => $request->video,
                'adult_price' => $request->adult_price,
                'child_price' => $request->child_price,
                'ticket_type' => $request->ticket_type,
                'slots' => $slots,                
                'start_date' => $start_date,
                'end_date' => $end_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'max_people' => $max_pepole,
                'max_people_offline' => $max_pepole_offline,
                'not_available' => !empty($request->not_available) ? json_encode($request->not_available) : json_encode([]),
                'max_ticket_per_txn' => $request->max_ticket_per_txn,
                'max_ticket_per_user_per_day' => $request->max_ticket_per_user_per_day,
                'faqs' => $faqs,
                'extra_services' => $services,
                'property' => (!empty($Property)) ? json_encode($Property) : '',
                'property_slug' => implode("~", $property_slug_array),
                'status' => $request->status,
                'create_user' => Auth::user()->id, 
                'terms_conditions' => $request->terms_conditions,
                'contact_email' => $request->contact_email,
                'contact_number' => $request->contact_number,
                'additional_email' => implode(",", $add_email),
                'additional_phone' => implode(",", $add_phone),
                'gst_applicable' => $request->gst_applicable,
                'show_price' => $request->show_price,
                'gst_number' => $request->gst_number,
                'gst_legal_name' => $request->gst_legal_name,
                // 'paytm_mid' => $request->paytm_mid,
                // 'hdfc_mid' => $request->hdfc_mid,
                'book_start_date' => (!empty($request->book_start_date)) ? date("Y-m-d", strtotime($request->book_start_date)) : '',
                'book_end_time' => date("h:i a", strtotime($request->book_end_time)),
                'days_from_start_date' => (!empty($request->days_from_start_date)) ? $request->days_from_start_date : 1
            ]);
            if ($Ticketing->save()) {
                $TicketingId = $Ticketing->id;
                if (Auth::user()->role == 2) {
                    if (!empty($request->sub_user)) {
                        $userAccess = array();
                        foreach ($request->sub_user as $value) {
                            $userAccess[] = array(
                                'user_id' => $value,
                                'service' => 'ticketing',
                                'service_id' => $TicketingId
                            );
                        }
                        DB::table('subuser_access')->insertOrIgnore($userAccess);
                    }
                }
                Session::flash('success', $request->category . ' added successful.');
                return Redirect::to('all-tickets');
            } else {
                Session::flash('success', 'Unable to add data');
                return Redirect::to('ticket-add');
            }
        }
    }

    public function editTicketing($id = null) {
        if (!(parent::checkWritePrivilege(24))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $TicketDetailsQry = Ticket::where('id', $id);
        if (Auth::user()->access_type == 'vendor') {
            $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $TicketDetailsQry->where('vendor_id', $vendor_id);
        }
        $TicketDetails = $TicketDetailsQry->first();
        if (!empty($TicketDetails)) {
            $TicketDetails->feature_image = $this->site . $TicketDetails->feature_image;
            $TicketDetails->banner_image = $this->site . $TicketDetails->banner_image;
            $TicketDetails->gallery = json_decode($TicketDetails->gallery);
            $property = (!empty($TicketDetails->property)) ? json_decode($TicketDetails->property, 1) : [];
            $data = array();
            if (!empty($property)) {
                foreach ($property as $key1 => $attribute) {
                    foreach ($attribute as $key2 => $terms) {
                        $data[$key1][$key2] = $terms['name'];
                    }
                }
            }
            $TicketDetails->property = $data;
            $TicketDetails->faqs = !empty($TicketDetails->faqs) ? json_decode($TicketDetails->faqs, 1) : [];
            $TicketDetails->extra_services = !empty($TicketDetails->extra_services) ? json_decode($TicketDetails->extra_services, 1) : [];        
            $TicketDetails->start_time = date("H:i", strtotime($TicketDetails->start_time));
            $TicketDetails->end_time = date("H:i", strtotime($TicketDetails->end_time));
            $TicketDetails->book_end_time = date("H:i", strtotime($TicketDetails->book_end_time));
            $TicketDetails->book_start_date = !empty($TicketDetails->book_start_date) ? date("d-m-Y", strtotime($TicketDetails->book_start_date)) : '';
            $slots = array();
            if (!empty($TicketDetails->slots)) {
                $slots = json_decode($TicketDetails->slots, 1);
                foreach ($slots as $key => $value) {
                    $slots[$key]['from_time'] = date("H:i", strtotime($value['from_time']));
                    $slots[$key]['to_time'] = date("H:i", strtotime($value['to_time']));
                }
                $TicketDetails->slots = $slots;
            } else {
                $TicketDetails->slots = [];
            }
            $gallery = array();
            $count = 1;
            foreach ($TicketDetails->gallery as $value) {
                $gallery[] = ['id' => $count, 'src' => $this->site . $value];
                $count++;
            }
            $gallery = json_encode($gallery);

            $Vendors = User::where('role', '2')->pluck('company', 'id');
            $Attributes = ServiceAttribute::where('service', 'ticket')->pluck('name', 'id');
            $CarAttributes = array();
            foreach ($Attributes as $key => $value) {
                $AttributeValue = AttributeValue::where('attr_id', $key)->pluck('name', 'id')->toArray();
                $CarAttributes[$value] = $AttributeValue;
            }
            $CityDetail = City::where(['state_id' => Auth::user()->state])->pluck('name', 'id')->toArray();
            $TicketDetails->not_available = !empty($TicketDetails->not_available) ? json_decode($TicketDetails->not_available, 1) : [];
            $Days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

            $SubUser = array();
            if (Auth::user()->role == 2) {
                $SubUserData = User::where('vendor_id', Auth::user()->id)->Where('access_type', '<>', 'agent')->orderBy('first_name', 'asc')->get();
                if (!empty($SubUserData)) {
                    foreach ($SubUserData as $value) {
                        $checked = 0;
                        $SubuserAccess = SubuserAccess::where(['user_id' => $value->id, 'service' => 'ticketing', 'service_id' => $id])->first();
                        if (!empty($SubuserAccess)) {
                            $checked = 1;
                        }
                        $SubUser[] = array('id' => $value->id, 'name' => $value->first_name . ' ' . $value->last_name, 'checked' => $checked);
                    }
                }
            }

            return view('ticketing.edit-ticketing', compact('Vendors', 'CarAttributes', 'CityDetail', 'TicketDetails', 'gallery', 'Days', 'SubUser'));
        } else {
            return redirect()->back();
        }
    }

    public function ticketEditRequest(Request $request) {
        $validate = Validator::make($request->all(), [
                    'vendor_id' => 'required',
                    'name' => 'required|string|min:3|max:100',
                    'content' => 'required|string',
                    'feature_image' => 'mimes:jpeg,png,jpg',
                    'banner_image' => 'mimes:jpeg,png,jpg',
                    'images.*' => 'mimes:jpeg,png,jpg',
                    'address' => 'string',
                    'category' => 'required|string',
                    'booking_mode' => 'string',
                    'city' => 'required|string',
                    'place' => 'required|string',
                    'status' => 'required|string',
                    'adult_price' => 'required|numeric',
                    'child_price' => 'required|numeric',
                    'max_ticket_per_txn' => 'required|numeric',
                    'max_ticket_per_user_per_day' => 'required|numeric',
                    'terms_conditions' => 'required|string',
                    'contact_number' => 'required|digits:10',
                    'contact_email' => 'required|email',
                    'show_price' => 'required',
                    'days_from_start_date' => 'numeric',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('ticketing-edit/' . $request->id)->withErrors($validate)->withInput();
        } else {
            $slug = str_replace(' ', '-', trim(strtolower($request->name)));

            $Ticketing = Ticket::find($request->id);
            $Ticketing->vendor_id = $request->vendor_id;
            $Ticketing->name = trim($request->name);
            $Ticketing->slug = $slug;
            $Ticketing->content = addslashes($request->content);
            $Ticketing->category = $request->category;
            $Ticketing->booking_mode = (!empty($request->booking_mode)) ? $request->booking_mode : 'sharing';
            $Ticketing->city = $request->city;
            $Ticketing->place = $request->place;
            $Ticketing->address = addslashes($request->address);
            $Ticketing->map_lat = $request->map_lat;
            $Ticketing->map_lng = $request->map_lng;
            $Ticketing->video = $request->video;
            $Ticketing->adult_price = $request->adult_price;
            $Ticketing->child_price = $request->child_price;
            $Ticketing->ticket_type = $request->ticket_type;
            $Ticketing->status = $request->status;
            $Ticketing->update_user = Auth::user()->id;
            $Ticketing->max_ticket_per_txn = $request->max_ticket_per_txn;
            $Ticketing->max_ticket_per_user_per_day = $request->max_ticket_per_user_per_day;
            $Ticketing->terms_conditions = $request->terms_conditions;
            $Ticketing->contact_email = $request->contact_email;
            $Ticketing->contact_number = $request->contact_number;
            $Ticketing->gst_applicable = $request->gst_applicable;
            $Ticketing->not_available = !empty($request->not_available) ? json_encode($request->not_available) : json_encode([]);
            $Ticketing->show_price = $request->show_price;
            $Ticketing->gst_number = $request->gst_number;
            $Ticketing->gst_legal_name = $request->gst_legal_name;
            $Ticketing->paytm_mid = $request->paytm_mid;
            $Ticketing->hdfc_mid = $request->hdfc_mid;
            $Ticketing->book_start_date = (!empty($request->book_start_date)) ? date("Y-m-d", strtotime($request->book_start_date)) : '';
            $Ticketing->book_end_time = date("h:i a", strtotime($request->book_end_time));
            $Ticketing->days_from_start_date = (!empty($request->days_from_start_date)) ? $request->days_from_start_date : 1;

            $add_email = $arr = explode(",", $request->additional_email);
            $add_email = array_filter($add_email, function ($n) { return !empty(trim($n)); });

            $add_phone = $arr = explode(",", $request->additional_phone);
            $add_phone = array_filter($add_phone, function ($n) { return !empty(trim($n)); });

            $Ticketing->additional_email = implode(',', $add_email);
            $Ticketing->additional_phone = implode(',', $add_phone);

            if ($request->category == 'Events') {
                $Ticketing->start_date = date("Y-m-d", strtotime($request->start_date));
                $Ticketing->end_date = date("Y-m-d", strtotime($request->end_date));
            }

            $UploadDir = 'images/ticketing/';
            $gallery_images = array();
            if ($request->hasFile('banner_image')) {
                if ($request->file('banner_image')->isValid()) {
                    $old_banner = public_path($Ticketing->banner_image);
                    if (file_exists($old_banner)) {
                        unlink($old_banner);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('banner_image')->getClientOriginalName());
                    $banner_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->banner_image->extension();
                    $request->banner_image->move(public_path($UploadDir), $banner_image);
                    $Ticketing->banner_image = $UploadDir . $banner_image;
                }
            }
            if ($request->hasFile('feature_image')) {
                if ($request->file('feature_image')->isValid()) {
                    $old_feature = public_path($Ticketing->feature_image);
                    if (file_exists($old_feature)) {
                        unlink($old_feature);
                    }
                    $filenameWithExt = str_replace(' ', '-', $request->file('feature_image')->getClientOriginalName());
                    $feature_image = pathinfo($filenameWithExt, PATHINFO_FILENAME) . '_' . time() . '.' . $request->feature_image->extension();
                    $request->feature_image->move(public_path($UploadDir), $feature_image);
                    $Ticketing->feature_image = $UploadDir . $feature_image;
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
            $old_gallery = !empty($Ticketing->gallery) ? json_decode($Ticketing->gallery) : [];
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
            $Ticketing->gallery = json_encode($gallery_images);

            if ($request->ticket_type == 'Slot Booking') {
                $data = array();
                $count = 0;
                foreach ($request->slots as $key => $value) {
                    $data[$count]['from_time'] = date("h:i a", strtotime($value['from_time']));
                    $data[$count]['to_time'] = date("h:i a", strtotime($value['to_time']));
                    $data[$count]['max_ticket'] = $value['max_ticket'];
                    $data[$count]['max_ticket_offline'] = $value['max_ticket_offline'];
                    $count++;
                }
                $Ticketing->slots = json_encode($data);
            } elseif ($request->ticket_type == 'Full Day Booking') {
                $Ticketing->start_time = date("h:i a", strtotime($request->start_time));
                $Ticketing->end_time = date("h:i a", strtotime($request->end_time));
                $Ticketing->max_people = $request->max_people;
                $Ticketing->max_people_offline = $request->max_people_offline;
                $Ticketing->slots = '';
            }
            $Ticketing->faqs = '';
            if ($request->faqs) {
                $data = array();
                foreach ($request->faqs as $value) {
                    $data[$value['title']] = $value['content'];
                }
                $Ticketing->faqs = json_encode($data);
            }
            $Ticketing->extra_services = '';
            if ($request->extra_services) {
                $data = array();
                foreach ($request->extra_services as $value) {
                    $data[] = array(
                        'name' => $value['title'],
                        'price' => $value['price'],
                        'quantity' => 0,
                        'maxquantity' => $value['quantity'],
                    );
                }
                $Ticketing->extra_services = json_encode($data);
            }

            $AttributeValues = AttributeValue::pluck('icon', 'id');
            $Property = $request->property;
            $property_slug_array = array();
            if (!empty($Property)) {
                foreach ($Property as $key1 => $attribute) {
                    foreach ($attribute as $key2 => $terms) {
                        $temp = explode('~', $terms);
                        $Property[$key1][$key2] = array('name' => $temp[1], 'icon' => $AttributeValues[$temp[0]]);
                        $property_slug_array[] = $temp[1];
                    }
                }
            }
            $Ticketing->property_slug = implode("~", $property_slug_array);
            $Ticketing->property = json_encode($Property);
            if ($Ticketing->save()) {
                if (Auth::user()->role == 2) {
                    DB::table('subuser_access')->where(['service' => 'ticketing', 'service_id' => $Ticketing->id])->delete();
                    if (!empty($request->sub_user)) {
                        $userAccess = array();
                        foreach ($request->sub_user as $value) {
                            $userAccess[] = array(
                                'user_id' => $value,
                                'service' => 'ticketing',
                                'service_id' => $Ticketing->id
                            );
                        }
                        DB::table('subuser_access')->insertOrIgnore($userAccess);
                    }
                }

                Session::flash('success', 'Ticketing details updated successful.');
                return Redirect::to('all-tickets');
            } else {
                Session::flash('success', 'Unable to update Ticketing details!');
                return Redirect::to('ticketing-edit/' . $request->id);
            }
        }
    }

    public function ticketAttribute() {
        if (!(parent::checkViewPrivilege(26))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $TicketAttributes = ServiceAttribute::where('service', 'ticket')->get();

        return view('ticketing.ticket-attributes', compact('TicketAttributes'));
    }

    public function ticketAttributeAddRequest(Request $request) {
        if (!(parent::checkWritePrivilege(26))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $validate = Validator::make($request->all(), [
                    'name' => 'required|string',
//                    'status' => 'required|string'
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('ticket-attribute')->withErrors($validate)->withInput();
        } else {
            $CarAttribute = new ServiceAttribute([
                'name' => $request->name,
                'service' => 'ticket',
//                'status' => $request->status
            ]);
            if ($CarAttribute->save()) {
                Session::flash('success', 'Ticketing attribute added successful.');
                return Redirect::to('ticket-attribute');
            } else {
                Session::flash('success', 'Unable to add attribute');
                return Redirect::to('ticket-attribute');
            }
        }
    }

    public function ticketAttributeTerm($id = null) {
        if (!(parent::checkViewPrivilege(26))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $ServiceAttribute = ServiceAttribute::find($id);
        $AttributeTerms = AttributeValue::where('attr_id', $id)->get();
        $site_url = $this->site;

        return view('ticketing.attribute-terms', compact('ServiceAttribute', 'AttributeTerms', 'site_url'));
    }

    public function ticketAttributeTermAddRequest(Request $request) {
        if (!(parent::checkWritePrivilege(26))) {
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
            return Redirect::to('ticket-attribute-terms/' . $request->attr_id)->withErrors($validate)->withInput();
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
                'attr_id' => $request->attr_id,
//                'status' => $request->status,
                'icon' => $icon_image
            ]);
            if ($AttributeTerm->save()) {
                Session::flash('success', 'Attribute term added successful.');
                return Redirect::to('ticket-attribute-terms/' . $request->attr_id);
            } else {
                Session::flash('success', 'Unable to add attribute term');
                return Redirect::to('ticket-attribute-terms/' . $request->attr_id);
            }
        }
    }
    
    public function ticketBlockData() {
        if (!(parent::checkViewPrivilege(65))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        return view('ticketing.ticket-block-data');
    }

    public function getTicketBlockData(Request $request) {

        $aColumns = array('ticket_name', 'block_date', 'block_reason', 'status', 'id');
        $sIndexColumn = "id";
        $sTable = "ticket_availability";
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
        $vender_condition = '`vendor_id` = ' . $vender_id; 
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
            $staff_condition = '';
            if (!empty($SubuserAccess)) {
                $staff_condition = ' AND id in (' . implode(',', $SubuserAccess) . ')';
            }
            $vendor_condtition .= $staff_condition;
        }
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

            $status = ($aRow->status == '1') ? 'Activate' : 'Deactivate';
            $row[] = $aRow->ticket_name;
            $row[] = $aRow->block_reason;
            $row[] = date("M d Y", strtotime($aRow->block_date));
            $row[] = ($aRow->status == '1') ? '<span style="text-transform: capitalize;font-size: 12px;color: #fff;background-color: #717171;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;">Blocked</span>' : '<span style="background-color: #28a745;font-size: 12px;font-weight: 700;border-radius: 0.25rem;padding: 0.25em 0.4em;color: #fff;text-transform: capitalize;">Available</span>';
            $row[] = '<div class="btn-group">
                <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
                <ul role="menu" class="dropdown-menu">                    
                    <li><a href="javascript:void(0);" class="delete-data" data-id="' . $aRow->id . '">Delete</a></li>
                </ul>
            </div>';
//            <li><a href="javascript:void(0);" class="change-status" data-id="'. $aRow->id .'" data-status="'. $aRow->status .'">'. $status .'</a></li>
            $output['data'][] = $row;
        }

        echo json_encode($output);
        exit;
    }

    public function blockTicket() {
        if (!(parent::checkWritePrivilege(65))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TicketQry = Ticket::where('status', 'publish')->where('vendor_id', $vender_id);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TicketQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Ticket = $TicketQry->orderBy('name', 'ASC')->pluck('name', 'id');

        return view('ticketing.block-ticket', compact('Ticket'));
    }

    public function blockTicketRequest(Request $request) {
        $validate = Validator::make($request->all(), [
            'ticket_id' => 'required|numeric',
            'block_date' => 'required|string',
        ]);
        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('block-ticket')->withErrors($validate)->withInput();
        } else {
            $Ticket = Ticket::find($request->ticket_id);
            $block_date = explode(' - ', $request->block_date);
            
            $difference = strtotime(date("Y-m-d", strtotime($block_date[1]))) - strtotime(date("Y-m-d", strtotime($block_date[0])));
            $days = round($difference / (60 * 60 * 24));
            $AvailabilityData = array();
            for ($i = 0; $i <= $days; $i++) {
                $date = date("Y-m-d", strtotime($block_date[0] . ' + ' . $i . ' days'));
                $AvailabilityData = [
                    'vendor_id' => $Ticket->vendor_id,
                    'ticket_id' => $request->ticket_id,
                    'ticket_name' => $Ticket->name,
                    'block_date' => $date,
                    'block_reason' => addslashes($request->block_reason),
                    'created_by' => Auth::user()->id,
                    'status' => 1
                ];
                DB::table('ticket_availability')->insertOrIgnore($AvailabilityData);
            }
            Session::flash('success', 'Ticketing blocked successful.');
            return Redirect::to('ticket-block-data');
        }
    }
    
    public function ticketBooking() {
        if (!(parent::checkViewPrivilege(66))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $ticket_id = ''; 
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime('+1 days'));
        $OrderData = array();
        if (isset($_POST['ticket_id']) && isset($_POST['check_date'])) {
            $ticket_id = $_POST['ticket_id'];
            $check_date = explode(" - ", $_POST['check_date']);
            $start_date = date("Y-m-d", strtotime($check_date[0]));
            $end_date = date("Y-m-d", strtotime($check_date[1]));
            $difference = strtotime($end_date) - strtotime($start_date);
            $days = floor($difference / (60 * 60 * 24));
            for($i = 0; $i <= $days; $i++) {
                $checkDate = date("Y-m-d", strtotime($start_date .' + '. $i .' days'));
                $CompleteData = DB::table('order_masters')->select(DB::raw('SUM(total_guests) as totQty'))
                        ->where(['service_type' => 'ticketing', 'service_name_id' => $ticket_id, 'start_date' => $checkDate, 'status' => 'completed', 'payment_status' => 'success'])
                        ->first();
                $PendingData = DB::table('order_masters')->select(DB::raw('SUM(total_guests) as totQty'))
                        ->where(['service_type' => 'ticketing', 'service_name_id' => $ticket_id, 'start_date' => $checkDate, 'status' => 'pending'])
                        ->first();
                $CancelData = DB::table('order_masters')->select(DB::raw('SUM(total_guests) as totQty'))
                        ->where(['service_type' => 'ticketing', 'service_name_id' => $ticket_id, 'start_date' => $checkDate, 'status' => 'cancelled', 'payment_status' => 'success'])
                        ->first();
                
                $OrderData[] = array(
                    'date' => $checkDate,
                    'completeQty' => !is_null($CompleteData->totQty) ? $CompleteData->totQty : 0,
                    'pendingQty' => !is_null($PendingData->totQty) ? $PendingData->totQty : 0,
                    'cancelQty' => !is_null($CancelData->totQty) ? $CancelData->totQty : 0
                );
            }            
        }
        $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        // $Ticket = Ticket::where('status', 'publish')->where('vendor_id', $vender_id)->orderBy('name', 'ASC')->pluck('name', 'id');
        $TicketQry = Ticket::where('status', 'publish')->where('vendor_id', $vender_id);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TicketQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Ticket = $TicketQry->orderBy('name', 'ASC')->pluck('name', 'id');

        return view('ticketing.ticket-booking', compact('Ticket', 'ticket_id', 'start_date', 'end_date', 'OrderData'));
    }

    public function ticketBookingReport(Request $request)
    {
        if (!(parent::checkViewPrivilege(93))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $TicketQuery = DB::table('tickets');// Ticket::where('status', 'publish');
        if (Auth::user()->access_type == 'vendor') {
            $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
            $TicketQuery->where('vendor_id', $vender_id);
            if ((Auth::user()->role == 3)) {
                $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
                if (!empty($SubuserAccess)) {
                    $TicketQuery->whereIn('id', array_values($SubuserAccess));
                }
            }
        }
        $Ticket = $TicketQuery->pluck('name', 'id')->toArray();
        $report_type = 'book_date';
        $MisTicketData = array();
        $check_date = date("Y-m-d");
        $TicketId = 0;
        if (isset($_GET['check_date']) && isset($_GET['report_type']) && isset($_GET['ticket_id'])) {
            $TicketId = $_GET['ticket_id'];
            
            $TicketList = array();
            if ($TicketId != 0 && isset($Ticket[$TicketId])) {
                $TicketList[$TicketId] = $Ticket[$TicketId];
            } else {
                $TicketList = $Ticket;
            }
            $check_date = date("Y-m-d", strtotime($_GET['check_date']));
            if ($_GET['report_type'] == 'book_date') {
                $report_type = 'book_date';
            
                $Orders = OrderMaster::where(['service_type' => 'ticketing', 'payment_status' => 'success'])
                            ->whereIn('service_name_id', array_keys($TicketList))
                            ->where('created_at', 'LIKE', $check_date .'%')
                            ->where('status', '!=', 'partially-cancelled')
                            ->orderBy('created_at', 'DESC')
                            ->get();
                if (!empty($Orders->toArray())) {
                    foreach ($Orders as $key => $value) {
                        $status = ($value->status == 'cancelled') ? 'Cancelled' : 'Confirmed';

                        $MisTicketData[] = array(
                            'invoice_id' => $value->invoice_id,
                            'invoice_slno' => !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A',
                            'oderID' => $value->id,
                            'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                            'guest_name' => $value->customer_name,
                            'guest_phone' => $value->customer_phone,
                            'guest_email' => $value->customer_email,
                            'unit_name' => $value->service_name,
                            'check_in' => date("d-M-Y", strtotime($value->start_date)),
                            'time' => $value->start_time .' - '. $value->end_time,
                            'total_amount' => $value->total_order_price,
                            'order_type' => $value->order_type,
                            'status' => $status,
                            'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                        );
                    }
                }
            } elseif ($_GET['report_type'] == 'stay_date') {
                $report_type = 'stay_date';                
                $Orders = OrderMaster::where(['status' => 'completed'])
                            ->where('service_type', 'ticketing')
                            ->whereIn('service_name_id', array_keys($TicketList))
                            ->where('start_date', $check_date)
                            ->get();
                if (!empty($Orders->toArray())) {
                    foreach ($Orders as $key => $value) {
                        $hotelId = $hotelName = $rooms = $start_date = $end_date = '';$night = 0;
                        
                        $start_date = date("d-M-Y", strtotime($value->start_date));
                        $time = $value->start_time .' - '. $value->end_time;
                        $hotelId = $value->service_name_id;
                        $hotelName = $value->service_name;
                        
                        

                        $MisTicketData[$hotelId]['data'][] = array(
                            'invoice_id' => $value->invoice_id,
                            'oderID' => $value->id,
                            'invoice_slno' => !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A',
                            'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                            'guest_name' => $value->customer_name,
                            'guest_phone' => $value->customer_phone,
                            'guest_email' => $value->customer_email,
                            'unit_name' => $hotelName,
                            'check_in' => $start_date,
                            'time' => $time,
                            'occupancy' => $value->total_guests,
                            'total_amount' => $value->total_order_price,
                            'order_type' => $value->order_type,
                        );
                        $MisTicketData[$hotelId]['name'] = $hotelName;
                        $MisTicketData[$hotelId]['total_book'] = (isset($MisTicketData[$hotelId]['total_book'])) ? $MisTicketData[$hotelId]['total_book'] + 1 : 1;
                        $MisTicketData[$hotelId]['total_occupancy'] = (isset($MisTicketData[$hotelId]['total_occupancy'])) ? $MisTicketData[$hotelId]['total_occupancy'] + $value->total_guests : $value->total_guests;
                    }
                }
            }
        } else {
            $Orders = OrderMaster::where(['service_type' => 'ticketing', 'payment_status' => 'success'])
                            ->whereIn('service_name_id', array_keys($Ticket))
                            ->where('created_at', 'LIKE', $check_date .'%')
                            ->where('status', '!=', 'partially-cancelled')
                            ->orderBy('created_at', 'DESC')
                            ->get();
            if (!empty($Orders->toArray())) {
                foreach ($Orders as $key => $value) {
                    $status = ($value->status == 'cancelled') ? 'Cancelled' : 'Confirmed';

                    $MisTicketData[] = array(
                        'invoice_id' => $value->invoice_id,
                        'invoice_slno' => !empty($value->invoice_serial) ? $value->invoice_serial : 'N/A',
                        'oderID' => $value->id,
                        'invoice' => $value->invoice,
                        'confirm_voucher' => $value->confimation_voucher,
                        'book_date' => date("d-M-Y h:i a", strtotime($value->created_at)),
                        'guest_name' => $value->customer_name,
                        'guest_phone' => $value->customer_phone,
                        'guest_email' => $value->customer_email,
                        'unit_name' => $value->service_name,
                        'check_in' => date("d-M-Y", strtotime($value->start_date)),
                        'time' => $value->start_time .' - '. $value->end_time,
                        'total_amount' => $value->total_order_price,
                        'order_type' => $value->order_type,
                        'status' => $status,
                        'cancel_date' => date("d-M-Y", strtotime($value->cancel_date))
                    );
                }
            }
        }
        // echo "<pre>";print_r($MisTicketData);exit;
        return view('ticketing.ticket-booking-report', compact('MisTicketData', 'check_date', 'report_type', 'Ticket', 'TicketId'));
    }

    public function ticketBookingRequest() {
        if (!(parent::checkViewPrivilege(24))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }

        return view('ticketing.ticket-booking-request');
    }

    public function getTicketBookingRequest(Request $request) {

        $aColumns = array('application_no', 'service_name', 'cust_first_name', 'adult', 'customer_city', 'type_of_experience', 'description_experience', 'publication', 'social_media_handle', 'social_media_handle_url', 'identity_type', 'identity_file', 'approve_status', 'created_at', 'year_of_experience', 'child', 'email', 'phone', 'cust_last_name', 'customer_country', 'customer_state', 'approve_status', 'book_status', 'modified_by', 'id');
        $sIndexColumn = "id";
        $sTable = "prebookings";
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
        // if (Auth::user()->access_type == 'vendor') {
        //     $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        //     $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        //     if ((Auth::user()->role == 3)) {
        //         $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
        //         $staff_condition = '';
        //         if (!empty($SubuserAccess)) {
        //             $staff_condition = ' AND id in (' . implode(',', $SubuserAccess) . ')';
        //         }
        //         $vendor_condtition .= $staff_condition;
        //     }
        // }
        $sWhere = 'WHERE created_at LIKE "'. date("Y") .'%" ' . $vendor_condtition;
        $searchColumns = array('name', 'category', 'city');
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
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
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
                        
            $cust_details = $aRow->cust_first_name .' '.  $aRow->cust_last_name .'<br>'. $aRow->phone .'<br>'. $aRow->email;
            $cust_addr = $aRow->customer_city .',<br>'.  $aRow->customer_state .',<br>'. $aRow->customer_country;
            $guest_data = ($aRow->child > 0) ? 'Adult: '. $aRow->adult .'<br>Child: '. $aRow->child : 'Adult: '. $aRow->adult;
            $approve = ($aRow->approve_status == 0) ? '<a href="javascript:void(0)" class="btn btn-primary approveBooking" data-id="'. $aRow->id .'">Approve</a>' : '<span class="text-success"><b>Approved</b><br>'. date("d-M-Y", strtotime($aRow->updated_at)) .'</span>';
            $aRow->identity_file = json_decode($aRow->identity_file, 1);
            $file_html = '';
            foreach ($aRow->identity_file as $key => $files) {
                $file_html .= '<a href="'. $this->site . $files .'" download>File'. ++$key .'</a><br>';
            }
            $row[] = $aRow->application_no;
            $row[] = $aRow->service_name;
            $row[] = $cust_details;
            $row[] = $guest_data;
            $row[] = $cust_addr;
            $row[] = $aRow->type_of_experience . ' - '. $aRow->year_of_experience .'Year(s)';
            $row[] = wordwrap($aRow->description_experience,50,"<br>\n");
            $row[] = $aRow->publication;
            $row[] = $aRow->social_media_handle;
            $row[] = $aRow->social_media_handle_url;
            $row[] = $aRow->identity_type;
            $row[] = $file_html;
            $row[] = $approve;
            $row[] = date("d-M-Y", strtotime($aRow->created_at));
            // $row[] = '<div class="btn-group">
            //     <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
            //     <ul role="menu" class="dropdown-menu">
            //         <li><a href="'. url('ticketing-edit', $aRow->id) .'">Edit</a></li>
            //         <li><a href="javascript:void(0)" class="deleteTicket" data-id="'. $aRow->id .'">Delete</a></li>
            //     </ul>
            // </div>';
            // $row[] = '<a href="' . url('ticketing-edit', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function customerInterest() {
        if (!(parent::checkViewPrivilege(24))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }

        return view('ticketing.customer-interest');
    }

    public function getCustomerInterest(Request $request) {

        $aColumns = array('application_no', 'service_name', 'cust_first_name', 'email', 'phone', 'cust_city', 'cust_state', 'cust_country', 'created_at', 'cust_last_name');
        $sIndexColumn = "id";
        $sTable = "customer_interests";
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
        // if (Auth::user()->access_type == 'vendor') {
        //     $vender_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        //     $vendor_condtition = ' AND vendor_id = ' . $vender_id;
        //     if ((Auth::user()->role == 3)) {
        //         $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticketing'])->pluck('service_id', 'id')->toArray();
        //         $staff_condition = '';
        //         if (!empty($SubuserAccess)) {
        //             $staff_condition = ' AND id in (' . implode(',', $SubuserAccess) . ')';
        //         }
        //         $vendor_condtition .= $staff_condition;
        //     }
        // }
        $sWhere = 'WHERE created_at LIKE "'. date("Y") .'%" ' . $vendor_condtition;
        $searchColumns = array('name', 'category', 'city');
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
        $exQuery = "SELECT * FROM $sTable $sWhere $sOrder";
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
                        
            $cust_details = $aRow->cust_first_name .' '.  $aRow->cust_last_name;            
            
            $row[] = $aRow->application_no;
            $row[] = $aRow->service_name;
            $row[] = $cust_details;
            $row[] = $aRow->email;
            $row[] = $aRow->phone;
            $row[] = $aRow->cust_city;
            $row[] = $aRow->cust_state;
            $row[] = $aRow->cust_country;
            $row[] = date("d-M-Y", strtotime($aRow->created_at));
            // $row[] = '<div class="btn-group">
            //     <button aria-expanded="false" data-toggle="dropdown" class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light" type="button">Action <span class="caret"></span></button>
            //     <ul role="menu" class="dropdown-menu">
            //         <li><a href="'. url('ticketing-edit', $aRow->id) .'">Edit</a></li>
            //         <li><a href="javascript:void(0)" class="deleteTicket" data-id="'. $aRow->id .'">Delete</a></li>
            //     </ul>
            // </div>';
            // $row[] = '<a href="' . url('ticketing-edit', $aRow->id) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $output['data'][] = $row;
        }
        $output['exportQuery'] = $exQuery;

        echo json_encode($output);
        exit;
    }

    public function ticketOfflineOrder() {
        if (!(parent::checkViewPrivilege(77))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }
        $CountryData = Country::pluck('name', 'id');
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TicketQry = Ticket::where(['status' => 'publish', 'vendor_id' => $vendor_id]);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticket'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TicketQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Ticket = $TicketQry->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $site = $this->site;
        $orderId = '';
        return view('ticketing.ticket-offline-order', compact('Ticket', 'CountryData', 'site', 'orderId'));
    }

    public function createTicketOrder(Request $request) {
        if (!(parent::checkWritePrivilege(77))) {
            Session::flash('success', 'You are not autherised to do this operation.');
            return redirect()->back();
        }
        // echo "<pre>";print_r($request->all());exit;
        
        $TicketData = Ticket::find($request->service_name_id);
        $total_guests = $request->total_adults;

        if ((date("Y-m-d", strtotime($request->start_date)) == date("Y-m-d")) && strtotime($TicketData->book_end_time) <= strtotime(date('H:i:s'))) {
            Session::flash('success', 'Sorry!, No tickets available for this date. Please choose different date.');
            return Redirect::to('ticket-offline-order');
        }
        $TicketInventory = DB::table('ticket_inventory')->where(['vendor_id' => $TicketData->vendor_id, 'date' => date("Y-m-d", strtotime($request->start_date)), 'ticket_id' => $TicketData->id])->first();
        if (!empty($TicketInventory)) {
            $TicketData->max_people = $TicketInventory->offline_quantity;
        }
        $OrderDetails = OrderMaster::select(DB::raw('SUM(total_guests) AS tot_booked'))
                    ->where('service_name_id', $TicketData->id)
                    ->where('start_date', date("Y-m-d", strtotime($request->start_date)))
                    ->where('order_type', 'offline')
                    ->where('service_type', 'ticketing')
                    ->where('status', '!=', 'cancelled')
                    ->first();
        if (!empty($OrderDetails)) {
            $TicketData->max_people -= $OrderDetails->tot_booked;
        }
        if ($TicketData->max_people < 1) {
            Session::flash('success', 'Sorry!, No tickets available for this date. Please choose different date.');
            return Redirect::to('ticket-offline-order');
        }

        $OrderData = OrderMaster::select(DB::raw('SUM(total_guests) AS tot_booked'))
                ->where('vendor_id', $TicketData->vendor_id)
                ->where('customer_phone', $request->customer_phone)
                ->where('created_at', 'like', date("Y-m-d") .'%')
                ->where('status', '!=', 'cancelled')
                ->first();
        if (!empty($OrderData)) {
            if ($TicketData->max_ticket_per_user_per_day < ($OrderData->tot_booked + $total_guests)) {
                Session::flash('success', 'Sorry!, maximum no. of tickets booked for the day using this mobile number.');
                return Redirect::to('ticket-offline-order');
            }
        }

        // echo "<pre>";print_r($request->all());exit;

        $LastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))->where('service_type', 'ticketing')->where('status', '!=', 'partially-cancelled')->first();
        if ($LastOrder->totOrder > 0) {
            $LastInvoiceId = (int)$LastOrder->totOrder + 1;
            $invoice_id = date('dmY'). 'TT00'. $LastInvoiceId;
        } else {
            $invoice_id = date('dmY') .'TT001';
        }
        $vendor_id = $TicketData->vendor_id;
        $vendorData = User::find($vendor_id);
        $vendor_name = $vendorData->company;
        $start_date = date("Y-m-d", strtotime($request->start_date));
        $end_date = null;
        
        $lastIdData = OrderMaster::orderBy('id', 'desc')->first();
        $lastId = !empty($lastIdData) ? $lastIdData->id + 1 : 1;
        $booking_id = 'OT-' . time() . '-' . $vendor_id . '-' . $lastId;

        $userId = Auth::user()->id;
        $status = 'completed';
        $payment_status = 'success';
        $payment_method = 'cash';
        $admin_amount = $vendor_amount = 0;
        $order_type = 'offline';
        
        $order_master = new OrderMaster([
            'order_id' => $booking_id,
            'invoice_id' => $invoice_id,
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendorData->company,
            'order_type' => $order_type,
            'customer_id' => $userId,
            'customer_name' => trim($request->customer_name),
            // 'customer_email' => trim($request->customer_email),
            'customer_phone' => trim($request->customer_phone),
            // 'customer_address1' => trim($request->customer_address1),
            // 'customer_city' => trim($request->customer_city),
            // 'customer_state' => trim($state[0]),
            // 'customer_zipcode' => trim($request->customer_zipcode),
            // 'customer_country' => trim($country[0]),
            // 'book_naration' => addslashes(trim($request->book_naration)),
            'service_type' => 'ticketing',
            'service_category' => $TicketData->category,
            'service_name' => $TicketData->name,
            'service_name_id' => $TicketData->id,
            'service_city' => $TicketData->city,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_time' => $TicketData->start_time,
            'end_time' => $TicketData->end_time,
            'total_adults' => $request->total_adults,
            'total_child' => 0,
            'total_guests' => $total_guests,
            'adult_price' => $TicketData->adult_price,
            'child_price' => $TicketData->child_price, 
            // 'rental_breakdown' => json_encode($price_break),
            'total_service_price' => $request->total_service_price,
            'sub_total_price' => $request->sub_total_price,
            // 'coupon_name' => $request->coupon_name,
            // 'coupon_code' => $request->coupon_code,
            'coupon_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'service_charge' => $request->service_charge,
            'total_order_price' => $request->total_order_price,
            'status' => $status,
            'payment_status' => $payment_status,
            'payment_gateway' => $request->payment_gateway,
            'payment_method' => $payment_method,
            'gate_number' => $TicketData->gate_no,
            'book_ip' => $request->ip(),
            'request_from' => 'web'
        ]);
        // echo "<pre>";print_r($OrderMaster);exit;
        if ($order_master->save()) {
            $OrderMasterId = $order_master->id;
            $OrderMaster = $OrderMasterNew = OrderMaster::find($OrderMasterId);
            
            if($request->payment_gateway == 'cash' || $request->payment_gateway == 'cheque') {
                // Generate Qr Code
                require_once public_path('QrCode/generateQrCode.php');
                $QrCodeData = array(
                    'invoiceId' => $OrderMasterNew->invoice_id,
                    'orderId' => $OrderMasterNew->order_id,
                    'txnId' => $OrderMasterNew->transaction_id,
                    'serviceType' => $OrderMasterNew->service_type,
                );
                $QrCode = generateQrCode(json_encode($QrCodeData));
                $OrderMaster->qr_code = $QrCode;
                $OrderMaster->qr_base64 = $this->site . $QrCode;
                $OrderMaster->qr_verified = 0;
                require_once public_path('s3_file_upload/s3_file_upload.php');
                if ($s3->putObjectFile($OrderMaster->qr_code, 'odishatourism', 'qrcodes/'. $OrderMaster->qr_code)) {
                    $OrderMaster->qr_base64 = 'https://odishatourism.s3.us-west-1.amazonaws.com/qrcodes/'. $OrderMaster->qr_code;
                }
            }
            $txn_id = !empty($OrderMasterNew->transaction_id) ? $OrderMasterNew->transaction_id : 'N/A';
            $discount = !empty($OrderMasterNew->coupon_amount) ? number_format($OrderMasterNew->coupon_amount, 2) : '0.00';

            $tspinword = parent::AmountInWords($OrderMasterNew->total_service_price);
            $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? '<u><b>GSTN No: '. $OrderMaster->gst_regd_no .'</b></u>' : '';
            $customerGSTCompany = (!empty($OrderMaster->gst_company_name)) ? '<u><b>Company Name: '. $OrderMaster->gst_company_name .'</b></u>' : '';

            $TicketInvoice = EmailTemplate::where('ref_code', 'ticketInvoice')->first();
            $check_date = date("d M Y", strtotime($OrderMaster->start_date));
            $duration = (!empty($OrderMaster->start_time)) ? '[' . $OrderMaster->start_time . ' - ' . $OrderMaster->end_time . ']' : 'All Day';

            $vendorGSTNo = (!empty($TicketData->gst_number)) ? $TicketData->gst_number : 'N/A';
            $vendorRegdCompany = (!empty($TicketData->gst_legal_name)) ? $TicketData->gst_legal_name : 'N/A';

            $childData = '';
            if ($OrderMaster->total_child > 0) {
                $childData = '<tr style="font-size:14px;"><td colspan="3" align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;"></td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. $OrderMaster->total_child .' Child</td><td align="center" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. number_format($OrderMaster->child_price, 2) .'</td><td align="right" valign="top" style="color:#000;border-right:1px solid #000;border-bottom:1px solid #000;">'. number_format($OrderMaster->total_child * $OrderMaster->child_price, 2) .'</td></tr>';
            }
            $extraService = $extraPrice = '';
            
            $childData .= $extraPrice;
            $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~invoiceid~", "~servicename~", "~checkdate~", "~totaladult~", "~adultprice~", "~totaladultprice~", "~childdata~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~"), 
                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $vendorData->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $OrderMaster->invoice_id, $OrderMaster->service_name, $check_date .'<br>'. $duration, $OrderMaster->total_adults, number_format($OrderMaster->adult_price, 2), number_format($OrderMaster->total_adults * $OrderMaster->adult_price, 2), $childData, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id), $TicketInvoice->source);
            if ($OrderMaster->vendor_id == 268 || $OrderMaster->vendor_id == 6048) { // 268  ,  6048
                $Message = str_replace($OrderMaster->total_adults .' Adult', $OrderMaster->total_adults, $Message);
            }
            $service_mail = $TicketInvoice->contact_email;
            $OrderMaster->invoice = $Message;

            $User = User::find($OrderMaster->customer_id);
            
            if ($OrderMaster->vendor_id == 268 || $OrderMaster->vendor_id == 6048) { // 268  ,  6048
                
                $ConfirmTemplate = EmailTemplate::where('ref_code','offlinespecialticketConfirmMail')->first();
                if (!empty($ConfirmTemplate)) {
                    $check_date = '['. date("h:i a", strtotime($OrderMaster->start_time)) .' - '. date("h:i a", strtotime($OrderMaster->end_time)) .'] | '. date("d M Y", strtotime($OrderMaster->start_date));
                    $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Invoice ID - '. $OrderMaster->invoice_id;

                    $TicketCategory = str_replace("Rourkela City Festival", "", trim($OrderMaster->service_name));
                    $TicketCategory = str_replace("(", "", trim($TicketCategory));
                    $TicketCategory = str_replace(")", "", trim($TicketCategory));                                            
                    $ticketbg = '';
                    if(trim($TicketCategory) == 'GOLD'){
                        // $ticketbg = 'https://rklcityfest.bookodisha.com/voucher/gold.jpg';
                        $TicketCategoryName = '<span style="color:#000;">'.$TicketCategory.'</span>';
                    }else if(trim($TicketCategory) == 'DIAMOND'){
                        // $ticketbg = 'https://rklcityfest.bookodisha.com/voucher/diamond.png';
                        $TicketCategoryName = '<span style="color:#000;">'.$TicketCategory.'</span>';
                    }else if(trim($TicketCategory) == 'PLATINUM'){
                        // $ticketbg = 'https://rklcityfest.bookodisha.com/voucher/platinum.jpg';
                        $TicketCategoryName = '<span style="color:#000;">'.$TicketCategory.'</span>';
                    }
                    $msg = str_replace(array("~bookingqrCode~", "~gateno~", "~servicename~", "~invoiceid~", "~adult~", "~checkdate~", "~ticketbg~"), 
                            array($OrderMaster->qr_base64, $TicketData->gate_no, $TicketCategoryName, $OrderMaster->invoice_id, $OrderMaster->total_adults, $check_date, $ticketbg), $ConfirmTemplate->source);
                    // $msg = str_replace(array("~bookingqrCode~", "~gateno~", "~servicename~", "~invoiceid~", "~adult~", "~checkdate~"), 
                    //         array($OrderMaster->qr_base64, $TicketData->gate_no, $OrderMaster->service_name, $OrderMaster->invoice_id, $OrderMaster->total_adults, $check_date), $ConfirmTemplate->source);

                    $OrderMaster->confimation_voucher = $msg;
                    // try {
                    //     Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                    // }
                    // catch(\Exception $e) {}
                }
            }

            $OrderMaster->save();
            // $Message .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
            
            // $admin = User::where('role', 1)->first();
            // $receipent = [$vendorData->email];
            // if (!empty($service_mail)) {
            //     array_push($receipent, $service_mail);
            // }
            // if (Auth::user()->user_role == 'agent_staff') {
            //     Mail::to($receipent)
            //         ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
            // } else {
            //     Mail::to($OrderMaster->customer_email)
            //         ->bcc($receipent)
            //         ->send(new \App\Mail\RegistrationMailUser($Message, $Subject));
            // }
            Session::flash('success', 'Order created successfully.');
            return Redirect::to('ticket-offline-order')->with(['confirm_voucher' => $OrderMaster->confimation_voucher, 'orderId' => $OrderMaster->id]);
            // return Redirect::to('ticket-offline-order');
        } else {
            Session::flash('success', 'Unable to create order.');
            return Redirect::to('ticket-offline-order');
        }
    }
    public function offlineTicketOrder()
    {
        // if (!(parent::checkViewPrivilege(77))) {
        //     Session::flash('success', 'You are not autherised to view this page.');
        //     return redirect()->back();
        // }
        $CountryData = Country::pluck('name', 'id');
        $vendor_id = (Auth::user()->role == 2) ? Auth::user()->id : Auth::user()->vendor_id;
        $TicketQry = Ticket::where(['status' => 'publish', 'vendor_id' => $vendor_id]);
        if ((Auth::user()->role == 3)) {
            $SubuserAccess = SubuserAccess::where(['user_id' => Auth::user()->id, 'service' => 'ticket'])->pluck('service_id', 'id')->toArray();
            if (!empty($SubuserAccess)) {
                $TicketQry->whereIn('id', array_values($SubuserAccess));
            }
        }
        $Ticket = $TicketQry->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $site = $this->site;
        $orderId = '';
        return view('ticketing.offline-ticket-order', compact('Ticket', 'CountryData', 'site', 'orderId'));
    }

    public function createOfflineTicketOrder(Request $request)
    {
        // if (!(parent::checkWritePrivilege(29))) {
        //     Session::flash('success', 'You are not autherised to do this operation.');
        //     return redirect()->back();
        // }
            //    echo "<pre>";print_r($request->all());exit;
            $TicketData = Ticket::find($request->service_name_id);
            // $total_guests = $request->total_adults;
            require_once public_path('paytm_lib/config_paytm.php');
            // if ($request->payment_gateway == 'hdfc') {
                // $PropertyAccount = PropertyAccount::where(['service_type' => $OrderMasterNew->service_type, 'service_id' => $OrderMasterNew->service_name_id])->first();
                // if (!empty($PropertyAccount) && PAYTM_ENVIRONMENT == 'PROD') {
                    $HDFC_KEY = 'tIfhoY';
                    $HDFC_SALT = 'hwMFIy8fv6gyKIWxWdwBZccvL7v8pvop';
                //     $MERCHANT_ID = $PropertyAccount->hdfc_mid;
                // }
                $txn_id = "TXN" . time() . rand(10000, 99999999);
                $booking_id = date('Ymdhi') . 'T' . rand(111, 999);

                $command = "create_invoice";
                $var1_arr = array(
                    'amount' => $request->total_order_price,
                    'txnid' => $txn_id,
                    'productinfo' => parent::cleanString($TicketData->name),
                    'firstname' => parent::cleanString($request->customer_name),
                    'email' => $request->customer_email,
                    'phone' => parent::cleanString($request->customer_phone),
                    'udf1' => $booking_id,
                    // 'address1' => parent::cleanString($OrderMasterNew->customer_address1),
                    // 'city' => parent::cleanString($OrderMasterNew->customer_city),
                    // 'state' => parent::cleanString($OrderMasterNew->customer_state),
                    // 'country' => parent::cleanString($OrderMasterNew->customer_country),
                    // 'zipcode' => $OrderMasterNew->customer_zipcode,
                    'validation_period' => 30,
                    'send_email_now' => '1',
                    'send_sms' => '1',
                    "time_unit" => "M"
                );
                $var1 = json_encode($var1_arr);
                $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                $hash = strtolower(hash('sha512', $hash_str));
                $r = array('key' => $HDFC_KEY, 'hash' => $hash, 'command' => $command, 'var1' => $var1);
                $qs = http_build_query($r);

                $wsUrl = 'https://info.payu.in/merchant/postservice.php?form=2';
                // $wsUrl = VERIFY_URL;

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
                $verify_response = json_decode($o, true);
                //  echo "<pre>";print_r($verify_response);exit;
                if (isset($verify_response['Status']) && $verify_response['Status'] == 'Success' && !empty($verify_response['URL'])) {
                    $link_expiry = date("Y-m-d H:i:s", strtotime('+30 minutes'));
                    // $PaymentHistory = new PaymentHistory([
                    //     'order_id' => $OrderMasterNew->order_id,
                    //     'vendor_id' => $OrderMasterNew->vendor_id,
                    //     'user_id' => $OrderMasterNew->customer_id,
                    //     'payment_method' => 'hdfc',
                    //     'transaction_id' => $txn_id,
                    //     'amount' => $OrderMasterNew->total_order_price,
                    //     'product_info' => parent::cleanString($OrderMasterNew->service_name),
                    //     'first_name' => $OrderMasterNew->customer_name,
                    //     'email' => $OrderMasterNew->customer_email,
                    //     'phone' => $OrderMasterNew->customer_phone,
                    //     'address1' => parent::cleanString($OrderMasterNew->customer_address1),
                    //     'city' => parent::cleanString($OrderMasterNew->customer_city),
                    //     'state' => parent::cleanString($OrderMasterNew->customer_state),
                    //     'country' => parent::cleanString($OrderMasterNew->customer_country),
                    //     'zipcode' => $OrderMasterNew->customer_zipcode,
                    //     'udf1' => $OrderMasterNew->invoice_id,
                    //     'udf2' => $OrderMasterNew->vendor_id,
                    //     'udf3' => $OrderMasterNew->vendor_name,
                    //     'udf4' => $OrderMasterNew->service_type,
                    //     'status' => 'pending'
                    // ]);
                    // $PaymentHistory->save();
                    // $payment_id = $PaymentHistory->id;

                    $OrderMasterNew['offline_url'] = $verify_response['URL'];
                    $OrderMasterNew['offline_link_expiry'] = $link_expiry;
                    Session::flash('success', 'Order created successfully.');
                    return view('ticketing.offline-ticket-order-success', compact('OrderMasterNew', 'var1_arr'));
                    // return Redirect::to('offline-order-success');
                } else {
                    Session::flash('failure', "Sorry!, Could not able to place order due to some technical issue in generating payment link. Please try again after some time.");
                    return Redirect::to('offline-ticket-order');
                }
            // }
            // Session::flash('success', 'Order created successfully.');
            // return Redirect::to('offline-order');
    }
}
