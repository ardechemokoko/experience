<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutoEcoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom_auto_ecole' => $this->nom_auto_ecole,
            'adresse' => $this->adresse,
            'email' => $this->email,
            'responsable_id' => $this->responsable_id,
            'contact' => $this->contact,
            'statut' => $this->statut,
            'statut_libelle' => $this->statut ? 'Active' : 'Inactive',
            
            // Relations
            'responsable' => new PersonneResource($this->whenLoaded('responsable')),
            'formations' => FormationAutoEcoleResource::collection($this->whenLoaded('formations')),
            'dossiers' => DossierResource::collection($this->whenLoaded('dossiers')),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

