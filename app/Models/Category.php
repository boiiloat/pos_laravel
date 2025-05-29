<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    // Remove SoftDeletes trait

    protected $fillable = [
        'name',
        'created_by',
        'deleted_by',
        'deleted_date'
    ];

    protected $dates = [
        'deleted_date'
    ];

    // Relationship with the user who created the category
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}