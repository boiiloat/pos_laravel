<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Store a new user with validation
    public function store(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle file upload if profile_image is provided
        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            // Store the image and get the file path if the file is provided
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
        }

        // Create the user
        $user = new User([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'profile_image' => $profileImagePath, // Store the image path if uploaded
            'role_id' => $request->role_id,
            'create_by' => $request->create_by, // 'create_by' is optional, can be null
        ]);

        $user->save();

        // Return the created user, including the full image URL
        $user->profile_image = asset('storage/' . $user->profile_image);

        return response()->json($user, 201);
    }

    // Get user information (for example, user details by ID)
    public function show($id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Return the user data, including the full image URL
        $user->profile_image = asset('storage/' . $user->profile_image);

        return response()->json($user);
    }
}
