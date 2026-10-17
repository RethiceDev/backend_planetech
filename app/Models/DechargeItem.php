<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DechargeItem extends Model
{
    protected $fillable = ['decharge_id', 'designation', 'quantite', 'etat'];

    public function decharge(): BelongsTo
    {
        return $this->belongsTo(Decharge::class);
    }
}
