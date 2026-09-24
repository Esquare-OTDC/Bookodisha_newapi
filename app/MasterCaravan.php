<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterCaravan extends Model
{
    protected $table = 'master_caravans';

    const SMALL_CARAVAN = 'small_caravan';
    const MEDIUM_CARAVAN = 'medium_caravan';
    const LARGE_CARAVAN = 'large_caravan';

    protected $fillable = [
        'vendor_id', 'title', 'slug', 'content', 'feature_image', 'banner_image', 'city', 'address', 'map_lat', 'map_lng', 'map_zoom', 'is_featured', 'gallery', 'video', 'faqs', 'property', 'property_slug', 'quantity',
        'status', 'create_user', 'update_user', 'deleted_at', 'caravan_type','price_per_day','contact_email', 'contact_number', 'additional_email', 'additional_phone', 'review_score', 'review_count', 'service_fee', 'guide_price_per_day', 'gst_applicable', 'terms_conditions', 'show_price', 'gst_number', 'gst_legal_name', 'paytm_mid', 'hdfc_mid','car_book_type'
    ];

    const CARAVAN_TYPE = [
        self::SMALL_CARAVAN => 'Small Caravan',
        self::MEDIUM_CARAVAN => 'Medium Caravan',
        self::LARGE_CARAVAN => 'Large Caravan',
    ];

    public static function getCaravanTypeOptions()
    {
        return self::CARAVAN_TYPE;
    }

    /**
     * Caravan Bookings relationship
     */

    public function bookings(){
        return $this->hasMany(CaravanBooking::class, 'caravn_id')->where('status', '!=', 0);
    }

    /**
     * Get the blocked caravans for the caravan.
     */
    public function blockedCaravans()
    {
        return $this->hasMany(BlockedCaravan::class, 'caravan_id');
    }

}
