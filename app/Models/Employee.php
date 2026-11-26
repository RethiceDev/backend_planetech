<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prenoms',
        'email',
        'telephone',
        'service',
        'poste',
        'date_embauche',
        'mac_address',
        'mac_address_2',
        'photo',
        'type_contrat',
    ];

    protected $casts = [
        'date_embauche' => 'date:Y-m-d',
    ];
}