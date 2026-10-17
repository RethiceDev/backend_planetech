<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Decharge extends Model
{
    protected $fillable = [
        'reference', 'nom', 'role', 'entreprise', 'numero', 'email',
        'type', 'somme', 'motif', 'description', 'signature_data',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($decharge) {
            if (empty($decharge->reference)) {
                $next = ((int) static::max('id')) + 1;
                $decharge->reference = 'DEC-' . date('Ym') . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(DechargeItem::class);
    }
}
