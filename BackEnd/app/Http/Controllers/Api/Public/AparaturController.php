<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Aparatur;
use App\Http\Controllers\Controller;
use App\Http\Resources\AparaturResource;
use Illuminate\Http\Request;

class AparaturController extends Controller
{
    /**
     * index
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        $query = Aparatur::query();

        // Filter berdasarkan nama jika parameter pencarian diberikan
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // Ambil data aparatur
        $aparaturs = $query->oldest()->get();

        //return with Api Resource
        return new AparaturResource(true, 'List Data Aparaturs', $aparaturs);
    }
}
