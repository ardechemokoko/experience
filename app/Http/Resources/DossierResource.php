<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DossierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidat_id' => $this->candidat_id,
            'auto_ecole_id' => $this->auto_ecole_id,
            'formation_id' => $this->formation_id,
            'statut' => $this->statut,
            'date_creation' => $this->date_creation?->format('Y-m-d'),
            'date_modification' => $this->date_modification?->format('Y-m-d'),
            'commentaires' => $this->commentaires,
            
            // Relations
            'candidat' => new CandidatResource($this->whenLoaded('candidat')),
            'auto_ecole' => new AutoEcoleResource($this->whenLoaded('autoEcole')),
            'formation' => new FormationAutoEcoleResource($this->whenLoaded('formation')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

