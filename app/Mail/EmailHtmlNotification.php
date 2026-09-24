<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class EmailHtmlNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $Message;
    private $Subject;

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
