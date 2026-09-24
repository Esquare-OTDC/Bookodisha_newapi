<?php

namespace App\HallModels;

use App\City;
use App\User;
use App\VendorProfile;
use Illuminate\Database\Eloquent\Model;

class HallProperty extends Model
{
    protected $table = 'm_property';

    protected $fillable = [
        'property_name',
        'hfacilities_id',
        'vender_id',
        'slug',
        'content',
        'youtube_video',
        'banner_image',
        'gallery',
        'terms_condition',
        'district_id',
        'place',
        'address',
        'contact_email',
        'manager_name',
        'manager_contact',
        'reception_contact',
        'additional_email',
        'additional_contact',
        'hotel_address',
        'publish_status',
        'feature_image',
        'gst_applicable',
        'gst_number',
        'hdfc_mid',
        'paytm_mid',
        'company_name',
        'status',
        'gst_legal_name',
        'created_by',
        'updated_by',
        'is_deleted'
    ];

    public function halls()
    {
        return $this->hasMany(Hall::class, 'property_id', 'id');
    }

    public function cityName(){
        return City::where('id', $this->district_id)->first()->name;
    }

    public function vendor($site = ''){
        $user =  User::select('company as vendor_name','email as vendor_email','photo as vendor_image','created_at as member_since')->where('id', $this->vender_id)->first();
        $vendor_profile = VendorProfile::where('vendor_id', $this->vender_id)->first();
        $profile = '';
        if (!empty($vendor_profile)) {
            $profile = $vendor_profile->profile_type;
            if ($vendor_profile->profile_type == 'own') {
                $vendor_slug = !empty($vendor_profile->profile_url) ? $vendor_profile->profile_url : 'javascript:void(0)';
            } else {
                $vendor_slug = $vendor_profile->slug;
            }
        } else {
            $vendor_slug = '';
        }
        return [
            'vendor_name'=>$user->vendor_name,
            'vendor_email'=>$user->vendor_email,
            'vendor_image'=>$site.$user->vendor_image,
            'member_since'=>date("M Y", strtotime($user->member_since)),
            'vendor_slug'=>$vendor_slug,
            'vendor_profile'=>$profile
        ];
    }
}
