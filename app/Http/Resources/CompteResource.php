<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'titulaire' => $this->titulaire,
            'nom_compte' => $this->nom_compte,
            'statut' => $this->statut,
            'type_compte' => $this->type_compte,
            'devise' => $this->devise,
            'solde' => $this->solde,
            'code_marchand' => $this->code_marchand,
            'qr_code' => $this->qr_code,
            'utilisateur_id' => $this->utilisateur_id,
            'client_id' => $this->client_id,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
