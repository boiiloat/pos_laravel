<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'image',
        'category_id',
        'created_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_date',
        'deleted_date'
    ];

    protected $appends = ['creator_name', 'category_name'];

    // Relationship with category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relationship with creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with deleter
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Accessor for image URL
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/products/' . $this->image);
        }
        return asset('images/default-product.png');
    }

    // Accessor for creator's full name
    public function getCreatorNameAttribute()
    {
        return $this->creator ? $this->creator->fullname : null;
    }

    // Accessor for category name
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : null;
    }
}