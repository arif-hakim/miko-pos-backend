<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'quantity',
        'profit',
        'price',
        'total_price',
        'items',
        'is_canceled'
    ];

    protected $casts = [
        'items' => 'object'
    ];

    public function transaction() {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
