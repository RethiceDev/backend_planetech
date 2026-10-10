<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domaine;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DomainesController extends Controller
{

    public function listes()
    {
        return response()->json(
            $domaines = Domaine::all()
        );
    }

}
