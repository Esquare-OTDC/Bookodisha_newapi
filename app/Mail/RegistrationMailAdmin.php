<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMailAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $Message;
    public $Subject;
    public $FrMail;
    public $FrName;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($Message, $Subject, $FrMail, $FrName)
    {
        $this->Message = $Message;
        $this->Subject = $Subject;
        $this->FrMail = $FrMail;
        $this->FrName = $FrName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return  $this->from($this->FrMail, $this->FrName)->subject($this->Subject)->html($this->Message);
    }
}
