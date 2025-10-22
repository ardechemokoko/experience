<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dossier extends Model
{
    use HasUuids;

    protected $table = 'dossiers';

    protected $fillable = [
        'candidat_id',
        'auto_ecole_id',
        'formation_id',
        'statut',
        'date_creation',
        'date_modification',
        'commentaires',
    ];

    protected function casts(): array
    {
        return [
            'date_creation' => 'date',
            'date_modification' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un dossier appartient à un candidat
     */
    public function candidat(): BelongsTo
    {
        return $this->belongsTo(Candidat::class, 'candidat_id');
    }

    /**
     * Relation : Un dossier appartient à une auto-école
     */
    public function autoEcole(): BelongsTo
    {
        return $this->belongsTo(AutoEcole::class, 'auto_ecole_id');
    }

    /**
     * Relation : Un dossier concerne une formation
     */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(FormationAutoEcole::class, 'formation_id');
    }

    /**
     * Relation : Un dossier contient plusieurs documents
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'dossier_id');
    }
}
