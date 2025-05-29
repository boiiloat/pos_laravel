<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fullname', 
        'username', 
        'password', 
        'profile_image',
        'role_id', 
        'create_date', 
        'create_by',
        'delete_date', 
        'delete_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'create_date' => 'datetime',
        'delete_date' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The name of the "deleted at" column.
     * Since you're using delete_date instead of deleted_at
     */
    const DELETED_AT = 'delete_date';

    /**
     * Get the role that belongs to the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the categories created by this user.
     */
    public function categories()
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    /**
     * Get the products created by this user.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'created_by');
    }
}