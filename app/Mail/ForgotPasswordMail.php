<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $Message;
    public $Subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($Message, $Subject)
    {
        $this->Message = $Message;
        $this->Subject = $Subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return  $this->subject($this->Subject)->html($this->Message);
    }
}
