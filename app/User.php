<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'vendor_id', 'email', 'password', 'company', 'first_name', 'last_name', 'phone', 'photo', 'payment_merchand_id', 'email_verified_at', 'verification_token', 'role', 'access_type', 'user_role', 'user_payment', 'user_book_from', 'agent_comission', 'agent_block_date', 'admin_commission', 'country', 'state', 'city', 'pincode', 'address', 'address2', 'birth_date', 'gender', 'password_rem_quetion', 'password_rem_ans', 'services', 'privilege', 'gst_regd_no', 'gst_company_name', 'gst_company_address', 'status', 'create_account_approval', 'wrong_attempts', 'device_id', 'device_token', 'device_type', 'login_type', 'social_login_id', 'commission_taken', 'night_limit_commission', 'foreign_visitors_commission', 'is_restricted_main_portal', 'created_by', 'modified_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
