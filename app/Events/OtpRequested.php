<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpRequested
{
    use Dispatchable, SerializesModels;

    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }
}
