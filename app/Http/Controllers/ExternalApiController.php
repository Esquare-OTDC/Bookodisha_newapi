<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExternalApiController extends Controller
{

    private $clientId = 'BookOdisha@2026';
    private $clientSecret = 'Vb4ZS1YdBMb^mLHfVp+F';

    public function __construct(Request $request)
    {
        $clientIdKey = $request->header('Client-ID');
        $clientSecretKey = $request->header('Client-Secret');

        if ($this->clientId !== $clientIdKey || $this->clientSecret !== $clientSecretKey) {
            abort(response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401));
        }
    }

    public function ticketsBookingApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'         => 'nullable|string|in:completed,cancelled',
            'data_type'      => 'nullable|string|in:all,pagination',
            'book_from'      => 'nullable|string|in:blocked',
            'booking_date'   => 'nullable|string',
            'service_name'   => 'nullable|string|max:255',
            'order_id'       => 'nullable|string|max:100',
            'invoice_id'     => 'nullable|string|max:100',
            'transaction_id' => 'nullable|string|max:100',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
            'fetch_from'     => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $perPage = 20;
        if ($request->filled('per_page')) {
            $perPage = $request->input('per_page', 20);
        }

        $query = DB::table('order_masters as o')
            ->leftjoin('ticket_bookings as tb', 'tb.booking_id', '=', 'o.order_id')
            ->where('o.service_type', 'ticketing')
            ->where('o.payment_status', 'success');

        /*
        * Status
        */
        // $query->whereIn(
        //     'o.status',
        //     [$request->input('status', 'completed'), 'cancelled']
        // );m

        if ($request->filled('status')) {
            $query->where('o.status', $request->input('status', 'completed'));
        } else {
            $query->whereIn('o.status', ['completed', 'cancelled']);
        }

        if($request->filled('fetch_from')) {
            $query->where('o.created_at', '>=', $request->fetch_from);
        }

        if ($request->filled('book_from')) {
            $query->where('book_from', $request->input('book_from', 'blocked'));
        }

        /*
        * Booking date
        * Expected format:
        * 2026-09-01 - 2026-09-17
        */
        if ($request->filled('booking_date')) {

            $dates = explode(' - ', $request->booking_date);

            if (count($dates) !== 2) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Invalid booking_date format. Expected: YYYY-MM-DD - YYYY-MM-DD',
                ], 422);
            }

            $startDate = date('Y-m-d', strtotime(trim($dates[0])));
            $endDate   = date('Y-m-d', strtotime(trim($dates[1])));

            if ($startDate > $endDate) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Start date cannot be greater than end date'
                ], 422);
            }

            $query->whereBetween('o.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        /*
        * Service name
        */
        if ($request->filled('service_name')) {
            $query->where(
                'o.service_name',
                'LIKE',
                '%' . $request->service_name . '%'
            );
        }

        /*
        * Order ID
        */
        if ($request->filled('order_id')) {
            $query->where('o.order_id', $request->order_id);
        }

        /*
        * Invoice ID
        */
        if ($request->filled('invoice_id')) {
            $query->where(
                'o.invoice_id',
                'LIKE',
                '%' . $request->invoice_id . '%'
            );
        }

        /*
        * Transaction ID
        */
        if ($request->filled('transaction_id')) {
            $query->where(
                'o.transaction_id',
                'LIKE',
                '%' . $request->transaction_id . '%'
            );
        }

        /*
        * Select only required columns
        */
        $query->select([
            'o.service_name_id as ticket_id',
            'o.order_id',
            'o.order_type',
            'o.book_from',
            'o.invoice_id',
            'o.vendor_name',
            'o.transaction_id',
            'o.service_name as ticket_name',
            'o.service_type',
            'o.service_category as event_category',
            'o.status',
            'o.payment_status',
            'o.created_at as booking_date',
            'o.total_guests as total_seats',
            'o.total_adults',
            'o.total_child',
            'o.start_date',
            'o.start_time',
            'o.end_time',
            'o.adult_price',
            'o.child_price',
            'tb.extra_services',
            'o.rental_breakdown as price_breakup',
            'o.service_charge',
            'o.total_order_price as totalPrice',
            'tb.user_type',
            'o.updated_at',
             DB::raw('
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at) = 0 THEN "Not modified"
                    WHEN TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at), " minutes ago")
                    WHEN TIMESTAMPDIFF(HOUR,o.created_at, o.updated_at) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR,o.created_at, o.updated_at), " hours ago")
                    WHEN TIMESTAMPDIFF(DAY,o.created_at, o.updated_at) < 30 THEN CONCAT(TIMESTAMPDIFF(DAY,o.created_at, o.updated_at), " days ago")
                    WHEN TIMESTAMPDIFF(MONTH,o.created_at, o.updated_at) < 12 THEN CONCAT(TIMESTAMPDIFF(MONTH,o.created_at, o.updated_at), " months ago")
                    ELSE CONCAT(TIMESTAMPDIFF(YEAR,o.created_at, o.updated_at), " years ago")
                END as modified_since
            ')
        ]);

        $data_type = 'all';
        if ($request->filled('data_type')) {
            $data_type = $request->input('data_type', 'pagination');
        }
        if ($data_type == 'all') {
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->get();
            return response()->json([
                'status' => 1,
                'message' => 'Success',
                'data' => $ticketing,
                'total' => $ticketing->count(),
                'data_type' => $data_type
            ]);
        } else {
            /*
            * Pagination
            */
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status'  => 1,
                'message' => 'Success',
                'data_type' => $data_type,
                'data'    => $ticketing->items(),
                'pagination' => [
                    'current_page' => $ticketing->currentPage(),
                    'per_page'     => $ticketing->perPage(),
                    'total'        => $ticketing->total(),
                    'last_page'    => $ticketing->lastPage(),
                    'from'         => $ticketing->firstItem(),
                    'to'           => $ticketing->lastItem(),
                ],
            ], 200);
        }
    }

    public function tourBookingApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'         => 'nullable|string|in:completed,cancelled',
            'data_type'      => 'nullable|string|in:all,pagination',
            'book_from'      => 'nullable|string|in:blocked',
            'booking_date'   => 'nullable|string',
            'service_name'   => 'nullable|string|max:255',
            'order_id'       => 'nullable|string|max:100',
            'invoice_id'     => 'nullable|string|max:100',
            'transaction_id' => 'nullable|string|max:100',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
            'fetch_from'     => 'nullable|date_format:Y-m-d H:i:s',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $perPage = 20;
        if ($request->filled('per_page')) {
            $perPage = $request->input('per_page', 20);
        }

        $query = DB::table('order_masters as o')
            ->leftjoin('tour_bookings as tb', 'tb.booking_id', '=', 'o.order_id')
            ->where('o.service_type', 'tour')
            ->where('o.payment_status', 'success');

        /*
        * Status
        */
        // $query->whereIn(
        //     'o.status',
        //     [$request->input('status', 'completed'), 'cancelled']
        // );

        if ($request->filled('status')) {
            $query->where('o.status', $request->input('status', 'completed'));
        } else {
            $query->whereIn('o.status', ['completed', 'cancelled']);
        }

        if ($request->filled('book_from')) {
            $query->where('book_from', $request->input('book_from', 'blocked'));
        }

        /*
        * Booking date
        * Expected format:
        * 2026-09-01 - 2026-09-17
        */
        if ($request->filled('booking_date')) {

            $dates = explode(' - ', $request->booking_date);

            if (count($dates) !== 2) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Invalid booking_date format. Expected: YYYY-MM-DD - YYYY-MM-DD',
                ], 422);
            }

            $startDate = date('Y-m-d', strtotime(trim($dates[0])));
            $endDate   = date('Y-m-d', strtotime(trim($dates[1])));

            if ($startDate > $endDate) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Start date cannot be greater than end date'
                ], 422);
            }

            $query->whereBetween('o.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }
         /*
        * Fetch Form Record
        */
        if($request->filled('fetch_from')) {
            $query->where('o.created_at', '>=', $request->fetch_from);
        }

        /*
        * Service name
        */
        if ($request->filled('service_name')) {
            $query->where(
                'o.service_name',
                'LIKE',
                '%' . $request->service_name . '%'
            );
        }

        /*
        * Order ID
        */
        if ($request->filled('order_id')) {
            $query->where('o.order_id', $request->order_id);
        }

        /*
        * Invoice ID
        */
        if ($request->filled('invoice_id')) {
            $query->where(
                'o.invoice_id',
                'LIKE',
                '%' . $request->invoice_id . '%'
            );
        }

        /*
        * Transaction ID
        */
        if ($request->filled('transaction_id')) {
            $query->where(
                'o.transaction_id',
                'LIKE',
                '%' . $request->transaction_id . '%'
            );
        }

        /*
        * Select only required columns
        */
        $query->select([
            'o.service_name_id as tour_id',
            'o.order_id',
            'o.order_type',
            'o.book_from',
            'o.invoice_id',
            'o.vendor_name',
            'o.transaction_id',
            'o.service_name as tour_name',
            'o.service_type',
            'o.service_category as category',
            'o.status',
            'o.payment_status',
            'o.created_at as booking_date',
            'o.total_guests as total_seats',
            'o.total_adults',
            'o.total_child',
            'o.start_date',
            'o.end_date',
            'o.adult_price',
            'o.child_price',
            'o.rental_breakdown as price_breakup',
            'o.service_charge',
            'o.total_order_price as totalPrice',
            'tb.user_type',
            'o.updated_at',
            DB::raw('
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at) = 0 THEN "Not modified"
                    WHEN TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at), " minutes ago")
                    WHEN TIMESTAMPDIFF(HOUR,o.created_at, o.updated_at) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR,o.created_at, o.updated_at), " hours ago")
                    WHEN TIMESTAMPDIFF(DAY,o.created_at, o.updated_at) < 30 THEN CONCAT(TIMESTAMPDIFF(DAY,o.created_at, o.updated_at), " days ago")
                    WHEN TIMESTAMPDIFF(MONTH,o.created_at, o.updated_at) < 12 THEN CONCAT(TIMESTAMPDIFF(MONTH,o.created_at, o.updated_at), " months ago")
                    ELSE CONCAT(TIMESTAMPDIFF(YEAR,o.created_at, o.updated_at), " years ago")
                END as modified_since
            ')
        ]);

        $data_type = 'all';
        if ($request->filled('data_type')) {
            $data_type = $request->input('data_type', 'pagination');
        }
        if ($data_type == 'all') {
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->get();
            return response()->json([
                'status' => 1,
                'message' => 'Success',
                'data' => $ticketing,
                'total' => $ticketing->count(),
                'data_type' => $data_type
            ]);
        } else {
            /*
            * Pagination
            */
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status'  => 1,
                'message' => 'Success',
                'data_type' => $data_type,
                'data'    => $ticketing->items(),
                'pagination' => [
                    'current_page' => $ticketing->currentPage(),
                    'per_page'     => $ticketing->perPage(),
                    'total'        => $ticketing->total(),
                    'last_page'    => $ticketing->lastPage(),
                    'from'         => $ticketing->firstItem(),
                    'to'           => $ticketing->lastItem(),
                ],
            ], 200);
        }
    }

    public function hotelBookingApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'         => 'nullable|string|in:completed,cancelled',
            'data_type'      => 'nullable|string|in:all,pagination',
            'book_from'      => 'nullable|string|in:blocked',
            'booking_date'   => 'nullable|string',
            'service_name'   => 'nullable|string|max:255',
            'order_id'       => 'nullable|string|max:100',
            'invoice_id'     => 'nullable|string|max:100',
            'transaction_id' => 'nullable|string|max:100',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
            'fetch_from'     => 'nullable|date_format:Y-m-d H:i:s',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $perPage = 20;
        if ($request->filled('per_page')) {
            $perPage = $request->input('per_page', 20);
        }

        $query = DB::table('order_masters as o')
            ->leftjoin('hotel_room_bookings as tb', 'tb.booking_id', '=', 'o.order_id')
            ->where('o.service_type', 'hotel')
            ->where('o.payment_status', 'success');

        /*
        * Status
        */
        // $query->whereIn(
        //     'o.status',
        //     [$request->input('status', 'completed'), 'cancelled']
        // );
        if ($request->filled('status')) {
            $query->where('o.status', $request->input('status', 'completed'));
        } else {
            $query->whereIn('o.status', ['completed', 'cancelled']);
        }

        if ($request->filled('book_from')) {
            $query->where('book_from', $request->input('book_from', 'blocked'));
        }
        /*
        * Booking date
        * Expected format:
        * 2026-09-01 - 2026-09-17
        */
        if ($request->filled('booking_date')) {

            $dates = explode(' - ', $request->booking_date);

            if (count($dates) !== 2) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Invalid booking_date format. Expected: YYYY-MM-DD - YYYY-MM-DD',
                ], 422);
            }

            $startDate = date('Y-m-d', strtotime(trim($dates[0])));
            $endDate   = date('Y-m-d', strtotime(trim($dates[1])));

            if ($startDate > $endDate) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Start date cannot be greater than end date'
                ], 422);
            }

            $query->whereBetween('o.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        if($request->filled('fetch_from')) {
            $query->where('o.created_at', '>=', $request->fetch_from);
        }

        /*
        * Service name
        */
        if ($request->filled('service_name')) {
            $query->where(
                'o.service_name',
                'LIKE',
                '%' . $request->service_name . '%'
            );
        }

        /*
        * Order ID
        */
        if ($request->filled('order_id')) {
            $query->where('o.order_id', $request->order_id);
        }

        /*
        * Invoice ID
        */
        if ($request->filled('invoice_id')) {
            $query->where(
                'o.invoice_id',
                'LIKE',
                '%' . $request->invoice_id . '%'
            );
        }

        /*
        * Transaction ID
        */
        if ($request->filled('transaction_id')) {
            $query->where(
                'o.transaction_id',
                'LIKE',
                '%' . $request->transaction_id . '%'
            );
        }

        /*
        * Select only required columns
        */
        $query->select([
            'o.service_name_id as hotel_id',
            'o.order_id',
            'o.order_type',
            'o.invoice_id',
            'o.vendor_name',
            'o.transaction_id',
            'o.service_name as hotel_name',
            'o.service_type',
            'o.service_category as event_category',
            'o.status',
            'o.payment_status',
            'o.book_from',
            'o.created_at as booking_date',
            'o.room_details',
            'o.room_request',
            'o.total_rooms',
            'o.start_date',
            'o.end_date',
            'o.total_adults as adult_count',
            'o.total_child as children_count',
            'tb.extra_person_price',
            'tb.pricing_details',
            'tb.request_for',
            'o.total_order_price as totalPrice',
            'tb.user_type',
            'o.updated_at',
            DB::raw('
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at) = 0 THEN "Not modified"
                    WHEN TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE,o.created_at, o.updated_at), " minutes ago")
                    WHEN TIMESTAMPDIFF(HOUR,o.created_at, o.updated_at) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR,o.created_at, o.updated_at), " hours ago")
                    WHEN TIMESTAMPDIFF(DAY,o.created_at, o.updated_at) < 30 THEN CONCAT(TIMESTAMPDIFF(DAY,o.created_at, o.updated_at), " days ago")
                    WHEN TIMESTAMPDIFF(MONTH,o.created_at, o.updated_at) < 12 THEN CONCAT(TIMESTAMPDIFF(MONTH,o.created_at, o.updated_at), " months ago")
                    ELSE CONCAT(TIMESTAMPDIFF(YEAR,o.created_at, o.updated_at), " years ago")
                END as modified_since
            ')
        ]);

        $data_type = 'all';
        if ($request->filled('data_type')) {
            $data_type = $request->input('data_type', 'pagination');
        }
        if ($data_type == 'all') {
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->get();
            return response()->json([
                'status' => 1,
                'message' => 'Success',
                'data' => $ticketing,
                'total' => $ticketing->count(),
                'data_type' => $data_type
            ]);
        } else {
            /*
            * Pagination
            */
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status'  => 1,
                'message' => 'Success',
                'data_type' => $data_type,
                'data'    => $ticketing->items(),
                'pagination' => [
                    'current_page' => $ticketing->currentPage(),
                    'per_page'     => $ticketing->perPage(),
                    'total'        => $ticketing->total(),
                    'last_page'    => $ticketing->lastPage(),
                    'from'         => $ticketing->firstItem(),
                    'to'           => $ticketing->lastItem(),
                ],
            ], 200);
        }
    }
}
