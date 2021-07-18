<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_name',
        'is_unit',
        'unit_id',
        'user_id',
        'payment_status',
        'description',
    ];

    protected $appends = [
        'transaction_value',
    ];


    public function transaction_details() {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }

    public function getTransactionValueAttribute() {
        $value = 0;
        foreach($this->transaction_details as $item){
            $value += $item->total_price;
        }
        return $value;
    }

    public function restoreAllProductsStock() {
        try {
            $transactionDetails = $this->transaction_details;
            foreach($transactionDetails as $detail) {
                $product = $detail->product;
                if(!$product) continue;
                $product->stock += $detail->quantity;
                $product->save();
            }
            return true;
        } catch (\Exception $e){
            dd($e->getMessage());
            return false;
        }
    }
}
