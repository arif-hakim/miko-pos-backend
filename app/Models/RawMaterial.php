<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'stock',
        'unit_measurement',
        'unit_id',
        'raw_material_category_id',
        'description',
        'picture',
        'conversion_id'
    ];

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function conversion(){
        return $this->belongsTo(Conversion::class, 'conversion_id');
    }

    public function stockHistories(){
        return $this->hasMany(RawMaterialStockHistory::class, 'raw_material_category_id');
    }

    public function getPictureAttribute($value) {
        return $value ? config('app.url') . '/storage/raw_materials/' . $value : null;
    }

    public function getDefaultPictureAttribute() {
        return config('app.url') . '/default.png';
    }

    public function raw_material_category() {
        return $this->belongsTo(RawMaterialCategory::class, 'raw_material_category_id');
    }
}
