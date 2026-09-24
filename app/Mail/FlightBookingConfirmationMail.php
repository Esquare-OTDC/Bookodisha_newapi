<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PDF;

class FlightBookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $Subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $Subject)
    {
        $this->data = $data;
        $this->Subject = $Subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        $directory = public_path('tickets');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $file_name = 'tickets/'. $data['orderMaster']->invoice_id .'_ticket.pdf';
        PDF::loadView('aero-travels.emails.booking-confirmation', $data)->save(public_path($file_name));
        return  $this->subject($this->Subject)->view('aero-travels.emails.booking-confirm-content')->with($data)->attach(
            public_path(
                $file_name
            ),
            [
                'as' => $data['orderMaster']->invoice_id . '_ticket.pdf',
                'mime' => 'application/pdf',
            ]
        );
    }
}
