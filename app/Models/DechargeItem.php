<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DechargeItem extends Model
{
    protected $fillable = [
        'decharge_id',
        'designation',
        'quantite',
        'prix_unitaire',
        'prix_global',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'decimal:2',
            'prix_global' => 'decimal:2',
        ];
    }

    public function decharge(): BelongsTo
    {
        return $this->belongsTo(Decharge::class);
    }
}
