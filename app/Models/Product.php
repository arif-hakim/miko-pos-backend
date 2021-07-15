<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'unit_measurement',
        'base_price',
        'selling_price',
        'unit_id',
        'category_id'
    ];

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function category(){
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function stockHistories(){
        return $this->hasMany(ProductStockHistory::class, 'product_id');
    }
}
