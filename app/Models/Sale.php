<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'sub_total',
        'discount_type',
        'discount',
        'grand_total',
        'is_paid',
        'status',
        'sale_date',
        'table_id',
        'created_by'
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'is_paid' => 'boolean',
    ];

    // Add the boot method here
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->invoice_number)) {
                $sale->invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
        });
    }
    // In your Sale model
    public function setGrandTotalAttribute($value)
    {
        if ($value !== null) {
            $this->attributes['grand_total'] = $value;
            return;
        }

        $grandTotal = $this->sub_total;

        if ($this->discount_type && $this->discount) {
            if ($this->discount_type === 'fixed') {
                $grandTotal -= $this->discount;
            } else {
                $grandTotal -= ($this->sub_total * $this->discount / 100);
            }
        }

        $this->attributes['grand_total'] = max(0, $grandTotal);
    }

    public function products()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    // App\Models\Sale.php

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

}


