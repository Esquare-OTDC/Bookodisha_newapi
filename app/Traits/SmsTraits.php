<?php

namespace App\Traits;

use App\Jobs\SendSmsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Http;


trait SmsTraits
{

    private $sendPayload;

    public function SmsSend(array $data)
    {
        try {
            if (!empty($data['ref_code'])) {
                $template = DB::table('sms_templates')
                    ->where('ref_code', $data['ref_code'])
                    ->first();

                if (!$template) {
                    return [
                        'status' => false,
                        'message' => 'Template not found'
                    ];
                }
                $templateText = $template->source;
                $templateId   = $template->templete_id ?? null;
            } else {
                $templateText = $data['template_text'] ?? '';
                $templateId   = $data['template_id'] ?? null;
            }
            $variables = $data['variables'] ?? [];
            $mobiles   = $data['mobiles'] ?? [];

            if (empty($templateText)) {
                return [
                    'status' => false,
                    'message' => 'Template text is required'
                ];
            }
            if (empty($mobiles)) {
                return [
                    'status' => false,
                    'message' => 'Mobile number is required'
                ];
            }
            // Step 2: Replace variables
            $sms_text = str_replace(
                array_keys($variables),
                array_values($variables),
                $templateText
            );
            // Step 3: Limit recipients
            if (strpos($sms_text, '  ') !== false) {
                Log::warning('Double space detected in SMS', ['text' => $sms_text]);
            }

            $recipients = array_slice($mobiles, 0, 3);
            if (empty($recipients)) {
                return [
                    'status' => false,
                    'message' => 'No recipients found'
                ];
            }
            $payload = [];
            foreach ($recipients as $mobile) {
                $payload[] = [
                    'module'    => 'sendsms',
                    'tempID'    => $templateId,
                    'content'   => $sms_text,
                    'mobile_no' => trim($mobile),
                    'action'    => 'sendOTPSMS'
                ];
            }
            // Step 5: Dispatch Job
            $this->Sendtest($payload);
            // SendSmsJob::dispatch($payload);
            Log::info('SMS FINAL TEXT', [
                'text' => $sms_text,
                'mobiles' => $mobiles
            ]);
            return [
                'status' => true,
                'message' => 'SMS job dispatched successfully'
            ];
        } catch (Exception $e) {
            Log::error('SMS Error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function Sendtest($sendPayload)
    {
        foreach ($sendPayload as $load) {
            try {
                $response = $this->SendSmsApi(
                    $load['module'],
                    $load['tempID'],
                    $load['content'],
                    $load['mobile_no']
                );

                Log::info('SMS API RESPONSE', [
                    'mobile' => $load['mobile_no'],
                    // 'status' => $response->status(),
                    'body' => $response
                ]);
            } catch (\Exception $e) {
                Log::error('SMS ERROR', [
                    'mobile' => $load['mobile_no'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function SendSmsApi($module, $tempID, $content, $mobile)
    {

    $data = "action=sendOTPSMS&department_id=D022001&template_id=" . urlencode($tempID) . "&sms_content=" . urlencode($content) . "&phonenumber=" . urlencode($mobile);
    $ch = curl_init(config('MsgClient.ClientURL'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
    }
}
