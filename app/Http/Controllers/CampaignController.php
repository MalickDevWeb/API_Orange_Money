<?php

namespace App\Http\Controllers;

use App\Services\BrevoService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function create(BrevoService $brevo)
    {
        $campaignData = [
            'to' => 'pppttt1732@gmail.com',
            'subject' => 'Test campagne depuis Laravel',
            'htmlContent' => '<h1>Campagne de test</h1><p>Cette campagne a été créée via l\'API Brevo depuis Laravel !</p>'
        ];

        $result = $brevo->sendCampaignEmail($campaignData);

        return response()->json($result);
    }

    public function sendNow(BrevoService $brevo, Request $request)
    {
        $campaignId = $request->input('campaign_id');

        if (!$campaignId) {
            return response()->json(['error' => 'campaign_id requis'], 400);
        }

        $result = $brevo->sendCampaign($campaignId);

        return response()->json($result);
    }
}
