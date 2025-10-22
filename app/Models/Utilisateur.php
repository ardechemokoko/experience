<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Utilisateur extends Model
{
    use HasUuids;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un utilisateur a une personne
     */
    public function personne(): HasOne
    {
        return $this->hasOne(Personne::class, 'utilisateur_id');
    }
}
