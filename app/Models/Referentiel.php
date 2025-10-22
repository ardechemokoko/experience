<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referentiel extends Model
{
    use HasUuids;

    protected $table = 'referentiels';

    protected $fillable = [
        'libelle',
        'code',
        'type_ref',
        'description',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Référentiels utilisés comme type de permis dans formations
     */
    public function formationsTypePermis(): HasMany
    {
        return $this->hasMany(FormationAutoEcole::class, 'type_permis_id');
    }

    /**
     * Relation : Référentiels utilisés comme session dans formations
     */
    public function formationsSession(): HasMany
    {
        return $this->hasMany(FormationAutoEcole::class, 'session_id');
    }

    /**
     * Relation : Référentiels utilisés comme type de document
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'type_document_id');
    }

    /**
     * Relation : Référentiels utilisés dans pièces justificatives (type document)
     */
    public function piecesJustificativesTypeDocument(): HasMany
    {
        return $this->hasMany(PieceJustificative::class, 'type_document_id');
    }

    /**
     * Relation : Référentiels utilisés dans pièces justificatives (type permis)
     */
    public function piecesJustificativesTypePermis(): HasMany
    {
        return $this->hasMany(PieceJustificative::class, 'type_permis_id');
    }

    /**
     * Relation : Référentiels utilisés dans pièces justificatives (inscription)
     */
    public function piecesJustificativesInscription(): HasMany
    {
        return $this->hasMany(PieceJustificative::class, 'inscription_id');
    }
}
