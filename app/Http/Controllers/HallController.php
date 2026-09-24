<?php

namespace App\Http\Controllers;

use App\SubuserAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HallController extends Controller
{
    public function hallOrders()
    {
        $OfflineAgents = [];

        $MasterHall = DB::table('m_hall')
            ->where('is_deleted', 0)
            ->where('status', '1')
            ->orderBy('hall_name', 'asc')
            ->pluck('hall_name', 'id');

        return view('hall.hall-orders', compact('OfflineAgents', 'MasterHall'));
    }

    public function getHallOrders(Request $request)
    {
            $aColumns = [
                'invoice_id',
                'service_name',
                'customer_name',
                'customer_phone',
                'created_at',
                'slot',
                'total_order_price',
                'order_type',
                'status',
                'payment_method',
                'payment_status',
                'transaction_id',
                'id',
                'customer_email'
            ];

            $sIndexColumn = 'id';
            $sTable = 'order_masters';

            $sLimit = '';

            if ($request->filled('start') && $request->input('length') != '-1') {
                $sLimit = ' LIMIT '
                    . intval($request->input('start'))
                    . ', '
                    . intval($request->input('length'));
            }

            $sOrder = ' ORDER BY created_at DESC ';

            if ($request->has('order')) {
                $orders = $request->input('order', []);
                $columns = $request->input('columns', []);

                $orderParts = [];

                foreach ($orders as $order) {
                    $columnIndex = intval($order['column']);

                    if (
                        isset($columns[$columnIndex]) &&
                        $columns[$columnIndex]['orderable'] === 'true' &&
                        isset($aColumns[$columnIndex])
                    ) {
                        if ($aColumns[$columnIndex] !== 'slot') {
                            $direction = $order['dir'] === 'asc' ? 'ASC' : 'DESC';
                            $orderParts[] = '`' . $aColumns[$columnIndex] . '` ' . $direction;
                        }
                    }
                }

                if (!empty($orderParts)) {
                    $sOrder = ' ORDER BY ' . implode(', ', $orderParts);
                }
            }

            $vendorCondition = '';
            $staffCondition = '';

            if (Auth::user()->access_type == 'vendor') {
                $vendorId = Auth::user()->role == 2
                    ? Auth::user()->id
                    : Auth::user()->vendor_id;

                $vendorCondition = ' AND vendor_id = ' . intval($vendorId);

                if (Auth::user()->role == 3) {
                    $subuserAccess = SubuserAccess::where([
                            'user_id' => Auth::user()->id,
                            'service' => 'hall'
                        ])
                        ->pluck('service_id', 'id')
                        ->toArray();

                    if (!empty($subuserAccess)) {
                        $serviceIds = array_map('intval', array_values($subuserAccess));
                        $staffCondition .= ' AND service_name_id IN (' . implode(',', $serviceIds) . ')';
                    }

                    if (Auth::user()->user_role == 'agent_staff') {
                        $staffCondition .= ' AND customer_id = "' . intval(Auth::user()->id) . '"';
                    }
                }
            }

            $sWhere = ' WHERE 1'
                . $vendorCondition
                . ' AND service_type = "hall"'
                . ' AND status != "partially-cancelled"'
                . $staffCondition;

            $bookingStatus = parent::cleanString($request->input('searchValue1', 'all'));

            if ($bookingStatus === 'cancelled') {
                $sWhere .= ' AND (
                    (
                        status = "cancelled"
                        OR status = "partially-cancelled"
                    )
                    AND payment_status = "success"
                )';
            } elseif ($bookingStatus !== '' && $bookingStatus !== 'all') {
                $sWhere .= ' AND status = "' . $bookingStatus . '"';
            }

            $searchColumns = [
                'service_name',
                'invoice_id',
                'transaction_id',
                'order_type',
                'created_at',
                'start_date',
                'end_date',
                'service_name_id',
                'payment_gateway',
                'customer_id',
                'request_from',
                'book_from',
                'slot'
            ];

            $customColumn = $request->input('searchValue3');
            $customValue = $request->input('searchValue4');

            if (
                !empty($customColumn) &&
                !empty($customValue) &&
                in_array($customColumn, $searchColumns)
            ) {
                if (
                    $customColumn === 'created_at' ||
                    $customColumn === 'start_date' ||
                    $customColumn === 'end_date'
                ) {
                    $dates = explode(' - ', $customValue);

                    if (count($dates) === 2) {
                        $startDate = date('Y-m-d', strtotime($dates[0]));
                        $endDate = date('Y-m-d', strtotime($dates[1]));

                        $sWhere .= ' AND `' . $customColumn . '` BETWEEN "'
                            . $startDate . ' 00:00:00" AND "'
                            . $endDate . ' 23:59:59"';
                    }
                } elseif ($customColumn === 'slot') {
                    $cleanValue = parent::cleanString($customValue);

                    $sWhere .= ' AND EXISTS (
                        SELECT 1
                        FROM t_booking
                        WHERE t_booking.vendor_id = 1
                        AND t_booking.is_deleted = 0
                        AND t_booking.slot_type LIKE "%' . $cleanValue . '%"
                    )';
                } else {
                    $cleanValue = parent::cleanString($customValue);
                    $sWhere .= ' AND `' . $customColumn . '` LIKE "%' . $cleanValue . '%"';
                }
            }

            if ($request->filled('searchValue5')) {
                $hallId = intval($request->input('searchValue5'));
                $sWhere .= ' AND service_name_id = ' . $hallId;
            }

            if ($request->filled('searchValue9')) {
                $orderType = parent::cleanString($request->input('searchValue9'));
                $sWhere .= ' AND order_type = "' . $orderType . '"';
            }

            if ($request->filled('searchValue10')) {
                $paymentMethod = parent::cleanString($request->input('searchValue10'));
                $sWhere .= ' AND payment_gateway = "' . $paymentMethod . '"';
            }

            $globalSearch = $request->input('search.value');

            if (!empty($globalSearch)) {
                $globalSearch = parent::cleanString($globalSearch);

                $searchParts = [];

                foreach ($aColumns as $column) {
                    if ($column === 'slot') {
                        continue;
                    }

                    $searchParts[] = '`' . $column . '` LIKE "%' . $globalSearch . '%"';
                }

                $sWhere .= ' AND (' . implode(' OR ', $searchParts) . ')';
            }

            $exportQuery = "SELECT * FROM {$sTable} {$sWhere} {$sOrder}";

            $printQuery = $dataQuery =
                "SELECT SQL_CALC_FOUND_ROWS *
                FROM {$sTable}
                {$sWhere}
                {$sOrder}
                {$sLimit}";

            $results = DB::select($dataQuery);

            $filteredResult = DB::select('SELECT FOUND_ROWS() AS totalrow');
            $filteredTotal = $filteredResult[0]->totalrow ?? 0;

            $totalResult = DB::select(
                "SELECT COUNT(`{$sIndexColumn}`) AS countindex
                FROM {$sTable}
                {$sWhere}"
            );

            $total = $totalResult[0]->countindex ?? 0;

            $output = [
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $total,
                'recordsFiltered' => $filteredTotal,
                'data' => []
            ];

            foreach ($results as $order) {
                $cancelOption = '';
                $cancelPolicyOption = '';
                $modifyOption = '';

                if ($order->status === 'completed' && $order->payment_status == 'success' && ($order->start_date >= date("Y-m-d") || $order->order_type == 'CMO' || Auth::user()->access_type == 'superadmin')) {
                    $cancelOption = '
                        <li>
                            <a href="javascript:void(0);"
                            class="cancel_booking"
                            data-status="' . $order->status . '"
                            data-id="' . $order->id . '"
                            data-amount="' . $order->total_order_price . '">
                                Cancel Booking (Refund full)
                            </a>
                        </li>';

                    $cancelPolicyOption = '
                        <li>
                            <a href="javascript:void(0);"
                            class="cancel_booking_policy"
                            data-status="' . $order->status . '"
                            data-id="' . $order->id . '"
                            data-toggle="modal"
                            data-target="#cancelPolicyModal">
                                Cancel Booking (Refund As policy)
                            </a>
                        </li>';

                }elseif ($order->status == 'pending' && $order->order_type == 'offline' && $order->payment_gateway == 'hdfc' && ($order->offline_link_expiry < date("Y-m-d H:i:s") ||  $order->start_date <= date("Y-m-d"))) {
                    $cancelOption = '<li><a href="javascript:void(0);" class="cancel_booking" data-status="' . $order->status . '" data-id="' . $order->id . '">Cancel Booking</a></li>';
                }

                $part_th_date = date("Y-m-d", strtotime($order->start_date .' +3 Days'));
                if($order->status != 'cancelled' && $order->status != 'partially-cancelled' && $order->status != 'pending' && ($order->start_date > date("Y-m-d") || $part_th_date >= date("Y-m-d"))){

                    $modifyOption = '
                        <li>
                            <a href="' . url('hall-modify-booking/' . $order->id) . '"
                            class="modify_booking"
                            data-id="' . $order->id . '"
                            target="_blank">
                                Modify Booking
                            </a>
                        </li>';
                }


                $orderType = $order->order_type;

                if (
                    $order->order_type !== 'online' &&
                    isset($order->book_from) &&
                    $order->book_from === 'blocked'
                ) {
                    $orderType .= '<br>(' . $order->book_from . ')';
                }

                $possibleBookingIds = [];

                if (isset($order->booking_id) && !empty($order->booking_id)) {
                    $possibleBookingIds[] = $order->booking_id;
                }

                if (isset($order->invoice_id) && !empty($order->invoice_id)) {
                    $possibleBookingIds[] = $order->invoice_id;
                }

                if (isset($order->order_id) && !empty($order->order_id)) {
                    $possibleBookingIds[] = $order->order_id;
                }

                $possibleBookingIds = array_unique($possibleBookingIds);

                $bookingSlotsQuery = DB::table('t_booking')
                    ->where('vendor_id', 1)
                    ->where('is_deleted', 0);

                if (!empty($order->service_name_id)) {
                    $bookingSlotsQuery->where('hall_id', $order->service_name_id);
                }

                $bookingSlotsQuery->where(function ($query) use ($possibleBookingIds, $order) {
                    if (!empty($possibleBookingIds)) {
                        $query->whereIn('booking_id', $possibleBookingIds);
                    }

                    if (!empty($order->id)) {
                        if (!empty($possibleBookingIds)) {
                            $query->orWhere('booking_id', 'LIKE', '%-' . $order->id);
                        } else {
                            $query->where('booking_id', 'LIKE', '%-' . $order->id);
                        }
                    }
                });

                $bookingSlots = $bookingSlotsQuery
                    ->select('slot_type', 'start_date', 'start_time', 'end_date', 'end_time')
                    ->get();

                $slotData = [];

                foreach ($bookingSlots as $bookingSlot) {
                    $slotName = ucwords(strtolower(str_replace('_', ' ', $bookingSlot->slot_type)));

                    if (!empty($bookingSlot->start_time) && !empty($bookingSlot->end_time)) {
                        $slotName .= '<br>'
                            . $bookingSlot->start_time
                            . ' - '
                            . $bookingSlot->end_time;
                    }

                    $slotData[] = $slotName;
                }

                $slot = !empty($slotData)
                    ? implode('<hr style="margin: 3px 0;">', $slotData)
                    : 'N/A';

                $transactionId = !empty($order->transaction_id)
                    ? $order->transaction_id
                    : 'Nill';

                $action = '
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-info btn-xs btn-outline dropdown-toggle waves-effect waves-light"
                                data-toggle="dropdown"
                                aria-expanded="false">
                            Action <span class="caret"></span>
                        </button>
                        <ul role="menu" class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a href="javascript:void(0);"
                                class="order_details"
                                data-id="' . $order->id . '"
                                data-toggle="modal"
                                data-target="#orderDetailsModal">
                                    Order Details
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);"
                                class="user_details"
                                data-id="' . $order->id . '"
                                data-toggle="modal"
                                data-target="#userDetailsModal">
                                    User Details
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0);"
                                class="update_order"
                                data-id="' . $order->id . '"
                                data-toggle="modal"
                                data-target="#updateOrderModal">
                                    Update Booking Details
                                </a>
                            </li>
                            ' . $cancelOption . '
                            ' . $cancelPolicyOption . '
                            ' . $modifyOption . '
                        </ul>
                    </div>';

                $row = [];
                $row[] = $order->invoice_id;
                $row[] = $order->service_name;
                $row[] = $order->customer_name;
                $row[] = $order->customer_phone;
                $row[] = date('M d Y H:i:s', strtotime($order->created_at));
                $row[] = $slot;
                $row[] = $order->total_order_price;
                $row[] = $orderType;
                $row[] = $order->status;
                $row[] = $order->payment_method;
                $row[] = $order->payment_status;
                $row[] = $transactionId;
                $row[] = $action;

                $output['data'][] = $row;
            }

            $output['exportQuery'] = $exportQuery;
            $output['printQuery'] = $printQuery;

            return response()->json($output);
    }

}
