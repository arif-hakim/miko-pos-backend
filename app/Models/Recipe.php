<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'raw_material_id',
        'amount',
        'unit_id',
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function raw_material() {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
