<?php
namespace App\Traits;

use App\HallModels\HallMasterInventory;
use App\OrderMaster;
use App\PaymentHistory;
use App\PropertyAccount;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Traits\HdfcTraits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

trait ConferenceTraits{

    use HdfcTraits;

    private $conferenceInvoice;
    private $orders;
    private $existingInventory = [];
    private $hall_id;
    private $property_id;

    private $request;

    /**
     * Set the current HTTP request instance.
     *
     * @param Request $request The incoming HTTP request.
     * @return void
     */
    public function setRequest(Request $request){
        $this->request = $request;
    }

    /**
     * Get the current HTTP request instance.
     *
     * @return Request|null The stored HTTP request, or null if none set.
     */
    public function getRequest(){
        return $this->request;
    }

    public function setHallIdBySlug($hall_slug){
        $hall = DB::table('m_hall')->where('slug', $hall_slug)->first();
        if($hall){
            $this->hall_id = $hall->id;
        }else{
            throw new Exception("Hall not found for the given slug: " . $hall_slug);
        }
    }

    public function setPropertyIdBySlug($property_slug){
        $property = DB::table('m_property')->where('slug', $property_slug)->first();
        if($property){
            $this->property_id = $property->id;
        }else{
            throw new Exception("Property not found for the given slug: " . $property_slug);
        }
    }
    /**
     * Set the current hall identifier.
     *
     * @param int|null $hall_id The hall id to set. Use null to unset.
     * @return void
     */
    public function setHallId($hall_id)
    {
        $this->hall_id = $hall_id;
    }

    /**
     * Get the current hall identifier.
     *
     * @return int|null The currently set hall id or null if not set.
     */
    public function getHallId()
    {
        return $this->hall_id;
    }

    /**
     * Set the current property identifier.
     *
     * @param int|null $property_id The property id to set. Use null to unset.
     * @return void
     */
    public function setPropertyId($property_id)
    {
        $this->property_id = $property_id;
    }

    /**
     * Get the current property identifier.
     *
     * @return int|null The currently set property id or null if not set.
     */
    public function getPropertyId()
    {
        return $this->property_id;
    }

    /******************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-02
    * Description: This function debugs a raw SQL query and returns the final executed query with bindings.
    * @param \Illuminate\Database\Query\Builder $query The query builder instance.
    * @return string The final executed query.
    * *****************************************************************************************/
    public function rawQueryDebug($query){

        $sql = vsprintf(
            str_replace('?', "'%s'", $query->toSql()),
            $query->getBindings()
        );
        return $sql;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-04
    * Description: This function sets the email template details.
    * @return void
    * *************************************************************************************************************************/
    public function setConferenceInvoice(){
        $this->conferenceInvoice = DB::table('email_templates')->where('ref_code', 'conferenceInvoice')->first();
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-04
    * Description: This function retrieves the email template details.
    * @return \Illuminate\Database\Eloquent\Collection The email template details.
    * *************************************************************************************************************************/
    public function getConferenceInvoice(){
        return $this->conferenceInvoice;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-04
    * Description: This function sets the orders.
    * @param \Illuminate\Database\Eloquent\Collection $orders The orders.
    * @return void
    * *************************************************************************************************************************/
    protected function setOrders($orders){
        $this->orders = $orders;
    }
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-04
    * Description: This function retrieves the orders.
    * @return \Illuminate\Database\Eloquent\Collection The orders.
    * *************************************************************************************************************************/
    protected function getOrders(){
        return $this->orders;
    }

    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-04
    * Description: This function get the existing inventory details which set in setExistingInventory function
    * @param date $date
    * @return array of dates in which inventory exists
    ************************************************************************************************************************* */
    protected function isSlotAvailable(string $date, string $requestedSlot = 'full_day'){
        $date = date("Y-m-d", strtotime($date));
        $isSlotAvailable = HallMasterInventory::where('hall_id', $this->getHallId())->where('inventory_date', $date);
        $query = clone $isSlotAvailable;
        if($isSlotAvailable->exists()){
            if ($requestedSlot === 'full_day') {
                // A full day request is blocked by ANY existing booking on that day
                $query->where('first_half_available', '0')->where('second_half_available', '0');
            }elseif ($requestedSlot === 'first_half') {
                // First half is blocked by an existing full day or another first half
                $query->where('first_half_available', '0');
            } elseif ($requestedSlot === 'second_half') {
                // Second half is blocked by an existing full day or another second half
                $query->where('second_half_available', '0');
            }else {
                throw new \InvalidArgumentException("Invalid slot type provided.");
            }
            // If a record exists, it means the slot is available
            return $query->exists();
        }else{
            return false; // No inventory exists for the given date
        }
    }
    /* ************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2026-06-04
    * Description: This function add or insert records in inventory for next 90 days and checks existing inventory from current dates
    * @param Collection $conference
    * @return void
    ************************************************************************************************************************* */
    protected function addInventoryNext90Days($vendor_id, HallMasterInventory $inventoryModel){
        $count = 0;
        $inventory = [];
        $today = date("Y-m-d");
        $inventoryModel->setHallId($this->getHallId())->setExistingInventory();
        $inventoryModel->where('vendor_id', $vendor_id);

        for($i = 0; $i<= 90; $i++){
            $date = date("Y-m-d", strtotime($today . ' + ' . $i . ' days'));
            if(!$inventoryModel->isInventoryExist($date)){
                $inventory[$count] = [
                    'vendor_id' => $vendor_id,
                    'hall_id' => $this->getHallId(),
                    'inventory_date' => $date,
                    'inventory_slot_type' => null,
                    'first_half_available' => '0',
                    'second_half_available' => '0',
                ];
                $count++;
            }
        }
        if(count($inventory) > 0){
            HallMasterInventory::insert($inventory);
        }
        return count($inventory) > 0 ? true : false;
    }

    public function encryptData(string $data){
        return Crypt::encryptString($data);
    }

    public function decryptData(string $encryptedData){
        return Crypt::decryptString($encryptedData);
    }

    public function updateInventory($hallId, $propertyId, $date){
        try{
            DB::transaction(function () use ($hallId, $propertyId, $date){

            });
        }catch(Exception $e){
            throw new Exception("Error updating inventory: " . $e->getMessage());
        }
    }

    public function calculateDays($checkIn, $checkOut){
        $days = 0;
        $days = 0;
        if ($checkIn != '' && $checkOut != '') {
            if ($checkIn == $checkOut) {
                $days = 1;
            } else {
                $difference = strtotime($checkOut) - strtotime($checkIn);
                $days = round($difference / (60 * 60 * 24));
                $days += 1; // Include the checkout day in the count
            }
        }
        return $days;
    }

    public function getSlotType($requestSlot = 'full_day'){
        $slot = '';
        switch($requestSlot){
            case 'full_day':
                $slot = 'FULL_DAY';
                break;

            case 'first_half':
                $slot = 'HALF_DAY';
                break;

            case 'second_half':
                $slot = 'HALF_DAY';
                break;
            default:
                $slot = '';
                break;
        }
        return $slot;
    }

    public function getTime($requestSlot = 'full_day'){
        $slots = array(
            'start_time'=>null,
            'end_time'=>null
        );
        $requestSlot = strtolower($requestSlot);
        $time = [
            'full_day' => [
                'start_time' => '09:00 am',
                'end_time'   => '06:00 pm',
            ],
            'first_half' => [
                'start_time' => '09:00 am',
                'end_time'   => '01:00 pm',
            ],
            'second_half' => [
                'start_time' => '02:00 pm',
                'end_time'   => '06:00 pm',
            ],
        ];
        return $time[$requestSlot] ?? $slots;
    }

    public function propertyQuery(){
        $query = DB::table('m_property as P')
                    ->select('P.id', 'HI.hall_id')
                    ->join('m_hall as MH','MH.property_id','=','P.id')
                    ->join('t_hall_inventory as HI','HI.hall_id','=','MH.id')
                    ->join('m_slot as MS','MS.hall_id','=','MH.id')
                    ->where('MH.publish_status', 'PUBLISH')
                    ->where('P.publish_status', 'PUBLISH')
                    ->where('P.status', '1')
                    ->where('P.is_deleted', 0)
                    ->where('MH.is_deleted',0);
        return clone $query;
    }

    public function getCategoryLowestCost(){
        $request = $this->getRequest();
        $data = DB::table('m_property as P')
                    ->select(
                        'P.id as property_id',
                        'MH.id as hall_id',
                        'MH.hall_name',
                        'MH.hcategory_id',
                        'MC.hcategory_name',
                        'MC.slug',
                        DB::raw('MIN(MS.price) as min_price')
                    )
                    ->join('m_hall as MH', 'MH.property_id', '=', 'P.id')
                    ->join('m_hcategory as MC', 'MC.id', '=', 'MH.hcategory_id')
                    ->join('m_slot as MS', 'MS.hall_id', '=', 'MH.id')
                    ->where('MS.slot','FULL_DAY')
                    ->where('MH.status','1')
                    ->where('MH.publish_status','PUBLISH')
                    ->where('MH.is_deleted','0');



        $data = $data->groupBy(
                        'P.id',
                        'MH.id',
                        'MH.hall_name',
                        'MH.hcategory_id',
                        'MC.hcategory_name',
                        'MC.slug'
                    )->get();
        $lowest_price = $result = array();
        foreach($data as $p){
            $lowest_price[$p->property_id][] = array(
                'name'=>$p->hall_name,
                'category'=>$p->hcategory_name,
                'price'=>$p->min_price,
                'category_slug'=>$p->slug
            );
        }
        foreach ($lowest_price as $propertyId => $halls) {
            foreach ($halls as $hall) {
                $category = $hall['category_slug'];
                if (
                    !isset($result[$propertyId][$category]) ||
                    $hall['price'] < $result[$propertyId][$category]['price']
                ) {
                    $result[$propertyId][$category] = $hall;
                }
            }
        }

        $result = collect($result)->map(function ($item) {
            return collect($item)->values()->all();
        })->toArray();

        return $result;
    }

    public function cleanString($string) {
        $str = trim(preg_replace('/[^A-Za-z0-9\-\_\,]/', ' ', $string));// Removes special chars.
        return $str;
    }
}
