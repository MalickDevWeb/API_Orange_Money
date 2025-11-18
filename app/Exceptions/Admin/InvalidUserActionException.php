<?php

namespace App\Exceptions\Admin;

use Exception;

class InvalidUserActionException extends Exception
{
    protected $message = 'Action utilisateur invalide';

    public function __construct(string $action = null, string $reason = null)
    {
        if ($action && $reason) {
            $this->message = "Action '{$action}' invalide : {$reason}";
        } elseif ($action) {
            $this->message = "Action '{$action}' non reconnue";
        }
        parent::__construct($this->message, 400);
    }
}
