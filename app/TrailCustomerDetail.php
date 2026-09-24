<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class TrailCustomerDetail extends Model {

    protected $table = 't_trail_customer_details';

    private $stateId = 4013; // Odisha State User Count

    private $isPaymentCompelete = false;

    private $user_customer_id;
    private $service_id;
    private $book_date;
    private $slot_start_time;
    private $slot_end_time;

    private $customerCount = 0;

    private $primaryCustomerIds = [];

    private $errors = '';

    protected $fillable = [
        'parent_customer_id',
        'service_name',
        'service_name_id',
        'country_id',
        'booking_id',
        'name',
        'phone',
        'state_id',
        'pincode',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
        'booking_date',
        'start_time',
        'end_time'
    ];


    public function setPrimaryCustomer($parent_customer_id, $service_name_id, $booking_date, $start_time, $end_time){

        $this->user_customer_id = $parent_customer_id;
        $this->service_id = $service_name_id;
        $this->book_date = $booking_date;
        $this->slot_start_time = $start_time;
        $this->slot_end_time = $end_time;
        return $this;
    }

    public function getAllPrimaryData(){
        return [
            'user_customer_id'=>$this->user_customer_id,
            'service_id'=>$this->service_id,
            'book_date'=>$this->book_date,
            'slot_start_time'=>$this->slot_start_time,
            'slot_end_time'=>$this->slot_end_time
        ];
    }

    public function getStateCountUser(){

        if (! $this->primaryCustomer()->exists()) {
            $this->errors = 'User does not exists. Please try again after sometime ';
            $this->customerCount = 0;
            return $this;
        }

        if($this->isPaymentCompelete){
            $this->errors = 'User already booked tickets for this event slot ';
            $this->customerCount = 0;
            return $this;
        }

        $count = self::where('state_id', $this->stateId)
                ->where('service_name_id', $this->service_id)
                ->where('booking_date', $this->book_date)
                ->where('start_time', $this->slot_start_time)
                ->where('end_time', $this->slot_end_time);

        if(!empty($this->primaryCustomerIds) && count($this->primaryCustomerIds) > 0){
            $count = $count->whereIn('booking_id', $this->primaryCustomerIds);
            $count =  $count->get()->count();
        }else{
            $count = 0;
        }

        
        $this->customerCount = $count + $this->customerCount;
        if($this->customerCount == 0 && !in_array($this->user_customer_id, $this->primaryCustomerIds)){
            //$this->customerCount = 1;
            $this->primaryCustomerIds[] = $this->user_customer_id;
        }
        return $this;
    }

    public function primaryCustomer(){
        return User::where('id', $this->user_customer_id);
    }

    public function isPreviousPaymentCompelete(){
        $isOrderPayment = OrderMaster::where('service_type','ticketing')
                        ->where('service_category','Events')
                        ->where('service_name_id', $this->service_id)
                        ->where('start_date', $this->book_date)
                        ->where('start_time', $this->slot_start_time)
                        ->where('end_time', $this->slot_end_time)
                        ->where(function($q){
                            //$q->whereIn('status', ['completed','pending']);
                            $q->whereIn('payment_status', ['success','pending']);
                        });
        $isOrderPaymentCount = clone $isOrderPayment;
        $isOrderPaymentCount = $isOrderPaymentCount->where('customer_id', $this->user_customer_id)->get()->count();
       if($isOrderPaymentCount > 0){
            $this->isPaymentCompelete = true;
            return $this;
       }
       //$this->customerCount = $this->totalPrimaryUserWithOdishaStateApplied();
       $this->totalPrimaryUserWithOdishaStateApplied();
       return $this;
    }

    public function totalPrimaryUserWithOdishaStateApplied(){
        $userCount = DB::table('order_masters as OM')
                     ->select('OM.customer_id','OM.order_id')
                     ->join('users as U', 'U.id', '=','OM.customer_id')
                     ->where('OM.service_type','ticketing')
                     ->where('OM.service_category','Events')
                     ->where('OM.service_name_id', $this->service_id)
                     ->where('OM.start_date', $this->book_date)
                     ->where('OM.start_time', $this->slot_start_time)
                     ->whereIn('OM.payment_status', ['success','pending'])
                     ->where('OM.end_time', $this->slot_end_time)->groupBy('OM.order_id','OM.customer_id');

        $userIds = clone $userCount;

        $this->primaryCustomerIds = $userIds->pluck('OM.order_id')->toArray();

        $userCount = $userCount->get()->count();

        return $userCount;
    }

    public function getErrors(){
        return $this->errors;
    }

    public function getCustomerOrderCount(){
        if($this->customerCount == 0){
            //$user = $this->primaryCustomer()->first();
            //$this->errors = 'User ('.$user->first_name.' '.$user->last_name.') have some records already present. Please try again after sometime';
        }
        return $this->customerCount;
    }

    public function isExceedOdishaUserQuota($request){
        $totalCount = $this->customerCount;
        $customer_details = $request->customer_details;
        $stateId = $this->stateId;
        if(!empty($request->customer_details) && is_array($request->customer_details) && count($request->customer_details) > 0){
            $countArray = array_filter($customer_details, function ($value) use ($stateId){
                $value = (object) $value;
                return $value->state_id == $stateId;
            });

            $totalCount += count($countArray);
        }

        if($totalCount > 3){
            $this->errors = 'The tickets reserved for Odisha have now reached full capacity. We appreciate your interest and encourage you to explore other available booking options.';
        }
        return $totalCount;
    }

}
