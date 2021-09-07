<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductStokHistory;

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
        'tax',
        'officer_name',
        'table_number',
        'employee_id',
        'employee_unit_id',
    ];

    protected $appends = [
        'transaction_value',
        'transaction_base_price',
        'grand_total',
        'profit',
        'change_value',
    ];


    public function transaction_details() {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }

    public function employee() {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function employee_unit() {
        return $this->belongsTo(Unit::class, 'employee_unit_id');
    }

    public function getProfitAttribute() {
        $value = 0;
        foreach($this->transaction_details as $item){
            $value += $item->profit;
        }
        return $value;
    }

    public function getChangeValueAttribute() {
        if (!array_key_exists('paid', $this->attributes) || !$this->attributes['paid']) return null; 
        $value = 0;
        foreach($this->transaction_details as $item){
            $value += $item->total_price;
        }
        return $this->attributes['paid'] - ($value + ( $value * ($this->tax / 100)));
    }

    public function getTransactionValueAttribute() {
        $value = 0;
        foreach($this->transaction_details as $item){
            $value += $item->total_price;
        }
        return $value;
    }

    public function getTransactionBasePriceAttribute() {
        $value = 0;
        foreach($this->transaction_details as $item){
            $value += $item->total_base_price;
        }
        return $value;
    }

    public function getGrandTotalAttribute() {
        $value = 0;
        foreach($this->transaction_details as $item){
            $value += $item->total_price;
        }
        return $value + ( $value * ($this->tax / 100));
    }

    public function restoreAllProductsStock() {
        try {
            \DB::beginTransaction();
            $transactionDetails = $this->transaction_details;
            foreach($transactionDetails as $detail) {
                $product = $detail->product;
                if(!$product) continue;
                
                $productStockHistory = new ProductStockHistory();
                $productStockHistory->product_id = $detail->product_id;
                $productStockHistory->from = $product->stock;
                $productStockHistory['changes'] = $detail->quantity;
                $productStockHistory->to = $product->stock + $detail->quantity;
                $productStockHistory->description = "#" . $this->code . " canceled";
                $productStockHistory->save();
                
                $product->stock += $detail->quantity;
                $product->save();
                $product->restoreRawMaterial();
            }
            \DB::commit();
            return true;
        } catch (\Exception $e){
            \DB::rollback();
            return $e->getMessage();
        }
    }
}
