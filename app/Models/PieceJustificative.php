<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PieceJustificative extends Model
{
    use HasUuids;

    protected $table = 'pieces_justificatives';

    protected $fillable = [
        'type_document_id',
        'type_permis_id',
        'is_national',
        'obligatoire',
        'inscription_id',
    ];

    protected function casts(): array
    {
        return [
            'is_national' => 'boolean',
            'obligatoire' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Type de document (référentiel)
     */
    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(Referentiel::class, 'type_document_id');
    }

    /**
     * Relation : Type de permis (référentiel)
     */
    public function typePermis(): BelongsTo
    {
        return $this->belongsTo(Referentiel::class, 'type_permis_id');
    }

    /**
     * Relation : Inscription (référentiel)
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Referentiel::class, 'inscription_id');
    }
}
