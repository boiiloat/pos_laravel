<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        return User::where('is_delete', false)
            ->whereNull('deleted_at')
            ->get();
    }

    public function store(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please login first.'
                ], 401);
            }

            if ($request->user()->role_id !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission denied. Only administrators can create new users.',
                    'hint' => 'Your role: ' . $request->user()->role->name
                ], 403);
            }

            $validated = $request->validate([
                'fullname' => 'required|string|max:255',
                'username' => 'required|string|unique:users|max:255',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $userData = [
                'fullname' => $validated['fullname'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
                'create_date' => now(),
                'create_by' => $request->user()->fullname,
                'is_delete' => false
            ];

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $userData['profile_image'] = $path;
            }

            $user = User::create($userData);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load('role'),
                'created_by' => $request->user()->fullname,
                'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::where('is_delete', false)
            ->whereNull('deleted_at')
            ->with('role')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user,
            'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null
        ]);
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $currentUser = auth()->user();

            // If you want SOFT DELETE (keep record in database)
            $user->update([
                'is_delete' => true,
                'deleted_at' => now(),
                'delete_by' => $currentUser ? $currentUser->fullname : 'System',
                'delete_date' => now()
            ]);

            // If you want HARD DELETE (permanently remove)
            // $user->forceDelete();

            // Delete associated profile image
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'deleted_by' => $currentUser ? $currentUser->fullname : 'System',
                'deleted_at' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}