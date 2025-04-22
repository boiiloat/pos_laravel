<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, HasFactory;
  

    protected $fillable = [
        'fullname', 'username', 'password', 'profile_image',
        'role_id', 'create_date', 'create_by',
        'delete_date', 'delete_by'
    ];

    protected $dates = ['deleted_at']; // Add this for soft deletes

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}