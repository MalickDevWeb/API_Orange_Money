<?php

namespace App\Exceptions\Admin;

use Exception;

class UserNotFoundException extends Exception
{
    protected $message = 'Utilisateur non trouvé';

    public function __construct(string $telephone = null)
    {
        if ($telephone) {
            $this->message = "Utilisateur avec le numéro {$telephone} non trouvé";
        }
        parent::__construct($this->message, 404);
    }
}
