<?php

namespace App\Exceptions\Admin;

use Exception;

class BalanceRequestNotFoundException extends Exception
{
    protected $message = 'Demande de solde non trouvée';

    public function __construct(string $telephone = null)
    {
        if ($telephone) {
            $this->message = "Aucune demande de solde en attente trouvée pour le numéro {$telephone}";
        }
        parent::__construct($this->message, 404);
    }
}
