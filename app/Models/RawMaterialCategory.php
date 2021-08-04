<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'unit_id',
    ];

    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function raw_materials() {
        return $this->hasMany(RawMaterial::class, 'raw_material_category_id');
    }
}
