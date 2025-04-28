<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct()
    {
        // Apply middleware based on permissions
        $this->middleware('can:create-users')->only('store');
        $this->middleware('can:update-users')->only('update');
        $this->middleware('can:delete-users')->only('destroy');
    }

    // Method to view users (allowed for both admins and non-admins)
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => User::where('is_delete', false)
                ->whereNull('deleted_at')
                ->get()
        ]);
    }

    // Method to create a new user (only allowed for admins)
    public function store(Request $request)
    {
        try {
            // Check if the current user is an admin (role_id = 1)
            if ($request->user()->role_id != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'You don\'t have permission to create a new user.',
                    'hint' => 'Only administrators can create new users. Your current role ID: ' . $request->user()->role_id
                ], 403); // 403 Forbidden
            }
    
            // Validation of the request data
            $validated = $request->validate([
                'fullname' => 'required|string|max:255',
                'username' => 'required|string|unique:users|max:255',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);
    
            // Prepare user data for creation
            $userData = [
                'fullname' => $validated['fullname'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
                'create_date' => now(),
                'create_by' => $request->user()->fullname,
                'is_delete' => false
            ];
    
            // Handle file upload if present
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $userData['profile_image'] = $path;
            }
    
            // Create the new user
            $user = User::create($userData);
    
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'created_by' => $request->user()->fullname,
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Method to view a specific user (allowed for both admins and non-admins)
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
    
    // Method to delete a user (only allowed for admins)
    public function destroy($id)
    {
        try {
            // Find the user to delete
            $user = User::findOrFail($id);
    
            // Get the current logged-in user
            $currentUser = auth()->user();
    
            // Check if the current user is an admin (role_id = 1) and trying to delete an admin (role_id = 1)
            if ($currentUser->role_id == 1 && $user->role_id == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin users cannot delete other admins.',
                ], 403); // 403 Forbidden
            }
    
            // Check if the user has role_id = 2 (cashier) and if the current user is an admin
            if ($user->role_id == 2 && $currentUser->role_id != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete users with role_id = 2 (cashiers).',
                    'hint' => 'Only administrators can delete cashiers.'
                ], 403); // 403 Forbidden
            }
    
            // Delete the user record permanently
            $user->delete();
    
            // Delete the user's profile image if it exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'User deleted permanently.',
                'deleted_by' => $currentUser ? $currentUser->fullname : 'System',
                'deleted_at' => now()->toDateTimeString()
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
}
