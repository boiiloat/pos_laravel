<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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
        try {
            $users = User::where('is_delete', false)
                ->whereNull('deleted_at')
                ->with('role')
                ->get();

            $transformedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'role' => [
                        'id' => $user->role_id,
                        'name' => $user->role->name ?? null
                    ],
                    'profile_image' => $user->profile_image,
                    'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
                    'create_date' => $user->create_date,
                    'create_by' => $user->create_by,
                    'updated_at' => $user->updated_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedUsers,
                'message' => 'Users retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('User fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
                ], Response::HTTP_FORBIDDEN);
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
                'is_delete' => false,  // ✅ FIXED: Explicitly set is_delete to false
                'deleted_at' => null,  // ✅ FIXED: Explicitly set deleted_at to null
                'created_at' => now(), // ✅ FIXED: Set created_at timestamp
                'updated_at' => now()  // ✅ FIXED: Set updated_at timestamp
            ];
    
            // Handle file upload if present
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $userData['profile_image'] = $path;
            }
    
            // Create the new user
            $user = User::create($userData);
            $user->load('role');

            // ✅ FIXED: Log the created user for debugging
            Log::info('New user created:', [
                'id' => $user->id,
                'username' => $user->username,
                'is_delete' => $user->is_delete,
                'deleted_at' => $user->deleted_at,
                'role_id' => $user->role_id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'role' => [
                        'id' => $user->role_id,
                        'name' => $user->role->name ?? null
                    ],
                    'profile_image' => $user->profile_image,
                    'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
                    'create_date' => $user->create_date,
                    'create_by' => $user->create_by,
                    'is_delete' => $user->is_delete, // ✅ FIXED: Include in response for verification
                    'deleted_at' => $user->deleted_at // ✅ FIXED: Include in response for verification
                ],
                'message' => 'User created successfully.',
                'created_by' => $request->user()->fullname,
            ], Response::HTTP_CREATED);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Method to view a specific user (allowed for both admins and non-admins)
    public function show($id)
    {
        try {
            $user = User::where('is_delete', false)
                ->whereNull('deleted_at')
                ->with('role')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'role' => [
                        'id' => $user->role_id,
                        'name' => $user->role->name ?? null
                    ],
                    'profile_image' => $user->profile_image,
                    'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
                    'create_date' => $user->create_date,
                    'create_by' => $user->create_by,
                    'updated_at' => $user->updated_at
                ],
                'message' => 'User retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('User fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Method to update a user
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('is_delete', false)
                ->whereNull('deleted_at')
                ->findOrFail($id);

            $currentUser = $request->user();

            // Check permissions - admins can update anyone, non-admins can only update themselves
            if ($currentUser->role_id != 1 && $currentUser->id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update your own profile unless you are an administrator.'
                ], Response::HTTP_FORBIDDEN);
            }

            // Validation rules
            $rules = [
                'fullname' => 'sometimes|required|string|max:255',
                'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
                'password' => 'sometimes|nullable|string|min:8',
                'role_id' => 'sometimes|required|exists:roles,id',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ];

            // Non-admins cannot change role_id
            if ($currentUser->role_id != 1) {
                unset($rules['role_id']);
            }

            $validated = $request->validate($rules);

            // Prepare update data
            $updateData = [];
            
            if (isset($validated['fullname'])) {
                $updateData['fullname'] = $validated['fullname'];
            }
            
            if (isset($validated['username'])) {
                $updateData['username'] = $validated['username'];
            }
            
            if (isset($validated['password']) && !empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            if (isset($validated['role_id']) && $currentUser->role_id == 1) {
                // Only admins can change roles
                $updateData['role_id'] = $validated['role_id'];
            }

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $updateData['profile_image'] = $request->file('profile_image')->store('profile_images', 'public');
            }

            // Add update tracking
            $updateData['updated_at'] = now();
            $updateData['updated_by'] = $currentUser->fullname;

            // ✅ FIXED: Ensure is_delete remains false during updates
            $updateData['is_delete'] = false;
            $updateData['deleted_at'] = null;

            // Update the user
            $user->update($updateData);
            $user->refresh()->load('role');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'username' => $user->username,
                    'role' => [
                        'id' => $user->role_id,
                        'name' => $user->role->name ?? null
                    ],
                    'profile_image' => $user->profile_image,
                    'profile_image_url' => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
                    'updated_at' => $user->updated_at,
                    'updated_by' => $updateData['updated_by'] ?? null
                ],
                'message' => 'User updated successfully',
                'updated_by' => $currentUser->fullname
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('User update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Method to update a user via POST (for form-data with method spoofing)
    public function updateViaPost(Request $request, $id)
    {
        // This method calls the regular update method
        return $this->update($request, $id);
    }

    // Method to delete a user (only allowed for admins)
    public function destroy($id)
    {
        try {
            // Find the user to delete
            $user = User::findOrFail($id);
    
            // Get the current logged-in user
            $currentUser = auth()->user();

            // Only admins can delete users
            if ($currentUser->role_id != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can delete users.'
                ], Response::HTTP_FORBIDDEN);
            }
    
            // Check if the current user is an admin trying to delete another admin
            if ($currentUser->role_id == 1 && $user->role_id == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin users cannot delete other admins.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Update deletion tracking before deleting
            $user->update([
                'deleted_by' => $currentUser->fullname,
                'deleted_at' => now(),
                'is_delete' => true
            ]);
    
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
            ], Response::HTTP_NO_CONTENT);
    
        } catch (\Exception $e) {
            Log::error('User deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}