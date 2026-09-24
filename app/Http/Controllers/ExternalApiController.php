<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExternalApiController extends Controller{

    public function ticketsBookingApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'         => 'nullable|string|in:completed,pending',
            'data_type'      => 'nullable|string|in:all,pagination',
            'booking_date'   => 'nullable|string',
            'service_name'   => 'nullable|string|max:255',
            'order_id'       => 'nullable|string|max:100',
            'invoice_id'     => 'nullable|string|max:100',
            'transaction_id' => 'nullable|string|max:100',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $perPage = 20;
        if($request->filled('per_page')){
            $perPage = $request->input('per_page', 20);
        }

        $query = DB::table('order_masters as o')
            ->join('ticket_bookings as tb', 'tb.booking_id', '=', 'o.order_id')
            ->where('o.service_type', 'ticketing');

        /*
        * Status
        */
        $query->where(
            'o.status',
            $request->input('status', 'completed')
        );

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

            if($startDate > $endDate){
                return response()->json([
                    'status'=>0,
                    'message'=>'Start date cannot be greater than end date'
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
            'o.order_id',
            'o.order_type',
            'o.invoice_id',
            'o.vendor_name',
            'o.transaction_id',
            'o.service_name as ticket_name',
            'o.service_type',
            'o.service_category as event_category',
            'o.status',
            'o.payment_status',
            'o.created_at as booking_date',
            'tb.total_seats',
            'tb.total_adults',
            'tb.total_child',
            'tb.start_date',
            'tb.start_time',
            'tb.end_time',
            'tb.adult_price',
            'tb.child_price',
            'tb.extra_services',
            'tb.service_charge',
            'tb.totalPrice',
            'tb.user_type',
        ]);

        $data_type = 'all';
        if($request->filled('data_type')){
            $data_type = $request->input('data_type','pagination');
        }
        if($data_type == 'all'){
            $ticketing = $query
            ->orderBy('o.id', 'desc')
            ->get();
            return response()->json([
                'status'=>1,
                'message'=>'Success',
                'data'=>$ticketing,
                'total'=>$ticketing->count(),
                'data_type'=>$data_type
            ]);
        }else{
            /*
            * Pagination
            */
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status'  => 1,
                'message' => 'Success',
                'data_type'=>$data_type,
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

    public function tourBookingApi(Request $request){
        $validator = Validator::make($request->all(), [
            'status'         => 'nullable|string|in:completed,pending',
            'data_type'      => 'nullable|string|in:all,pagination',
            'booking_date'   => 'nullable|string',
            'service_name'   => 'nullable|string|max:255',
            'order_id'       => 'nullable|string|max:100',
            'invoice_id'     => 'nullable|string|max:100',
            'transaction_id' => 'nullable|string|max:100',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $perPage = 20;
        if($request->filled('per_page')){
            $perPage = $request->input('per_page', 20);
        }

        $query = DB::table('order_masters as o')
            ->join('tour_bookings as tb', 'tb.booking_id', '=', 'o.order_id')
            ->where('o.service_type', 'tour');

        /*
        * Status
        */
        $query->where(
            'o.status',
            $request->input('status', 'completed')
        );

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

            if($startDate > $endDate){
                return response()->json([
                    'status'=>0,
                    'message'=>'Start date cannot be greater than end date'
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
            'o.order_id',
            'o.order_type',
            'o.invoice_id',
            'o.vendor_name',
            'o.transaction_id',
            'o.service_name as tour_name',
            'o.service_type',
            'o.service_category as category',
            'o.status',
            'o.payment_status',
            'o.created_at as booking_date',
            'tb.total_seats',
            'tb.total_adults',
            'tb.total_child',
            'o.start_date',
            'o.end_date',
            'tb.adult_price',
            'tb.child_price',
            'tb.price_breakup',
            'tb.service_charge',
            'tb.totalPrice',
            'tb.user_type',
        ]);

        $data_type = 'all';
        if($request->filled('data_type')){
            $data_type = $request->input('data_type','pagination');
        }
        if($data_type == 'all'){
            $ticketing = $query
            ->orderBy('o.id', 'desc')
            ->get();
            return response()->json([
                'status'=>1,
                'message'=>'Success',
                'data'=>$ticketing,
                'total'=>$ticketing->count(),
                'data_type'=>$data_type
            ]);
        }else{
            /*
            * Pagination
            */
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status'  => 1,
                'message' => 'Success',
                'data_type'=>$data_type,
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

    public function hotelBookingApi(Request $request){
        $validator = Validator::make($request->all(), [
            'status'         => 'nullable|string|in:completed,pending',
            'data_type'      => 'nullable|string|in:all,pagination',
            'booking_date'   => 'nullable|string',
            'service_name'   => 'nullable|string|max:255',
            'order_id'       => 'nullable|string|max:100',
            'invoice_id'     => 'nullable|string|max:100',
            'transaction_id' => 'nullable|string|max:100',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $perPage = 20;
        if($request->filled('per_page')){
            $perPage = $request->input('per_page', 20);
        }

        $query = DB::table('order_masters as o')
            ->join('hotel_room_bookings as tb', 'tb.booking_id', '=', 'o.order_id')
            ->where('o.service_type', 'hotel');

        /*
        * Status
        */
        $query->where(
            'o.status',
            $request->input('status', 'completed')
        );

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

            if($startDate > $endDate){
                return response()->json([
                    'status'=>0,
                    'message'=>'Start date cannot be greater than end date'
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
            'o.created_at as booking_date',
            'o.room_details',
            'o.room_request',
            'o.total_rooms',
            'o.start_date',
            'o.end_date',
            'tb.adult as adult_count',
            'tb.children as children_count',
            'tb.extra_person_price',
            'tb.pricing_details',
            'tb.request_for',
            'tb.totalPrice',
            'tb.user_type',
        ]);

        $data_type = 'all';
        if($request->filled('data_type')){
            $data_type = $request->input('data_type','pagination');
        }
        if($data_type == 'all'){
            $ticketing = $query
            ->orderBy('o.id', 'desc')
            ->get();
            return response()->json([
                'status'=>1,
                'message'=>'Success',
                'data'=>$ticketing,
                'total'=>$ticketing->count(),
                'data_type'=>$data_type
            ]);
        }else{
            /*
            * Pagination
            */
            $ticketing = $query
                ->orderBy('o.id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status'  => 1,
                'message' => 'Success',
                'data_type'=>$data_type,
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
