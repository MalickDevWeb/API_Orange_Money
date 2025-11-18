<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->supplier->id,
                    'nom' => $this->supplier->nom,
                    'prenom' => $this->supplier->prenom,
                    'telephone' => $this->supplier->telephone,
                    'email' => $this->supplier->email,
                ];
            }),
            'montant' => $this->montant,
            'statut' => $this->statut,
            'motif_rejet' => $this->when($this->motif_rejet, $this->motif_rejet),
            'created_at' => $this->created_at,
            'traitee_at' => $this->traitee_at,
        ];
    }
}
