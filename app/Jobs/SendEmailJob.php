<?php

namespace App\Jobs;

use App\Mail\EmailHtmlNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;          // Retry up to 3 times on failure
    public $timeout = 60;         // Kill job if it runs over 60 seconds
    public $backoff = 10;         // Wait 10 seconds between retries

    protected $recipientEmail;
    protected $body;
    protected $subject;
    protected $customData;

    public function __construct($to, $body, $subject)
    {
        $this->recipientEmail = $to;
        $this->body  = $body;
        $this->subject = $subject;
    }

    public function handle()
    {
        Mail::to($this->recipientEmail)
            ->send(new EmailHtmlNotification(
                $this->body,
                $this->subject
            ));
    }

    // Called when all retries are exhausted
    public function failed($exception)
    {
        Log::error('Bulk email job failed', [
            'email'     => $this->recipientEmail,
            'exception' => $exception->getMessage(),
        ]);
    }
}
