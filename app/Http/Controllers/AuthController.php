<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // ✅ FIXED: Enhanced query with better logging
        $user = User::where('username', $request->username)
            ->where('is_delete', false)
            ->whereNull('deleted_at')
            ->with('role') // Load role relationship
            ->first();

        // ✅ FIXED: Better logging for debugging
        Log::info('Login attempt:', [
            'username' => $request->username,
            'user_found' => $user ? 'yes' : 'no',
            'user_id' => $user ? $user->id : null,
            'is_delete' => $user ? $user->is_delete : null,
            'deleted_at' => $user ? $user->deleted_at : null,
            'role_id' => $user ? $user->role_id : null
        ]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or has been deleted'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // ✅ FIXED: Create token with user abilities based on role
        $abilities = $this->getUserAbilities($user->role_id);
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'role_id' => $user->role_id,
                'role' => [
                    'id' => $user->role_id,
                    'name' => $user->role->name ?? null
                ],
                'profile_image' => $user->profile_image,
                'profile_image_url' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
            ]
        ]);
    }

    /**
     * Get user abilities based on role_id
     */
    private function getUserAbilities($role_id)
    {
        switch ($role_id) {
            case 1: // Admin
                return [
                    'create-users',
                    'update-users',
                    'delete-users',
                    'view-roles',
                    'create-roles',
                    'update-roles',
                    'delete-roles',
                    'create-categories',
                    'update-categories',
                    'delete-categories',
                    'create-products',
                    'update-products',
                    'delete-products'
                ];
            case 2: // Cashier
                return [
                    'update-users', // Can update own profile only
                    'view-roles',
                    'create-products',
                    'update-products'
                ];
            default:
                return [];
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}