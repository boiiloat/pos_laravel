<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
    
        $user = User::where('username', $request->username)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }
    
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
    
// In AuthController.php
public function createUser(Request $request)
{
    // Check if the authenticated user is allowed to create users
    if ($request->user()->role_id != 1) { // Changed from == to !=
        return response()->json(['message' => 'Unauthorized - Only admin can create users'], 403);
    }
    
    $request->validate([
        'username' => 'required|unique:users',
        'password' => 'required|min:6',
        'role_id' => 'required|integer|exists:roles,id'
    ]);
    
    $user = User::create([
        'username' => $request->username,
        'password' => Hash::make($request->password),
        'role_id' => $request->role_id
    ]);
    
    return response()->json([
        'message' => 'User created successfully',
        'user' => $user
    ], 201);
}
}