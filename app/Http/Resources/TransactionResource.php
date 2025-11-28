<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type,
            'montant' => $this->montant,
            'montant_signe' => $this->montant_signe,
            'frais' => $this->frais ?? 0,
            'reference' => $this->reference,
            'statut' => $this->statut,
            'note' => $this->note,
            'date_transaction' => $this->date_transaction,
        ];
    }
}
