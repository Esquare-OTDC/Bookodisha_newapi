<?php

namespace App\Http\Controllers;

use App\City;
use App\Country;
use App\EmailTemplate;
use App\GstDetail;
use App\GstTable;
use App\HallModels\BlockedHallInventory;
use App\HallModels\Hall;
use App\HallModels\HallAttribute;
use App\HallModels\HallBooking;
use App\HallModels\HallCategory;
use App\HallModels\HallMasterInventory;
use App\HallModels\HallProperty;
use App\HallModels\HallRoomFacility;
use App\HallModels\Slot;
use App\MasterHotel;
use App\OrderDetail;
use App\OrderMaster;
use App\PaymentHistory;
use App\PropertyAccount;
use App\SmsTemplate;
use App\State;
use App\SubuserAccess;
use App\Traits\ConferenceTraits;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PDF;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Mail;

class HallBookingController extends Controller
{
    use ConferenceTraits;

    public $site;
    public $frontendUrl;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') .'/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }
    /**
     * Display the hall listing page.
     *
     * Performs authorization validation and loads the vendor list
     * required by the hall management view.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function allHall()
    {
        if (!(parent::checkViewPrivilege(7))) {
            Session::flash('success', 'You are not autherised to view this page.');
            return redirect()->back();
        }

        $Vendors = User::where('role', '2')
            ->orderBy('company', 'asc')
            ->pluck('company', 'id');

        return view('hall.all-hall', compact('Vendors'));
    }

    /**
     * Retrieve hall records for DataTables AJAX requests.
     *
     * Supports pagination, sorting, filtering, global search,
     * and staff-level access restrictions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHallDetails(Request $request)
    {
        $aColumns = array('id', 'property_name', 'place', 'contact_email', 'manager_contact', 'status');
        $sIndexColumn = "id";
        $sTable = "m_property";
        $sLimit = "";

        if (isset($_POST['start']) && $_POST['length'] != '-1') {
            $sLimit = " LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
        }

        $sOrder = " ORDER BY id DESC ";

        if (isset($_POST['order'])) {
            $sOrder = " ORDER BY ";

            for ($i = 0; $i < intval(count($_POST['order'])); $i++) {
                if ($_POST['columns'][$_POST['order'][$i]['column']]['orderable'] == "true") {
                    $sOrder .= "`" .
                        $aColumns[intval($_POST['order'][$i]['column'])] .
                        "` " .
                        ($_POST['order'][$i]['dir'] === 'asc' ? 'asc' : 'desc') .
                        ", ";
                }
            }

            $sOrder = substr_replace($sOrder, "", -2);

            if ($sOrder == " ORDER BY") {
                $sOrder = "";
            }
        }

        $staff_condition = '';

        if (Auth::user()->role == 3) {
            $SubuserAccess = SubuserAccess::where([
                'user_id' => Auth::user()->id,
                'service' => 'hall'
            ])->pluck('service_id')->toArray();

            if (!empty($SubuserAccess)) {
                $staff_condition = ' AND id IN (' . implode(',', $SubuserAccess) . ')';
            }
        }

        $sWhere = " WHERE id != '' AND is_deleted = 0 " . $staff_condition;

        if (Auth::user()->access_type != 'superadmin') {
            if (Auth::user()->role == 3 && !empty(Auth::user()->vendor_id)) {
                $createdBy = Auth::user()->vendor_id;
            } else {
                $createdBy = Auth::user()->id;
            }

            $sWhere .= " AND created_by = " . intval($createdBy);
        }

        $searchColumns = array('property_name', 'place');

        if (!empty($_POST['searchValue2']) && !empty($_POST['searchValue3'])) {
            if (in_array($_POST['searchValue2'], $searchColumns)) {
                $_POST['searchValue3'] = parent::cleanString($_POST['searchValue3']);

                $sWhere .= ' AND ' . $_POST['searchValue2'] . ' LIKE "%' . $_POST['searchValue3'] . '%"';
            }
        }

        if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
            $searchValue = parent::cleanString($_POST['search']['value']);
            $sWhere .= " AND (";

            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . $searchValue . "%' OR ";
            }

            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        $sQuery = "
                SELECT SQL_CALC_FOUND_ROWS * FROM $sTable
                $sWhere
                $sOrder
                $sLimit
                ";

        $rResult = DB::select($sQuery);

        $aResultFilterTotal = DB::select(
            "SELECT FOUND_ROWS() as totalrow"
        );

        $iFilteredTotal = $aResultFilterTotal[0]->totalrow;

        $sQuery = "
                SELECT COUNT($sIndexColumn) as countindex
                FROM $sTable
                $sWhere
                ";

        $aResultTotal = DB::select($sQuery);
        $iTotal = $aResultTotal[0]->countindex;

        $output = array(
            "draw" => intval($_POST['draw']),
            "recordsTotal" => $iTotal,
            "recordsFiltered" => $iFilteredTotal,
            "data" => array()
        );

        foreach ($rResult as $aRow) {
            $encryptedId = Crypt::encryptString($aRow->id);
            $row = array();

            $row[] =
                '<div class="checkbox-fade">
                    <label>
                        <input
                            type="checkbox"
                            value="' . $aRow->id . '"
                            class="itemcheck"
                        >
                        <span class="cr">
                            <i class="cr-icon icofont icofont-ui-check txt-primary"></i>
                        </span>
                    </label>
                </div>';

            $row[] = $aRow->property_name;
            $row[] = $aRow->place;
            $row[] = $aRow->contact_email;
            $row[] = $aRow->manager_contact;

            if ($aRow->status == 1) {
                $row[] = '<span style="
                        text-transform: capitalize;
                        font-size: 12px;
                        color: #fff;
                        background-color: #28a745;
                        font-weight: 700;
                        border-radius: .25rem;
                        padding: .25em .4em;
                    ">
                        Publish
                    </span>';

            } elseif ($aRow->status == 0) {
                $row[] = '<span style="
                            text-transform: capitalize;
                            font-size: 12px;
                            color: #fff;
                            background-color: #ffc107;
                            font-weight: 700;
                            border-radius: .25rem;
                            padding: .25em .4em;
                        ">
                            Draft
                        </span>';
            } else {
                $row[] = '<span style="
                            text-transform: capitalize;
                            font-size: 12px;
                            color: #fff;
                            background-color: #6c757d;
                            font-weight: 700;
                            border-radius: .25rem;
                            padding: .25em .4em;
                        ">
                            Unknown
                        </span>';
            }

            $row[] =
                '<div class="btn-group">
                                        <button
                                            aria-expanded="false"
                                            data-toggle="dropdown"
                                            class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light"
                                            type="button"
                                        >
                                            Action <span class="caret"></span>
                                        </button>

                                        <ul role="menu" class="dropdown-menu">
                                            <li>
                                                <a href="' . url('hall-edit', $encryptedId) . '">
                                                    Edit Property
                                                </a>
                                            </li>
                                            <li>
                                                <a href="' . url('manage-hall-rooms', $encryptedId) . '">
                                                    Manage Property
                                                </a>
                                            </li>
                                            <li>
                                                <a
                                                    href="javascript:void(0)"
                                                    class="deleteHall"
                                                    data-id="' . $encryptedId . '"
                                                >
                                                    Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>';

            $output['data'][] = $row;
        }

        return response()->json($output);
    }
    /**
     * Perform hall operations.
     *
     * Currently supports soft deletion of hall records.
     *
     * @param Request $request
     * @return string JSON encoded response
     */


    public function hallOprsn(Request $request)
    {
        $response = [];
        try {
            /*
            |--------------------------------------------------------------------------
            | Delete Hall Property
            |--------------------------------------------------------------------------
            */

            if ($request->request_type == 'delete_hall') {
                $hallId = Crypt::decryptString($request->Id);
                $hall = HallProperty::where('id', $hallId)
                    ->where('is_deleted', 0)
                    ->first();
                if (!$hall) {
                    $response['status'] = 0;
                    $response['message'] = 'Hall not found.';
                } else {
                    $hall->update([
                        'is_deleted' => 1,
                        'updated_by' => Auth::id(),
                        'updated_at' => now()
                    ]);
                    $response['status'] = 1;
                    $response['message'] = 'Hall deleted successfully.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Hall Room
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'delete_hall_room') {
                try {
                    $roomId = Crypt::decryptString($request->Id);
                    $room = Hall::where('id', $roomId)
                        ->where('is_deleted', 0)
                        ->first();
                    if (!$room) {
                        $response['status'] = 0;
                        $response['message'] = 'Room not found.';
                    } else {
                        $room->update([
                            'is_deleted' => 1,
                            'updated_by' => Auth::id(),
                            'updated_at' => now()
                        ]);
                        Slot::where('hall_id', $room->id)
                            ->where('is_deleted', 0)
                            ->update([
                                'is_deleted' => 1,
                                'updated_by' => Auth::id(),
                                'updated_at' => now()
                            ]);
                        $response['status'] = 1;
                        $response['message'] = 'Hall deleted successfully.';
                    }
                } catch (\Exception $e) {
                    $response['status'] = 0;
                    $response['message'] = 'Invalid Hall ID.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Bulk Publish / Draft Hall Rooms
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'publish-room' || $request->request_type == 'draft-room') {
                $ids = json_decode($request->IdArray, true);
                if (empty($ids)) {
                    $response['status'] = 0;
                    $response['message'] = 'No items selected.';
                    return response()->json($response);
                }
                $hallIds = [];
                foreach ($ids as $id) {
                    try {
                        $hallIds[] = Crypt::decryptString($id);
                    } catch (\Exception $e) {
                        $hallIds[] = $id;
                    }
                }
                if ($request->request_type == 'publish-room') {
                    Hall::whereIn('id', $hallIds)
                        ->where('is_deleted', 0)
                        ->update([
                            'publish_status' => 'PUBLISH',
                            'status' => '1',
                            'updated_by' => Auth::id(),
                            'updated_at' => now()
                        ]);
                    $response['status'] = 1;
                    $response['message'] = 'Selected halls published successfully.';
                } else {
                    Hall::whereIn('id', $hallIds)
                        ->where('is_deleted', 0)
                        ->update([
                            'publish_status' => 'DRAFT',
                            'status' => '0',
                            'updated_by' => Auth::id(),
                            'updated_at' => now()
                        ]);
                    $response['status'] = 1;
                    $response['message'] = 'Selected halls moved to draft successfully.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Bulk Publish / Draft Hall Properties
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'publish' || $request->request_type == 'draft') {
                $ids = json_decode($request->IdArray, true);
                if (empty($ids)) {
                    $response['status'] = 0;
                    $response['message'] = 'No items selected.';
                    return response()->json($response);
                }
                $propertyIds = [];
                foreach ($ids as $id) {
                    try {
                        $propertyIds[] = Crypt::decryptString($id);
                    } catch (\Exception $e) {
                        $propertyIds[] = $id;
                    }
                }
                if ($request->request_type == 'publish') {
                    HallProperty::whereIn('id', $propertyIds)
                        ->where('is_deleted', 0)
                        ->update([
                            'publish_status' => 'PUBLISH',
                            'status' => '1',
                            'updated_by' => Auth::id(),
                            'updated_at' => now()
                        ]);
                    $response['status'] = 1;
                    $response['message'] = 'Selected properties published successfully.';
                } else {
                    HallProperty::whereIn('id', $propertyIds)
                        ->where('is_deleted', 0)
                        ->update([
                            'publish_status' => 'DRAFT',
                            'status' => '0',
                            'updated_by' => Auth::id(),
                            'updated_at' => now()
                        ]);
                    $response['status'] = 1;
                    $response['message'] = 'Selected properties moved to draft successfully.';
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Get Hall City
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'get_hall_city') {
                $cities = HallProperty::where('is_deleted', 0)
                    ->whereNotNull('place')
                    ->where('place', '!=', '')
                    ->pluck('place', 'place')
                    ->toArray();
                if (empty($cities)) {
                    $response['status'] = 0;
                    $response['message'] = 'No city found.';
                    $response['data'] = [];
                } else {
                    $response['status'] = 1;
                    $response['data'] = $cities;
                }
                return response()->json($response);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Availability Data
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'get_availability_data') {
                $month = strlen($request->month) == 1 ? '0' . $request->month : $request->month;
                $check_date = $request->year . '-' . $month;
                $inventories = HallMasterInventory::where('hall_id', $request->roomId)
                    ->where('inventory_date', 'like', $check_date . '%')
                    ->orderBy('inventory_date')
                    ->get();
                if ($inventories->count() > 0) {
                    $available_data = [];
                    foreach ($inventories as $inventory) {
                        if (
                            empty($inventory->inventory_slot_type) &&
                            $inventory->first_half_available == 0 &&
                            $inventory->second_half_available == 0
                        ) {
                            $available_data[] = [
                                'title' => 'FD - Available',
                                'color' => 'green',
                                'start' => $inventory->inventory_date
                            ];
                            continue;
                        }
                        if ($inventory->inventory_slot_type == 1) {
                            $available_data[] = [
                                'title' => 'FD - Booked',
                                'color' => 'orange',
                                'start' => $inventory->inventory_date
                            ];
                            continue;
                        }
                        if ($inventory->inventory_slot_type == 2) {
                            if ($inventory->first_half_available == 0) {
                                $available_data[] = [
                                    'title' => 'FH - Available',
                                    'color' => 'green',
                                    'start' => $inventory->inventory_date
                                ];
                            } elseif ($inventory->first_half_available == 2) {
                                $available_data[] = [
                                    'title' => 'FH - Booked',
                                    'color' => 'orange',
                                    'start' => $inventory->inventory_date
                                ];
                            }
                            if ($inventory->second_half_available == 0) {
                                $available_data[] = [
                                    'title' => 'SH - Available',
                                    'color' => 'green',
                                    'start' => $inventory->inventory_date
                                ];
                            } elseif ($inventory->second_half_available == 2) {
                                $available_data[] = [
                                    'title' => 'SH - Booked',
                                    'color' => 'orange',
                                    'start' => $inventory->inventory_date
                                ];
                            }
                        }
                    }
                    $response['status'] = 1;
                    $response['data'] = $available_data;
                } else {
                    $response['status'] = 0;
                    $response['message'] = 'No inventory found.';
                    $response['data'] = [];
                }
                return response()->json($response);
            }
            /*
            |--------------------------------------------------------------------------
            | Mark as  released in block hall page
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'mark_block_released') {
                if (!(parent::checkWritePrivilege(12))) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'You are not authorized to do this operation.'
                    ]);
                }
                DB::beginTransaction();
                try {
                    $item_array = json_decode($request->IdArray, true);
                    if (empty($item_array)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'No records selected.'
                        ]);
                    }
                    $blockedRecords = DB::table('t_blocked_hall_inventory')
                        ->whereIn('id', $item_array)
                        ->whereDate('block_date', '>=', Carbon::today())
                        ->get();
                    if ($blockedRecords->isEmpty()) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'No matching blocked records found.'
                        ]);
                    }
                    foreach ($blockedRecords as $record) {
                        $query = DB::table('t_hall_inventory')
                            ->where('hall_id', $record->hall_id)
                            ->whereDate('inventory_date', $record->block_date);
                        if ($record->slot_type == 1) {
                            $query->update([
                                'inventory_slot_type' => null,
                                'first_half_available' => '0',
                                'second_half_available' => '0'
                            ]);
                        } elseif ($record->slot_type == 2) {
                            $query->update([
                                'first_half_available' => '0',
                            ]);
                        } elseif ($record->slot_type == 3) {
                            $query->update([
                                'second_half_available' => '0'
                            ]);
                        }
                        $updated = DB::table('t_hall_inventory')
                            ->where('hall_id', $record->hall_id)
                            ->whereDate('inventory_date', $record->block_date)
                            ->first();
                        if ($updated->first_half_available == '0' && $updated->second_half_available == '0') {
                            DB::table('t_hall_inventory')
                                ->where('hall_id', $record->hall_id)
                                ->whereDate('inventory_date', $record->block_date)
                                ->update([
                                    'inventory_slot_type' => null
                                ]);
                        }
                    }

                    // delete blocked records
                    DB::table('t_blocked_hall_inventory')
                        ->whereIn('id', $item_array)
                        ->whereDate('block_date', '>=', Carbon::today())
                        ->delete();
                    DB::commit();
                    return response()->json([
                        'status' => 1,
                        'message' => 'Block released successfully.'
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage()
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Get Hall Invoice PDF
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'get_invoice_html') {
                try {
                    if (empty($request->orderID)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Order ID is required.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Order Details
                    |--------------------------------------------------------------------------
                    */

                    $OrderMaster = OrderMaster::where('order_id', $request->orderID)->first();
                    if (empty($OrderMaster)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Order record not found.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Hall Booking Details
                    |--------------------------------------------------------------------------
                    */

                    $bookings = DB::table('t_booking as tb')
                        ->join('m_hall as h', 'tb.hall_id', '=', 'h.id')
                        ->join('m_hcategory as hc', 'h.hcategory_id', '=', 'hc.id')
                        ->join('order_masters as om', 'tb.booking_id', '=', 'om.order_id')
                        ->select(
                            'om.invoice_id',
                            'om.order_id',
                            'om.service_name',
                            'om.order_type',
                            'tb.start_date',
                            'tb.end_date',
                            'tb.slot_type',
                            'tb.totalPrice as booking_amount',
                            'hc.hcategory_name as hall_type'
                        )
                        ->where('om.order_id', $request->orderID)
                        ->where('tb.is_deleted', 0)
                        ->where('h.is_deleted', 0)
                        ->orderBy('tb.start_date', 'asc')
                        ->get();
                    if ($bookings->isEmpty()) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Hall booking details not found.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Existing Hall Invoice Template
                    |--------------------------------------------------------------------------
                    */

                    $HallInvoice = DB::table('email_templates')
                        ->where('ref_code', 'hallInvoice')
                        ->first();
                    if (empty($HallInvoice) || empty($HallInvoice->source)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Hall invoice template not found.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Customer Details
                    |--------------------------------------------------------------------------
                    */

                    $customerName = $OrderMaster->customer_name ?? '';
                    $customerPhone = $OrderMaster->customer_phone ?? $OrderMaster->phone ?? $OrderMaster->mobile ?? '';
                    $customerEmail = $OrderMaster->customer_email ?? $OrderMaster->email ?? '';
                    $customerAddress = $OrderMaster->customer_address ?? $OrderMaster->address ?? '';
                    $customerGSTNo = $OrderMaster->customer_gst_no ?? $OrderMaster->gst_regd_no ?? '';
                    $customerGSTCompany = $OrderMaster->customer_gst_company ?? '';

                    /*
                    |--------------------------------------------------------------------------
                    | Hall Details
                    |--------------------------------------------------------------------------
                    */

                    $hallAddress = $OrderMaster->service_address ?? '';
                    $hallEmail = $OrderMaster->service_email ?? '';
                    $hallGSTNo = $OrderMaster->service_gst_no ?? '';
                    $hallGSTCompany = $OrderMaster->service_gst_company ?? '';

                    /*
                    |--------------------------------------------------------------------------
                    | Payment Details
                    |--------------------------------------------------------------------------
                    */

                    $paymentMethod = $OrderMaster->payment_method ?? $OrderMaster->payment_mode ?? 'PAYTM';
                    $transactionId = $OrderMaster->transaction_id ?? '';

                    /*
                     * Payment Reference blank
                     */

                    $paymentReference = '';

                    /*
                    |--------------------------------------------------------------------------
                    | Amount Details
                    |--------------------------------------------------------------------------
                    */

                    /*
                     * Net Total
                     */

                    $netTotal = (float) ($OrderMaster->sub_total_price ?? 0);

                    /*
                     * GST
                     */

                    $gstAmount = (float) ($OrderMaster->tax_amount ?? 0);

                    /*
                     * Grand Total
                     */

                    $grandTotal = (float) ($OrderMaster->total_order_price ?? ($netTotal + $gstAmount));

                    /*
                    |--------------------------------------------------------------------------
                    | Main Logo
                    |--------------------------------------------------------------------------
                    */

                    $mainLogoPath = public_path('images/frontend/otdc_white.jpg');
                    $mainLogo = '';
                    if (file_exists($mainLogoPath)) {
                        $mainLogoType = pathinfo($mainLogoPath, PATHINFO_EXTENSION);
                        $mainLogo = 'data:image/' . $mainLogoType . ';base64,' . base64_encode(file_get_contents($mainLogoPath));
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Company Logo
                    |--------------------------------------------------------------------------
                    */

                    $companyLogoPath = public_path('images/profile/otdc1_1_1746204931.png');
                    $companyLogo = '';
                    if (file_exists($companyLogoPath)) {
                        $companyLogoType = pathinfo($companyLogoPath, PATHINFO_EXTENSION);
                        $companyLogo = 'data:image/' . $companyLogoType . ';base64,' . base64_encode(file_get_contents($companyLogoPath));
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Build Hall Booking Rows
                    |--------------------------------------------------------------------------
                    */

                    $roomPricing = '';
                    foreach ($bookings as $booking) {
                        $bookingId = htmlspecialchars($booking->invoice_id ?? '', ENT_QUOTES, 'UTF-8');
                        $hallType = htmlspecialchars($booking->hall_type ?? '', ENT_QUOTES, 'UTF-8');
                        $hallName = htmlspecialchars($booking->service_name ?? '', ENT_QUOTES, 'UTF-8');
                        $startDate = '';
                        if (!empty($booking->start_date)) {
                            $startDate = date('d M Y', strtotime($booking->start_date));
                        }
                        $endDate = '';
                        if (!empty($booking->end_date)) {
                            $endDate = date('d M Y', strtotime($booking->end_date));
                        }
                        if (!empty($startDate) && !empty($endDate)) {
                            $dateRange = $startDate . ' - ' . $endDate;
                        } elseif (!empty($startDate)) {
                            $dateRange = $startDate;
                        } else {
                            $dateRange = '-';
                        }

                        /*
                         * Booking Type = order_masters.order_type
                         */

                        $bookingType = htmlspecialchars($booking->order_type ?? '', ENT_QUOTES, 'UTF-8');
                        $amount = (float) ($booking->booking_amount ?? 0);
                        $roomPricing .= '
                        <tr>
                            <td
                                align="center"
                                valign="middle"
                                style="color: #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;"
                            >
                                ' . $bookingId . '
                            </td>
                            <td
                                align="center"
                                valign="middle"
                                style="color: #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;"
                            >
                                ' . $hallType . '
                            </td>
                            <td align="center" valign="middle" style="color: #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;">
                                ' . $hallName . '
                            </td>
                            <td
                                align="center"
                                valign="middle"
                                style="color: #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;"
                            >
                                ' . htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') . '
                            </td>
                            <td
                                align="center"
                                valign="middle"
                                style="color: #000; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px;"
                            >
                                ' . $bookingType . '
                            </td>
                            <td
                                align="right"
                                valign="middle"
                                style="color: #000; border-bottom: 1px solid #000; padding: 4px;"
                            >
                                ' . number_format($amount, 2) . '
                            </td>
                        </tr>';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Order Date
                    |--------------------------------------------------------------------------
                    */

                    $orderDateValue = $OrderMaster->created_at ?? $OrderMaster->created_on ?? now();
                    $orderDate = date('d M Y h:i a', strtotime($orderDateValue));

                    /*
                    |--------------------------------------------------------------------------
                    | Amount Summary
                    |--------------------------------------------------------------------------
                    |
                    | Only:
                    | Net Total
                    | GST
                    | Grand Total
                    |
                    |--------------------------------------------------------------------------
                    */

                    $amountSummary = '
                    <tr>
                        <td
                            colspan="5"
                            align="right"
                            valign="middle"
                            style="color: #000; padding-right: 10px; font-size: 13px; line-height: 20px;"
                        >
                            Net Total :
                        </td>
                        <td
                            align="right"
                            valign="middle"
                            style="color: #000; font-size: 13px; line-height: 20px;"
                        >
                            ' . number_format($netTotal, 2) . '
                        </td>
                    </tr>
                    <tr>
                        <td
                            colspan="5"
                            align="right"
                            valign="middle"
                            style="color: #000; padding-right: 10px; font-size: 13px; line-height: 20px;"
                        >
                            GST :
                        </td>
                        <td
                            align="right"
                            valign="middle"
                            style="color: #000; font-size: 13px; line-height: 20px;"
                        >
                            ' . number_format($gstAmount, 2) . '
                        </td>
                    </tr>
                    <tr>
                        <td
                            colspan="5"
                            align="right"
                            valign="middle"
                            style="color: #000; padding-right: 10px; font-size: 13px; line-height: 20px; font-weight: bold;"
                        >
                            Grand Total :
                        </td>
                        <td
                            align="right"
                            valign="middle"
                            style="color: #000; font-size: 13px; line-height: 20px; font-weight: bold;"
                        >
                            ' . number_format($grandTotal, 2) . '
                        </td>
                    </tr>';

                    /*
                    |--------------------------------------------------------------------------
                    | Replace Existing ~ordertotal~ Row
                    |--------------------------------------------------------------------------
                    */

                    $templateSource = preg_replace(
                        '/<tr\b[^>]*>(?:(?!<\/tr>).)*~ordertotal~(?:(?!<\/tr>).)*<\/tr>/is',
                        $amountSummary,
                        $HallInvoice->source,
                        1
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Replace Hall Invoice Placeholders
                    |--------------------------------------------------------------------------
                    */

                    $Message = str_replace(array(
                        "~otdcLogo~",
                        "~username~",
                        "~usermobile~",
                        "~usermail~",
                        "~useraddress~",
                        "~usergstno~",
                        "~usergstcompany~",
                        "~hoteladdress~",
                        "~hotelemail~",
                        "~hotelgst~",
                        "~hotelgstcompany~",
                        "~vendorLogo~",
                        "~orderdate~",
                        "~roomfeesdetails~",
                        "~orderdetails~",
                        "~totalserviceprice~",
                        "~couponamount~",
                        "~subtotal~",
                        "~gst~",
                        "~paymentmethod~",
                        "~txnid~",
                        "~couponname~",
                        "~tspinword~",
                        "~payuid~"
                    ), array(
                        /*
                         * Logo
                         */

                        $mainLogo,
                        /*
                         * Customer
                         */

                        $customerName,
                        $customerPhone,
                        $customerEmail,
                        $customerAddress,
                        $customerGSTNo,
                        $customerGSTCompany,

                        /*
                         * Hall
                         */

                        $hallAddress,
                        $hallEmail,
                        $hallGSTNo,
                        $hallGSTCompany,
                        /*
                         * Vendor Logo
                         */

                        $companyLogo,
                        /*
                         * Order Date
                         */

                        $orderDate,
                        /*
                         * Hall Rows
                         */

                        $roomPricing,
                        $roomPricing,
                        /*
                         * Unused total
                         */

                        '',
                        /*
                         * Unused discount
                         */

                        '',
                        /*
                         * Net Total
                         */

                        number_format($netTotal, 2),
                        /*
                         * GST
                         */

                        number_format($gstAmount, 2),
                        /*
                         * Payment Method
                         */

                        $paymentMethod,
                        /*
                         * Transaction ID
                         */

                        $transactionId,
                        /*
                         * Coupon Name
                         */

                        '',
                        /*
                         * Total in words unused
                         */

                        '',
                        /*
                         * Payment Reference blank
                         */

                        $paymentReference
                    ), $templateSource);

                    /*
                    |--------------------------------------------------------------------------
                    | Create Documents Directory
                    |--------------------------------------------------------------------------
                    */

                    $directory = public_path('documents');
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PDF File Name
                    |--------------------------------------------------------------------------
                    */

                    $invoiceNumber = !empty($OrderMaster->invoice_id) ? $OrderMaster->invoice_id : $OrderMaster->order_id;
                    $safeInvoiceNumber = preg_replace('/[^A-Za-z0-9_-]/', '_', $invoiceNumber);
                    $file = 'documents/Invoice_' . $safeInvoiceNumber . '_' . time() . '.pdf';
                    $pdfname = public_path($file);

                    /*
                    |--------------------------------------------------------------------------
                    | Generate PDF
                    |--------------------------------------------------------------------------
                    */

                    PDF::loadHTML(html_entity_decode($Message, ENT_QUOTES, 'UTF-8'))->save($pdfname);

                    /*
                    |--------------------------------------------------------------------------
                    | Return Response
                    |--------------------------------------------------------------------------
                    */

                    return response()->json([
                        'status' => 1,
                        'message' => 'Invoice generated successfully.',
                        'data' => asset($file)
                    ]);
                } catch (\Throwable $e) {
                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage() . ' | File: ' . basename($e->getFile()) . ' | Line: ' . $e
                            ->getLine()
                    ]);
                }
            } elseif ($request->request_type == 'export_booking_report') {
                if (!(parent::checkWritePrivilege(86))) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'You are not authorised to do this operation.'
                    ]);
                }
                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Filter Values
                    |--------------------------------------------------------------------------
                    */

                    $property_id = $request->property_id;
                    $check_date = date('Y-m-d', strtotime($request->filter_date));

                    /*
                    |--------------------------------------------------------------------------
                    | Get Exact Booking Order From Page
                    |--------------------------------------------------------------------------
                    */

                    $bookingOrder = $request->booking_order ?? [];

                    /*
                    |--------------------------------------------------------------------------
                    | Hall Booking Query
                    |--------------------------------------------------------------------------
                    */

                    $query = DB::table('t_booking as tb')
                        ->join('m_hall as h', 'tb.hall_id', '=', 'h.id')
                        ->join('m_hcategory as hc', 'h.hcategory_id', '=', 'hc.id')
                        ->join('order_masters as om', 'tb.booking_id', '=', 'om.order_id')
                        ->select(
                            'om.invoice_id',
                            'om.order_id',
                            'tb.booking_date',
                            'om.customer_name',
                            'om.service_name',
                            'hc.hcategory_name as hall_type',
                            'tb.slot_type',
                            'tb.totalPrice as total_amount',
                            'om.status'
                        )
                        ->where('tb.is_deleted', 0)
                        ->where('h.is_deleted', 0);

                    /*
                    |--------------------------------------------------------------------------
                    | Property Filter
                    |--------------------------------------------------------------------------
                    */

                    if ($property_id != 0) {
                        $query->where('h.property_id', $property_id);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Date Filter
                    |--------------------------------------------------------------------------
                    */

                    if ($request->report_type == 'book_date') {
                        $query->whereDate('tb.booking_date', $check_date);
                    } else {
                        $query->whereDate('tb.start_date', $check_date);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Exact Same Order As Web Page
                    |--------------------------------------------------------------------------
                    */

                    if (is_array($bookingOrder) && count($bookingOrder) > 0) {

                        /*
                         * Create:
                         *
                         * FIELD(
                         *om.invoice_id,
                         *?, ?, ?
                         * )
                         */

                        $placeholders = implode(',', array_fill(0, count($bookingOrder), '?'));
                        $query->orderByRaw("FIELD(om.invoice_id, $placeholders)", $bookingOrder);
                    } else {

                        /*
                         * Fallback order
                         */

                        $query->orderBy('tb.booking_date', 'DESC');
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Data
                    |--------------------------------------------------------------------------
                    */

                    $bookings = $query->get();

                    /*
                    |--------------------------------------------------------------------------
                    | Documents Directory
                    |--------------------------------------------------------------------------
                    */

                    $directory = public_path('documents');
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CSV File
                    |--------------------------------------------------------------------------
                    */

                    $file = 'documents/Hall_Booking_Report_' . date('d-m-Y_h-i-a') . '.csv';
                    $filePath = public_path($file);
                    $handle = fopen($filePath, 'w');
                    if ($handle === false) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Unable to create Excel file.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UTF-8 BOM
                    |--------------------------------------------------------------------------
                    */

                    fwrite($handle, "\xEF\xBB\xBF");

                    /*
                    |--------------------------------------------------------------------------
                    | Excel Header
                    |--------------------------------------------------------------------------
                    | Same arrangement as web page
                    |--------------------------------------------------------------------------
                    */

                    fputcsv($handle, [
                        'Sl No',
                        'Booking Id',
                        'Booking Date',
                        'Customer Name',
                        'Hall Name',
                        'Hall Type',
                        'Slot',
                        'Total Amount',
                        'Booking Status'
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Excel Rows
                    |--------------------------------------------------------------------------
                    */

                    $count = 1;
                    foreach ($bookings as $row) {

                        /*
                         * Booking Date
                         */

                        $bookingDate = '';
                        if (!empty($row->booking_date)) {
                            $bookingDate = date('d-m-Y', strtotime($row->booking_date));
                        }

                        /*
                         * Slot
                         *
                         * SECOND_HALF
                         * becomes
                         * SECOND HALF
                         */

                        $slotType = strtoupper(str_replace('_', ' ', $row->slot_type ?? ''));

                        /*
                         * Status
                         */

                        $bookingStatus = ucfirst($row->status ?? '');

                        /*
                        |--------------------------------------------------------------------------
                        | Write Row
                        |--------------------------------------------------------------------------
                        */

                        fputcsv($handle, [
                            /*
                             * Sl No
                             */

                            $count++,

                            /*
                             * Booking Id
                             */

                            $row->invoice_id ?? '',

                            /*
                             * Booking Date
                             */

                            $bookingDate,

                            /*
                             * Customer Name
                             */

                            $row->customer_name ?? '',

                            /*
                             * Hall Name
                             */

                            $row->service_name ?? '',

                            /*
                             * Hall Type
                             */

                            $row->hall_type ?? '',

                            /*
                             * Slot
                             */

                            $slotType,

                            /*
                             * Total Amount
                             */

                            number_format(
                                (float) ($row->total_amount ?? 0),
                                2,
                                '.',
                                ''
                            ),

                            /*
                             * Booking Status
                             */

                            $bookingStatus
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Close CSV
                    |--------------------------------------------------------------------------
                    */

                    fclose($handle);

                    /*
                    |--------------------------------------------------------------------------
                    | Response
                    |--------------------------------------------------------------------------
                    */

                    return response()->json([
                        'status' => 1,
                        'message' => 'Excel exported successfully.',
                        'file_path' => asset($file)
                    ]);
                } catch (\Throwable $e) {

                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage() . ' | File: ' . basename($e->getFile()) . ' | Line: ' . $e
                            ->getLine()
                    ]);
                }
            } elseif ($request->request_type == 'print_booking_report') {
                if (!(parent::checkWritePrivilege(86))) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'You are not authorised to do this operation.'
                    ]);
                }
                try {
                    $property_id = $request->property_id;
                    $check_date = date('Y-m-d', strtotime($request->filter_date));

                    /*
                    |--------------------------------------------------------------------------
                    | Get Hall Booking Report Data
                    |--------------------------------------------------------------------------
                    */

                    $query = DB::table('t_booking as tb')
                        ->join('m_hall as h', 'tb.hall_id', '=', 'h.id')
                        ->join('m_hcategory as hc', 'h.hcategory_id', '=', 'hc.id')
                        ->join('order_masters as om', 'tb.booking_id', '=', 'om.order_id')
                        ->select(
                            'om.invoice_id',
                            'om.order_id',
                            'tb.booking_date',
                            'om.customer_name',
                            'om.service_name',
                            'hc.hcategory_name as hall_type',
                            // Slot directly from t_booking
                            'tb.slot_type',
                            'tb.totalPrice as total_amount',
                            'om.status'
                        )
                        ->where('tb.is_deleted', 0)
                        ->where('h.is_deleted', 0);

                    /*
                    |--------------------------------------------------------------------------
                    | Property Filter
                    |--------------------------------------------------------------------------
                    */

                    if ($property_id != 0) {
                        $query->where('h.property_id', $property_id);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Date Filter
                    |--------------------------------------------------------------------------
                    */

                    if ($request->report_type == 'book_date') {
                        $query->whereDate('tb.booking_date', $check_date);
                        $heading = 'Booking Date';
                    } else {
                        $query->whereDate('tb.start_date', $check_date);
                        $heading = 'Stay Date';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Data
                    |--------------------------------------------------------------------------
                    */

                    $bookings = $query
                        ->orderBy('tb.booking_date', 'DESC')
                        ->get();

                    /*
                    |--------------------------------------------------------------------------
                    | Build PDF HTML
                    |--------------------------------------------------------------------------
                    */

                    $html = '
                    <!DOCTYPE html>
                    <html>
                        <head>
                            <meta charset="UTF-8">
                            <style>
                                body {
                                    font-family: Arial, Helvetica, sans-serif;
                                    font-size: 11px;
                                    color: #000;
                                }

                                h3 {
                                    text-align: center;
                                    margin: 10px 0 15px;
                                    font-size: 16px;
                                }

                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                }

                                th,
                                td {
                                    border: 1px solid #000;
                                    padding: 6px 4px;
                                    text-align: center;
                                    vertical-align: middle;
                                }

                                th {
                                    font-weight: bold;
                                    background: #f2f2f2;
                                }

                                .text-left {
                                    text-align: left;
                                }

                                .text-right {
                                    text-align: right;
                                }
                            </style>
                        </head>
                        <body>
                            <h3>
                                Hall Booking Report
                                (' . $heading . ' - ' . date('d-m-Y', strtotime($check_date)) . ')
                            </h3>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Sl No</th>
                                        <th>Booking ID</th>
                                        <th>Booking Date</th>
                                        <th>Customer Name</th>
                                        <th>Hall Name</th>
                                        <th>Hall Type</th>
                                        <th>Slot</th>
                                        <th>Total Amount</th>
                                        <th>Booking Status</th>
                                    </tr>
                                </thead>
                                <tbody>';

                    /*
                    |--------------------------------------------------------------------------
                    | Report Rows
                    |--------------------------------------------------------------------------
                    */

                    $count = 1;
                    foreach ($bookings as $row) {
                        $bookingDate = '';
                        if (!empty($row->booking_date)) {
                            $bookingDate = date('d-m-Y', strtotime($row->booking_date));
                        }

                        /*
                         * Slot from t_booking.slot_type
                         *
                         * FIRST_HALF  -> FIRST HALF
                         * SECOND_HALF -> SECOND HALF
                         * FULL_DAY -> FULL DAY
                         */

                        $slotType = strtoupper(str_replace('_', ' ', $row->slot_type ?? ''));
                        $html .= '
                        <tr>
                            <td>' . $count++ . '</td>
                            <td>' . htmlspecialchars($row->invoice_id ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            <td>' . $bookingDate . '</td>
                            <td>' . htmlspecialchars($row->customer_name ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            <td>' . htmlspecialchars($row->service_name ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            <td>' . htmlspecialchars($row->hall_type ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            <td>' . htmlspecialchars($slotType, ENT_QUOTES, 'UTF-8') . '</td>
                            <td class="text-right">' . number_format((float) $row->total_amount, 2) . '</td>
                            <td>' . ucfirst($row->status ?? '') . '</td>
                        </tr>';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | No Record
                    |--------------------------------------------------------------------------
                    */

                    if ($count == 1) {
                        $html .= '
                        <tr>
                            <td colspan="9" style="text-align: center;">
                                No Record Found
                            </td>
                        </tr>';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Close HTML
                    |--------------------------------------------------------------------------
                    */

                    $html .= '
                                </tbody>
                            </table>
                        </body>
                    </html>';

                    /*
                    |--------------------------------------------------------------------------
                    | Create Documents Directory
                    |--------------------------------------------------------------------------
                    */

                    $directory = public_path('documents');
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PDF File Name
                    |--------------------------------------------------------------------------
                    */

                    $file = 'documents/Hall_Booking_Report_' . date('d-m-Y_h-i-a') . '.pdf';
                    $pdfname = public_path($file);

                    /*
                    |--------------------------------------------------------------------------
                    | Generate PDF
                    |--------------------------------------------------------------------------
                    */

                    PDF::loadHTML(html_entity_decode($html, ENT_QUOTES, 'UTF-8'))->save($pdfname);

                    /*
                    |--------------------------------------------------------------------------
                    | Response
                    |--------------------------------------------------------------------------
                    */

                    return response()->json([
                        'status' => 1,
                        'message' => 'Report generated successfully.',
                        'file_path' => asset($file)
                    ]);
                } catch (\Throwable $e) {

                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage() . ' | File: ' . basename($e->getFile()) . ' | Line: ' . $e
                            ->getLine()
                    ]);
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Export Selected Hall Property Availability With Booked
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'export_hall_availability_with_booked') {
                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Request
                    |--------------------------------------------------------------------------
                    */

                    if (empty($request->property_id)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Please select property.'
                        ]);
                    }
                    if (empty($request->filter_date)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Please select date range.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Authenticated User
                    |--------------------------------------------------------------------------
                    */

                    $user = Auth::user();
                    if (!$user) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Your session has expired. Please login again.'
                        ], 401);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Parse Date Range
                    |--------------------------------------------------------------------------
                    */

                    $range = preg_split('/\s+-\s+/', trim($request->filter_date));
                    if (!is_array($range) || count($range) != 2) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Invalid date range.'
                        ]);
                    }
                    $startDate = Carbon::createFromFormat('d-m-Y', trim($range[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('d-m-Y', trim($range[1]))->endOfDay();

                    /*
                    |--------------------------------------------------------------------------
                    | Reverse Range If Needed
                    |--------------------------------------------------------------------------
                    */

                    if ($endDate->lt($startDate)) {
                        $oldStartDate = $startDate->copy();
                        $startDate = $endDate
                            ->copy()
                            ->startOfDay();
                        $endDate = $oldStartDate
                            ->copy()
                            ->endOfDay();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Halls Accessible To Logged-in User
                    |--------------------------------------------------------------------------
                    | SAME LOGIC AS hallAvailablityReport()
                    |--------------------------------------------------------------------------
                    */

                    $hallQuery = Hall::query();
                    if ($user->access_type == 'vendor') {
                        $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
                        $hallQuery->where('created_by', $vendorId);
                    }
                    $HallList = $hallQuery
                        ->where('is_deleted', 0)
                        ->whereNotNull('property_id')
                        ->whereNotNull('hcategory_id')
                        ->select('id', 'hall_name', 'property_id', 'hcategory_id', 'created_by')
                        ->orderBy('hall_name', 'asc')
                        ->get();

                    /*
                    |--------------------------------------------------------------------------
                    | Accessible Property IDs
                    |--------------------------------------------------------------------------
                    */

                    $propertyIds = $HallList
                        ->pluck('property_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();

                    /*
                    |--------------------------------------------------------------------------
                    | Same Property List Logic As Hall Availability Report
                    |--------------------------------------------------------------------------
                    */

                    $MasterProperty = HallProperty::query()
                        ->where('is_deleted', 0)
                        ->where('status', '1')
                        ->whereIn('id', $propertyIds)
                        ->orderBy('property_name', 'asc')
                        ->pluck('property_name', 'id');

                    /*
                    |--------------------------------------------------------------------------
                    | Selected Property
                    |--------------------------------------------------------------------------
                    */

                    $PropertyId = (int) $request->property_id;
                    if (!$MasterProperty->has($PropertyId)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'You cannot access the selected property.'
                        ], 403);
                    }
                    $PropertyName = $MasterProperty->get($PropertyId, '');

                    /*
                    |--------------------------------------------------------------------------
                    | Get Halls Under Selected Property
                    |--------------------------------------------------------------------------
                    */

                    $selectedHalls = $HallList
                        ->where('property_id', $PropertyId)
                        ->values();

                    /*
                    |--------------------------------------------------------------------------
                    | Get Categories
                    |--------------------------------------------------------------------------
                    */

                    $categoryIds = $selectedHalls
                        ->pluck('hcategory_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                    $CategoryList = HallCategory::query()
                        ->where('is_deleted', 0)
                        ->whereIn('id', $categoryIds)
                        ->pluck('hcategory_name', 'id');

                    /*
                    |--------------------------------------------------------------------------
                    | Separate Conference And Convention Halls
                    |--------------------------------------------------------------------------
                    */

                    $conferenceHallIds = [];
                    $conventionHallIds = [];
                    foreach ($selectedHalls as $hall) {
                        $categoryName = strtolower(trim($CategoryList->get($hall->hcategory_id, '')));
                        if (strpos($categoryName, 'conference') !== false) {
                            $conferenceHallIds[] = (int) $hall->id;
                        }
                        if (strpos($categoryName, 'convention') !== false) {
                            $conventionHallIds[] = (int) $hall->id;
                        }
                    }
                    $conferenceHallIds = array_values(array_unique($conferenceHallIds));
                    $conventionHallIds = array_values(array_unique($conventionHallIds));

                    /*
                    |--------------------------------------------------------------------------
                    | Total Halls
                    |--------------------------------------------------------------------------
                    */

                    $conferenceTotal = count($conferenceHallIds);
                    $conventionTotal = count($conventionHallIds);
                    $selectedHallIds = array_values(array_unique(array_merge($conferenceHallIds, $conventionHallIds)));

                    /*
                    |--------------------------------------------------------------------------
                    | Get Inventory
                    |--------------------------------------------------------------------------
                    |
                    | 0 = Available
                    | 1 = Booked
                    | 2 = Blocked
                    |
                    |--------------------------------------------------------------------------
                    */

                    $inventoryByDate = collect();
                    if (!empty($selectedHallIds)) {
                        $inventoryByDate = HallMasterInventory::query()
                            ->whereIn('hall_id', $selectedHallIds)
                            ->whereBetween('inventory_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                            ->select('id', 'hall_id', 'inventory_date', 'first_half_available', 'second_half_available')
                            ->orderBy('id', 'asc')
                            ->get()
                            ->groupBy(
                                function ($inventory) {
                                    return Carbon::parse($inventory->inventory_date)->format('Y-m-d');
                                }
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare Report Data
                    |--------------------------------------------------------------------------
                    */

                    $HallReportData = [];
                    $period = CarbonPeriod::create($startDate
                        ->copy()
                        ->startOfDay(), $endDate
                            ->copy()
                            ->startOfDay());
                    foreach ($period as $date) {
                        $databaseDate = $date->format('Y-m-d');
                        $dateInventory = $inventoryByDate->get($databaseDate, collect());

                        /*
                        |--------------------------------------------------------------------------
                        | Latest Inventory Row For Each Hall
                        |--------------------------------------------------------------------------
                        | EXACT SAME LOGIC AS WEB REPORT
                        |--------------------------------------------------------------------------
                        */

                        $dateInventoryByHall = $dateInventory
                            ->groupBy('hall_id')
                            ->map(
                                function ($rows) {
                                    return $rows
                                        ->last();
                                }
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Conference Booked
                        |--------------------------------------------------------------------------
                        */

                        $conferenceBooked = 0;
                        foreach ($conferenceHallIds as $hallId) {
                            $inventory = $dateInventoryByHall->get($hallId);
                            if (!$inventory) {
                                continue;
                            }
                            $firstHalf = (int) ($inventory->first_half_available ?? 0);
                            $secondHalf = (int) ($inventory->second_half_available ?? 0);

                            /*
                             * Both halves booked
                             */

                            if ($firstHalf === 1 && $secondHalf === 1) {
                                $conferenceBooked += 1;
                            }

                            /*
                             * One half booked
                             */ elseif ($firstHalf === 1 || $secondHalf === 1) {
                                $conferenceBooked += 0.5;
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Convention Booked
                        |--------------------------------------------------------------------------
                        */

                        $conventionBooked = 0;
                        foreach ($conventionHallIds as $hallId) {
                            $inventory = $dateInventoryByHall->get($hallId);
                            if (!$inventory) {
                                continue;
                            }
                            $firstHalf = (int) ($inventory->first_half_available ?? 0);
                            $secondHalf = (int) ($inventory->second_half_available ?? 0);

                            /*
                             * Both halves booked
                             */

                            if ($firstHalf === 1 && $secondHalf === 1) {
                                $conventionBooked += 1;
                            }

                            /*
                             * One half booked
                             */ elseif ($firstHalf === 1 || $secondHalf === 1) {
                                $conventionBooked += 0.5;
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Report Row
                        |--------------------------------------------------------------------------
                        */

                        $HallReportData[] = [
                            'date' => $databaseDate,
                            'display_date' => $date->format('d-M-Y'),
                            'conference_booked' => $conferenceBooked,
                            'conference_total' => $conferenceTotal,
                            'convention_booked' => $conventionBooked,
                            'convention_total' => $conventionTotal
                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Excel
                    |--------------------------------------------------------------------------
                    */

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet
                        ->getActiveSheet();
                    $sheet->setTitle('Hall Availability');

                    /*
                    |--------------------------------------------------------------------------
                    | Heading
                    |--------------------------------------------------------------------------
                    */

                    $sheet->mergeCells('A1:C1');
                    $sheet->setCellValue('A1', 'Availability status of ' . $PropertyName . ' as on (' . date('d-m-Y h:i a') . ')');

                    /*
                    |--------------------------------------------------------------------------
                    | Headers
                    |--------------------------------------------------------------------------
                    */

                    $sheet->setCellValue('A2', 'Date');
                    $sheet->setCellValue('B2', 'Convention Hall (Booked / Total)');
                    $sheet->setCellValue('C2', 'Conference Hall (Booked / Total)');

                    /*
                    |--------------------------------------------------------------------------
                    | Excel Rows
                    |--------------------------------------------------------------------------
                    */

                    $excelRow = 3;
                    foreach ($HallReportData as $row) {

                        /*
                         * Convention
                         */

                        $conventionBooked = $row['convention_booked'];
                        $conventionDisplay = $conventionBooked == floor($conventionBooked) ? number_format(
                            $conventionBooked,
                            0
                        ) : number_format($conventionBooked, 1);

                        /*
                         * Conference
                         */

                        $conferenceBooked = $row['conference_booked'];
                        $conferenceDisplay = $conferenceBooked == floor($conferenceBooked) ? number_format(
                            $conferenceBooked,
                            0
                        ) : number_format($conferenceBooked, 1);

                        /*
                         * Date
                         */

                        $sheet->setCellValueByColumnAndRow(1, $excelRow, $row['display_date']);

                        /*
                         * Convention
                         */

                        $sheet->setCellValueByColumnAndRow(2, $excelRow, $conventionDisplay . ' / ' . $row['convention_total']);

                        /*
                         * Conference
                         */

                        $sheet->setCellValueByColumnAndRow(3, $excelRow, $conferenceDisplay . ' / ' . $row['conference_total']);
                        $excelRow++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Styling
                    |--------------------------------------------------------------------------
                    */

                    $lastRow = $sheet
                        ->getHighestRow();
                    $sheet
                        ->getStyle('A1:C2')
                        ->getFont()
                        ->setBold(true);
                    $sheet
                        ->getStyle('A1:C' . $lastRow)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet
                        ->getStyle('A1:C' . $lastRow)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    foreach (['A', 'B', 'C'] as $column) {
                        $sheet
                            ->getColumnDimension($column)
                            ->setAutoSize(true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Documents Directory
                    |--------------------------------------------------------------------------
                    */

                    $directory = public_path('documents');
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | File Name
                    |--------------------------------------------------------------------------
                    */

                    $safePropertyName = preg_replace('/[^A-Za-z0-9_-]/', '_', $PropertyName);
                    $fileName = 'documents/Hall_Availability_With_Booked_' . $safePropertyName . '_' . date('d-m-Y_h-i-s') . '.xlsx';

                    /*
                    |--------------------------------------------------------------------------
                    | Save Excel
                    |--------------------------------------------------------------------------
                    */

                    $writer = new Xlsx($spreadsheet);
                    $writer->save(public_path($fileName));

                    /*
                    |--------------------------------------------------------------------------
                    | Response
                    |--------------------------------------------------------------------------
                    */

                    return response()->json([
                        'status' => 1,
                        'message' => 'Hall availability exported successfully.',
                        'file_path' => asset($fileName)
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Hall Availability With Booked Export Error', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage()
                    ], 500);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Export All Hall Availability
            |--------------------------------------------------------------------------
            */ elseif ($request->request_type == 'export_all_hall_availability') {
                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Date Range
                    |--------------------------------------------------------------------------
                    */

                    if (empty($request->filter_date)) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Please select date range.'
                        ]);
                    }
                    $range = preg_split('/\s+-\s+/', trim($request->filter_date));
                    if (!is_array($range) || count($range) != 2) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Invalid date range.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare Date Range
                    |--------------------------------------------------------------------------
                    */

                    $startDate = Carbon::createFromFormat('d-m-Y', trim($range[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('d-m-Y', trim($range[1]))->endOfDay();

                    /*
                    |--------------------------------------------------------------------------
                    | Reverse Date Range If Needed
                    |--------------------------------------------------------------------------
                    */

                    if ($endDate->lt($startDate)) {
                        $oldStartDate = $startDate->copy();
                        $startDate = $endDate
                            ->copy()
                            ->startOfDay();
                        $endDate = $oldStartDate
                            ->copy()
                            ->endOfDay();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Logged-in User
                    |--------------------------------------------------------------------------
                    */

                    $user = Auth::user();
                    if (!$user) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'Your session has expired. Please login again.'
                        ], 401);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Accessible Halls
                    |--------------------------------------------------------------------------
                    | Same logic as Hall Availability Report
                    |--------------------------------------------------------------------------
                    */

                    $hallQuery = Hall::query();
                    if ($user->access_type == 'vendor') {
                        $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
                        $hallQuery->where('created_by', $vendorId);
                    }
                    $HallList = $hallQuery
                        ->where('is_deleted', 0)
                        ->whereNotNull('property_id')
                        ->whereNotNull('hcategory_id')
                        ->select('id', 'hall_name', 'property_id', 'hcategory_id', 'created_by')
                        ->orderBy('hall_name', 'asc')
                        ->get();

                    /*
                    |--------------------------------------------------------------------------
                    | Get Property IDs From Accessible Halls
                    |--------------------------------------------------------------------------
                    */

                    $propertyIds = $HallList
                        ->pluck('property_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();

                    /*
                    |--------------------------------------------------------------------------
                    | Get Properties
                    |--------------------------------------------------------------------------
                    | Same property filtering as Hall Availability Report
                    |--------------------------------------------------------------------------
                    */

                    $MasterProperty = HallProperty::query()
                        ->where('is_deleted', 0)
                        ->where('status', '1')
                        ->whereIn('id', $propertyIds)
                        ->orderBy('property_name', 'asc')
                        ->pluck('property_name', 'id');
                    if ($MasterProperty->isEmpty()) {
                        return response()->json([
                            'status' => 0,
                            'message' => 'No hall property found.'
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Get Hall Categories
                    |--------------------------------------------------------------------------
                    */

                    $categoryIds = $HallList
                        ->pluck('hcategory_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();
                    $CategoryList = HallCategory::query()
                        ->where('is_deleted', 0)
                        ->whereIn('id', $categoryIds)
                        ->pluck('hcategory_name', 'id');

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare Property => Hall IDs
                    |--------------------------------------------------------------------------
                    |
                    | We keep one Excel column per property.
                    |
                    | The total is:
                    | Conference halls + Convention halls
                    |
                    |--------------------------------------------------------------------------
                    */

                    $propertyHallData = [];
                    $allSelectedHallIds = [];
                    foreach ($MasterProperty as $propertyId => $propertyName) {

                        /*
                         * Halls belonging to current property
                         */

                        $selectedHalls = $HallList
                            ->where('property_id', $propertyId)
                            ->values();
                        $conferenceHallIds = [];
                        $conventionHallIds = [];

                        /*
                         * Separate halls according to category
                         */

                        foreach ($selectedHalls as $hall) {
                            $categoryName = strtolower(trim($CategoryList->get($hall->hcategory_id, '')));

                            /*
                             * Conference
                             */

                            if (strpos($categoryName, 'conference') !== false) {
                                $conferenceHallIds[] = (int) $hall->id;
                            }

                            /*
                             * Convention
                             */

                            if (strpos($categoryName, 'convention') !== false) {
                                $conventionHallIds[] = (int) $hall->id;
                            }
                        }

                        /*
                         * Remove duplicate IDs
                         */

                        $conferenceHallIds = array_values(array_unique($conferenceHallIds));
                        $conventionHallIds = array_values(array_unique($conventionHallIds));

                        /*
                         * Merge Conference + Convention halls
                         */

                        $selectedHallIds = array_values(array_unique(array_merge($conferenceHallIds, $conventionHallIds)));

                        /*
                         * Total halls for this property
                         */

                        $propertyTotal = count($conferenceHallIds) + count($conventionHallIds);

                        /*
                         * Store property data
                         */

                        $propertyHallData[(int) $propertyId] = [
                            'property_name' => $propertyName,
                            'hall_ids' => $selectedHallIds,
                            'total' => $propertyTotal
                        ];

                        /*
                         * Add to global hall IDs
                         */

                        $allSelectedHallIds = array_merge($allSelectedHallIds, $selectedHallIds);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Unique Hall IDs
                    |--------------------------------------------------------------------------
                    */

                    $allSelectedHallIds = array_values(array_unique($allSelectedHallIds));

                    /*
                    |--------------------------------------------------------------------------
                    | Get Inventory
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    |
                    | 0 = Available
                    | 1 = Booked
                    | 2 = Blocked
                    |
                    | Only 1 is counted as booked.
                    |
                    |--------------------------------------------------------------------------
                    */

                    $inventoryByDate = collect();
                    if (!empty($allSelectedHallIds)) {
                        $inventoryByDate = HallMasterInventory::query()
                            ->whereIn('hall_id', $allSelectedHallIds)
                            ->whereBetween('inventory_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                            ->select('id', 'hall_id', 'inventory_date', 'first_half_available', 'second_half_available')
                            /*
                             * Same logic as Hall Availability Report.
                             * Last row will be treated as latest row.
                             */

                            ->orderBy('id', 'asc')
                            ->get()
                            /*
                             * Group inventory date-wise
                             */

                            ->groupBy(
                                function ($inventory) {
                                    return Carbon::parse($inventory->inventory_date)->format('Y-m-d');
                                }
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Spreadsheet
                    |--------------------------------------------------------------------------
                    */

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet
                        ->getActiveSheet();
                    $sheet->setTitle('All Hall Availability');

                    /*
                    |--------------------------------------------------------------------------
                    | Total Excel Columns
                    |--------------------------------------------------------------------------
                    |
                    | 1 Date column
                    | +
                    | 1 column for each property
                    |
                    |--------------------------------------------------------------------------
                    */

                    $totalColumns = $MasterProperty->count() + 1;

                    /*
                    |--------------------------------------------------------------------------
                    | Main Heading
                    |--------------------------------------------------------------------------
                    */

                    $sheet->mergeCellsByColumnAndRow(1, 1, $totalColumns, 1);
                    $sheet->setCellValueByColumnAndRow(1, 1, 'All Hall Availability Report (' . $startDate->format('d-m-Y') . ' - ' . $endDate
                        ->format('d-m-Y') . ')');

                    /*
                    |--------------------------------------------------------------------------
                    | Date Header
                    |--------------------------------------------------------------------------
                    */

                    $sheet->setCellValueByColumnAndRow(1, 2, 'Date');

                    /*
                    |--------------------------------------------------------------------------
                    | Property Headers
                    |--------------------------------------------------------------------------
                    |
                    | Previous arrangement is preserved:
                    |
                    | Date
                    | Orchid (Booked / Total)
                    | Emerald Banquet Hall (Booked / Total)
                    |
                    |--------------------------------------------------------------------------
                    */

                    $column = 2;
                    foreach ($MasterProperty as $propertyId => $propertyName) {
                        $sheet->setCellValueByColumnAndRow($column, 2, $propertyName . ' (Booked / Total)');
                        $column++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare Date Period
                    |--------------------------------------------------------------------------
                    */

                    $period = CarbonPeriod::create($startDate
                        ->copy()
                        ->startOfDay(), $endDate
                            ->copy()
                            ->startOfDay());

                    /*
                    |--------------------------------------------------------------------------
                    | Excel Data Rows
                    |--------------------------------------------------------------------------
                    */

                    $excelRow = 3;
                    foreach ($period as $date) {
                        $databaseDate = $date->format('Y-m-d');

                        /*
                         * Write Date
                         */

                        $sheet->setCellValueByColumnAndRow(1, $excelRow, $date->format('d-m-Y'));

                        /*
                        |--------------------------------------------------------------------------
                        | Inventory For Current Date
                        |--------------------------------------------------------------------------
                        */

                        $dateInventory = $inventoryByDate->get($databaseDate, collect());

                        /*
                        |--------------------------------------------------------------------------
                        | IMPORTANT FIX
                        |--------------------------------------------------------------------------
                        |
                        | Same as Hall Availability Report:
                        |
                        | Group by hall_id
                        | and take only the latest inventory row.
                        |
                        | Because inventory query is ordered by ID ASC,
                        | last() gives latest inventory record.
                        |
                        |--------------------------------------------------------------------------
                        */

                        $dateInventoryByHall = $dateInventory
                            ->groupBy('hall_id')
                            ->map(
                                function ($rows) {
                                    return $rows
                                        ->last();
                                }
                            );

                        /*
                         * Start property columns
                         */

                        $column = 2;

                        /*
                        |--------------------------------------------------------------------------
                        | Calculate Each Property
                        |--------------------------------------------------------------------------
                        */

                        foreach ($propertyHallData as $propertyId => $propertyData) {

                            /*
                             * Start booked count
                             */

                            $booked = 0;

                            /*
                            |--------------------------------------------------------------------------
                            | Check Each Hall Under Property
                            |--------------------------------------------------------------------------
                            */

                            foreach ($propertyData['hall_ids'] as $hallId) {

                                /*
                                 * Get latest inventory
                                 * for current hall/current date
                                 */

                                $inventory = $dateInventoryByHall
                                    ->get($hallId);

                                /*
                                 * No inventory means no booked count
                                 */

                                if (!$inventory) {
                                    continue;
                                }

                                /*
                                 * First Half Status
                                 */

                                $firstHalf = (int) ($inventory->first_half_available ?? 0);

                                /*
                                 * Second Half Status
                                 */

                                $secondHalf = (int) ($inventory->second_half_available ?? 0);

                                /*
                                |--------------------------------------------------------------------------
                                | Same Booked Calculation As Hall Availability Report
                                |--------------------------------------------------------------------------
                                |
                                | first = 1 and second = 1
                                | Full booked = 1
                                |
                                |--------------------------------------------------------------------------
                                */

                                if ($firstHalf === 1 && $secondHalf === 1) {
                                    $booked += 1;
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | Only One Half Is Booked
                                |--------------------------------------------------------------------------
                                |
                                | first = 1
                                | OR
                                | second = 1
                                |
                                | Half booked = 0.5
                                |
                                | If other half is blocked (2),
                                | it is still only 0.5 booked.
                                |
                                |--------------------------------------------------------------------------
                                */ elseif ($firstHalf === 1 || $secondHalf === 1) {
                                    $booked += 0.5;
                                }

                                /*
                                 * 0 = Available
                                 * 2 = Blocked
                                 *
                                 * Neither is counted as booked.
                                 */
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Total Hall Count
                            |--------------------------------------------------------------------------
                            */

                            $total = $propertyData['total'];

                            /*
                            |--------------------------------------------------------------------------
                            | Format Booked Value
                            |--------------------------------------------------------------------------
                            |
                            | 0=> 0
                            | 1=> 1
                            | 0.5 => 0.5
                            | 1.5 => 1.5
                            |
                            |--------------------------------------------------------------------------
                            */

                            if (floor($booked) == $booked) {
                                $bookedDisplay = (int) $booked;
                            } else {
                                $bookedDisplay = number_format($booked, 1);
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Write Booked / Total
                            |--------------------------------------------------------------------------
                            |
                            | Examples:
                            |
                            | 0 / 4
                            | 1 / 4
                            | 0.5 / 4
                            |
                            |--------------------------------------------------------------------------
                            */

                            $sheet->setCellValueByColumnAndRow($column, $excelRow, $bookedDisplay . ' / ' . $total);
                            $column++;
                        }

                        /*
                         * Next Excel Row
                         */

                        $excelRow++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Excel Styling
                    |--------------------------------------------------------------------------
                    */

                    $lastColumn = $sheet->getHighestColumn();
                    $lastRow = $sheet->getHighestRow();

                    /*
                     * Bold Heading + Header
                     */

                    $sheet
                        ->getStyle('A1:' . $lastColumn . '2')
                        ->getFont()
                        ->setBold(true);

                    /*
                     * Center Alignment
                     */

                    $sheet
                        ->getStyle('A1:' . $lastColumn . $lastRow)
                        ->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                    /*
                     * Borders
                     */

                    $sheet
                        ->getStyle('A1:' . $lastColumn . $lastRow)
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                    /*
                    |--------------------------------------------------------------------------
                    | Auto Size Columns
                    |--------------------------------------------------------------------------
                    */

                    for ($columnIndex = 1; $columnIndex <= $totalColumns; $columnIndex++) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                        $sheet
                            ->getColumnDimension($columnLetter)
                            ->setAutoSize(true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Documents Directory
                    |--------------------------------------------------------------------------
                    */

                    $directory = public_path('documents');
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Excel File Name
                    |--------------------------------------------------------------------------
                    */

                    $fileName = 'documents/All_Hall_Availability_' . date('d-m-Y_h-i-s') . '.xlsx';

                    /*
                    |--------------------------------------------------------------------------
                    | Save Excel
                    |--------------------------------------------------------------------------
                    */

                    $writer = new Xlsx($spreadsheet);
                    $writer->save(public_path($fileName));

                    /*
                    |--------------------------------------------------------------------------
                    | Return Download URL
                    |--------------------------------------------------------------------------
                    */

                    return response()->json([
                        'status' => 1,
                        'message' => 'All hall availability exported successfully.',
                        'file_path' => asset($fileName)
                    ]);
                } catch (\Throwable $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Log Error
                    |--------------------------------------------------------------------------
                    */

                    \Log::error('Export All Hall Availability Error', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Error Response
                    |--------------------------------------------------------------------------
                    */

                    return response()->json([
                        'status' => 0,
                        'message' => $e->getMessage()
                    ], 500);
                }
            } else {
                $response['status'] = 0;
                $response['message'] = 'Invalid request type.';
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
        }
        return response()->json($response);
    }

    /**
     * Display hall room management screen.
     *
     * Decrypts the hall identifier and loads hall details
     * required for room configuration.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */

    public function manageHallRooms(Request $request)
    {
        try {
            $propertyId = Crypt::decryptString($request->id);
        } catch (DecryptException $e) {
            return redirect()->back()->with('error', 'Invalid Hall ID');
        }

        $HallAttributes = HallAttribute::with([
            'facilities' => function ($query) {
                $query->active()
                    ->hall()
                    ->orderBy('facility_name', 'asc');
            }
        ])
            ->active()
            ->where('status', '1')
            ->orderBy('attribute_name', 'asc')
            ->get();

        $hallCategories = HallCategory::where('status', '1')
            ->where('is_deleted', '0')
            ->orderBy('hcategory_name', 'asc')
            ->get();

        $PropertyDetails = HallProperty::find($propertyId);

        if (!$PropertyDetails) {
            return redirect()->back()->with('error', 'Hall Property not found');
        }

        $HotelRooms = Hall::where('property_id', $propertyId)
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($HotelRooms as $room) {
            $room->full_day_price = DB::table('m_slot')
                ->where('hall_id', $room->id)
                ->where('booking_type', 'FULL_DAY')
                ->where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->value('price');

            $room->half_day_price = DB::table('m_slot')
                ->where('hall_id', $room->id)
                ->where('booking_type', 'HALF_DAY')
                ->where('is_deleted', 0)
                ->orderBy('id', 'desc')
                ->value('price');
        }

        return view(
            'hall.manage-hall-rooms',
            compact(
                'propertyId',
                'PropertyDetails',
                'HotelRooms',
                'HallAttributes',
                'hallCategories'
            )
        );
    }

    public function addHallRooms(Request $request)
    {
        if (!(parent::checkWritePrivilege(7))) {
            Session::flash('error', 'You are not authorized to do this operation.');
            return redirect()->back();
        }

        $validate = Validator::make($request->all(), [
            'property_id' => 'required|exists:m_property,id',
            'title' => 'required|string|max:50',
            'hall_type' => 'required|integer|exists:m_hcategory,id',
            'hall_capacity' => 'required|numeric|min:1',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'full_day_price' => 'nullable|numeric|min:0',
            'half_day_price' => 'nullable|numeric|min:0',
            'status' => 'required',
        ]);

        if ($validate->fails()) {
            return Redirect::back()
                ->withErrors($validate)
                ->withInput();
        }

        $uploadDir = public_path('images/hall-room');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        DB::beginTransaction();

        try {
            $featureImage = '';

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_feature_' . $file->getClientOriginalName();
                $file->move($uploadDir, $fileName);
                $featureImage = 'images/hall-room/' . $fileName;
            }

            $galleryImages = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->move($uploadDir, $fileName);
                    $galleryImages[] = 'images/hall-room/' . $fileName;
                }
            }

            $publishStatus = ($request->status == 'publish') ? 'PUBLISH' : 'DRAFT';
            $status = ($request->status == 'publish') ? '1' : '0';

            $hallRoom = Hall::create([
                'hall_name' => $request->title,
                'slug' => Str::slug($request->title) . '-' . uniqid(),
                'hcategory_id' => $request->hall_type,
                'property_id' => $request->property_id,
                'hfacilities_id' => $request->filled('facilities')
                    ? implode(',', $request->facilities)
                    : null,
                'room_capacity' => $request->hall_capacity,
                'quantity' => 1,
                'feature_image' => $featureImage,
                'gallery' => json_encode($galleryImages),
                'publish_status' => $publishStatus,
                'status' => $status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'is_deleted' => 0,
            ]);

            DB::table('m_slot')
                ->where('hall_id', $hallRoom->id)
                ->delete();

            if ($request->filled('full_day_price')) {
                DB::table('m_slot')->insert([
                    'hall_id' => $hallRoom->id,
                    'booking_type' => 'FULL_DAY',
                    'slot' => 'FULL_DAY',
                    'time' => null,
                    'price' => $request->full_day_price,
                    'status' => '1',
                    'created_by' => Auth::id(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::id(),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_deleted' => 0,
                ]);
            }

            if ($request->filled('half_day_price')) {
                DB::table('m_slot')->insert([
                    'hall_id' => $hallRoom->id,
                    'booking_type' => 'HALF_DAY',
                    'slot' => 'HALF_DAY',
                    'time' => null,
                    'price' => $request->half_day_price,
                    'status' => '1',
                    'created_by' => Auth::id(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::id(),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'is_deleted' => 0,
                ]);
            }

            $this->setHallId($hallRoom->id);
            $this->setPropertyId($request->property_id);

            $isInventoryCreated = $this->addInventoryNext90Days(
                1,
                new HallMasterInventory()
            );

            if (!$isInventoryCreated) {
                DB::rollBack();
                Session::flash('error', 'Hall room created but failed to create inventory.');
                return redirect()->back();
            }

            DB::commit();

            Session::flash('success', 'Hall room added successfully.');

            return Redirect::to(
                'manage-hall-rooms/' . Crypt::encryptString($request->property_id)
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Session::flash(
                'error',
                'An error occurred while adding hall room: ' . $e->getMessage()
            );

            return redirect()->back();
        }
    }

    /**
     * Display the edit form for a hall room.
     *
     * Decrypts the provided room id, loads the room record plus related
     * attributes/facilities/categories, and fetches current FULL_DAY and
     * HALF_DAY prices to pre-fill the edit UI.
     *
     * @param mixed $id Encrypted hall room identifier.
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * @author :- Esha Pandey
     */
    public function editHallRoom($id)
    {
        try {
            $roomId = Crypt::decryptString($id);
        } catch (DecryptException $e) {
            return redirect()->back()->with('error', 'Invalid Room ID');
        }

        $RoomDetails = Hall::find($roomId);

        if (!$RoomDetails) {
            return redirect()->back()->with('error', 'Room not found');
        }

        $HallAttributes = HallAttribute::with([
            'facilities' => function ($query) {
                $query->active()
                    ->hall()
                    ->orderBy('facility_name', 'asc');
            }
        ])
            ->active()
            ->orderBy('attribute_name', 'asc')
            ->get();

        $hallCategories = HallCategory::where('status', '1')
            ->where('is_deleted', '0')
            ->orderBy('hcategory_name', 'asc')
            ->get();

        $fullDayPrice = DB::table('m_slot')
            ->where('hall_id', $roomId)
            ->where('booking_type', 'FULL_DAY')
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->value('price');

        $halfDayPrice = DB::table('m_slot')
            ->where('hall_id', $roomId)
            ->where('booking_type', 'HALF_DAY')
            ->where('is_deleted', 0)
            ->orderBy('id', 'desc')
            ->value('price');

        $selectedFacilities = [];

        if (!empty($RoomDetails->hfacilities_id)) {
            $selectedFacilities = explode(',', $RoomDetails->hfacilities_id);
        }

        return view(
            'hall.edit-property-hall',
            compact(
                'RoomDetails',
                'HallAttributes',
                'hallCategories',
                'fullDayPrice',
                'halfDayPrice',
                'selectedFacilities'
            )
        );
    }

    /**
     * Update an existing hall room.
     *
     * Validates incoming request data, updates hall room core fields,
     * handles feature/galleries image replacement and deletion,
     * and refreshes FULL_DAY / HALF_DAY slot pricing records.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @author :- Esha Pandey
     */
    public function updateHallRoom(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'room_id' => 'required',
            'title' => 'required|string|max:50',
            'hall_type' => 'required|integer|exists:m_hcategory,id',
            'hall_capacity' => 'required|numeric|min:1',
            'full_day_price' => 'nullable|numeric|min:0',
            'half_day_price' => 'nullable|numeric|min:0',
            'status' => 'required',
        ]);

        if ($validate->fails()) {
            return redirect()->back()
                ->withErrors($validate)
                ->withInput();
        }

        try {
            $roomId = Crypt::decryptString($request->room_id);
        } catch (DecryptException $e) {
            return redirect()->back()->with('error', 'Invalid Room ID');
        }

        $hallRoom = Hall::where('id', $roomId)
            ->where('is_deleted', 0)
            ->first();

        if (!$hallRoom) {
            return redirect()->back()->with('error', 'Room not found');
        }

        DB::beginTransaction();

        try {
            $uploadDir = public_path('images/hall-room');

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            /*
            |--------------------------------------------------------------------------
            | Feature Image
            |--------------------------------------------------------------------------
            */

            $featureImage = $hallRoom->feature_image;

            // User removed existing image and did not upload a new one
            if ($request->feature_removed == '1' && !$request->hasFile('image')) {
                if (!empty($featureImage) && file_exists(public_path($featureImage))) {
                    @unlink(public_path($featureImage));
                }

                $featureImage = null;
            }

            // User uploaded a new feature image
            if ($request->hasFile('image')) {
                if (
                    !empty($hallRoom->feature_image) &&
                    file_exists(public_path($hallRoom->feature_image))
                ) {
                    @unlink(public_path($hallRoom->feature_image));
                }

                $file = $request->file('image');
                $fileName = time() . '_feature_' . uniqid() . '.' . $file->getClientOriginalExtension();

                $file->move($uploadDir, $fileName);
                $featureImage = 'images/hall-room/' . $fileName;
            }

            /*
            |--------------------------------------------------------------------------
            | Gallery Images
            |--------------------------------------------------------------------------
            */

            $galleryImages = [];

            // Keep preloaded images
            if ($request->filled('old')) {
                foreach ($request->old as $image) {
                    $galleryImages[] = 'images/hall-room/' . $image;
                }
            }

            // Upload new gallery images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    $file->move($uploadDir, $fileName);
                    $galleryImages[] = 'images/hall-room/' . $fileName;
                }
            }

            // Delete removed images
            $oldGallery = json_decode($hallRoom->gallery, true);

            if (!is_array($oldGallery)) {
                $oldGallery = [];
            }

            foreach ($oldGallery as $oldImage) {
                if (!in_array($oldImage, $galleryImages)) {
                    $path = public_path($oldImage);

                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Hall
            |--------------------------------------------------------------------------
            */

            $slug = Str::slug($request->title) . '-' . uniqid();
            $publishStatus = ($request->status == 'publish') ? 'PUBLISH' : 'DRAFT';
            $status = ($request->status == 'publish') ? '1' : '0';

            DB::table('m_hall')
                ->where('id', $roomId)
                ->update([
                    'hall_name' => $request->title,
                    'slug' => $slug,
                    'hcategory_id' => $request->hall_type,
                    'hfacilities_id' => $request->filled('facilities')
                        ? implode(',', $request->facilities)
                        : null,
                    'room_capacity' => $request->hall_capacity,
                    'quantity' => 1,
                    'feature_image' => $featureImage,
                    'gallery' => json_encode($galleryImages),
                    'publish_status' => $publishStatus,
                    'status' => $status,
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Update FULL DAY Slot
            |--------------------------------------------------------------------------
            */

            if ($request->filled('full_day_price')) {
                DB::table('m_slot')
                    ->where('hall_id', $roomId)
                    ->where('booking_type', 'FULL_DAY')
                    ->update([
                        'price' => $request->full_day_price,
                        'status' => '1',
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Update HALF DAY Slot
            |--------------------------------------------------------------------------
            */

            if ($request->filled('half_day_price')) {
                DB::table('m_slot')
                    ->where('hall_id', $roomId)
                    ->where('booking_type', 'HALF_DAY')
                    ->update([
                        'price' => $request->half_day_price,
                        'status' => '1',
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            Session::flash('success', 'Hall room updated successfully.');

            return Redirect::to(
                'manage-hall-rooms/' . Crypt::encryptString($hallRoom->property_id)
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Session::flash(
                'error',
                'An error occurred while updating hall room: ' . $e->getMessage()
            );

            return redirect()->back()->withInput();
        }
    }


    /**
     * Display the create hall form.
     *
     * Loads vendors, hall attributes, facilities,
     * and district information.
     * @author :- Esha Pandey
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */

    public function addNewHall()
    {
        if (!(parent::checkWritePrivilege(7))) {
            Session::flash('error', 'You are not authorized to do this operation.');
            return redirect()->back();
        }

        $Vendors = User::where('role', '2')
            ->orderBy('company', 'asc')
            ->pluck('company', 'id');

        $HallAttributes = HallAttribute::with([
            'facilities' => function ($query) {
                $query->active()
                    ->hall()
                    ->orderBy('facility_name', 'asc');
            }
        ])
            ->active()
            ->where('status', '1')
            ->orderBy('attribute_name', 'asc')
            ->get();

        $Districts = City::where('state_id', Auth::user()->state)
            ->orderBy('name', 'asc')
            ->get();

        return view(
            'hall.add-new-hall',
            compact('Vendors', 'HallAttributes', 'Districts')
        );
    }

    /* *********************************************************************************************************************
     * @author : Sadyasnata Patasani
     * @date : 11/06/2026
     * Description : This function validates and stores new hall property details in the m_property table. It handles banner image, feature image, gallery uploads, hall content, location details, contact information, GST details, and publish status.
     * @param Request $request - The submitted hall creation form data.
     ************************************************************************************************************************ */

    public function addNewHallRequest(Request $request)
    {
        if (!(parent::checkWritePrivilege(7))) {
            Session::flash('error', 'You are not authorized to do this operation.');
            return redirect()->back();
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'contents' => 'required',
            'terms_conditions' => 'required',
            'district_id' => 'required|numeric',
            'place' => 'required|string|max:100',
            'contact_number' => 'required|max:10',
            'reception_contact' => 'required|max:10',
            'banner_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'feature_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validate->fails()) {
            return Redirect::to('add-new-hall')
                ->withErrors($validate)
                ->withInput();
        }

        $uploadDir = public_path('images/hall');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $bannerImage = '';

        if ($request->hasFile('banner_image')) {
            $file = $request->file('banner_image');
            $fileName = $file->getClientOriginalName();
            $file->move($uploadDir, $fileName);
            $bannerImage = 'images/hall/' . $fileName;
        }

        $featureImage = '';

        if ($request->hasFile('feature_image')) {
            $file = $request->file('feature_image');
            $fileName = $file->getClientOriginalName();
            $file->move($uploadDir, $fileName);
            $featureImage = 'images/hall/' . $fileName;
        }

        $galleryImages = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $fileName = $file->getClientOriginalName();
                $file->move($uploadDir, $fileName);
                $galleryImages[] = 'images/hall/' . $fileName;
            }
        }

        $slug = Str::slug($request->name) . '-' . uniqid();
        $publishStatus = ($request->status == 'publish') ? 'PUBLISH' : 'DRAFT';
        $status = ($request->status == 'publish') ? '1' : '0';

        HallProperty::create([
            'property_name' => $request->name,
            'vender_id' => '1',
            'slug' => $slug,
            'hall_capacity' => $request->hall_capacity,
            'content' => $request->contents,
            'youtube_video' => $request->video,
            'banner_image' => $bannerImage,
            'gallery' => json_encode($galleryImages),
            'terms_condition' => $request->terms_conditions,
            'district_id' => $request->district_id,
            'place' => $request->place,
            'address' => $request->address,
            'contact_email' => $request->contact_email,
            'manager_name' => $request->manager_name,
            'manager_contact' => $request->contact_number,
            'reception_contact' => $request->reception_contact,
            'additional_email' => $request->additional_email,
            'additional_contact' => $request->additional_phone,
            'hotel_address' => $request->real_address,
            'publish_status' => $publishStatus,
            'feature_image' => $featureImage,
            'gst_applicable' => $request->gst_applicable,
            'gst_number' => $request->gst_number,
            'company_name' => $request->gst_legal_name,
            'status' => $status,
            'created_by' => Auth::user()->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_by' => Auth::user()->id,
            'updated_at' => date('Y-m-d H:i:s'),
            'is_deleted' => 0,
        ]);

        Session::flash('success', 'Property added successfully.');

        return Redirect::to('all-hall');
    }

    /**
     * Display the hall edit form.
     *
     * Loads hall details, images, gallery information,
     * vendors and district data for editing.
     * @author :- Esha Pandey
     *
     * @param string $id Encrypted hall identifier
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */

    public function editHall($id)
    {
        $id = Crypt::decryptString($id);

        if (!parent::checkWritePrivilege(7)) {
            Session::flash('success', 'You are not authorised to do this operation.');
            return redirect()->back();
        }

        $HallDetails = HallProperty::find($id);

        if (!$HallDetails) {
            Session::flash('success', 'Hall not found.');
            return redirect()->back();
        }

        $HallDetails->feature_image = !empty($HallDetails->feature_image)
            ? asset($HallDetails->feature_image)
            : '';

        /*
        |--------------------------------------------------------------------------
        | Banner Image
        |--------------------------------------------------------------------------
        */

        $HallDetails->banner_image = !empty($HallDetails->banner_image)
            ? asset($HallDetails->banner_image)
            : '';

        /*
        |--------------------------------------------------------------------------
        | Gallery Images
        |--------------------------------------------------------------------------
        */

        $gallery = [];

        if (!empty($HallDetails->gallery)) {
            $galleryImages = json_decode($HallDetails->gallery, true);

            if (is_array($galleryImages)) {
                foreach ($galleryImages as $key => $image) {
                    $gallery[] = [
                        'id' => $key + 1,
                        'src' => asset($image)
                    ];
                }
            }
        }

        $gallery = json_encode($gallery);

        /*
        |--------------------------------------------------------------------------
        | Vendors
        |--------------------------------------------------------------------------
        */

        $Vendors = User::where('role', 2)
            ->orderBy('company', 'ASC')
            ->pluck('company', 'id');

        $Districts = City::where('state_id', Auth::user()->state)
            ->orderBy('name', 'asc')
            ->get();

        return view(
            'hall.edit-hall',
            compact('HallDetails', 'Districts', 'Vendors', 'gallery')
        );
    }

    /**
     * Update an existing hall.
     *
     * Validates request data, updates hall information,
     * manages image replacement, and persists changes.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function hallEditRequest(Request $request)
    {
        try {
            $id = Crypt::decryptString($request->id);
        } catch (DecryptException $e) {
            return redirect()->back()->with('success', 'Invalid Hall ID');
        }

        $hall = HallProperty::find($id);

        if (!$hall) {
            return redirect()->back()->with('success', 'Hall not found');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'content' => 'required',
            'terms_conditions' => 'required',
            'district_id' => 'required|numeric',
            'place' => 'required|string|max:100',
            'contact_number' => 'required|max:10',
            'reception_contact' => 'required|max:10',
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'feature_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Basic Information
        |--------------------------------------------------------------------------
        */

        $hall->property_name = $request->name;
        $hall->content = $request->content;
        $hall->youtube_video = $request->video;
        $hall->terms_condition = $request->terms_conditions;
        $hall->vender_id = '1';

        $slug = Str::slug($request->name) . '-' . uniqid();
        $hall->slug = $slug;

        /*
        |--------------------------------------------------------------------------
        | Location
        |--------------------------------------------------------------------------
        */

        $hall->district_id = $request->district_id;
        $hall->place = $request->place;
        $hall->address = $request->address;

        /*
        |--------------------------------------------------------------------------
        | Contact Information
        |--------------------------------------------------------------------------
        */

        $hall->contact_email = $request->contact_email;
        $hall->manager_name = $request->manager_name;
        $hall->manager_contact = $request->contact_number;
        $hall->reception_contact = $request->reception_contact;
        $hall->additional_email = $request->additional_email;
        $hall->additional_contact = $request->additional_phone;
        $hall->hotel_address = $request->real_address;

        /*
        |--------------------------------------------------------------------------
        | GST
        |--------------------------------------------------------------------------
        */

        $hall->gst_applicable = $request->gst_applicable;
        // $hall->gst_applicable = $request->show_price ?? 0;
        $hall->gst_number = $request->gst_number;
        $hall->company_name = $request->gst_legal_name;

        /*
        |--------------------------------------------------------------------------
        | Publish Status
        |--------------------------------------------------------------------------
        */

        $status = strtoupper($request->publish_status ?? '');

        if ($status === 'PUBLISH') {
            $hall->publish_status = 'PUBLISH';
            $hall->status = '1';
        } else {
            $hall->publish_status = 'DRAFT';
            $hall->status = '0';
        }

        $uploadDir = public_path('images/hall');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        /*
        |--------------------------------------------------------------------------
        | Feature Image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('feature_image')) {
            if (
                !empty($hall->feature_image) &&
                File::exists(public_path($hall->feature_image))
            ) {
                File::delete(public_path($hall->feature_image));
            }

            $file = $request->file('feature_image');
            $filename = time() . '_' . uniqid() . '_feature.' . $file->getClientOriginalExtension();

            $file->move($uploadDir, $filename);
            $hall->feature_image = 'images/hall/' . $filename;
        }

        /*
        |--------------------------------------------------------------------------
        | Banner Image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('banner_image')) {
            if (
                !empty($hall->banner_image) &&
                File::exists(public_path($hall->banner_image))
            ) {
                File::delete(public_path($hall->banner_image));
            }

            $file = $request->file('banner_image');
            $filename = time() . '_' . uniqid() . '_banner.' . $file->getClientOriginalExtension();

            $file->move($uploadDir, $filename);
            $hall->banner_image = 'images/hall/' . $filename;
        }

        /*
        |--------------------------------------------------------------------------
        | Gallery
        |--------------------------------------------------------------------------
        */

        $existingGallery = json_decode($hall->gallery, true) ?? [];
        $updatedGallery = [];

        // Keep selected old images
        if ($request->has('oldimage')) {
            foreach ($request->oldimage as $index) {
                $index = (int) $index - 1;

                if (isset($existingGallery[$index])) {
                    $updatedGallery[] = $existingGallery[$index];
                }
            }
        }

        // Upload new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                $file->move($uploadDir, $filename);
                $updatedGallery[] = 'images/hall/' . $filename;
            }
        }

        // Delete removed gallery files
        $removedImages = array_diff($existingGallery, $updatedGallery);

        foreach ($removedImages as $image) {
            if (!empty($image) && File::exists(public_path($image))) {
                File::delete(public_path($image));
            }
        }

        $hall->gallery = json_encode(array_values($updatedGallery));

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $hall->save();

        Session::flash('success', 'Property updated successfully');

        return Redirect::to('all-hall');
    }

    public function hallRoomBlockData()
    {
        $hallQuery = Hall::query();

        if (Auth::user()->access_type == 'vendor') {
            $vendorId = (Auth::user()->role == 2)
                ? Auth::user()->id
                : Auth::user()->vendor_id;

            $hallQuery->where('created_by', $vendorId);
        }

        $HallList = $hallQuery
            ->where('is_deleted', 0)
            ->where('status', '1')
            ->whereNotNull('property_id')
            ->select('id', 'hall_name', 'property_id', 'created_by')
            ->get();

        $propertyIds = $HallList
            ->pluck('property_id')
            ->filter()
            ->unique()
            ->toArray();

        $MasterProperty = HallProperty::where('is_deleted', 0)
            ->where('status', '1')
            ->whereIn('id', $propertyIds)
            ->pluck('property_name', 'id');

        return view(
            'hall.hall-room-block-data',
            compact('MasterProperty', 'HallList')
        );
    }


    public function getHallBlockData(Request $request)
    {
        $query = BlockedHallInventory::with('hall');

        if (Auth::user()->access_type == 'vendor') {
            $createdBy = (Auth::user()->role == 2)
                ? Auth::user()->id
                : Auth::user()->vendor_id;

            $propertyIds = HallProperty::where('created_by', $createdBy)
                ->where('is_deleted', 0)
                ->pluck('id')
                ->toArray();

            $query->whereIn('property_id', $propertyIds);
        }

        if (!empty($request->searchValue1)) {
            $query->where('property_id', $request->searchValue1);
        }

        if (!empty($request->searchValue2)) {
            $query->where('hall_id', $request->searchValue2);
        }

        if (!empty($request->searchValue3)) {
            $dates = explode(' - ', $request->searchValue3);

            if (count($dates) == 2) {
                $fromDate = Carbon::createFromFormat(
                    'd-m-Y',
                    trim($dates[0])
                )->format('Y-m-d');

                $toDate = Carbon::createFromFormat(
                    'd-m-Y',
                    trim($dates[1])
                )->format('Y-m-d');

                $query->whereBetween('block_date', [$fromDate, $toDate]);
            }
        }

        if (!empty($request->search['value'])) {
            $searchValue = $request->search['value'];

            $query->where(function ($q) use ($searchValue) {
                $q->where('property_name', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('block_reason', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('block_date', 'LIKE', '%' . $searchValue . '%')
                    ->orWhereHas('hall', function ($hallQuery) use ($searchValue) {
                        $hallQuery->where(
                            'hall_name',
                            'LIKE',
                            '%' . $searchValue . '%'
                        );
                    });

                if (stripos('Full Day', $searchValue) !== false) {
                    $q->orWhere('slot_type', 1);
                }

                if (stripos('First Half', $searchValue) !== false) {
                    $q->orWhere('slot_type', 2);
                }

                if (stripos('Second Half', $searchValue) !== false) {
                    $q->orWhere('slot_type', 3);
                }
            });
        }

        $recordsTotal = $query->count();

        $records = $query
            ->orderBy('id', 'desc')
            ->skip($request->get('start', 0))
            ->take($request->get('length', 10))
            ->get();

        $data = [];

        foreach ($records as $row) {
            $slotType = $this->getHallSlotTypeText($row->slot_type);

            $data[] = [
                '<input type="checkbox" class="itemcheck" value="' . $row->id . '">',
                $row->property_name,
                optional($row->hall)->hall_name,
                $slotType,
                $row->block_reason,
                date('d-M-Y', strtotime($row->block_date)),
            ];
        }

        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
            'exportQuery' => '',
        ]);
    }

    public function blockHallRoom()
    {
        $propertyQuery = HallProperty::where('is_deleted', 0);

        if (Auth::user()->access_type == 'vendor') {
            $createdBy = (Auth::user()->role == 2)
                ? Auth::user()->id
                : Auth::user()->vendor_id;

            $propertyQuery->where('created_by', $createdBy);
        }

        $propertyIds = $propertyQuery
            ->pluck('id')
            ->toArray();

        $HallList = Hall::where('is_deleted', 0)
            ->where('status', '1')
            ->whereIn('property_id', $propertyIds)
            ->whereNotNull('property_id')
            ->whereNotNull('hcategory_id')
            ->select(
                'id',
                'hall_name',
                'property_id',
                'hcategory_id',
                'created_by'
            )
            ->orderBy('hall_name', 'asc')
            ->get();

        $usedPropertyIds = $HallList
            ->pluck('property_id')
            ->filter()
            ->unique()
            ->toArray();

        $categoryIds = $HallList
            ->pluck('hcategory_id')
            ->filter()
            ->unique()
            ->toArray();

        $MasterProperty = HallProperty::where('is_deleted', 0)
            ->where('status', '1')
            ->whereIn('id', $usedPropertyIds)
            ->orderBy('property_name', 'asc')
            ->pluck('property_name', 'id');

        $CategoryList = HallCategory::where('is_deleted', 0)
            ->whereIn('id', $categoryIds)
            ->select('id', 'hcategory_name')
            ->orderBy('hcategory_name', 'asc')
            ->get();

        return view(
            'hall.block-hall-room',
            compact('MasterProperty', 'HallList', 'CategoryList')
        );
    }


    public function blockHallRoomRequest(Request $request)
    {
        $request->validate([
            'property_id' => 'required|numeric',
            'hcategory_id' => 'required|numeric',
            'hall_id' => 'required|numeric',
            'slot_type' => 'required|in:1,2,3',
            'block_date' => 'required|string',
            'block_reason' => 'required|string',
        ]);

        $hall = Hall::where('id', $request->hall_id)
            ->where('property_id', $request->property_id)
            ->where('hcategory_id', $request->hcategory_id)
            ->first();

        if (empty($hall)) {
            Session::flash('error', 'Hall not found.');
            return redirect('block-hall-room')->withInput();
        }

        $property = HallProperty::find($request->property_id);
        $vendorId = '1';

        $dates = explode(' - ', $request->block_date);
        $startDate = Carbon::createFromFormat('d-m-Y', trim($dates[0]));
        $endDate = Carbon::createFromFormat('d-m-Y', trim($dates[1]));
        $slot = 'full_day';

        $slotArr = [
            1 => 'full_day',
            2 => 'first_half',
            3 => 'second_half'
        ];

        DB::beginTransaction();

        try {
            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $blockDate = $date->format('Y-m-d');

                $alreadyBlocked = BlockedHallInventory::where('hall_id', $hall->id)
                    ->where('block_date', $blockDate)
                    ->where(function ($query) use ($request) {
                        if ($request->slot_type == 1) {
                            $query->whereIn('slot_type', [1, 2, 3]);
                        } elseif ($request->slot_type == 2) {
                            $query->whereIn('slot_type', [1, 2]);
                        } elseif ($request->slot_type == 3) {
                            $query->whereIn('slot_type', [1, 3]);
                        }
                    })
                    ->exists();

                if ($alreadyBlocked) {
                    Session::flash(
                        'error',
                        'Selected hall is already blocked for selected date/slot.'
                    );

                    return redirect('block-hall-room')->withInput();
                }

                $this->setHallId($hall->id);

                if (!$this->isSlotAvailable($blockDate, $slotArr[$request->slot_type])) {
                    Session::flash(
                        'error',
                        'The requested slot is not available for the date: ' . $blockDate
                    );

                    return redirect('block-hall-room')->withInput();
                }

                $inventorySlotType = ($request->slot_type == 1) ? 1 : 2;

                $inventory = HallMasterInventory::where('vendor_id', $vendorId)
                    ->where('hall_id', $hall->id)
                    ->where('inventory_date', $blockDate)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Inventory record not found for update'
                    ]);
                }

                if ($request->slot_type == 1) {
                    $inventory->inventory_slot_type = '1';
                    $inventory->first_half_available = '2';
                    $inventory->second_half_available = '2';
                } elseif ($request->slot_type == 2) {
                    $inventory->inventory_slot_type = '2';
                    $inventory->first_half_available = '2';
                } elseif ($request->slot_type == 3) {
                    $inventory->inventory_slot_type = '2';
                    $inventory->second_half_available = '2';
                }

                $inventory->save();

                BlockedHallInventory::create([
                    'vendor_id' => $vendorId,
                    'hall_id' => $hall->id,
                    'property_id' => $request->property_id,
                    'property_name' => $property ? $property->property_name : '',
                    'slot_type' => $request->slot_type,
                    'block_date' => $blockDate,
                    'block_reason' => $request->block_reason,
                    'created_by' => Auth::user()->id,
                ]);
            }

            DB::commit();

            Session::flash('success', 'Hall inventory blocked successfully.');

            return redirect()->route('block-property');
        } catch (\Exception $e) {
            DB::rollBack();

            Session::flash('error', 'Unable to block due to ' . $e->getMessage());

            return redirect()->route('block-property');
        }
    }

    public function exportHallBlockData(Request $request)
    {
        $query = BlockedHallInventory::with('hall');

        if (Auth::user()->access_type == 'vendor') {
            $vendorId = (Auth::user()->role == 2)
                ? Auth::user()->id
                : Auth::user()->vendor_id;

            $query->where('vendor_id', $vendorId);
        }

        if (!empty($request->searchValue1)) {
            $query->where('property_id', $request->searchValue1);
        }

        if (!empty($request->searchValue2)) {
            $query->where('hall_id', $request->searchValue2);
        }

        if (!empty($request->searchValue3)) {
            $dates = explode(' - ', $request->searchValue3);

            if (count($dates) == 2) {
                $fromDate = Carbon::createFromFormat(
                    'd-m-Y',
                    trim($dates[0])
                )->format('Y-m-d');

                $toDate = Carbon::createFromFormat(
                    'd-m-Y',
                    trim($dates[1])
                )->format('Y-m-d');

                $query->whereBetween('block_date', [$fromDate, $toDate]);
            }
        }

        $records = $query->orderBy('id', 'desc')->get();

        $excel = '<table border="1">';
        $excel .= '<thead><tr>';
        $excel .= '<th>Property Name</th>';
        $excel .= '<th>Hall Name</th>';
        $excel .= '<th>Slot Type</th>';
        $excel .= '<th>Reason</th>';
        $excel .= '<th>Date</th>';
        $excel .= '</tr></thead><tbody>';

        foreach ($records as $row) {
            $slotType = $this->getHallSlotTypeText($row->slot_type);

            $excel .= '<tr>';
            $excel .= '<td>' . e($row->property_name) . '</td>';
            $excel .= '<td>' . e(optional($row->hall)->hall_name) . '</td>';
            $excel .= '<td>' . e($slotType) . '</td>';
            $excel .= '<td>' . e($row->block_reason) . '</td>';
            $excel .= '<td>' . date('d-M-Y', strtotime($row->block_date)) . '</td>';
            $excel .= '</tr>';
        }

        $excel .= '</tbody></table>';

        return response($excel)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header(
                'Content-Disposition',
                'attachment; filename="hall_block_report.xls"'
            );
    }


    private function getHallSlotTypeText($slotType)
    {
        if ($slotType == 1) {
            return 'Full Day';
        }

        if ($slotType == 2) {
            return 'First Half';
        }

        if ($slotType == 3) {
            return 'Second Half';
        }

        return '';
    }


    public function propertyAvailability()
    {
        $hallQuery = Hall::query();

        if (Auth::user()->access_type == 'vendor') {
            $vendorId = (Auth::user()->role == 2)
                ? Auth::user()->id
                : Auth::user()->vendor_id;

            $hallQuery->where('created_by', $vendorId);
        }

        $HallList = $hallQuery
            ->where('is_deleted', 0)
            ->whereNotNull('property_id')
            ->whereNotNull('hcategory_id')
            ->select('id', 'hall_name', 'property_id', 'hcategory_id', 'created_by')
            ->get();

        $propertyIds = $HallList
            ->pluck('property_id')
            ->filter()
            ->unique()
            ->toArray();

        $categoryIds = $HallList
            ->pluck('hcategory_id')
            ->filter()
            ->unique()
            ->toArray();

        $MasterProperty = HallProperty::where('is_deleted', 0)
            ->where('status', '1')
            ->whereIn('id', $propertyIds)
            ->pluck('property_name', 'id');

        $CategoryList = HallCategory::where('is_deleted', 0)
            ->whereIn('id', $categoryIds)
            ->select('id', 'hcategory_name')
            ->get();

        return view(
            'hall.property-availability',
            compact('MasterProperty', 'HallList', 'CategoryList')
        );
    }


    public function hallBookingReport(Request $request)
    {
        if (!(parent::checkViewPrivilege(86))) {
            Session::flash('success', 'You are not authorised to view this page.');
            return redirect()->back();
        }

        // Property List
        $MasterProperty = HallProperty::where('is_deleted', '0')
            ->where('status', '1')
            ->pluck('property_name', 'id')
            ->toArray();

        // Default Filters
        $property_id = $request->filled('property_id')
            ? $request->property_id
            : 0;

        $report_type = $request->filled('report_type')
            ? $request->report_type
            : 'book_date';

        $check_date = $request->filled('check_date')
            ? $request->check_date
            : Carbon::today()->format('d-m-Y');

        $date = Carbon::createFromFormat('d-m-Y', $check_date)->format('Y-m-d');

        $query = DB::table('t_booking as tb')
            ->join('m_hall as h', 'tb.hall_id', '=', 'h.id')
            ->join('m_hcategory as hc', 'h.hcategory_id', '=', 'hc.id')
            ->join('order_masters as om', 'tb.booking_id', '=', 'om.order_id')
            ->select(
                'om.invoice_id',
                'om.order_id',
                'tb.booking_date',
                'om.customer_name',
                'om.service_name',
                'hc.hcategory_name as hall_type',

                // Get slot directly from t_booking
                'tb.slot_type',
                'tb.totalPrice as total_amount',
                'om.status',
                'tb.booking_id as oderID'
            )
            ->where('tb.is_deleted', '0')
            ->where('h.is_deleted', '0');

        // Property Filter
        if ($property_id != 0) {
            $query->where('h.property_id', $property_id);
        }

        // Date Filter
        if ($report_type == 'book_date') {
            $query->whereDate('tb.booking_date', $date);
        } else {
            $query->whereDate('tb.start_date', $date);
        }

        $bookings = $query
            ->orderBy('tb.booking_date', 'desc')
            ->get();

        $MisHallData = [];

        foreach ($bookings as $row) {
            /*
             * FIRST_HALF  -> FIRST HALF
             * SECOND_HALF -> SECOND HALF
             * FULL_DAY -> FULL DAY
             */
            $slotType = strtoupper(
                str_replace('_', ' ', $row->slot_type ?? '')
            );

            $MisHallData[] = [
                'invoice_id' => $row->invoice_id,
                'order_id' => $row->order_id,
                'book_date' => Carbon::parse($row->booking_date)->format('d-m-Y'),
                'customer_name' => $row->customer_name,
                'service_name' => $row->service_name,
                'hall_type' => $row->hall_type,

                // Correct slot_type
                'slot_type' => $slotType,

                'total_amount' => $row->total_amount,
                'status' => $row->status,
                'oderID' => $row->oderID,

                // Required by Blade
                'link' => 1,
                'book_from' => '',
                'cancel_date' => ''
            ];
        }

        return view(
            'hall.hall-booking-report',
            compact(
                'MisHallData',
                'MasterProperty',
                'property_id',
                'report_type',
                'check_date'
            )
        );
    }

    public function hallAvailablityReport(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()
                ->route('login')
                ->with('error', 'Your session has expired. Please login again.');
        }

        $hallQuery = Hall::query();

        if ($user->access_type == 'vendor') {
            $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;
            $hallQuery->where('created_by', $vendorId);
        }

        $HallList = $hallQuery
            ->where('is_deleted', 0)
            ->whereNotNull('property_id')
            ->whereNotNull('hcategory_id')
            ->select('id', 'hall_name', 'property_id', 'hcategory_id', 'created_by')
            ->orderBy('hall_name', 'asc')
            ->get();

        $propertyIds = $HallList
            ->pluck('property_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $MasterProperty = HallProperty::query()
            ->where('is_deleted', 0)
            ->where('status', '1')
            ->whereIn('id', $propertyIds)
            ->orderBy('property_name', 'asc')
            ->pluck('property_name', 'id');

        $PropertyId = $request->get('property_id');

        if (empty($PropertyId) && $MasterProperty->isNotEmpty()) {
            $PropertyId = $MasterProperty->keys()->first();
        }

        $PropertyId = !empty($PropertyId) ? (int) $PropertyId : null;

        if ($PropertyId !== null && !$MasterProperty->has($PropertyId)) {
            abort(403, 'You cannot access the selected property.');
        }

        $PropertyName = $PropertyId !== null ? $MasterProperty->get($PropertyId, '') : '';

        $startDate = Carbon::today()->startOfDay();
        $endDate = Carbon::today()->addDays(30)->endOfDay();

        if ($request->filled('check_date')) {
            try {
                $range = preg_split('/\s+-\s+/', trim($request->get('check_date')));

                if (!is_array($range) || count($range) != 2) {
                    throw new \Exception('Invalid date range');
                }

                $startDate = Carbon::createFromFormat('d-m-Y', trim($range[0]))->startOfDay();
                $endDate = Carbon::createFromFormat('d-m-Y', trim($range[1]))->endOfDay();
            } catch (\Exception $exception) {
                return redirect()
                    ->route('hall-availablity-report')
                    ->withErrors([
                        'check_date' => 'Please select a valid date range.'
                    ]);
            }
        }

        if ($endDate->lt($startDate)) {
            $oldStartDate = $startDate->copy();
            $startDate = $endDate->copy()->startOfDay();
            $endDate = $oldStartDate->copy()->endOfDay();
        }

        $selectedHalls = $HallList
            ->where('property_id', $PropertyId)
            ->values();

        $categoryIds = $selectedHalls
            ->pluck('hcategory_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $CategoryList = HallCategory::query()
            ->where('is_deleted', 0)
            ->whereIn('id', $categoryIds)
            ->pluck('hcategory_name', 'id');

        $conferenceHallIds = [];
        $conventionHallIds = [];

        foreach ($selectedHalls as $hall) {
            $categoryName = strtolower(trim($CategoryList->get($hall->hcategory_id, '')));

            if (strpos($categoryName, 'conference') !== false) {
                $conferenceHallIds[] = (int) $hall->id;
            }

            if (strpos($categoryName, 'convention') !== false) {
                $conventionHallIds[] = (int) $hall->id;
            }
        }

        $conferenceHallIds = array_values(array_unique($conferenceHallIds));
        $conventionHallIds = array_values(array_unique($conventionHallIds));

        $conferenceTotal = count($conferenceHallIds);
        $conventionTotal = count($conventionHallIds);

        $selectedHallIds = array_values(
            array_unique(
                array_merge($conferenceHallIds, $conventionHallIds)
            )
        );

        $inventoryByDate = collect();

        if (!empty($selectedHallIds)) {
            $inventoryByDate = HallMasterInventory::query()
                ->whereIn('hall_id', $selectedHallIds)
                ->whereBetween('inventory_date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ])
                ->select(
                    'id',
                    'hall_id',
                    'inventory_date',
                    'first_half_available',
                    'second_half_available'
                )
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy(function ($inventory) {
                    return Carbon::parse($inventory->inventory_date)->format('Y-m-d');
                });
        }

        $HallReportData = [];

        $period = CarbonPeriod::create(
            $startDate->copy()->startOfDay(),
            $endDate->copy()->startOfDay()
        );

        foreach ($period as $date) {
            $databaseDate = $date->format('Y-m-d');
            $dateInventory = $inventoryByDate->get($databaseDate, collect());

            $dateInventoryByHall = $dateInventory
                ->groupBy('hall_id')
                ->map(function ($rows) {
                    return $rows->last();
                });

            $conferenceBooked = 0;

            foreach ($conferenceHallIds as $hallId) {
                $inventory = $dateInventoryByHall->get($hallId);

                if (!$inventory) {
                    continue;
                }

                $firstHalf = (int) ($inventory->first_half_available ?? 0);
                $secondHalf = (int) ($inventory->second_half_available ?? 0);

                if ($firstHalf === 1 && $secondHalf === 1) {
                    $conferenceBooked += 1;
                } elseif ($firstHalf === 1 || $secondHalf === 1) {
                    $conferenceBooked += 0.5;
                }
            }

            $conventionBooked = 0;

            foreach ($conventionHallIds as $hallId) {
                $inventory = $dateInventoryByHall->get($hallId);

                if (!$inventory) {
                    continue;
                }

                $firstHalf = (int) ($inventory->first_half_available ?? 0);
                $secondHalf = (int) ($inventory->second_half_available ?? 0);

                if ($firstHalf === 1 && $secondHalf === 1) {
                    $conventionBooked += 1;
                } elseif ($firstHalf === 1 || $secondHalf === 1) {
                    $conventionBooked += 0.5;
                }
            }

            $HallReportData[] = [
                'date' => $databaseDate,
                'display_date' => $date->format('d-M-Y'),
                'conference_booked' => $conferenceBooked,
                'conference_total' => $conferenceTotal,
                'convention_booked' => $conventionBooked,
                'convention_total' => $conventionTotal
            ];
        }

        return view(
            'hall.hall-availability-report',
            [
                'MasterProperty' => $MasterProperty,
                'PropertyId' => $PropertyId,
                'PropertyName' => $PropertyName,
                'HallReportData' => $HallReportData,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        );
    }


    public function offlineHallOrder(){
        $user = Auth::user();
        $propertyQuery = HallProperty::whereHas('halls', function ($query) {
            $query->where('is_deleted', 0)
                ->where('status', 1);
        })
            ->where(function ($query) {
                $query->where('is_deleted', 0)
                    ->orWhereNull('is_deleted');
            })
            ->whereNotNull('property_name')
            ->where('property_name', '!=', '');

        if ((int) $user->role === 2) {

            $propertyQuery->where('vender_id', $user->id);

        } elseif (!empty($user->vendor_id) && !in_array($user->access_type,['admin', 'superadmin'])) {

            $propertyQuery->where('vender_id', $user->vendor_id);
        }

        $MasterProperty = $propertyQuery
            ->orderBy('property_name', 'asc')
            ->pluck('property_name', 'id');

        $CountryData = Country::orderBy(
            'name',
            'asc'
        )->pluck('name', 'id');

        $adminUser = in_array($user->access_type,['admin', 'superadmin']) ? 1 : 0;

        $hallCategory = HallCategory::where('is_deleted', 0)
            ->where('status', '1')
            ->orderBy('hcategory_name', 'asc')
            ->pluck('hcategory_name', 'id');

        return view('hall.offline-hall-order',
            compact('MasterProperty', 'CountryData', 'adminUser','hallCategory')
        );
    }


    /**
     * Offline Hall Order
     */
    public function createOfflineHallOrder(Request $request)
    {
        if ($request->isMethod('get')) {
            if (!(parent::checkViewPrivilege(29))) {

                Session::flash('failure','You are not authorised to view this page.');
                return redirect()->back();
            }

            $user = Auth::user();
            $vendorId = ($user->role == 2) ? $user->id : $user->vendor_id;

            $propertyQuery = HallProperty::query()
                ->where('is_deleted', 0)
                ->where('status', '1');

            if ($user->access_type == 'vendor' && !empty($vendorId)) {
                $propertyQuery->where('created_by', $vendorId);
            }

            $MasterProperty = $propertyQuery
                ->orderBy('property_name','asc')
                ->pluck('property_name','id');

            $CountryData = Country::query()
                ->orderBy('country_name','asc')
                ->pluck('country_name','id');

            $adminUser = ($user->role == 1) ? 1 : 0;
            $hallCategory = HallCategory::where('is_deleted', 0)
                ->where('status', '1')
                ->orderBy('hcategory_name','asc')
                ->pluck('hcategory_name','id');

            return view('hall.offline-hall-order',compact( 'MasterProperty','CountryData','adminUser','hallCategory'));
        }

        if (!(parent::checkWritePrivilege(29))) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You are not authorised to perform this operation.',
                    'data' => []
                ], 403);
            }

            Session::flash('failure','You are not authorised to perform this operation.');
            return redirect()->route('offline-hall-order');
        }

        if ($request->request_type === 'check_hall_availability') {
            return $this->checkOfflineHallAvailability($request);
        }

        if ($request->request_type === 'get_states_country') {
            $countryId = $request->countryId;
            $states = DB::table('states')
                ->where('country_id',$countryId)
                ->orderBy('name','asc')
                ->pluck('name','id');

            $html = '<option value="">Select State</option>';

            foreach ($states as $id => $name) {
                $html .= '<option value="' . e($name . '~' . $id) . '">' . e($name) . '</option>';
            }

            return response()->json([
                'status' => 1,
                'data' => $html
            ]);
        }

        if ( $request->request_type === 'get_city_state') {
            $stateId = $request->stateId;
            $cities = DB::table('cities')
                ->where('state_id',$stateId)
                ->orderBy('name','asc')
                ->pluck('name', 'id');

            $html = '<option value="">Select City</option>';

            foreach ($cities as $id => $name) {
                $html .= '<option value="' . e($name) . '">' . e($name) . '</option>';
            }

            return response()->json([
                'status' => 1,
                'data' => $html
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'property_id' => 'required|integer',
                'hall_id'     => 'required|integer',
                'slot_type'   => 'required|in:FULL_DAY,FIRST_HALF,SECOND_HALF',
                'check_date'  => 'required|string',
                'book_from'   =>  'required|in:live,blocked',
                'customer_name' =>'required|string|max:150',
                'customer_email' => 'required|email|max:150',
                'customer_phone' =>'required|string|max:20',
                'payment_gateway' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('offline-hall-order')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $bookingDate = Carbon::createFromFormat('d M Y', trim($request->check_date))
                ->startOfDay()
                ->format('Y-m-d');

        } catch (\Throwable $exception) {
            Session::flash('failure','Invalid booking date format.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        if ($bookingDate < now()->format('Y-m-d')) {
            Session::flash('failure','Previous dates cannot be selected.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $propertyId = (int) $request->property_id;
        $hallId =   (int) $request->hall_id;
        $requestedSlot = strtoupper(trim((string) $request->slot_type));


        $property = HallProperty::where('id',$propertyId)
            ->where('is_deleted',0)
            ->first();

        if (empty($property)) {
            Session::flash('failure','Selected property was not found.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $hall = DB::table('m_hall as h')
            ->leftJoin('m_hcategory as hc','h.hcategory_id','=','hc.id')
            ->where('h.id',$hallId)
            ->where('h.property_id',$propertyId)
            ->where('h.status','1')
            ->where('h.is_deleted','0')
            ->select('h.*','hc.hcategory_name')
            ->first();

        if (empty($hall)) {
            Session::flash('failure','Selected Hall was not found under this property.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $slot = DB::table('m_slot')
            ->where('slot',strtoupper($request->slot_type))
            ->where('hall_id',$hallId)
            ->where('status','1')
            ->where('is_deleted','0')
            ->first();

        if (empty($slot)) {
            $slot = DB::table('m_slot')
                ->where('booking_type',strtoupper($request->slot_type))
                ->where('hall_id',$hallId)
                ->where('status','1')
                ->where('is_deleted','0')
                ->first();
        }

        if (empty($slot)) {
            Session::flash('failure','Selected slot was not found.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $masterSlotType = strtoupper(str_replace([' ', '-'],'_',(string) ($slot->slot ?? $slot->booking_type ?? '')));

        if ($requestedSlot === 'FULL_DAY' && $masterSlotType !== 'FULL_DAY') {

            Session::flash('failure','Invalid Full Day slot selected.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        if (in_array($requestedSlot,['FIRST_HALF','SECOND_HALF'],true) && $masterSlotType !== 'HALF_DAY') {
            Session::flash('failure','Invalid Half Day slot selected.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $availabilityResult = $this->validateHallSlotAvailability($propertyId,$hallId,$requestedSlot,$bookingDate,false,$request->book_from);

        if (!$availabilityResult['available']) {
            Session::flash('failure',$availabilityResult['message']);
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $user = Auth::user();
        $vendorId = $property->vender_id;
        $vendor = User::find($vendorId);

        if (empty($vendor)) {
            Session::flash('failure','Vendor information was not found.');
            return redirect()
                ->route('offline-hall-order')
                ->withInput();
        }

        $state = !empty($request->customer_state) ? explode('~',(string) $request->customer_state,2) : [];

        $country = !empty($request->customer_country) ? explode('~',(string) $request->customer_country,2) : [];

        DB::beginTransaction();
        try {
            $availabilityResult = $this->validateHallSlotAvailability($propertyId,$hallId,$requestedSlot,$bookingDate,true,$request->book_from);

            if (!$availabilityResult['available']){
                throw new \RuntimeException($availabilityResult['message']);
            }

            if ($user->user_role === 'agent_staff') {
                $customerId = $user->id;
            } else {
                $customer = User::where('email',trim($request->customer_email))
                    ->orWhere('phone',trim($request->customer_phone))
                    ->first();

                if (!empty($customer)) {
                    $customerId = $customer->id;
                } else {
                    $customer = new User();
                    $customer->email = trim($request->customer_email);
                    $customer->password = bcrypt(random_int(10000000,99999999));
                    $customer->first_name = trim( $request->customer_name);
                    $customer->phone = trim($request->customer_phone);
                    $customer->pincode = $request->customer_zipcode;
                    $customer->address = $request->customer_address1;
                    $customer->address2 = $request->address2;
                    $customer->country = $country[1] ?? null;
                    $customer->state = $state[1] ?? null;
                    $customer->city = $request->customer_city;
                    $customer->vendor_id = 0;
                    $customer->role = 4;
                    $customer->access_type = 'customer';
                    $customer->login_type ='email';
                    $customer->status = 1;
                    $customer->create_account_approval = 0;
                    $customer->save();
                    $customerId = $customer->id;
                }
            }

            $lastIdData = OrderMaster::orderBy('id','desc')->first();
            $lastId =  !empty($lastIdData) ? $lastIdData->id + 1 : 1;
            $bookingId = 'OT-' . time() . '-' . $vendorId . '-' . $lastId;

            $lastOrder = OrderMaster::select(DB::raw('count(id) as totOrder'))
                    ->where('service_type','hall')
                    ->where('status', '!=','partially-cancelled')
                    ->first();

            if ($lastOrder->totOrder > 0) {

                $lastInvoiceId = (int) $lastOrder->totOrder + 1;
                $invoiceId = date('dmY') . 'HL00' . $lastInvoiceId;

            } else {
                $invoiceId = date('dmY') . 'HL001';
            }

            $instantPaymentMethods = [
                'cash',
                'credit',
                'upi',
                'card'
            ];

            $isInstantPayment = in_array($request->payment_gateway,$instantPaymentMethods,true);
            $orderStatus = $isInstantPayment ? 'completed' : 'pending';
            $paymentStatus = $isInstantPayment ? 'success' : 'pending';
            $paymentMethod = $isInstantPayment ? $request->payment_gateway : '';
            $orderType = $user->user_role === 'agent_staff' ? $user->first_name : 'offline';

            $totalDays = 1;

            $unitPrice = (float) $slot->price;
            $hallPrice = $unitPrice;

            $taxPercentage = 5;

            $taxAmount = ceil($hallPrice * ($taxPercentage / 100));
            $serviceCharge = (float) ( $request->service_charge ?? 0);
            $couponAmount = (float) ($request->coupon_amount ?? 0);
            $subTotal = $hallPrice;
            $totalOrderPrice = $subTotal + $taxAmount + $serviceCharge - $couponAmount;

            $orderMaster = new OrderMaster();
            $orderMaster->order_id = $bookingId;
            $orderMaster->invoice_id = $invoiceId;
            $orderMaster->invoice_serial = '';
            $orderMaster->vendor_id = $vendorId;
            $orderMaster->vendor_name = $vendor->company ?? '';
            $orderMaster->order_type = $orderType;
            $orderMaster->customer_id = $customerId;
            $orderMaster->customer_name = trim($request->customer_name);
            $orderMaster->customer_email = trim($request->customer_email);
            $orderMaster->customer_phone = trim($request->customer_phone);
            $orderMaster->customer_address1 = trim((string)$request->customer_address1);
            $orderMaster->customer_city = parent::cleanString(trim((string)$request->customer_city));
            $orderMaster->customer_state = isset($state[0]) ? parent::cleanString(trim($state[0])) : '';
            $orderMaster->customer_country = isset($country[0]) ? parent::cleanString(trim($country[0])) : '';
            $orderMaster->customer_zipcode = trim((string)$request->customer_zipcode);
            $orderMaster->gst_regd_no = trim((string)$request->gst_regd_no);
            $orderMaster->gst_company_name = trim((string)$request->gst_company_name);
            $orderMaster->gst_company_address =trim((string)$request->gst_company_address);
            $orderMaster->book_naration = addslashes(trim((string)$request->book_naration));
            $orderMaster->service_type = 'hall';
            $orderMaster->service_name = $hall->hall_name;
            $orderMaster->service_name_id = $hallId;
            $orderMaster->start_date = $bookingDate;
            $orderMaster->end_date = $bookingDate;
            $orderMaster->room_request = $request->hall_details;


            $orderMaster->room_details =
                json_encode([
                    $hallId => [
                        'hall_name' => $hall->hall_name,
                        'hall_category' => $hall->hcategory_name ?? '',
                        'slot_id' => $slot->id,
                        'slot_name' => $requestedSlot,
                        'quantity' => 1,
                        'booking_date' => $bookingDate
                    ]
                ]);


            $orderMaster->total_service_price = $hallPrice;
            $orderMaster->sub_total_price = $subTotal;
            $orderMaster->coupon_name = $request->coupon_name;
            $orderMaster->coupon_code = $request->coupon_code;
            $orderMaster->coupon_amount = $couponAmount;
            $orderMaster->tax_percentage = $taxPercentage;
            $orderMaster->tax_amount = $taxAmount;
            $orderMaster->service_charge = $serviceCharge;
            $orderMaster->total_order_price = $totalOrderPrice;
            $orderMaster->status =  $orderStatus;
            $orderMaster->payment_gateway = $request->payment_gateway;
            $orderMaster->payment_method = $paymentMethod;
            $orderMaster->payment_status = $paymentStatus;
            $orderMaster->split_initiate_status = 0;
            $orderMaster->admin_amount = $totalOrderPrice;
            $orderMaster->vendor_amount = 0;
            $orderMaster->book_from = $request->book_from;
            $orderMaster->request_from ='web';

            $txn_id = "TXN". time() . rand(10000, 99999999);

            $liveInventory = DB::table('t_hall_inventory')
                    ->where('hall_id',$hallId)
                    ->whereDate('inventory_date',$bookingDate)
                    ->lockForUpdate()
                    ->first();

            if (!$liveInventory) {
                throw new \RuntimeException('Live Hall inventory was not found for ' .
                    Carbon::parse(
                        $bookingDate
                    )->format('d M Y') .
                    '.'
                );
            }
            $blockedInventory = DB::table('t_blocked_hall_inventory')
                ->where('hall_id',$hallId)
                ->whereDate('block_date',$bookingDate)
                ->lockForUpdate()
                ->first();
            if($request->book_from == 'blocked'){
                if (!$blockedInventory) {
                    throw new \RuntimeException('Blocked Hall inventory was not found for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                    );
                }
            }

            $inventoryUpdate = ['updated_at' => now()];
            $blockedInventoryUpdate = ['updated_at' => now()];
            if ($requestedSlot === 'FULL_DAY') {
                if ((int) $liveInventory->first_half_available !== 0 || (int) $liveInventory->second_half_available !== 0) {

                    throw new \RuntimeException(
                        'Full Day is not available for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                    );
                }

                $inventoryUpdate['inventory_slot_type'] = 1;
                $inventoryUpdate['first_half_available'] = 1;
                $inventoryUpdate['second_half_available'] = 1;
                $blockedInventoryUpdate['first_half_available'] = 1;
                $blockedInventoryUpdate['second_half_available'] = 1;
            }elseif ($requestedSlot === 'FIRST_HALF') {
                if ((int)$liveInventory->first_half_available !== 0) {

                    throw new \RuntimeException(
                        'First Half is not available for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                    );
                }

                $inventoryUpdate['inventory_slot_type'] = 2;
                $inventoryUpdate['first_half_available'] = 1;
                $blockedInventoryUpdate['first_half_available'] = 1;
            }elseif ($requestedSlot === 'SECOND_HALF') {
                if ((int)$liveInventory->second_half_available !== 0) {

                    throw new \RuntimeException(
                        'Second Half is not available for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                    );
                }

                $inventoryUpdate['inventory_slot_type'] = 2;
                $inventoryUpdate['second_half_available'] = 1;
                $blockedInventoryUpdate['second_half_available'] = 1;
            } else {

                throw new \RuntimeException(
                    'Invalid Hall slot type.'
                );
            }

            if($orderMaster->save()){
                $OrderMasterId = $orderMaster->id;
                $OrderMasterNew = OrderMaster::find($OrderMasterId);
                if($request->payment_gateway != 'hdfc' && !empty($request->payment_gateway)) {
                // Generate Qr Code
                    require_once public_path('QrCode/generateQrCode.php');
                    $QrCodeData = array(
                        'invoiceId' => $OrderMasterNew->invoice_id,
                        'orderId' => $OrderMasterNew->order_id,
                        'txnId' => $OrderMasterNew->transaction_id,
                        'serviceType' => $OrderMasterNew->service_type,
                    );
                    $QrCode = generateQrCode(json_encode($QrCodeData));
                    $OrderMasterNew->qr_code = $QrCode;
                    $OrderMasterNew->qr_verified = 0;
                }
                if($request->payment_gateway == 'hdfc'){
                    require_once public_path('paytm_lib/config_paytm.php');
                    $PropertyAccount = PropertyAccount::where(['service_type' => $OrderMasterNew->service_type, 'service_id' => $OrderMasterNew->service_name_id])->first();
                    if (!empty($PropertyAccount) && PAYTM_ENVIRONMENT == 'PROD') {
                        $HDFC_KEY = $PropertyAccount->hdfc_key;
                        $HDFC_SALT = $PropertyAccount->hdfc_salt;
                        $MERCHANT_ID = $PropertyAccount->hdfc_mid;
                    }

                    $command = "create_invoice";
                    $var1_arr = array(
                        'amount' => $OrderMasterNew->total_order_price,
                        'txnid' => $txn_id,
                        'productinfo' => parent::cleanString($OrderMasterNew->service_name),
                        'firstname' => $OrderMasterNew->customer_name,
                        'email' => $OrderMasterNew->customer_email,
                        'phone' => $OrderMasterNew->customer_phone,
                        'address1' => $OrderMasterNew->customer_address1,
                        'city' => $OrderMasterNew->customer_city,
                        'state' => $OrderMasterNew->customer_state,
                        'country' => $OrderMasterNew->customer_country,
                        'zipcode' => $OrderMasterNew->customer_zipcode,
                        'validation_period' => 30,
                        'send_email_now' => '1',
                        'send_sms' => '1',
                        'time_unit' => 'M'
                    );
                    $var1 = json_encode($var1_arr);
                    $hash_str = $HDFC_KEY . '|' . $command . '|' . $var1 . '|' . $HDFC_SALT;
                    $hash = strtolower(hash('sha512', $hash_str));
                    $r = array('key' => $HDFC_KEY, 'hash' => $hash, 'command' => $command, 'var1' => $var1);
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
                    $verify_response = json_decode($o, true);

                    if (isset($verify_response['Status']) && $verify_response['Status'] == 'Success' && !empty($verify_response['URL'])) {
                        $link_expiry = date("Y-m-d H:i:s", strtotime('+30 minutes'));
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
                        $PaymentHistory->save();
                        $payment_id = $PaymentHistory->id;

                        $OrderMasterNew->offline_short_url = $verify_response['URL'];
                        $OrderMasterNew->offline_long_url = $verify_response['URL'];
                        $OrderMasterNew->offline_link_expiry = $link_expiry;
                        $OrderMasterNew->payment_id = $payment_id;
                        $OrderMasterNew->transaction_id = $txn_id;
                        $OrderMasterNew->hdfc_key = $HDFC_KEY;
                        $OrderMasterNew->hdfc_salt = $HDFC_SALT;
                        $OrderMasterNew->paytm_mid = $MERCHANT_ID;

                    }else {
                        OrderMaster::find($OrderMasterId)->delete();
                        Session::flash('success', "Sorry!, Could not able to place order due to some technical issue in generating payment link. Please try again after some time.");
                        return redirect()
                                ->route('offline-hall-order')
                                ->withInput();
                    }
                }
            }

            $count = 0;
            $OrderDetailsData = array();
            $OrderDetailsData[$count] = [
                'order_id' => $bookingId,
                'order_master_id' => $OrderMasterId,
                'service_type' => 'hall',
                'service_name' => $OrderMasterNew->service_name,
                'service_name_id' => $OrderMasterNew->service_name_id,
                'service_city' => $OrderMasterNew->customer_city,
                'start_date' => $OrderMasterNew->start_date,
                'end_date' => $OrderMasterNew->end_date,
                'service_item_quantity' => 1,
                'service_item_price' => ceil($subTotal),
                'unit_total_price' => ceil($subTotal),
                'status' => $orderStatus
            ];
            OrderDetail::insert($OrderDetailsData);

            DB::table('t_hall_inventory')
                ->where('id',$liveInventory->id)
                ->update($inventoryUpdate);

            if($request->book_from == 'blocked'){
                DB::table('t_blocked_hall_inventory')->where('id', $blockedInventory->id)->update($blockedInventoryUpdate);
            }

            if (!empty($request->coupon_code)) {
                DB::table('coupons')
                    ->where('coupon_code', $request->coupon_code)
                    ->increment('already_used');
            }
            $OrderMasterDetails = clone $OrderMasterNew;
            $OrderMasterNew->save();
            DB::commit();
            Session::flash('success','Hall order created successfully. Booking ID: ' . $bookingId .', Invoice ID: ' . $invoiceId);
            if($request->payment_gateway == 'hdfc'){
                $this->sendNotification($OrderMasterDetails, $requestedSlot);
            }

            return redirect()->route('offline-hall-order');
        } catch (\Throwable $exception) {
            DB::rollBack();
            Session::flash('failure','Unable to create Hall order: ' . $exception->getMessage());
            return redirect()->route('offline-hall-order')->withInput();
        }
    }

    private function sendNotification($OrderMaster, $requestedSlot){
        $RentalInvoice = EmailTemplate::where('ref_code', 'hallInvoice')->first();
        if (!empty($RentalInvoice)) {
            $Subject = $RentalInvoice->subject . ' - ' . $OrderMaster->service_name . ' - Booking ID - ' . $OrderMaster->invoice_id;
            $check_date = date("d M Y", strtotime($OrderMaster->start_date)) .' '. date("h:i a", strtotime($OrderMaster->start_time)) .' - <br>' . date("d M Y", strtotime($OrderMaster->end_date)) .' '. date("h:i a", strtotime($OrderMaster->end_time));

            $hallDetails = Hall::where('id',$OrderMaster->service_name_id)->with('category')->first();

            $property = HallProperty::where('id',$hallDetails->property_id)
            ->where('is_deleted',0)
            ->first();

            $OrderDetails = OrderDetail::where('order_master_id', $OrderMaster->id)->get();
            $Vendor = User::find($property->vender_id);

            $vendorGSTNo = (!empty($property->gst_number)) ? $property->gst_number : 'N/A';
            $vendorRegdCompany = (!empty($property->gst_legal_name)) ? $property->gst_legal_name : 'N/A';
            $guide_text = ($OrderMaster->days_for_guide > 0) ? $OrderMaster->days_for_guide : 'N/A';

            $routes = '';$routes_agent = ''; $route_confirm = '';$count = 1;
            $category_name = $hallDetails->category->hcategory_name;
            $propertyDetails = $property;
            $PaymentHistory = PaymentHistory::where('id',  $OrderMaster->payment_id)->first();
            $customerGSTNo = '';
            $customerGSTNo = (!empty($OrderMaster->gst_regd_no)) ? '<u><b>GSTN No: '. $OrderMaster->gst_regd_no .'</b></u>' : '';
            $customerGSTCompany = (!empty($OrderMaster->gst_company_name)) ? '<u><b>Company Name: '. $OrderMaster->gst_company_name .'</b></u>' : '';

            foreach($OrderDetails as $route) {
                $room_price = $route->unit_total_price;

                $routes .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $OrderMaster->invoice_id . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . $category_name . '</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. $OrderMaster->service_name .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($route->start_date)) .' - <br>'. date("d M Y", strtotime($route->end_date)) .'</td><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . str_replace('_',' ',strtoupper($requestedSlot)) . ' </td><td align="right" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">'. number_format($OrderMaster->sub_total_price , 2) .'</td></tr>';

                $routes_agent .= '<tr><td align="center" valign="middle" style="color:#000;border-right:1px solid #000; border-bottom:1px solid #000">' . date("d M Y", strtotime($route->start_date)) .' - <br>'. date("d M Y", strtotime($route->end_date)) .'</td></tr>';

                $route_confirm .= '<tr><td width="10%" rowspan="3">'. $count .'</td><td><strong>Pick up Location</strong>: ' . $route->pickup_address .', '. $route->pickup_city . '</td></tr><tr><td><strong>Start Date</strong>: ' . date("d M Y", strtotime($route->start_date)) .'</td><td><strong>End Date</strong>: '. date("d M Y", strtotime($route->end_date)) .'</td></tr><tr><td colspan="3">&nbsp;</td></tr>';
                $count++;

                $difference = strtotime(date("Y-m-d", strtotime($route->end_date))) - strtotime(date("Y-m-d", strtotime($route->start_date)));
                $days = floor($difference / (60 * 60 * 24));
                $cal_day = ($days == 0) ? 1 : $days + 1;
            }

            $maps = '';

            $routes .= '<tr><td colspan="5" align="right" valign="middle" style="color:#000;border-right:1px solid #000;padding-right:10px;font-size:15px;color:#000;line-height:20px">NET Total :</td><td align="right" valign="middle" style="color:#000;font-size:15px;color:#000;line-height:20px">'.number_format($OrderMaster->sub_total_price , 2). '</td></tr>';

            $routes .= '<tr><td colspan="5" align="right" valign="middle" style="color:#000;border-right:1px solid #000;padding-right:10px;font-size:15px;color:#000;line-height:20px">GST :</td><td align="right" valign="middle" style="color:#000;font-size:15px;color:#000;line-height:20px">'.number_format($OrderMaster->tax_amount , 2). '</td></tr>';

            $Message = str_replace(array("~otdcLogo~", "~username~", "~usermobile~", "~usermail~", "~useraddress~", "~usergstno~", "~usergstcompany~", "~vendorgst~", "~vendorgstcompany~", "~vendorLogo~", "~orderdate~", "~orderdetails~", "~totalserviceprice~", "~couponname~", "~couponamount~", "~subtotal~", "~gst~", "~ordertotal~", "~paymentmethod~", "~txnid~", "~guidecharge~", "~payuid~"),
                    array($this->site, $OrderMaster->customer_name, $OrderMaster->customer_phone, $OrderMaster->customer_email, $OrderMaster->customer_address1, $customerGSTNo, $customerGSTCompany, $vendorGSTNo, $vendorRegdCompany, $this->site . $Vendor->photo, date("d M Y h:i a", strtotime($OrderMaster->created_at)), $routes, number_format($OrderMaster->total_service_price, 2), $OrderMaster->coupon_name, number_format($OrderMaster->coupon_amount, 2), number_format($OrderMaster->sub_total_price, 2), number_format($OrderMaster->tax_amount, 2), number_format($OrderMaster->total_order_price, 2), $OrderMaster->payment_method, $OrderMaster->transaction_id, number_format($OrderMaster->guide_charge, 2), $PaymentHistory->mihpayid), $RentalInvoice->source);
            $service_mail = $propertyDetails->contact_email;
            if (!empty($propertyDetails->additional_email)) {
                $service_mail = !empty($service_mail) ? $service_mail .','. $propertyDetails->additional_email : $propertyDetails->additional_email;
            }
            $OrderMaster->invoice = $Message;
            $User = User::find($OrderMaster->customer_id);
            $To = $User->email;
            $ConfirmTemplate = EmailTemplate::where('ref_code','hallConfirmMail')->first();
            if (!empty($ConfirmTemplate)) {
                $SubjConfirm = $ConfirmTemplate->subject .' - '. $OrderMaster->service_name .' - Booking ID - '. $OrderMaster->invoice_id;
                $msg = str_replace(array("~vendorLogo~", "~username~", "~servicename~", "~orderdetail~", "~ordertotal~", "~txnid~", "~paymentmethod~", "~termsconditions~", "~guideservice~", "~invoiceid~"),
                        array($this->site . $Vendor->photo, $OrderMaster->customer_name, $OrderMaster->service_name, $route_confirm, number_format($OrderMaster->total_order_price, 2), $OrderMaster->transaction_id, $OrderMaster->payment_method, $propertyDetails->terms_conditions, $guide_text, $OrderMaster->invoice_id), $ConfirmTemplate->source);
                $OrderMaster->confimation_voucher = $msg;
                $msg .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
                try {
                    Mail::to($To)->send(new \App\Mail\RegistrationMailUser($msg, $SubjConfirm));
                }
                catch(\Exception $e) {}
            }

            $admin = User::where('role', 1)->first();
            $invoicemap = '';
            $Vendor_mail = array();
            if ($OrderMaster->service_type != 'merchant') {
                $Vendor = User::find($OrderMaster->vendor_id);
                array_push($Vendor_mail, $Vendor->email);
            }
            $receipent = array_merge($Vendor_mail, array($admin->email));
            if (!empty($service_mail)) {
                $receipent = array_merge($receipent, explode(',', $service_mail));
            }

            $tspinword = parent::AmountInWords($OrderMaster->total_service_price);
            $Message = str_replace('~tspinword~', $tspinword, $Message);
            $frontUrl = str_replace('/tourism/', '/', $this->frontendUrl);
            $EmailBody = '<div style="display:flex;gap:10px;justify-content:space-between;"><p style="width:70%;">Dear '. $OrderMaster->customer_name .',<br><br> please <a href="'. $frontUrl . 'user/flight-booking-history"><b>click here</b></a> to check booking details / cancel booking.<br>Please copy the following url and paste it in your browser if you are unable to click the link. <br><br>'. $frontUrl . 'user/flight-booking-history </p>'. $invoicemap .'</div>';
            $EmailBody .= $Message;
            $EmailBody .= '<div style="margin-top:30px;text-align:center;"><p style="font-family: Segoe UI;color:#333;">Feel free to <a href="https://www.bookodisha.com/tourism/contact">contact us</a> for any further questions or clarifications</p><p style="font-family: Segoe UI;color:#333;"><b>bookodisha.com support team</b></p><p style="font-family: Segoe UI;font-size:11px;color:#999;margin: 0px !important; ">Please do not reply to this message. This email address is automated for delivering outbound messages.<br> Please check the web site for more information&nbsp;<a href="https://www.bookodisha.com/" target="_blank">www.bookodisha.com</a> <br>Copyright &copy; 2022 Odisha Tourism. All rights reserved. <br /> <span style="font-size:16px;"> Powered by&nbsp;&nbsp;&copy;2022-2023&nbsp;<b>Privacy Policy</b><b>&nbsp;</b><b>|&nbsp;</b><b>Odisha Tourism Support</b></span></p><p>&nbsp;</p></div>';
            try {
                Mail::to($To)
                    ->bcc($receipent)
                    ->send(new \App\Mail\RegistrationMailUser($EmailBody, $Subject));
            }
            catch(\Exception $e) {}

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
                if (!empty($propertyDetails->additional_phone))
                    $sms_recipient = array_merge($sms_recipient, explode(",", $propertyDetails->additional_phone));
                if (!empty($sms_recipient)) {
                    $to_sms = implode(',', array_slice($sms_recipient,0,3));
                    parent::sendSms($to_sms, $sms_txt_admin, $SmsTemplateAdmin->templete_id);
                }
            }
        }
    }


    /**
     * Check Hall Availability
     */
    private function checkOfflineHallAvailability(Request $request) {

        $validator = Validator::make(
            $request->all(),
            [
                'property_id' =>'required|integer',
                'book_from' =>'required|in:live,blocked',
                'check_date' =>'required|string',
                'hall_category_id' =>'required|integer',
                'slot_type' =>'required|in:FULL_DAY,FIRST_HALF,SECOND_HALF',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
                'data' => []
            ]);
        }

        try {
            $bookingDate = Carbon::createFromFormat('d M Y',trim($request->check_date))
                    ->startOfDay()
                    ->format('Y-m-d');

        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid booking date format.',
                'data' => []
            ]);
        }

        if ($bookingDate < now()->format('Y-m-d')) {

            return response()->json([
                'status' => 0,
                'message' => 'Previous dates cannot be selected.',
                'data' => []
            ]);
        }


        $propertyId = (int) $request->property_id;
        $slotType = strtoupper(trim($request->slot_type));

        $property = HallProperty::where('id', $propertyId)
                ->where('is_deleted', '0')
                ->where('publish_status','PUBLISH')
                ->where('status','1')
                ->first();

        if (empty($property)) {
            return response()->json([
                'status' => 0,
                'message' => 'Selected property was not found.',
                'data' => []
            ]);
        }


        $halls = DB::table('m_hall as h')
            ->leftJoin('m_hcategory as hc','h.hcategory_id', '=', 'hc.id')
            ->where('h.property_id', $propertyId)
            ->where('h.hcategory_id', $request->hall_category_id)
            ->where('h.status','1')
            ->where('h.is_deleted','0')
            ->select('h.id', 'h.hall_name', 'hc.hcategory_name')
            ->orderBy('h.hall_name','asc')
            ->get();


        $availableHalls = [];
        $GstDetails = [];

        $GSTData =GstDetail::pluck('value','name')->toArray();

        $GstTable = GstTable::where([
                'vendor_id' => $property->vender_id,
                'service_type' => 'hall'
            ])
                ->orderBy('min_amount','asc')
                ->pluck('gst','min_amount')
                ->toArray();

        if (!empty($GstTable)) {
            foreach ($GstTable as $k => $gst) {
                $GstDetails[$k] = json_decode($gst,true);
            }

        } else {
            $GstDetails[0] = $GSTData;
        }

        foreach ($halls as $hall) {
            $liveInventory = DB::table('t_hall_inventory')
                    ->where('hall_id',$hall->id)
                    ->whereDate('inventory_date',$bookingDate)
                    ->first();

            $blockedInventory = DB::table('t_blocked_hall_inventory')
                    ->where('hall_id', $hall->id)
                    ->whereDate('block_date',$bookingDate)
                    ->first();

            if (!$liveInventory) {
                continue;
            }

            $liveFirst =(int)$liveInventory->first_half_available;
            $liveSecond =(int)$liveInventory->second_half_available;

            $blockedFirst = null;
            $blockedSecond = null;

            if ($blockedInventory) {
                $blockedFirst = (int)$blockedInventory->first_half_available;
                $blockedSecond = (int)$blockedInventory->second_half_available;
            }

            $isAvailable = false;

            if ($slotType === 'FULL_DAY') {

                $liveAvailable =
                    $liveFirst === 0 &&
                    $liveSecond === 0;

                $blockedAvailable =
                    !$blockedInventory ||
                    (
                        $blockedFirst === 0 &&
                        $blockedSecond === 0
                    );

                $isAvailable =
                    $liveAvailable &&
                    $blockedAvailable;
            }


            /*
            |--------------------------------------------------------------------------
            | FIRST HALF
            |--------------------------------------------------------------------------
            */

            elseif (
                $slotType ===
                'FIRST_HALF'
            ) {

                $liveAvailable =
                    $liveFirst === 0;

                $blockedAvailable =
                    !$blockedInventory ||
                    $blockedFirst === 0;

                $isAvailable =
                    $liveAvailable &&
                    $blockedAvailable;
            }


            /*
            |--------------------------------------------------------------------------
            | SECOND HALF
            |--------------------------------------------------------------------------
            */

            elseif (
                $slotType ===
                'SECOND_HALF'
            ) {

                $liveAvailable =
                    $liveSecond === 0;

                $blockedAvailable =
                    !$blockedInventory ||
                    $blockedSecond === 0;

                $isAvailable =
                    $liveAvailable &&
                    $blockedAvailable;
            }


            /*
            |--------------------------------------------------------------------------
            | ADD AVAILABLE HALL
            |--------------------------------------------------------------------------
            */

            if (!$isAvailable) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | GET SLOT
            |--------------------------------------------------------------------------
            */

            if (
                $slotType ===
                'FULL_DAY'
            ) {

                $slot = DB::table(
                    'm_slot'
                )
                    ->select(
                        'price',
                        'id'
                    )
                    ->where(
                        'hall_id',
                        $hall->id
                    )
                    ->where(
                        'status',
                        '1'
                    )
                    ->where(
                        'is_deleted',
                        0
                    )
                    ->where(function ($query) {

                        $query
                            ->where(
                                'slot',
                                'FULL_DAY'
                            )
                            ->orWhere(
                                'booking_type',
                                'FULL_DAY'
                            );
                    })
                    ->first();

            } else {

                $slot = DB::table(
                    'm_slot'
                )
                    ->select(
                        'price',
                        'id'
                    )
                    ->where(
                        'hall_id',
                        $hall->id
                    )
                    ->where(
                        'status',
                        '1'
                    )
                    ->where(
                        'is_deleted',
                        0
                    )
                    ->where(function ($query) {

                        $query
                            ->where(
                                'slot',
                                'HALF_DAY'
                            )
                            ->orWhere(
                                'booking_type',
                                'HALF_DAY'
                            );
                    })
                    ->first();
            }


            if (!$slot) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | AVAILABLE HALL DATA
            |--------------------------------------------------------------------------
            */

            $availableHalls[] = [

                'hall_id' =>
                    $hall->id,

                'hall_name' =>
                    $hall->hall_name,

                'hcategory_name' =>
                    $hall->hcategory_name,

                'slot_id' =>
                    $slot->id,

                'booking_type' =>
                    $slotType ===
                    'FULL_DAY'
                        ? 'FULL_DAY'
                        : 'HALF_DAY',

                'slot_type' =>
                    $slotType,

                'available_half' =>
                    $slotType,

                'available_date' =>
                    $bookingDate,

                'price' =>
                    (float) $slot->price,

                'days' =>
                    1,

                'total_price' =>
                    (float) $slot->price
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | NO AVAILABLE HALL
        |--------------------------------------------------------------------------
        */

        if (
            empty($availableHalls)
        ) {

            return response()->json([
                'status' => 0,
                'message' =>
                    'Sorry! No Hall slot is available for the selected date.',
                'data' => []
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 1,
            'message' => '',
            'data' => $availableHalls
        ]);
    }


    /**
     * Validate Hall Slot Availability
    */
    private function validateHallSlotAvailability(int $propertyId, int $hallId, string $requestedSlot,string $bookingDate,
        bool $lockInventory = false,
        string $book_from = 'live'
    ) {

        $requestedSlot = strtoupper(trim($requestedSlot));


        /*
        |--------------------------------------------------------------------------
        | SLOT VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $requestedSlot,
                [
                    'FULL_DAY',
                    'FIRST_HALF',
                    'SECOND_HALF'
                ],
                true
            )
        ) {

            return [
                'available' => false,
                'message' =>
                    'Invalid Hall slot selected.'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | LIVE INVENTORY
        |--------------------------------------------------------------------------
        */

        $liveQuery =
            DB::table(
                't_hall_inventory'
            )
                ->where(
                    'hall_id',
                    $hallId
                )
                ->whereDate(
                    'inventory_date',
                    $bookingDate
                );

        if ($lockInventory) {

            $liveQuery->lockForUpdate();
        }

        $liveInventory =
            $liveQuery->first();


        if (!$liveInventory) {

            return [
                'available' => false,
                'message' =>
                    'Live Hall inventory was not found for ' .
                    Carbon::parse(
                        $bookingDate
                    )->format('d M Y') .
                    '.'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | BLOCKED INVENTORY
        |--------------------------------------------------------------------------
        */

        $blockedQuery =
            DB::table(
                't_blocked_hall_inventory'
            )
                ->where(
                    'hall_id',
                    $hallId
                )
                ->whereDate(
                    'block_date',
                    $bookingDate
                );

        if ($lockInventory) {

            $blockedQuery->lockForUpdate();
        }

        $blockedInventory =
            $blockedQuery->first();


        /*
        |--------------------------------------------------------------------------
        | LIVE INVENTORY STATUS
        |--------------------------------------------------------------------------
        */

        $liveFirst =
            (int)
            $liveInventory->first_half_available;

        $liveSecond =
            (int)
            $liveInventory->second_half_available;


        /*
        |--------------------------------------------------------------------------
        | BLOCKED INVENTORY STATUS
        |--------------------------------------------------------------------------
        */

        $blockedFirst =
            $blockedInventory
                ? (int)
                    $blockedInventory->first_half_available
                : 0;

        $blockedSecond =
            $blockedInventory
                ? (int)
                    $blockedInventory->second_half_available
                : 0;


        /*
        |--------------------------------------------------------------------------
        | FULL DAY
        |--------------------------------------------------------------------------
        */

        if (
            $requestedSlot ===
            'FULL_DAY'
        ) {

            if (
                $liveFirst !== 0 ||
                $liveSecond !== 0
            ) {

                return [
                    'available' => false,
                    'message' =>
                        'Full Day is already booked in live inventory for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                ];
            }


            if (
                $blockedInventory &&
                (
                    $blockedFirst !== 0 ||
                    $blockedSecond !== 0
                )
            ) {

                return [
                    'available' => false,
                    'message' =>
                        'Full Day is blocked for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | FIRST HALF
        |--------------------------------------------------------------------------
        */

        if (
            $requestedSlot ===
            'FIRST_HALF'
        ) {

            if (
                $liveFirst !== 0
            ) {

                return [
                    'available' => false,
                    'message' =>
                        'First Half is already booked in live inventory for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                ];
            }


            if (
                $blockedInventory &&
                $blockedFirst !== 0
            ) {

                return [
                    'available' => false,
                    'message' =>
                        'First Half is blocked for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SECOND HALF
        |--------------------------------------------------------------------------
        */

        if (
            $requestedSlot ===
            'SECOND_HALF'
        ) {

            if (
                $liveSecond !== 0
            ) {

                return [
                    'available' => false,
                    'message' =>
                        'Second Half is already booked in live inventory for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                ];
            }


            if (
                $blockedInventory &&
                $blockedSecond !== 0
            ) {

                return [
                    'available' => false,
                    'message' =>
                        'Second Half is blocked for ' .
                        Carbon::parse(
                            $bookingDate
                        )->format('d M Y') .
                        '.'
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | AVAILABLE
        |--------------------------------------------------------------------------
        */

        return [
            'available' => true,
            'message' => ''
        ];
    }


}
