<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurationAction extends Model
{
    protected $fillable = ['procuration_id', 'libelle_action'];

    public function procuration(): BelongsTo
    {
        return $this->belongsTo(Procuration::class);
    }
}
