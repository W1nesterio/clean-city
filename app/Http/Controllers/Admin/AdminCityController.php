<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class AdminCityController extends Controller
{
    use AdminAccess;

    public function index()
    {
        $this->requireSuperAdmin();
        $cities = City::withCount('organizations')->orderBy('name')->get();
        return view('admin.cities.index', compact('cities'));
    }

    public function store(Request $request)
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
        ]);
        City::create($data + ['active' => true]);
        return redirect()->route('admin.cities.index')->with('success', 'Город добавлен');
    }

    public function destroy(City $city)
    {
        $this->requireSuperAdmin();
        $city->delete();
        return redirect()->route('admin.cities.index')->with('success', 'Город удалён');
    }
}
