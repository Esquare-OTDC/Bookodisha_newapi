<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->payload as $load) {
            $response = $this->SendSms($load['module'],  $load['tempID'], $load['content'], $load['mobile_no']);
            Log::info('SMS Sent', [
                'mobile' => $load['mobile_no'],
                'response' => $response->body()
            ]);
        }
    }

    private function SendSms($module,  $tempID, $content, $mobile)
    {
        $postdata =
            [
                'action' => config('MsgClient.' . $module . 'action'),
                // 'source' => config('MsgClient.' . $module . '.source'),
                'department_id' => config('MsgClient.' . $module . '.department_id'),
                'phonenumber' => $mobile,
                'template_id' => $tempID,
                'sms_content' => $content,
            ];
        return Http::withoutVerifying()->asMultipart()->post(config('MsgClient.ClientURL'), $postdata);
    }
}
