<?php

namespace App\Exceptions\Auth;

use Exception;

class InvalidOtpException extends Exception
{
    protected $message = 'Code OTP invalide ou expiré';

    public function __construct(string $message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        parent::__construct($this->message, 401);
    }
}
