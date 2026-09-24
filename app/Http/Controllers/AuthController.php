<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator,
    Redirect,
    Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Session;
Use App\User;
use App\EmailTemplate;
Use App\TmpUser;

class AuthController extends Controller {

    public $site;
    public $frontendUrl;

    public function __construct() {
        $this->site = (env('APP_ENV') == 'local') ? env('TEST_URL') : env('APP_URL') . '/';
        $this->frontendUrl = (env('APP_ENV') == 'local') ? env('FRONTEND_TEST_URL') : env('FRONTEND_URL');
    }

    public function login() {
        if (Auth::check()) {
            return redirect()->intended('dashboard');
//            if (Auth::user()->access_type == 'superadmin') {
//                return redirect()->intended('dash-board');
//            } else {
//                return redirect()->intended('dashboard');
//            }
        }
        return view('auth.login');
    }

    public function postLogin(Request $request) {
        request()->validate(['email' => 'required|email','password' => 'required']);
        // echo "<pre>";print_r($request->all());exit;
        $credentials = $request->only('email', 'password');
        $remember_me = $request->has('remember_me') ? true : false;
        $captcha = $_POST['g-recaptcha-response'];
        $secret = env('RECAPTCHA_SECRET_KEY');
        $ip = $_SERVER['REMOTE_ADDR'];
        $action = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha&remoteip=$ip");
        $result = json_decode($action,1);
        if ($result['success'] == 1) {
            if (Auth::attempt($credentials, $remember_me)) {
                if (Auth::user()->status == 0 ) {
                    Session::flash('success', 'Your account is deactivated. Please contact admin to activate your account.');
                    Auth::logout();
                    return Redirect('login');
                } elseif (Auth::user()->is_restricted_main_portal == 1 ) {
                    Session::flash('success', 'You are not authorised to access.');
                    Auth::logout();
                    return Redirect('login');
                } elseif (Auth::user()->access_type == 'agent' || Auth::user()->access_type == 'customer') {
                    Session::flash('success', 'Un-authorised access.');
                    Auth::logout();
                    return Redirect('login');
                }
                $user = $request->user();
                $user->wrong_attempts = 0;
                $user->save();
                return redirect()->intended('dashboard');
                // if (Auth::user()->access_type == 'superadmin') {
                //     return redirect()->intended('dash-board');
                // } else {
                //     return redirect()->intended('dashboard');
                // }
            } else {
                $Userdata = User::where('email', $request->email)->first();
                if (!empty($Userdata)) {
                    if ($Userdata->status == 1) {
                        $Userdata->wrong_attempts += 1;
                        if ($Userdata->wrong_attempts >= 7 ) {
                            $Userdata->status = 0;
                        }
                        $Userdata->save();
                        Session::flash('success', 'These credentials do not match our records.');
                    } else {
                        Session::flash('success', 'Your account is deactivated. Please contact admin to activate your account.');
                    }
                } else {
                    $TmpUser = TmpUser::where('email', $request->email)->first();
                    if (!empty($TmpUser)) {
                        if ($TmpUser->attempt >= 3) {
                            Session::flash('success', 'Your account is deactivated. Please contact admin to activate your account.');
                        } else {
                            $TmpUser->attempt += 1;
                            $TmpUser->save();
                            Session::flash('success', 'These credentials do not match our records.');
                        }
                    } else {
                        DB::table('temp_users')->insert([
                            'email' => $request->email,
                            'attempt' => 1,
                            'created_at' => date("Y-m-d H:i:s")
                        ]);
                        Session::flash('success', 'These credentials do not match our records.');
                    }
                }
                return Redirect::to('login');
            }
        } else {
            Session::flash('success', 'Invalid Captcha. Please try again.');
            return Redirect::to('login');
        }

//        return $this->sendFailedLoginResponse($request);
    }

    protected function sendFailedLoginResponse(Request $request) {
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')]
        ]);
    }

    public function logout() {
        Session::flush();
        Auth::logout();
        return Redirect('login');
    }

    public function forgotPassword(Request $request) {
        if ($request->request_type == 'retrieve_password') {
            $validate = Validator::make($request->all(), [
                'emailId' => 'required|email'
            ]);
            if ($validate->fails()) {
                $responce['status'] = 0;
                $errors = $validate->errors();
                if ($errors->has('emailId')) {
                    $responce['message'] = $errors->first('emailId');
                }
            } else {
                $captcha = $request->captchaResponse;
                $secret = env('RECAPTCHA_SECRET_KEY');
                $ip = $_SERVER['REMOTE_ADDR'];
                $action = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha&remoteip=$ip");
                $result = json_decode($action,1);
                if ($result['success'] == 1) {
                    $UserDetails = User::where('email', $request->emailId)->first();
                    if(!empty($UserDetails)) {
                        $token = Str::random(155);
                        $verify_link = $this->site .'change-password/' . $token . '/' . $request->emailId;

                        // Send forgot password mail to user
                        $ForgotPasswordTemplete = EmailTemplate::where('ref_code', 'ForgotPassword')->first();
                        $Message = str_replace(array("~firstname~", "~lastname~", "~website_url~", "~verify_link~", "~site_url~"), array($UserDetails->first_name, $UserDetails->last_name, $this->frontendUrl, $verify_link, $this->site), $ForgotPasswordTemplete->source);
                        $Subject = $ForgotPasswordTemplete->subject;
                        Mail::to($request->emailId)->send(new \App\Mail\ForgotPasswordMail($Message, $Subject));

                        $current_date = date('Y-m-d H:i:s');
                        $link_expire = date('Y-m-d H:i:s', strtotime('+2 hour', strtotime($current_date)));
                        DB::insert("INSERT INTO `password_resets`(`email`, `token`, `expire_time`, `created_at`) VALUES ('".$request->emailId."', '".$token."', '".$link_expire."', '".$current_date."')");

                        // Save email logs
                        DB::insert("INSERT INTO `email_logs`(`user_id`, `email`, `subject`, `email_content`, `created_at`) VALUES ('".$UserDetails->id."', '".$request->emailId."', '".$Subject."', '".$Message."', '".$current_date."')");

                        $responce['status'] = 1;
                        $responce['message'] = 'A passsword reset mail is send to your mail id, Please go through this mail for change password';
                    } else {
                        Session::flash('success', 'Your provided email id not found in our record');
                        $responce['status'] = 0;
                        $responce['message'] = 'A passsword reset mail is send to your mail id, Please go through this mail for change password';
                    }
                }
                else {
                    $responce['status'] = 0;
                    $responce['message'] = 'Invalid Captcha. Please try again.';
                }
            }
        }
        echo json_encode($responce);
        exit;
    }

    public function changePassword($token, $email) {
        $isValid = false;
        $message = '';

        $UserDetails = User::where('email', $email)->first();
        if(!empty($UserDetails)) {

            $PasswordLinkData = DB::table('password_resets')->where(['token' => $token, 'email' => $email])->first();
            if(!empty($PasswordLinkData)) {

                if(strtotime($PasswordLinkData->expire_time) > strtotime(date('Y-m-d H:i:s'))) {
                    $isValid = true;
                } else {
                    $message = 'Password change token is expired.';
                }
            } else {
                $message = 'Invalid token in url parameter.';
            }
        } else {
            $message = 'Invalid email id in url parameter.';
        }

        return view('auth.change-password', compact('message', 'isValid', 'token', 'email'));
    }

    public function postPasswordChange(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password_reset_token' => 'required|string',
            'password' => 'confirmed|required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
            'password_confirmation' => 'required|min:8'
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            return Redirect::to('change-password/' . $request->password_reset_token . '/' . $request->email)->withErrors($validate)->withInput();
        } else {
            $CurrTime = date("Y-m-d H:i:s");
            $TokenVerify = DB::table('password_resets')->where(['token' => $request->password_reset_token, 'email' => $request->email])
                    ->where('expire_time', '>', $CurrTime)
                    ->first();
            if(!empty($TokenVerify)) {
                if($TokenVerify->is_validated != 1) {
                    $UserDetails = User::where('email', $request->email)->first();
                    if(!empty($UserDetails)) {
                        $UserDetails->password = bcrypt($request->password);
                        $UserDetails->save();

                        DB::table('password_resets')->where('id', $TokenVerify->id)->update(['is_validated' => 1, 'execute_time' => $CurrTime]);

                        Session::flash('success', 'Password changed successfully. Please go for login');
                        return Redirect::to('change-password/' . $request->password_reset_token . '/' . $request->email);
                    } else {
                        Session::flash('success', 'User not found with the provided email address');
                        return Redirect::to('change-password/' . $request->password_reset_token . '/' . $request->email);
                    }
                } else {
                    Session::flash('success', 'You already used this token url for change password');
                    return Redirect::to('change-password/' . $request->password_reset_token . '/' . $request->email);
                }
            } else {
                Session::flash('success', 'Token expired or invalid email');
                return Redirect::to('change-password/' . $request->password_reset_token . '/' . $request->email);
            }
        }
    }
}
