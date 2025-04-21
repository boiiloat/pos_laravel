<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;


class RoleController extends Controller
{
    public function index()
    {
        return Role::all();
    }
    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);

        $role = Role::create([
            'name' => $request->name,
            'create_date' => now(),
            'create_by' => $request->create_by,
        ]);

        return response()->json($role, 201);
    }

    public function show($id)
    {
        return Role::findOrFail($id);
    }
}
