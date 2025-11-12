<?php

namespace App\Observers;

use App\Models\Compte;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class CompteObserver
{
    /**
     * Handle the Compte "creating" event.
     */
    public function creating(Compte $compte): void
    {
        $qrCode = new QrCode($compte->numero_compte);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $compte->qr_code = base64_encode($result->getString());
    }
}
