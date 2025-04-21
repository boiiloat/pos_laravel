<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::with('role')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required',
            'role_id' => 'required|exists:roles,id',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle Profile Image Upload
        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
        } else {
            $profileImagePath = null; // If no image is uploaded
        }

        // Create User
        $user = User::create([
            'fullname' => $request->fullname,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'profile_image' => $profileImagePath,
            'role_id' => $request->role_id,
            'create_date' => now(),
            'create_by' => 'system', // Or any logic to assign this value
        ]);

        return response()->json($user, 201);
    }

    public function show($id)
    {
        return User::with('role')->findOrFail($id);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'is_delete' => true,
            'delete_date' => now(),
            'delete_by' => request()->delete_by,
        ]);
        return response()->json(['message' => 'User soft deleted']);
    }
}
