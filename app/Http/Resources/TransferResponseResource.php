<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResponseResource extends JsonResource
{
    public string $montant_transferer;
    public string $solde_actuelle;
    public string $type;
    public string $numero_transfer;
    public string $numero_destinataire;

    public function __construct($resource)
    {
        parent::__construct($resource);

        if (is_array($resource)) {
            $this->montant_transferer = $resource['montant_transferer'] ?? '';
            $this->solde_actuelle = $resource['solde_actuelle'] ?? '';
            $this->type = $resource['type'] ?? '';
            $this->numero_transfer = $resource['numero_transfer'] ?? '';
            $this->numero_destinataire = $resource['numero_destinataire'] ?? '';
        }
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'montant_transferer' => $this->montant_transferer,
            'solde_actuelle' => $this->solde_actuelle,
            'type' => $this->type,
            'numero_transfer' => $this->numero_transfer,
            'numero_destinataire' => $this->numero_destinataire,
        ];
    }
}
