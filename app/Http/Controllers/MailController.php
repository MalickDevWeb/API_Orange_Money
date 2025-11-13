<?php

namespace App\Http\Controllers;

use App\Services\BrevoService;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function send(BrevoService $brevo)
    {
        $response = $brevo->sendMail(
            'pppttt1732@gmail.com',
            'Test Laravel x Brevo',
            '<h1>Hello 👋</h1><p>Email envoyé via Brevo API.</p>'
        );

        return response()->json($response);
    }
}
