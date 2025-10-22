<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidat extends Model
{
    use HasUuids;

    protected $table = 'candidats';

    protected $fillable = [
        'personne_id',
        'numero_candidat',
        'date_naissance',
        'lieu_naissance',
        'nip',
        'type_piece',
        'numero_piece',
        'nationalite',
        'genre',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un candidat appartient à une personne
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'personne_id');
    }

    /**
     * Relation : Un candidat peut avoir plusieurs dossiers
     */
    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class, 'candidat_id');
    }
}
