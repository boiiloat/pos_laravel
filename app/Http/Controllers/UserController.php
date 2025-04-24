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
        // $this->middleware('can:view-users')->only(['index', 'show']);
        $this->middleware('can:create-users')->only('store');
        $this->middleware('can:update-users')->only('update');
        $this->middleware('can:delete-users')->only('destroy');
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => User::where('is_delete', false)
                ->whereNull('deleted_at')
                ->get()
        ]);
        
    }

    public function store(Request $request)
    {
        try {

              // Check permission first
        if ($request->user()->role_id != 1) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied. Only administrators can create new users.',
                'hint' => 'Your role_id: ' . $request->user()->role_id
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

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $userData['profile_image'] = $path;
            }

            $user = User::create($userData);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'created_by' => $request->user()->fullname,
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

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $validated = $request->validate([
                'fullname' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|unique:users,username,'.$id.'|max:255',
                'password' => 'sometimes|string|min:8',
                'role_id' => 'sometimes|exists:roles,id',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $updateData = [];
            if ($request->has('fullname')) $updateData['fullname'] = $validated['fullname'];
            if ($request->has('username')) $updateData['username'] = $validated['username'];
            if ($request->has('password')) $updateData['password'] = Hash::make($validated['password']);
            if ($request->has('role_id')) $updateData['role_id'] = $validated['role_id'];
            
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $updateData['profile_image'] = $path;
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->load('role'),
                'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $currentUser = auth()->user();

            $user->update([
                'is_delete' => true,
                'deleted_at' => now(),
                'delete_by' => $currentUser ? $currentUser->fullname : 'System',
                'delete_date' => now()
            ]);

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