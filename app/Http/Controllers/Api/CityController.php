<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'region']);

        return response()->json(['cities' => $cities]);
    }
}
