<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialStockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'changes',
        'description',
        'source',
        'source_id',
    ];

    public function raw_material() {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
