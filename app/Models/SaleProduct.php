<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'price',
        'is_free',
        'sale_id',
        'product_id',
        'image',
        'product_name',
        'created_by'
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'price' => 'decimal:2',
        'created_date' => 'datetime'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}