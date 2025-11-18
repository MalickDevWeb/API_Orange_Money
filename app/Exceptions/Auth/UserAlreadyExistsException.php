<?php

namespace App\Exceptions\Auth;

use Exception;

class UserAlreadyExistsException extends Exception
{
    protected $message = 'Un utilisateur avec ce numéro de téléphone existe déjà';

    public function __construct(string $telephone = null)
    {
        if ($telephone) {
            $this->message = "Un utilisateur avec le numéro {$telephone} existe déjà";
        }
        parent::__construct($this->message, 409);
    }
}
