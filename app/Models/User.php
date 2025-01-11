<?php

// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullname', 
        'username', 
        'password', 
        'profile_image', 
        'role_id', 
        'create_date', 
        'create_by', 
        'delete_by'
    ];

    protected $casts = [
        'password' => 'hashed', // Automatically hash the password field
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
