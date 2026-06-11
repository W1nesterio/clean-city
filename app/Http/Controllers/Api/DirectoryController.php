<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function organizations(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'worker'], true)) {
            return response()->json(['message' => 'Нет доступа'], 403);
        }

        return response()->json([
            'organizations' => Organization::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function workers(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Доступ только для администратора'], 403);
        }

        return response()->json([
            'workers' => User::query()
                ->where('role', 'worker')
                ->with('organization')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
