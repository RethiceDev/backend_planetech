<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DechargeTranche extends Model
{
    protected $fillable = [
        'decharge_id',
        'nature',
        'libelle',
        'date_prevue',
        'montant',
    ];

    protected $casts = [
        'date_prevue' => 'date',
    ];

    public function decharge()
    {
        return $this->belongsTo(Decharge::class);
    }
}
