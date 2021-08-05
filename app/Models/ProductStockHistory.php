<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStockHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'from',
        'changes',
        'to',
        'description',
        'source',
        'source_id',
    ];

    protected $appends = [
        'source'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getSourceAttribute() {
        if ($this->attributes['source'] == 'unit') return Unit::whereId($this->attributes['source_id'])->with('branch')->first();
        else return null;
    }
}
