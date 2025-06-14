<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method_name',
        'created_by',
        'deleted_by'
    ];

    protected $casts = [
        'created_date' => 'datetime'
    ];

    // Always load createdBy relationship with minimal fields
    protected $with = ['createdBy'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')
                   ->select(['id', 'fullname']);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')
                   ->select(['id', 'fullname']);
    }

    // If you want to include deletedBy in $with only when needed
    public function scopeWithDeletedBy($query)
    {
        return $query->with('deletedBy');
    }
}