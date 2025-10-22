<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasUuids;

    protected $table = 'documents';

    protected $fillable = [
        'dossier_id',
        'type_document_id',
        'nom_fichier',
        'chemin_fichier',
        'type_mime',
        'taille_fichier',
        'valide',
        'commentaires',
    ];

    protected function casts(): array
    {
        return [
            'valide' => 'boolean',
            'taille_fichier' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un document appartient à un dossier
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class, 'dossier_id');
    }

    /**
     * Relation : Un document a un type (référentiel)
     */
    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(Referentiel::class, 'type_document_id');
    }
}
