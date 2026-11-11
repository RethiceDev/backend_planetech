<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entretien extends Model
{
    protected $table = 'entretiens';

    protected $fillable = [
        'nom',
        'prenoms',
        'poste',
        'telephone'
    ];
}
