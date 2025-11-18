<?php

namespace App\Exceptions\Auth;

use Exception;

class AccountPendingException extends Exception
{
    protected $message = 'Votre compte est en attente d\'approbation par un administrateur';

    public function __construct(string $message = null)
    {
        if ($message) {
            $this->message = $message;
        }
        parent::__construct($this->message, 403);
    }
}
