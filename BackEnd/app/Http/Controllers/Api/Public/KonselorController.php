<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Konselor;
use App\Http\Controllers\Controller;
use App\Http\Resources\KonselorResource;

class KonselorController extends Controller
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $konselors = Konselor::oldest()->get();

        //return with Api Resource
        return new KonselorResource(true, 'List Data Konselors', $konselors);
    }
}
