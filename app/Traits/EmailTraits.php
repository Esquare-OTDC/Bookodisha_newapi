<?php

namespace App\Traits;

use Illuminate\Support\Facades\Mail;
use App\Mail\EmailHtmlNotification;
use Illuminate\Support\Facades\Log;

trait EmailTraits
{
    /* *************************************************************************************************************************
    * @author: Saikat Mohanty
    * @date: 2024-04-24
    * Describption: This function is responsible for sending emails. It takes an array of jobs, where each job contains the recipient's email address, the email body, and the subject. The function iterates through the jobs and sends an email to each recipient using Laravel's Mail facade. Additionally, it includes a BCC to 'saikat.mohanty@estpl.in'
    * @param array $jobs An array of email jobs, where each job is an associative array with keys 'to', 'body', and 'subject'.
    * @return void
    * *************************************************************************************************************************/
    public function sendEmail($jobs)
    {
        if(is_array($jobs)){
            foreach($jobs as $job){

                Mail::to($job['to'])->bcc('saikat.mohanty@estpl.in')
                    ->send(new EmailHtmlNotification(
                        $job['body'],
                        $job['subject']
                    ));
            }
        }

    }
}
