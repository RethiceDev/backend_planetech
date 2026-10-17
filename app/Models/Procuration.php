<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procuration extends Model
{
    protected $fillable = [
        'reference', 'nom_donnateur', 'role_donnateur', 'nom_procurant',
        'cip_procurant', 'expiration_cip', 'role_procurant', 'email_procurant',
        'date_debut', 'date_fin'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($procuration) {
            if (empty($procuration->reference)) {
                $procuration->reference = 'PROC-' . date('Ym') . '-' . str_pad((static::max('id') + 1), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProcurationAction::class);
    }
}
