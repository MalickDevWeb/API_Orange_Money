<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin' => $this->whenLoaded('admin', function () {
                return [
                    'id' => $this->admin->id,
                    'nom' => $this->admin->nom,
                    'prenom' => $this->admin->prenom,
                ];
            }),
            'action_type' => $this->action_type,
            'target_user' => $this->whenLoaded('targetUser', function () {
                return [
                    'id' => $this->targetUser->id,
                    'nom' => $this->targetUser->nom,
                    'prenom' => $this->targetUser->prenom,
                    'telephone' => $this->targetUser->telephone,
                ];
            }),
            'details' => $this->details,
            'created_at' => $this->created_at,
        ];
    }
}
