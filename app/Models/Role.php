<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'create_date',
        'create_by',
        'is_delete', 
        'delete_date', 
        'delete_by'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
