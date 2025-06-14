<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SalePayment extends Model
{
    protected $fillable = [
        'payment_amount',
        'exchange_rate',
        'payment_method_name',
        'sale_id',
        'payment_method_id',
        'created_by'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'created_date' => 'datetime'
    ];

    protected $with = ['createdBy', 'paymentMethod'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')
                   ->select(['id', 'fullname']);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class)
                   ->select(['id', 'invoice_number']);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class)
                   ->select(['id', 'payment_method_name']);
    }
}